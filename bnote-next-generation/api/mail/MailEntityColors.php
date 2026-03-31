<?php
/**
 * Entity accent colors from frontend/config/entity-config.json — same source as frontend/lib/entity-config.ts.
 * oklch values are mapped to mail-safe hex. Concert uses the solid app accent (see globals.css --accent / bg-accent), not the lighter calendar mix hex.
 */
declare(strict_types=1);

require_once __DIR__ . '/MailDesignTokens.php';

final class MailEntityColors {
    /** @var array<string,mixed>|null */
    private static ?array $config = null;
    /** @var array<string, array{color:string, icon?:string}>|null */
    private static ?array $entities = null;

    /** @return array<string,mixed> */
    private static function config(): array {
        if (self::$config !== null) {
            return self::$config;
        }
        $root = dirname(__DIR__, 2);
        $path = $root . '/frontend/config/entity-config.json';
        if (!is_readable($path)) {
            self::$config = [];
            return self::$config;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        self::$config = is_array($data) ? $data : [];
        return self::$config;
    }

    /** @return array<string, array{color:string, icon?:string}> */
    private static function entities(): array {
        if (self::$entities !== null) {
            return self::$entities;
        }
        $cfg = self::config();
        $e = isset($cfg['entities']) && is_array($cfg['entities']) ? $cfg['entities'] : [];
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
        return self::resolveColorToHex($raw, self::fallbackHex($key));
    }

    private static function fallbackHex(string $key): string {
        return match ($key) {
            'rehearsal' => '#3399ff',
            'concert' => '#d18000',
            'vote' => '#a855f7',
            'task' => '#25a65a',
            default => '#6b7280',
        };
    }

    public static function statusHex(string $status): string {
        $statusKey = strtolower(trim($status));
        if ($statusKey === '') {
            return MailDesignTokens::get('textMuted');
        }
        $cfg = self::config();
        $raw = '';
        if (isset($cfg['statuses']) && is_array($cfg['statuses']) && isset($cfg['statuses'][$statusKey]) && is_array($cfg['statuses'][$statusKey])) {
            $raw = trim((string) ($cfg['statuses'][$statusKey]['color'] ?? ''));
        }
        if ($raw === '') {
            return match ($statusKey) {
                'confirmed', 'active' => MailDesignTokens::get('participationSuccess'),
                'cancelled', 'canceled', 'inactive' => MailDesignTokens::get('participationDestructive'),
                default => MailDesignTokens::get('textMuted'),
            };
        }
        return self::resolveColorToHex($raw, MailDesignTokens::get('textMuted'));
    }

    private static function resolveColorToHex(string $raw, string $fallback): string {
        $v = trim($raw);
        if ($v === '') {
            return $fallback;
        }
        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $v, $m)) {
            return '#' . strtolower($m[1]);
        }
        if (preg_match('/^var\(\s*(--[a-zA-Z0-9\-_]+)\s*\)$/', $v, $m)) {
            return self::cssVarToHex(strtolower($m[1]), $fallback);
        }
        $oklchHex = self::oklchToHex($v);
        if ($oklchHex !== null) {
            return $oklchHex;
        }
        return $fallback;
    }

    private static function cssVarToHex(string $cssVar, string $fallback): string {
        return match ($cssVar) {
            '--primary' => MailDesignTokens::get('primary'),
            '--success' => MailDesignTokens::get('participationSuccess'),
            '--destructive' => MailDesignTokens::get('participationDestructive'),
            '--warning' => MailDesignTokens::get('participationWarning'),
            '--muted-foreground' => MailDesignTokens::get('textMuted'),
            default => $fallback,
        };
    }

    private static function oklchToHex(string $raw): ?string {
        if (!preg_match('/oklch\(\s*([0-9]*\.?[0-9]+%?)\s+([0-9]*\.?[0-9]+)\s+([0-9]*\.?[0-9]+)\s*\)/i', trim($raw), $m)) {
            return null;
        }
        $lRaw = strtolower(trim((string) $m[1]));
        $c = (float) $m[2];
        $h = (float) $m[3];
        $l = str_ends_with($lRaw, '%')
            ? ((float) rtrim($lRaw, '%')) / 100.0
            : (float) $lRaw;
        $l = max(0.0, min(1.0, $l));
        $rad = deg2rad($h);
        $a = $c * cos($rad);
        $b = $c * sin($rad);

        $l_ = $l + 0.3963377774 * $a + 0.2158037573 * $b;
        $m_ = $l - 0.1055613458 * $a - 0.0638541728 * $b;
        $s_ = $l - 0.0894841775 * $a - 1.2914855480 * $b;

        $l3 = $l_ * $l_ * $l_;
        $m3 = $m_ * $m_ * $m_;
        $s3 = $s_ * $s_ * $s_;

        $rLin = +4.0767416621 * $l3 - 3.3077115913 * $m3 + 0.2309699292 * $s3;
        $gLin = -1.2684380046 * $l3 + 2.6097574011 * $m3 - 0.3413193965 * $s3;
        $bLin = -0.0041960863 * $l3 - 0.7034186147 * $m3 + 1.7076147010 * $s3;

        $r = self::linearToSrgb8($rLin);
        $g = self::linearToSrgb8($gLin);
        $bb = self::linearToSrgb8($bLin);

        return sprintf('#%02x%02x%02x', $r, $g, $bb);
    }

    private static function linearToSrgb8(float $x): int {
        $x = max(0.0, min(1.0, $x));
        $srgb = $x <= 0.0031308 ? 12.92 * $x : 1.055 * pow($x, 1 / 2.4) - 0.055;
        return (int) round(max(0.0, min(1.0, $srgb)) * 255.0);
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
