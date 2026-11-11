<?php
/**
 * Postcolors – Übersicht misc.php?action=postcolors
 *
 * Teamfarben einzeln, darunter alle Spieler*innen mit ihrer effektiven Farbe im gewählten
 * Theme (Theme-Farbe oder Master). Optional anderes erlaubtes Theme per ?tid=.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function postcolors_misc_page(): void
{
    global $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

    $lang->load('postcolors');
    if (!postcolors_enabled()) {
        error($lang->postcolors_disabled);
    }

    $groups = PostcolorsThemeHelper::userGroups($mybb->user);
    $themes = array_filter(PostcolorsRepository::themes(), static function (array $t) use ($groups): bool {
        return PostcolorsThemeHelper::themeAllowed($t, $groups);
    });

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
    if (!isset($themes[$tid])) {
        $tid = (int)($theme['tid'] ?? 0);
    }
    $themeName = isset($themes[$tid]) ? $themes[$tid]['name'] : (string)($theme['name'] ?? '');

    $data = postcolors_data();

    // Teams
    $teamrows = '';
    $trow = 'trow1';
    foreach (PostcolorsRepository::teams(true) as $team) {
        $color = $team['colors'][$tid] ?? ($team['colors'][0] ?? '');
        if (!PostcolorsThemeHelper::isValidColor($color)) {
            continue;
        }
        $row_color = strtoupper($color);
        $row_name = htmlspecialchars_uni((string)$team['title']);
        $tag = '';
        foreach ($data['teams'] ?? [] as $cached) {
            if ((int)$cached['id'] === (int)$team['teamid']) {
                $tag = (string)$cached['tag'];
            }
        }
        $row_tag = $tag !== '' ? htmlspecialchars_uni('<' . $tag . '>…</' . $tag . '>') : '';
        $row_mycode = htmlspecialchars_uni('[pc=' . $team['slug'] . ']…[/pc]');
        eval('$teamrows .= "' . $templates->get('postcolors_list_team') . '";');
        $trow = alt_trow();
    }
    if ($teamrows === '') {
        $empty_text = $lang->postcolors_list_no_teams;
        eval('$teamrows = "' . $templates->get('postcolors_list_empty') . '";');
    }

    // Spieler*innen
    $players = [];
    $all = PostcolorsRepository::allPlayers();
    $names = PostcolorsRepository::playerNames(array_keys($all), (int)($mybb->settings['postcolors_username_fid'] ?? 0));
    foreach ($all as $pid => $player) {
        $color = $player['colors'][$tid] ?? ($player['colors'][0] ?? '');
        if (!PostcolorsThemeHelper::isValidColor($color) || !isset($names[$pid])) {
            continue;
        }
        $players[] = ['pid' => $pid, 'name' => $names[$pid]['display'], 'color' => strtoupper($color)];
    }
    usort($players, static function (array $a, array $b): int {
        return strcasecmp($a['name'], $b['name']);
    });

    $playerrows = '';
    $trow = 'trow1';
    foreach ($players as $player) {
        $row_color = $player['color'];
        $row_name = htmlspecialchars_uni($player['name']);
        $tag = (string)($data['player_tags'][$player['pid']] ?? '');
        $row_tag = $tag !== '' ? htmlspecialchars_uni('<' . $tag . '>…</' . $tag . '>') : '';
        eval('$playerrows .= "' . $templates->get('postcolors_list_player') . '";');
        $trow = alt_trow();
    }
    if ($playerrows === '') {
        $empty_text = $lang->postcolors_list_no_players;
        eval('$playerrows = "' . $templates->get('postcolors_list_empty') . '";');
    }

    // Theme-Auswahl
    $themeselect = '';
    if (count($themes) > 1) {
        $theme_options = '';
        foreach ($themes as $t) {
            $selected = (int)$t['tid'] === $tid ? ' selected="selected"' : '';
            $theme_options .= '<option value="' . (int)$t['tid'] . '"' . $selected . '>' . htmlspecialchars_uni($t['name']) . '</option>';
        }
        eval('$themeselect = "' . $templates->get('postcolors_list_themeselect') . '";');
    }

    $list_note = $lang->sprintf($lang->postcolors_list_note, htmlspecialchars_uni($themeName));

    add_breadcrumb($lang->postcolors_list_title, 'misc.php?action=postcolors');
    $page = '';
    eval('$page = "' . $templates->get('postcolors_list') . '";');
    output_page($page);
    exit;
}
