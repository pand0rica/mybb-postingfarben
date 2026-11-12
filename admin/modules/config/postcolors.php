<?php
/**
 * Postcolors – ACP-Modul (Konfiguration → Postcolors).
 *
 * Tabs: Teamfarben, Spieler*innen, Werkzeuge, Migration (nur solange {prefix}colors existiert).
 * Schreibende Aktionen laufen ausschließlich unter POST; den my_post_key prüft admin/index.php.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$lang->load('postcolors');

if (!function_exists('postcolors_boot')) {
    flash_message($lang->postcolors_error_inactive, 'error');
    admin_redirect('index.php?module=config-plugins');
}
postcolors_boot();
require_once POSTCOLORS_DIR . 'migration/MigrateFromV1.php';

define('POSTCOLORS_ACP_URL', 'index.php?module=config-postcolors');

$page->add_breadcrumb_item($lang->postcolors, POSTCOLORS_ACP_URL);
$page->extra_header .= '<style type="text/css">' . (string)@file_get_contents(POSTCOLORS_DIR . 'assets/postcolors.css')
    . "\n.postcolors_table td{vertical-align:middle}.postcolors_hex.postcolors_invalid{border-color:#c00}</style>\n"
    . '<script type="text/javascript" src="' . htmlspecialchars_uni($mybb->asset_url) . '/jscripts/postcolors.js?ver=' . POSTCOLORS_VERSION . '"></script>' . "\n";

$action = $mybb->get_input('action');

/* ------------------------------------------------------------------------------ Hilfen */

function postcolors_acp_tabs(string $active): void
{
    global $page, $lang;
    $tabs = [
        'teams' => ['title' => $lang->postcolors_tab_teams, 'link' => POSTCOLORS_ACP_URL, 'description' => $lang->postcolors_tab_teams_desc],
        'add_team' => ['title' => $lang->postcolors_tab_add_team, 'link' => POSTCOLORS_ACP_URL . '&amp;action=add_team', 'description' => $lang->postcolors_tab_add_team_desc],
        'players' => ['title' => $lang->postcolors_tab_players, 'link' => POSTCOLORS_ACP_URL . '&amp;action=players', 'description' => $lang->postcolors_tab_players_desc],
        'tools' => ['title' => $lang->postcolors_tab_tools, 'link' => POSTCOLORS_ACP_URL . '&amp;action=tools', 'description' => $lang->postcolors_tab_tools_desc],
    ];
    if ($active === 'edit_team') {
        $tabs['edit_team'] = ['title' => $lang->postcolors_tab_edit_team, 'link' => '#', 'description' => $lang->postcolors_tab_edit_team_desc];
    }
    if (PostcolorsMigrateFromV1::legacyExists()) {
        $tabs['migration'] = ['title' => $lang->postcolors_tab_migration, 'link' => POSTCOLORS_ACP_URL . '&amp;action=migration', 'description' => $lang->postcolors_tab_migration_desc];
    }
    $page->output_nav_tabs($tabs, $active);
}

function postcolors_acp_swatch(string $color): string
{
    if (!PostcolorsThemeHelper::isValidColor($color)) {
        return '<em>–</em>';
    }
    $color = strtoupper($color);
    return '<span class="postcolors_swatch" style="background-color: ' . $color . ';"></span> <code>' . $color . '</code>';
}

/**
 * Farbtabelle wie im UserCP (Master + alle Themes) für das Team-Formular.
 *
 * @param array<int,string> $colors tid => Farbe/Eingabe
 */
