<html>
<head>
<title>{$mybb->settings['bbname']} - {$lang->postcolors_usercp_title}</title>
{$headerinclude}
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/postcolors.js?ver=2.0.0"></script>
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
{$errors}
<form action="usercp.php" method="post" class="postcolors_form">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="do_postcolors" />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder postcolors_table">
<tr>
<td class="thead" colspan="4"><strong>{$lang->postcolors_usercp_title}</strong></td>
</tr>
<tr>
<td class="trow2" colspan="4"><span class="smalltext">{$lang->postcolors_usercp_intro}</span>{$as_note}</td>
</tr>
<tr>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_theme}</strong></span></td>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_preview}</strong></span></td>
<td class="tcat"><span class="smalltext"><strong>{$lang->postcolors_color}</strong></span></td>
<td class="tcat"></td>
</tr>
{$rows}
</table>
<br />
{$tagsection}
{$speechsection}
<div align="center"><input type="submit" class="button" value="{$lang->postcolors_save}" /></div>
</form>
</td>
</tr>
</table>
{$footer}
</body>
</html>
