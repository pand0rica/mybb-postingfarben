<?php
/**
 * Postcolors – CLI-Lifecycle-Helper (nicht Teil des Release-Zips).
 *
 *   php tests/cli-plugin.php install     install() + activate()
 *   php tests/cli-plugin.php uninstall   deactivate() + uninstall()
 *   php tests/cli-plugin.php deactivate  nur deactivate()
 *   php tests/cli-plugin.php activate    nur activate()
 *   php tests/cli-plugin.php status      Zähler ausgeben
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only.');
}
define('IN_MYBB', 1);
chdir(dirname(__DIR__));
require_once './inc/init.php';

if (!defined('MYBB_ADMIN_DIR')) {
    $admin_dir = isset($config['admin_dir']) ? $config['admin_dir'] : 'admin';
    define('MYBB_ADMIN_DIR', MYBB_ROOT . $admin_dir . '/');
}
require_once MYBB_ROOT . 'inc/functions_task.php';
require_once MYBB_ROOT . 'inc/plugins/postcolors.php';

function postcolors_cli_set_active(bool $active): void
{
    global $cache;
    $c = $cache->read('plugins');
    if (!is_array($c)) {
        $c = [];
    }
    if (!isset($c['active']) || !is_array($c['active'])) {
        $c['active'] = [];
    }
    if ($active) {
        $c['active']['postcolors'] = 'postcolors';
    } else {
        unset($c['active']['postcolors']);
    }
    $cache->update('plugins', $c);
}

$cmd = $argv[1] ?? 'status';
if ($cmd === 'install') {
    postcolors_install();
    postcolors_activate();
    postcolors_cli_set_active(true);
} elseif ($cmd === 'uninstall') {
    postcolors_deactivate();
    postcolors_uninstall();
    postcolors_cli_set_active(false);
} elseif ($cmd === 'deactivate') {
    postcolors_deactivate();
    postcolors_cli_set_active(false);
} elseif ($cmd === 'activate') {
    postcolors_activate();
    postcolors_cli_set_active(true);
}

$count = static function (string $table, string $where) use ($db): int {
    return (int)$db->fetch_field($db->simple_select($table, 'COUNT(*) AS c', $where), 'c');
};
foreach (['postcolors_players', 'postcolors_colors', 'postcolors_teams', 'postcolors_teamcolors'] as $table) {
    echo str_pad($table, 26) . ($db->table_exists($table) ? 'EXISTS' : 'MISSING') . "\n";
}
echo str_pad('settings', 26) . $count('settings', "name LIKE 'postcolors\\_%'") . "\n";
echo str_pad('settinggroups', 26) . $count('settinggroups', "name = 'postcolors'") . "\n";
echo str_pad('templates', 26) . $count('templates', "title LIKE 'postcolors\\_%'") . "\n";
echo str_pad('templategroups', 26) . $count('templategroups', "prefix = 'postcolors'") . "\n";
echo str_pad('stylesheets', 26) . $count('themestylesheets', "name = 'postcolors.css'") . "\n";
echo str_pad('headerinclude edits', 26) . $count('templates', "title = 'headerinclude' AND template LIKE '%postcolors_css%'") . "\n";
echo str_pad('usercp nav edits', 26) . $count('templates', "template LIKE '%<!--postcolors_nav-->%'") . "\n";
echo str_pad('cache', 26) . (is_array($cache->read('postcolors')) ? 'SET' : 'EMPTY') . "\n";