function postcolors_acp_color_rows(array $colors): string
{
    global $lang, $mybb;

    $data = postcolors_data();
    $fallbackBg = PostcolorsThemeHelper::safeCssColor((string)($mybb->settings['postcolors_bg_fallback'] ?? '')) ?? '#FFFFFF';
    $master = (string)($colors[0] ?? '');
    $masterValid = PostcolorsThemeHelper::isValidColor($master);

    $entries = [0 => [$lang->postcolors_master, $fallbackBg]];
    foreach (PostcolorsRepository::themes() as $tid => $theme) {
        $entries[$tid] = [$theme['name'], (string)($data['bg'][$tid] ?? $fallbackBg)];
    }

    $html = '<table class="general postcolors_table" cellspacing="0"><thead><tr>'
        . '<th>' . $lang->postcolors_theme . '</th><th>' . $lang->postcolors_preview . '</th>'
        . '<th>' . $lang->postcolors_color . '</th><th></th></tr></thead><tbody>';
    foreach ($entries as $tid => [$name, $bg]) {
        $value = (string)($colors[$tid] ?? '');
        $valid = PostcolorsThemeHelper::isValidColor($value);
        $effective = $valid ? strtoupper($value) : ($tid !== 0 && $masterValid ? strtoupper($master) : '');
        $picker = $valid ? strtoupper($value) : ($effective !== '' ? $effective : '#000000');
        $placeholder = $tid === 0 ? $lang->postcolors_none : ($masterValid ? strtoupper($master) : $lang->postcolors_none);
        $hint = $tid === 0
            ? '<br /><span class="smalltext">' . $lang->postcolors_master_desc_team . '</span>'
            : '<br /><span class="smalltext postcolors_uses_master"' . ($valid ? ' hidden="hidden"' : '') . '>' . $lang->postcolors_uses_master . '</span>';
        $html .= '<tr class="postcolors_row" data-role="' . ($tid === 0 ? 'master' : 'theme') . '">'
            . '<td><strong>' . htmlspecialchars_uni($name) . '</strong>' . $hint . '</td>'
            . '<td><div class="postcolors_preview" style="background-color: ' . htmlspecialchars_uni($bg) . ';">'
            . '<span class="postcolors_sample" style="color: ' . ($effective !== '' ? $effective : 'inherit') . ';">' . $lang->postcolors_sample . '</span></div>'
            . '<div class="postcolors_contrast smalltext" hidden="hidden">' . $lang->postcolors_low_contrast . '</div></td>'
            . '<td class="postcolors_inputs"><input type="color" class="postcolors_picker" value="' . $picker . '" aria-label="' . htmlspecialchars_uni($name) . '" /> '
            . '<input type="text" class="text_input postcolors_hex" name="color[' . (int)$tid . ']" value="' . htmlspecialchars_uni($value) . '" size="9" maxlength="7" placeholder="' . htmlspecialchars_uni($placeholder) . '" autocomplete="off" /></td>'
            . '<td><button type="button" class="button postcolors_reset">' . ($tid === 0 ? $lang->postcolors_clear : $lang->postcolors_reset) . '</button></td>'
            . '</tr>';
    }
    return $html . '</tbody></table>';
}

/* ------------------------------------------------------------------------------ Team speichern */

