<?php
/**
 * Postcolors – UserCP-Seite usercp.php?action=postcolors
 *
 * Ein Formular, ein Speichern-Button: Master-Farbe, Farbe pro erlaubtem Theme, eigener Tag und
 * Einstellungen für die automatische Färbung wörtlicher Rede. Gespeichert wird immer auf die pid
 * (Hauptaccount), daher wirkt eine Änderung sofort in allen verknüpften Accounts.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Voreingestellte Anführungszeichen-Paare. */
function postcolors_quote_presets(): array
{
    return [
        'de' => ["\u{201E}", "\u{201C}"],
        'guillemets' => ["\u{00BB}", "\u{00AB}"],
        'guillemets_fr' => ["\u{00AB}", "\u{00BB}"],
        'straight' => ['"', '"'],
        'en' => ["\u{201C}", "\u{201D}"],
        'single' => ["'", "'"],
        'de_single' => ["\u{201A}", "\u{2018}"],
    ];
}

/** Ermittelt den Preset-Schlüssel zu einem Paar ('custom', falls keins passt). */
function postcolors_quote_preset_key(string $open, string $close): string
{
    foreach (postcolors_quote_presets() as $key => $pair) {
        if ($pair[0] === $open && $pair[1] === $close) {
            return $key;
        }
    }
    return ($open === '' && $close === '') ? 'de' : 'custom';
}

/** Prüft ein eigenes Anführungszeichen (1–3 Zeichen, kein Leerraum/Steuerzeichen). */
function postcolors_valid_quote_mark(string $mark): bool
{
    $len = function_exists('mb_strlen') ? mb_strlen($mark, 'UTF-8') : strlen($mark);
    return $len >= 1 && $len <= 3 && !preg_match('/[\s\x00-\x1F\x7F]/u', $mark);
}

