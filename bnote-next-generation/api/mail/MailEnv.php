<?php
/**
 * SMTP / sender configuration for Next Gen mail (env-first).
 */
declare(strict_types=1);

final class MailEnv {
    public static function host(): string {
        return self::getenvTrim('MAIL_HOST');
    }

    public static function port(): int {
        $p = self::getenvTrim('MAIL_PORT');
        return $p !== '' ? max(1, (int) $p) : 587;
    }

    public static function username(): string {
        return self::getenvTrim('MAIL_USERNAME');
    }

    public static function password(): string {
        return (string) (getenv('MAIL_PASSWORD') ?: '');
    }

    /** '', 'tls', or 'ssl' */
    public static function encryption(): string {
        $e = strtolower(self::getenvTrim('MAIL_ENCRYPTION'));
        return in_array($e, ['tls', 'ssl'], true) ? $e : '';
    }

    public static function fromAddress(): string {
        return self::getenvTrim('MAIL_FROM_ADDRESS');
    }

    public static function fromName(): string {
        return self::getenvTrim('MAIL_FROM_NAME');
    }

    /**
     * Public base URL of the Next Gen SPA (no trailing slash), e.g. https://example.com/bnote-next-generation
     */
    public static function nextgenPublicBaseUrl(): string {
        $full = self::getenvTrim('NEXTGEN_PUBLIC_URL');
        if ($full !== '') {
            return rtrim($full, '/');
        }
        $origin = self::getenvTrim('NEXTGEN_PUBLIC_ORIGIN');
        $path = self::getenvTrim('NEXT_PUBLIC_BASE_PATH');
        if ($origin !== '' && $path !== '') {
            return rtrim($origin, '/') . '/' . trim($path, '/');
        }
        return '';
    }

    private static function getenvTrim(string $key): string {
        $v = getenv($key);
        return is_string($v) ? trim($v) : '';
    }
}
