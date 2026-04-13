<?php
/**
 * Shared URL builder for Next Gen public feed endpoints.
 */
declare(strict_types=1);

require_once __DIR__ . '/mail/MailEnv.php';

final class NextGenPublicFeedUrl {
    public static function publicConcertsFeedUrl(): string {
        return self::buildAbsoluteUrl('public-concerts.json.php');
    }

    public static function publicConcertsFeedTokenizedUrl(string $token): string {
        $base = self::publicConcertsFeedUrl();
        $sep = strpos($base, '?') === false ? '?' : '&';
        return $base . $sep . http_build_query(['token' => $token]);
    }

    private static function buildAbsoluteUrl(string $scriptFile): string {
        $script = ltrim($scriptFile, '/');
        $base = trim(MailEnv::nextgenPublicBaseUrl());
        if ($base !== '') {
            return rtrim($base, '/') . '/api/' . $script;
        }

        $scheme = 'https';
        if (
            (isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '')
            || (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'https')
        ) {
            $scheme = 'https';
        } elseif (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'http') {
            $scheme = 'http';
        }

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $scriptName = trim((string) ($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'));
        $apiDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($apiDir === '' || $apiDir === '.') {
            $apiDir = '/api';
        }

        return $scheme . '://' . $host . $apiDir . '/' . $script;
    }
}
