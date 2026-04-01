<?php
/**
 * BNote Next Generation - Lazy module provisioning helpers
 *
 * Mirrors legacy update_db addModule behavior:
 * - Create module when missing
 * - Grant module to all configured super users
 */

class ModuleProvisioning {
    /**
     * Ensure a module exists and return its ID.
     *
     * @return int Module ID (0 on failure)
     */
    public static function ensureModuleExists(string $name, string $icon, string $category): int {
        global $system_data;
        if (!isset($system_data)) {
            return 0;
        }

        $moduleId = intval($system_data->getModuleId($name));
        if ($moduleId > 0) {
            return $moduleId;
        }

        try {
            $system_data->dbcon->execute(
                "INSERT INTO module (name, icon, category) VALUES (?, ?, ?)",
                [['s', $name], ['s', $icon], ['s', $category]]
            );
        } catch (Exception $e) {
            // Ignore insert race/duplicate style errors and continue with lookup below.
        }

        $moduleId = intval($system_data->getModuleId($name));
        if ($moduleId <= 0) {
            return 0;
        }

        self::grantModuleToSuperUsers($moduleId);
        return $moduleId;
    }

    private static function grantModuleToSuperUsers(int $moduleId): void {
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
                [['i', $userId], ['i', $moduleId]]
            );
            if (intval($exists) === 1) {
                continue;
            }
            try {
                $system_data->dbcon->execute(
                    "INSERT INTO privilege (user, module) VALUES (?, ?)",
                    [['i', $userId], ['i', $moduleId]]
                );
            } catch (Exception $e) {
                // Ignore duplicates/races.
            }
        }
    }
}
