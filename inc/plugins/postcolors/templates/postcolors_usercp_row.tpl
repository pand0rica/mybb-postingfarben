<tr class="postcolors_row" data-role="{$row_role}">
<td class="{$trow}"><strong>{$row_name}</strong>{$row_hint}</td>
<td class="{$trow}"><div class="postcolors_preview" style="background-color: {$row_bg};"><span class="postcolors_sample" style="color: {$row_effective};">{$lang->postcolors_sample}</span></div><div class="postcolors_contrast smalltext" hidden="hidden">{$lang->postcolors_low_contrast}</div></td>
<td class="{$trow} postcolors_inputs"><input type="color" class="postcolors_picker" value="{$row_picker}" title="{$row_name}" aria-label="{$row_name}" /> <input type="text" class="textbox postcolors_hex" name="color[{$row_tid}]" value="{$row_value}" size="9" maxlength="7" placeholder="{$row_placeholder}" autocomplete="off" /></td>
<td class="{$trow}"><button type="button" class="button postcolors_reset">{$row_reset_label}</button></td>
</tr>
