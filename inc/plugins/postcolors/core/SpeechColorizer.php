<?php
/**
 * Postcolors – wendet die Erkennung wörtlicher Rede auf fertig geparstes HTML an.
 *
 * Arbeitet ausschließlich auf Textknoten: Das HTML wird in Tags und Text zerlegt, nur die
 * Textteile werden zu einer Zeichenliste zusammengesetzt, erkannt und anschließend einzeln
 * mit <span> umschlossen. Spans enthalten dadurch nie ein Tag, es entsteht immer gültig
 * verschachteltes HTML. Ausgelassen werden Zitate, Code, Links, bereits gefärbte Passagen
 * und unbekannte Elemente (Namens-Tags bei erlaubtem HTML).
 */

if (!defined('IN_MYBB') && PHP_SAPI !== 'cli') {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsSpeechColorizer
{
    /** Elemente, deren Inhalt nie gefärbt wird. */
    private const SKIP = ['blockquote', 'code', 'pre', 'a', 'script', 'style', 'textarea', 'select',
        'button', 'iframe', 'object', 'video', 'audio', 'svg', 'math'];

    /** Blockelemente = Absatzgrenze. */
    private const BLOCK = ['p', 'div', 'blockquote', 'li', 'ul', 'ol', 'table', 'thead', 'tbody',
        'tfoot', 'tr', 'td', 'th', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'dl', 'dt', 'dd',
        'section', 'article', 'header', 'footer', 'aside', 'nav', 'figure', 'figcaption',
        'fieldset', 'legend', 'details', 'summary', 'pre', 'center'];

    private const VOID = ['br', 'hr', 'img', 'input', 'meta', 'link', 'area', 'base', 'col',
        'embed', 'param', 'source', 'track', 'wbr'];

    /** Standard-HTML-Elemente; alles andere gilt als Namens-Tag und wird ausgelassen. */
    private const KNOWN = ['a', 'abbr', 'acronym', 'address', 'area', 'article', 'aside', 'audio',
        'b', 'bdi', 'bdo', 'big', 'blockquote', 'br', 'button', 'caption', 'center', 'cite', 'code',
        'col', 'colgroup', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'embed',
        'fieldset', 'figcaption', 'figure', 'font', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'header', 'hr', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li',
        'mark', 'nav', 'object', 'ol', 'p', 'param', 'pre', 'q', 's', 'samp', 'section', 'select',
        'small', 'source', 'span', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody',
        'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'tt', 'u', 'ul', 'var', 'video',
        'wbr', 'script', 'style', 'svg', 'math', 'meta', 'link', 'base', 'track'];

    /** @var PostcolorsSpeechDetector */
    private $detector;

    /** @var string */
    private $class;

    public function __construct(string $open, string $close, string $class)
    {
        $this->detector = new PostcolorsSpeechDetector($open, $close);
        $this->class = $class;
    }

    public function apply(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $parts = preg_split('#(<!--.*?-->|</?[a-zA-Z][^>]*>)#s', $html, -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return $html;
        }

        $segments = [];   // [type, raw, units|null]
        $flat = [];       // Zeichenliste
        $map = [];        // flatIndex => [segIndex, unitIndex] | null
        $skipName = null;
        $skipDepth = 0;

        foreach ($parts as $raw) {
            $segIndex = count($segments);
            if ($raw[0] === '<' && preg_match('#^<(!--|/?)([a-zA-Z][a-zA-Z0-9\-]*)?#', $raw, $m)) {
                $segments[] = ['tag', $raw, null];
                if ($m[1] === '!--') {
                    continue;
                }
                $closing = ($m[1] === '/');
                $name = strtolower($m[2] ?? '');
                $selfClosing = in_array($name, self::VOID, true) || substr($raw, -2) === '/>';

                if ($skipName !== null) {
                    if ($name === $skipName && !$selfClosing) {
                        $skipDepth += $closing ? -1 : 1;
                        if ($skipDepth === 0) {
                            $skipName = null;
                            if (in_array($name, self::BLOCK, true)) {
                                $this->push($flat, $map, "\n", null);
                                $this->push($flat, $map, "\n", null);
                            }
                        }
                    }
                    continue;
                }

                if (!$closing && !$selfClosing && $this->startsSkip($name, $raw)) {
                    $skipName = $name;
                    $skipDepth = 1;
                    if (in_array($name, self::BLOCK, true)) {
                        $this->push($flat, $map, "\n", null);
                        $this->push($flat, $map, "\n", null);
                    } else {
                        $this->push($flat, $map, PostcolorsSpeechDetector::OBJ, null);
                    }
                    continue;
                }

                if ($name === 'br') {
                    $this->push($flat, $map, "\n", null);
                } elseif (in_array($name, self::BLOCK, true)) {
                    $this->push($flat, $map, "\n", null);
                    $this->push($flat, $map, "\n", null);
                } elseif ($name === 'img') {
                    $this->push($flat, $map, PostcolorsSpeechDetector::OBJ, null);
                }
                continue;
            }

            if ($skipName !== null) {
                $segments[] = ['skip', $raw, null];
                continue;
            }

            preg_match_all('/&(?:#[0-9]+|#x[0-9a-fA-F]+|[a-zA-Z][a-zA-Z0-9]*);|./su', $raw, $um);
            $units = $um[0];
            $segments[] = ['text', $raw, $units];
            foreach ($units as $unitIndex => $unit) {
                $decoded = (strlen($unit) > 1 && $unit[0] === '&')
                    ? html_entity_decode($unit, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    : $unit;
                if ($decoded === "\n" || $decoded === "\r") {
                    $decoded = ' ';
                }
                $this->push($flat, $map, $decoded, [$segIndex, $unitIndex]);
            }
        }

        $ranges = $this->detector->detect($flat);
        if (empty($ranges)) {
            return $html;
        }

        $marked = [];
        foreach ($ranges as $range) {
            for ($i = $range[0]; $i < $range[1]; $i++) {
                if (isset($map[$i])) {
                    $marked[$map[$i][0]][$map[$i][1]] = true;
                }
            }
        }

        $out = '';
        foreach ($segments as $segIndex => $segment) {
            if ($segment[0] !== 'text' || empty($marked[$segIndex])) {
                $out .= $segment[1];
                continue;
            }
            $open = false;
            foreach ($segment[2] as $unitIndex => $unit) {
                $isMarked = !empty($marked[$segIndex][$unitIndex]);
                if ($isMarked && !$open) {
                    $out .= '<span class="' . $this->class . '">';
                    $open = true;
                } elseif (!$isMarked && $open) {
                    $out .= '</span>';
                    $open = false;
                }
                $out .= $unit;
            }
            if ($open) {
                $out .= '</span>';
            }
        }
        return $out;
    }

    private function push(array &$flat, array &$map, string $char, ?array $ref): void
    {
        $map[count($flat)] = $ref;
        $flat[] = $char;
    }

    /** Soll der Inhalt dieses Elements ausgelassen werden? */
    private function startsSkip(string $name, string $raw): bool
    {
        if (in_array($name, self::SKIP, true)) {
            return true;
        }
        if (!in_array($name, self::KNOWN, true)) {
            return true; // Namens-Tag oder unbekanntes Element
        }
        if ($name === 'div' && preg_match('#class\s*=\s*["\'][^"\']*\bcodeblock\b#i', $raw)) {
            return true;
        }
        if (($name === 'span' || $name === 'div' || $name === 'p' || $name === 'font')
            && (preg_match('#class\s*=\s*["\'](?:[^"\']*\s)?pc(?:\s|["\'])#i', $raw)
                || preg_match('#style\s*=\s*["\'][^"\']*(?<![-\w])color\s*:#i', $raw)
                || ($name === 'font' && preg_match('#\scolor\s*=#i', $raw)))) {
            return true;
        }
        return false;
    }
}
