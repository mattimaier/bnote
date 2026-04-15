<?php
/**
 * BNote Next Generation - Outfits API Module
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
 * Outfits API module
 * Provides outfit (costume/uniform) management endpoints
 */
require_once BNOTE_ROOT . "/src/data/modules/outfitsdata.php";
require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";

class OutfitsModule
{
  private $data;

  public function __construct()
  {
    global $system_data;
    $moduleId = getLegacyModuleId($system_data, LegacyModuleKey::OUTFITS);
    if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
      Response::error("Access denied to Outfits", 403);
    }

    $this->data = new OutfitsData();
  }

  public function handle()
  {
    $action = $_GET["action"] ?? ($_POST["action"] ?? "list");

    switch ($action) {
      case "list":
        return $this->listOutfits();
      case "get":
        return $this->getOutfit();
      case "create":
        return $this->createOutfit();
      case "update":
        return $this->updateOutfit();
      case "delete":
        return $this->deleteOutfit();
      default:
        Response::error("Unknown action: " . $action, 400);
    }
  }

  /**
   * List all outfits
   */
  private function listOutfits()
  {
    $selection = $this->data->findAllNoRef();
    $result = [];
    if (is_array($selection)) {
      for ($i = 1; $i < count($selection); $i++) {
        $row = $selection[$i];
        $result[] = [
          "id" => intval($row["id"]),
          "name" => $row["name"] ?? "",
          "description" => $row["description"] ?? "",
        ];
      }
    }
    usort($result, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    return $result;
  }

  /**
   * Get single outfit by ID
   */
  private function getOutfit()
  {
    $id = $_GET["id"] ?? ($_POST["id"] ?? null);
    if (!$id || !is_numeric($id)) {
      Response::error("Outfit ID required", 400);
    }

    $row = $this->data->findByIdNoRef($id);
    if (!$row || empty($row)) {
      Response::error("Outfit not found", 404);
    }

    return [
      "id" => intval($row["id"]),
      "name" => $row["name"] ?? "",
      "description" => $row["description"] ?? "",
    ];
  }

  /**
   * Create new outfit
   */
  private function createOutfit()
  {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!$data) {
      $data = $_POST;
    }

    $values = [
      "name" => $data["name"] ?? "",
      "description" => $data["description"] ?? "",
    ];

    try {
      $id = $this->data->create($values);
      return [
        "success" => true,
        "id" => intval($id),
        "message" => "Outfit created successfully",
      ];
    } catch (BNoteError $e) {
      Response::error($e->getMessage(), 400);
    }
  }

  /**
   * Update outfit
   */
  private function updateOutfit()
  {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!$data) {
      $data = $_POST;
    }

    $id = $data["id"] ?? ($_GET["id"] ?? null);
    if (!$id || !is_numeric($id)) {
      Response::error("Outfit ID required", 400);
    }

    $values = [];
    foreach (["name", "description"] as $field) {
      if (array_key_exists($field, $data)) {
        $values[$field] = $data[$field];
      }
    }

    try {
      $this->data->update($id, $values);
      return [
        "success" => true,
        "message" => "Outfit updated successfully",
      ];
    } catch (BNoteError $e) {
      Response::error($e->getMessage(), 400);
    }
  }

  /**
   * Delete outfit
   */
  private function deleteOutfit()
  {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!$data) {
      $data = $_POST;
    }

    $id = $data["id"] ?? ($_GET["id"] ?? null);
    if (!$id || !is_numeric($id)) {
      Response::error("Outfit ID required", 400);
    }

    try {
      $this->data->delete($id);
      return [
        "success" => true,
        "message" => "Outfit deleted successfully",
      ];
    } catch (BNoteError $e) {
      Response::error($e->getMessage(), 400);
    }
  }
}
