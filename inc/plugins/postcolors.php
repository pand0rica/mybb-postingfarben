<?php
/**
 * Postcolors 2.0 – Postingfarben pro Spieler*in und Theme, Teamfarben, Namens-Tags,
 * MyCode [pc], Editor-Button und automatische Färbung wörtlicher Rede.
 *
 * @author  pand0rica
 * @license GPL-3.0-or-later
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

define('POSTCOLORS_VERSION', '2.0.0');
define('POSTCOLORS_DIR', MYBB_ROOT . 'inc/plugins/postcolors/');

/* =====================================================================================
 * Hook-Registrierung
 * ===================================================================================== */

$plugins->add_hook('global_start', 'postcolors_global_start');
$plugins->add_hook('global_intermediate', 'postcolors_global_intermediate');
$plugins->add_hook('pre_output_page', 'postcolors_pre_output_page');
$plugins->add_hook('archive_thread_start', 'postcolors_archive_css');
$plugins->add_hook('archive_announcement_start', 'postcolors_archive_css');

$plugins->add_hook('parse_message_start', 'postcolors_parse_start');
$plugins->add_hook('parse_message_end', 'postcolors_parse_end');
$plugins->add_hook('text_parse_message', 'postcolors_text_parse');

$plugins->add_hook('postbit', 'postcolors_postbit');
$plugins->add_hook('postbit_prev', 'postcolors_postbit_prev');
$plugins->add_hook('postbit_pm', 'postcolors_postbit_pm');
$plugins->add_hook('postbit_announcement', 'postcolors_postbit_announcement');
$plugins->add_hook('newreply_threadreview_post', 'postcolors_threadreview');
$plugins->add_hook('portal_announcement', 'postcolors_portal_announcement');
$plugins->add_hook('printthread_post', 'postcolors_printthread_post');
$plugins->add_hook('archive_thread_post', 'postcolors_archive_post');
$plugins->add_hook('search_results_post', 'postcolors_search_post');
$plugins->add_hook('xmlhttp_edit_post_end', 'postcolors_xmlhttp_edit_post');
$plugins->add_hook('xmlhttp_update_post', 'postcolors_xmlhttp_update_post');

$plugins->add_hook('editpost_end', 'postcolors_editpost_end');
$plugins->add_hook('private_send_end', 'postcolors_private_send_end');
$plugins->add_hook('datahandler_post_validate_post', 'postcolors_validate_post');
$plugins->add_hook('datahandler_post_validate_thread', 'postcolors_validate_post');
$plugins->add_hook('datahandler_pm_validate', 'postcolors_validate_pm');
$plugins->add_hook('datahandler_user_update', 'postcolors_user_update');
$plugins->add_hook('datahandler_user_delete_end', 'postcolors_user_delete');

$plugins->add_hook('usercp_menu', 'postcolors_usercp_menu');
$plugins->add_hook('usercp_start', 'postcolors_usercp_start');
$plugins->add_hook('misc_start', 'postcolors_misc_start');

$plugins->add_hook('admin_config_menu', 'postcolors_admin_menu');
$plugins->add_hook('admin_config_action_handler', 'postcolors_admin_action_handler');
$plugins->add_hook('admin_config_permissions', 'postcolors_admin_permissions');
$plugins->add_hook('admin_config_settings_begin', 'postcolors_admin_settings_lang');
$plugins->add_hook('admin_config_settings_change_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_delete_commit', 'postcolors_theme_deleted');
$plugins->add_hook('admin_style_themes_add_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_import_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_duplicate_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_edit_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_edit_stylesheet_simple_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_edit_stylesheet_advanced_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_add_stylesheet_commit', 'postcolors_rebuild_cache');
$plugins->add_hook('admin_style_themes_delete_stylesheet_commit', 'postcolors_rebuild_cache');

/* =====================================================================================
 * Plugin-Info & Lifecycle
 * ===================================================================================== */

function postcolors_info(): array
{
    global $lang;
    $lang->load('postcolors');

    return [
        'name' => 'Postcolors',
        'description' => $lang->postcolors_plugin_description ?? 'Posting colours per player and theme, team colours, name tags, [pc] MyCode and automatic colouring of direct speech.',
        'website' => 'https://github.com/pand0rica/mybb-postingfarben',
        'author' => 'pand0rica',
        'authorsite' => 'https://github.com/pand0rica',
        'version' => POSTCOLORS_VERSION,
        'guid' => '',
        'codename' => 'postcolors',
        'compatibility' => '18*',
    ];
}

