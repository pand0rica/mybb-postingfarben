# Postcolors – Project Context

## Identität
- **Name:** Postcolors
- **Codename / Dateiname:** `postcolors` (Präfix: `postcolors_`)
- **Version:** 2.0.0 (kompletter Neubau, ersetzt 1.x)
- **Entwickelt von:** pand0rica
- **Kurzbeschreibung:** Postingfarben pro Spieler*in und Theme, Teamfarben, Namens-Tags
  (`<sarah>…</sarah>`), MyCode `[pc]`, Editor-Button und automatische Färbung wörtlicher Rede.
- **MyBB:** 1.8.x · **PHP:** 7.4 + 8.x

## Zweck & Zielgruppe
RPG-Foren, in denen jede Person, die spielt (mit allen Charakter-Accounts), eine eigene
Schriftfarbe für Beiträge hat – optional pro Theme unterschiedlich, damit die Farbe auf hellen
und dunklen Styles lesbar bleibt. Gefärbt wird über Namens-Tags (Hauptweg), `[pc]` oder
automatisch (wörtliche Rede). Zusätzlich verwaltet das Team globale Teamfarben (z. B. Häuser).

## Architektur
- **Variante:** Klassen-/Core-Split (viele Hooks, Parser-Logik, Migration, ACP mit Tabs).
- **Hauptdatei** `inc/plugins/postcolors.php`: info, Lifecycle, Hook-Registrierung, dünne
  Hook-Handler, die an die Core-Klassen delegieren.
- **Core** `inc/plugins/postcolors/core/`:
  - `Repository.php` – alle DB-Zugriffe (Spieler*innen, Farben, Teams, Themes).
  - `CacheBuilder.php` – baut den Datacache `postcolors` (CSS pro Theme, Tag-Liste, Teams,
    Sprech-Einstellungen, Theme-Hintergründe).
  - `ThemeHelper.php` – erlaubte Themes, Hintergrundfarbe aus `themestylesheets` (inkl.
    Eltern-Themes), Farbvalidierung, WCAG-Kontrast.
  - `TagRegistry.php` – Validierung von Tags, reservierte HTML-Elementnamen, Konfliktauflösung.
  - `MyCodeRenderer.php` – Namens-Tags + `[pc]`: Platzhalter in `parse_message_start`,
    Spans in `parse_message_end`, Umschreiben beim Speichern/Bearbeiten.
  - `SpeechDetector.php` – reine String-API (testbar per CLI), erkennt wörtliche Rede.
  - `SpeechColorizer.php` – wendet den Detector auf Textknoten von HTML an (überspringt Tags,
    Zitate, Code, Links, bereits gefärbte Passagen).
- **Module** `inc/plugins/postcolors/modules/usercp.php`, `modules/list.php` (Übersicht, aufgerufen von `postcolors.php` im MyBB-Root).
- **Migration** `inc/plugins/postcolors/migration/MigrateFromV1.php`.
- **ACP** `admin/modules/config/postcolors.php` (Tabs: Teamfarben, Spieler*innen, Werkzeuge,
  Migration – letzterer nur, solange `{prefix}colors` existiert).
- **JS** `jscripts/postcolors.js` (UserCP/ACP-Picker), `jscripts/postcolors_editor.js` (SCEditor).

## Datenbank-Tabellen (alle `utf8mb4`, InnoDB)
| Tabelle (`TABLE_PREFIX .`) | Zweck | Wichtige Spalten |
|---|---|---|
| `postcolors_players` | Einstellungen pro Spieler*in | `pid` PK (= uid des Hauptaccounts), `custom_tag` VARCHAR(50) NULL UNIQUE (utf8mb4_bin, klein gespeichert), `speech_enabled` TINYINT, `quote_presets` VARCHAR(100) (kommagetrennte Voreinstellungen, Mehrfachauswahl), `quote_open`/`quote_close` VARCHAR(3) (eigenes Paar), `updated` INT |
| `postcolors_colors` | Farben pro Spieler*in | `pid`, `tid` (0 = Master), `color` CHAR(7); PK (`pid`,`tid`) |
| `postcolors_teams` | Teamfarben | `teamid` PK, `title`, `slug` UNIQUE, `tag` UNIQUE (utf8mb4_bin, klein), `disporder`, `active` |
| `postcolors_teamcolors` | Farben pro Team | `teamid`, `tid` (0 = Master), `color`; PK (`teamid`,`tid`) |

