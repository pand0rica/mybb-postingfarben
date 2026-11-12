<?php
/**
 * Postcolors – Anführungszeichen-Paare für die automatische Färbung wörtlicher Rede.
 *
 * Pro Person können mehrere Voreinstellungen (Spalte quote_presets, kommagetrennte Schlüssel)
 * und zusätzlich ein eigenes Paar (quote_open/quote_close) aktiv sein.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsQuotes
{
    /**
     * Voreinstellungen in Anwendungsreihenfolge: doppelte vor einfachen Zeichen, damit bei
     * verschachtelten Zitaten („… ‚…‘ …“) zuerst die äußere Rede gefärbt wird.
     *
     * @return array<string, array{0:string,1:string}>
     */
    public static function presets(): array
    {
        return [
            'de' => ["\u{201E}", "\u{201C}"],
            'guillemets' => ["\u{00BB}", "\u{00AB}"],
            'guillemets_fr' => ["\u{00AB}", "\u{00BB}"],
            'straight' => ['"', '"'],
            'en' => ["\u{201C}", "\u{201D}"],
            'de_single' => ["\u{201A}", "\u{2018}"],
            'single' => ["'", "'"],
        ];
    }

    /** Bereinigt eine Schlüsselliste (nur bekannte Schlüssel, Reihenfolge wie presets()). */
    public static function normalizeKeys(array $keys): array
    {
        $keys = array_map('strval', $keys);
        return array_values(array_filter(array_keys(self::presets()), static function (string $key) use ($keys): bool {
            return in_array($key, $keys, true);
        }));
    }

    /** @return string[] */
    public static function parseKeys(string $csv): array
    {
        return self::normalizeKeys(array_map('trim', explode(',', $csv)));
    }

    /** Liefert den Schlüssel einer Voreinstellung zu einem Paar oder ''. */
    public static function presetKey(string $open, string $close): string
    {
        foreach (self::presets() as $key => $pair) {
            if ($pair[0] === $open && $pair[1] === $close) {
                return $key;
            }
        }
        return '';
    }

    /**
     * Alle aktiven Paare: Voreinstellungen in fester Reihenfolge, eigenes Paar zuletzt, ohne Doppelte.
     *
     * @return array<int, array{0:string,1:string}>
     */
    public static function pairs(string $presetsCsv, string $open, string $close): array
    {
        $presets = self::presets();
        $pairs = [];
        foreach (self::parseKeys($presetsCsv) as $key) {
            $pairs[] = $presets[$key];
        }
        if ($open !== '' && $close !== '' && !in_array([$open, $close], $pairs, true)) {
            $pairs[] = [$open, $close];
        }
        return $pairs;
    }
}
