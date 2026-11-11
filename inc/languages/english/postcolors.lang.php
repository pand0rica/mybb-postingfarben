<?php
/**
 * Postcolors 2.0 – English language file (frontend)
 */

$l['postcolors_plugin_description'] = 'Posting colours per player and theme, team colours, name tags, [pc] MyCode and automatic colouring of direct speech.';
$l['postcolors_nav'] = 'Posting colours';
$l['postcolors_disabled'] = 'Posting colours are currently switched off.';

// User CP
$l['postcolors_usercp_title'] = 'Posting colours';
$l['postcolors_usercp_intro'] = 'Your colours apply to all your linked accounts. Set a master colour and, if needed, a different colour per theme. Themes without their own colour use the master colour.';
$l['postcolors_as_note'] = 'You are logged in with a linked account. Changes are saved for your main account and apply to all your accounts.';
$l['postcolors_theme'] = 'Theme';
$l['postcolors_preview'] = 'Preview';
$l['postcolors_color'] = 'Colour';
$l['postcolors_master'] = 'Master colour';
$l['postcolors_master_desc'] = 'Used for every theme without its own colour.';
$l['postcolors_uses_master'] = 'Uses the master colour.';
$l['postcolors_sample'] = '“This is how your text looks.”';
$l['postcolors_low_contrast'] = 'Low contrast – this colour may be hard to read on this background.';
$l['postcolors_none'] = 'none';
$l['postcolors_reset'] = 'Use master colour';
$l['postcolors_clear'] = 'Remove';
$l['postcolors_save'] = 'Save';
$l['postcolors_saved'] = 'Your posting colours have been saved.';
$l['postcolors_error_color'] = 'The colour for “{1}” is not valid. Please use the format #RRGGBB.';
$l['postcolors_error_tag_format'] = 'Your own tag must start with a letter and may only contain letters, digits, umlauts, ß, _ and - (max. 50 characters).';
$l['postcolors_error_tag_reserved'] = 'This tag is an HTML element name and cannot be used.';
$l['postcolors_error_tag_taken'] = 'This tag is already used by someone else or by a team colour.';
$l['postcolors_error_quotes'] = 'Please enter an opening and a closing quotation mark (1–3 characters each, no spaces).';

// Name tag
$l['postcolors_tag_title'] = 'Name tag';
$l['postcolors_tag_intro'] = 'With your name tag everyone can colour text in your colour, e.g. <code>&lt;name&gt;“Text”&lt;/name&gt;</code>. If you change your name or your own tag, the new tag applies immediately – the old one no longer colours.';
$l['postcolors_tag_profile'] = 'Name in your profile';
$l['postcolors_tag_current'] = 'Your name tag';
$l['postcolors_tag_none'] = 'none';
$l['postcolors_tag_needs_color'] = 'Your name tag becomes active as soon as you have set a colour.';
$l['postcolors_custom_tag'] = 'Own tag (optional)';
$l['postcolors_custom_tag_desc'] = 'Replaces the name from your profile – useful if your name contains spaces or special characters. Letters, digits, umlauts, ß, _ and -.';
$l['postcolors_conflict_invalid'] = 'The name “{1}” cannot be used as a tag (spaces or special characters). Please choose your own tag.';
$l['postcolors_conflict_reserved'] = 'The name “{1}” is an HTML element name. Please choose your own tag.';
$l['postcolors_conflict_taken'] = 'The tag “{1}” is already taken. Please choose your own tag.';

// Direct speech
$l['postcolors_speech_title'] = 'Colour direct speech automatically';
$l['postcolors_speech_enable'] = 'Colour direct speech in my posts and private messages automatically';
$l['postcolors_speech_desc'] = 'Everything between the chosen quotation marks (including the marks) is shown in your colour – without any code in the text. Quotes, code, links and text that is already coloured stay untouched.';
$l['postcolors_speech_pair'] = 'Quotation marks';
$l['postcolors_speech_custom'] = 'Own pair';
$l['postcolors_speech_custom_desc'] = 'Opening and closing mark, max. 3 characters each.';
$l['postcolors_speech_open'] = 'Opening mark';
$l['postcolors_speech_close'] = 'Closing mark';
$l['postcolors_preset_de'] = 'German';
$l['postcolors_preset_guillemets'] = 'Guillemets (German style)';
$l['postcolors_preset_guillemets_fr'] = 'Guillemets (French style)';
$l['postcolors_preset_straight'] = 'straight double';
$l['postcolors_preset_en'] = 'English';
$l['postcolors_preset_single'] = 'straight single';
$l['postcolors_preset_de_single'] = 'German single';
$l['postcolors_preset_custom'] = 'Own pair';

// Overview
$l['postcolors_list_title'] = 'Posting colours';
$l['postcolors_list_note'] = 'The colours shown depend on the theme. Currently shown: <strong>{1}</strong>.';
$l['postcolors_list_theme'] = 'Theme:';
$l['postcolors_list_show'] = 'Show';
$l['postcolors_list_teams'] = 'Team colours';
$l['postcolors_list_players'] = 'Players';
$l['postcolors_list_usage'] = 'Usage';
$l['postcolors_list_no_teams'] = 'There are no team colours yet.';
$l['postcolors_list_no_players'] = 'Nobody has set a colour yet.';

// Editor
$l['postcolors_editor_button'] = 'Posting colour';
$l['postcolors_editor_mine'] = 'My colour';
$l['postcolors_editor_none'] = 'You have not set a colour yet.';
$l['postcolors_editor_teams'] = 'Team colours';
