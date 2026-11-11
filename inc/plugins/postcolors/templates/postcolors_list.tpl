<html>
<head>
<title>{$mybb->settings['bbname']} - {$lang->postcolors_list_title}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder postcolors_list">
<tr>
<td class="thead" colspan="3"><strong>{$lang->postcolors_list_title}</strong></td>
</tr>
<tr>
<td class="trow2" colspan="3"><span class="smalltext">{$list_note}</span>{$themeselect}</td>
</tr>
<tr>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_list_teams}</strong></span></td>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_color}</strong></span></td>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_list_usage}</strong></span></td>
</tr>
{$teamrows}
<tr>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_list_players}</strong></span></td>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_color}</strong></span></td>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_list_usage}</strong></span></td>
</tr>
{$playerrows}
</table>
{$footer}
</body>
</html>
