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

return [
    'gryffindor' => ['Gryffindor', 'gry', '#B80B0B'],
    'ravenclaw' => ['Ravenclaw', 'rav', '#1C1FA9'],
    'hufflepuff' => ['Hufflepuff', 'huf', '#FAFD19'],
    'slytherin' => ['Slytherin', 'sly', '#0C941F'],
    'schulpersonal' => ['Schulpersonal', 'leh', '#1FAE9F'],
    'studenten' => ['Studenten', 'stu', '#82571F'],
    'ministerium' => ['Ministerium', 'aur', '#D56C2D'],
    'jaeger' => ['Jäger', 'jäg', '#7B037A'],
    'sonstige' => ['Sonstige', 'son', '#EB8E17'],
];
