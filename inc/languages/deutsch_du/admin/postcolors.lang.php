<?php
/**
 * Postcolors 2.0 – Deutsche Sprachdatei (Admin-CP)
 */

$l['postcolors'] = 'Postcolors';
$l['postcolors_plugin_description'] = 'Postingfarben pro Spieler*in und Theme, Teamfarben, Namens-Tags, MyCode [pc] und automatische Färbung wörtlicher Rede.';
$l['postcolors_admin_permission'] = 'Darf Postcolors verwalten?';
$l['postcolors_error_inactive'] = 'Postcolors ist nicht aktiv.';

// Einstellungen
$l['setting_group_postcolors'] = 'Postcolors';
$l['setting_group_postcolors_desc'] = 'Einstellungen für Postingfarben, Teamfarben, Namens-Tags und wörtliche Rede.';
$l['setting_postcolors_enabled'] = 'Postcolors aktivieren';
$l['setting_postcolors_enabled_desc'] = 'Schaltet Farbausgabe, Namens-Tags, [pc], die UserCP-Seite und die Übersicht an oder aus.';
$l['setting_postcolors_usergroups'] = 'Wer darf Farben festlegen?';
$l['setting_postcolors_usergroups_desc'] = 'Benutzergruppen (Haupt- oder Zusatzgruppe des angemeldeten Accounts), die die UserCP-Seite nutzen dürfen. Gespeicherte Farben werden immer angezeigt.';
$l['setting_postcolors_username_fid'] = 'Profilfeld „Spieler*innen-Name“';
$l['setting_postcolors_username_fid_desc'] = 'ID des Profilfelds mit dem Namen der Person, die spielt (z. B. 4 für fid4). Der Wert des Hauptaccounts dient als Namens-Tag und Anzeigename sowie für die Migration aus 1.x.';
$l['setting_postcolors_bg_variable'] = 'CSS-Variable für den Hintergrund';
$l['setting_postcolors_bg_variable_desc'] = 'Name einer CSS-Variable in den Theme-Stylesheets, die den Beitragshintergrund enthält (z. B. --main-bgcolor). Wird für die Farbvorschau verwendet. Leer lassen, um immer den Fallback zu nutzen.';
$l['setting_postcolors_bg_fallback'] = 'Fallback-Hintergrundfarbe';
$l['setting_postcolors_bg_fallback_desc'] = 'Hintergrundfarbe der Vorschau, wenn die Variable nicht gefunden wird (#RRGGBB).';
$l['setting_postcolors_bold'] = 'Gefärbten Text fett darstellen';
$l['setting_postcolors_bold_desc'] = 'Gefärbter Text wird fett angezeigt.';
$l['setting_postcolors_element_selectors'] = 'Element-Selektoren';
$l['setting_postcolors_element_selectors_desc'] = 'Namens-Tags zusätzlich als CSS-Element-Selektoren ausgeben (sarah{…}), damit auch HTML gefärbt wird, das nicht durch den Parser läuft.';
$l['setting_postcolors_editor_format'] = 'Editor-Button fügt ein';
$l['setting_postcolors_editor_format_desc'] = 'Womit der Editor-Button den markierten Text umschließt.';
$l['setting_postcolors_editor_format_tag'] = 'Namens-Tag (<name>…</name>)';
$l['setting_postcolors_editor_format_pc'] = 'MyCode ([pc]…[/pc])';
$l['setting_postcolors_speech_allowed'] = 'Automatische Färbung wörtlicher Rede erlauben';
$l['setting_postcolors_speech_allowed_desc'] = 'Spieler*innen dürfen im UserCP die automatische Färbung wörtlicher Rede einschalten.';
$l['setting_postcolors_speech_forums'] = 'Foren mit automatischer Färbung';
$l['setting_postcolors_speech_forums_desc'] = 'Foren, in denen wörtliche Rede automatisch gefärbt wird.';
$l['setting_postcolors_speech_pm'] = 'Automatische Färbung in PNs';
$l['setting_postcolors_speech_pm_desc'] = 'Wörtliche Rede auch in privaten Nachrichten färben.';

// Admin-Log
$l['admin_log_config_postcolors_add_team'] = 'Teamfarbe #{1} ({2}) angelegt';
$l['admin_log_config_postcolors_edit_team'] = 'Teamfarbe #{1} ({2}) bearbeitet';
$l['admin_log_config_postcolors_delete_team'] = 'Teamfarbe #{1} ({2}) gelöscht';
$l['admin_log_config_postcolors_order'] = 'Reihenfolge der Teamfarben gespeichert';
$l['admin_log_config_postcolors_delete_player'] = 'Farben von Spieler*in #{1} gelöscht';
$l['admin_log_config_postcolors_tools'] = 'Postcolors-Cache neu aufgebaut';
$l['admin_log_config_postcolors_migration'] = 'Postcolors-Migration aus 1.x: {1} {2}';

