<?php
/**
 * Postcolors – Teamfarben, die die Migration aus 1.x anlegt.
 *
 * Version 1.x hatte keine Teamfarben-Verwaltung. Foren, die dort Teamfarben fest im Template
 * stehen hatten, tragen sie hier ein, damit die Migration sie als Teamfarben übernimmt
 * (Format: slug => [Titel, Namens-Tag, #RRGGBB]). Leer = keine Teamfarben importieren.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

return [];
