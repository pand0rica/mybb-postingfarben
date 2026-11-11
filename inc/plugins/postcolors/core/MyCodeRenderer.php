<?php
/**
 * Postcolors – Namens-Tags (<sarah>…</sarah>) und MyCode [pc]…[/pc].
 *
 * Ablauf beim Parsen:
 * 1. parse_message_start: bekannte Tags werden (außerhalb von [code]/[php]) korrekt
 *    verschachtelt in Platzhalter "\x1AO<key>\x1A" / "\x1AC\x1A" getauscht. So überstehen sie
 *    das HTML-Escaping des Parsers – unabhängig davon, ob HTML erlaubt ist.
 * 2. parse_message_end: Platzhalter werden zu <span class="pc pc-<key>">. Die Spans umschließen
 *    nur Textläufe (vor jedem HTML-Tag geschlossen, danach wieder geöffnet), damit nie ungültig
 *    verschachteltes HTML entsteht.
 *
 * Schlüssel: "p<pid>" = Spieler*in, "t<teamid>" = Team.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsMyCodeRenderer
{
    public const PH = "\x1A";

    /** @var callable|null liefert die pid für [pc] ohne Parameter beim Rendern (Vorschau) */
    public static $authorResolver = null;

    /** @var array<int,int> Cache: zitierte Beitrags-ID => pid */
    private static $quotedAuthors = [];

    private static function tokenPattern(): string
    {
        return '#<(/?)(' . PostcolorsTagRegistry::PATTERN . ')>'
            . '|\[(/?)pc(?:=(&quot;.*?&quot;|"(?:[^"\\\\\]]|\\\\.)*"|[^\]]*))?\]#iu';
    }

    /**
     * parse_message_start: Tags -> Platzhalter.
     */
    public static function start(string $message, array $data): string
    {
        if (strpos($message, self::PH) !== false) {
            $message = str_replace(self::PH, '', $message);
        }
        if (strpos($message, '<') === false && stripos($message, '[pc') === false && stripos($message, '[/pc') === false) {
            return $message;
        }

        $codeRanges = self::codeRanges($message);
        if (!preg_match_all(self::tokenPattern(), $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $message;
        }

        $tags = $data['tags'] ?? [];
        $stack = [];         // [type, name, key|null]
        $replacements = [];  // offset => [length, text]

        foreach ($matches as $m) {
            $offset = $m[0][1];
            $length = strlen($m[0][0]);
            if (self::inRanges($offset, $codeRanges)) {
                continue;
            }

            if (isset($m[2]) && $m[2][1] !== -1 && $m[2][0] !== '') {
                // Namens-Tag
                $tag = PostcolorsTagRegistry::normalize($m[2][0]);
                if (!isset($tags[$tag])) {
                    continue; // unbekannt: MyBB-Standardverhalten
                }
                if ($m[1][0] === '/') {
                    $close = self::closeEntry($stack, 'n', $tag);
                    if ($close !== null) {
                        $replacements[$offset] = [$length, $close];
                    }
                    continue;
                }
                $stack[] = ['n', $tag, $tags[$tag]];
                $replacements[$offset] = [$length, self::PH . 'O' . $tags[$tag] . self::PH];
                continue;
            }

            // [pc] / [/pc]
            if (isset($m[3]) && $m[3][0] === '/') {
                $close = self::closeEntry($stack, 'pc', '');
                $replacements[$offset] = [$length, $close ?? ''];
                continue;
            }
            $param = isset($m[4]) && $m[4][1] !== -1 ? self::cleanParam($m[4][0]) : '';
            $key = self::resolveKey($param, $data);
            $stack[] = ['pc', '', $key];
            $replacements[$offset] = [$length, $key !== null ? self::PH . 'O' . $key . self::PH : ''];
        }

        if (empty($replacements)) {
            return $message;
        }

        krsort($replacements);
        foreach ($replacements as $offset => $replacement) {
            $message = substr_replace($message, $replacement[1], $offset, $replacement[0]);
        }
        // Nicht geschlossene Tags am Ende schließen
        foreach ($stack as $entry) {
            if ($entry[2] !== null) {
                $message .= self::PH . 'C' . self::PH;
            }
        }
        return $message;
    }

    /**
     * Schließt den passenden offenen Eintrag (inkl. darin noch offener Einträge).
     * Liefert die Ersetzung oder null, wenn es keinen passenden offenen Eintrag gibt.
     */
    private static function closeEntry(array &$stack, string $type, string $name): ?string
    {
        for ($i = count($stack) - 1; $i >= 0; $i--) {
            if ($stack[$i][0] === $type && $stack[$i][1] === $name) {
                $out = '';
                while (count($stack) > $i) {
                    $entry = array_pop($stack);
                    if ($entry[2] !== null) {
                        $out .= self::PH . 'C' . self::PH;
                    }
                }
                return $out;
            }
        }
        return null;
    }

    private static function resolveKey(string $param, array $data): ?string
    {
        $known = $data['known'] ?? [];
        if ($param === '') {
            $pid = is_callable(self::$authorResolver) ? (int)call_user_func(self::$authorResolver) : 0;
            return ($pid > 0 && isset($known[$pid])) ? 'p' . $pid : null;
        }
        if (preg_match('/^p(\d+)$/', $param, $pm)) {
            return isset($known[(int)$pm[1]]) ? 'p' . (int)$pm[1] : null;
        }
        $slugs = $data['slugs'] ?? [];
        return isset($slugs[$param]) ? 't' . (int)$slugs[$param] : null;
    }

    /** Entfernt Anführungszeichen ("…", &quot;…&quot;) und Escapes aus einem MyCode-Attribut. */
    public static function cleanParam(string $param): string
    {
        $param = trim(htmlspecialchars_decode($param, ENT_QUOTES));
        if (strlen($param) >= 2 && ($param[0] === '"' || $param[0] === "'") && substr($param, -1) === $param[0]) {
            $param = substr($param, 1, -1);
        }
        $param = str_replace(['\\"', '\\\\'], ['"', '\\'], $param);
        return strtolower(trim($param));
    }

    /**
     * parse_message_end: Platzhalter -> Spans.
     */
    public static function end(string $html): string
    {
        if (strpos($html, self::PH) === false) {
            return $html;
        }
        $parts = preg_split('#(<[^>]*>)#', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return str_replace(self::PH, '', $html);
        }

        $stack = [];
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<') {
                if (strpos($part, self::PH) !== false) {
                    // Platzhalter in einem Attribut: Zustand übernehmen, Text entfernen
                    preg_match_all('#\x1A(O[pt]\d+|C)\x1A#', $part, $pm);
                    foreach ($pm[1] as $token) {
                        self::applyToken($stack, $token);
                    }
                    $part = preg_replace('#\x1A[^\x1A]*\x1A#', '', $part);
                    $part = str_replace(self::PH, '', (string)$part);
                }
                $out .= $part;
                continue;
            }
            $pieces = preg_split('#\x1A(O[pt]\d+|C)\x1A#', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($pieces as $index => $piece) {
                if ($index % 2 === 1) {
                    self::applyToken($stack, $piece);
                    continue;
                }
                $piece = str_replace(self::PH, '', $piece);
                if ($piece === '') {
                    continue;
                }
                if (empty($stack)) {
                    $out .= $piece;
                } else {
                    $out .= '<span class="pc pc-' . end($stack) . '">' . $piece . '</span>';
                }
            }
        }
        return $out;
    }

    private static function applyToken(array &$stack, string $token): void
    {
        if ($token === 'C') {
            array_pop($stack);
        } else {
            $stack[] = substr($token, 1);
        }
    }

    /**
     * text_parse_message: bekannte Tags in Text-Ausgaben (E-Mail, Suche-Vorschau) entfernen.
     */
    public static function strip(string $message, array $data): string
    {
        if (strpos($message, '<') === false && stripos($message, '[pc') === false && stripos($message, '[/pc') === false) {
            return $message;
        }
        $tags = $data['tags'] ?? [];
        return (string)preg_replace_callback(self::tokenPattern(), static function (array $m) use ($tags): string {
            if (isset($m[2]) && $m[2] !== '') {
                return isset($tags[PostcolorsTagRegistry::normalize($m[2])]) ? '' : $m[0];
            }
            return '';
        }, $message);
    }

    /**
     * Vor dem Speichern: [pc] -> [pc=p<pid>] außerhalb von Zitaten, fremde [pc=pX] -> eigene pid.
     * In Zitaten bleibt alles, wie es ist; nur [pc] ohne Parameter bekommt dort die pid der Person,
     * die den zitierten Beitrag (pid=… im Zitat) geschrieben hat.
     */
    public static function rewriteForSave(string $message, int $authorPid): string
    {
        if (stripos($message, '[pc') === false) {
            return $message;
        }
        $codeRanges = self::codeRanges($message);
        $quotes = self::quoteRanges($message, $codeRanges);
        $pattern = '#\[pc(?:=(&quot;.*?&quot;|"(?:[^"\\\\\]]|\\\\.)*"|[^\]]*))?\]#i';
        return self::rewriteTokens($message, $pattern, $codeRanges, $quotes, $authorPid);
    }

    private static function rewriteTokens(string $message, string $pattern, array $codeRanges, array $quotes, int $authorPid): string
    {
        if (!preg_match_all($pattern, $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $message;
        }
        $replacements = [];
        foreach ($matches as $m) {
            $offset = $m[0][1];
            if (self::inRanges($offset, $codeRanges)) {
                continue;
            }
            $param = isset($m[1]) && $m[1][1] !== -1 ? self::cleanParam($m[1][0]) : '';
            $quote = self::quoteAt($offset, $quotes);
            if ($quote !== null) {
                if ($param === '' && $quote['pid'] > 0) {
                    $pid = self::quotedAuthorPid($quote['pid']);
                    if ($pid > 0) {
                        $replacements[$offset] = [strlen($m[0][0]), '[pc=p' . $pid . ']'];
                    }
                }
                continue;
            }
            if ($param === '' || preg_match('/^p\d+$/', $param)) {
                $new = $authorPid > 0 ? '[pc=p' . $authorPid . ']' : '[pc]';
            } else {
                $new = '[pc=' . $param . ']';
            }
            if ($new !== $m[0][0]) {
                $replacements[$offset] = [strlen($m[0][0]), $new];
            }
        }
        krsort($replacements);
        foreach ($replacements as $offset => $replacement) {
            $message = substr_replace($message, $replacement[1], $offset, $replacement[0]);
        }
        return $message;
    }

    /**
     * Zum Bearbeiten: [pc=p<eigene pid>] außerhalb von Zitaten wieder als [pc] anzeigen.
     * Funktioniert auf Roh- und HTML-escaptem Text.
     */
    public static function rewriteForEdit(string $message, int $authorPid): string
    {
        if ($authorPid <= 0 || stripos($message, '[pc=') === false) {
            return $message;
        }
        $codeRanges = self::codeRanges($message);
        $quotes = self::quoteRanges($message, $codeRanges);
        $pattern = '#\[pc=(?:&quot;|")?p' . $authorPid . '(?:&quot;|")?\]#i';
        if (!preg_match_all($pattern, $message, $matches, PREG_OFFSET_CAPTURE)) {
            return $message;
        }
        $replacements = [];
        foreach ($matches[0] as $match) {
            if (self::inRanges($match[1], $codeRanges) || self::quoteAt($match[1], $quotes) !== null) {
                continue;
            }
            $replacements[$match[1]] = strlen($match[0]);
        }
        krsort($replacements);
        foreach ($replacements as $offset => $length) {
            $message = substr_replace($message, '[pc]', $offset, $length);
        }
        return $message;
    }

    /* ---------------------------------------------------------------- Hilfen */

    /** @return array<int, array{0:int,1:int}> */
    private static function codeRanges(string $message): array
    {
        $ranges = [];
        if (stripos($message, '[code]') === false && stripos($message, '[php]') === false) {
            return $ranges;
        }
        if (preg_match_all('#\[(code|php)\](.*?)\[/\1\]#si', $message, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $match) {
                $ranges[] = [$match[1], $match[1] + strlen($match[0])];
            }
        }
        return $ranges;
    }

    private static function inRanges(int $offset, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($offset >= $range[0] && $offset < $range[1]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Äußere Zitat-Bereiche mit der zitierten Beitrags-ID.
     *
     * @return array<int, array{start:int, end:int, pid:int}>
     */
    private static function quoteRanges(string $message, array $codeRanges): array
    {
        $ranges = [];
        if (stripos($message, '[quote') === false) {
            return $ranges;
        }
        if (!preg_match_all('#\[(/?)quote((?:=|\s)[^\]]*)?\]#i', $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $ranges;
        }
        $depth = 0;
        $current = null;
        foreach ($matches as $m) {
            $offset = $m[0][1];
            if (self::inRanges($offset, $codeRanges)) {
                continue;
            }
            if ($m[1][0] === '') {
                if ($depth === 0) {
                    $pid = 0;
                    if (isset($m[2]) && $m[2][1] !== -1 && preg_match('#pid=(?:&quot;|["\'])?(\d+)#i', $m[2][0], $pm)) {
                        $pid = (int)$pm[1];
                    }
                    $current = ['start' => $offset, 'end' => strlen($message), 'pid' => $pid];
                }
                $depth++;
            } elseif ($depth > 0) {
                $depth--;
                if ($depth === 0 && $current !== null) {
                    $current['end'] = $offset + strlen($m[0][0]);
                    $ranges[] = $current;
                    $current = null;
                }
            }
        }
        if ($current !== null) {
            $ranges[] = $current; // nicht geschlossenes Zitat reicht bis zum Ende
        }
        return $ranges;
    }

    private static function quoteAt(int $offset, array $quotes): ?array
    {
        foreach ($quotes as $quote) {
            if ($offset >= $quote['start'] && $offset < $quote['end']) {
                return $quote;
            }
        }
        return null;
    }

    private static function quotedAuthorPid(int $postId): int
    {
        if (!isset(self::$quotedAuthors[$postId])) {
            $post = get_post($postId);
            self::$quotedAuthors[$postId] = !empty($post['uid']) ? postcolors_resolve_pid((int)$post['uid']) : 0;
        }
        return self::$quotedAuthors[$postId];
    }
}
