# H4W-Anpassungen (nur Branch `h4w`)

| Datei | Anpassung | Zweck |
|---|---|---|
| `inc/plugins/postcolors/migration/legacy_teams.php` | Die 9 Teamfarben, die in 1.x fest im Template `listen_postcolors` standen (Slugs `gryffindor` … `sonstige`, Tags `gry`, `rav`, `huf`, `sly`, `leh`, `stu`, `aur`, `jäg`, `son`) | Migration importiert sie als Teamfarben, damit die Tags in alten Beiträgen weiter färben |
| `inc/plugins/postcolors.php` (`postcolors_setting_definitions()`) | Standardwert von `postcolors_bg_variable` = `--h4w-main-bgcolor` | Vorschau-Hintergrund aus der Root-Variable des H4W-Themes |

Keine Template-Anpassungen: Der UserCP-Link wird beim Aktivieren in `usercp_nav_rpg` nach dem Muster eines vorhandenen Links eingefügt (generischer Code auf `main`).
