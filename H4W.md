# H4W-Anpassungen (nur Branch `h4w`)

| Datei | Anpassung | Zweck |
|---|---|---|
| `inc/plugins/postcolors/migration/legacy_teams.php` | Die 9 Teamfarben, die in 1.x fest im Template `listen_postcolors` standen (Slugs `gryffindor` … `sonstige`, Tags `gry`, `rav`, `huf`, `sly`, `leh`, `stu`, `aur`, `jäg`, `son`) | Migration importiert sie als Teamfarben, damit die Tags in alten Beiträgen weiter färben |
| `inc/plugins/postcolors.php` (`postcolors_setting_definitions()`) | Standardwert von `postcolors_bg_variable` = `--h4w-main-bgcolor` | Vorschau-Hintergrund aus der Root-Variable des H4W-Themes |
| `inc/plugins/postcolors.php` (`postcolors_install_template_edits()`, `postcolors_h4w_nav_row()`, `postcolors_remove_template_edits()`) | UserCP-Menüpunkt „Postingfarben“ nur im RPG-Menü der User-Accounts: Template `usercp_nav_rpg_user` (Group Templates, Gruppen 43/32, Master-Template `sid = -1` aus `mybb-stylekit/templates-master`), vor `{$usercp_nav_rpg_custom}`, mit Inline-SVG wie die übrigen Plugin-Zeilen. Der fest eingetragene 1.x-Link `usercp.php?action=postcolor` wird beim Aktivieren entfernt. Kein Eintrag in `usercp_nav_rpg`, `usercp_nav_rpg_chara` oder `usercp_nav_misc`. | Charaktere haben keine eigenen Farben; die Einstellungen gehören zum Account der Person, die spielt |

Die Menüzeile steht identisch (inkl. `<!--postcolors_nav-->`) auch in `mybb-stylekit/templates-master/usercp_nav_rpg_user.html`. Bei Änderungen an `postcolors_h4w_nav_row()` beide Stellen gleich halten.
