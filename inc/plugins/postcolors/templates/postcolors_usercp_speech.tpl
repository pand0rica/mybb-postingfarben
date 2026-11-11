<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder postcolors_table postcolors_speech">
<tr>
<td class="thead" colspan="2"><strong>{$lang->postcolors_speech_title}</strong></td>
</tr>
<tr>
<td class="trow1" colspan="2"><label><input type="checkbox" class="checkbox" name="speech_enabled" value="1"{$speech_checked} /> {$lang->postcolors_speech_enable}</label><br /><span class="smalltext">{$lang->postcolors_speech_desc}</span></td>
</tr>
<tr>
<td class="trow2" width="40%"><strong>{$lang->postcolors_speech_pair}</strong></td>
<td class="trow2"><select name="quote_preset" class="postcolors_quote_preset">{$preset_options}</select></td>
</tr>
<tr class="postcolors_quote_custom">
<td class="trow1"><strong>{$lang->postcolors_speech_custom}</strong><br /><span class="smalltext">{$lang->postcolors_speech_custom_desc}</span></td>
<td class="trow1"><input type="text" class="textbox" name="quote_open" value="{$quote_open}" size="4" maxlength="3" aria-label="{$lang->postcolors_speech_open}" /> … <input type="text" class="textbox" name="quote_close" value="{$quote_close}" size="4" maxlength="3" aria-label="{$lang->postcolors_speech_close}" /></td>
</tr>
</table>
<br />