function postcolors_install(): void
{
    global $db, $cache;

    $charset = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    if (!$db->table_exists('postcolors_players')) {
        $db->write_query("CREATE TABLE " . TABLE_PREFIX . "postcolors_players (
            pid INT UNSIGNED NOT NULL,
            custom_tag VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
            speech_enabled TINYINT(1) NOT NULL DEFAULT 0,
            quote_open VARCHAR(3) NOT NULL DEFAULT '',
            quote_close VARCHAR(3) NOT NULL DEFAULT '',
            updated INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (pid),
            UNIQUE KEY custom_tag (custom_tag)
        ) {$charset}");
    }
    if (!$db->table_exists('postcolors_colors')) {
        $db->write_query("CREATE TABLE " . TABLE_PREFIX . "postcolors_colors (
            pid INT UNSIGNED NOT NULL,
            tid INT UNSIGNED NOT NULL DEFAULT 0,
            color CHAR(7) NOT NULL,
            PRIMARY KEY (pid, tid),
            KEY tid (tid)
        ) {$charset}");
    }
    if (!$db->table_exists('postcolors_teams')) {
        $db->write_query("CREATE TABLE " . TABLE_PREFIX . "postcolors_teams (
            teamid INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(100) NOT NULL DEFAULT '',
            slug VARCHAR(30) NOT NULL,
            tag VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
            disporder INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (teamid),
            UNIQUE KEY slug (slug),
            UNIQUE KEY tag (tag)
        ) {$charset}");
    }
    if (!$db->table_exists('postcolors_teamcolors')) {
        $db->write_query("CREATE TABLE " . TABLE_PREFIX . "postcolors_teamcolors (
            teamid INT UNSIGNED NOT NULL,
            tid INT UNSIGNED NOT NULL DEFAULT 0,
            color CHAR(7) NOT NULL,
            PRIMARY KEY (teamid, tid),
            KEY tid (tid)
        ) {$charset}");
    }

    postcolors_install_settings();
    rebuild_settings();

    $cache->update('postcolors', false);
}

function postcolors_is_installed(): bool
{
    global $db;
    // Bewusst die neue Tabelle prüfen, nicht die Settinggroup: Version 1.x hinterlässt eine
    // Settinggroup gleichen Namens.
    return $db->table_exists('postcolors_players');
}

function postcolors_uninstall(): void
{
    global $db, $cache;

    foreach (['postcolors_players', 'postcolors_colors', 'postcolors_teams', 'postcolors_teamcolors'] as $table) {
        if ($db->table_exists($table)) {
            $db->drop_table($table);
        }
    }

    $db->delete_query('settings', "name LIKE 'postcolors\\_%'");
    $db->delete_query('settinggroups', "name = 'postcolors'");
    rebuild_settings();

    // Templates + Gruppe (falls deactivate() nicht lief), Stylesheet, Cache
    $db->delete_query('templates', "title LIKE 'postcolors\\_%' OR title = 'postcolors'");
    $db->delete_query('templategroups', "prefix = 'postcolors'");
    postcolors_remove_stylesheet();

    $cache->delete('postcolors');
}

function postcolors_activate(): void
{
    // Settings späterer Versionen nachrüsten, vorhandene Werte bleiben unangetastet.
    postcolors_install_settings();
    rebuild_settings();

    postcolors_install_templates();
    postcolors_install_stylesheet();
    postcolors_install_template_edits();

    require_once POSTCOLORS_DIR . 'core/bootstrap.php';
    PostcolorsCacheBuilder::rebuild();
}

function postcolors_deactivate(): void
{
    global $db;

    postcolors_remove_template_edits();
    $db->delete_query('templates', "title LIKE 'postcolors\\_%' AND sid = -2");
    $db->delete_query('templategroups', "prefix = 'postcolors'");
    postcolors_remove_stylesheet();
}

/* =====================================================================================
 * Lifecycle-Helfer
 * ===================================================================================== */

/**
 * Setting-Definitionen (Titel/Beschreibung englisch als Fallback; die Sprachdateien liefern
 * Übersetzungen über setting_<name>).
 */
function postcolors_setting_definitions(): array
{
    return [
        'postcolors_enabled' => ['Enable Postcolors', 'Turns colour output, name tags, [pc], the User CP page and the overview on or off.', 'yesno', '1'],
        'postcolors_usergroups' => ['Who may set colours', 'User groups (primary or additional group of the logged-in account) that may use the User CP page.', 'groupselect', '-1'],
        'postcolors_username_fid' => ['Player name profile field', 'ID of the custom profile field that holds the name of the person who plays (e.g. 4 for fid4). Used as name tag, display name and for the migration from 1.x.', 'numeric', '0'],
        'postcolors_bg_variable' => ['Background CSS variable', 'Name of a CSS variable in the theme stylesheets that holds the post background (e.g. --main-bgcolor). Used for the colour preview. Leave empty to always use the fallback.', 'text', '--h4w-main-bgcolor'],
        'postcolors_bg_fallback' => ['Fallback background colour', 'Background colour for the preview when the variable is not found (#RRGGBB).', 'text', '#FFFFFF'],
        'postcolors_bold' => ['Bold coloured text', 'Show coloured text in bold.', 'yesno', '1'],
        'postcolors_element_selectors' => ['Element selectors', 'Additionally output name tags as CSS element selectors (sarah{…}), so raw HTML that bypasses the parser is coloured as well.', 'yesno', '1'],
        'postcolors_editor_format' => ['Editor button inserts', 'What the editor button wraps around the selected text.', "select\ntag=Name tag (<name>…</name>)\npc=MyCode ([pc]…[/pc])", 'tag'],
        'postcolors_speech_allowed' => ['Allow automatic colouring of direct speech', 'Players may switch on automatic colouring of direct speech in their User CP.', 'yesno', '1'],
        'postcolors_speech_forums' => ['Forums with automatic colouring', 'Forums in which direct speech is coloured automatically.', 'forumselect', '-1'],
        'postcolors_speech_pm' => ['Automatic colouring in private messages', 'Also colour direct speech in private messages.', 'yesno', '1'],
    ];
}

