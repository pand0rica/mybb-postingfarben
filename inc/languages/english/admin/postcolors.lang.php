<?php
/**
 * Postcolors 2.0 – English language file (Admin CP)
 */

$l['postcolors'] = 'Postcolors';
$l['postcolors_plugin_description'] = 'Posting colours per player and theme, team colours, name tags, [pc] MyCode and automatic colouring of direct speech.';
$l['postcolors_admin_permission'] = 'Can manage Postcolors?';
$l['postcolors_error_inactive'] = 'Postcolors is not active.';

// Settings
$l['setting_group_postcolors'] = 'Postcolors';
$l['setting_group_postcolors_desc'] = 'Settings for posting colours, team colours, name tags and direct speech.';
$l['setting_postcolors_enabled'] = 'Enable Postcolors';
$l['setting_postcolors_enabled_desc'] = 'Turns colour output, name tags, [pc], the User CP page and the overview on or off.';
$l['setting_postcolors_usergroups'] = 'Who may set colours';
$l['setting_postcolors_usergroups_desc'] = 'User groups (primary or additional group of the logged-in account) that may use the User CP page. Colours that have been saved are always shown.';
$l['setting_postcolors_username_fid'] = 'Player name profile field';
$l['setting_postcolors_username_fid_desc'] = 'ID of the custom profile field that holds the name of the person who plays (e.g. 4 for fid4). The value of the main account is used as name tag and display name, and for the migration from 1.x.';
$l['setting_postcolors_bg_variable'] = 'Background CSS variable';
$l['setting_postcolors_bg_variable_desc'] = 'Name of a CSS variable in the theme stylesheets that holds the post background (e.g. --main-bgcolor). Used for the colour preview. Leave empty to always use the fallback.';
$l['setting_postcolors_bg_fallback'] = 'Fallback background colour';
$l['setting_postcolors_bg_fallback_desc'] = 'Background colour for the preview when the variable is not found (#RRGGBB).';
$l['setting_postcolors_bold'] = 'Bold coloured text';
$l['setting_postcolors_bold_desc'] = 'Show coloured text in bold.';
$l['setting_postcolors_element_selectors'] = 'Element selectors';
$l['setting_postcolors_element_selectors_desc'] = 'Additionally output name tags as CSS element selectors (sarah{…}), so raw HTML that bypasses the parser is coloured as well.';
$l['setting_postcolors_editor_format'] = 'Editor button inserts';
$l['setting_postcolors_editor_format_desc'] = 'What the editor button wraps around the selected text.';
$l['setting_postcolors_editor_format_tag'] = 'Name tag (<name>…</name>)';
$l['setting_postcolors_editor_format_pc'] = 'MyCode ([pc]…[/pc])';
$l['setting_postcolors_speech_allowed'] = 'Allow automatic colouring of direct speech';
$l['setting_postcolors_speech_allowed_desc'] = 'Players may switch on automatic colouring of direct speech in their User CP.';
$l['setting_postcolors_speech_forums'] = 'Forums with automatic colouring';
$l['setting_postcolors_speech_forums_desc'] = 'Forums in which direct speech is coloured automatically.';
$l['setting_postcolors_speech_pm'] = 'Automatic colouring in private messages';
$l['setting_postcolors_speech_pm_desc'] = 'Also colour direct speech in private messages.';
$l['setting_postcolors_list_layout_template'] = 'Overview: frame template (optional)';
$l['setting_postcolors_list_layout_template_desc'] = 'Name of an existing template the overview postcolors.php is embedded into, e.g. the frame of your other lists. It receives the content as {$listen_content} / {$postcolors_list_content}, the navigation as {$listen_nav} and the title as {$headline}. Empty = own page of the plugin.';
$l['setting_postcolors_list_nav_template'] = 'Overview: navigation template (optional)';
$l['setting_postcolors_list_nav_template_desc'] = 'Name of an existing template with the navigation of your lists. Only used together with the frame template.';

