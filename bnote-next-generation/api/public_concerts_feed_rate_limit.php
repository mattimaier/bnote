<?php
/**
 * File-based rate limiter for public concerts feed access (per IP).
 */
declare(strict_types=1);

final class PublicConcertsFeedRateLimit {
    public const WINDOW_SECONDS = 900;
    public const MAX_ATTEMPTS = 120;

    public static function consume(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($ip === '') {
            $ip = 'unknown';
        }
        $key = preg_replace('/[^a-f0-9]/i', '_', hash('sha256', $ip));

        $dir = self::storageDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $path = $dir . '/public_concerts_feed_' . $key . '.json';

        $now = time();
        $windowStart = $now - self::WINDOW_SECONDS;

        $times = [];
        if (is_readable($path)) {
            $raw = @file_get_contents($path);
            $decoded = json_decode($raw ?: '', true);
            if (is_array($decoded) && isset($decoded['times']) && is_array($decoded['times'])) {
                foreach ($decoded['times'] as $t) {
                    $t = (int) $t;
                    if ($t >= $windowStart) {
                        $times[] = $t;
                    }
                }
            }
        }

        if (count($times) >= self::MAX_ATTEMPTS) {
            return false;
        }

        $times[] = $now;
        $payload = json_encode(['times' => $times]);
        $tmp = $path . '.tmp';
        $fp = @fopen($tmp, 'c+b');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, (string) $payload);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
            @rename($tmp, $path);
        } else {
            @file_put_contents($path, (string) $payload);
        }

        return true;
    }

    private static function storageDir(): string {
        if (defined('BNOTE_ROOT')) {
            return BNOTE_ROOT . '/data/api_rate_limit';
        }
        return sys_get_temp_dir() . '/bnote_api_rate_limit';
    }
}