Farbe immer `#RRGGBB` (Großbuchstaben). Auflösung: Theme-Farbe → Master → keine.

## Spieler*innen-Identität
`pid = as_uid > 0 ? as_uid : uid` (Enhanced Account Switcher). Die Zuordnung uid → as_uid
kommt aus dem Datacache **`accountswitcher`** des Account Switchers (enthält alle Accounts mit
`as_uid != 0`; der Account Switcher baut ihn nach jeder Änderung selbst neu, eigene Hooks hat
er nicht). Ohne Account Switcher (Spalte `users.as_uid` fehlt): `pid = uid`, Hinweis im ACP.
Für den eingeloggten Account wird `$mybb->user['as_uid']` direkt gelesen.

## Datacache `postcolors`
`css[tid]` (fertiger CSS-String, `0` = nur Master-Farben für unbekannte Themes),
`tags[tag] = 'p12' | 't3'`, `slugs[slug] = teamid`, `teams[]` (für Editor/Liste),
`players[pid] = [tag, speech_open, speech_close]`, `known[]` (pids mit Farbe),
`bg[tid]`, `conflicts[]`. Gebaut nur bei Änderungen (UserCP, ACP, Migration,
Theme-Löschung/-Änderung, Profilfeld-Änderung, Settings-Änderung).

## Settings (settinggroup `postcolors`)
| Setting | optionscode | Zweck |
|---|---|---|
| `postcolors_enabled` | yesno | Plugin an/aus (CSS, Parser, UserCP, Liste) |
| `postcolors_usergroups` | groupselect | Wer Farben setzen darf (Gruppen des eingeloggten Accounts) |
| `postcolors_username_fid` | numeric | Profilfeld mit dem Namen der Person, die spielt (Tag + Anzeige + Migration) |
| `postcolors_bg_variable` | text | CSS-Variable des Theme-Hintergrunds für die Vorschau (main: leer) |
| `postcolors_bg_fallback` | text | Fallback-Hintergrund `#RRGGBB` |
| `postcolors_bold` | yesno | Gefärbter Text fett |
| `postcolors_element_selectors` | yesno | Element-Selektoren (`sarah{…}`) zusätzlich ausgeben |
| `postcolors_editor_format` | select | Editor-Button fügt Namens-Tag oder `[pc]` ein |
| `postcolors_speech_allowed` | yesno | Auto-Färbung wörtlicher Rede global erlaubt |
| `postcolors_speech_forums` | forumselect | Foren mit Auto-Färbung |
| `postcolors_speech_pm` | yesno | Auto-Färbung in PNs |
| `postcolors_list_layout_template` | text | Rahmen-Template für `postcolors.php` (z. B. `listen`), leer = eigene Seite |
| `postcolors_list_nav_template` | text | Navigations-Template dazu (z. B. `listen_nav`) |

