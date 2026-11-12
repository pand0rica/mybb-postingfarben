# Postcolors 2.0 – Implementation Plan

**Umsetzung:** folge `<Dev-Kit>/workflows/2-implement.md`. Schritte der Reihe nach.
Bei Unklarheit gezielt nachfragen, aber nicht umplanen. Kontext: `PROJECT_CONTEXT.md`.

---

## Schritt 1 – Grundgerüst & Zugriffsschutz
- [x] **Ziel:** Hauptdatei, `info()`, Hook-Registrierung, Autoload der Core-Klassen.
- **Dateien:** `inc/plugins/postcolors.php`
- **Vorlage:** `standards/boilerplate.md` §1, §2
- **Abnahme:** `php -l`; Plugin erscheint in der Pluginliste.

## Schritt 2 – Lifecycle (install/is_installed/uninstall)
- [x] **Ziel:** 4 Tabellen (utf8mb4, InnoDB), Settings idempotent; vorhandene Alt-Settinggroup
  `postcolors` + Alt-Setting `postcolors_username_fid` übernehmen; `is_installed()` prüft
  `postcolors_players`; Uninstall entfernt Tabellen, Settings (Gruppe + Einträge), Templates,
  Templategruppe, Stylesheet, Cache.
- **Vorlage:** `boilerplate.md` §3, §4 · `conventions.md` §4
- **Abnahme:** `tests/cli-plugin.php install|uninstall|status` – nach Uninstall alle Zähler 0.

## Schritt 3 – activate()/deactivate()
- [x] **Ziel:** Templates (Gruppe „Postcolors“, Prefix `postcolors`) aus `.tpl`, Stylesheet
  `postcolors.css` im Master-Theme, `{$postcolors_css}` in `headerinclude`, UserCP-Link in
  `usercp_nav_rpg` (falls vorhanden) sonst `usercp_nav_misc`. Deactivate spiegelt exakt.
- **Vorlage:** `boilerplate.md` §5, §6, §8, §9, §14
- **Abnahme:** CLI install → uninstall → Zähler 0; Template-Edits verschwinden.

## Schritt 4 – Repository, ThemeHelper, TagRegistry, CacheBuilder
- [x] **Ziel:** DB-Zugriffe, erlaubte Themes, Hintergrund aus `themestylesheets`
  (Eltern-Kette), Tag-Validierung/Konflikte, Datacache `postcolors`.
- **Abnahme:** `tests/cli-selftest.php` (Cache-Inhalt, Konflikte, Farbauflösung).

## Schritt 5 – SpeechDetector + CLI-Test
- [x] **Ziel:** Regeln aus der Spezifikation (Kontext für gleiche Zeichen, Apostroph-Heuristik,
  Absatzgrenzen, Romanschreibweise, unbalanciert = nichts färben).
- **Dateien:** `core/SpeechDetector.php`, `core/SpeechColorizer.php`, `tests/speech_detector_test.php`
- **Abnahme:** `php tests/speech_detector_test.php` – ≥ 20 Fälle grün.

## Schritt 6 – MyCodeRenderer (Namens-Tags + [pc])
- [x] **Ziel:** Platzhalter/Spans, Verschachtelung, Code-Bereiche auslassen, Speichern
  (`[pc]` → `[pc=pX]`, Zitate unangetastet), Bearbeiten (`[pc=pX]` → `[pc]`), Vorschau-Kontext.
- **Abnahme:** Selbsttest + HTTP-Flows (Thread mit HTML aus/an, Vorschau, Quick-Edit).

## Schritt 7 – Frontend: UserCP, Übersicht, CSS-Ausgabe, Auto-Färbung
- [x] **Dateien:** `modules/usercp.php`, `modules/list.php` + `postcolors.php`, Templates, `jscripts/postcolors.js`
- **Abnahme:** Playwright: Picker, Reset, Speichern, Vorschau; Liste pro Theme.

## Schritt 8 – Editor
- [x] **Ziel:** SCEditor-Button + Formate (`[pc]`, Namens-Tags im WYSIWYG), CSS im iframe.
- **Dateien:** `jscripts/postcolors_editor.js`
- **Vorlage:** `boilerplate.md` §16, `debugging.md` Punkt 25, 27
- **Abnahme:** Playwright Round-Trip WYSIWYG ↔ Quelltext ohne Verlust.

## Schritt 9 – ACP-Modul + Migration
- [x] **Dateien:** `admin/modules/config/postcolors.php`, `migration/MigrateFromV1.php`
- **Vorlage:** `boilerplate.md` §12
- **Abnahme:** HTTP-Flows im ACP (Team anlegen/bearbeiten/löschen/sortieren, Probelauf,
  Migration, Alttabelle löschen) auf der Testinstanz.

## Schritt 10 – Sprachdateien
- [x] english + deutsch_du, Frontend + Admin getrennt.
- **Abnahme:** `<Dev-Kit>/scripts/lang_check.py`.

## Schritt 11 – Begleitdateien
- [x] README (inkl. Upgrade von 1.x), CHANGELOG, PLUGIN_POST.html, LICENSE, FUNDING, .gitignore,
  .claudeignore, docs/screenshots/.gitkeep.
