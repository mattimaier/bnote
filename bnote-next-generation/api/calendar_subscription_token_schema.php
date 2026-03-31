<?php
/**
 * First-touch creation of calendar_subscription_token (Next Gen).
 */
declare(strict_types=1);

final class CalendarSubscriptionTokenSchema {
    private static bool $ensured = false;

    public static function ensureTable(object $db): bool {
        if (self::$ensured) {
            return true;
        }

        $mysqli = self::mysqliFromDatabase($db);
        if ($mysqli === null) {
            error_log('CalendarSubscriptionTokenSchema: could not access mysqli from Database');
            return false;
        }

        $table = 'calendar_subscription_token';
        if ($res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'")) {
            if ($res->num_rows > 0) {
                $res->free();
                self::ensureColumns($mysqli);
                self::$ensured = true;
                return true;
            }
            $res->free();
        }

        $withFk = <<<SQL
CREATE TABLE IF NOT EXISTS `calendar_subscription_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_plain` char(64) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cst_user_id` (`user_id`),
  UNIQUE KEY `cst_token_plain` (`token_plain`),
  UNIQUE KEY `cst_token_hash` (`token_hash`),
  CONSTRAINT `cst_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        $noFk = <<<SQL
CREATE TABLE IF NOT EXISTS `calendar_subscription_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_plain` char(64) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cst_user_id` (`user_id`),
  UNIQUE KEY `cst_token_plain` (`token_plain`),
  UNIQUE KEY `cst_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        if ($mysqli->query($withFk)) {
            self::ensureColumns($mysqli);
            self::$ensured = true;
            return true;
        }
        error_log('CalendarSubscriptionTokenSchema: CREATE with FK failed: ' . $mysqli->error);

        if ($mysqli->query($noFk)) {
            self::ensureColumns($mysqli);
            self::$ensured = true;
            return true;
        }
        error_log('CalendarSubscriptionTokenSchema: CREATE without FK failed: ' . $mysqli->error);
        return false;
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

    /**
     * Lightweight legacy self-healing for already existing tables.
     */
    private static function ensureColumns(mysqli $mysqli): void {
        $cols = [];
        if ($res = $mysqli->query("SHOW COLUMNS FROM `calendar_subscription_token`")) {
            while ($row = $res->fetch_assoc()) {
                $name = isset($row['Field']) ? (string) $row['Field'] : '';
                if ($name !== '') {
                    $cols[$name] = true;
                }
            }
            $res->free();
        }

        if (!isset($cols['token_plain'])) {
            $mysqli->query("ALTER TABLE `calendar_subscription_token` ADD COLUMN `token_plain` char(64) DEFAULT NULL AFTER `user_id`");
        }
        if (!isset($cols['token_hash'])) {
            $mysqli->query("ALTER TABLE `calendar_subscription_token` ADD COLUMN `token_hash` char(64) DEFAULT NULL AFTER `token_plain`");
        }

        self::ensureUniqueIndex($mysqli, 'cst_user_id', 'ALTER TABLE `calendar_subscription_token` ADD UNIQUE KEY `cst_user_id` (`user_id`)');
        self::ensureUniqueIndex($mysqli, 'cst_token_plain', 'ALTER TABLE `calendar_subscription_token` ADD UNIQUE KEY `cst_token_plain` (`token_plain`)');
        self::ensureUniqueIndex($mysqli, 'cst_token_hash', 'ALTER TABLE `calendar_subscription_token` ADD UNIQUE KEY `cst_token_hash` (`token_hash`)');
    }

    private static function ensureUniqueIndex(mysqli $mysqli, string $indexName, string $ddl): void {
        $exists = false;
        if ($res = $mysqli->query("SHOW INDEX FROM `calendar_subscription_token` WHERE Key_name = '" . $mysqli->real_escape_string($indexName) . "'")) {
            $exists = $res->num_rows > 0;
            $res->free();
        }
        if (!$exists) {
            $mysqli->query($ddl);
        }
    }
}

