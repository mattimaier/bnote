<?php
/**
 * First-touch creation of persistent login token storage (Next Gen).
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

declare(strict_types=1);

final class PersistentLoginTokenSchema {
    private static bool $ensured = false;

    /**
     * @param object $db BNote Database instance
     */
    public static function ensureTable(object $db): bool {
        if (self::$ensured) {
            return true;
        }

        $mysqli = self::mysqliFromDatabase($db);
        if ($mysqli === null) {
            error_log('PersistentLoginTokenSchema: could not access mysqli from Database');
            return false;
        }

        $table = 'persistent_login_token';
        if ($res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'")) {
            if ($res->num_rows > 0) {
                $res->free();
                self::$ensured = true;
                return true;
            }
            $res->free();
        }

        $withFk = <<<SQL
CREATE TABLE IF NOT EXISTS `persistent_login_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime NULL DEFAULT NULL,
  `revoked_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plt_token_hash` (`token_hash`),
  KEY `plt_user_id` (`user_id`),
  KEY `plt_expires_at` (`expires_at`),
  CONSTRAINT `plt_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        $noFk = <<<SQL
CREATE TABLE IF NOT EXISTS `persistent_login_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime NULL DEFAULT NULL,
  `revoked_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plt_token_hash` (`token_hash`),
  KEY `plt_user_id` (`user_id`),
  KEY `plt_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        if ($mysqli->query($withFk)) {
            self::$ensured = true;
            return true;
        }

        error_log('PersistentLoginTokenSchema: CREATE with FK failed: ' . $mysqli->error);

        if ($mysqli->query($noFk)) {
            self::$ensured = true;
            return true;
        }

        error_log('PersistentLoginTokenSchema: CREATE without FK failed: ' . $mysqli->error);
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
}