// Tabs
$l['postcolors_tab_teams'] = 'Teamfarben';
$l['postcolors_tab_teams_desc'] = 'Globale Teamfarben (z. B. Häuser, Gruppen). Jedes Team kann über seinen Namens-Tag oder mit [pc=slug] verwendet werden.';
$l['postcolors_tab_add_team'] = 'Teamfarbe anlegen';
$l['postcolors_tab_add_team_desc'] = 'Neue Teamfarbe mit Master-Farbe und optionalen Farben pro Theme anlegen.';
$l['postcolors_tab_edit_team'] = 'Teamfarbe bearbeiten';
$l['postcolors_tab_edit_team_desc'] = 'Titel, Slug, Namens-Tag und Farben der Teamfarbe ändern.';
$l['postcolors_tab_players'] = 'Spieler*innen';
$l['postcolors_tab_players_desc'] = 'Alle, die eine Farbe festgelegt haben. Farben gehören zum Hauptaccount (Account Switcher).';
$l['postcolors_tab_tools'] = 'Werkzeuge';
$l['postcolors_tab_tools_desc'] = 'Status und Cache.';
$l['postcolors_tab_migration'] = 'Migration';
$l['postcolors_tab_migration_desc'] = 'Übernimmt die Farben aus Postcolors 1.x (Tabelle colors). Starte zuerst einen Probelauf.';

// Allgemein
$l['postcolors_theme'] = 'Theme';
$l['postcolors_preview'] = 'Vorschau';
$l['postcolors_color'] = 'Farbe';
$l['postcolors_master'] = 'Master-Farbe';
$l['postcolors_master_desc_team'] = 'Gilt für jedes Theme ohne eigene Farbe.';
$l['postcolors_uses_master'] = 'Verwendet die Master-Farbe.';
$l['postcolors_sample'] = '„So sieht der Text aus.“';
$l['postcolors_low_contrast'] = 'Geringer Kontrast – diese Farbe ist auf diesem Hintergrund eventuell schlecht lesbar.';
$l['postcolors_none'] = 'keine';
$l['postcolors_reset'] = 'Master verwenden';
$l['postcolors_clear'] = 'Entfernen';
$l['postcolors_save'] = 'Speichern';
$l['postcolors_save_order'] = 'Reihenfolge speichern';
$l['postcolors_controls'] = 'Optionen';
$l['postcolors_options'] = 'Optionen';
$l['postcolors_edit'] = 'Bearbeiten';
$l['postcolors_delete'] = 'Löschen';
$l['postcolors_yes'] = 'Ja';
$l['postcolors_no'] = 'Nein';
$l['postcolors_player'] = 'Spieler*in';
$l['postcolors_tag_current'] = 'Namens-Tag';
$l['postcolors_theme_colors'] = 'Theme-Farben';
$l['postcolors_speech_short'] = 'Wörtliche Rede';
$l['postcolors_list_no_teams'] = 'Es gibt noch keine Teamfarben.';
$l['postcolors_list_no_players'] = 'Bisher hat noch niemand eine Farbe festgelegt.';
$l['postcolors_conflict_invalid'] = 'Name „{1}“ ist kein gültiger Tag.';
$l['postcolors_conflict_reserved'] = 'Name „{1}“ ist ein HTML-Elementname.';
$l['postcolors_conflict_taken'] = 'Tag „{1}“ ist bereits vergeben.';
$l['postcolors_no_account_switcher'] = 'Der Enhanced Account Switcher ist nicht installiert: Jeder Account gilt als eigene*r Spieler*in.';