if (($action === 'add_team' || $action === 'edit_team')) {
    $teamid = $mybb->get_input('teamid', MyBB::INPUT_INT);
    $team = $teamid > 0 ? PostcolorsRepository::team($teamid) : null;
    if ($action === 'edit_team' && !$team) {
        flash_message($lang->postcolors_error_team_missing, 'error');
        admin_redirect(POSTCOLORS_ACP_URL);
    }

    $values = [
        'title' => (string)($team['title'] ?? ''),
        'slug' => (string)($team['slug'] ?? ''),
        'tag' => (string)($team['tag'] ?? ''),
        'disporder' => (int)($team['disporder'] ?? 0),
        'active' => (int)($team['active'] ?? 1),
        'colors' => (array)($team['colors'] ?? []),
    ];
    $errors = [];

    if ($mybb->request_method === 'post') {
        $values['title'] = trim($mybb->get_input('title'));
        $values['slug'] = strtolower(trim($mybb->get_input('slug')));
        $values['tag'] = PostcolorsTagRegistry::normalize($mybb->get_input('tag'));
        $values['disporder'] = $mybb->get_input('disporder', MyBB::INPUT_INT);
        $values['active'] = $mybb->get_input('active', MyBB::INPUT_INT) === 1 ? 1 : 0;

        if ($values['title'] === '' || my_strlen($values['title']) > 100) {
            $errors[] = $lang->postcolors_error_title;
        }
        if (!preg_match('/^[a-z0-9_-]{1,30}$/', $values['slug']) || preg_match('/^p\d+$/', $values['slug'])) {
            $errors[] = $lang->postcolors_error_slug;
        } else {
            $other = PostcolorsRepository::teamBySlug($values['slug']);
            if ($other && (int)$other['teamid'] !== $teamid) {
                $errors[] = $lang->postcolors_error_slug_taken;
            }
        }
        if ($values['tag'] !== '') {
            if (!PostcolorsTagRegistry::isWellFormed($values['tag'])) {
                $errors[] = $lang->postcolors_error_tag_format;
            } elseif (PostcolorsTagRegistry::isReserved($values['tag'])) {
                $errors[] = $lang->postcolors_error_tag_reserved;
            } else {
                $escaped = $db->escape_string($values['tag']);
                $data = postcolors_data();
                $owner = $data['tags'][$values['tag']] ?? '';
                $teamTaken = (int)$db->fetch_field($db->simple_select('postcolors_teams', 'COUNT(*) AS c',
                    "tag = '{$escaped}' AND teamid != " . $teamid), 'c');
                $playerTaken = (int)$db->fetch_field($db->simple_select('postcolors_players', 'COUNT(*) AS c',
                    "custom_tag = '{$escaped}'"), 'c');
                if ($teamTaken || $playerTaken || ($owner !== '' && $owner !== 't' . $teamid)) {
                    $errors[] = $lang->postcolors_error_tag_taken;
                }
            }
        }

        $input = $mybb->get_input('color', MyBB::INPUT_ARRAY);
        $colors = [];
        foreach (array_merge([0], array_keys(PostcolorsRepository::themes())) as $tid) {
            $raw = isset($input[$tid]) && is_string($input[$tid]) ? $input[$tid] : '';
            $color = PostcolorsThemeHelper::normalizeColor($raw);
            if ($color === null) {
                $errors[] = $lang->postcolors_error_color_acp;
                $values['colors'][$tid] = $raw;
                continue;
            }
            $colors[$tid] = $color;
            $values['colors'][$tid] = $color;
        }

        if (empty($errors)) {
            $newId = PostcolorsRepository::saveTeam($teamid, $values['title'], $values['slug'], $values['tag'], $values['disporder'], $values['active']);
            PostcolorsRepository::writeTeamColors($newId, $colors);
            PostcolorsCacheBuilder::rebuild();
            log_admin_action($newId, $values['title'], $action === 'add_team' ? 'add' : 'edit');
            flash_message($lang->postcolors_team_saved, 'success');
            admin_redirect(POSTCOLORS_ACP_URL);
        }
    }

    $page->add_breadcrumb_item($action === 'add_team' ? $lang->postcolors_tab_add_team : $lang->postcolors_tab_edit_team);
    $page->output_header($lang->postcolors);
    postcolors_acp_tabs($action);
    if (!empty($errors)) {
        $page->output_inline_error($errors);
    }

    $url = POSTCOLORS_ACP_URL . '&amp;action=' . $action . ($teamid > 0 ? '&amp;teamid=' . $teamid : '');
    $form = new Form($url, 'post', 'postcolors_team_form');
    echo '<div class="postcolors_form">';
    $container = new FormContainer($action === 'add_team' ? $lang->postcolors_tab_add_team : $lang->postcolors_tab_edit_team);
    $container->output_row($lang->postcolors_team_title . ' <em>*</em>', $lang->postcolors_team_title_desc,
        $form->generate_text_box('title', $values['title'], ['id' => 'title', 'maxlength' => 100]), 'title');
    $container->output_row($lang->postcolors_team_slug . ' <em>*</em>', $lang->postcolors_team_slug_desc,
        $form->generate_text_box('slug', $values['slug'], ['id' => 'slug', 'maxlength' => 30]), 'slug');
    $container->output_row($lang->postcolors_team_tag, $lang->postcolors_team_tag_desc,
        $form->generate_text_box('tag', $values['tag'], ['id' => 'tag', 'maxlength' => 50]), 'tag');
    $container->output_row($lang->postcolors_team_disporder, '',
        $form->generate_numeric_field('disporder', $values['disporder'], ['id' => 'disporder', 'min' => 0]), 'disporder');
    $container->output_row($lang->postcolors_team_active, '',
        $form->generate_yes_no_radio('active', $values['active']), 'active');
    $container->output_row($lang->postcolors_team_colors, $lang->postcolors_team_colors_desc, postcolors_acp_color_rows($values['colors']));
    $container->end();
    echo '</div>';
    $form->output_submit_wrapper([$form->generate_submit_button($lang->postcolors_save)]);
    $form->end();
    $page->output_footer();
    exit;
}

/* ------------------------------------------------------------------------------ Team löschen */

