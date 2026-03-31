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
            <<<SQL
CREATE TABLE IF NOT EXISTS `nextgen_escalation_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_test` tinyint(1) NOT NULL DEFAULT 0,
  `trigger_kind` varchar(32) NOT NULL DEFAULT 'scheduled',
  `delivery_mode` varchar(32) NOT NULL DEFAULT 'scheduled',
  `otype` char(1) NOT NULL DEFAULT '',
  `oid` int(10) unsigned NOT NULL DEFAULT 0,
  `event_title` varchar(255) NOT NULL DEFAULT '',
  `reason_summary` varchar(255) NOT NULL DEFAULT '',
  `payload_json` mediumtext NULL,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_event` (`otype`,`oid`)
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

    /**
     * @param array<string,mixed> $payload
     */
    public static function addEscalationAudit(object $db, array $payload): bool {
        $isTest = !empty($payload['is_test']) ? 1 : 0;
        $triggerKind = substr(trim((string) ($payload['trigger_kind'] ?? 'scheduled')), 0, 32);
        $deliveryMode = substr(trim((string) ($payload['delivery_mode'] ?? 'scheduled')), 0, 32);
        $otype = strtoupper(substr(trim((string) ($payload['otype'] ?? '')), 0, 1));
        $oid = max(0, (int) ($payload['oid'] ?? 0));
        $eventTitle = substr(trim((string) ($payload['event_title'] ?? '')), 0, 255);
        $reasonSummary = substr(trim((string) ($payload['reason_summary'] ?? '')), 0, 255);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            $json = '{}';
        }
        try {
            $db->execute(
                'INSERT INTO nextgen_escalation_audit
                    (is_test, trigger_kind, delivery_mode, otype, oid, event_title, reason_summary, payload_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    ['i', $isTest],
                    ['s', $triggerKind !== '' ? $triggerKind : 'scheduled'],
                    ['s', $deliveryMode !== '' ? $deliveryMode : 'scheduled'],
                    ['s', $otype],
                    ['i', $oid],
                    ['s', $eventTitle],
                    ['s', $reasonSummary],
                    ['s', $json],
                ]
            );
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function listEscalationAudit(object $db, int $limit = 50): array {
        $limit = max(1, min(200, $limit));
        $sel = $db->getSelection(
            'SELECT id, created_at, is_test, trigger_kind, delivery_mode, otype, oid, event_title, reason_summary, payload_json
             FROM nextgen_escalation_audit
             ORDER BY id DESC
             LIMIT ?',
            [['i', $limit]]
        );
        if (!is_array($sel) || count($sel) < 2) {
            return [];
        }
        $out = [];
        for ($i = 1; $i < count($sel); $i++) {
            $row = $sel[$i];
            $payloadRaw = (string) ($row['payload_json'] ?? '');
            $payload = json_decode($payloadRaw, true);
            if (!is_array($payload)) {
                $payload = null;
            }
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'is_test' => ((int) ($row['is_test'] ?? 0) === 1),
                'trigger_kind' => (string) ($row['trigger_kind'] ?? ''),
                'delivery_mode' => (string) ($row['delivery_mode'] ?? ''),
                'otype' => (string) ($row['otype'] ?? ''),
                'oid' => (int) ($row['oid'] ?? 0),
                'event_title' => (string) ($row['event_title'] ?? ''),
                'reason_summary' => (string) ($row['reason_summary'] ?? ''),
                'payload' => $payload,
            ];
        }
        return $out;
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
