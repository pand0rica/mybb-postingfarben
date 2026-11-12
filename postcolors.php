<?php
/**
 * Postcolors – Übersicht aller Postingfarben (Teamfarben + Spieler*innen).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'postcolors.php');

$templatelist = 'postcolors_list,postcolors_list_content,postcolors_list_team,postcolors_list_player,'
    . 'postcolors_list_empty,postcolors_list_themeselect';

require_once './global.php';

$lang->load('postcolors');

if (!function_exists('postcolors_boot') || !postcolors_is_installed()) {
    error_no_permission();
}
if (empty($mybb->usergroup['canview'])) {
    error_no_permission();
}

postcolors_boot();
require_once POSTCOLORS_DIR . 'modules/list.php';
postcolors_list_page();
