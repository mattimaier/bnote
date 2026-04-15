<?php
/**
 * BNote Next Generation - Lazy module provisioning helpers
 *
 * Mirrors legacy update_db addModule behavior:
 * - Create module when missing
 * - Grant module to all configured super users
 */

class ModuleProvisioning
{
  private const NEXTGEN_ONLY_CATEGORY = "nextgen";

  /**
   * Ensure a module exists and return its ID.
   *
   * @return int Module ID (0 on failure)
   */
  public static function ensureModuleExists(
    string $name,
    string $icon,
    string $category,
    bool $migrateExistingCategory = false,
  ): int {
    global $system_data;
    if (!isset($system_data)) {
      return 0;
    }

    $moduleId = intval($system_data->getModuleId($name));
    if ($moduleId > 0) {
      if ($migrateExistingCategory) {
        self::migrateModuleCategory($moduleId, $category);
      }
      return $moduleId;
    }

    try {
      $system_data->dbcon->execute("INSERT INTO module (name, icon, category) VALUES (?, ?, ?)", [
        ["s", $name],
        ["s", $icon],
        ["s", $category],
      ]);
    } catch (Exception $e) {
      // Ignore insert race/duplicate style errors and continue with lookup below.
    }

    $moduleId = intval($system_data->getModuleId($name));
    if ($moduleId <= 0) {
      return 0;
    }

    if ($migrateExistingCategory) {
      self::migrateModuleCategory($moduleId, $category);
    }
    self::grantModuleToSuperUsers($moduleId);
    return $moduleId;
  }

  /**
   * Ensure a Next Generation only module exists and is hidden from legacy
   * navigation by assigning it to a dedicated category.
   */
  public static function ensureNextGenOnlyModuleExists(string $name, string $icon): int
  {
    return self::ensureModuleExists($name, $icon, self::NEXTGEN_ONLY_CATEGORY, true);
  }

  private static function migrateModuleCategory(int $moduleId, string $category): void
  {
    global $system_data;
    if ($moduleId <= 0 || !isset($system_data)) {
      return;
    }

    $currentCategory = (string) $system_data->dbcon->colValue(
      "SELECT category FROM module WHERE id = ? LIMIT 1",
      "category",
      [["i", $moduleId]],
    );
    if (trim($currentCategory) === trim($category)) {
      return;
    }

    try {
      $system_data->dbcon->execute("UPDATE module SET category = ? WHERE id = ?", [["s", $category], ["i", $moduleId]]);
    } catch (Exception $e) {
      // Ignore DB errors and keep runtime behavior unchanged.
    }
  }

  private static function grantModuleToSuperUsers(int $moduleId): void
  {
    global $system_data;
    if ($moduleId <= 0 || !isset($system_data)) {
      return;
    }

    $superUsers = $system_data->getSuperUsers();
    if (!is_array($superUsers) || count($superUsers) === 0) {
      return;
    }

    foreach ($superUsers as $uid) {
      $userId = intval($uid);
      if ($userId <= 0) {
        continue;
      }
      $exists = $system_data->dbcon->colValue(
        "SELECT 1 as x FROM privilege WHERE user = ? AND module = ? LIMIT 1",
        "x",
        [["i", $userId], ["i", $moduleId]],
      );
      if (intval($exists) === 1) {
        continue;
      }
      try {
        $system_data->dbcon->execute("INSERT INTO privilege (user, module) VALUES (?, ?)", [
          ["i", $userId],
          ["i", $moduleId],
        ]);
      } catch (Exception $e) {
        // Ignore duplicates/races.
      }
    }
  }
}