function postcolors_install_settings(): void
{
    global $db;

    $group = $db->fetch_array($db->simple_select('settinggroups', 'gid', "name = 'postcolors'"));
    $groupData = [
        'name' => 'postcolors',
        'title' => 'Postcolors',
        'description' => $db->escape_string('Settings for posting colours, team colours, name tags and direct speech.'),
        'disporder' => 50,
        'isdefault' => 0,
    ];
    if ($group) {
        // Settinggroup aus 1.x übernehmen
        $gid = (int)$group['gid'];
        $db->update_query('settinggroups', $groupData, 'gid = ' . $gid);
    } else {
        $gid = (int)$db->insert_query('settinggroups', $groupData);
    }

    $order = 1;
    foreach (postcolors_setting_definitions() as $name => $def) {
        $existing = $db->fetch_array($db->simple_select('settings', 'sid', "name = '" . $db->escape_string($name) . "'"));
        $data = [
            'title' => $db->escape_string($def[0]),
            'description' => $db->escape_string($def[1]),
            'optionscode' => $db->escape_string($def[2]),
            'disporder' => $order++,
            'gid' => $gid,
        ];
        if ($existing) {
            // Vorhandenen Wert behalten (auch postcolors_username_fid aus 1.x), Metadaten aktualisieren
            $db->update_query('settings', $data, 'sid = ' . (int)$existing['sid']);
            continue;
        }
        $data['name'] = $db->escape_string($name);
        $data['value'] = $db->escape_string($def[3]);
        $db->insert_query('settings', $data);
    }
}

function postcolors_install_templates(): void
{
    global $db;

    $db->delete_query('templategroups', "prefix = 'postcolors'");
    $db->insert_query('templategroups', [
        'prefix' => 'postcolors',
        'title' => 'Postcolors',
        'isdefault' => 0,
    ]);

    $dir = POSTCOLORS_DIR . 'templates/';
    if (!is_dir($dir)) {
        return;
    }
    foreach ((array)glob($dir . '*.tpl') as $file) {
        $title = basename((string)$file, '.tpl');
        $content = (string)file_get_contents((string)$file);
        $data = [
            'title' => $db->escape_string($title),
            'template' => $db->escape_string($content),
            'sid' => -2,
            'version' => '1800',
            'dateline' => TIME_NOW,
        ];
        $existing = $db->fetch_array($db->simple_select('templates', 'tid', "title = '" . $db->escape_string($title) . "' AND sid = -2"));
        if ($existing) {
            $db->update_query('templates', $data, 'tid = ' . (int)$existing['tid']);
        } else {
            $db->insert_query('templates', $data);
        }
    }
}

function postcolors_install_stylesheet(): void
{
    global $db;
    require_once MYBB_ADMIN_DIR . 'inc/functions_themes.php';

    $file = POSTCOLORS_DIR . 'assets/postcolors.css';
    if (!file_exists($file)) {
        return;
    }
    $css = $db->escape_string((string)file_get_contents($file));
    $master = 1; // MyBB Master Style – alle Themes erben das Stylesheet

    $existing = $db->fetch_array($db->simple_select('themestylesheets', 'sid', "name = 'postcolors.css' AND tid = {$master}"));
    if ($existing) {
        $db->update_query('themestylesheets', ['stylesheet' => $css, 'lastmodified' => TIME_NOW], 'sid = ' . (int)$existing['sid']);
        $sid = (int)$existing['sid'];
    } else {
        $sid = (int)$db->insert_query('themestylesheets', [
            'name' => 'postcolors.css',
            'tid' => $master,
            'attachedto' => '',
            'stylesheet' => $css,
            'cachefile' => 'postcolors.css',
            'lastmodified' => TIME_NOW,
        ]);
    }
    if (!cache_stylesheet($master, 'postcolors.css', (string)file_get_contents($file))) {
        $db->update_query('themestylesheets', ['cachefile' => 'css.php?stylesheet=' . $sid], 'sid = ' . $sid);
    }

    $query = $db->simple_select('themes', 'tid');
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list((int)$theme['tid']);
    }
}

