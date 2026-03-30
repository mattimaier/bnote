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

    /**
     * Next.js base path only (leading slash, no trailing slash), for same-origin links when no absolute URL is configured.
     * Matches frontend default in next.config.ts when NEXT_PUBLIC_BASE_PATH is unset in PHP.
     */
    public static function nextgenAppPathPrefix(): string {
        $v = getenv('NEXT_PUBLIC_BASE_PATH');
        if ($v === false || $v === null) {
            return '/bnote-next-generation';
        }
        $p = trim((string) $v);
        if ($p === '') {
            return '';
        }
        return '/' . trim($p, '/');
    }

    /**
     * Root-relative password reset URL (always usable on the same host as the SPA).
     */
    public static function nextgenPasswordResetRelativeUrl(string $plainToken): string {
        $prefix = self::nextgenAppPathPrefix();
        $suffix = '/reset-password/confirm/?token=' . rawurlencode($plainToken);
        return ($prefix === '' ? '' : $prefix) . $suffix;
    }

    /**
     * Delay between consecutive sends when using {@see NextGenMailer::sendBulk()} (comment / admin fan-out).
     * Env `NEXTGEN_MAIL_BULK_DELAY_MS`: milliseconds, 0 = no pause. Unset defaults to 100 ms to reduce SMTP rate limits.
     */
    public static function bulkSendDelayMicroseconds(): int {
        $raw = getenv('NEXTGEN_MAIL_BULK_DELAY_MS');
        if ($raw === false || $raw === null) {
            return 100_000;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return 100_000;
        }
        $ms = (int) $s;
        if ($ms < 0) {
            $ms = 0;
        }
        if ($ms > 10_000) {
            $ms = 10_000;
        }

        return $ms * 1000;
    }

    private static function getenvTrim(string $key): string {
        $v = getenv($key);
        return is_string($v) ? trim($v) : '';
    }
}
