# Postcolors – Agent Instructions

Dieses Repo wird mit dem **MyBB Plugin Dev Kit** entwickelt. Bevor du hier etwas tust:

1. Lies zuerst das Dev-Kit: **`<Dev-Kit>/AGENTS.md`** und halte dich an alle dort
   verlinkten Standards. Das Kit ist die kanonische Quelle für Konventionen, Boilerplate,
   Debugging und Testing.
2. Lies dann in diesem Repo: `PROJECT_CONTEXT.md` (Kontext) und – in der Umsetzungsphase –
   `IMPLEMENTATION_PLAN.md` (die Arbeitsschritte).

Das Dev-Kit wird **nicht** in dieses Repo kopiert, sondern referenziert. `<Dev-Kit>` ist der
Ort, an dem das Kit in deiner Umgebung liegt (z. B. das angehängte Repo `pand0rica/mybb-devkit`).
Liegt es nicht vor, frag danach, statt zu raten.

## Sprache
Antworten Deutsch, Code/Bezeichner Englisch. PHP 7.4 + 8.x. Keine externen Libraries.
Alle Texte (Begleitdateien, Sprachdateien, Templates, Settings) geschlechtergerecht:
genderneutral, sonst Gender-Sternchen; Englisch neutral (Kit `standards/conventions.md` §10).

## Phasen (werden einzeln ausgelöst)

| Eingabe | Phase | Folge dieser Kit-Datei |
|---|---|---|
| „umsetzen / implementieren" | Umsetzung | `<Dev-Kit>/workflows/2-implement.md` + `IMPLEMENTATION_PLAN.md` |
| „reviewe das Projekt" | Review | `<Dev-Kit>/workflows/3-review.md` |
| „release das Projekt" | Release | `<Dev-Kit>/workflows/4-release.md` |

## Die 5 goldenen Regeln (Kurzform, Details im Kit)
Keine Core-Edits · `TABLE_PREFIX` · `$mybb->get_input()` · POST + `verify_post_check()` ·
alles mit Präfix `postcolors_`. Ausgabe `htmlspecialchars_uni()`, SQL `escape_string()`/`(int)`.

## Branches
`main` = generisches Plugin. `h4w` = `main` + Forum-Anpassungen (siehe `H4W.md` auf `h4w`).
Immer `main` → `h4w` mergen, nie umgekehrt.
