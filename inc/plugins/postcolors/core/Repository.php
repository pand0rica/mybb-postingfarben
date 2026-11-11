<?php
/**
 * Postcolors – Datenbankzugriffe.
 *
 * Konvention: Die Methoden escapen alle Werte selbst (Aufrufer übergeben Rohwerte).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsRepository
{
    public const T_PLAYERS = 'postcolors_players';
    public const T_COLORS = 'postcolors_colors';
    public const T_TEAMS = 'postcolors_teams';
    public const T_TEAMCOLORS = 'postcolors_teamcolors';

    /* ---------------------------------------------------------------- Themes */

    /**
     * Alle auswählbaren Themes (ohne Master-Theme), sortiert nach Name.
     *
     * @return array<int, array{tid:int,name:string,pid:int,allowedgroups:string}>
     */
    public static function themes(): array
    {
        global $db;
        $themes = [];
        $query = $db->simple_select('themes', 'tid, name, pid, allowedgroups', 'pid != 0', ['order_by' => 'name']);
        while ($row = $db->fetch_array($query)) {
            $themes[(int)$row['tid']] = [
                'tid' => (int)$row['tid'],
                'name' => (string)$row['name'],
                'pid' => (int)$row['pid'],
                'allowedgroups' => (string)$row['allowedgroups'],
            ];
        }
        return $themes;
    }

    /* ---------------------------------------------------------------- Spieler*innen */

    public static function player(int $pid): ?array
    {
        global $db;
        $row = $db->fetch_array($db->simple_select(self::T_PLAYERS, '*', 'pid = ' . $pid));
        return $row ?: null;
    }

    /** @return array<int, string> tid => #RRGGBB */
    public static function playerColors(int $pid): array
    {
        global $db;
        $colors = [];
        $query = $db->simple_select(self::T_COLORS, 'tid, color', 'pid = ' . $pid);
        while ($row = $db->fetch_array($query)) {
            $colors[(int)$row['tid']] = (string)$row['color'];
        }
        return $colors;
    }

    public static function savePlayer(int $pid, ?string $customTag, int $speechEnabled, string $open, string $close): void
    {
        global $db;
        // replace_query() schreibt null als '' – custom_tag ist UNIQUE, daher eigene Query mit NULL.
        $tag = ($customTag === null || $customTag === '') ? 'NULL' : "'" . $db->escape_string($customTag) . "'";
        $db->write_query("REPLACE INTO " . TABLE_PREFIX . self::T_PLAYERS . "
            (pid, custom_tag, speech_enabled, quote_open, quote_close, updated)
            VALUES (" . $pid . ", {$tag}, " . ($speechEnabled ? 1 : 0) . ", '"
            . $db->escape_string($open) . "', '" . $db->escape_string($close) . "', " . (int)TIME_NOW . ")");
    }

    /**
     * Schreibt Farben: '' löscht die Zeile, #RRGGBB setzt sie. Andere tids bleiben unberührt.
     *
     * @param array<int, string> $colors tid => '' | #RRGGBB
     */
    public static function writePlayerColors(int $pid, array $colors): void
    {
        self::writeColors(self::T_COLORS, 'pid', $pid, $colors);
    }

    /**
     * Alle Spieler*innen mit Einstellungen oder Farben (für Cache und ACP).
     *
     * @return array<int, array{pid:int, custom_tag:?string, speech_enabled:int, quote_open:string, quote_close:string, colors: array<int,string>}>
     */
    public static function allPlayers(): array
    {
        global $db;
        $players = [];
        $query = $db->simple_select(self::T_COLORS, 'pid, tid, color');
        while ($row = $db->fetch_array($query)) {
            $pid = (int)$row['pid'];
            if (!isset($players[$pid])) {
                $players[$pid] = self::emptyPlayer($pid);
            }
            $players[$pid]['colors'][(int)$row['tid']] = (string)$row['color'];
        }
        $query = $db->simple_select(self::T_PLAYERS, '*');
        while ($row = $db->fetch_array($query)) {
            $pid = (int)$row['pid'];
            if (!isset($players[$pid])) {
                $players[$pid] = self::emptyPlayer($pid);
            }
            $players[$pid]['custom_tag'] = $row['custom_tag'] !== null ? (string)$row['custom_tag'] : null;
            $players[$pid]['speech_enabled'] = (int)$row['speech_enabled'];
            $players[$pid]['quote_open'] = (string)$row['quote_open'];
            $players[$pid]['quote_close'] = (string)$row['quote_close'];
        }
        return $players;
    }

    private static function emptyPlayer(int $pid): array
    {
        return ['pid' => $pid, 'custom_tag' => null, 'speech_enabled' => 0, 'quote_open' => '',
            'quote_close' => '', 'colors' => []];
    }

    /** @param int[] $pids */
    public static function deletePlayers(array $pids): void
    {
        global $db;
        $pids = array_filter(array_map('intval', $pids));
        if (empty($pids)) {
            return;
        }
        $in = implode(',', $pids);
        $db->delete_query(self::T_COLORS, "pid IN ({$in})");
        $db->delete_query(self::T_PLAYERS, "pid IN ({$in})");
    }

    /**
     * Anzeigename (Profilfeld des Hauptaccounts, sonst Benutzername) und Profilfeld-Wert.
     *
     * @param int[] $pids
     * @return array<int, array{username:string, profile_name:string, display:string}>
     */
    public static function playerNames(array $pids, int $fid): array
    {
        global $db;
        $pids = array_values(array_filter(array_map('intval', $pids)));
        $names = [];
        if (empty($pids)) {
            return $names;
        }
        $in = implode(',', $pids);
        $field = ($fid > 0 && $db->field_exists('fid' . $fid, 'userfields')) ? 'f.fid' . $fid : "''";
        $query = $db->query("SELECT u.uid, u.username, {$field} AS profile_name
            FROM " . TABLE_PREFIX . "users u
            LEFT JOIN " . TABLE_PREFIX . "userfields f ON (f.ufid = u.uid)
            WHERE u.uid IN ({$in})");
        while ($row = $db->fetch_array($query)) {
            $profile = trim((string)($row['profile_name'] ?? ''));
            $names[(int)$row['uid']] = [
                'username' => (string)$row['username'],
                'profile_name' => $profile,
                'display' => $profile !== '' ? $profile : (string)$row['username'],
            ];
        }
        return $names;
    }

    /* ---------------------------------------------------------------- Teams */

    /** @return array<int, array> teamid => Zeile inkl. 'colors' */
    public static function teams(bool $onlyActive = false): array
    {
        global $db;
        $teams = [];
        $where = $onlyActive ? 'active = 1' : '';
        $query = $db->simple_select(self::T_TEAMS, '*', $where, ['order_by' => 'disporder, title']);
        while ($row = $db->fetch_array($query)) {
            $row['teamid'] = (int)$row['teamid'];
            $row['disporder'] = (int)$row['disporder'];
            $row['active'] = (int)$row['active'];
            $row['colors'] = [];
            $teams[$row['teamid']] = $row;
        }
        if (!empty($teams)) {
            $query = $db->simple_select(self::T_TEAMCOLORS, 'teamid, tid, color',
                'teamid IN (' . implode(',', array_keys($teams)) . ')');
            while ($row = $db->fetch_array($query)) {
                if (isset($teams[(int)$row['teamid']])) {
                    $teams[(int)$row['teamid']]['colors'][(int)$row['tid']] = (string)$row['color'];
                }
            }
        }
        return $teams;
    }

    public static function team(int $teamid): ?array
    {
        $teams = self::teams();
        return $teams[$teamid] ?? null;
    }

    public static function teamBySlug(string $slug): ?array
    {
        global $db;
        $row = $db->fetch_array($db->simple_select(self::T_TEAMS, '*', "slug = '" . $db->escape_string($slug) . "'"));
        return $row ?: null;
    }

    /** Legt ein Team an oder aktualisiert es; liefert die teamid. */
    public static function saveTeam(int $teamid, string $title, string $slug, string $tag, int $disporder, int $active): int
    {
        global $db;
        $data = [
            'title' => $db->escape_string($title),
            'slug' => $db->escape_string($slug),
            'tag' => $db->escape_string($tag),
            'disporder' => $disporder,
            'active' => $active ? 1 : 0,
        ];
        if ($teamid > 0) {
            $db->update_query(self::T_TEAMS, $data, 'teamid = ' . $teamid);
            return $teamid;
        }
        return (int)$db->insert_query(self::T_TEAMS, $data);
    }

    /** @param array<int, string> $colors tid => '' | #RRGGBB */
    public static function writeTeamColors(int $teamid, array $colors): void
    {
        self::writeColors(self::T_TEAMCOLORS, 'teamid', $teamid, $colors);
    }

    public static function deleteTeam(int $teamid): void
    {
        global $db;
        $db->delete_query(self::T_TEAMCOLORS, 'teamid = ' . $teamid);
        $db->delete_query(self::T_TEAMS, 'teamid = ' . $teamid);
    }

    public static function setTeamOrder(int $teamid, int $disporder): void
    {
        global $db;
        $db->update_query(self::T_TEAMS, ['disporder' => $disporder], 'teamid = ' . $teamid);
    }

    /* ---------------------------------------------------------------- Allgemein */

    public static function deleteThemeColors(int $tid): void
    {
        global $db;
        if ($tid <= 0) {
            return;
        }
        $db->delete_query(self::T_COLORS, 'tid = ' . $tid);
        $db->delete_query(self::T_TEAMCOLORS, 'tid = ' . $tid);
    }

    /** @param array<int, string> $colors */
    private static function writeColors(string $table, string $key, int $id, array $colors): void
    {
        global $db;
        foreach ($colors as $tid => $color) {
            $tid = (int)$tid;
            if ($tid < 0) {
                continue;
            }
            if ($color === '') {
                $db->delete_query($table, "{$key} = {$id} AND tid = {$tid}");
                continue;
            }
            if (!PostcolorsThemeHelper::isValidColor($color)) {
                continue;
            }
            $db->replace_query($table, [
                $key => $id,
                'tid' => $tid,
                'color' => $db->escape_string(strtoupper($color)),
            ], '', false);
        }
    }
}
