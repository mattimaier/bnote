<?php
/**
 * First-touch creation of public_concerts_feed_token (Next Gen).
 */
declare(strict_types=1);

final class PublicConcertsFeedTokenSchema
{
  private static bool $ensured = false;

  public static function ensureTable(object $db): bool
  {
    if (self::$ensured) {
      return true;
    }

    $mysqli = self::mysqliFromDatabase($db);
    if ($mysqli === null) {
      error_log("PublicConcertsFeedTokenSchema: could not access mysqli from Database");
      return false;
    }

    $table = "public_concerts_feed_token";
    if ($res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'")) {
      if ($res->num_rows > 0) {
        $res->free();
        self::ensureColumns($mysqli);
        self::$ensured = true;
        return true;
      }
      $res->free();
    }

    $ddl = <<<SQL
    CREATE TABLE IF NOT EXISTS `public_concerts_feed_token` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `scope_key` varchar(32) NOT NULL,
      `token_plain` char(64) NOT NULL,
      `token_hash` char(64) NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `pcft_scope_key` (`scope_key`),
      UNIQUE KEY `pcft_token_plain` (`token_plain`),
      UNIQUE KEY `pcft_token_hash` (`token_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    SQL;

    if (!$mysqli->query($ddl)) {
      error_log("PublicConcertsFeedTokenSchema: CREATE failed: " . $mysqli->error);
      return false;
    }

    self::ensureColumns($mysqli);
    self::$ensured = true;
    return true;
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

  private static function ensureColumns(mysqli $mysqli): void
  {
    $cols = [];
    if ($res = $mysqli->query("SHOW COLUMNS FROM `public_concerts_feed_token`")) {
      while ($row = $res->fetch_assoc()) {
        $name = isset($row["Field"]) ? (string) $row["Field"] : "";
        if ($name !== "") {
          $cols[$name] = true;
        }
      }
      $res->free();
    }

    if (!isset($cols["scope_key"])) {
      $mysqli->query(
        "ALTER TABLE `public_concerts_feed_token` ADD COLUMN `scope_key` varchar(32) DEFAULT 'global' AFTER `id`",
      );
    }
    if (!isset($cols["token_plain"])) {
      $mysqli->query(
        "ALTER TABLE `public_concerts_feed_token` ADD COLUMN `token_plain` char(64) DEFAULT NULL AFTER `scope_key`",
      );
    }
    if (!isset($cols["token_hash"])) {
      $mysqli->query(
        "ALTER TABLE `public_concerts_feed_token` ADD COLUMN `token_hash` char(64) DEFAULT NULL AFTER `token_plain`",
      );
    }

    self::ensureUniqueIndex(
      $mysqli,
      "pcft_scope_key",
      "ALTER TABLE `public_concerts_feed_token` ADD UNIQUE KEY `pcft_scope_key` (`scope_key`)",
    );
    self::ensureUniqueIndex(
      $mysqli,
      "pcft_token_plain",
      "ALTER TABLE `public_concerts_feed_token` ADD UNIQUE KEY `pcft_token_plain` (`token_plain`)",
    );
    self::ensureUniqueIndex(
      $mysqli,
      "pcft_token_hash",
      "ALTER TABLE `public_concerts_feed_token` ADD UNIQUE KEY `pcft_token_hash` (`token_hash`)",
    );
  }

  private static function ensureUniqueIndex(mysqli $mysqli, string $indexName, string $ddl): void
  {
    $exists = false;
    if (
      $res = $mysqli->query(
        "SHOW INDEX FROM `public_concerts_feed_token` WHERE Key_name = '" .
          $mysqli->real_escape_string($indexName) .
          "'",
      )
    ) {
      $exists = $res->num_rows > 0;
      $res->free();
    }
    if (!$exists) {
      $mysqli->query($ddl);
    }
  }
}
