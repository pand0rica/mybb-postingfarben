# Postcolors – Start-Prompt für Phase 2 (Umsetzung)

Du setzt das MyBB-1.8-Plugin **Postcolors** (Codename `postcolors`) um. Das
Umsetzungs-Paket aus Phase 1 liegt bereits vollständig im Repo:

  <Plugin-Repo>

Lies in dieser Reihenfolge, BEVOR du irgendetwas tust:
1. `AGENTS.md` (dieses Repo) – Pointer aufs Dev-Kit, 5 goldene Regeln, Phasen-Zuordnung.
2. `PROJECT_CONTEXT.md` (dieses Repo) – Architektur, Tabellen, Settings, Hooks, Abhängigkeiten,
   „What NOT to change".
3. `IMPLEMENTATION_PLAN.md` (dieses Repo) – die nummerierten Arbeitsschritte.
4. Aus dem Dev-Kit bei Bedarf: `<Dev-Kit>/standards/boilerplate.md` und
   `<Dev-Kit>/standards/conventions.md`.

Folge danach exakt `<Dev-Kit>/workflows/2-implement.md`:
- Arbeite die Schritte aus `IMPLEMENTATION_PLAN.md` der Reihe nach ab und hake sie dort ab.
- Halte die 5 goldenen Regeln ein (keine Core-Edits, `TABLE_PREFIX`, `get_input()`,
  POST + `verify_post_check()`, Präfix `postcolors_`).
- Bei Unklarheiten gezielt nachfragen – **nicht neu planen**.
- Sprachdateien: englisch + deutsch_du, Frontend- und Admin-Sets strikt getrennt.
- Alle Texte geschlechtergerecht.

Abhängigkeit: Enhanced Account Switcher ist optional (empfohlen); es muss vorher kein anderes
Plugin umgesetzt oder migriert werden.

Antworten auf Deutsch, Code/Bezeichner/Kommentare auf Englisch, PHP 7.4 + 8.x, keine externen
Libraries.