function postcolors_remove_stylesheet(): void
{
    global $db;
    require_once MYBB_ADMIN_DIR . 'inc/functions_themes.php';

    $query = $db->simple_select('themestylesheets', 'tid', "name = 'postcolors.css'");
    $tids = [];
    while ($row = $db->fetch_array($query)) {
        $tids[] = (int)$row['tid'];
    }
    $db->delete_query('themestylesheets', "name = 'postcolors.css'");
    foreach ($tids as $tid) {
        @unlink(MYBB_ROOT . 'cache/themes/theme' . $tid . '/postcolors.css');
        @unlink(MYBB_ROOT . 'cache/themes/theme' . $tid . '/postcolors.min.css');
    }
    $query = $db->simple_select('themes', 'tid');
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list((int)$theme['tid']);
    }
}

/** Fertige UserCP-Zeile für das Standard-Template usercp_nav_misc. */
function postcolors_nav_misc_row(): string
{
    return '<tr><td class="trow1 smalltext"><a href="usercp.php?action=postcolors" class="usercp_nav_item usercp_nav_postcolors">{$lang->postcolors_nav}</a></td></tr><!--postcolors_nav-->';
}

function postcolors_install_template_edits(): void
{
    global $db;
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    postcolors_remove_template_edits();

    // Farb-CSS in den Seitenkopf
    find_replace_templatesets('headerinclude', '#$#', "\n{\$postcolors_css}", 1, false, 1);

    // H4W: Menüpunkt nur im RPG-Menü der User-Accounts (Group Templates wählt
    // usercp_nav_rpg_user für die Gruppen 43/32; Charaktere bekommen keinen Eintrag).
    // Der fest eingetragene 1.x-Link (action=postcolor) wird dabei ersetzt.
    find_replace_templatesets('usercp_nav_rpg_user', '#\n?[^\n]*usercp\.php\?action=postcolor(?!s)[^\n]*#', '', 0);
    find_replace_templatesets('usercp_nav_rpg_user', '#' . preg_quote('{$usercp_nav_rpg_custom}', '#') . '#',
        postcolors_h4w_nav_row() . "\n    " . '{$usercp_nav_rpg_custom}', 0, false, 1);
}

/** H4W: Menüzeile im Stil der übrigen Plugin-Zeilen der RPG-Kategorie (Inline-SVG in Linkfarbe). */
function postcolors_h4w_nav_row(): string
{
    return '<tr><td class="trow1 smalltext"><a href="usercp.php?action=postcolors" class="usercp_nav_item usercp_nav_postcolors" style="padding-left: 0;">'
        . '<svg class="usercp_nav_icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" aria-hidden="true" style="vertical-align: -3px; margin-right: 6px;">'
        . '<path d="M8 1.8C5.4 5 3.8 7.3 3.8 9.6a4.2 4.2 0 0 0 8.4 0C12.2 7.3 10.6 5 8 1.8z" stroke-width="1.3" stroke-linejoin="round"/>'
        . '<path d="M6 10.2a2 2 0 0 0 2 1.9" stroke-width="1.3" stroke-linecap="round"/></svg>'
        . '{$lang->postcolors_nav}</a></td></tr><!--postcolors_nav-->';
}

/**
 * Baut die Linkzeile für usercp_nav_rpg nach dem Muster eines vorhandenen Links derselben Box,
 * damit sie zum Markup des Themes passt (Tabelle oder div-Layout).
 */
function postcolors_rpg_nav_line(string $template): string
{
    foreach (preg_split('/\R/', $template) as $line) {
        if (!preg_match('#<a\s[^>]*href="[^"]*"[^>]*>.*?</a>#is', $line)) {
            continue;
        }
        $new = preg_replace('#(<a\s[^>]*href=")[^"]*(")#i', '${1}usercp.php?action=postcolors${2}', $line, 1);
        $new = preg_replace('#(<a\s[^>]*>).*?(</a>)#is', '${1}{$lang->postcolors_nav}${2}', (string)$new, 1);
        $new = preg_replace('#\bclass="([^"]*)usercp_nav_[a-z0-9_]+#i', 'class="$1usercp_nav_postcolors', (string)$new, 1);
        return trim((string)$new) . '<!--postcolors_nav-->';
    }
    return '<a href="usercp.php?action=postcolors" class="usercp_nav_item usercp_nav_postcolors">{$lang->postcolors_nav}</a><!--postcolors_nav-->';
}

function postcolors_remove_template_edits(): void
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    find_replace_templatesets('headerinclude', '#\n?\{\$postcolors_css\}#', '', 0);
    foreach (['usercp_nav_rpg_user', 'usercp_nav_rpg', 'usercp_nav_misc'] as $title) {
        // Nur die eigene Zeile (inkl. vorangehendem Zeilenumbruch) entfernen
        find_replace_templatesets($title, '#\n?[^\n]*<!--postcolors_nav-->#', '', 0);
    }
}

