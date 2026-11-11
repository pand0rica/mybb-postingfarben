/**
 * Postcolors 2.0 – Color-Picker, Live-Vorschau und Kontrastwarnung (UserCP und ACP).
 * Nativer <input type="color"> + synchronisiertes Hex-Feld. Ein leeres Hex-Feld bedeutet
 * „keine eigene Farbe“ (Theme-Zeilen nutzen dann die Master-Farbe).
 */
(function () {
	'use strict';

	var HEX = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i;

	function normalize(value) {
		var m = HEX.exec(String(value || '').trim());
		if (!m) {
			return null;
		}
		var hex = m[1];
		if (hex.length === 3) {
			hex = hex.charAt(0) + hex.charAt(0) + hex.charAt(1) + hex.charAt(1) + hex.charAt(2) + hex.charAt(2);
		}
		return '#' + hex.toUpperCase();
	}

	function parseRgb(css) {
		var m = /rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)/i.exec(css || '');
		return m ? [parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10)] : null;
	}

	function hexToRgb(hex) {
		return [parseInt(hex.substr(1, 2), 16), parseInt(hex.substr(3, 2), 16), parseInt(hex.substr(5, 2), 16)];
	}

	function luminance(rgb) {
		var c = rgb.map(function (v) {
			v /= 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
		});
		return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
	}

	function contrast(a, b) {
		var la = luminance(a), lb = luminance(b);
		return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
	}

	function init(form) {
		var rows = Array.prototype.slice.call(form.querySelectorAll('.postcolors_row'));
		if (!rows.length) {
			return;
		}
		var masterRow = form.querySelector('.postcolors_row[data-role="master"]');
		var masterHex = masterRow ? masterRow.querySelector('.postcolors_hex') : null;

		function masterColor() {
			return masterHex ? normalize(masterHex.value) : null;
		}

		function update(row) {
			var hex = row.querySelector('.postcolors_hex');
			var picker = row.querySelector('.postcolors_picker');
			var sample = row.querySelector('.postcolors_sample');
			var preview = row.querySelector('.postcolors_preview');
			var warning = row.querySelector('.postcolors_contrast');
			var hint = row.querySelector('.postcolors_uses_master');
			var isMaster = row.getAttribute('data-role') === 'master';
			var own = normalize(hex.value);
			var effective = own || (!isMaster ? masterColor() : null);

			if (own && picker) {
				picker.value = own;
			} else if (effective && picker) {
				picker.value = effective;
			}
			if (!isMaster) {
				var master = masterColor();
				hex.placeholder = master || hex.getAttribute('data-empty') || hex.placeholder;
			}
			if (hint) {
				hint.hidden = !!own || hex.value.trim() !== '';
			}
			if (sample) {
				sample.style.color = effective || 'inherit';
			}
			hex.classList.toggle('postcolors_invalid', hex.value.trim() !== '' && !own);

			if (warning && preview) {
				var bg = parseRgb(window.getComputedStyle(preview).backgroundColor);
				var low = !!(effective && bg && contrast(hexToRgb(effective), bg) < 3);
				warning.hidden = !low;
			}
		}

		function updateAll() {
			rows.forEach(update);
		}

		rows.forEach(function (row) {
			var hex = row.querySelector('.postcolors_hex');
			var picker = row.querySelector('.postcolors_picker');
			var reset = row.querySelector('.postcolors_reset');
			if (!hex.getAttribute('data-empty')) {
				hex.setAttribute('data-empty', hex.placeholder);
			}
			if (picker) {
				picker.addEventListener('input', function () {
					hex.value = picker.value.toUpperCase();
					row === masterRow ? updateAll() : update(row);
				});
			}
			hex.addEventListener('input', function () {
				row === masterRow ? updateAll() : update(row);
			});
			hex.addEventListener('blur', function () {
				var n = normalize(hex.value);
				if (n) {
					hex.value = n;
				}
			});
			if (reset) {
				reset.addEventListener('click', function (e) {
					e.preventDefault();
					hex.value = '';
					row === masterRow ? updateAll() : update(row);
				});
			}
		});
		updateAll();

		var preset = form.querySelector('.postcolors_quote_preset');
		var custom = form.querySelector('.postcolors_quote_custom');
		if (preset && custom) {
			var toggle = function () {
				custom.hidden = preset.value !== 'custom';
			};
			preset.addEventListener('change', toggle);
			toggle();
		}
	}

	function boot() {
		var forms = document.querySelectorAll('.postcolors_form');
		Array.prototype.forEach.call(forms, init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
