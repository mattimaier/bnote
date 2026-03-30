<?php
/**
 * Inline SVG icons for transactional mail — same names as frontend/config/entity-config.json → components/icons.tsx (Tabler).
 * Email clients cannot run React/Iconify; use compact stroke SVGs with currentColor (white on entity circle).
 */
declare(strict_types=1);

require_once __DIR__ . '/MailEntityColors.php';

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
            default => self::svgMusic(),
        };
    }

    /**
     * Tabler-style paths (stroke), 24×24, scaled to 22px in mail.
     */
    private static function svgWrap(string $innerPath): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" '
            . 'role="img" aria-hidden="true" style="display:block;vertical-align:middle;">'
            . $innerPath
            . '</svg>';
    }

    private static function svgMusic(): string {
        return self::svgWrap(
            '<path d="M6 5h12v12a3 3 0 1 1 -3 3a3 3 0 0 1 3 -3v-6h-4v7a3 3 0 1 1 -3 3a3 3 0 0 1 3 -3v-6z" />'
        );
    }

    private static function svgMicrophone(): string {
        return self::svgWrap(
            '<path d="M9 5a3 3 0 1 1 6 0v6a3 3 0 1 1 -6 0v-6z" />'
            . '<path d="M5 10a3 3 0 0 0 3 3h8a3 3 0 0 0 3 -3" />'
            . '<path d="M12 19v3" />'
        );
    }

    private static function svgVote(): string {
        return self::svgWrap(
            '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />'
            . '<path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />'
        );
    }
}