/* =====================================================================================
 * Laufzeit-Helfer
 * ===================================================================================== */

/** Lädt die Core-Klassen bei Bedarf. */
function postcolors_boot(): void
{
    require_once POSTCOLORS_DIR . 'core/bootstrap.php';
}

/** Liest den Datacache (baut ihn bei Bedarf einmalig neu). */
function postcolors_data(): array
{
    global $cache;
    static $data = null;
    if ($data === null) {
        $data = $cache->read('postcolors');
        if (!is_array($data) || ($data['version'] ?? 0) !== 2) {
            postcolors_boot();
            $data = PostcolorsCacheBuilder::rebuild();
        }
    }
    return $data;
}

/** Cache neu bauen (auch als Hook-Handler nutzbar). */
function postcolors_rebuild_cache(): void
{
    postcolors_boot();
    PostcolorsCacheBuilder::rebuild();
}

function postcolors_enabled(): bool
{
    global $mybb;
    return !empty($mybb->settings['postcolors_enabled']);
}

/**
 * pid (Hauptaccount) eines Accounts: as_uid > 0 ? as_uid : uid.
 * Quelle ist der Datacache "accountswitcher" des Enhanced Account Switchers; ohne ihn pid = uid.
 */
function postcolors_resolve_pid(int $uid): int
{
    global $mybb, $cache;
    static $map = null;

    if ($uid <= 0) {
        return 0;
    }
    if (isset($mybb->user['uid'], $mybb->user['as_uid']) && (int)$mybb->user['uid'] === $uid) {
        $as = (int)$mybb->user['as_uid'];
        return $as > 0 ? $as : $uid;
    }
    if ($map === null) {
        $map = [];
        $switchers = $cache->read('accountswitcher');
        if (is_array($switchers)) {
            foreach ($switchers as $row) {
                if (is_array($row) && (int)($row['as_uid'] ?? 0) > 0) {
                    $map[(int)$row['uid']] = (int)$row['as_uid'];
                }
            }
        }
    }
    return $map[$uid] ?? $uid;
}

/** Ist das Feld users.as_uid vorhanden (Account Switcher installiert)? Nur im ACP verwenden. */
function postcolors_has_account_switcher(): bool
{
    global $db;
    return $db->field_exists('as_uid', 'users');
}

/**
 * pid für [pc] ohne Parameter beim Rendern (Vorschau, Quick-Edit): die Person, deren
 * Beitrag gerade geschrieben/bearbeitet wird.
 */
function postcolors_render_author_pid(): int
{
    global $mybb, $post, $postcolors_render_uid;

    if (!empty($postcolors_render_uid)) {
        return postcolors_resolve_pid((int)$postcolors_render_uid);
    }
    if (!defined('THIS_SCRIPT')) {
        return 0;
    }
    if (THIS_SCRIPT === 'editpost.php' && is_array($post) && !empty($post['uid'])) {
        return postcolors_resolve_pid((int)$post['uid']);
    }
    if (in_array(THIS_SCRIPT, ['newthread.php', 'newreply.php', 'private.php', 'xmlhttp.php'], true)) {
        return postcolors_resolve_pid((int)($mybb->user['uid'] ?? 0));
    }
    return 0;
}

/**
 * Färbt wörtliche Rede im fertig geparsten HTML eines Beitrags/einer PN.
 *
 * @param string $html       geparster Text
 * @param int    $author_uid uid des Accounts, der den Text geschrieben hat
 * @param int    $fid        Forum (0 = keine Forumsprüfung, -1 = globale Ankündigung)
 * @param bool   $is_pm      Private Nachricht (Setting postcolors_speech_pm)
 */
function postcolors_apply_speech(string $html, int $author_uid, int $fid = 0, bool $is_pm = false): string
{
    global $mybb;

    if ($html === '' || $author_uid <= 0 || !postcolors_enabled() || empty($mybb->settings['postcolors_speech_allowed'])) {
        return $html;
    }
    if ($is_pm) {
        if (empty($mybb->settings['postcolors_speech_pm'])) {
            return $html;
        }
    } elseif ($fid !== 0) {
        $forums = trim((string)($mybb->settings['postcolors_speech_forums'] ?? '-1'));
        if ($forums !== '-1') {
            if ($forums === '' || $fid < 0 || !in_array($fid, array_map('intval', explode(',', $forums)), true)) {
                return $html;
            }
        }
    }

    $data = postcolors_data();
    $pid = postcolors_resolve_pid($author_uid);
    if (!isset($data['speech'][$pid])) {
        return $html;
    }
    postcolors_boot();
    require_once POSTCOLORS_DIR . 'core/SpeechDetector.php';
    require_once POSTCOLORS_DIR . 'core/SpeechColorizer.php';

    static $colorizers = [];
    if (!isset($colorizers[$pid])) {
        [$open, $close] = $data['speech'][$pid];
        $colorizers[$pid] = new PostcolorsSpeechColorizer((string)$open, (string)$close, 'pc pc-p' . $pid . ' pc-speech');
    }
    return $colorizers[$pid]->apply($html);
}

