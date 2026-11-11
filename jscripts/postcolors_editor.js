/**
 * Postcolors 2.0 – SCEditor-Integration.
 *
 * - Button „postcolor“ mit Dropdown (Meine Farbe + Teamfarben); fügt je nach Setting einen
 *   Namens-Tag (<sarah>…</sarah>) oder [pc]…[/pc] ein.
 * - WYSIWYG-Darstellung: [pc] und bekannte Namens-Tags erscheinen als farbige Spans
 *   (Farb-CSS des Themes wird ins Editor-iframe injiziert). Beim Zurückwandeln entstehen wieder
 *   [pc] bzw. <sarah>. Nur korrekt gepaarte, bekannte Namens-Tags werden umgewandelt; alles
 *   andere bleibt unverändert Text – so geht beim Umschalten nichts verloren.
 *
 * Konfiguration: window.postcolors_config (vom Server per json_encode eingebettet).
 */
jQuery(function ($) {
	'use strict';

	var config = window.postcolors_config;
	var sceditor = $.sceditor || window.sceditor;
	if (!config || !sceditor || !sceditor.formats || !sceditor.formats.bbcode || typeof window.opt_editor === 'undefined') {
		return;
	}

	var tags = config.tags || {};
	var teams = config.teams || [];
	var own = config.own || {};
	var TAG_RE = /\[(code|php)\][\s\S]*?\[\/\1\]|<(\/?)([a-zA-Z][a-zA-Z0-9äöüÄÖÜß_\-]*)>/gi;

	function escapeAttr(value) {
		return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	function teamBySlug(slug) {
		for (var i = 0; i < teams.length; i++) {
			if (teams[i].slug === slug) {
				return teams[i];
			}
		}
		return null;
	}

	/** Schlüssel (p12 / t3) für einen [pc]-Parameter. */
	function keyForParam(param) {
		param = String(param || '').replace(/^["']|["']$/g, '').toLowerCase();
		if (param === '' || param === '-') {
			return own.pid ? 'p' + own.pid : '';
		}
		if (/^p\d+$/.test(param)) {
			return param;
		}
		var team = teamBySlug(param);
		return team ? 't' + team.id : '';
	}

	/** Gepaarte, bekannte Namens-Tags -> [pcname=tag]…[/pcname] (außerhalb von [code]/[php]). */
	function nameTagsToBBCode(source) {
		if (typeof source !== 'string' || source.indexOf('<') === -1) {
			return source;
		}
		var tokens = [];
		var stack = [];
		var match;
		TAG_RE.lastIndex = 0;
		while ((match = TAG_RE.exec(source)) !== null) {
			if (match[1]) {
				continue; // Code-Block
			}
			var tag = match[3].toLowerCase();
			if (!Object.prototype.hasOwnProperty.call(tags, tag)) {
				continue;
			}
			var token = {index: match.index, length: match[0].length, close: match[2] === '/', tag: tag, pair: null};
			if (!token.close) {
				stack.push(token);
			} else {
				for (var s = stack.length - 1; s >= 0; s--) {
					if (stack[s].tag === tag) {
						stack[s].pair = token;
						token.pair = stack[s];
						stack.splice(s, 1);
						break;
					}
				}
			}
			tokens.push(token);
		}
		var out = '';
		var last = 0;
		tokens.forEach(function (t) {
			if (!t.pair) {
				return;
			}
			out += source.substring(last, t.index) + (t.close ? '[/pcname]' : '[pcname=' + t.tag + ']');
			last = t.index + t.length;
		});
		return out + source.substring(last);
	}

	/* ------------------------------------------------------------ BBCode-Formate */

	sceditor.formats.bbcode.set('pc', {
		tags: {span: {'data-pc': null}},
		isInline: true,
		allowsEmpty: true,
		format: function (element, content) {
			var param = element.getAttribute('data-pc') || '-';
			return '[pc' + (param !== '-' ? '=' + param : '') + ']' + content + '[/pc]';
		},
		html: function (token, attrs, content) {
			var param = String(attrs.defaultattr || '').replace(/^["']|["']$/g, '').toLowerCase();
			var key = keyForParam(param);
			return '<span class="pc' + (key ? ' pc-' + key : '') + '" data-pc="' + escapeAttr(param || '-') + '">' + content + '</span>';
		}
	});

	sceditor.formats.bbcode.set('pcname', {
		tags: {span: {'data-pcname': null}},
		isInline: true,
		allowsEmpty: true,
		format: function (element, content) {
			var tag = String(element.getAttribute('data-pcname') || '').toLowerCase();
			if (!Object.prototype.hasOwnProperty.call(tags, tag)) {
				return content;
			}
			return '<' + tag + '>' + content + '</' + tag + '>';
		},
		html: function (token, attrs, content) {
			var tag = String(attrs.defaultattr || '').toLowerCase();
			if (!Object.prototype.hasOwnProperty.call(tags, tag)) {
				return content;
			}
			return '<span class="pc pc-' + escapeAttr(tags[tag]) + '" data-pcname="' + escapeAttr(tag) + '">' + content + '</span>';
		}
	});

	// Format-Konstruktor umhüllen: Namens-Tags vor dem Umwandeln in WYSIWYG-HTML vorbereiten.
	var Original = sceditor.formats.bbcode;
	if (!Original.__postcolors) {
		var Wrapped = function () {
			var instance = new Original();
			['toHtml', 'fragmentToHtml'].forEach(function (method) {
				if (typeof instance[method] === 'function') {
					var originalMethod = instance[method];
					instance[method] = function (source) {
						var args = Array.prototype.slice.call(arguments);
						args[0] = nameTagsToBBCode(source);
						return originalMethod.apply(this, args);
					};
				}
			});
			return instance;
		};
		Object.keys(Original).forEach(function (key) {
			Wrapped[key] = Original[key];
		});
		Wrapped.prototype = Original.prototype;
		Wrapped.__postcolors = true;
		sceditor.formats.bbcode = Wrapped;
	}

	/* ------------------------------------------------------------ Button + Dropdown */

	function wrapFor(kind, team) {
		var useTag = config.format === 'tag';
		if (kind === 'own') {
			return useTag && own.tag ? ['<' + own.tag + '>', '</' + own.tag + '>'] : ['[pc]', '[/pc]'];
		}
		return useTag && team.tag ? ['<' + team.tag + '>', '</' + team.tag + '>'] : ['[pc=' + team.slug + ']', '[/pc]'];
	}

	function item(label, key) {
		var $a = $('<a href="#" role="menuitem"></a>');
		$('<span class="postcolors_swatch postcolors_swatch_current pc"></span>').addClass('pc-' + key).appendTo($a);
		$('<span></span>').text(label).appendTo($a);
		return $a;
	}

	function openDropDown(editor, caller) {
		var $content = $('<div class="postcolors_dropdown" role="menu"></div>');

		if (own.has && own.pid) {
			item(config.lang.mine, 'p' + own.pid).attr('data-kind', 'own').appendTo($content);
		} else {
			$('<div class="postcolors_dropdown_head"></div>').text(config.lang.none).appendTo($content);
		}
		if (teams.length) {
			$('<div class="postcolors_dropdown_head"></div>').text(config.lang.teams).appendTo($content);
			teams.forEach(function (team, index) {
				item(team.title, 't' + team.id).attr('data-kind', 'team').attr('data-index', index).appendTo($content);
			});
		}

		$content.on('click', 'a', function (e) {
			e.preventDefault();
			var kind = $(this).attr('data-kind');
			var pair = wrapFor(kind, teams[parseInt($(this).attr('data-index'), 10)] || null);
			editor.insert(pair[0], pair[1]);
			editor.closeDropDown(true);
		});

		editor.createDropDown(caller, 'postcolor', $content.get(0));
	}

	sceditor.command.set('postcolor', {
		exec: function (caller) {
			openDropDown(this, caller);
		},
		txtExec: function (caller) {
			openDropDown(this, caller);
		},
		tooltip: config.lang.button
	});

	var toolbar = String(window.opt_editor.toolbar || '');
	if (toolbar.indexOf('postcolor') === -1) {
		window.opt_editor.toolbar = /(^|[,|])color([,|]|$)/.test(toolbar)
			? toolbar.replace(/(^|[,|])color([,|]|$)/, '$1color,postcolor$2')
			: (toolbar.indexOf('quote|') !== -1 ? toolbar.replace('quote|', 'quote,postcolor|') : toolbar + '|postcolor');
	}

	/* ------------------------------------------------------------ Farb-CSS im iframe */

	var injected = [];
	var tries = 0;
	function injectCss() {
		$('textarea').each(function () {
			var instance;
			try {
				instance = $(this).sceditor('instance');
			} catch (err) {
				instance = null;
			}
			if (!instance || injected.indexOf(instance) !== -1 || typeof instance.css !== 'function') {
				return;
			}
			instance.css(String(instance.css() || '') + '\n' + String(config.css || ''));
			injected.push(instance);
		});
		if (++tries < 20) {
			window.setTimeout(injectCss, 250);
		}
	}
	window.setTimeout(injectCss, 0);
});