// Teams
$l['postcolors_team_title'] = 'Titel';
$l['postcolors_team_title_desc'] = 'z. B. Gryffindor';
$l['postcolors_team_slug'] = 'Slug';
$l['postcolors_team_slug_desc'] = 'Wird im MyCode [pc=slug] verwendet. Kleinbuchstaben, Ziffern, _ und -, max. 30 Zeichen, eindeutig.';
$l['postcolors_team_tag'] = 'Namens-Tag';
$l['postcolors_team_tag_desc'] = 'Optional. Damit kann jede*r Text mit &lt;tag&gt;…&lt;/tag&gt; färben. Muss über Teams und Spieler*innen hinweg eindeutig sein.';
$l['postcolors_team_disporder'] = 'Sortierung';
$l['postcolors_team_active'] = 'Aktiv';
$l['postcolors_team_colors'] = 'Farben';
$l['postcolors_team_colors_desc'] = 'Master-Farbe plus optional eine Farbe pro Theme. Leere Theme-Felder verwenden die Master-Farbe.';
$l['postcolors_team_saved'] = 'Die Teamfarbe wurde gespeichert.';
$l['postcolors_team_deleted'] = 'Die Teamfarbe wurde gelöscht.';
$l['postcolors_order_saved'] = 'Die Reihenfolge wurde gespeichert.';
$l['postcolors_confirm_delete_team'] = 'Willst du die Teamfarbe „{1}“ wirklich löschen?';
$l['postcolors_error_team_missing'] = 'Diese Teamfarbe existiert nicht.';
$l['postcolors_error_title'] = 'Bitte gib einen Titel ein (max. 100 Zeichen).';
$l['postcolors_error_slug'] = 'Der Slug darf nur Kleinbuchstaben, Ziffern, _ und - enthalten (max. 30 Zeichen) und nicht wie p123 aussehen.';
$l['postcolors_error_slug_taken'] = 'Dieser Slug wird bereits von einer anderen Teamfarbe verwendet.';
$l['postcolors_error_tag_format'] = 'Der Tag muss mit einem Buchstaben beginnen und darf nur Buchstaben, Ziffern, Umlaute, ß, _ und - enthalten (max. 50 Zeichen).';
$l['postcolors_error_tag_reserved'] = 'Dieser Tag ist ein HTML-Elementname und kann nicht verwendet werden.';
$l['postcolors_error_tag_taken'] = 'Dieser Tag wird bereits von einer Person oder einer anderen Teamfarbe verwendet.';
$l['postcolors_error_color_acp'] = 'Mindestens eine Farbe ist ungültig. Bitte verwende das Format #RRGGBB.';

// Spieler*innen
$l['postcolors_player_deleted'] = 'Die Farben wurden gelöscht.';
$l['postcolors_confirm_delete_player'] = 'Willst du wirklich alle Farben und Einstellungen dieser Person löschen?';

// Werkzeuge
$l['postcolors_status_as'] = 'Enhanced Account Switcher';
$l['postcolors_status_fid'] = 'Profilfeld „Spieler*innen-Name“';
$l['postcolors_status_fid_missing'] = 'nicht gesetzt oder nicht gefunden – Namens-Tags aus dem Profil sind nicht verfügbar';
$l['postcolors_status_tags'] = 'Aktive Namens-Tags';
$l['postcolors_status_conflicts'] = 'Tag-Konflikte';
$l['postcolors_status_built'] = 'Cache erstellt';
$l['postcolors_rebuild_cache'] = 'Cache neu aufbauen';
$l['postcolors_cache_rebuilt'] = 'Der Cache wurde neu aufgebaut.';

