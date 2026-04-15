<?php
/**
 * First-touch schema for Next Gen public event visibility state.
 */
declare(strict_types=1);

final class NextGenPublicEventVisibilitySchema
{
  private static bool $ensured = false;

  public static function ensureTable(object $db): bool
  {
    if (self::$ensured) {
      return true;
    }

    $mysqli = self::mysqliFromDatabase($db);
    if ($mysqli === null) {
      error_log("NextGenPublicEventVisibilitySchema: could not access mysqli from Database");
      return false;
    }

    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS `nextgen_public_event_visibility` (
      `otype` char(1) NOT NULL,
      `oid` int(10) unsigned NOT NULL,
      `is_published` tinyint(1) NOT NULL DEFAULT 0,
      `published_at` datetime DEFAULT NULL,
      `published_by` int(10) unsigned DEFAULT NULL,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`otype`,`oid`),
      KEY `idx_is_published` (`is_published`),
      KEY `idx_updated_at` (`updated_at`),
      KEY `idx_published_by` (`published_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    SQL;

    if (!$mysqli->query($sql)) {
      error_log("NextGenPublicEventVisibilitySchema: CREATE failed: " . $mysqli->error);
      return false;
    }

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
}
