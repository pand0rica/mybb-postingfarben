<?php
/**
 * Postcolors – baut den Datacache "postcolors".
 *
 * Wird nur bei Änderungen neu gebaut (UserCP, ACP, Migration, Themes, Profilfeld, Settings).
 * Im normalen Seitenaufruf wird ausschließlich gelesen – keine Queries.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsCacheBuilder
{
    public const CACHE_NAME = 'postcolors';

    /**
     * Plugin-Settings frisch aus der Datenbank (beim Speichern im ACP ist $mybb->settings noch alt).
     *
     * @return array<string, string>
     */
    public static function settings(): array
    {
        global $db;
        $settings = [];
        $query = $db->simple_select('settings', 'name, value', "name LIKE 'postcolors\\_%'");
        while ($row = $db->fetch_array($query)) {
            $settings[(string)$row['name']] = (string)$row['value'];
        }
        return $settings;
    }

    public static function rebuild(): array
    {
        global $cache;
        $data = self::build();
        $cache->update(self::CACHE_NAME, $data);
        return $data;
    }

    public static function build(): array
    {
        $settings = self::settings();
        $fid = (int)($settings['postcolors_username_fid'] ?? 0);
        $bold = !empty($settings['postcolors_bold']);
        $elements = !empty($settings['postcolors_element_selectors']);

        $themes = PostcolorsRepository::themes();
        $teams = PostcolorsRepository::teams();
        $players = PostcolorsRepository::allPlayers();
        $names = PostcolorsRepository::playerNames(array_keys($players), $fid);

        $tagInput = [];
        foreach ($players as $pid => $player) {
            if (empty($player['colors'])) {
                continue; // ohne Farbe kein Tag
            }
            $tagInput[] = [
                'pid' => $pid,
                'custom_tag' => $player['custom_tag'],
                'profile_name' => $names[$pid]['profile_name'] ?? '',
            ];
        }
        $resolved = PostcolorsTagRegistry::resolve(array_values($teams), $tagInput);

        // Einträge, die färben können: key => [colors, tag]
        $entries = [];
        $known = [];
        $speech = [];
        foreach ($players as $pid => $player) {
            if (empty($player['colors'])) {
                continue;
            }
            $known[$pid] = 1;
            $entries['p' . $pid] = ['colors' => $player['colors'], 'tag' => $resolved['player_tags'][$pid] ?? ''];
            if (!empty($player['speech_enabled']) && $player['quote_open'] !== '' && $player['quote_close'] !== '') {
                $speech[$pid] = [$player['quote_open'], $player['quote_close']];
            }
        }
        $slugs = [];
        $teamList = [];
        foreach ($teams as $teamid => $team) {
            if (empty($team['active'])) {
                continue;
            }
            $entries['t' . $teamid] = ['colors' => $team['colors'], 'tag' => $resolved['team_tags'][$teamid] ?? ''];
            $slugs[(string)$team['slug']] = $teamid;
            $teamList[] = [
                'id' => $teamid,
                'slug' => (string)$team['slug'],
                'title' => (string)$team['title'],
                'tag' => $resolved['team_tags'][$teamid] ?? '',
            ];
        }

        $css = [];
        foreach (array_merge([0], array_keys($themes)) as $tid) {
            $css[(int)$tid] = self::css((int)$tid, $entries, $bold, $elements);
        }

        $bg = PostcolorsThemeHelper::backgrounds(
            $themes,
            (string)($settings['postcolors_bg_variable'] ?? ''),
            (string)($settings['postcolors_bg_fallback'] ?? '#FFFFFF')
        );

        return [
            'version' => 2,
            'built' => TIME_NOW,
            'css' => $css,
            'tags' => $resolved['tags'],
            'player_tags' => $resolved['player_tags'],
            'slugs' => $slugs,
            'teams' => $teamList,
            'known' => $known,
            'speech' => $speech,
            'bg' => $bg,
            'conflicts' => $resolved['conflicts'],
        ];
    }

    /**
     * CSS für ein Theme. Farben sind validiert (#RRGGBB), Tags per Whitelist-Regex geprüft –
     * es kann daher nichts aus dem <style>-Block ausbrechen.
     */
    private static function css(int $tid, array $entries, bool $bold, bool $elements): string
    {
        $rules = [];
        $boldSelectors = $bold ? ['.pc'] : [];
        foreach ($entries as $key => $entry) {
            $color = $entry['colors'][$tid] ?? ($entry['colors'][0] ?? '');
            if (!PostcolorsThemeHelper::isValidColor($color)) {
                continue;
            }
            $selectors = ['.pc-' . $key];
            $tag = (string)$entry['tag'];
            if ($elements && $tag !== '' && PostcolorsTagRegistry::isValid($tag)) {
                $selectors[] = $tag;
                if ($bold) {
                    $boldSelectors[] = $tag;
                }
            }
            $rules[] = implode(',', $selectors) . '{color:' . strtoupper($color) . '}';
        }
        if (!empty($boldSelectors)) {
            array_unshift($rules, implode(',', $boldSelectors) . '{font-weight:bold}');
        }
        return implode("\n", $rules);
    }
}