if ($action === 'delete_team') {
    $teamid = $mybb->get_input('teamid', MyBB::INPUT_INT);
    $team = PostcolorsRepository::team($teamid);
    if (!$team) {
        flash_message($lang->postcolors_error_team_missing, 'error');
        admin_redirect(POSTCOLORS_ACP_URL);
    }
    if ($mybb->get_input('no')) {
        admin_redirect(POSTCOLORS_ACP_URL);
    }
    if ($mybb->request_method === 'post') {
        PostcolorsRepository::deleteTeam($teamid);
        PostcolorsCacheBuilder::rebuild();
        log_admin_action($teamid, $team['title'], 'delete');
        flash_message($lang->postcolors_team_deleted, 'success');
        admin_redirect(POSTCOLORS_ACP_URL);
    }
    $page->output_confirm_action(POSTCOLORS_ACP_URL . '&amp;action=delete_team&amp;teamid=' . $teamid,
        $lang->sprintf($lang->postcolors_confirm_delete_team, htmlspecialchars_uni((string)$team['title'])));
}

/* ------------------------------------------------------------------------------ Sortierung */

if ($action === 'order' && $mybb->request_method === 'post') {
    foreach ($mybb->get_input('disporder', MyBB::INPUT_ARRAY) as $teamid => $order) {
        PostcolorsRepository::setTeamOrder((int)$teamid, (int)$order);
    }
    PostcolorsCacheBuilder::rebuild();
    log_admin_action('order');
    flash_message($lang->postcolors_order_saved, 'success');
    admin_redirect(POSTCOLORS_ACP_URL);
}

/* ------------------------------------------------------------------------------ Spieler*innen */

if ($action === 'delete_player') {
    $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
    if ($mybb->get_input('no') || $pid <= 0) {
        admin_redirect(POSTCOLORS_ACP_URL . '&action=players');
    }
    if ($mybb->request_method === 'post') {
        PostcolorsRepository::deletePlayers([$pid]);
        PostcolorsCacheBuilder::rebuild();
        log_admin_action($pid, 'player');
        flash_message($lang->postcolors_player_deleted, 'success');
        admin_redirect(POSTCOLORS_ACP_URL . '&action=players');
    }
    $page->output_confirm_action(POSTCOLORS_ACP_URL . '&amp;action=delete_player&amp;pid=' . $pid, $lang->postcolors_confirm_delete_player);
}

if ($action === 'players') {
    $page->add_breadcrumb_item($lang->postcolors_tab_players);
    $page->output_header($lang->postcolors);
    postcolors_acp_tabs('players');

    if (!postcolors_has_account_switcher()) {
        echo '<div class="alert">' . $lang->postcolors_no_account_switcher . '</div>';
    }

    $data = postcolors_data();
    $players = PostcolorsRepository::allPlayers();
    $names = PostcolorsRepository::playerNames(array_keys($players), (int)($mybb->settings['postcolors_username_fid'] ?? 0));
    uasort($players, static function (array $a, array $b) use ($names): int {
        return strcasecmp($names[$a['pid']]['display'] ?? '', $names[$b['pid']]['display'] ?? '');
    });
    $conflicts = [];
    foreach ($data['conflicts'] ?? [] as $conflict) {
        $conflicts[(int)$conflict['pid']][] = $conflict;
    }

    $table = new Table();
    $table->construct_header($lang->postcolors_player);
    $table->construct_header($lang->postcolors_tag_current);
    $table->construct_header($lang->postcolors_master);
    $table->construct_header($lang->postcolors_theme_colors, ['class' => 'align_center']);
    $table->construct_header($lang->postcolors_speech_short, ['class' => 'align_center']);
    $table->construct_header($lang->postcolors_controls, ['class' => 'align_center', 'width' => 120]);
    foreach ($players as $pid => $player) {
        $name = $names[$pid]['display'] ?? ('#' . $pid);
        $tag = (string)($data['player_tags'][$pid] ?? '');
        $tagCell = $tag !== '' ? '<code>&lt;' . htmlspecialchars_uni($tag) . '&gt;</code>' : '<em>–</em>';
        foreach ($conflicts[$pid] ?? [] as $conflict) {
            $tagCell .= '<br /><span class="smalltext" style="color:#b45309">'
                . $lang->sprintf($lang->{'postcolors_conflict_' . $conflict['reason']}, htmlspecialchars_uni((string)$conflict['tag'])) . '</span>';
        }
        $table->construct_cell('<a href="index.php?module=user-users&amp;action=edit&amp;uid=' . (int)$pid . '">'
            . htmlspecialchars_uni($name) . '</a> <span class="smalltext">(uid ' . (int)$pid . ')</span>');
        $table->construct_cell($tagCell);
        $table->construct_cell(postcolors_acp_swatch((string)($player['colors'][0] ?? '')));
        $table->construct_cell((string)count(array_filter(array_keys($player['colors']))), ['class' => 'align_center']);
        $pairLabels = [];
        foreach (PostcolorsQuotes::pairs($player['quote_presets'], $player['quote_open'], $player['quote_close']) as $pair) {
            $pairLabels[] = htmlspecialchars_uni($pair[0] . '…' . $pair[1]);
        }
        $table->construct_cell(!empty($player['speech_enabled']) && !empty($pairLabels)
            ? implode(' ', $pairLabels) : '–', ['class' => 'align_center']);
        $table->construct_cell('<a href="' . POSTCOLORS_ACP_URL . '&amp;action=delete_player&amp;pid=' . (int)$pid
            . '&amp;my_post_key=' . $mybb->post_code . '" onclick="return AdminCP.deleteConfirmation(this, \''
            . addslashes($lang->postcolors_confirm_delete_player) . '\')">' . $lang->postcolors_delete . '</a>', ['class' => 'align_center']);
        $table->construct_row();
    }
    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->postcolors_list_no_players, ['colspan' => 6]);
        $table->construct_row();
    }
    $table->output($lang->postcolors_tab_players);
    $page->output_footer();
    exit;
}