// Admin log
$l['admin_log_config_postcolors_add_team'] = 'Added team colour #{1} ({2})';
$l['admin_log_config_postcolors_edit_team'] = 'Edited team colour #{1} ({2})';
$l['admin_log_config_postcolors_delete_team'] = 'Deleted team colour #{1} ({2})';
$l['admin_log_config_postcolors_order'] = 'Saved the order of team colours';
$l['admin_log_config_postcolors_delete_player'] = 'Deleted the colours of player #{1}';
$l['admin_log_config_postcolors_tools'] = 'Rebuilt the Postcolors cache';
$l['admin_log_config_postcolors_migration'] = 'Postcolors migration from 1.x: {1} {2}';

// Tabs
$l['postcolors_tab_teams'] = 'Team colours';
$l['postcolors_tab_teams_desc'] = 'Global team colours (e.g. houses, groups). Each team can be used with its name tag or with [pc=slug].';
$l['postcolors_tab_add_team'] = 'Add team colour';
$l['postcolors_tab_add_team_desc'] = 'Create a new team colour with master colour and optional colours per theme.';
$l['postcolors_tab_edit_team'] = 'Edit team colour';
$l['postcolors_tab_edit_team_desc'] = 'Change title, slug, name tag and colours of the team colour.';
$l['postcolors_tab_players'] = 'Players';
$l['postcolors_tab_players_desc'] = 'Everyone who has set a colour. Colours belong to the main account (Account Switcher).';
$l['postcolors_tab_tools'] = 'Tools';
$l['postcolors_tab_tools_desc'] = 'Status and cache.';
$l['postcolors_tab_migration'] = 'Migration';
$l['postcolors_tab_migration_desc'] = 'Takes over the colours of Postcolors 1.x (table colors). Run a dry run first.';

// Common
$l['postcolors_theme'] = 'Theme';
$l['postcolors_preview'] = 'Preview';
$l['postcolors_color'] = 'Colour';
$l['postcolors_master'] = 'Master colour';
$l['postcolors_master_desc_team'] = 'Used for every theme without its own colour.';
$l['postcolors_uses_master'] = 'Uses the master colour.';
$l['postcolors_sample'] = '“This is how the text looks.”';
$l['postcolors_low_contrast'] = 'Low contrast – this colour may be hard to read on this background.';
$l['postcolors_none'] = 'none';
$l['postcolors_reset'] = 'Use master colour';
$l['postcolors_clear'] = 'Remove';
$l['postcolors_save'] = 'Save';
$l['postcolors_save_order'] = 'Save order';
$l['postcolors_controls'] = 'Controls';
$l['postcolors_options'] = 'Options';
$l['postcolors_edit'] = 'Edit';
$l['postcolors_delete'] = 'Delete';
$l['postcolors_yes'] = 'Yes';
$l['postcolors_no'] = 'No';
$l['postcolors_player'] = 'Player';
$l['postcolors_tag_current'] = 'Name tag';
$l['postcolors_theme_colors'] = 'Theme colours';
$l['postcolors_speech_short'] = 'Direct speech';
$l['postcolors_list_no_teams'] = 'There are no team colours yet.';
$l['postcolors_list_no_players'] = 'Nobody has set a colour yet.';
$l['postcolors_conflict_invalid'] = 'Name “{1}” is not a valid tag.';
$l['postcolors_conflict_reserved'] = 'Name “{1}” is an HTML element name.';
$l['postcolors_conflict_taken'] = 'Tag “{1}” is already taken.';
$l['postcolors_no_account_switcher'] = 'Enhanced Account Switcher is not installed: every account counts as its own player.';