// Migration
$l['postcolors_migration_pending'] = 'Die Tabelle von Postcolors 1.x existiert noch. Unter „Migration“ kannst du die Farben übernehmen.';
$l['postcolors_migration_intro'] = 'Die Migration übernimmt die Farben aus der 1.x-Tabelle <code>colors</code>. Personen werden über das Profilfeld aus den Einstellungen gefunden und ihrem Hauptaccount zugeordnet. Beiträge werden nicht verändert – Namens-Tags wie &lt;sarah&gt; funktionieren weiter. Starte mit einem Probelauf; er schreibt nichts.';
$l['postcolors_migration_dry'] = 'Probelauf – es wurde noch nichts geschrieben. Prüfe den Bericht, wähle bei Konflikten den Hauptaccount und führe dann die Migration aus.';
$l['postcolors_migration_done'] = 'Die Migration wurde ausgeführt.';
$l['postcolors_migration_no_legacy'] = 'Die Tabelle von 1.x existiert nicht.';
$l['postcolors_migration_error_legacy_missing'] = 'Die Tabelle von 1.x existiert nicht.';
$l['postcolors_migration_error_tables_missing'] = 'Die Tabellen von Postcolors 2.0 fehlen. Bitte installiere zuerst das Plugin.';
$l['postcolors_migration_error_fid_missing'] = 'Das Profilfeld „Spieler*innen-Name“ ist nicht gesetzt oder existiert nicht. Bitte prüfe die Postcolors-Einstellungen.';
$l['postcolors_migration_options'] = 'Optionen';
$l['postcolors_migration_derive'] = 'Master-Farbe automatisch ableiten';
$l['postcolors_migration_derive_desc'] = 'Haben alle Einträge einer Person dieselbe Farbe, wird sie zur Master-Farbe. Sonst wird die häufigste Farbe zur Master-Farbe und nur abweichende Themes behalten eine eigene Farbe.';
$l['postcolors_migration_overwrite'] = 'Vorhandene 2.0-Farben überschreiben';
$l['postcolors_migration_overwrite_desc'] = 'Personen, die in 2.0 schon Farben haben, werden standardmäßig übersprungen (damit die Migration mehrfach ausgeführt werden kann).';
$l['postcolors_migration_preview'] = 'Probelauf';
$l['postcolors_migration_run'] = 'Migration ausführen';
$l['postcolors_migration_players'] = 'Spieler*innen';
$l['postcolors_migration_old_names'] = 'Name in 1.x';
$l['postcolors_migration_colors'] = 'Farben';
$l['postcolors_migration_status'] = 'Status';
$l['postcolors_migration_status_migrated'] = 'übernommen';
$l['postcolors_migration_status_exists'] = 'übersprungen – hat in 2.0 bereits Farben';
$l['postcolors_migration_status_error'] = 'Fehler';
$l['postcolors_migration_note_merged'] = 'mehrere 1.x-Namen zusammengeführt';
$l['postcolors_migration_note_master_all'] = 'überall dieselbe Farbe → Master-Farbe';
$l['postcolors_migration_note_master_frequent'] = 'häufigste Farbe → Master-Farbe';
$l['postcolors_migration_note_exists'] = 'vorhandene Farben bleiben';
$l['postcolors_migration_note_error'] = 'konnte nicht gespeichert werden';
$l['postcolors_migration_nothing'] = 'Keine Farben zu übernehmen.';
$l['postcolors_migration_conflicts'] = 'Konflikte: gleicher Name an mehreren unverknüpften Hauptaccounts';
$l['postcolors_migration_choose'] = 'Hauptaccount, der die Farben bekommt';
$l['postcolors_migration_skip'] = '– überspringen –';
$l['postcolors_migration_skipped'] = 'Übersprungene Einträge';
$l['postcolors_migration_reason'] = 'Grund';
$l['postcolors_migration_reason_empty_name'] = 'leerer Name';
$l['postcolors_migration_reason_invalid_color'] = 'ungültige Farbe';
$l['postcolors_migration_reason_theme_missing'] = 'Theme existiert nicht mehr';
$l['postcolors_migration_reason_duplicate'] = 'doppelter Eintrag (neuester Eintrag verwendet)';
$l['postcolors_migration_reason_player_missing'] = 'kein Account mit diesem Namen';
$l['postcolors_migration_reason_error'] = 'Fehler beim Lesen';
$l['postcolors_migration_teams'] = 'Teamfarben';
$l['postcolors_migration_team_imported'] = 'importiert';
$l['postcolors_migration_team_imported_without_tag'] = 'ohne Tag importiert (Tag bereits vergeben)';
$l['postcolors_migration_team_exists'] = 'übersprungen – Slug existiert bereits';
$l['postcolors_migration_team_error'] = 'Fehler';
$l['postcolors_migration_no_teams'] = 'Für den Import sind keine Teamfarben hinterlegt.';
$l['postcolors_migration_cleanup'] = 'Altreste von 1.x';
$l['postcolors_migration_cleanup_item'] = 'Eintrag';
$l['postcolors_migration_count'] = 'Anzahl';
$l['postcolors_migration_cleanup_templates'] = 'Templates von 1.x (alle Template-Sets)';
$l['postcolors_migration_cleanup_headerinclude'] = 'Variable {$postcolors} in headerinclude';
$l['postcolors_migration_cleanup_usercp_nav_rpg'] = 'Alter UserCP-Link in usercp_nav_rpg';
$l['postcolors_migration_cleanup_setting'] = 'Einstellung postcolors_activated';
$l['postcolors_migration_tags'] = 'In Beiträgen verwendete Tags ohne Zuordnung';
$l['postcolors_migration_tag'] = 'Tag';
$l['postcolors_migration_tags_ok'] = 'Alle in Beiträgen und PNs verwendeten Tags sind zugeordnet.';
$l['postcolors_migration_drop'] = '1.x-Tabelle löschen';
$l['postcolors_migration_drop_hint'] = 'Die Migration wurde ausgeführt. Wenn alles passt, kannst du die 1.x-Tabelle <code>colors</code> löschen.';
$l['postcolors_migration_confirm_drop'] = 'Willst du die 1.x-Tabelle wirklich löschen? Das kann nicht rückgängig gemacht werden.';
$l['postcolors_migration_dropped'] = 'Die 1.x-Tabelle wurde gelöscht.';
$l['postcolors_migration_drop_denied'] = 'Die 1.x-Tabelle kann erst nach einer erfolgreichen Migration gelöscht werden.';
