<?php
/**
 * Postcolors – Migration von 1.x (Tabelle {prefix}colors) auf 2.0.
 *
 * - analyze(): Probelauf ohne Schreibzugriffe, liefert den Bericht.
 * - run():     führt die Migration aus (idempotent: Personen, die in 2.0 bereits Farben haben,
 *              werden ohne die Option „überschreiben“ übersprungen; Teams mit vorhandenem Slug
 *              bleiben unangetastet).
 *
 * Fehler pro Datensatz werden abgefangen und im Bericht vermerkt, statt abzubrechen.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsMigrateFromV1
{
    public const LEGACY_TABLE = 'colors';
    public const STATE_CACHE = 'postcolors_migration';

    /** Templates von 1.x (werden in allen Template-Sets gelöscht). */
    public const LEGACY_TEMPLATES = ['header_postcolors', 'listen_postcolors', 'listen_postcolors_bit',
        'usercp_postcolor', 'usercp_postcolor_add', 'usercp_postcolors'];

    /** @var bool */
    private $deriveMaster;

    /** @var bool */
    private $overwrite;

    /** @var array<string,int> Auswahl bei Konflikten: Spielername (normalisiert) => pid */
    private $choices;

    /** @var bool */
    private $dryRun = true;

    public function __construct(bool $deriveMaster = true, bool $overwrite = false, array $choices = [])
    {
        $this->deriveMaster = $deriveMaster;
        $this->overwrite = $overwrite;
        $this->choices = [];
        foreach ($choices as $key => $pid) {
            $this->choices[(string)$key] = (int)$pid;
        }
    }

    public static function legacyExists(): bool
    {
        global $db;
        // "colors" ist ein generischer Name: nur als 1.x-Tabelle werten, wenn die Spalten passen
        return $db->table_exists(self::LEGACY_TABLE)
            && $db->field_exists('player', self::LEGACY_TABLE)
            && $db->field_exists('color', self::LEGACY_TABLE)
            && $db->field_exists('style', self::LEGACY_TABLE);
    }

    public static function state(): array
    {
        global $cache;
        $state = $cache->read(self::STATE_CACHE);
        return is_array($state) ? $state : [];
    }

    public function analyze(): array
    {
        $this->dryRun = true;
        return $this->process();
    }

    public function run(): array
    {
        global $cache;
        $this->dryRun = false;
        $report = $this->process();
        if (empty($report['errors'])) {
            $cache->update(self::STATE_CACHE, ['done' => TIME_NOW, 'migrated' => count($report['migrated'])]);
        }
        PostcolorsCacheBuilder::rebuild();
        return $report;
    }

    /**
     * Löscht die Alttabelle (nur nach erfolgreicher Migration).
     */
    public static function dropLegacyTable(): bool
    {
        global $db, $cache;
        $state = self::state();
        if (empty($state['done']) || !self::legacyExists()) {
            return false;
        }
        $db->drop_table(self::LEGACY_TABLE);
        $cache->delete(self::STATE_CACHE);
        return true;
    }

    /* ------------------------------------------------------------------ Ablauf */

    private function process(): array
    {
        global $db, $mybb;

        $report = [
            'dry' => $this->dryRun,
            'errors' => [],
            'fid' => 0,
            'migrated' => [],     // pid => [names[], colors[tid=>color], notes[]]
            'conflicts' => [],    // key => [name, candidates[pid=>label]]
            'skipped' => [],      // [name, style, color, reason]
            'teams' => [],        // [slug, title, status]
            'cleanup' => [],      // [what, count]
            'tags' => [],         // tag => count
        ];

        if (!self::legacyExists()) {
            $report['errors'][] = 'legacy_missing';
            return $report;
        }
        if (!$db->table_exists('postcolors_players')) {
            $report['errors'][] = 'tables_missing';
            return $report;
        }

        // Schritt 2: Profilfeld-Setting (in install() übernommen)
        $fid = (int)($mybb->settings['postcolors_username_fid'] ?? 0);
        $report['fid'] = $fid;
        if ($fid <= 0 || !$db->field_exists('fid' . $fid, 'userfields')) {
            $report['errors'][] = 'fid_missing';
            return $report;
        }

        // Schritt 3: Namen -> Hauptaccounts
        $hasAs = $db->field_exists('as_uid', 'users');
        $byName = [];
        $labels = [];
        $query = $db->query("SELECT u.uid, u.username, " . ($hasAs ? 'u.as_uid' : '0 AS as_uid') . ", f.fid{$fid} AS name
            FROM " . TABLE_PREFIX . "users u
            INNER JOIN " . TABLE_PREFIX . "userfields f ON (f.ufid = u.uid)
            WHERE f.fid{$fid} != ''");
        while ($row = $db->fetch_array($query)) {
            $key = self::key((string)$row['name']);
            if ($key === '') {
                continue;
            }
            $pid = (int)$row['as_uid'] > 0 ? (int)$row['as_uid'] : (int)$row['uid'];
            $byName[$key][$pid] = true;
            if ($pid === (int)$row['uid'] || !isset($labels[$pid])) {
                $labels[$pid] = (string)$row['username'];
            }
        }
        // Benutzernamen der Hauptaccounts für die Auswahl
        if (!empty($labels)) {
            $query = $db->simple_select('users', 'uid, username', 'uid IN (' . implode(',', array_map('intval', array_keys($labels))) . ')');
            while ($row = $db->fetch_array($query)) {
                $labels[(int)$row['uid']] = (string)$row['username'];
            }
        }

        // Schritt 4: Zeilen lesen und prüfen
        $themes = PostcolorsRepository::themes();
        $rows = [];
        $query = $db->simple_select(self::LEGACY_TABLE, '*', '', ['order_by' => 'cid', 'order_dir' => 'DESC']);
        while ($row = $db->fetch_array($query)) {
            $rows[] = $row;
        }

        $players = []; // key => [name, colors[tid=>color]]
        foreach ($rows as $row) {
            try {
                $name = trim((string)($row['player'] ?? ''));
                $style = (int)($row['style'] ?? 0);
                $rawColor = (string)($row['color'] ?? '');
                $key = self::key($name);
                if ($key === '') {
                    $report['skipped'][] = [$name, $style, $rawColor, 'empty_name'];
                    continue;
                }
                $color = PostcolorsThemeHelper::normalizeColor($rawColor);
                if ($color === null || $color === '') {
                    $report['skipped'][] = [$name, $style, $rawColor, 'invalid_color'];
                    continue;
                }
                if (!isset($themes[$style])) {
                    $report['skipped'][] = [$name, $style, $rawColor, 'theme_missing'];
                    continue;
                }
                if (!isset($players[$key])) {
                    $players[$key] = ['name' => $name, 'colors' => []];
                }
                if (isset($players[$key]['colors'][$style])) {
                    // Zeilen sind absteigend nach cid sortiert: der neueste Eintrag gewinnt
                    $report['skipped'][] = [$name, $style, $rawColor, 'duplicate'];
                    continue;
                }
                $players[$key]['colors'][$style] = $color;
            } catch (Throwable $e) {
                $report['skipped'][] = [(string)($row['player'] ?? ''), (int)($row['style'] ?? 0), (string)($row['color'] ?? ''), 'error'];
            }
        }

        // pid zuordnen, mehrere Namen pro pid zusammenführen
        $merged = []; // pid => [names[], colors[tid=>color], notes[]]
        foreach ($players as $key => $player) {
            $candidates = array_keys($byName[$key] ?? []);
            if (empty($candidates)) {
                foreach ($player['colors'] as $tid => $color) {
                    $report['skipped'][] = [$player['name'], $tid, $color, 'player_missing'];
                }
                continue;
            }
            if (count($candidates) > 1) {
                $choice = $this->choices[$key] ?? 0;
                if (!in_array($choice, $candidates, true)) {
                    $options = [];
                    foreach ($candidates as $pid) {
                        $options[$pid] = ($labels[$pid] ?? ('#' . $pid)) . ' (uid ' . $pid . ')';
                    }
                    $report['conflicts'][$key] = ['name' => $player['name'], 'candidates' => $options];
                    continue;
                }
                $candidates = [$choice];
            }
            $pid = (int)$candidates[0];
            if (!isset($merged[$pid])) {
                $merged[$pid] = ['names' => [], 'colors' => [], 'notes' => [], 'label' => $labels[$pid] ?? ''];
            } else {
                $merged[$pid]['notes'][] = 'merged';
            }
            $merged[$pid]['names'][] = $player['name'];
            foreach ($player['colors'] as $tid => $color) {
                if (!isset($merged[$pid]['colors'][$tid])) {
                    $merged[$pid]['colors'][$tid] = $color;
                }
            }
        }

        // Schritt 5: Master ableiten und schreiben
        foreach ($merged as $pid => $entry) {
            try {
                $colors = $entry['colors'];
                if ($this->deriveMaster && !empty($colors)) {
                    $counts = array_count_values($colors);
                    arsort($counts);
                    $master = (string)array_key_first($counts);
                    $final = [0 => $master];
                    foreach ($colors as $tid => $color) {
                        if ($color !== $master) {
                            $final[$tid] = $color;
                        }
                    }
                    if (count($counts) === 1) {
                        $entry['notes'][] = 'master_all';
                    } else {
                        $entry['notes'][] = 'master_frequent';
                    }
                    $colors = $final;
                }
                ksort($colors);
                $entry['colors'] = $colors;

                $existing = PostcolorsRepository::playerColors($pid);
                if (!empty($existing) && !$this->overwrite) {
                    $entry['notes'][] = 'exists';
                    $entry['status'] = 'exists';
                } else {
                    $entry['status'] = 'migrated';
                    if (!$this->dryRun) {
                        if ($this->overwrite) {
                            $db->delete_query('postcolors_colors', 'pid = ' . (int)$pid);
                        }
                        PostcolorsRepository::writePlayerColors($pid, $colors);
                        if (!PostcolorsRepository::player($pid)) {
                            PostcolorsRepository::savePlayer($pid, null, 0, '', '', '');
                        }
                    }
                }
                $report['migrated'][$pid] = $entry;
            } catch (Throwable $e) {
                $entry['status'] = 'error';
                $entry['notes'][] = 'error';
                $report['migrated'][$pid] = $entry;
            }
        }

        // Schritt 6: Teamfarben
        $legacyTeams = include __DIR__ . '/legacy_teams.php';
        $order = 1;
        foreach ((array)$legacyTeams as $slug => $team) {
            try {
                [$title, $tag, $color] = $team;
                $slug = (string)$slug;
                if (PostcolorsRepository::teamBySlug($slug)) {
                    $report['teams'][] = [$slug, $title, $tag, $color, 'exists'];
                    continue;
                }
                $tagNorm = PostcolorsTagRegistry::normalize((string)$tag);
                $tagFree = !$db->fetch_field($db->simple_select('postcolors_teams', 'COUNT(*) AS c',
                    "tag = '" . $db->escape_string($tagNorm) . "'"), 'c');
                $status = $tagFree ? 'imported' : 'imported_without_tag';
                if (!$this->dryRun) {
                    $teamid = PostcolorsRepository::saveTeam(0, (string)$title, $slug, $tagFree ? $tagNorm : '', $order, 1);
                    PostcolorsRepository::writeTeamColors($teamid, [0 => (string)PostcolorsThemeHelper::normalizeColor((string)$color)]);
                }
                $report['teams'][] = [$slug, $title, $tag, $color, $status];
            } catch (Throwable $e) {
                $report['teams'][] = [(string)$slug, '', '', '', 'error'];
            }
            $order++;
        }

        // Schritt 7: Altreste
        $report['cleanup'] = $this->cleanup();

        // Schritt 8: verwendete Tags ohne Zuordnung
        $report['tags'] = $this->unknownTags($merged, $fid, (array)$legacyTeams);

        return $report;
    }

    /** Altreste von 1.x entfernen (bzw. im Probelauf nur zählen). */
    private function cleanup(): array
    {
        global $db;
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

        $result = [];
        $titles = "'" . implode("','", array_map([$db, 'escape_string'], self::LEGACY_TEMPLATES)) . "'";
        $count = (int)$db->fetch_field($db->simple_select('templates', 'COUNT(*) AS c', "title IN ({$titles})"), 'c');
        $result[] = ['templates', $count];
        if (!$this->dryRun && $count > 0) {
            $db->delete_query('templates', "title IN ({$titles})");
        }

        $count = (int)$db->fetch_field($db->simple_select('templates', 'COUNT(*) AS c',
            "title = 'headerinclude' AND template LIKE '%{\$postcolors}%'"), 'c');
        $result[] = ['headerinclude', $count];
        if (!$this->dryRun && $count > 0) {
            find_replace_templatesets('headerinclude', '#\{\$postcolors\}\n?#', '', 0);
        }

        $count = (int)$db->fetch_field($db->simple_select('templates', 'COUNT(*) AS c',
            "title = 'usercp_nav_rpg' AND template LIKE '%action=postcolor%'"), 'c');
        $result[] = ['usercp_nav_rpg', $count];
        if (!$this->dryRun && $count > 0) {
            // Nur Zeilen mit dem alten Link (action=postcolor ohne "s"), nicht den neuen
            find_replace_templatesets('usercp_nav_rpg', '#^[^\n]*action=postcolor(?!s)[^\n]*\n?#m', '', 0);
        }

        $count = (int)$db->fetch_field($db->simple_select('settings', 'COUNT(*) AS c', "name = 'postcolors_activated'"), 'c');
        $result[] = ['setting', $count];
        if (!$this->dryRun && $count > 0) {
            $db->delete_query('settings', "name = 'postcolors_activated'");
            rebuild_settings();
        }
        return $result;
    }

    /**
     * Sucht in Beiträgen und PNs nach Tags der Form <name>, die keinem Spieler*innen- oder
     * Team-Tag zugeordnet sind (batchweise).
     *
     * @return array<string,int> tag => Anzahl Treffer
     */
    private function unknownTags(array $merged, int $fid, array $legacyTeams): array
    {
        global $db;

        // Künftige Tag-Liste: aktueller Cache + migrierte Personen + importierte Teams
        $data = PostcolorsCacheBuilder::build();
        $known = $data['tags'];
        $names = PostcolorsRepository::playerNames(array_keys($merged), $fid);
        foreach ($names as $name) {
            $known[PostcolorsTagRegistry::normalize($name['profile_name'])] = true;
        }
        foreach ($legacyTeams as $team) {
            $known[PostcolorsTagRegistry::normalize((string)($team[1] ?? ''))] = true;
        }
        $query = $db->simple_select('postcolors_teams', 'tag');
        while ($row = $db->fetch_array($query)) {
            $known[(string)$row['tag']] = true;
        }

        $found = [];
        $pattern = '#<(' . PostcolorsTagRegistry::PATTERN . ')>#u';
        foreach ([['posts', 'pid'], ['privatemessages', 'pmid']] as [$table, $idField]) {
            $last = 0;
            do {
                $query = $db->simple_select($table, "{$idField}, message",
                    "{$idField} > {$last} AND message LIKE '%<%>%'", ['order_by' => $idField, 'limit' => 1000]);
                $rows = 0;
                while ($row = $db->fetch_array($query)) {
                    $rows++;
                    $last = (int)$row[$idField];
                    if (!preg_match_all($pattern, (string)$row['message'], $m)) {
                        continue;
                    }
                    foreach ($m[1] as $tag) {
                        $tag = PostcolorsTagRegistry::normalize($tag);
                        if (PostcolorsTagRegistry::isReserved($tag) || isset($known[$tag])) {
                            continue;
                        }
                        $found[$tag] = ($found[$tag] ?? 0) + 1;
                    }
                }
            } while ($rows === 1000);
        }
        arsort($found);
        return $found;
    }

    /** Vergleichsschlüssel für Namen (getrimmt, Groß/Klein egal). */
    public static function key(string $name): string
    {
        $name = trim($name);
        return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    }
}