// Teams
$l['postcolors_team_title'] = 'Title';
$l['postcolors_team_title_desc'] = 'e.g. Gryffindor';
$l['postcolors_team_slug'] = 'Slug';
$l['postcolors_team_slug_desc'] = 'Used in the MyCode [pc=slug]. Lower-case letters, digits, _ and -, max. 30 characters, unique.';
$l['postcolors_team_tag'] = 'Name tag';
$l['postcolors_team_tag_desc'] = 'Optional. Lets everyone colour text with &lt;tag&gt;…&lt;/tag&gt;. Must be unique across teams and players.';
$l['postcolors_team_disporder'] = 'Order';
$l['postcolors_team_active'] = 'Active';
$l['postcolors_team_colors'] = 'Colours';
$l['postcolors_team_colors_desc'] = 'Master colour plus optional colour per theme. Empty theme fields use the master colour.';
$l['postcolors_team_saved'] = 'The team colour has been saved.';
$l['postcolors_team_deleted'] = 'The team colour has been deleted.';
$l['postcolors_order_saved'] = 'The order has been saved.';
$l['postcolors_confirm_delete_team'] = 'Do you really want to delete the team colour “{1}”?';
$l['postcolors_error_team_missing'] = 'This team colour does not exist.';
$l['postcolors_error_title'] = 'Please enter a title (max. 100 characters).';
$l['postcolors_error_slug'] = 'The slug may only contain lower-case letters, digits, _ and - (max. 30 characters) and must not look like p123.';
$l['postcolors_error_slug_taken'] = 'This slug is already used by another team colour.';
$l['postcolors_error_tag_format'] = 'The tag must start with a letter and may only contain letters, digits, umlauts, ß, _ and - (max. 50 characters).';
$l['postcolors_error_tag_reserved'] = 'This tag is an HTML element name and cannot be used.';
$l['postcolors_error_tag_taken'] = 'This tag is already used by a player or another team colour.';
$l['postcolors_error_color_acp'] = 'At least one colour is not valid. Please use the format #RRGGBB.';

// Players
$l['postcolors_player_deleted'] = 'The colours have been deleted.';
$l['postcolors_confirm_delete_player'] = 'Do you really want to delete all colours and settings of this player?';

// Tools
$l['postcolors_status_as'] = 'Enhanced Account Switcher';
$l['postcolors_status_fid'] = 'Player name profile field';
$l['postcolors_status_fid_missing'] = 'not set or not found – name tags from the profile are not available';
$l['postcolors_status_tags'] = 'Active name tags';
$l['postcolors_status_conflicts'] = 'Tag conflicts';
$l['postcolors_status_built'] = 'Cache built';
$l['postcolors_rebuild_cache'] = 'Rebuild cache';
$l['postcolors_cache_rebuilt'] = 'The cache has been rebuilt.';

