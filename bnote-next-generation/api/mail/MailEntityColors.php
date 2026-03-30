<?php
/**
 * Entity accent colors from frontend/config/entity-config.json — same source as frontend/lib/entity-config.ts.
 * oklch values are mapped to mail-safe hex. Concert uses the solid app accent (see globals.css --accent / bg-accent), not the lighter calendar mix hex.
 */
declare(strict_types=1);

final class MailEntityColors {
    /** @var array<string, array{color:string, icon?:string}>|null */
    private static ?array $entities = null;

    /** @return array<string, array{color:string, icon?:string}> */
    private static function entities(): array {
        if (self::$entities !== null) {
            return self::$entities;
        }
        $root = dirname(__DIR__, 2);
        $path = $root . '/frontend/config/entity-config.json';
        if (!is_readable($path)) {
            self::$entities = [];

            return self::$entities;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $e = is_array($data) && isset($data['entities']) && is_array($data['entities'])
            ? $data['entities'] : [];
        self::$entities = $e;

        return self::$entities;
    }

    private static function rawColor(string $entityKey): string {
        $k = strtolower($entityKey);
        $e = self::entities();

        return isset($e[$k]['color']) ? trim((string) $e[$k]['color']) : '';
    }

    public static function solidHex(string $entityKey): string {
        $key = strtolower($entityKey);
        $raw = self::rawColor($key);
        if ($raw === '') {
            return self::fallbackHex($key);
        }
        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $raw, $m)) {
            return '#' . strtolower($m[1]);
        }
        if (str_contains(strtolower($raw), 'oklch')) {
            return self::oklchToMailHex($key, $raw);
        }

        return self::fallbackHex($key);
    }

    /** Concert: oklch(0.68 0.20 80) → solid accent for icon circle + badge label (matches EventDetail bg-accent / .event-badge.accent color). */
    private static function oklchToMailHex(string $key, string $raw): string {
        $r = strtolower($raw);
        if ($key === 'concert' && str_contains($r, '0.68') && str_contains($r, '80')) {
            return '#d18000';
        }
        if ($key === 'meeting' && str_contains($r, '0.62') && str_contains($r, '150')) {
            return '#3d9970';
        }

        return self::fallbackHex($key);
    }

    private static function fallbackHex(string $key): string {
        return match ($key) {
            'rehearsal' => '#3399ff',
            'concert' => '#d18000',
            'vote' => '#a855f7',
            default => '#6b7280',
        };
    }

    /**
     * Pill + icon colors for comment-discussion entity header (approx. getPillStyle / event-badge).
     *
     * @return array{icon_bg:string,badge_bg:string,badge_border:string,badge_text:string}
     */
    public static function commentDiscussionCardAccents(string $entityKey): array {
        $icon_bg = self::solidHex($entityKey);

        return [
            'icon_bg' => $icon_bg,
            'badge_bg' => self::mixWithWhite($icon_bg, 0.10),
            'badge_border' => self::mixWithWhite($icon_bg, 0.22),
            'badge_text' => $icon_bg,
        ];
    }

    /** @param float $colorShare 0…1 fraction of entity color vs white */
    public static function mixWithWhite(string $hex, float $colorShare): string {
        $hex = ltrim(trim($hex), '#');
        if (!preg_match('/^([0-9a-f]{6})$/i', $hex, $m)) {
            return '#f3f4f6';
        }
        $hex = strtolower($m[1]);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $w = 1.0 - $colorShare;
        $nr = (int) round($r * $colorShare + 255 * $w);
        $ng = (int) round($g * $colorShare + 255 * $w);
        $nb = (int) round($b * $colorShare + 255 * $w);

        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, $nr)),
            max(0, min(255, $ng)),
            max(0, min(255, $nb))
        );
    }

    /** Light fill for “new comment” callout, tinted by entity accent. */
    public static function mailMutedFill(string $solidHex, float $colorShare = 0.085): string {
        $hex = ltrim(trim($solidHex), '#');
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return '#eff6ff';
        }

        return self::mixWithWhite('#' . strtolower($hex), $colorShare);
    }
}
