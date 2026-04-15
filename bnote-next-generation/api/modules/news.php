<?php
/**
 * BNote Next Generation - News API Module
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
 * News API module
 * Get/save dashboard news (Nachrichten). Admin-only: requires Nachrichten module permission.
 *
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
require_once BNOTE_ROOT . "/src/data/modules/nachrichtendata.php";
require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../text_normalizer.php";

class NewsModule
{
  /** @var NachrichtenData */
  private $newsData;

  public function __construct()
  {
    global $system_data;
    $moduleId = getLegacyModuleId($system_data, LegacyModuleKey::NEWS);
    if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
      Response::error("Access denied to News", 403);
    }
    $this->newsData = new NachrichtenData($GLOBALS["dir_prefix"] ?? "");
  }

  public function handle()
  {
    $action = $_GET["action"] ?? ($_POST["action"] ?? "get");

    switch ($action) {
      case "get":
        return $this->normalizeResponse($this->get(), $action);
      case "save":
        return $this->normalizeResponse($this->save(), $action);
      default:
        Response::error("Unknown action: " . $action, 400);
    }
  }

  private function normalizeResponse($payload, $action)
  {
    $textFields = ["content"];
    $stats = ["count" => 0, "samples" => []];
    $normalized = TextNormalizer::normalizeFieldsRecursive($payload, $textFields, $stats, true);
    TextNormalizer::logStats("news", $action, $stats);
    return $normalized;
  }

  /** Return raw content for the editor (no preparedContent). */
  private function get()
  {
    $content = $this->newsData->fetchContent();
    return ["content" => $content !== false ? $content : ""];
  }

  /** Save content from POST body (JSON). */
  private function save()
  {
    $rawInput = file_get_contents("php://input");
    $body = is_string($rawInput) && $rawInput !== "" ? json_decode($rawInput, true) : null;
    if (!is_array($body) || !array_key_exists("content", $body)) {
      Response::error("Missing content", 400);
    }
    $content = $body["content"];
    if (!is_string($content)) {
      Response::error("Content must be a string", 400);
    }
    $this->newsData->storeContent($content);
    return ["ok" => true];
  }
}