// Migration
$l['postcolors_migration_pending'] = 'The table of Postcolors 1.x still exists. You can take over the colours under “Migration”.';
$l['postcolors_migration_intro'] = 'The migration takes over the colours from the 1.x table <code>colors</code>. Players are matched via the profile field from the settings and assigned to their main account. Posts are not changed – name tags like &lt;sarah&gt; keep working. Start with a dry run; it does not write anything.';
$l['postcolors_migration_dry'] = 'Dry run – nothing has been written yet. Check the report and choose the main account for conflicts, then run the migration.';
$l['postcolors_migration_done'] = 'The migration has been carried out.';
$l['postcolors_migration_no_legacy'] = 'The 1.x table does not exist.';
$l['postcolors_migration_error_legacy_missing'] = 'The 1.x table does not exist.';
$l['postcolors_migration_error_tables_missing'] = 'The Postcolors 2.0 tables are missing. Please install the plugin first.';
$l['postcolors_migration_error_fid_missing'] = 'The player name profile field is not set or does not exist. Please check the Postcolors settings.';
$l['postcolors_migration_options'] = 'Options';
$l['postcolors_migration_derive'] = 'Derive master colour automatically';
$l['postcolors_migration_derive_desc'] = 'If all entries of a player have the same colour, it becomes the master colour. Otherwise the most frequent colour becomes the master colour and only differing themes keep their own colour.';
$l['postcolors_migration_overwrite'] = 'Overwrite existing 2.0 colours';
$l['postcolors_migration_overwrite_desc'] = 'Players who already have colours in 2.0 are skipped by default (so the migration can be run several times).';
$l['postcolors_migration_preview'] = 'Dry run';
$l['postcolors_migration_run'] = 'Run migration';
$l['postcolors_migration_players'] = 'Players';
$l['postcolors_migration_old_names'] = 'Name in 1.x';
$l['postcolors_migration_colors'] = 'Colours';
$l['postcolors_migration_status'] = 'Status';
$l['postcolors_migration_status_migrated'] = 'taken over';
$l['postcolors_migration_status_exists'] = 'skipped – already has colours in 2.0';
$l['postcolors_migration_status_error'] = 'error';
$l['postcolors_migration_note_merged'] = 'several 1.x names merged';
$l['postcolors_migration_note_master_all'] = 'same colour everywhere → master colour';
$l['postcolors_migration_note_master_frequent'] = 'most frequent colour → master colour';
$l['postcolors_migration_note_exists'] = 'existing colours kept';
$l['postcolors_migration_note_error'] = 'could not be saved';
$l['postcolors_migration_nothing'] = 'No colours to take over.';
$l['postcolors_migration_conflicts'] = 'Conflicts: same name at several unlinked main accounts';
$l['postcolors_migration_choose'] = 'Main account that gets the colours';
$l['postcolors_migration_skip'] = '– skip –';
$l['postcolors_migration_skipped'] = 'Skipped entries';
$l['postcolors_migration_reason'] = 'Reason';
$l['postcolors_migration_reason_empty_name'] = 'empty name';
$l['postcolors_migration_reason_invalid_color'] = 'invalid colour';
$l['postcolors_migration_reason_theme_missing'] = 'theme no longer exists';
$l['postcolors_migration_reason_duplicate'] = 'duplicate entry (newest entry used)';
$l['postcolors_migration_reason_player_missing'] = 'no account with this name';
$l['postcolors_migration_reason_error'] = 'error while reading';
$l['postcolors_migration_teams'] = 'Team colours';
$l['postcolors_migration_team_imported'] = 'imported';
$l['postcolors_migration_team_imported_without_tag'] = 'imported without tag (tag already taken)';
$l['postcolors_migration_team_exists'] = 'skipped – slug already exists';
$l['postcolors_migration_team_error'] = 'error';
$l['postcolors_migration_no_teams'] = 'No team colours are configured for import.';
$l['postcolors_migration_cleanup'] = 'Leftovers of 1.x';
$l['postcolors_migration_cleanup_item'] = 'Item';
$l['postcolors_migration_count'] = 'Count';
$l['postcolors_migration_cleanup_templates'] = 'Templates of 1.x (all template sets)';
$l['postcolors_migration_cleanup_headerinclude'] = 'Variable {$postcolors} in headerinclude';
$l['postcolors_migration_cleanup_usercp_nav_rpg'] = 'Old User CP link in usercp_nav_rpg';
$l['postcolors_migration_cleanup_setting'] = 'Setting postcolors_activated';
$l['postcolors_migration_tags'] = 'Tags used in posts without assignment';
$l['postcolors_migration_tag'] = 'Tag';
$l['postcolors_migration_tags_ok'] = 'All tags used in posts and private messages are assigned.';
$l['postcolors_migration_drop'] = 'Delete 1.x table';
$l['postcolors_migration_drop_hint'] = 'The migration has been carried out. If everything looks fine, you can delete the 1.x table <code>colors</code>.';
$l['postcolors_migration_confirm_drop'] = 'Do you really want to delete the 1.x table? This cannot be undone.';
$l['postcolors_migration_dropped'] = 'The 1.x table has been deleted.';
$l['postcolors_migration_drop_denied'] = 'The 1.x table can only be deleted after a successful migration.';
