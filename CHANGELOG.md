# Changelog

## [2.0.0] – 2026-11-12

Complete rewrite. Upgrade path from 1.x: see README → *Upgrade from 1.x*.

### New
- Colours belong to the **player** (main account of Enhanced Account Switcher) and are shared by
  all linked accounts.
- **Master colour** plus optional colour per theme (theme colour → master colour → none).
- **Team colours** in the ACP (title, slug, name tag, master + per-theme colours, order, active).
- **Name tags** (`<sarah>…</sarah>`, `<gry>…</gry>`) are processed by the plugin itself and work
  regardless of the forum's HTML setting – in posts, private messages, previews, signatures,
  portal, search, print view and archive. Correct nesting, unclosed tags are closed.
- **MyCode `[pc]` / `[pc=slug]`**; `[pc]` is stored as the author's id so it renders anywhere;
  quotes keep the colour of the quoted author.
- **Editor button** with dropdown (own colour, team colours); coloured display in the WYSIWYG
  editor with lossless switching to source mode.
- **Automatic colouring of direct speech** with several quotation mark pairs at once (checkboxes,
  plus an own pair), applied when the post is displayed (stored text stays unchanged);
  apostrophe-aware, nested quotes are coloured once.
- **User CP page** with one form, colour picker + hex field, live preview on the theme
  background (CSS variables incl. `var()` chains and parent-theme inheritance are resolved),
  contrast warning, own tag; colour rows are stacked on narrow screens.
- **Overview** `postcolors.php` with theme selection and tags to copy; optionally embedded into
  the frame of the forum's other lists (frame + navigation template). The address
  `misc.php?action=postcolors` redirects there.
- **Migration from 1.x** with dry run, conflict selection, report and admin log entry.
- English and German (du) language files.

### Security
- Colours are validated as `#RRGGBB` before they reach the database or CSS (1.x allowed stored
  XSS through the colour field).
- All queries escaped / cast; all forms protected with `my_post_key`; theme permissions checked
  per user group.

### Fixed
- 1.x matched user groups with `LIKE '%1%'` (also 11, 12, 21 …).
- 1.x read colours with two queries on every page; 2.0 serves everything from a datacache.
- 1.x hard-coded the table prefix and a theme id.
- Uninstall now removes all settings, templates, the template group, the stylesheet and the cache.