function postcolors_usercp_page(): void
{
    global $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $usercpnav, $db;

    $lang->load('postcolors');

    if (!postcolors_enabled()) {
        error($lang->postcolors_disabled);
    }
    if (empty($mybb->user['uid'])) {
        error_no_permission();
    }
    $groups = PostcolorsThemeHelper::userGroups($mybb->user);
    if (!PostcolorsThemeHelper::groupsAllowed($groups, (string)($mybb->settings['postcolors_usergroups'] ?? '-1'))) {
        error_no_permission();
    }

    $pid = postcolors_resolve_pid((int)$mybb->user['uid']);
    $themes = array_filter(PostcolorsRepository::themes(), static function (array $t) use ($groups): bool {
        return PostcolorsThemeHelper::themeAllowed($t, $groups);
    });
    $data = postcolors_data();
    $speechAllowed = !empty($mybb->settings['postcolors_speech_allowed']);

    $player = PostcolorsRepository::player($pid) ?? [
        'custom_tag' => null, 'speech_enabled' => 0, 'quote_open' => '', 'quote_close' => '',
    ];
    $colors = PostcolorsRepository::playerColors($pid);

    $form = [
        'colors' => $colors,
        'custom_tag' => (string)($player['custom_tag'] ?? ''),
        'speech_enabled' => (int)$player['speech_enabled'],
        'quote_open' => (string)$player['quote_open'],
        'quote_close' => (string)$player['quote_close'],
    ];
    $errors = [];

    if ($mybb->get_input('action') === 'do_postcolors' && $mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        // Farben: nur Master (0) und erlaubte Themes
        $input = $mybb->get_input('color', MyBB::INPUT_ARRAY);
        $newColors = [];
        foreach (array_merge([0], array_keys($themes)) as $tid) {
            $raw = isset($input[$tid]) && is_string($input[$tid]) ? $input[$tid] : '';
            $color = PostcolorsThemeHelper::normalizeColor($raw);
            if ($color === null) {
                $errors[] = $lang->sprintf($lang->postcolors_error_color,
                    htmlspecialchars_uni($tid === 0 ? $lang->postcolors_master : $themes[$tid]['name']));
                $form['colors'][$tid] = $raw;
                continue;
            }
            $newColors[$tid] = $color;
            if ($color === '') {
                unset($form['colors'][$tid]);
            } else {
                $form['colors'][$tid] = $color;
            }
        }

        // Eigener Tag
        $customTag = PostcolorsTagRegistry::normalize($mybb->get_input('custom_tag'));
        $form['custom_tag'] = $customTag;
        if ($customTag !== '') {
            if (!PostcolorsTagRegistry::isWellFormed($customTag)) {
                $errors[] = $lang->postcolors_error_tag_format;
            } elseif (PostcolorsTagRegistry::isReserved($customTag)) {
                $errors[] = $lang->postcolors_error_tag_reserved;
            } elseif (postcolors_tag_taken($customTag, 'p' . $pid, $data)) {
                $errors[] = $lang->postcolors_error_tag_taken;
            }
        }

        // Wörtliche Rede
        $speechEnabled = $speechAllowed ? ($mybb->get_input('speech_enabled', MyBB::INPUT_INT) === 1 ? 1 : 0) : (int)$player['speech_enabled'];
        $open = (string)$player['quote_open'];
        $close = (string)$player['quote_close'];
        if ($speechAllowed) {
            $preset = $mybb->get_input('quote_preset');
            $presets = postcolors_quote_presets();
            if (isset($presets[$preset])) {
                [$open, $close] = $presets[$preset];
            } elseif ($preset === 'custom') {
                $open = trim($mybb->get_input('quote_open'));
                $close = trim($mybb->get_input('quote_close'));
                if (!postcolors_valid_quote_mark($open) || !postcolors_valid_quote_mark($close)) {
                    $errors[] = $lang->postcolors_error_quotes;
                }
            } else {
                [$open, $close] = $presets['de'];
            }
        }
        $form['speech_enabled'] = $speechEnabled;
        $form['quote_open'] = $open;
        $form['quote_close'] = $close;

        if (empty($errors)) {
            $db->write_query('START TRANSACTION');
            PostcolorsRepository::writePlayerColors($pid, $newColors);
            PostcolorsRepository::savePlayer($pid, $customTag !== '' ? $customTag : null, $speechEnabled, $open, $close);
            $db->write_query('COMMIT');
            PostcolorsCacheBuilder::rebuild();
            redirect('usercp.php?action=postcolors', $lang->postcolors_saved);
        }
    }

    add_breadcrumb($lang->nav_usercp, 'usercp.php');
    add_breadcrumb($lang->postcolors_usercp_title, 'usercp.php?action=postcolors');

    $errors = !empty($errors) ? inline_error($errors) : '';
    $fallbackBg = PostcolorsThemeHelper::safeCssColor((string)($mybb->settings['postcolors_bg_fallback'] ?? '')) ?? '#FFFFFF';
    $master = (string)($form['colors'][0] ?? '');
    $masterValid = PostcolorsThemeHelper::isValidColor($master);

    // Zeilen: Master + Themes
    $rows = '';
    $trow = 'trow1';
    $entries = [0 => ['name' => $lang->postcolors_master, 'bg' => $fallbackBg]];
    foreach ($themes as $tid => $t) {
        $entries[$tid] = ['name' => $t['name'], 'bg' => (string)($data['bg'][$tid] ?? $fallbackBg)];
    }
    foreach ($entries as $tid => $entry) {
        $value = (string)($form['colors'][$tid] ?? '');
        $valid = PostcolorsThemeHelper::isValidColor($value);
        $effective = $valid ? strtoupper($value) : ($tid !== 0 && $masterValid ? strtoupper($master) : '');

        $row_tid = (int)$tid;
        $row_name = htmlspecialchars_uni($entry['name']);
        $row_bg = htmlspecialchars_uni($entry['bg']);
        $row_value = htmlspecialchars_uni($value);
        $row_picker = $valid ? strtoupper($value) : ($effective !== '' ? $effective : '#000000');
        $row_effective = $effective !== '' ? $effective : 'inherit';
        $row_placeholder = $tid === 0 ? $lang->postcolors_none : ($masterValid ? strtoupper($master) : $lang->postcolors_none);
        $row_hint = $tid === 0
            ? '<br /><span class="smalltext">' . $lang->postcolors_master_desc . '</span>'
            : '<br /><span class="smalltext postcolors_uses_master"' . ($valid ? ' hidden' : '') . '>' . $lang->postcolors_uses_master . '</span>';
        $row_reset_label = $tid === 0 ? $lang->postcolors_clear : $lang->postcolors_reset;
        $row_role = $tid === 0 ? 'master' : 'theme';
        eval('$rows .= "' . $templates->get('postcolors_usercp_row') . '";');
        $trow = alt_trow();
    }

    // Tag-Bereich
    $fid = (int)($mybb->settings['postcolors_username_fid'] ?? 0);
    $names = PostcolorsRepository::playerNames([$pid], $fid);
    $profileName = (string)($names[$pid]['profile_name'] ?? '');
    $currentTag = (string)($data['player_tags'][$pid] ?? '');
    $tag_current = $currentTag !== ''
        ? '<code>&lt;' . htmlspecialchars_uni($currentTag) . '&gt;…&lt;/' . htmlspecialchars_uni($currentTag) . '&gt;</code>'
        : '<em>' . $lang->postcolors_tag_none . '</em>';
    $tag_notice = '';
    foreach ($data['conflicts'] ?? [] as $conflict) {
        if ((int)$conflict['pid'] !== $pid) {
            continue;
        }
        $reasonKey = 'postcolors_conflict_' . $conflict['reason'];
        $tag_notice .= '<div class="postcolors_notice">' . $lang->sprintf($lang->$reasonKey ?? '', htmlspecialchars_uni((string)$conflict['tag'])) . '</div>';
    }
    if ($currentTag === '' && $profileName !== '' && $tag_notice === '' && empty($form['colors'])) {
        $tag_notice = '<div class="postcolors_notice">' . $lang->postcolors_tag_needs_color . '</div>';
    }
    $profile_name = $profileName !== '' ? htmlspecialchars_uni($profileName) : '<em>' . $lang->postcolors_tag_none . '</em>';
    $custom_tag = htmlspecialchars_uni($form['custom_tag']);
    $tagsection = '';
    eval('$tagsection = "' . $templates->get('postcolors_usercp_tag') . '";');

    // Wörtliche Rede
    $speechsection = '';
    if ($speechAllowed) {
        $speech_checked = $form['speech_enabled'] ? ' checked="checked"' : '';
        $presetKey = postcolors_quote_preset_key($form['quote_open'], $form['quote_close']);
        $preset_options = '';
        foreach (postcolors_quote_presets() as $key => $pair) {
            $label = htmlspecialchars_uni($pair[0] . '…' . $pair[1]) . ' – ' . $lang->{'postcolors_preset_' . $key};
            $selected = $presetKey === $key ? ' selected="selected"' : '';
            $preset_options .= '<option value="' . $key . '"' . $selected . '>' . $label . '</option>';
        }
        $preset_options .= '<option value="custom"' . ($presetKey === 'custom' ? ' selected="selected"' : '') . '>'
            . $lang->postcolors_preset_custom . '</option>';
        $quote_open = htmlspecialchars_uni($presetKey === 'custom' ? $form['quote_open'] : '');
        $quote_close = htmlspecialchars_uni($presetKey === 'custom' ? $form['quote_close'] : '');
        eval('$speechsection = "' . $templates->get('postcolors_usercp_speech') . '";');
    }

    $as_note = ($pid !== (int)$mybb->user['uid']) ? '<div class="postcolors_notice">' . $lang->postcolors_as_note . '</div>' : '';

    $page = '';
    eval('$page = "' . $templates->get('postcolors_usercp') . '";');
    output_page($page);
    exit;
}

/** Ist ein Tag bereits an einen anderen Eintrag (Team, andere Person) vergeben? */
function postcolors_tag_taken(string $tag, string $ownKey, array $data): bool
{
    global $db;

    if (isset($data['tags'][$tag]) && $data['tags'][$tag] !== $ownKey) {
        return true;
    }
    // Auch inaktive Teams und eigene Tags anderer Personen ohne Farbe
    $escaped = $db->escape_string($tag);
    if ($db->fetch_field($db->simple_select('postcolors_teams', 'COUNT(*) AS c', "tag = '{$escaped}'"), 'c')) {
        return true;
    }
    $pid = (int)substr($ownKey, 1);
    return (bool)$db->fetch_field($db->simple_select('postcolors_players', 'COUNT(*) AS c',
        "custom_tag = '{$escaped}' AND pid != {$pid}"), 'c');
}
