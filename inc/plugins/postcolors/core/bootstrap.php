<?php
/**
 * Postcolors – lädt die Core-Klassen (ohne Speech-Klassen, die nur bei Bedarf geladen werden).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

require_once __DIR__ . '/ThemeHelper.php';
require_once __DIR__ . '/TagRegistry.php';
require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/CacheBuilder.php';
require_once __DIR__ . '/MyCodeRenderer.php';
require_once __DIR__ . '/Quotes.php';
