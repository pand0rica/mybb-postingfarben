<?php
/**
 * Postcolors – Farb- und Theme-Helfer (Validierung, erlaubte Themes, Hintergrundfarbe).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class PostcolorsThemeHelper
{
    /**
     * Normalisiert eine Farbe auf #RRGGBB (Großbuchstaben). Leere Eingabe -> ''.
     * Ungültige Eingabe -> null.
     */
    public static function normalizeColor(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if ($value[0] !== '#') {
            $value = '#' . $value;
        }
        if (preg_match('/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/', $value, $m)) {
            $value = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return null;
        }
        return strtoupper($value);
    }

    public static function isValidColor(string $value): bool
    {
        return (bool)preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
    }

    /**
     * Haupt- und Zusatzgruppen eines Accounts.
     *
     * @return int[]
     */
    public static function userGroups(array $user): array
    {
        $groups = [(int)($user['usergroup'] ?? 0)];
        foreach (explode(',', (string)($user['additionalgroups'] ?? '')) as $gid) {
            $gid = (int)trim($gid);
            if ($gid > 0) {
                $groups[] = $gid;
            }
        }
        return array_values(array_unique($groups));
    }

    /**
     * Prüft eine Gruppenliste gegen ein groupselect-Setting (-1 = alle, '' = keine).
     */
    public static function groupsAllowed(array $groups, string $setting): bool
    {
        $setting = trim($setting);
        if ($setting === '-1') {
            return true;
        }
        if ($setting === '') {
            return false;
        }
        $allowed = array_map('intval', explode(',', $setting));
        return count(array_intersect($groups, $allowed)) > 0;
    }

    /**
     * Darf eine Gruppenliste ein Theme benutzen? (allowedgroups: "all" oder Liste)
     */
    public static function themeAllowed(array $theme, array $groups): bool
    {
        $allowed = trim((string)($theme['allowedgroups'] ?? ''));
        if ($allowed === '' || strtolower($allowed) === 'all') {
            return true;
        }
        $list = [];
        foreach (explode(',', $allowed) as $gid) {
            $gid = trim($gid);
            if ($gid === 'all') {
                return true;
            }
            if ($gid !== '') {
                $list[] = (int)$gid;
            }
        }
        return count(array_intersect($groups, $list)) > 0;
    }

    /**
     * Ermittelt die Hintergrundfarbe jedes Themes aus seinen Stylesheets (inklusive geerbter
     * Stylesheets der Eltern-Themes) über eine CSS-Variable.
     *
     * @param array<int, array> $themes tid => Theme-Zeile (tid, pid)
     * @return array<int, string> tid => CSS-Farbwert
     */
    public static function backgrounds(array $themes, string $variable, string $fallback): array
    {
        global $db;

        $fallback = self::safeCssColor($fallback) ?? '#FFFFFF';
        $result = [];
        $variable = trim($variable);

        if ($variable === '' || !preg_match('/^--[a-zA-Z0-9_-]+$/', $variable)) {
            foreach ($themes as $tid => $theme) {
                $result[(int)$tid] = $fallback;
            }
            return $result;
        }

        $parents = [];
        $query = $db->simple_select('themes', 'tid, pid');
        while ($row = $db->fetch_array($query)) {
            $parents[(int)$row['tid']] = (int)$row['pid'];
        }
        foreach ($themes as $tid => $theme) {
            $parents[(int)$tid] = (int)($theme['pid'] ?? ($parents[(int)$tid] ?? 0));
        }

        // Stylesheets je Theme und Name (wie MyBB: gleichnamiges Stylesheet im Child ersetzt das der Eltern)
        $sheets = [];
        $query = $db->simple_select('themestylesheets', 'tid, name, stylesheet', '', ['order_by' => 'sid']);
        while ($row = $db->fetch_array($query)) {
            $sheets[(int)$row['tid']][(string)$row['name']] = (string)$row['stylesheet'];
        }

        foreach (array_map('intval', array_keys($themes)) as $tid) {
            // Kette vom Theme bis zur Wurzel
            $chain = [];
            $current = $tid;
            while ($current > 0 && !in_array($current, $chain, true) && count($chain) < 20) {
                $chain[] = $current;
                $current = $parents[$current] ?? 0;
            }
            $effective = [];
            foreach ($chain as $ancestor) {
                foreach ($sheets[$ancestor] ?? [] as $name => $css) {
                    if (!isset($effective[$name])) {
                        $effective[$name] = $css;
                    }
                }
            }
            $vars = self::collectRootVariables($effective);
            $value = isset($vars[$variable]) ? self::resolveVariables($vars[$variable], $vars) : null;
            $value = $value !== null ? self::safeCssColor($value) : null;
            $result[$tid] = $value ?? $fallback;
        }
        return $result;
    }

    /**
     * Sammelt Custom Properties aus :root-Regeln. Werte aus :where(:root) (Spezifität 0) werden
     * von normalen :root-Regeln überschrieben; @media-Blöcke werden ignoriert.
     *
     * @param array<string,string> $stylesheets Name => CSS
     * @return array<string,string> --name => Wert
     */
    public static function collectRootVariables(array $stylesheets): array
    {
        $weak = [];
        $strong = [];
        foreach ($stylesheets as $css) {
            $css = (string)preg_replace('#/\*.*?\*/#s', '', $css);
            // @media/@supports-Blöcke (eine Verschachtelungsebene) entfernen
            $css = (string)preg_replace('#@(media|supports|container)[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}#s', '', $css);
            if (!preg_match_all('#([^{}]+)\{([^{}]*)\}#s', $css, $rules, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($rules as $rule) {
                $selector = trim($rule[1]);
                if (stripos($selector, ':root') === false) {
                    continue;
                }
                $isWeak = stripos($selector, ':where(') !== false;
                if (!preg_match_all('#(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+)#', $rule[2], $decls, PREG_SET_ORDER)) {
                    continue;
                }
                foreach ($decls as $decl) {
                    $value = trim(str_ireplace('!important', '', $decl[2]));
                    if ($isWeak) {
                        $weak[$decl[1]] = $value;
                    } else {
                        $strong[$decl[1]] = $value;
                    }
                }
            }
        }
        return array_merge($weak, $strong);
    }

    /**
     * Löst var(--x) bzw. var(--x, Fallback) rekursiv auf (max. 10 Ebenen).
     */
    public static function resolveVariables(string $value, array $vars, int $depth = 0): ?string
    {
        $value = trim($value);
        if ($depth > 10) {
            return null;
        }
        if (!preg_match('#^var\(\s*(--[a-zA-Z0-9_-]+)\s*(?:,\s*(.+))?\)$#s', $value, $m)) {
            return $value;
        }
        if (isset($vars[$m[1]])) {
            $resolved = self::resolveVariables($vars[$m[1]], $vars, $depth + 1);
            if ($resolved !== null) {
                return $resolved;
            }
        }
        return isset($m[2]) && trim($m[2]) !== '' ? self::resolveVariables($m[2], $vars, $depth + 1) : null;
    }

    /**
     * Lässt nur harmlose CSS-Farbwerte zu (Hex, rgb/rgba/hsl/hsla, Farbnamen).
     */
    public static function safeCssColor(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)
            || preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/deg]+\)$/i', $value)
            || preg_match('/^[a-zA-Z]{3,30}$/', $value)) {
            return $value;
        }
        return null;
    }

    /**
     * WCAG-Kontrastverhältnis zwischen zwei #RRGGBB-Farben (null, wenn nicht berechenbar).
     */
    public static function contrast(string $a, string $b): ?float
    {
        if (!self::isValidColor($a) || !self::isValidColor($b)) {
            return null;
        }
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $rgb = [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
        $c = [];
        foreach ($rgb as $v) {
            $v /= 255;
            $c[] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }
}