/* ------------------------------------------------------------------------------ Werkzeuge */

if ($action === 'tools') {
    if ($mybb->request_method === 'post' && $mybb->get_input('do') === 'rebuild') {
        PostcolorsCacheBuilder::rebuild();
        log_admin_action('rebuild');
        flash_message($lang->postcolors_cache_rebuilt, 'success');
        admin_redirect(POSTCOLORS_ACP_URL . '&action=tools');
    }

    $page->add_breadcrumb_item($lang->postcolors_tab_tools);
    $page->output_header($lang->postcolors);
    postcolors_acp_tabs('tools');

    $data = postcolors_data();
    $table = new Table();
    $table->construct_cell($lang->postcolors_status_as, ['width' => '40%']);
    $table->construct_cell(postcolors_has_account_switcher() ? $lang->postcolors_yes : $lang->postcolors_no_account_switcher);
    $table->construct_row();
    $table->construct_cell($lang->postcolors_status_fid);
    $fid = (int)($mybb->settings['postcolors_username_fid'] ?? 0);
    $table->construct_cell($fid > 0 && $db->field_exists('fid' . $fid, 'userfields') ? 'fid' . $fid : $lang->postcolors_status_fid_missing);
    $table->construct_row();
    $table->construct_cell($lang->postcolors_status_tags);
    $table->construct_cell((string)count($data['tags'] ?? []));
    $table->construct_row();
    $table->construct_cell($lang->postcolors_status_conflicts);
    $table->construct_cell((string)count($data['conflicts'] ?? []));
    $table->construct_row();
    $table->construct_cell($lang->postcolors_status_built);
    $table->construct_cell(!empty($data['built']) ? my_date('relative', (int)$data['built']) : '–');
    $table->construct_row();
    $table->output($lang->postcolors_tab_tools);

    $form = new Form(POSTCOLORS_ACP_URL . '&amp;action=tools', 'post');
    echo $form->generate_hidden_field('do', 'rebuild');
    $form->output_submit_wrapper([$form->generate_submit_button($lang->postcolors_rebuild_cache)]);
    $form->end();
    $page->output_footer();
    exit;
}

/* ------------------------------------------------------------------------------ Migration */

