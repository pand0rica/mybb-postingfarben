<?php
/**
 * Postcolors – Erkennung wörtlicher Rede.
 *
 * Reine String-API ohne MyBB-Abhängigkeiten, damit die Klasse per CLI testbar ist
 * (tests/speech_detector_test.php). Arbeitet auf einer Zeichenliste (UTF-8-Zeichen) und
 * liefert Bereiche [start, ende) in Zeichen-Offsets.
 *
 * Konventionen der Eingabe:
 * - "\n" steht für einen einfachen Zeilenumbruch,
 * - "\n" + optionaler Leerraum + "\n" ist eine Absatzgrenze,
 * - OBJ (U+FFFC) steht für eine ausgelassene Passage (Link, Zitat-Inhalt …) und zählt wie
 *   ein Wortzeichen.
 */

if (!defined('IN_MYBB') && PHP_SAPI !== 'cli') {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsSpeechDetector
{
    public const OBJ = "\u{FFFC}";

    /** @var string[] */
    private $open;

    /** @var string[] */
    private $close;

    /** @var bool */
    private $same;

    /** @var bool Paar besteht aus Apostroph-ähnlichen Zeichen (besonders vorsichtig). */
    private $apostropheLike;

    /** Elisionen, vor denen ein einfaches gerades Anführungszeichen ein Apostroph ist. */
    private const ELISIONS = [
        'n', 'ne', 'nen', 'nem', 'ner', 'nes', 's', 'em', 'm', 'r', 'twas', 'tis', 'til',
        'cause', 'bout', 'round', 'til', 'ya', 'cha', 'scuse',
    ];

    public function __construct(string $open, string $close)
    {
        $this->open = self::chars($open);
        $this->close = self::chars($close);
        $this->same = ($open === $close);
        $this->apostropheLike = in_array($close, ["'", "\u{2018}", "\u{2019}", "\u{201A}"], true)
            || in_array($open, ["'", "\u{2018}", "\u{2019}"], true);
    }

    /**
     * Zerlegt einen String in UTF-8-Zeichen.
     *
     * @return string[]
     */
    public static function chars(string $text): array
    {
        if ($text === '') {
            return [];
        }
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($chars) ? $chars : str_split($text);
    }

    /**
     * Findet wörtliche Rede in einer Zeichenliste.
     *
     * @param string[] $chars
     * @return array<int, array{0:int,1:int}> Bereiche [start, ende) inklusive Anführungszeichen
     */
    public function detect(array $chars): array
    {
        if (empty($this->open) || empty($this->close)) {
            return [];
        }

        $ranges = [];
        $n = count($chars);
        $paragraphStart = 0;
        $pending = null; // [openPos, paragraphEnd] einer offenen Rede aus dem vorigen Absatz

        $i = 0;
        while ($i <= $n) {
            $boundaryLen = ($i < $n) ? $this->paragraphBoundaryLength($chars, $i) : 0;
            if ($i === $n || $boundaryLen > 0) {
                $paragraphEnd = $i;
                $result = $this->detectParagraph($chars, $paragraphStart, $paragraphEnd);

                // Romanschreibweise: vorige Rede blieb offen, neuer Absatz beginnt wieder mit
                // einem öffnenden Zeichen -> vorigen Absatz bis zum Ende färben.
                if ($pending !== null) {
                    $first = $this->firstNonSpace($chars, $paragraphStart, $paragraphEnd);
                    if ($first !== null && $this->matchesAt($chars, $first, $this->open)
                        && (!$this->same || $this->canOpen($chars, $first, $paragraphStart, $paragraphEnd))) {
                        $ranges[] = $pending;
                    }
                    $pending = null;
                }

                foreach ($result['ranges'] as $range) {
                    $ranges[] = $range;
                }
                if ($result['open'] !== null) {
                    $end = $this->lastNonSpaceEnd($chars, $result['open'], $paragraphEnd);
                    $pending = [$result['open'], $end];
                }

                if ($i === $n) {
                    break;
                }
                $i += $boundaryLen;
                $paragraphStart = $i;
                continue;
            }
            $i++;
        }

        usort($ranges, static function (array $a, array $b): int {
            return $a[0] <=> $b[0];
        });
        return $ranges;
    }

    /**
     * Bequeme String-Variante für Tests: umschließt erkannte Rede mit $before/$after.
     */
    public function markup(string $text, string $before = '[', string $after = ']'): string
    {
        $chars = self::chars($text);
        $ranges = $this->detect($chars);
        $out = '';
        $r = 0;
        $count = count($chars);
        for ($i = 0; $i < $count; $i++) {
            if ($r < count($ranges) && $ranges[$r][0] === $i) {
                $out .= $before;
            }
            $out .= $chars[$i];
            if ($r < count($ranges) && $ranges[$r][1] === $i + 1) {
                $out .= $after;
                $r++;
            }
        }
        return $out;
    }

    /**
     * @param string[] $chars
     * @return array{ranges: array<int, array{0:int,1:int}>, open: int|null}
     */
    private function detectParagraph(array $chars, int $start, int $end): array
    {
        $ranges = [];
        $openPos = null;
        $depth = 0;
        $openLen = count($this->open);
        $closeLen = count($this->close);

        $i = $start;
        while ($i < $end) {
            if ($this->same) {
                if ($this->matchesAt($chars, $i, $this->open)) {
                    $canOpen = $this->canOpen($chars, $i, $start, $end);
                    $canClose = $this->canClose($chars, $i, $start, $end, $closeLen);
                    if ($canOpen && $canClose) {
                        // beides möglich -> kein Redezeichen (Regel 3)
                        $i += $openLen;
                        continue;
                    }
                    if ($openPos === null && $canOpen) {
                        $openPos = $i;
                    } elseif ($openPos !== null && $canClose) {
                        $ranges[] = [$openPos, $i + $closeLen];
                        $openPos = null;
                    } elseif ($openPos !== null && $canOpen) {
                        // erneut öffnend, ohne dass geschlossen wurde: konservativ neu beginnen
                        $openPos = $i;
                    }
                    $i += $openLen;
                    continue;
                }
                $i++;
                continue;
            }

            if ($this->matchesAt($chars, $i, $this->open) && $this->canOpenDistinct($chars, $i, $end)) {
                if ($depth === 0) {
                    $openPos = $i;
                }
                $depth++;
                $i += $openLen;
                continue;
            }
            if ($depth > 0 && $this->matchesAt($chars, $i, $this->close)
                && $this->canCloseDistinct($chars, $i, $start, $end, $closeLen)) {
                $depth--;
                if ($depth === 0 && $openPos !== null) {
                    $ranges[] = [$openPos, $i + $closeLen];
                    $openPos = null;
                }
                $i += $closeLen;
                continue;
            }
            $i++;
        }

        return ['ranges' => $ranges, 'open' => $openPos];
    }

    /** Länge einer Absatzgrenze ab $i ("\n", Leerraum, "\n") oder 0. */
    private function paragraphBoundaryLength(array $chars, int $i): int
    {
        if ($chars[$i] !== "\n") {
            return 0;
        }
        $n = count($chars);
        $j = $i + 1;
        while ($j < $n && ($chars[$j] === ' ' || $chars[$j] === "\t" || $chars[$j] === "\u{00A0}")) {
            $j++;
        }
        if ($j < $n && $chars[$j] === "\n") {
            $j++;
            while ($j < $n && self::isSpace($chars[$j])) {
                $j++;
            }
            return $j - $i;
        }
        return 0;
    }

    private function matchesAt(array $chars, int $i, array $marker): bool
    {
        $len = count($marker);
        for ($k = 0; $k < $len; $k++) {
            if (!isset($chars[$i + $k]) || $chars[$i + $k] !== $marker[$k]) {
                return false;
            }
        }
        return true;
    }

    private function canOpen(array $chars, int $i, int $start, int $end): bool
    {
        $len = count($this->open);
        $prev = ($i > $start) ? $chars[$i - 1] : null;
        $next = ($i + $len < $end) ? $chars[$i + $len] : null;
        if ($next === null || self::isSpace($next)) {
            return false;
        }
        if ($prev !== null && !self::isSpace($prev) && !self::isOpeningContext($prev)) {
            return false;
        }
        if ($this->apostropheLike && $this->isElision($chars, $i + $len, $end)) {
            return false;
        }
        return true;
    }

    private function canClose(array $chars, int $i, int $start, int $end, int $len): bool
    {
        $prev = ($i > $start) ? $chars[$i - 1] : null;
        $next = ($i + $len < $end) ? $chars[$i + $len] : null;
        if ($prev === null || self::isSpace($prev)) {
            return false;
        }
        if ($next !== null && !self::isSpace($next) && !self::isClosingFollower($next)) {
            return false;
        }
        return true;
    }

    private function canOpenDistinct(array $chars, int $i, int $end): bool
    {
        $len = count($this->open);
        $next = ($i + $len < $end) ? $chars[$i + $len] : null;
        if ($next === null || self::isSpace($next)) {
            return false;
        }
        return true;
    }

    private function canCloseDistinct(array $chars, int $i, int $start, int $end, int $len): bool
    {
        $prev = ($i > $start) ? $chars[$i - 1] : null;
        if ($prev === null || self::isSpace($prev)) {
            return false;
        }
        if ($this->apostropheLike) {
            $next = ($i + $len < $end) ? $chars[$i + $len] : null;
            if ($next !== null && self::isWordChar($next)) {
                return false; // geht‘s
            }
        }
        return true;
    }

    /** Folgt ab $pos eine bekannte Elision bzw. eine Ziffer ('n, 's, '90)? */
    private function isElision(array $chars, int $pos, int $end): bool
    {
        if ($pos >= $end) {
            return false;
        }
        if (ctype_digit($chars[$pos])) {
            return true;
        }
        $word = '';
        $j = $pos;
        while ($j < $end && self::isLetter($chars[$j])) {
            $word .= $chars[$j];
            $j++;
        }
        if ($word === '') {
            return false;
        }
        $lower = function_exists('mb_strtolower') ? mb_strtolower($word, 'UTF-8') : strtolower($word);
        if (!in_array($lower, self::ELISIONS, true)) {
            return false;
        }
        // Eine Elision ist es nur, wenn danach kein schließendes Zeichen direkt folgt ('s' = Rede "s")
        if ($j < $end && $this->matchesAt($chars, $j, $this->close)) {
            return false;
        }
        return true;
    }

    private function firstNonSpace(array $chars, int $start, int $end): ?int
    {
        for ($i = $start; $i < $end; $i++) {
            if (!self::isSpace($chars[$i])) {
                return $i;
            }
        }
        return null;
    }

    private function lastNonSpaceEnd(array $chars, int $start, int $end): int
    {
        for ($i = $end - 1; $i >= $start; $i--) {
            if (!self::isSpace($chars[$i])) {
                return $i + 1;
            }
        }
        return $end;
    }

    public static function isSpace(string $c): bool
    {
        return $c === ' ' || $c === "\n" || $c === "\t" || $c === "\r" || $c === "\u{00A0}"
            || $c === "\u{2009}" || $c === "\u{202F}";
    }

    private static function isOpeningContext(string $c): bool
    {
        return in_array($c, ['(', '[', '{', '–', '—', '-', '/', ':', '»', '«', '„', '“', '‚', '‘', "\u{2026}"], true);
    }

    private static function isClosingFollower(string $c): bool
    {
        return in_array($c, ['.', ',', ';', ':', '!', '?', ')', ']', '}', '–', '—', '-', '/', '…',
            '»', '«', '“', '”', '‘', '’', '"', "'"], true);
    }

    private static function isLetter(string $c): bool
    {
        return (bool)preg_match('/^\p{L}$/u', $c);
    }

    private static function isWordChar(string $c): bool
    {
        return $c === self::OBJ || (bool)preg_match('/^[\p{L}\p{N}]$/u', $c);
    }
}
