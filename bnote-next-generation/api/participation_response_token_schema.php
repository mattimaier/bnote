<?php
/**
 * First-touch creation of participation_response_token (Next Gen). No separate migration run required.
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

declare(strict_types=1);

final class ParticipationResponseTokenSchema
{
  private static bool $ensured = false;

  /**
   * Ensures participation_response_token exists. Tries CREATE with FK; on failure retries without FK.
   *
   * @param object $db BNote Database instance
   */
  public static function ensureTable(object $db): bool
  {
    if (self::$ensured) {
      return true;
    }

    $mysqli = self::mysqliFromDatabase($db);
    if ($mysqli === null) {
      error_log("ParticipationResponseTokenSchema: could not access mysqli from Database");
      return false;
    }

    $table = "participation_response_token";
    if ($res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'")) {
      if ($res->num_rows > 0) {
        $res->free();
        self::$ensured = true;
        return true;
      }
      $res->free();
    }

    $withFk = <<<SQL
    CREATE TABLE IF NOT EXISTS `participation_response_token` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `token_hash` char(64) NOT NULL,
      `event_type` char(1) NOT NULL,
      `event_id` int(10) unsigned NOT NULL,
      `user_id` int(10) unsigned DEFAULT NULL,
      `contact_id` int(10) unsigned DEFAULT NULL,
      `expires_at` datetime NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `token_hash` (`token_hash`),
      KEY `evt_user` (`event_type`, `event_id`, `user_id`),
      KEY `evt_contact` (`event_type`, `event_id`, `contact_id`),
      CONSTRAINT `prt_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
      CONSTRAINT `prt_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    SQL;

    $noFk = <<<SQL
    CREATE TABLE IF NOT EXISTS `participation_response_token` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `token_hash` char(64) NOT NULL,
      `event_type` char(1) NOT NULL,
      `event_id` int(10) unsigned NOT NULL,
      `user_id` int(10) unsigned DEFAULT NULL,
      `contact_id` int(10) unsigned DEFAULT NULL,
      `expires_at` datetime NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `token_hash` (`token_hash`),
      KEY `evt_user` (`event_type`, `event_id`, `user_id`),
      KEY `evt_contact` (`event_type`, `event_id`, `contact_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    SQL;

    if ($mysqli->query($withFk)) {
      self::$ensured = true;
      return true;
    }

    error_log("ParticipationResponseTokenSchema: CREATE with FK failed: " . $mysqli->error);

    if ($mysqli->query($noFk)) {
      self::$ensured = true;
      return true;
    }

    error_log("ParticipationResponseTokenSchema: CREATE without FK failed: " . $mysqli->error);
    return false;
  }

  private static function mysqliFromDatabase(object $db): ?mysqli
  {
    try {
      $ref = new ReflectionClass($db);
      if (!$ref->hasProperty("db")) {
        return null;
      }
      $p = $ref->getProperty("db");
      $p->setAccessible(true);
      $m = $p->getValue($db);
      return $m instanceof mysqli ? $m : null;
    } catch (ReflectionException $e) {
      return null;
    }
  }
}