if ($action === 'migration') {
    if (!PostcolorsMigrateFromV1::legacyExists()) {
        flash_message($lang->postcolors_migration_no_legacy, 'error');
        admin_redirect(POSTCOLORS_ACP_URL);
    }
    $do = $mybb->get_input('do');
    // Submit-Buttons senden ihre Beschriftung als Wert – daher über den Namen unterscheiden
    if ($mybb->get_input('do_run') !== '') {
        $do = 'run';
    } elseif ($mybb->get_input('do_preview') !== '') {
        $do = 'preview';
    }

    if ($do === 'drop') {
        if ($mybb->get_input('no')) {
            admin_redirect(POSTCOLORS_ACP_URL . '&action=migration');
        }
        if ($mybb->request_method === 'post') {
            if (PostcolorsMigrateFromV1::dropLegacyTable()) {
                log_admin_action('drop_legacy');
                flash_message($lang->postcolors_migration_dropped, 'success');
                admin_redirect(POSTCOLORS_ACP_URL);
            }
            flash_message($lang->postcolors_migration_drop_denied, 'error');
            admin_redirect(POSTCOLORS_ACP_URL . '&action=migration');
        }
        $page->output_confirm_action(POSTCOLORS_ACP_URL . '&amp;action=migration&amp;do=drop', $lang->postcolors_migration_confirm_drop);
    }

    $derive = $mybb->get_input('derive_master', MyBB::INPUT_INT) === 1 || $mybb->request_method !== 'post';
    $overwrite = $mybb->get_input('overwrite', MyBB::INPUT_INT) === 1;
    $choices = [];
    foreach ($mybb->get_input('choice', MyBB::INPUT_ARRAY) as $key => $pid) {
        $choices[(string)base64_decode((string)$key)] = (int)$pid;
    }
    $report = null;
    if ($mybb->request_method === 'post' && ($do === 'preview' || $do === 'run')) {
        $migration = new PostcolorsMigrateFromV1($derive, $overwrite, $choices);
        if ($do === 'run') {
            $report = $migration->run();
            log_admin_action('migration', count($report['migrated']));
        } else {
            $report = $migration->analyze();
        }
    }

    $page->add_breadcrumb_item($lang->postcolors_tab_migration);
    $page->output_header($lang->postcolors);
    postcolors_acp_tabs('migration');

    if ($report === null) {
        echo '<div class="notice">' . $lang->postcolors_migration_intro . '</div>';
    } else {
        echo '<div class="' . ($report['dry'] ? 'notice' : 'success') . '">'
            . ($report['dry'] ? $lang->postcolors_migration_dry : $lang->postcolors_migration_done) . '</div>';
        foreach ($report['errors'] as $error) {
            echo '<div class="error">' . ($lang->{'postcolors_migration_error_' . $error} ?? htmlspecialchars_uni($error)) . '</div>';
        }
    }

    $form = new Form(POSTCOLORS_ACP_URL . '&amp;action=migration', 'post', 'postcolors_migration_form');

    if ($report !== null && empty($report['errors'])) {
        $themes = PostcolorsRepository::themes();

        // Übernommen / zusammengeführt
        $table = new Table();
        $table->construct_header($lang->postcolors_player);
        $table->construct_header($lang->postcolors_migration_old_names);
        $table->construct_header($lang->postcolors_migration_colors);
        $table->construct_header($lang->postcolors_migration_status);
        foreach ($report['migrated'] as $pid => $entry) {
            $colors = [];
            foreach ($entry['colors'] as $tid => $color) {
                $colors[] = ($tid === 0 ? $lang->postcolors_master : htmlspecialchars_uni($themes[$tid]['name'] ?? ('#' . $tid)))
                    . ': ' . postcolors_acp_swatch($color);
            }
            $notes = array_map(static function (string $note) use ($lang): string {
                return $lang->{'postcolors_migration_note_' . $note} ?? $note;
            }, array_unique($entry['notes']));
            $table->construct_cell(htmlspecialchars_uni((string)$entry['label']) . ' <span class="smalltext">(uid ' . (int)$pid . ')</span>');
            $table->construct_cell(htmlspecialchars_uni(implode(', ', $entry['names'])));
            $table->construct_cell(implode('<br />', $colors));
            $table->construct_cell(($lang->{'postcolors_migration_status_' . $entry['status']} ?? $entry['status'])
                . (!empty($notes) ? '<br /><span class="smalltext">' . implode('; ', $notes) . '</span>' : ''));
            $table->construct_row();
        }
        if ($table->num_rows() === 0) {
            $table->construct_cell($lang->postcolors_migration_nothing, ['colspan' => 4]);
            $table->construct_row();
        }
        $table->output($lang->postcolors_migration_players);

        // Konflikte mit Auswahl
        if (!empty($report['conflicts'])) {
            $table = new Table();
            $table->construct_header($lang->postcolors_migration_old_names);
            $table->construct_header($lang->postcolors_migration_choose);
            foreach ($report['conflicts'] as $key => $conflict) {
                $options = ['0' => $lang->postcolors_migration_skip];
                foreach ($conflict['candidates'] as $pid => $label) {
                    $options[(string)$pid] = $label;
                }
                $table->construct_cell(htmlspecialchars_uni((string)$conflict['name']));
                $table->construct_cell($form->generate_select_box('choice[' . base64_encode((string)$key) . ']', $options,
                    (string)($choices[(string)$key] ?? '0')));
                $table->construct_row();
            }
            $table->output($lang->postcolors_migration_conflicts);
        }

        // Übersprungen
        if (!empty($report['skipped'])) {
            $table = new Table();
            $table->construct_header($lang->postcolors_migration_old_names);
            $table->construct_header($lang->postcolors_theme);
            $table->construct_header($lang->postcolors_color);
            $table->construct_header($lang->postcolors_migration_reason);
            foreach ($report['skipped'] as [$name, $style, $color, $reason]) {
                $table->construct_cell(htmlspecialchars_uni((string)$name));
                $table->construct_cell(htmlspecialchars_uni($themes[(int)$style]['name'] ?? ('#' . (int)$style)));
                $table->construct_cell(htmlspecialchars_uni((string)$color));
                $table->construct_cell($lang->{'postcolors_migration_reason_' . $reason} ?? htmlspecialchars_uni((string)$reason));
                $table->construct_row();
            }
            $table->output($lang->postcolors_migration_skipped);
        }

        // Teams
        $table = new Table();
        $table->construct_header($lang->postcolors_team_title);
        $table->construct_header($lang->postcolors_team_slug);
        $table->construct_header($lang->postcolors_team_tag);
        $table->construct_header($lang->postcolors_color);
        $table->construct_header($lang->postcolors_migration_status);
        foreach ($report['teams'] as [$slug, $title, $tag, $color, $status]) {
            $table->construct_cell(htmlspecialchars_uni((string)$title));
            $table->construct_cell(htmlspecialchars_uni((string)$slug));
            $table->construct_cell(htmlspecialchars_uni((string)$tag));
            $table->construct_cell(postcolors_acp_swatch((string)PostcolorsThemeHelper::normalizeColor((string)$color)));
            $table->construct_cell($lang->{'postcolors_migration_team_' . $status} ?? htmlspecialchars_uni((string)$status));
            $table->construct_row();
        }
        if ($table->num_rows() === 0) {
            $table->construct_cell($lang->postcolors_migration_no_teams, ['colspan' => 5]);
            $table->construct_row();
        }
        $table->output($lang->postcolors_migration_teams);

        // Altreste
        $table = new Table();
        $table->construct_header($lang->postcolors_migration_cleanup_item);
        $table->construct_header($lang->postcolors_migration_count, ['class' => 'align_center']);
        foreach ($report['cleanup'] as [$what, $count]) {
            $table->construct_cell($lang->{'postcolors_migration_cleanup_' . $what} ?? htmlspecialchars_uni((string)$what));
            $table->construct_cell((string)(int)$count, ['class' => 'align_center']);
            $table->construct_row();
        }
        $table->output($lang->postcolors_migration_cleanup);

        // Tags ohne Zuordnung
        $table = new Table();
        $table->construct_header($lang->postcolors_migration_tag);
        $table->construct_header($lang->postcolors_migration_count, ['class' => 'align_center']);
        foreach ($report['tags'] as $tag => $count) {
            $table->construct_cell('<code>&lt;' . htmlspecialchars_uni((string)$tag) . '&gt;</code>');
            $table->construct_cell((string)(int)$count, ['class' => 'align_center']);
            $table->construct_row();
        }
        if ($table->num_rows() === 0) {
            $table->construct_cell($lang->postcolors_migration_tags_ok, ['colspan' => 2]);
            $table->construct_row();
        }
        $table->output($lang->postcolors_migration_tags);
    }

    // Optionen + Buttons
    $container = new FormContainer($lang->postcolors_migration_options);
    $container->output_row($lang->postcolors_migration_derive, $lang->postcolors_migration_derive_desc,
        $form->generate_yes_no_radio('derive_master', $derive ? 1 : 0));
    $container->output_row($lang->postcolors_migration_overwrite, $lang->postcolors_migration_overwrite_desc,
        $form->generate_yes_no_radio('overwrite', $overwrite ? 1 : 0));
    $container->end();

    $buttons = [$form->generate_submit_button($lang->postcolors_migration_preview, ['name' => 'do_preview'])];
    if ($report !== null && $report['dry'] && empty($report['errors'])) {
        $buttons[] = $form->generate_submit_button($lang->postcolors_migration_run, ['name' => 'do_run']);
    }
    $form->output_submit_wrapper($buttons);
    $form->end();

    $state = PostcolorsMigrateFromV1::state();
    if (!empty($state['done'])) {
        // GET auf die Bestätigungsseite; erst deren POST löscht die Tabelle
        $drop = new Form('index.php', 'get');
        echo $drop->generate_hidden_field('module', 'config-postcolors');
        echo $drop->generate_hidden_field('action', 'migration');
        echo $drop->generate_hidden_field('do', 'drop');
        echo '<div class="notice">' . $lang->postcolors_migration_drop_hint . '</div>';
        $drop->output_submit_wrapper([$drop->generate_submit_button($lang->postcolors_migration_drop)]);
        $drop->end();
    }
    $page->output_footer();
    exit;
}