/** Merkt sich den Autor für den nächsten parse_message-Aufruf (Hooks vor dem Parsen). */
function postcolors_set_speech_context(int $uid, int $fid, bool $is_pm = false): void
{
    global $postcolors_speech_next;
    $postcolors_speech_next = ['uid' => $uid, 'fid' => $fid, 'pm' => $is_pm];
}

/* =====================================================================================
 * Globale Ausgabe
 * ===================================================================================== */

function postcolors_global_start(): void
{
    global $lang, $postcolors_css, $postcolors_render_uid, $postcolors_speech_next;

    $postcolors_css = '';
    $postcolors_render_uid = 0;
    $postcolors_speech_next = null;
    $lang->load('postcolors');

    if (defined('THIS_SCRIPT') && isset($GLOBALS['templatelist'])) {
        if (THIS_SCRIPT === 'usercp.php') {
            $GLOBALS['templatelist'] .= ',postcolors_usercp,postcolors_usercp_row,postcolors_usercp_tag,postcolors_usercp_speech';
        } elseif (THIS_SCRIPT === 'misc.php') {
            $GLOBALS['templatelist'] .= ',postcolors_list,postcolors_list_team,postcolors_list_player,postcolors_list_empty,postcolors_list_themeselect';
        }
    }
}

/** CSS des aktuellen Themes aus dem Cache – keine Query. */
function postcolors_css_for_theme(int $tid): string
{
    $data = postcolors_data();
    $css = $data['css'][$tid] ?? ($data['css'][0] ?? '');
    return $css === '' ? '' : '<style type="text/css" id="postcolors-css">' . "\n" . $css . "\n</style>";
}

function postcolors_global_intermediate(): void
{
    global $postcolors_css, $theme;

    if (!postcolors_enabled()) {
        return;
    }
    $postcolors_css = postcolors_css_for_theme((int)($theme['tid'] ?? 0));
}

function postcolors_archive_css(): void
{
    global $cache;
    if (!postcolors_enabled()) {
        return;
    }
    $default = $cache->read('default_theme');
    echo postcolors_css_for_theme((int)($default['tid'] ?? 0));
}

/**
 * Editor-Script + Konfiguration in den <head> von Seiten mit SCEditor
 * (pre_output_page statt headerinclude, siehe Dev-Kit debugging.md Punkt 25).
 */
function postcolors_pre_output_page($contents)
{
    global $mybb, $lang, $theme;

    if (!is_string($contents) || !postcolors_enabled() || stripos($contents, 'jquery.sceditor') === false
        || stripos($contents, '</head>') === false || strpos($contents, 'postcolors_editor.js') !== false) {
        return $contents;
    }

    $data = postcolors_data();
    $uid = (int)($mybb->user['uid'] ?? 0);
    $pid = postcolors_render_author_pid();
    if ($pid <= 0 && $uid > 0) {
        $pid = postcolors_resolve_pid($uid);
    }
    $tid = (int)($theme['tid'] ?? 0);

    $config = [
        'format' => ($mybb->settings['postcolors_editor_format'] ?? 'tag') === 'pc' ? 'pc' : 'tag',
        'own' => [
            'pid' => $pid,
            'tag' => (string)($data['player_tags'][$pid] ?? ''),
            'has' => isset($data['known'][$pid]),
        ],
        'teams' => array_values($data['teams'] ?? []),
        'tags' => (object)($data['tags'] ?? []),
        'css' => (string)($data['css'][$tid] ?? ($data['css'][0] ?? '')),
        'lang' => [
            'button' => $lang->postcolors_editor_button ?? 'Posting colour',
            'mine' => $lang->postcolors_editor_mine ?? 'My colour',
            'none' => $lang->postcolors_editor_none ?? 'You have not set a colour yet.',
            'teams' => $lang->postcolors_editor_teams ?? 'Team colours',
        ],
    ];

    $json = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    $block = '<script type="text/javascript">var postcolors_config = ' . $json . ';</script>' . "\n"
        . '<script type="text/javascript" src="' . htmlspecialchars_uni($mybb->asset_url) . '/jscripts/postcolors_editor.js?ver=' . POSTCOLORS_VERSION . '"></script>' . "\n";
    $pos = (int)stripos($contents, '</head>');
    return substr_replace($contents, $block, $pos, 0);
}

/* =====================================================================================
 * Parser
 * ===================================================================================== */

function postcolors_parse_start($message)
{
    if (!is_string($message) || !postcolors_enabled()) {
        return $message;
    }
    postcolors_boot();
    if (PostcolorsMyCodeRenderer::$authorResolver === null) {
        PostcolorsMyCodeRenderer::$authorResolver = 'postcolors_render_author_pid';
    }
    return PostcolorsMyCodeRenderer::start($message, postcolors_data());
}