## Hooks (alle im Core 1.8.41 per `run_hooks` nachgewiesen)
| Hook | Zweck |
|---|---|
| `global_start` | Sprachdatei laden, Variablen initialisieren |
| `global_intermediate` | `$postcolors_css` (Theme-CSS aus Cache) für `{$postcolors_css}` in `headerinclude` |
| `pre_output_page` | Editor-Script + JSON-Konfiguration in `<head>` (nur Seiten mit SCEditor) |
| `archive_start` | CSS im Archiv (kein `headerinclude`) |
| `parse_message_start` / `parse_message_end` | Namens-Tags + `[pc]` → Spans; Speech-Kontext |
| `text_parse_message` | Tags in Text-Ausgaben entfernen |
| `postbit`, `postbit_prev`, `postbit_pm`, `postbit_announcement` | Auto-Färbung |
| `newreply_threadreview_post`, `portal_announcement` | Speech-Kontext vor dem Parsen setzen |
| `printthread_post`, `archive_thread_post`, `search_results_post`, `xmlhttp_update_post` | Auto-Färbung nach dem Parsen |
| `xmlhttp_edit_post_end` | Quick-Edit: Rohtext `[pc=pX]` → `[pc]`; Autor für Parse setzen |
| `editpost_end`, `private_send_end` | Editor-Inhalt `[pc=pX]` → `[pc]` |
| `datahandler_post_validate_post`, `datahandler_post_validate_thread`, `datahandler_pm_validate` | `[pc]` → `[pc=pX]` vor dem Speichern |
| `datahandler_user_update` | Cache neu bauen, wenn das Namens-Profilfeld sich ändert |
| `datahandler_user_delete_end` | Daten gelöschter Hauptaccounts entfernen |
| `usercp_start`, `usercp_menu` | UserCP-Seite `usercp.php?action=postcolors` |
| `misc_start` | Alte Adresse `misc.php?action=postcolors` → 301 auf `postcolors.php` |
| `admin_config_menu`, `admin_config_action_handler`, `admin_config_permissions` | ACP-Modul |
| `admin_style_themes_delete_commit`, `admin_style_themes_edit_stylesheet_*_commit`, `admin_style_themes_add_commit`, `admin_style_themes_import_commit`, `admin_style_themes_duplicate_commit` | Farbzeilen entfernen / Cache neu |
| `admin_config_settings_change_commit` | Cache neu (CSS hängt an Settings) |

## Eigene Hooks / API
Öffentliche Funktionen: `postcolors_apply_speech(string $html, int $author_uid, int $fid = 0): string`,
`postcolors_resolve_pid(int $uid): int`, `postcolors_rebuild_cache(): void`.

## Abhängigkeiten
- **Enhanced Account Switcher** (optional, empfohlen) – Erkennung über Spalte `users.as_uid`
  und Datacache `accountswitcher`.

## Berechtigungen
- UserCP-Formular: eingeloggt + Gruppe in `postcolors_usergroups` (Haupt- oder Zusatzgruppe
  des eingeloggten Accounts). Gespeicherte Farben werden unabhängig davon angezeigt.
- Theme-Farben nur für Themes, die der Account nutzen darf (`allowedgroups` per explode).
- ACP: Modul-Berechtigung `config/postcolors`.

## What NOT to change
- Namens-Tags sind der dauerhafte Hauptweg, keine Konvertierung von Beiträgen.
- `[pc=pX]` innerhalb von `[quote]` wird beim Speichern **nie** umgeschrieben – Zitate behalten
  die Farbe der ursprünglich schreibenden Person. `[pc]` ohne Parameter in einem Zitat mit
  `pid=` bekommt die Farbe der Person, die den zitierten Beitrag geschrieben hat.
- Namens-Tags in `[code]`/`[php]` bleiben Text.
- Pc-Spans umschließen nur Textläufe (vor jedem HTML-Tag geschlossen, danach wieder geöffnet),
  damit nie ungültig verschachteltes HTML entsteht.
- Auto-Färbung wird bei der Anzeige angewendet, der gespeicherte Text bleibt unverändert.
- Tag-Spalten mit `utf8mb4_bin`, Tags klein gespeichert (`unicode_ci` würde `ä = a` werten).
- Kein eigener uid→pid-Cache: der `accountswitcher`-Cache ist die Quelle.
- Forum-spezifisches (Hogwarts-Teamfarben, `--h4w-main-bgcolor`) nur auf Branch `h4w`.
