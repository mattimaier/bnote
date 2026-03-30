<?php
/**
 * Auto-generated from frontend/mail-design-tokens.json — run: npm run sync:mail-design
 */
declare(strict_types=1);

final class MailDesignTokens {
    /** @return array<string, string> */
    public static function tokens(): array {
        return [
        'primary' => "#3399ff",
        'primaryContent' => "#ffffff",
        'text' => "#111827",
        'textMuted' => "#6b7280",
        'textSecondary' => "#4b5563",
        'border' => "#e5e7eb",
        'pageBg' => "#f4f4f5",
        'cardBg' => "#ffffff",
        'shadowCard' => "0 1px 3px rgba(0,0,0,0.08)",
        'radiusCard' => "16px",
        'radiusButton' => "8px",
        'buttonPaddingY' => "10px",
        'buttonPaddingX' => "16px",
        'fontFamily' => "system-ui,-apple-system,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif",
        'fontSizeBody' => "15px",
        'fontSizeSmall' => "13px",
        'fontSizeFooter' => "12px",
        'lineHeight' => "1.5",
        'logoWidth' => "40",
        'logoHeight' => "40",
        ];
    }

    public static function get(string $key, string $default = ''): string {
        $t = self::tokens();
        return $t[$key] ?? $default;
    }
}