/* ------------------------------------------------------------------------------ Teamliste */

$page->output_header($lang->postcolors);
postcolors_acp_tabs('teams');

if (PostcolorsMigrateFromV1::legacyExists() && empty(PostcolorsMigrateFromV1::state()['done'])) {
    echo '<div class="notice">' . $lang->postcolors_migration_pending . '</div>';
}

$form = new Form(POSTCOLORS_ACP_URL . '&amp;action=order', 'post');
$table = new Table();
$table->construct_header($lang->postcolors_team_title);
$table->construct_header($lang->postcolors_team_slug);
$table->construct_header($lang->postcolors_team_tag);
$table->construct_header($lang->postcolors_master);
$table->construct_header($lang->postcolors_team_active, ['class' => 'align_center']);
$table->construct_header($lang->postcolors_team_disporder, ['class' => 'align_center', 'width' => 80]);
$table->construct_header($lang->postcolors_controls, ['class' => 'align_center', 'width' => 150]);

foreach (PostcolorsRepository::teams() as $teamid => $team) {
    $table->construct_cell('<strong><a href="' . POSTCOLORS_ACP_URL . '&amp;action=edit_team&amp;teamid=' . $teamid . '">'
        . htmlspecialchars_uni((string)$team['title']) . '</a></strong>');
    $table->construct_cell('<code>[pc=' . htmlspecialchars_uni((string)$team['slug']) . ']</code>');
    $table->construct_cell($team['tag'] !== '' ? '<code>&lt;' . htmlspecialchars_uni((string)$team['tag']) . '&gt;</code>' : '–');
    $table->construct_cell(postcolors_acp_swatch((string)($team['colors'][0] ?? '')));
    $table->construct_cell($team['active'] ? $lang->postcolors_yes : $lang->postcolors_no, ['class' => 'align_center']);
    $table->construct_cell($form->generate_numeric_field('disporder[' . $teamid . ']', $team['disporder'], ['style' => 'width: 50px', 'min' => 0]), ['class' => 'align_center']);
    $popup = new PopupMenu('postcolors_team_' . $teamid, $lang->postcolors_options);
    $popup->add_item($lang->postcolors_edit, POSTCOLORS_ACP_URL . '&amp;action=edit_team&amp;teamid=' . $teamid);
    $popup->add_item($lang->postcolors_delete, POSTCOLORS_ACP_URL . '&amp;action=delete_team&amp;teamid=' . $teamid
        . '&amp;my_post_key=' . $mybb->post_code, "return AdminCP.deleteConfirmation(this, '"
        . addslashes($lang->sprintf($lang->postcolors_confirm_delete_team, htmlspecialchars_uni((string)$team['title']))) . "')");
    $table->construct_cell($popup->fetch(), ['class' => 'align_center']);
    $table->construct_row();
}
if ($table->num_rows() === 0) {
    $table->construct_cell($lang->postcolors_list_no_teams, ['colspan' => 7]);
    $table->construct_row();
}
$table->output($lang->postcolors_tab_teams);
$form->output_submit_wrapper([$form->generate_submit_button($lang->postcolors_save_order)]);
$form->end();
$page->output_footer();
