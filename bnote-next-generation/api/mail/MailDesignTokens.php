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
        'maxWidthCard' => "680px",
        'radiusCard' => "16px",
        'radiusButton' => "8px",
        'buttonPaddingY' => "13px",
        'buttonPaddingX' => "24px",
        'fontFamily' => "ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Helvetica Neue\",Arial,sans-serif",
        'fontSizeBody' => "15px",
        'fontSizeSmall' => "13px",
        'fontSizeFooter' => "12px",
        'lineHeight' => "1.5",
        'logoWidth' => "40",
        'logoHeight' => "40",
        'pageBgDark' => "#12151c",
        'cardBgDark' => "#1e232c",
        'textDark' => "#eceff4",
        'textSecondaryDark' => "#b8c0cc",
        'textMutedDark' => "#8b95a5",
        'borderDark' => "#3d4654",
        'shadowCardDark' => "0 1px 3px rgba(0,0,0,0.45)",
        ];
    }

    public static function get(string $key, string $default = ''): string {
        $t = self::tokens();
        return $t[$key] ?? $default;
    }
}