function postcolors_parse_end($message)
{
    global $postcolors_speech_next;
    if (!is_string($message) || !postcolors_enabled()) {
        return $message;
    }
    postcolors_boot();
    $message = PostcolorsMyCodeRenderer::end($message);

    if (is_array($postcolors_speech_next)) {
        $context = $postcolors_speech_next;
        $postcolors_speech_next = null;
        $message = postcolors_apply_speech($message, (int)$context['uid'], (int)$context['fid'], (bool)$context['pm']);
    }
    return $message;
}

function postcolors_text_parse($message)
{
    if (!is_string($message) || !postcolors_enabled()) {
        return $message;
    }
    postcolors_boot();
    return PostcolorsMyCodeRenderer::strip($message, postcolors_data());
}

/* =====================================================================================
 * Automatische Färbung – Ansichten
 * ===================================================================================== */

function postcolors_current_fid(array $post): int
{
    global $fid, $forum, $thread;
    if (!empty($post['fid'])) {
        return (int)$post['fid'];
    }
    if (!empty($fid)) {
        return (int)$fid;
    }
    if (is_array($forum) && !empty($forum['fid'])) {
        return (int)$forum['fid'];
    }
    if (is_array($thread) && !empty($thread['fid'])) {
        return (int)$thread['fid'];
    }
    return 0;
}

function postcolors_postbit(&$post)
{
    if (is_array($post) && isset($post['message'])) {
        $post['message'] = postcolors_apply_speech((string)$post['message'], (int)($post['uid'] ?? 0), postcolors_current_fid($post));
    }
    return $post;
}

function postcolors_postbit_prev(&$post)
{
    if (is_array($post) && isset($post['message'])) {
        $isPm = defined('THIS_SCRIPT') && THIS_SCRIPT === 'private.php';
        $post['message'] = postcolors_apply_speech((string)$post['message'], (int)($post['uid'] ?? 0), $isPm ? 0 : postcolors_current_fid($post), $isPm);
    }
    return $post;
}

function postcolors_postbit_pm(&$post)
{
    if (is_array($post) && isset($post['message'])) {
        $post['message'] = postcolors_apply_speech((string)$post['message'], (int)($post['uid'] ?? ($post['fromid'] ?? 0)), 0, true);
    }
    return $post;
}

function postcolors_postbit_announcement(&$post)
{
    if (is_array($post) && isset($post['message'])) {
        $post['message'] = postcolors_apply_speech((string)$post['message'], (int)($post['uid'] ?? 0), (int)($post['fid'] ?? 0));
    }
    return $post;
}

function postcolors_threadreview(): void
{
    global $post;
    if (is_array($post)) {
        postcolors_set_speech_context((int)($post['uid'] ?? 0), postcolors_current_fid($post));
    }
}

function postcolors_portal_announcement(): void
{
    global $announcement;
    if (is_array($announcement)) {
        postcolors_set_speech_context((int)($announcement['uid'] ?? 0), (int)($announcement['fid'] ?? 0));
    }
}

function postcolors_printthread_post(): void
{
    global $postrow;
    if (is_array($postrow) && isset($postrow['message'])) {
        $postrow['message'] = postcolors_apply_speech((string)$postrow['message'], (int)($postrow['uid'] ?? 0), postcolors_current_fid($postrow));
    }
}

function postcolors_archive_post(): void
{
    global $post;
    if (is_array($post) && isset($post['message'])) {
        $post['message'] = postcolors_apply_speech((string)$post['message'], (int)($post['uid'] ?? 0), postcolors_current_fid($post));
    }
}

function postcolors_search_post(): void
{
    global $post, $prev;
    if (is_array($post) && is_string($prev) && $prev !== '') {
        $prev = postcolors_apply_speech($prev, (int)($post['uid'] ?? 0), (int)($post['fid'] ?? 0));
    }
}

function postcolors_xmlhttp_edit_post(): void
{
    global $mybb, $post, $postcolors_render_uid;
    if (!is_array($post) || !postcolors_enabled()) {
        return;
    }
    postcolors_boot();
    $postcolors_render_uid = (int)($post['uid'] ?? 0);
    if ($mybb->get_input('do') === 'get_post' && isset($post['message'])) {
        $post['message'] = PostcolorsMyCodeRenderer::rewriteForEdit((string)$post['message'], postcolors_resolve_pid((int)$post['uid']));
    }
}

function postcolors_xmlhttp_update_post(): void
{
    global $post;
    if (is_array($post) && isset($post['message'])) {
        $post['message'] = postcolors_apply_speech((string)$post['message'], (int)($post['uid'] ?? 0), (int)($post['fid'] ?? 0));
    }
}

/* =====================================================================================
 * Speichern & Bearbeiten ([pc] <-> [pc=pX])
 * ===================================================================================== */

