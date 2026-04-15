<?php
/**
 * BNote Next Generation - Tasks API Module
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
 * Tasks API module
 * Provides task (Aufgaben) CRUD, completion, group tasks, tour links, and email notifications
 */
require_once BNOTE_ROOT . "/src/data/modules/aufgabendata.php";
require_once BNOTE_ROOT . "/src/data/modules/tourdata.php";
require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../text_normalizer.php";
require_once __DIR__ . "/../mail/NextGenMailPolicy.php";
require_once __DIR__ . "/../mail/NextGenMailer.php";
require_once __DIR__ . "/../mail/builders/TaskNotificationMailBuilder.php";

class TasksModule
{
  private $data;
  private $tourData;

  public function __construct()
  {
    global $system_data;
    $moduleId = getLegacyModuleId($system_data, LegacyModuleKey::TASKS);
    if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
      Response::error("Access denied to Tasks", 403);
    }

    $this->data = new AufgabenData();
    $this->tourData = new TourData();
  }

  public function handle()
  {
    $action = $_GET["action"] ?? ($_POST["action"] ?? "list");

    switch ($action) {
      case "list":
        return $this->normalizeResponse($this->listTasks(), $action);
      case "get":
        return $this->normalizeResponse($this->getTask(), $action);
      case "create":
        return $this->normalizeResponse($this->createTask(), $action);
      case "update":
        return $this->normalizeResponse($this->updateTask(), $action);
      case "delete":
        return $this->normalizeResponse($this->deleteTask(), $action);
      case "complete":
        return $this->normalizeResponse($this->completeTask(), $action);
      case "createGroupTasks":
        return $this->normalizeResponse($this->createGroupTasks(), $action);
      case "getContacts":
        return $this->normalizeResponse($this->getContacts(), $action);
      case "getGroups":
        return $this->normalizeResponse($this->getGroups(), $action);
      case "getTours":
        return $this->normalizeResponse($this->getTours(), $action);
      default:
        Response::error("Unknown action: " . $action, 400);
    }
  }

  private function normalizeResponse($payload, $action)
  {
    $textFields = ["title", "description", "assignee", "creator", "name", "instrument", "message"];
    $stats = ["count" => 0, "samples" => []];
    $normalized = TextNormalizer::normalizeFieldsRecursive($payload, $textFields, $stats, true);
    TextNormalizer::logStats("tasks", $action, $stats);
    return $normalized;
  }

  private function getUserId()
  {
    return Auth::getUserId();
  }

  private function getPayload()
  {
    $rawInput = file_get_contents("php://input");
    $data = $rawInput ? json_decode($rawInput, true) : null;
    return $data ?? $_POST;
  }

  /**
   * Map API assigned_to to the key AufgabenData expects
   */
  private function prepareValuesForCreate($values)
  {
    $key = Lang::txt("AufgabenView_add_editEntityForm.assigned_to");
    $values[$key] = $values["assigned_to"] ?? null;
    return $values;
  }

  private function prepareValuesForUpdate($values)
  {
    return $this->prepareValuesForCreate($values);
  }

  /**
   * Send Next Gen HTML mail to assignee. Skipped in demo / when mail not configured.
   */
  private function sendCreateNotification(int $assignedTo, string $title, string $description, int $taskId): void
  {
    global $system_data;
    if ($system_data->inDemoMode()) {
      return;
    }
    if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
      return;
    }
    if (!NextGenMailPolicy::contactAllowsTransactionalNotification($system_data, $assignedTo)) {
      return;
    }
    $to = $this->data->getContactmail($assignedTo);
    if (empty($to)) {
      return;
    }
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    try {
      $msg = TaskNotificationMailBuilder::build(
        $system_data,
        $locale,
        TaskNotificationMailBuilder::MODE_CREATE,
        $title,
        $description,
        $taskId,
        [$to],
        [],
      );
      NextGenMailer::send($msg);
    } catch (Throwable $e) {
      error_log("TasksModule: Failed to send create notification: " . $e->getMessage());
    }
  }

  private function sendUpdateNotification(int $assignedTo, string $title, string $description, int $taskId): void
  {
    global $system_data;
    if ($system_data->inDemoMode()) {
      return;
    }
    if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
      return;
    }
    if (!NextGenMailPolicy::contactAllowsTransactionalNotification($system_data, $assignedTo)) {
      return;
    }
    $to = $this->data->getContactmail($assignedTo);
    if (empty($to)) {
      return;
    }
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    try {
      $msg = TaskNotificationMailBuilder::build(
        $system_data,
        $locale,
        TaskNotificationMailBuilder::MODE_UPDATE,
        $title,
        $description,
        $taskId,
        [$to],
        [],
      );
      NextGenMailer::send($msg);
    } catch (Throwable $e) {
      error_log("TasksModule: Failed to send update notification: " . $e->getMessage());
    }
  }

  private function getTaskTitleById(int $id): string
  {
    $sel = $this->data->getTasks(false);
    if (!is_array($sel)) {
      return "";
    }
    for ($i = 1; $i < count($sel); $i++) {
      if ((int) ($sel[$i]["id"] ?? 0) === $id) {
        return trim((string) ($sel[$i]["title"] ?? ""));
      }
    }
    return "";
  }

  private function getTaskDescriptionById(int $id): string
  {
    $sel = $this->data->getTasks(false);
    if (!is_array($sel)) {
      return "";
    }
    for ($i = 1; $i < count($sel); $i++) {
      if ((int) ($sel[$i]["id"] ?? 0) === $id) {
        return (string) ($sel[$i]["description"] ?? "");
      }
    }
    return "";
  }

  private function listTasks()
  {
    $openOnly = !isset($_GET["open"]) || $_GET["open"] !== "0";
    $tourId = isset($_GET["tour_id"]) ? (int) $_GET["tour_id"] : null;

    if ($tourId > 0) {
      $sel = $this->tourData->getTasks($tourId, $openOnly);
    } else {
      $sel = $this->data->getTasks($openOnly);
    }

    $list = [];
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        $row = $sel[$i];
        $list[] = $this->formatTaskRow($row);
      }
    }
    return $list;
  }

  private function formatTaskRow($row)
  {
    $assignedTo = isset($row["assigned_to"]) ? (int) $row["assigned_to"] : null;
    $assigneeIdentity = $assignedTo ? $this->getContactIdentity($assignedTo) : null;
    return [
      "id" => (int) $row["id"],
      "title" => $row["title"] ?? "",
      "description" => $row["description"] ?? "",
      "created_at" => $row["created_at"] ?? null,
      "due_at" => $row["due_at"] ?? null,
      "is_complete" => !empty($row["is_complete"]),
      "completed_at" => $row["completed_at"] ?? null,
      "assigned_to" => $assignedTo,
      "assignee" => $row["assignee"] ?? null,
      "assigneeName" => $assigneeIdentity["name"] ?? ($row["assignee"] ?? null),
      "assigneeEmail" => $assigneeIdentity["email"] ?? null,
      "creator" => $row["creator"] ?? null,
    ];
  }

  private function getContactIdentity(int $contactId): ?array
  {
    if ($contactId < 1) {
      return null;
    }
    global $system_data;
    $row = $system_data->dbcon->fetchRow("SELECT name, surname, email FROM contact WHERE id = ?", [["i", $contactId]]);
    if (!is_array($row)) {
      return null;
    }
    $name = trim(($row["name"] ?? "") . " " . ($row["surname"] ?? ""));
    return [
      "name" => $name !== "" ? $name : null,
      "email" => $row["email"] ?? null,
    ];
  }

  private function getTask()
  {
    $id = $_GET["id"] ?? ($_POST["id"] ?? null);
    if (!$id) {
      Response::error("Missing id", 400);
    }
    $id = (int) $id;

    $sel = $this->data->getTasks(false);
    $task = null;
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        if ((int) $sel[$i]["id"] === $id) {
          $task = $sel[$i];
          break;
        }
      }
    }
    if (!$task) {
      $sel = $this->data->getTasks(true);
      if (is_array($sel)) {
        for ($i = 1; $i < count($sel); $i++) {
          if ((int) $sel[$i]["id"] === $id) {
            $task = $sel[$i];
            break;
          }
        }
      }
    }
    if (!$task) {
      Response::error("Task not found", 404);
    }

    $result = $this->formatTaskRow($task);

    // Add tour IDs if linked
    global $system_data;
    $tourTaskSel = $system_data->dbcon->getSelection("SELECT tour FROM tour_task WHERE task = ?", [["i", $id]]);
    $tourIds = [];
    if (is_array($tourTaskSel)) {
      for ($j = 1; $j < count($tourTaskSel); $j++) {
        $tourIds[] = (int) $tourTaskSel[$j]["tour"];
      }
    }
    $result["tourIds"] = $tourIds;

    return $result;
  }

  private function createTask()
  {
    $data = $this->getPayload();
    $title = trim($data["title"] ?? "");
    if ($title === "") {
      Response::error("Title is required", 400);
    }

    $values = [
      "title" => $title,
      "description" => $data["description"] ?? "",
      "due_at" => $data["due_at"] ?? null,
      "assigned_to" => isset($data["assigned_to"]) ? (int) $data["assigned_to"] : null,
    ];
    $values = $this->prepareValuesForCreate($values);

    $taskId = $this->data->create($values);
    if (!$taskId) {
      Response::error("Failed to create task", 500);
    }

    $tourId = isset($data["tour_id"]) ? (int) $data["tour_id"] : null;
    if ($tourId > 0) {
      $this->tourData->addReference($tourId, "task", $taskId);
    }

    $assignedTo = $values["assigned_to"] ?? null;
    if ($assignedTo) {
      $this->sendCreateNotification((int) $assignedTo, $title, (string) ($values["description"] ?? ""), (int) $taskId);
    }

    return ["id" => (int) $taskId, "success" => true];
  }

  private function updateTask()
  {
    $data = $this->getPayload();
    $id = $data["id"] ?? ($_GET["id"] ?? null);
    if (!$id) {
      Response::error("Missing id", 400);
    }
    $id = (int) $id;

    $values = [
      "title" => isset($data["title"]) ? trim($data["title"]) : null,
      "description" => $data["description"] ?? null,
      "due_at" => $data["due_at"] ?? null,
      "assigned_to" => isset($data["assigned_to"]) ? (int) $data["assigned_to"] : null,
    ];
    $values = array_filter($values, function ($v) {
      return $v !== null;
    });
    if (empty($values)) {
      Response::error("No fields to update", 400);
    }

    $values = $this->prepareValuesForUpdate($values);
    $this->data->update($id, $values);

    $assignedTo = isset($values["assigned_to"]) ? (int) $values["assigned_to"] : null;
    if ($assignedTo) {
      $title = isset($values["title"]) ? trim((string) $values["title"]) : "";
      if ($title === "") {
        $title = $this->getTaskTitleById($id);
      }
      if ($title !== "") {
        $desc = isset($values["description"]) ? (string) $values["description"] : $this->getTaskDescriptionById($id);
        $this->sendUpdateNotification($assignedTo, $title, $desc, $id);
      }
    }

    return ["success" => true];
  }

  private function deleteTask()
  {
    $id = $_GET["id"] ?? ($_POST["id"] ?? null);
    if (!$id) {
      Response::error("Missing id", 400);
    }
    $id = (int) $id;

    global $system_data;
    $system_data->dbcon->execute("DELETE FROM tour_task WHERE task = ?", [["i", $id]]);
    $this->data->delete($id);

    return ["success" => true];
  }

  private function completeTask()
  {
    $data = $this->getPayload();
    $id = $data["id"] ?? ($_GET["id"] ?? null);
    $complete = isset($data["complete"])
      ? (bool) $data["complete"]
      : isset($_GET["complete"]) && $_GET["complete"] === "1";
    if (!$id) {
      Response::error("Missing id", 400);
    }
    $id = (int) $id;

    $this->data->markTask($id, $complete ? 1 : 0);

    return ["success" => true, "is_complete" => $complete];
  }

  private function createGroupTasks()
  {
    $data = $this->getPayload();
    $groupIds = $data["groupIds"] ?? ($data["group_ids"] ?? []);
    if (!is_array($groupIds)) {
      $groupIds = [];
    }
    $groupIds = array_map("intval", array_filter($groupIds));
    if (empty($groupIds)) {
      Response::error("At least one group is required", 400);
    }

    $title = trim($data["title"] ?? "");
    if ($title === "") {
      Response::error("Title is required", 400);
    }

    $description = $data["description"] ?? "";
    $dueAt = $data["due_at"] ?? null;
    $key = Lang::txt("AufgabenView_add_editEntityForm.assigned_to");

    $adp = $this->data->adp();
    $created = 0;

    foreach ($groupIds as $gid) {
      $contacts = $adp->getGroupContacts($gid);
      if (!is_array($contacts)) {
        continue;
      }
      for ($j = 1; $j < count($contacts); $j++) {
        $contactId = (int) ($contacts[$j]["id"] ?? 0);
        if ($contactId <= 0) {
          continue;
        }

        $values = [
          "title" => $title,
          "description" => $description,
          "due_at" => $dueAt,
          "assigned_to" => $contactId,
        ];
        $values[$key] = $contactId;
        $this->data->create($values);
        $created++;

        $this->sendCreateNotification($contactId, $title, $description);
      }
    }

    return ["success" => true, "created" => $created];
  }

  private function getContacts()
  {
    $adp = $this->data->adp();
    $sel = $adp->getContacts();
    $list = [];
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        $c = $sel[$i];
        $list[] = [
          "id" => (int) $c["id"],
          "name" => trim(($c["name"] ?? "") . " " . ($c["surname"] ?? "")),
          "email" => $c["email"] ?? null,
          "instrument" => $c["instrumentname"] ?? null,
        ];
      }
    }
    return $list;
  }

  private function getGroups()
  {
    $adp = $this->data->adp();
    $sel = $adp->getGroups();
    $list = [];
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        $g = $sel[$i];
        $list[] = [
          "id" => (int) $g["id"],
          "name" => $g["name"] ?? "",
        ];
      }
    }
    return $list;
  }

  private function getTours()
  {
    $sel = $this->data->adp()->getTours();
    $list = [];
    if (is_array($sel)) {
      for ($i = 1; $i < count($sel); $i++) {
        $t = $sel[$i];
        $list[] = [
          "id" => (int) $t["id"],
          "name" => $t["name"] ?? "",
        ];
      }
    }
    return $list;
  }
}
