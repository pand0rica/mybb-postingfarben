<?php
/**
 * Postcolors 2.0 – Deutsche Sprachdatei (Frontend, du)
 */

$l['postcolors_plugin_description'] = 'Postingfarben pro Spieler*in und Theme, Teamfarben, Namens-Tags, MyCode [pc] und automatische Färbung wörtlicher Rede.';
$l['postcolors_nav'] = 'Postingfarben';
$l['postcolors_disabled'] = 'Postingfarben sind derzeit ausgeschaltet.';

// UserCP
$l['postcolors_usercp_title'] = 'Postingfarben';
$l['postcolors_usercp_intro'] = 'Deine Farben gelten für alle deine verknüpften Accounts. Lege eine Master-Farbe fest und bei Bedarf eine eigene Farbe pro Theme. Themes ohne eigene Farbe verwenden die Master-Farbe.';
$l['postcolors_as_note'] = 'Du bist mit einem verknüpften Account angemeldet. Änderungen werden für deinen Hauptaccount gespeichert und gelten in allen deinen Accounts.';
$l['postcolors_theme'] = 'Theme';
$l['postcolors_preview'] = 'Vorschau';
$l['postcolors_color'] = 'Farbe';
$l['postcolors_master'] = 'Master-Farbe';
$l['postcolors_master_desc'] = 'Gilt für jedes Theme ohne eigene Farbe.';
$l['postcolors_uses_master'] = 'Verwendet die Master-Farbe.';
$l['postcolors_sample'] = '„So sieht dein Text aus.“';
$l['postcolors_low_contrast'] = 'Geringer Kontrast – diese Farbe ist auf diesem Hintergrund eventuell schlecht lesbar.';
$l['postcolors_none'] = 'keine';
$l['postcolors_reset'] = 'Master verwenden';
$l['postcolors_clear'] = 'Entfernen';
$l['postcolors_save'] = 'Speichern';
$l['postcolors_saved'] = 'Deine Postingfarben wurden gespeichert.';
$l['postcolors_error_color'] = 'Die Farbe für „{1}“ ist ungültig. Bitte verwende das Format #RRGGBB.';
$l['postcolors_error_tag_format'] = 'Dein eigener Tag muss mit einem Buchstaben beginnen und darf nur Buchstaben, Ziffern, Umlaute, ß, _ und - enthalten (max. 50 Zeichen).';
$l['postcolors_error_tag_reserved'] = 'Dieser Tag ist ein HTML-Elementname und kann nicht verwendet werden.';
$l['postcolors_error_tag_taken'] = 'Dieser Tag wird bereits von jemand anderem oder einer Teamfarbe verwendet.';
$l['postcolors_error_quotes'] = 'Bitte gib ein öffnendes und ein schließendes Anführungszeichen an (je 1–3 Zeichen, ohne Leerzeichen).';
$l['postcolors_error_quotes_none'] = 'Bitte wähle mindestens ein Paar Anführungszeichen aus oder schalte die automatische Färbung aus.';

// Namens-Tag
$l['postcolors_tag_title'] = 'Namens-Tag';
$l['postcolors_tag_intro'] = 'Mit deinem Namens-Tag kann jede*r Text in deiner Farbe schreiben, z. B. <code>&lt;name&gt;„Text“&lt;/name&gt;</code>. Änderst du deinen Namen oder deinen eigenen Tag, gilt sofort der neue Tag – der alte färbt nicht mehr.';
$l['postcolors_tag_profile'] = 'Name in deinem Profil';
$l['postcolors_tag_current'] = 'Dein Namens-Tag';
$l['postcolors_tag_none'] = 'keiner';
$l['postcolors_tag_needs_color'] = 'Dein Namens-Tag wird aktiv, sobald du eine Farbe festgelegt hast.';
$l['postcolors_custom_tag'] = 'Eigener Tag (optional)';
$l['postcolors_custom_tag_desc'] = 'Ersetzt den Namen aus deinem Profil – sinnvoll, wenn dein Name Leerzeichen oder Sonderzeichen enthält. Buchstaben, Ziffern, Umlaute, ß, _ und -.';
$l['postcolors_conflict_invalid'] = 'Der Name „{1}“ kann nicht als Tag verwendet werden (Leer- oder Sonderzeichen). Bitte wähle einen eigenen Tag.';
$l['postcolors_conflict_reserved'] = 'Der Name „{1}“ ist ein HTML-Elementname. Bitte wähle einen eigenen Tag.';
$l['postcolors_conflict_taken'] = 'Der Tag „{1}“ ist bereits vergeben. Bitte wähle einen eigenen Tag.';

// Wörtliche Rede
$l['postcolors_speech_title'] = 'Wörtliche Rede automatisch färben';
$l['postcolors_speech_enable'] = 'Wörtliche Rede in meinen Beiträgen und PNs automatisch färben';
$l['postcolors_speech_desc'] = 'Alles zwischen den gewählten Anführungszeichen (inklusive der Zeichen) erscheint in deiner Farbe – ohne Code im Text. Zitate, Code, Links und bereits gefärbter Text bleiben unverändert.';
$l['postcolors_speech_pair'] = 'Anführungszeichen';
$l['postcolors_speech_pair_desc'] = 'Mehrere möglich – alle gewählten Paare werden gefärbt.';
$l['postcolors_speech_custom'] = 'Eigenes Paar';
$l['postcolors_speech_custom_desc'] = 'Öffnendes und schließendes Zeichen, je max. 3 Zeichen.';
$l['postcolors_speech_open'] = 'Öffnendes Zeichen';
$l['postcolors_speech_close'] = 'Schließendes Zeichen';
$l['postcolors_preset_de'] = 'deutsch';
$l['postcolors_preset_guillemets'] = 'Guillemets (deutsch)';
$l['postcolors_preset_guillemets_fr'] = 'Guillemets (französisch)';
$l['postcolors_preset_straight'] = 'gerade, doppelt';
$l['postcolors_preset_en'] = 'englisch';
$l['postcolors_preset_single'] = 'gerade, einfach';
$l['postcolors_preset_de_single'] = 'deutsch, einfach';
$l['postcolors_preset_custom'] = 'Eigenes Paar';

// Übersicht
$l['postcolors_list_title'] = 'Postingfarben';
$l['postcolors_list_note'] = 'Die angezeigten Farben hängen vom Theme ab. Angezeigt wird gerade: <strong>{1}</strong>.';
$l['postcolors_list_theme'] = 'Theme:';
$l['postcolors_list_show'] = 'Anzeigen';
$l['postcolors_list_teams'] = 'Teamfarben';
$l['postcolors_list_players'] = 'Spieler*innen';
$l['postcolors_list_usage'] = 'Verwendung';
$l['postcolors_list_no_teams'] = 'Es gibt noch keine Teamfarben.';
$l['postcolors_list_no_players'] = 'Bisher hat noch niemand eine Farbe festgelegt.';

// Editor
$l['postcolors_editor_button'] = 'Postingfarbe';
$l['postcolors_editor_mine'] = 'Meine Farbe';
$l['postcolors_editor_none'] = 'Du hast noch keine Farbe festgelegt.';
$l['postcolors_editor_teams'] = 'Teamfarben';