/** @param PostDataHandler $handler */
function postcolors_validate_post(&$handler)
{
    if (!postcolors_enabled() || !is_object($handler) || !isset($handler->data['message'])) {
        return $handler;
    }
    $uid = (int)($handler->data['uid'] ?? 0);
    if ($uid <= 0 && !empty($handler->data['pid'])) {
        $existing = get_post((int)$handler->data['pid']);
        $uid = (int)($existing['uid'] ?? 0);
    }
    postcolors_boot();
    $handler->data['message'] = PostcolorsMyCodeRenderer::rewriteForSave((string)$handler->data['message'], postcolors_resolve_pid($uid));
    return $handler;
}

/** @param PMDataHandler $handler */
function postcolors_validate_pm(&$handler)
{
    if (!postcolors_enabled() || !is_object($handler) || !isset($handler->data['message'])) {
        return $handler;
    }
    postcolors_boot();
    $uid = (int)($handler->data['fromid'] ?? 0);
    $handler->data['message'] = PostcolorsMyCodeRenderer::rewriteForSave((string)$handler->data['message'], postcolors_resolve_pid($uid));
    return $handler;
}

function postcolors_editpost_end(): void
{
    global $message, $post;
    if (!postcolors_enabled() || !is_string($message) || !is_array($post)) {
        return;
    }
    postcolors_boot();
    $message = PostcolorsMyCodeRenderer::rewriteForEdit($message, postcolors_resolve_pid((int)($post['uid'] ?? 0)));
}

function postcolors_private_send_end(): void
{
    global $message, $mybb;
    if (!postcolors_enabled() || !is_string($message)) {
        return;
    }
    postcolors_boot();
    $message = PostcolorsMyCodeRenderer::rewriteForEdit($message, postcolors_resolve_pid((int)($mybb->user['uid'] ?? 0)));
}

/* =====================================================================================
 * Accounts
 * ===================================================================================== */

/** @param UserDataHandler $handler */
function postcolors_user_update(&$handler)
{
    global $mybb, $db;
    $fid = (int)($mybb->settings['postcolors_username_fid'] ?? 0);
    if ($fid <= 0 || !is_object($handler) || empty($handler->data['uid'])
        || !isset($handler->data['profile_fields']['fid' . $fid])) {
        return $handler;
    }
    $uid = (int)$handler->data['uid'];
    if ($db->fetch_field($db->simple_select('postcolors_colors', 'COUNT(*) AS c', 'pid = ' . $uid), 'c')) {
        // Profilfeld wird erst nach diesem Hook geschrieben – Cache im MyBB-Shutdown neu bauen
        add_shutdown('postcolors_rebuild_cache');
    }
    return $handler;
}

/** @param UserDataHandler $handler */
function postcolors_user_delete(&$handler)
{
    if (is_object($handler) && !empty($handler->delete_uids)) {
        postcolors_boot();
        PostcolorsRepository::deletePlayers((array)$handler->delete_uids);
        PostcolorsCacheBuilder::rebuild();
    }
    return $handler;
}

/* =====================================================================================
 * UserCP & Übersicht
 * ===================================================================================== */

function postcolors_usercp_menu(): void
{
    global $lang;
    $lang->load('postcolors');
}

function postcolors_usercp_start(): void
{
    global $mybb;
    if ($mybb->get_input('action') !== 'postcolors' && $mybb->get_input('action') !== 'do_postcolors') {
        return;
    }
    postcolors_boot();
    require_once POSTCOLORS_DIR . 'modules/usercp.php';
    postcolors_usercp_page();
}

function postcolors_misc_start(): void
{
    global $mybb;
    if ($mybb->get_input('action') !== 'postcolors') {
        return;
    }
    postcolors_boot();
    require_once POSTCOLORS_DIR . 'modules/misc.php';
    postcolors_misc_page();
}

/* =====================================================================================
 * ACP
 * ===================================================================================== */

function postcolors_admin_menu(&$sub_menu)
{
    global $lang;
    $lang->load('postcolors');
    $sub_menu[] = [
        'id' => 'postcolors',
        'title' => $lang->postcolors ?? 'Postcolors',
        'link' => 'index.php?module=config-postcolors',
    ];
    return $sub_menu;
}

function postcolors_admin_action_handler(&$actions)
{
    $actions['postcolors'] = ['active' => 'postcolors', 'file' => 'postcolors.php'];
    return $actions;
}

function postcolors_admin_permissions(&$admin_permissions)
{
    global $lang;
    $lang->load('postcolors');
    $admin_permissions['postcolors'] = $lang->postcolors_admin_permission ?? 'Can manage Postcolors?';
    return $admin_permissions;
}

function postcolors_admin_settings_lang(): void
{
    global $lang;
    $lang->load('postcolors');
}

function postcolors_theme_deleted(): void
{
    global $theme;
    postcolors_boot();
    if (is_array($theme) && !empty($theme['tid'])) {
        PostcolorsRepository::deleteThemeColors((int)$theme['tid']);
    }
    PostcolorsCacheBuilder::rebuild();
}
