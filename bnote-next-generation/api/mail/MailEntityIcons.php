<?php
/**
 * Inline SVG icons for transactional mail — same names as frontend/config/entity-config.json → components/icons.tsx (Tabler).
 * Email clients cannot run React/Iconify; use compact stroke SVGs with currentColor (white on entity circle).
 */
declare(strict_types=1);

final class MailEntityIcons {
    /**
     * @return array{color:string, icon?:string}|null
     */
    private static function entityEntry(string $entityKey): ?array {
        $root = dirname(__DIR__, 2);
        $path = $root . '/frontend/config/entity-config.json';
        if (!is_readable($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $k = strtolower($entityKey);
        if (!is_array($data) || !isset($data['entities'][$k]) || !is_array($data['entities'][$k])) {
            return null;
        }

        return $data['entities'][$k];
    }

    /** Icon name from entity-config.json (e.g. music, mic-vocal, vote). */
    public static function iconNameForEntityKey(string $entityKey): string {
        $e = self::entityEntry($entityKey);
        $name = isset($e['icon']) ? trim((string) $e['icon']) : '';

        return $name !== '' ? strtolower($name) : 'music';
    }

    /**
     * Trusted SVG fragment (no user input) for insertion inside the icon circle.
     */
    public static function inlineSvgForEntityKey(string $entityKey): string {
        return self::svgForIconName(self::iconNameForEntityKey($entityKey));
    }

    public static function svgForIconName(string $iconName): string {
        $n = strtolower(trim($iconName));

        return match ($n) {
            'music', 'trumpet' => self::svgMusic(),
            'mic', 'mic-vocal' => self::svgMicrophone(),
            'vote' => self::svgVote(),
            'check-square' => self::svgCheckSquare(),
            'users' => self::svgUsers(),
            'map-pin' => self::svgMapPin(),
            'calendar', 'calendar-check' => self::svgCalendar(),
            'terminal' => self::svgTerminal(),
            'building' => self::svgBuilding(),
            'shield-check' => self::svgShieldCheck(),
            'user', 'user-circle' => self::svgUser(),
            'package' => self::svgPackage(),
            'shirt' => self::svgShirt(),
            default => self::svgMusic(),
        };
    }

    /**
     * Tabler-style paths (stroke), 24×24, scaled to 18px in mail.
     */
    private static function svgWrap(string $innerPath): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" '
            . 'role="img" aria-hidden="true" style="display:block;margin:0 auto;">'
            . $innerPath
            . '</svg>';
    }

    private static function svgMusic(): string {
        // Tabler-style "music" (single stem + two note heads)
        return self::svgWrap(
            '<path d="M9 18V5l12-2v13" />'
            . '<circle cx="6" cy="18" r="3" />'
            . '<circle cx="18" cy="16" r="3" />'
        );
    }

    private static function svgMicrophone(): string {
        // Tabler outline "microphone"
        return self::svgWrap(
            '<path d="M9 5a3 3 0 0 1 6 0v6a3 3 0 0 1 -6 0v-6z" />'
            . '<path d="M5 10a7 7 0 0 0 14 0" />'
            . '<path d="M12 19v4" />'
        );
    }

    private static function svgVote(): string {
        // Tabler outline "circle-dot"
        return self::svgWrap(
            '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />'
            . '<path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />'
        );
    }

    private static function svgCheckSquare(): string {
        // Tabler-style "check-square"
        return self::svgWrap(
            '<path d="M9 12l2 2l4 -4" />'
            . '<rect x="4" y="4" width="16" height="16" rx="2" />'
        );
    }

    private static function svgUsers(): string {
        return self::svgWrap(
            '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />'
            . '<circle cx="9.5" cy="7" r="3" />'
            . '<path d="M22 21v-2a4 4 0 0 0-3-3.87" />'
            . '<path d="M16 3.13a3 3 0 0 1 0 5.82" />'
        );
    }

    private static function svgMapPin(): string {
        return self::svgWrap(
            '<path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z" />'
            . '<circle cx="12" cy="11" r="2.5" />'
        );
    }

    private static function svgCalendar(): string {
        return self::svgWrap(
            '<rect x="3" y="5" width="18" height="16" rx="2" />'
            . '<path d="M16 3v4M8 3v4M3 11h18" />'
        );
    }

    private static function svgTerminal(): string {
        return self::svgWrap(
            '<rect x="3" y="4" width="18" height="16" rx="2" />'
            . '<path d="M7 9l3 3l-3 3M13 15h4" />'
        );
    }

    private static function svgBuilding(): string {
        return self::svgWrap(
            '<path d="M3 21h18" />'
            . '<path d="M5 21V7l7-4l7 4v14" />'
            . '<path d="M9 10h2v2H9zM13 10h2v2h-2zM9 14h2v2H9zM13 14h2v2h-2z" />'
        );
    }

    private static function svgShieldCheck(): string {
        return self::svgWrap(
            '<path d="M12 22s8-4 8-10V6l-8-4l-8 4v6c0 6 8 10 8 10z" />'
            . '<path d="M9 12l2 2l4-4" />'
        );
    }

    private static function svgUser(): string {
        return self::svgWrap(
            '<circle cx="12" cy="8" r="4" />'
            . '<path d="M4 20a8 8 0 0 1 16 0" />'
        );
    }

    private static function svgPackage(): string {
        return self::svgWrap(
            '<path d="M3 7l9-4l9 4v10l-9 4l-9-4z" />'
            . '<path d="M3 7l9 4l9-4M12 11v10" />'
        );
    }

    private static function svgShirt(): string {
        return self::svgWrap(
            '<path d="M7 4l2 2h6l2-2l4 3l-3 4h-2v9H8v-9H6L3 7z" />'
        );
    }
}
