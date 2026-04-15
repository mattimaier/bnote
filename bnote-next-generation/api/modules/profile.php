<?php
/**
 * BNote Next Generation - Profile (My Contact Data) API Module
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
 * Profile API module
 * Provides endpoints for the current user's own contact data.
 * No Kontakte module permission required - every logged-in user can access their own data.
 */
require_once BNOTE_ROOT . "/src/logic/defaultcontroller.php";
require_once BNOTE_ROOT . "/src/data/modules/kontaktdatendata.php";
require_once BNOTE_ROOT . "/src/data/modules/gruppendata.php";
require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";

class ProfileModule
{
  private $data;
  private $groupData;

  public function __construct()
  {
    $this->data = new KontaktdatenData();
    $this->groupData = new GruppenData();
  }

  public function handle()
  {
    $action = $_GET["action"] ?? ($_POST["action"] ?? "getMine");

    switch ($action) {
      case "getMine":
        return $this->getMine();
      case "updateMine":
        return $this->updateMine();
      case "getUserPreferences":
        return $this->getUserPreferences();
      case "updateUserPreferences":
        return $this->updateUserPreferences();
      case "getInstruments":
        return $this->getInstruments();
      default:
        Response::error("Unknown action: " . $action, 400);
    }
  }

  /**
   * Persist user.email_notification (direct update on user table).
   */
  private function persistUserEmailNotification($uid, $enabled)
  {
    global $system_data;
    $emn = $enabled ? 1 : 0;
    $system_data->dbcon->execute("UPDATE user SET email_notification = ? WHERE id = ?", [["i", $emn], ["i", $uid]]);
  }

  /**
   * Get the current user's contact data.
   */
  private function getMine()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }

    $uid = $_SESSION["user"] ?? null;
    if (!$uid) {
      Response::error("Session invalid", 403);
    }

    global $system_data;

    $contact = $this->data->getContactForUser($uid);
    if (!$contact || !is_array($contact) || empty($contact["id"])) {
      return null;
    }

    $id = intval($contact["id"]);
    $groups = $this->data->getContactGroupsArray($id);

    return [
      "id" => $id,
      "name" => $contact["name"] ?? "",
      "surname" => $contact["surname"] ?? "",
      "nickname" => $contact["nickname"] ?? "",
      "company" => $contact["company"] ?? "",
      "phone" => $contact["phone"] ?? "",
      "mobile" => $contact["mobile"] ?? "",
      "business" => $contact["business"] ?? "",
      "email" => $contact["email"] ?? "",
      "web" => $contact["web"] ?? "",
      "notes" => $contact["notes"] ?? "",
      "instrument" => intval($contact["instrument"] ?? 0),
      "instrumentname" => $contact["instrumentname"] ?? "",
      "is_conductor" => intval($contact["is_conductor"] ?? 0) === 1,
      "birthday" => $contact["birthday"] ?? null,
      "status" => $contact["status"] ?? "",
      "address" => intval($contact["address"] ?? 0),
      "street" => $contact["street"] ?? "",
      "city" => $contact["city"] ?? "",
      "zip" => $contact["zip"] ?? "",
      "groups" => $groups,
      "share_address" => intval($contact["share_address"] ?? 0) === 1,
      "share_phones" => intval($contact["share_phones"] ?? 0) === 1,
      "share_birthday" => intval($contact["share_birthday"] ?? 0) === 1,
      "share_email" => intval($contact["share_email"] ?? 0) === 1,
      "email_notification" => $system_data->userEmailNotificationOn($uid),
    ];
  }

  /**
   * Update the current user's contact data.
   */
  private function updateMine()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }

    $uid = $_SESSION["user"] ?? null;
    if (!$uid) {
      Response::error("Session invalid", 403);
    }

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!$data) {
      $data = $_POST;
    }

    if (array_key_exists("email_notification", $data)) {
      $raw = $data["email_notification"];
      $on = $raw === true || $raw === 1 || $raw === "1" || $raw === "on";
      $this->persistUserEmailNotification($uid, $on);
      unset($data["email_notification"]);
    }

    $values = [];
    foreach (
      [
        "name",
        "surname",
        "nickname",
        "company",
        "phone",
        "mobile",
        "business",
        "email",
        "web",
        "notes",
        "instrument",
        "birthday",
        "status",
      ]
      as $field
    ) {
      if (isset($data[$field])) {
        $values[$field] = $data[$field];
      }
    }

    if (isset($data["street"]) || isset($data["city"]) || isset($data["zip"])) {
      $values["street"] = $data["street"] ?? "";
      $values["city"] = $data["city"] ?? "";
      $values["zip"] = $data["zip"] ?? "";
    }

    foreach (["share_address", "share_phones", "share_birthday", "share_email"] as $field) {
      if (isset($data[$field])) {
        $values[$field] = $data[$field] ? "on" : "";
      }
    }

    if (empty($values)) {
      return [
        "success" => true,
        "message" => "Preferences updated successfully",
      ];
    }

    $_POST = array_merge($_POST ?? [], $values);

    try {
      $this->data->update($uid, $values);
      return [
        "success" => true,
        "message" => "Contact updated successfully",
      ];
    } catch (BNoteError $e) {
      Response::error($e->getMessage(), 400);
    }
  }

  /**
   * User-level preferences (no contact record required).
   */
  private function getUserPreferences()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }
    $uid = $_SESSION["user"] ?? null;
    if (!$uid) {
      Response::error("Session invalid", 403);
    }
    global $system_data;
    return [
      "email_notification" => $system_data->userEmailNotificationOn($uid),
    ];
  }

  private function updateUserPreferences()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }
    $uid = $_SESSION["user"] ?? null;
    if (!$uid) {
      Response::error("Session invalid", 403);
    }
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!$data) {
      $data = $_POST;
    }
    if (!array_key_exists("email_notification", $data)) {
      Response::error("email_notification is required", 400);
    }
    $raw = $data["email_notification"];
    $on = $raw === true || $raw === 1 || $raw === "1" || $raw === "on";
    $this->persistUserEmailNotification($uid, $on);
    return [
      "success" => true,
      "message" => "Preferences updated successfully",
      "email_notification" => $on,
    ];
  }

  /**
   * Get list of instruments for dropdown.
   */
  private function getInstruments()
  {
    if (!Auth::check()) {
      Response::error("Authentication required", 403);
    }

    global $system_data;
    $db = $system_data->dbcon;
    $sel = $db->getSelection("SELECT id, name FROM instrument ORDER BY `rank`, name");
    $result = [];
    for ($i = 1; $i < count($sel); $i++) {
      $row = $sel[$i];
      $result[] = [
        "id" => intval($row["id"]),
        "name" => $row["name"] ?? "",
      ];
    }
    return $result;
  }
}
