<?php
/**
 * First-touch schema for reminder settings, nonce replay protection, and per-user run idempotency.
 */
declare(strict_types=1);

final class ReminderSchema {
    private static bool $ensured = false;

    public static function ensureTables(object $db): bool {
        if (self::$ensured) {
            return true;
        }

        $mysqli = self::mysqliFromDatabase($db);
        if ($mysqli === null) {
            error_log('ReminderSchema: could not access mysqli from Database');
            return false;
        }

        $statements = [
            <<<SQL
CREATE TABLE IF NOT EXISTS `nextgen_reminder_config` (
  `config_key` varchar(80) NOT NULL,
  `config_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS `nextgen_reminder_nonce` (
  `nonce_hash` char(64) NOT NULL,
  `seen_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`nonce_hash`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS `nextgen_reminder_run_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_key` varchar(32) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `delivery_mode` varchar(20) NOT NULL DEFAULT 'scheduled',
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_run_user` (`run_key`,`user_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL,
        ];

        foreach ($statements as $sql) {
            if (!$mysqli->query($sql)) {
                error_log('ReminderSchema: CREATE failed: ' . $mysqli->error);
                return false;
            }
        }

        self::$ensured = true;
        return true;
    }

    public static function purgeExpiredNonces(object $db): void {
        $db->execute('DELETE FROM nextgen_reminder_nonce WHERE expires_at < UTC_TIMESTAMP()', []);
    }

    public static function countRecentNonceRequests(object $db, int $seconds): int {
        $seconds = max(1, min(3600, $seconds));
        $sel = $db->getSelection(
            'SELECT COUNT(*) AS cnt FROM nextgen_reminder_nonce WHERE seen_at >= (UTC_TIMESTAMP() - INTERVAL ? SECOND)',
            [['i', $seconds]]
        );
        if (!is_array($sel) || !isset($sel[1]['cnt'])) {
            return 0;
        }
        return (int) $sel[1]['cnt'];
    }

    public static function rememberNonce(object $db, string $nonceHash, int $ttlSeconds): bool {
        $ttlSeconds = max(60, min(3600, $ttlSeconds));
        try {
            $db->execute(
                'INSERT INTO nextgen_reminder_nonce (nonce_hash, expires_at) VALUES (?, (UTC_TIMESTAMP() + INTERVAL ? SECOND))',
                [['s', $nonceHash], ['i', $ttlSeconds]]
            );
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function hasRunForUser(object $db, string $runKey, int $userId): bool {
        $sel = $db->getSelection(
            'SELECT id FROM nextgen_reminder_run_log WHERE run_key = ? AND user_id = ? LIMIT 1',
            [['s', $runKey], ['i', $userId]]
        );
        return is_array($sel) && count($sel) > 1;
    }

    public static function markRunForUser(object $db, string $runKey, int $userId, string $mode): bool {
        $mode = trim($mode) === '' ? 'scheduled' : substr(trim($mode), 0, 20);
        try {
            $db->execute(
                'INSERT INTO nextgen_reminder_run_log (run_key, user_id, delivery_mode) VALUES (?, ?, ?)',
                [['s', $runKey], ['i', $userId], ['s', $mode]]
            );
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function mysqliFromDatabase(object $db): ?mysqli {
        try {
            $ref = new ReflectionClass($db);
            if (!$ref->hasProperty('db')) {
                return null;
            }
            $p = $ref->getProperty('db');
            $p->setAccessible(true);
            $m = $p->getValue($db);
            return $m instanceof mysqli ? $m : null;
        } catch (ReflectionException $e) {
            return null;
        }
    }
}
