<?php
/**
 * First-touch schema and helpers for Next Gen stats audit data.
 */
declare(strict_types=1);

final class NextGenStatsAuditSchema {
    private static bool $ensured = false;

    public static function ensureTables(object $db): bool {
        if (self::$ensured) {
            return true;
        }

        $mysqli = self::mysqliFromDatabase($db);
        if ($mysqli === null) {
            error_log('NextGenStatsAuditSchema: could not access mysqli from Database');
            return false;
        }

        $statements = [
            <<<SQL
CREATE TABLE IF NOT EXISTS `nextgen_mail_delivery_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `template_id` varchar(80) NOT NULL DEFAULT '',
  `module` varchar(40) NOT NULL DEFAULT '',
  `to_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `bcc_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `recipient_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_template` (`template_id`),
  KEY `idx_module` (`module`),
  KEY `idx_success_created` (`success`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS `nextgen_participation_token_apply_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event_type` char(1) NOT NULL DEFAULT '',
  `event_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned DEFAULT NULL,
  `contact_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_event` (`event_type`,`event_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_contact` (`contact_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL,
        ];

        foreach ($statements as $sql) {
            if (!$mysqli->query($sql)) {
                error_log('NextGenStatsAuditSchema: CREATE failed: ' . $mysqli->error);
                return false;
            }
        }

        self::$ensured = true;
        return true;
    }

    public static function logMailDelivery(
        object $db,
        string $templateId,
        string $module,
        int $toCount,
        int $bccCount,
        bool $success
    ): bool {
        if (!self::ensureTables($db)) {
            return false;
        }

        $toCount = max(0, min(65535, $toCount));
        $bccCount = max(0, min(65535, $bccCount));
        $recipientCount = min(65535, $toCount + $bccCount);
        $templateId = substr(trim($templateId), 0, 80);
        $module = substr(trim($module), 0, 40);

        try {
            $db->execute(
                'INSERT INTO nextgen_mail_delivery_audit
                    (template_id, module, to_count, bcc_count, recipient_count, success)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    ['s', $templateId],
                    ['s', $module],
                    ['i', $toCount],
                    ['i', $bccCount],
                    ['i', $recipientCount],
                    ['i', $success ? 1 : 0],
                ]
            );
            return true;
        } catch (Throwable $e) {
            error_log('NextGenStatsAuditSchema::logMailDelivery failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function logParticipationTokenApply(
        object $db,
        string $eventType,
        int $eventId,
        int $userId,
        int $contactId,
        string $status
    ): bool {
        if (!self::ensureTables($db)) {
            return false;
        }

        $eventType = strtoupper(substr(trim($eventType), 0, 1));
        if ($eventType !== 'R' && $eventType !== 'C') {
            return false;
        }

        $eventId = max(0, $eventId);
        $userId = $userId > 0 ? $userId : 0;
        $contactId = $contactId > 0 ? $contactId : 0;
        $status = strtolower(substr(trim($status), 0, 16));

        try {
            $db->execute(
                'INSERT INTO nextgen_participation_token_apply_audit
                    (event_type, event_id, user_id, contact_id, status)
                 VALUES (?, ?, ?, ?, ?)',
                [
                    ['s', $eventType],
                    ['i', $eventId],
                    ['i', $userId],
                    ['i', $contactId],
                    ['s', $status],
                ]
            );
            return true;
        } catch (Throwable $e) {
            error_log('NextGenStatsAuditSchema::logParticipationTokenApply failed: ' . $e->getMessage());
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
