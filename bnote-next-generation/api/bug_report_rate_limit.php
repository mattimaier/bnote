<?php
/**
 * File-based rate limiter for authenticated beta bug reports (per IP + user).
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU General Public License v3+
 */

class BugReportRateLimit {
    const WINDOW_SECONDS = 900; // 15 minutes
    const MAX_ATTEMPTS = 5;

    public static function consumeOr429(int $userId): void {
        self::checkOr429($userId);
        self::commit($userId);
    }

    public static function checkOr429(int $userId): void {
        $times = self::loadRecentTimes($userId);
        if (count($times) >= self::MAX_ATTEMPTS) {
            Response::error('bug_report_rate_limited', 429);
        }
    }

    public static function commit(int $userId): void {
        $times = self::loadRecentTimes($userId);
        $times[] = time();
        self::writeTimes($userId, $times);
    }

    /** @return list<int> */
    private static function loadRecentTimes(int $userId): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($ip === '') {
            $ip = 'unknown';
        }
        $keyRaw = $ip . '|' . strval($userId);
        $key = preg_replace('/[^a-f0-9]/i', '_', hash('sha256', $keyRaw));

        $path = self::storageDir() . '/bugreport_' . $key . '.json';
        $windowStart = time() - self::WINDOW_SECONDS;

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
        return $times;
    }

    /** @param list<int> $times */
    private static function writeTimes(int $userId, array $times): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($ip === '') {
            $ip = 'unknown';
        }
        $keyRaw = $ip . '|' . strval($userId);
        $key = preg_replace('/[^a-f0-9]/i', '_', hash('sha256', $keyRaw));
        $dir = self::storageDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $path = $dir . '/bugreport_' . $key . '.json';
        $payload = json_encode(['times' => $times]);
        $tmp = $path . '.tmp';
        $fp = @fopen($tmp, 'c+b');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, $payload);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
            @rename($tmp, $path);
        } else {
            @file_put_contents($path, $payload);
        }
    }

    private static function storageDir(): string {
        if (defined('BNOTE_ROOT')) {
            return BNOTE_ROOT . '/data/api_rate_limit';
        }
        return sys_get_temp_dir() . '/bnote_api_rate_limit';
    }
}
