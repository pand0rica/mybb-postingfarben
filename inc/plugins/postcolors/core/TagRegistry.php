<?php
/**
 * Postcolors – Namens-Tags: Validierung, reservierte Namen, Konfliktauflösung.
 *
 * Priorität bei gleichen Tags: aktive/inaktive Teams > eigene Tags (custom_tag) > Tags aus dem
 * Profilfeld (niedrigste pid gewinnt). Wer leer ausgeht, landet in den Konflikten.
 */

if (!defined('IN_MYBB') && PHP_SAPI !== 'cli') {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsTagRegistry
{
    /** Erlaubte Zeichen eines Tags (Groß/Klein wird ignoriert). */
    public const PATTERN = '[a-zA-Z][a-zA-Z0-9äöüÄÖÜß_-]*';

    public const MAX_LENGTH = 50;

    /** HTML-/SVG-Elemente und MyBB-Interna, die nie ein Namens-Tag sein dürfen. */
    private const RESERVED = [
        'a', 'abbr', 'acronym', 'address', 'applet', 'area', 'article', 'aside', 'audio', 'b',
        'base', 'basefont', 'bdi', 'bdo', 'bgsound', 'big', 'blink', 'blockquote', 'body', 'br',
        'button', 'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'data',
        'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'dir', 'div', 'dl', 'dt', 'em',
        'embed', 'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'frame',
        'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html',
        'i', 'iframe', 'image', 'img', 'input', 'ins', 'isindex', 'kbd', 'keygen', 'label',
        'legend', 'li', 'link', 'listing', 'main', 'map', 'mark', 'marquee', 'math', 'menu',
        'menuitem', 'meta', 'meter', 'multicol', 'nav', 'nextid', 'nobr', 'noembed', 'noframes',
        'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'picture',
        'plaintext', 'pre', 'progress', 'q', 'rb', 'rp', 'rt', 'rtc', 'ruby', 's', 'samp',
        'script', 'search', 'section', 'select', 'slot', 'small', 'source', 'spacer', 'span',
        'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'svg', 'table', 'tbody', 'td',
        'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'tt',
        'u', 'ul', 'var', 'video', 'wbr', 'xmp', 'circle', 'ellipse', 'g', 'line', 'path',
        'polygon', 'polyline', 'rect', 'text', 'tspan', 'use', 'defs', 'symbol', 'mybb-code',
        'mybb_code', 'pc', 'quote', 'php', 'url', 'img', 'color', 'size',
    ];

    public static function normalize(string $tag): string
    {
        $tag = trim($tag);
        return function_exists('mb_strtolower') ? mb_strtolower($tag, 'UTF-8') : strtolower($tag);
    }

    /** Erfüllt der Text das Tag-Format (ohne Reservierungsprüfung)? */
    public static function isWellFormed(string $tag): bool
    {
        if ($tag === '' || (function_exists('mb_strlen') ? mb_strlen($tag, 'UTF-8') : strlen($tag)) > self::MAX_LENGTH) {
            return false;
        }
        return (bool)preg_match('/^' . self::PATTERN . '$/u', $tag);
    }

    public static function isReserved(string $tag): bool
    {
        return in_array(self::normalize($tag), self::RESERVED, true);
    }

    public static function isValid(string $tag): bool
    {
        return self::isWellFormed($tag) && !self::isReserved($tag);
    }

    /**
     * Löst alle Tags auf.
     *
     * @param array<int, array{teamid:int, tag:string, active:int}> $teams
     * @param array<int, array{pid:int, custom_tag:?string, profile_name:string}> $players
     * @return array{tags: array<string,string>, player_tags: array<int,string>, team_tags: array<int,string>, conflicts: array<int, array>}
     */
    public static function resolve(array $teams, array $players): array
    {
        $owner = [];       // tag => key
        $tags = [];        // nur aktive Einträge (färben)
        $playerTags = [];
        $teamTags = [];
        $conflicts = [];

        foreach ($teams as $team) {
            $tag = self::normalize((string)$team['tag']);
            if ($tag === '' || !self::isValid($tag) || isset($owner[$tag])) {
                continue;
            }
            $key = 't' . (int)$team['teamid'];
            $owner[$tag] = $key;
            $teamTags[(int)$team['teamid']] = $tag;
            if (!empty($team['active'])) {
                $tags[$tag] = $key;
            }
        }

        usort($players, static function (array $a, array $b): int {
            return (int)$a['pid'] <=> (int)$b['pid'];
        });

        // Durchgang 1: eigene Tags, Durchgang 2: Profilfeld-Namen
        foreach ([true, false] as $customPass) {
            foreach ($players as $player) {
                $pid = (int)$player['pid'];
                if (isset($playerTags[$pid])) {
                    continue;
                }
                $custom = self::normalize((string)($player['custom_tag'] ?? ''));
                if ($customPass !== ($custom !== '')) {
                    continue;
                }
                $source = $customPass ? 'custom' : 'profile';
                $tag = $customPass ? $custom : self::normalize((string)($player['profile_name'] ?? ''));
                if ($tag === '') {
                    continue;
                }
                if (!self::isWellFormed($tag)) {
                    $conflicts[] = ['pid' => $pid, 'tag' => $tag, 'source' => $source, 'reason' => 'invalid', 'by' => ''];
                    continue;
                }
                if (self::isReserved($tag)) {
                    $conflicts[] = ['pid' => $pid, 'tag' => $tag, 'source' => $source, 'reason' => 'reserved', 'by' => ''];
                    continue;
                }
                if (isset($owner[$tag])) {
                    $conflicts[] = ['pid' => $pid, 'tag' => $tag, 'source' => $source, 'reason' => 'taken', 'by' => $owner[$tag]];
                    continue;
                }
                $owner[$tag] = 'p' . $pid;
                $tags[$tag] = 'p' . $pid;
                $playerTags[$pid] = $tag;
            }
        }

        return ['tags' => $tags, 'player_tags' => $playerTags, 'team_tags' => $teamTags, 'conflicts' => $conflicts];
    }
}
