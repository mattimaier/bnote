<?php
/**
 * BNote Next Generation - Authentication Helpers
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Authentication and permission helpers for BNote API
 * Wraps existing SystemData authentication methods
 */
class Auth
{
  /**
   * Check if user is authenticated
   * @return bool True if authenticated, false otherwise
   */
  public static function check()
  {
    global $system_data;
    return $system_data->isUserAuthenticated();
  }

  /**
   * Get current user ID
   * @return int|null User ID or null if not authenticated
   */
  public static function getUserId()
  {
    global $system_data;
    return $system_data->getUserId();
  }

  /**
   * Check if user has permission for a module
   * @param string $moduleName Module name (e.g., 'Proben', 'Kontakte')
   * @return bool True if user has permission, false otherwise
   */
  public static function checkModule($moduleName)
  {
    global $system_data;
    $moduleId = $system_data->getModuleId($moduleName);
    if (!$moduleId) {
      return false;
    }
    return $system_data->userHasPermission($moduleId);
  }

  /**
   * Get current user information
   * @return array User data including ID, name, permissions
   */
  public static function getUserInfo()
  {
    global $system_data;
    if (!self::check()) {
      return null;
    }

    $userId = self::getUserId();
    $contact = $system_data->getUsersContact($userId);
    $permissions = $system_data->getUserModulePermissions($userId);

    return [
      "id" => $userId,
      "name" => $contact["name"] ?? "",
      "surname" => $contact["surname"] ?? "",
      "email" => $contact["email"] ?? "",
      "permissions" => $permissions,
    ];
  }
}
