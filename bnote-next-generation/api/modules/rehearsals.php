<?php
/**
 * BNote Next Generation - Rehearsals API Module
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
 * Rehearsals API module
 * Handles rehearsal detail endpoints with participants grouped by instruments
 *
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
// Use BNOTE_ROOT constant from paths.php (loaded by api/index.php)
require_once BNOTE_ROOT . "/src/data/modules/probendata.php";
require_once BNOTE_ROOT . "/src/data/modules/startdata.php";
require_once BNOTE_ROOT . "/src/data/modules/locationsdata.php";
require_once BNOTE_ROOT . "/src/data/modules/gruppendata.php";
require_once BNOTE_ROOT . "/src/data/modules/repertoiredata.php";
require_once BNOTE_ROOT . "/src/data/database.php";
require_once __DIR__ . "/../response.php";
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../mail/EventParticipantNotifier.php";
require_once __DIR__ . "/../mail/EventInfoMailService.php";
require_once __DIR__ . "/../mail/EscalationAlertService.php";
require_once __DIR__ . "/../text_normalizer.php";

class RehearsalsModule
{
  private $data;

  public function __construct()
  {
    // Check authentication
    if (!Auth::check()) {
      Response::error("Authentication required", 401);
    }

    // Module permission is not required for read access (GET rehearsal).
    // Write actions (getMeta, update) check permission in handle().
    $this->data = new ProbenData();
  }

  public function handle()
  {
    $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
    $action = $_GET["action"] ?? ($_POST["action"] ?? null);
    $id = $_GET["id"] ?? null;

    // If no action specified but ID is provided, treat as GET request
    if ($method === "GET" && $id && ($action === null || $action === "")) {
      return $this->normalizeResponse($this->getRehearsal($id), "get");
    }

    if ($action === "list") {
      return $this->normalizeResponse($this->listRehearsals(), $action);
    }

    if ($action === "meta") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->getMeta(), $action);
    }

    if ($action === "update") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->updateRehearsal(), $action);
    }

    if ($action === "create") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->createRehearsal(), $action);
    }

    if ($action === "delete") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->deleteRehearsal(), $action);
    }

    if ($action === "create_series") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->createSeries(), $action);
    }

    if ($action === "emailInfoDraft") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->emailInfoDraft(), $action);
    }

    if ($action === "emailInfoPreview") {
      $this->requireRehearsalsModulePermission();
      return $this->emailInfoPreview();
    }

    if ($action === "emailInfoSend") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->emailInfoSend(), $action);
    }

    if ($action === "acceptEscalationRisk") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->acceptEscalationRisk(), $action);
    }

    if ($action === "resetEscalationRisk") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->resetEscalationRisk(), $action);
    }

    if ($action === "list_series") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->listSeries(), $action);
    }

    if ($action === "get_series") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->getSeries(), $action);
    }

    if ($action === "update_series") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->updateSeries(), $action);
    }

    if ($action === "list_by_series") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->listRehearsalsBySeries(), $action);
    }

    if ($action === "delete_series") {
      $this->requireRehearsalsModulePermission();
      return $this->normalizeResponse($this->deleteSeries(), $action);
    }

    // Handle explicit actions if needed in the future
    if ($action) {
      Response::error("Unknown action: " . $action, 400);
    }

    Response::error("Method not supported or missing ID", 400);
  }

  private function normalizeResponse($payload, $action)
  {
    $stats = ["count" => 0, "samples" => []];
    $normalized = TextNormalizer::normalizeAllStringsRecursive($payload, $stats, true);
    TextNormalizer::logStats("rehearsals", $action, $stats);
    return $normalized;
  }

  /**
   * List rehearsals the current user can see (future only, access-controlled).
   */
  private function listRehearsals()
  {
    $userId = Auth::getUserId();
    $canManageWarnings = $this->canManageRehearsalParticipation($userId);
    $rehearsals = $this->getAccessibleRehearsals($userId);
    $list = [];
    if (is_array($rehearsals)) {
      for ($i = 1; $i < count($rehearsals); $i++) {
        $r = $rehearsals[$i];
        $participationStats = $this->getParticipationStatsForRehearsal($r["id"] ?? null);
        $warning = $canManageWarnings ? $this->getEscalationWarningForEvent("R", intval($r["id"] ?? 0)) : null;
        $list[] = [
          "id" => intval($r["id"]),
          "begin" => $r["begin"] ?? "",
          "end" => $r["end"] ?? "",
          "approve_until" => $r["approve_until"] ?? "",
          "location_name" => $r["location_name"] ?? "",
          "notes" => $r["notes"] ?? "",
          "status" => $r["status"] ?? "",
          "conductor" => isset($r["conductor"]) ? intval($r["conductor"]) : null,
          "participationStats" => $participationStats,
          "escalationWarning" => $warning,
        ];
      }
    }
    return $list;
  }

  private function getAccessibleRehearsals($userId)
  {
    global $system_data;
    $uid = intval($userId);
    $moduleId = getLegacyModuleId($system_data, LegacyModuleKey::REHEARSALS);
    $hasRehearsalsModule = $moduleId ? $system_data->userHasPermission($moduleId) : false;
    if ($system_data->isUserSuperUser($uid) || $hasRehearsalsModule) {
      $query = "SELECT r.id, r.begin, r.end, r.approve_until, r.conductor, r.notes, r.status, l.name as location_name
                      FROM rehearsal r
                      JOIN location l ON r.location = l.id
                      ORDER BY r.begin DESC";
      return $system_data->dbcon->getSelection($query);
    }

    $startData = new StartData();
    $usersPhases = $startData->adp()->getUsersPhases($uid);
    $rehearsalIds = array_merge($this->getRehearsalsForUser($uid), $this->getRehearsalsForPhases($usersPhases));
    $rehearsalIds = array_map("intval", array_unique($rehearsalIds));
    if (count($rehearsalIds) === 0) {
      return [];
    }

    $placeholders = implode(",", array_fill(0, count($rehearsalIds), "?"));
    $params = array_map(fn($id) => ["i", $id], $rehearsalIds);
    $query = "SELECT r.id, r.begin, r.end, r.approve_until, r.conductor, r.notes, r.status, l.name as location_name
                  FROM rehearsal r
                  JOIN location l ON r.location = l.id
                  WHERE r.id IN ($placeholders)
                  ORDER BY r.begin DESC";
    return $system_data->dbcon->getSelection($query, $params);
  }

  private function getParticipationStatsForRehearsal($rehearsalId)
  {
    global $system_data;
    if (!$rehearsalId || !is_numeric($rehearsalId)) {
      return [
        "yes" => 0,
        "maybe" => 0,
        "no" => 0,
        "pending" => 0,
        "total" => 0,
      ];
    }
    $rid = intval($rehearsalId);
    $query = "SELECT 
                    SUM(CASE WHEN ru.participate = 1 THEN 1 ELSE 0 END) as yes,
                    SUM(CASE WHEN ru.participate = 2 THEN 1 ELSE 0 END) as maybe,
                    SUM(CASE WHEN ru.participate = 0 THEN 1 ELSE 0 END) as no,
                    SUM(CASE WHEN ru.participate IS NULL OR ru.participate < 0 THEN 1 ELSE 0 END) as pending
                  FROM rehearsal_contact rc
                  JOIN contact ct ON rc.contact = ct.id
                  JOIN user u ON u.contact = ct.id
                  LEFT JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
                  WHERE rc.rehearsal = ?";
    $rows = $system_data->dbcon->getSelection($query, [["i", $rid], ["i", $rid]]);
    $row = is_array($rows) && isset($rows[1]) ? $rows[1] : null;
    $yes = isset($row["yes"]) ? intval($row["yes"]) : 0;
    $maybe = isset($row["maybe"]) ? intval($row["maybe"]) : 0;
    $no = isset($row["no"]) ? intval($row["no"]) : 0;
    $pending = isset($row["pending"]) ? intval($row["pending"]) : 0;
    $total = $yes + $maybe + $no + $pending;
    return [
      "yes" => $yes,
      "maybe" => $maybe,
      "no" => $no,
      "pending" => $pending,
      "total" => $total,
    ];
  }

  /**
   * @return array<string,int>
   */
  private function loadInstrumentMinimums()
  {
    global $system_data;
    $raw = (string) ($system_data->getDynamicConfigParameter("instrument_minimums") ?? "");
    if ($raw === "") {
      return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return [];
    }
    if (isset($decoded["rehearsal"]) || isset($decoded["concert"])) {
      $decoded = is_array($decoded["rehearsal"] ?? null) ? $decoded["rehearsal"] : [];
    }
    $out = [];
    foreach ($decoded as $instrumentId => $minimum) {
      $key = trim((string) $instrumentId);
      $min = is_numeric($minimum) ? max(0, (int) $minimum) : 0;
      if ($min < 1 || $key === "") {
        continue;
      }
      if (is_numeric($key) && (int) $key > 0) {
        $out[(string) ((int) $key)] = $min;
        continue;
      }
    }
    return $out;
  }

  /**
   * @return array<string,array{id:string,name:string}>
   */
  private function loadInstrumentSectionsByInstrument()
  {
    global $system_data;
    if ((string) ($system_data->getDynamicConfigParameter("beta_section_coverage_enabled") ?? "") !== "1") {
      return [];
    }
    $sectionsRaw = (string) ($system_data->getDynamicConfigParameter("nextgen_instrument_sections") ?? "");
    if ($sectionsRaw === "") {
      return [];
    }
    $sectionsDecoded = json_decode($sectionsRaw, true);
    if (!is_array($sectionsDecoded)) {
      return [];
    }
    $map = [];
    foreach ($sectionsDecoded as $section) {
      if (!is_array($section)) {
        continue;
      }
      $sectionId = trim((string) ($section["id"] ?? ""));
      $sectionName = trim((string) ($section["name"] ?? ""));
      if ($sectionId === "" || $sectionName === "") {
        continue;
      }
      $instrumentIds = [];
      $rawInstrumentIds =
        isset($section["instrument_ids"]) && is_array($section["instrument_ids"]) ? $section["instrument_ids"] : [];
      foreach ($rawInstrumentIds as $rawId) {
        $instrumentId = (int) $rawId;
        if ($instrumentId > 0) {
          $instrumentIds[] = $instrumentId;
        }
      }
      foreach ($section["concert_instrument_targets"] ?? [] as $target) {
        if (!is_array($target)) {
          continue;
        }
        $targetInstrumentId = (int) ($target["instrument_id"] ?? 0);
        if ($targetInstrumentId > 0) {
          $instrumentIds[] = $targetInstrumentId;
        }
      }
      $instrumentIds = array_values(array_unique($instrumentIds));
      foreach ($instrumentIds as $instrumentId) {
        $key = (string) $instrumentId;
        if (!isset($map[$key])) {
          $map[$key] = [
            "id" => $sectionId,
            "name" => $sectionName,
          ];
        }
      }
    }
    return $map;
  }

  /**
   * Require Rehearsals (Proben) module permission for write operations.
   * Users without the module still have read access to rehearsals they are allowed to see.
   */
  private function requireRehearsalsModulePermission()
  {
    $userId = Auth::getUserId();
    if (!$this->canManageRehearsalParticipation($userId)) {
      Response::error("Access denied to Rehearsals", 403);
    }
  }

  private function hasRehearsalsModulePermission()
  {
    global $system_data;
    $moduleId = getLegacyModuleId($system_data, LegacyModuleKey::REHEARSALS);
    return $moduleId ? $system_data->userHasPermission($moduleId) : false;
  }

  private function canManageRehearsalParticipation($userId)
  {
    global $system_data;
    $uid = intval($userId);
    return $system_data->isUserSuperUser($uid) ||
      $system_data->isUserMemberGroup(1, $uid) ||
      $this->hasRehearsalsModulePermission();
  }

  private function isUserInvitedToRehearsal($rehearsalId, $userId)
  {
    global $system_data;
    $rid = intval($rehearsalId);
    $uid = intval($userId);
    if ($rid <= 0 || $uid <= 0) {
      return false;
    }

    $count = $system_data->dbcon->colValue(
      "SELECT COUNT(*) AS cnt
             FROM rehearsal_contact rc
             JOIN user u ON u.contact = rc.contact
             WHERE rc.rehearsal = ? AND u.id = ?",
      "cnt",
      [["i", $rid], ["i", $uid]],
    );
    return intval($count) > 0;
  }

  /**
   * Get single rehearsal with full details including participants grouped by instruments
   * GET /api/index.php?module=rehearsals&id={id}
   */
  private function getRehearsal($id)
  {
    global $system_data;

    // Validate ID
    if (!is_numeric($id)) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $id = intval($id);

    // Get rehearsal data
    $rehearsal = $this->data->findByIdNoRef($id);
    if (!$rehearsal) {
      Response::error("Rehearsal not found", 404);
    }

    // Check user access (similar to dashboard logic)
    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }

    $canEdit = $this->canManageRehearsalParticipation($userId);
    $canEditParticipation = $this->canManageRehearsalParticipation($userId);

    // Get location with address
    $location = null;
    if ($rehearsal["location"]) {
      $locData = new LocationsData();
      $loc = $locData->findByIdNoRef($rehearsal["location"]);
      if ($loc) {
        $address = $locData->getAddress($loc["address"]);
        $location = [
          "id" => intval($loc["id"]),
          "name" => $loc["name"],
          "address" => [
            "street" => $address["street"] ?? null,
            "city" => $address["city"] ?? null,
            "zip" => $address["zip"] ?? null,
            "state" => $address["state"] ?? null,
            "country" => $address["country"] ?? null,
          ],
        ];
      }
    }

    // Get conductor
    $conductor = null;
    if ($rehearsal["conductor"] && $rehearsal["conductor"] > 0) {
      $conductorName = $this->data->adp()->getConductorname($rehearsal["conductor"]);
      $conductor = [
        "id" => intval($rehearsal["conductor"]),
        "name" => $conductorName,
      ];
    }

    // Get songs/pieces to practice
    $songs = $this->data->getSongsForRehearsal($id);
    unset($songs[0]); // Remove header
    $songsToPractice = [];
    foreach ($songs as $song) {
      $songsToPractice[] = [
        "id" => intval($song["id"]),
        "title" => $song["title"],
        "notes" => $song["notes"] ?? null,
      ];
    }

    // Get groups
    $groups = $this->data->getRehearsalGroups($id);
    unset($groups[0]); // Remove header
    $groupList = [];
    foreach ($groups as $group) {
      $groupList[] = [
        "id" => intval($group["id"]),
        "name" => $group["name"] ?? null,
      ];
    }

    // Get event contacts
    $eventContacts = [];
    $contacts = $this->data->getRehearsalContacts($id);
    unset($contacts[0]); // Remove header
    foreach ($contacts as $contact) {
      $eventContacts[] = [
        "id" => intval($contact["id"]),
        "name" => $contact["name"] ?? null,
      ];
    }

    // Get all instruments used with category information
    global $system_data;
    $query = "SELECT DISTINCT i.id, i.name, i.rank, i.category as category_id, c.name as category_name 
                  FROM instrument i 
                  JOIN contact ct ON ct.instrument = i.id
                  JOIN rehearsal_contact rc ON rc.contact = ct.id
                  LEFT JOIN category c ON i.category = c.id
                  WHERE rc.rehearsal = ?
                  ORDER BY i.rank, i.name";
    $usedInstruments = $system_data->dbcon->getSelection($query, [["i", $id]]);
    unset($usedInstruments[0]); // Remove header

    // Group participants by instrument
    $participantsByInstrument = [];
    $totalStats = ["yes" => 0, "maybe" => 0, "no" => 0, "pending" => 0];
    $instrumentMinimums = $this->loadInstrumentMinimums();
    $instrumentSections = $this->loadInstrumentSectionsByInstrument();

    foreach ($usedInstruments as $instrument) {
      $instrumentId = $instrument["id"];

      // Custom query to get participants with reason and category info
      $partQuery = "SELECT i.id as instrument_id, i.name as instrument, i.category as category_id, c.name as category_name,
                         ct.id as contact_id, CONCAT(ct.name, ' ', ct.surname) as contactname, ct.email as contact_email,
                         u.id as user_id, IFNULL(ru.participate, -1) as participate, ru.reason
                         FROM rehearsal_contact rc
                         JOIN contact ct ON rc.contact = ct.id
                         JOIN user u ON u.contact = ct.id
                         JOIN instrument i ON ct.instrument = i.id
                         LEFT JOIN category c ON i.category = c.id
                         LEFT OUTER JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
                         WHERE rc.rehearsal = ? AND ct.instrument = ?
                         ORDER BY instrument, contactname";
      $participants = $system_data->dbcon->getSelection($partQuery, [["i", $id], ["i", $id], ["i", $instrumentId]]);
      unset($participants[0]); // Remove header

      if (count($participants) > 0) {
        $instrumentParticipants = [];
        $instrumentStats = ["yes" => 0, "maybe" => 0, "no" => 0, "pending" => 0];

        foreach ($participants as $participant) {
          $participate = $participant["participate"];
          if ($participate === null || $participate === "" || $participate < 0) {
            $participate = null; // Pending
            $instrumentStats["pending"]++;
            $totalStats["pending"]++;
          } else {
            $participate = intval($participate);
            if ($participate === 1) {
              $instrumentStats["yes"]++;
              $totalStats["yes"]++;
            } elseif ($participate === 2) {
              $instrumentStats["maybe"]++;
              $totalStats["maybe"]++;
            } else {
              $instrumentStats["no"]++;
              $totalStats["no"]++;
            }
          }

          $instrumentParticipants[] = [
            "id" => intval($participant["contact_id"]),
            "userId" => intval($participant["user_id"]),
            "name" => $participant["contactname"],
            "email" => $participant["contact_email"] ?? null,
            "participate" => $participate,
            "reason" => $participant["reason"] ?? null,
          ];
        }

        $participantsByInstrument[] = [
          "instrument" => [
            "id" => intval($instrumentId),
            "name" => $instrument["name"],
            "minimumRequired" => $instrumentMinimums[(string) intval($instrumentId)] ?? 0,
            "section" => $instrumentSections[(string) intval($instrumentId)] ?? null,
            "category" => [
              "id" => intval($instrument["category_id"] ?? 0),
              "name" => $instrument["category_name"] ?? "Uncategorized",
            ],
          ],
          "participants" => $instrumentParticipants,
          "stats" => $instrumentStats,
        ];
      }
    }

    // Format response
    $response = [
      "id" => intval($rehearsal["id"]),
      "type" => "R",
      "begin" => $rehearsal["begin"],
      "end" => $rehearsal["end"] ?? null,
      "approve_until" => $rehearsal["approve_until"] ?? null,
      "status" => $rehearsal["status"] ?? "planned",
      "notes" => $rehearsal["notes"] ?? null,
      "location" => $location,
      "conductor" => $conductor,
      "songsToPractice" => $songsToPractice,
      "groups" => $groupList,
      "eventContacts" => $eventContacts,
      "canEdit" => $canEdit,
      "canEditParticipation" => $canEditParticipation,
      "participantsByInstrument" => $participantsByInstrument,
      "participationStats" => [
        "yes" => $totalStats["yes"],
        "maybe" => $totalStats["maybe"],
        "no" => $totalStats["no"],
        "pending" => $totalStats["pending"],
        "total" => $totalStats["yes"] + $totalStats["maybe"] + $totalStats["no"] + $totalStats["pending"],
      ],
      "escalationWarning" => $canEdit ? $this->getEscalationWarningForEvent("R", $id) : null,
    ];

    return $response;
  }

  /**
   * @return null|array<string,mixed>
   */
  private function getEscalationWarningForEvent(string $otype, int $oid)
  {
    global $system_data;
    if ($oid < 1) {
      return null;
    }
    try {
      return EscalationAlertService::getWarningForEvent($system_data, $otype, $oid);
    } catch (Throwable $e) {
      error_log("rehearsal escalation warning failed: " . $e->getMessage());
      return null;
    }
  }

  /**
   * @return array<string,mixed>
   */
  private function acceptEscalationRisk(): array
  {
    global $system_data;
    $payload = $this->getRequestData();
    $id = (int) ($payload["id"] ?? 0);
    if ($id < 1) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }
    $user = Auth::getUserInfo();
    $acceptedByName = trim((string) (($user["name"] ?? "") . " " . ($user["surname"] ?? "")));
    if ($acceptedByName === "") {
      $acceptedByName = (string) ($user["username"] ?? "#" . (string) $userId);
    }
    return EscalationAlertService::acceptRiskForEvent($system_data, "R", $id, (int) $userId, $acceptedByName);
  }

  /**
   * @return array<string,mixed>
   */
  private function resetEscalationRisk(): array
  {
    global $system_data;
    $payload = $this->getRequestData();
    $id = (int) ($payload["id"] ?? 0);
    if ($id < 1) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }
    return EscalationAlertService::resetRiskAcceptanceForEvent($system_data, "R", $id);
  }

  private function getMeta()
  {
    global $system_data;

    $locationsData = new LocationsData();
    $groupData = new GruppenData();
    $repertoireData = new RepertoireData();

    $locationsSel = $locationsData->findAllNoRef();
    $groupsSel = $groupData->findAllNoRef();
    $songsSel = $repertoireData->findAllNoRef();
    $conductorsSel = $this->data->adp()->getConductors();
    $contactsSel = $this->data->getContacts();

    $locations = [];
    for ($i = 1; $i < count($locationsSel); $i++) {
      $locations[] = [
        "id" => intval($locationsSel[$i]["id"]),
        "name" => $locationsSel[$i]["name"] ?? null,
      ];
    }

    $groups = [];
    for ($i = 1; $i < count($groupsSel); $i++) {
      $groups[] = [
        "id" => intval($groupsSel[$i]["id"]),
        "name" => $groupsSel[$i]["name"] ?? null,
      ];
    }

    $songs = [];
    for ($i = 1; $i < count($songsSel); $i++) {
      $songs[] = [
        "id" => intval($songsSel[$i]["id"]),
        "title" => urldecode($songsSel[$i]["title"] ?? ""),
      ];
    }

    $conductors = [];
    for ($i = 1; $i < count($conductorsSel); $i++) {
      $c = $conductorsSel[$i];
      $instrumentName = null;
      $instrumentId = $c["instrument"] ?? null;
      if ($instrumentId && $instrumentId > 0) {
        $instrumentName = $system_data->dbcon->colValue("SELECT name FROM instrument WHERE id = ?", "name", [
          ["i", $instrumentId],
        ]);
      }
      $conductors[] = [
        "id" => intval($c["id"]),
        "name" => trim(($c["name"] ?? "") . " " . ($c["surname"] ?? "")),
        "email" => $c["email"] ?? null,
        "instrument" => $instrumentName,
      ];
    }

    $contacts = [];
    for ($i = 1; $i < count($contactsSel); $i++) {
      $c = $contactsSel[$i];
      $instrumentName = null;
      $instrumentId = $c["instrument"] ?? null;
      if ($instrumentId && $instrumentId > 0) {
        $instrumentName = $system_data->dbcon->colValue("SELECT name FROM instrument WHERE id = ?", "name", [
          ["i", $instrumentId],
        ]);
      }
      $contacts[] = [
        "id" => intval($c["id"]),
        "name" => $c["fullname"] ?? trim(($c["name"] ?? "") . " " . ($c["surname"] ?? "")),
        "subtitle" => $instrumentName,
        "email" => $c["email"] ?? null,
        "instrument" => $instrumentName,
      ];
    }

    $groupMembers = [];
    $groupMembersSel = $system_data->dbcon->getSelection(
      "SELECT `group` as group_id, contact as contact_id FROM contact_group",
      [],
    );
    unset($groupMembersSel[0]);
    foreach ($groupMembersSel as $row) {
      $groupId = intval($row["group_id"] ?? 0);
      $contactId = intval($row["contact_id"] ?? 0);
      if ($groupId <= 0 || $contactId <= 0) {
        continue;
      }
      if (!array_key_exists(strval($groupId), $groupMembers)) {
        $groupMembers[strval($groupId)] = [];
      }
      $groupMembers[strval($groupId)][] = $contactId;
    }

    return [
      "locations" => $locations,
      "groups" => $groups,
      "songs" => $songs,
      "conductors" => $conductors,
      "contacts" => $contacts,
      "statusOptions" => $this->data->getStatusOptions(),
      "groupMembers" => $groupMembers,
      "defaultDurationMinutes" => intval($this->data->getDefaultDuration()),
      "defaultStartTime" => strval($this->data->getDefaultTime()),
      "defaultConductorId" => intval($system_data->getDynamicConfigParameter("default_conductor")),
    ];
  }

  private function updateRehearsal()
  {
    global $system_data;

    $payload = $this->getRequestData();
    $id = $payload["id"] ?? null;
    if (!$id || !is_numeric($id)) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $id = intval($id);
    $before = $this->data->findByIdNoRef($id);
    $beforeStatus = trim((string) ($before["status"] ?? ""));

    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }

    $fields = $payload["fields"] ?? [];
    $values = [
      "begin" => $fields["begin"] ?? "",
      "end" => $fields["end"] ?? "",
      "approve_until" => $fields["approve_until"] ?? "",
      "status" => $fields["status"] ?? "planned",
      "notes" => $fields["notes"] ?? "",
      "location" => $fields["location"] ?? 0,
      "conductor" => $fields["conductor"] ?? 0,
    ];

    if (empty($values["approve_until"]) && !empty($values["begin"])) {
      $values["approve_until"] = $values["begin"];
    }

    // Legacy ProbenData::validate uses Regex::isText() which rejects " and \ (EditorJS JSON).
    // Validate with notes cleared, then restore so update() stores the real value.
    $notesBackup = $values["notes"];
    $values["notes"] = "";
    $this->data->validate($values);
    $values["notes"] = $notesBackup;
    $this->data->update($id, $values);
    $afterStatus = trim((string) ($values["status"] ?? ""));
    if ($beforeStatus !== "" && $afterStatus !== "" && $beforeStatus !== $afterStatus) {
      try {
        EscalationAlertService::resetRiskAcceptanceForEvent($system_data, "R", $id);
      } catch (Throwable $e) {
        error_log("RehearsalsModule escalation acceptance reset hook failed: " . $e->getMessage());
      }
    }

    if (array_key_exists("groups", $payload)) {
      $groups = array_map("intval", $payload["groups"] ?? []);
      $system_data->dbcon->execute("DELETE FROM rehearsal_group WHERE rehearsal = ?", [["i", $id]]);
      if (count($groups) > 0) {
        $tuples = [];
        $params = [];
        foreach ($groups as $groupId) {
          $tuples[] = "(?, ?)";
          $params[] = ["i", $id];
          $params[] = ["i", $groupId];
        }
        $query = "INSERT INTO rehearsal_group (rehearsal, `group`) VALUES " . join(",", $tuples);
        $system_data->dbcon->execute($query, $params);
      }
    }

    if (array_key_exists("contacts", $payload)) {
      $previousContactIds = $this->rehearsalContactIds($id);
      $contacts = array_map("intval", $payload["contacts"] ?? []);
      $system_data->dbcon->execute("DELETE FROM rehearsal_contact WHERE rehearsal = ?", [["i", $id]]);
      if (count($contacts) > 0) {
        $tuples = [];
        $params = [];
        foreach ($contacts as $contactId) {
          $tuples[] = "(?, ?)";
          $params[] = ["i", $id];
          $params[] = ["i", $contactId];
        }
        $query = "INSERT INTO rehearsal_contact VALUES " . join(",", $tuples);
        $system_data->dbcon->execute($query, $params);
      }

      if (count($contacts) > 0) {
        $placeholders = implode(",", array_fill(0, count($contacts), "?"));
        $params = [["i", $id]];
        foreach ($contacts as $contactId) {
          $params[] = ["i", $contactId];
        }
        $query = "DELETE ru FROM rehearsal_user ru JOIN user u ON ru.user = u.id WHERE ru.rehearsal = ? AND u.contact NOT IN ($placeholders)";
        $system_data->dbcon->execute($query, $params);
      } else {
        $system_data->dbcon->execute("DELETE FROM rehearsal_user WHERE rehearsal = ?", [["i", $id]]);
      }

      $addedContacts = array_values(array_diff($contacts, $previousContactIds));
      if (count($addedContacts) > 0) {
        EventParticipantNotifier::sendSafe($system_data, new StartData(), "R", $id, $addedContacts);
      }
    }

    if (array_key_exists("songs", $payload)) {
      $songs = $payload["songs"] ?? [];
      $system_data->dbcon->execute("DELETE FROM rehearsal_song WHERE rehearsal = ?", [["i", $id]]);
      if (count($songs) > 0) {
        $tuples = [];
        $params = [];
        foreach ($songs as $song) {
          $songId = intval($song["id"] ?? 0);
          if ($songId <= 0) {
            continue;
          }
          $tuples[] = "(?, ?, ?)";
          $params[] = ["i", $songId];
          $params[] = ["i", $id];
          $params[] = ["s", $song["notes"] ?? ""];
        }
        if (count($tuples) > 0) {
          $query = "INSERT INTO rehearsal_song (song, rehearsal, notes) VALUES " . join(",", $tuples);
          $system_data->dbcon->execute($query, $params);
        }
      }
    }

    if (array_key_exists("participants", $payload)) {
      $participants = $payload["participants"] ?? [];
      foreach ($participants as $participant) {
        $userId = intval($participant["userId"] ?? 0);
        if ($userId <= 0) {
          continue;
        }
        if (!$this->isUserInvitedToRehearsal($id, $userId)) {
          continue;
        }
        $participate = $participant["participate"] ?? null;
        if ($participate === null || $participate === "") {
          $system_data->dbcon->execute("DELETE FROM rehearsal_user WHERE rehearsal = ? AND user = ?", [
            ["i", $id],
            ["i", $userId],
          ]);
          continue;
        }
        $participate = intval($participate);
        $exists = $system_data->dbcon->colValue(
          "SELECT count(*) as cnt FROM rehearsal_user WHERE rehearsal = ? AND user = ?",
          "cnt",
          [["i", $id], ["i", $userId]],
        );
        if (intval($exists) > 0) {
          $system_data->dbcon->execute("UPDATE rehearsal_user SET participate = ? WHERE rehearsal = ? AND user = ?", [
            ["i", $participate],
            ["i", $id],
            ["i", $userId],
          ]);
        } else {
          $system_data->dbcon->execute(
            "INSERT INTO rehearsal_user (rehearsal, user, participate, replyon) VALUES (?, ?, ?, NOW())",
            [["i", $id], ["i", $userId], ["i", $participate]],
          );
        }
      }
    }

    return ["success" => true];
  }

  private function createRehearsal()
  {
    $payload = $this->getRequestData();
    $fields = $payload["fields"] ?? [];
    $rehearsalFields = [
      "begin" => $fields["begin"] ?? "",
      "end" => $fields["end"] ?? "",
      "approve_until" => $fields["approve_until"] ?? "",
      "status" => $fields["status"] ?? "planned",
      "notes" => $fields["notes"] ?? "",
      "location" => $fields["location"] ?? 0,
      "conductor" => $fields["conductor"] ?? 0,
    ];

    if (empty($rehearsalFields["approve_until"]) && !empty($rehearsalFields["begin"])) {
      $rehearsalFields["approve_until"] = $rehearsalFields["begin"];
    }

    $newId = $this->insertRehearsalWithRelations($payload, $rehearsalFields, null);
    return ["id" => $newId];
  }

  private function deleteRehearsal()
  {
    global $system_data;
    $payload = $this->getRequestData();
    $id = intval($payload["id"] ?? 0);
    if ($id <= 0) {
      Response::error("Invalid rehearsal ID", 400);
    }

    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }

    $system_data->dbcon->execute("DELETE FROM rehearsal_group WHERE rehearsal = ?", [["i", $id]]);
    $system_data->dbcon->execute("DELETE FROM rehearsal_contact WHERE rehearsal = ?", [["i", $id]]);
    $system_data->dbcon->execute("DELETE FROM rehearsal_song WHERE rehearsal = ?", [["i", $id]]);
    $system_data->dbcon->execute("DELETE FROM rehearsal_user WHERE rehearsal = ?", [["i", $id]]);
    $system_data->dbcon->execute("DELETE FROM rehearsal WHERE id = ?", [["i", $id]]);

    return ["success" => true];
  }

  private function createSeries()
  {
    global $system_data;
    $payload = $this->getRequestData();

    $cycle = intval($payload["cycle"] ?? 0);
    if ($cycle !== 1 && $cycle !== 2) {
      Response::error("js.rehearsals.series.error.invalidCycle", 400);
    }

    $firstSession = trim(strval($payload["firstSession"] ?? ""));
    $lastSession = trim(strval($payload["lastSession"] ?? ""));
    if ($firstSession === "" || $lastSession === "") {
      Response::error("js.rehearsals.series.error.requiredDates", 400);
    }
    if (strtotime($firstSession) === false || strtotime($lastSession) === false) {
      Response::error("js.rehearsals.series.error.invalidDate", 400);
    }
    if (strtotime($lastSession) < strtotime($firstSession)) {
      Response::error("js.rehearsals.series.error.dateRange", 400);
    }

    $duration = intval($payload["duration"] ?? 0);
    if ($duration <= 0) {
      Response::error("js.rehearsals.series.error.durationPositive", 400);
    }
    $defaultTime = trim(strval($payload["defaultTime"] ?? ""));
    if (!preg_match('/^\d{2}:\d{2}$/', $defaultTime)) {
      Response::error("js.rehearsals.series.error.invalidTime", 400);
    }

    $groupIds = array_values(array_filter(array_map("intval", $payload["groupIds"] ?? []), fn($id) => $id > 0));
    if (count($groupIds) === 0) {
      Response::error("js.rehearsals.series.error.requiredGroups", 400);
    }
    $locationId = intval($payload["location"] ?? 0);
    if ($locationId <= 0) {
      Response::error("js.rehearsals.series.error.requiredLocation", 400);
    }

    $conductorId = intval($payload["conductor"] ?? 0);
    $notes = strval($payload["notes"] ?? "");
    $statusRaw = trim(strval($payload["status"] ?? "planned"));
    $status = $statusRaw !== "" ? $statusRaw : "planned";
    $seriesName = trim(strval($payload["name"] ?? ""));
    if ($seriesName === "") {
      Response::error("js.rehearsals.series.error.requiredName", 400);
    }
    if (strlen($seriesName) > 190) {
      Response::error("js.rehearsals.series.error.nameTooLong", 400);
    }

    $dates = $this->generateSeriesDates($firstSession, $lastSession, $cycle);
    if (count($dates) === 0) {
      Response::error("js.rehearsals.series.error.noSessionsGenerated", 400);
    }

    $seriesId = null;
    $createdCount = 0;
    try {
      $seriesId = $system_data->dbcon->prepStatement("INSERT INTO rehearsalserie (name) VALUES (?)", [
        ["s", $seriesName],
      ]);
      if (!$seriesId || intval($seriesId) <= 0) {
        throw new Exception("Failed to create series");
      }

      $seriesId = intval($seriesId);
      $payloadForChildren = [
        "groups" => $groupIds,
        "contacts" => $this->buildContactsForGroups($groupIds, $payload["contacts"] ?? []),
        "songs" => $payload["songs"] ?? [],
        "participants" => [],
      ];

      foreach ($dates as $sessionDate) {
        $beginIso = $sessionDate . "T" . $defaultTime . ":00";
        $endIso = date("Y-m-d\TH:i:s", strtotime($beginIso) + $duration * 60);
        $fields = [
          "begin" => $beginIso,
          "end" => $endIso,
          "approve_until" => $beginIso,
          "status" => $status,
          "notes" => $notes,
          "location" => $locationId,
          "conductor" => $conductorId,
        ];
        $this->insertRehearsalWithRelations($payloadForChildren, $fields, $seriesId);
        $createdCount++;
      }
    } catch (Throwable $e) {
      if ($seriesId !== null && intval($seriesId) > 0) {
        $sid = intval($seriesId);
        // Best-effort cleanup when true DB transactions are unavailable in this protocol.
        $system_data->dbcon->execute("DELETE FROM rehearsal WHERE serie = ?", [["i", $sid]]);
        $system_data->dbcon->execute("DELETE FROM rehearsalserie WHERE id = ?", [["i", $sid]]);
      }
      error_log("create_series failed: " . $e->getMessage());
      Response::error("js.rehearsals.series.error.createFailed: " . $e->getMessage(), 500);
    }

    return [
      "seriesId" => intval($seriesId),
      "createdCount" => $createdCount,
    ];
  }

  private function listSeries()
  {
    global $system_data;
    $rows = $system_data->dbcon->preparedQuery(
      "SELECT s.id, s.name, MIN(r.begin) AS first_session, MAX(r.begin) AS last_session, COUNT(r.id) AS rehearsal_count
             FROM rehearsalserie s
             JOIN rehearsal r ON r.serie = s.id
             GROUP BY s.id, s.name
             ORDER BY MAX(r.begin) DESC",
      [],
    );
    $result = [];
    foreach ($rows as $row) {
      $result[] = [
        "id" => intval($row["id"] ?? 0),
        "name" => $row["name"] ?? "",
        "firstSession" => $row["first_session"] ?? "",
        "lastSession" => $row["last_session"] ?? "",
        "rehearsalCount" => intval($row["rehearsal_count"] ?? 0),
      ];
    }
    return $result;
  }

  private function getSeries()
  {
    global $system_data;
    $id = intval($_GET["id"] ?? 0);
    if ($id <= 0) {
      Response::error("js.rehearsals.series.error.invalidSeriesId", 400);
    }

    $series = $system_data->dbcon->fetchRow("SELECT id, name FROM rehearsalserie WHERE id = ?", [["i", $id]]);
    if (!$series) {
      Response::error("js.rehearsals.series.error.seriesNotFound", 404);
    }

    $rehearsals = $system_data->dbcon->preparedQuery(
      "SELECT id, begin, end, approve_until, status, notes, location, conductor
             FROM rehearsal
             WHERE serie = ?
             ORDER BY begin ASC",
      [["i", $id]],
    );
    if (count($rehearsals) === 0) {
      return [
        "id" => intval($series["id"]),
        "name" => strval($series["name"] ?? ""),
        "cycle" => 1,
        "firstSession" => "",
        "lastSession" => "",
        "defaultTime" => "",
        "duration" => 0,
        "status" => "planned",
        "location" => 0,
        "conductor" => 0,
        "notes" => "",
        "groupIds" => [],
        "rehearsals" => [],
      ];
    }

    $first = $rehearsals[0];
    $last = $rehearsals[count($rehearsals) - 1];
    $firstTs = strtotime($first["begin"] ?? "");
    $endTs = strtotime($first["end"] ?? "");
    $duration = $firstTs && $endTs && $endTs >= $firstTs ? intval(round(($endTs - $firstTs) / 60)) : 0;
    $cycle = 1;
    if (count($rehearsals) > 1) {
      $firstDate = strtotime($rehearsals[0]["begin"] ?? "");
      $secondDate = strtotime($rehearsals[1]["begin"] ?? "");
      if ($firstDate && $secondDate) {
        $days = intval(round(($secondDate - $firstDate) / 86400));
        $cycle = $days >= 13 ? 2 : 1;
      }
    }

    $groupRows = $system_data->dbcon->preparedQuery(
      "SELECT DISTINCT rg.`group` AS group_id
             FROM rehearsal_group rg
             JOIN rehearsal r ON r.id = rg.rehearsal
             WHERE r.serie = ?
             ORDER BY rg.`group` ASC",
      [["i", $id]],
    );
    $groupIds = array_map(fn($row) => intval($row["group_id"] ?? 0), $groupRows);
    $groupIds = array_values(array_filter($groupIds, fn($gid) => $gid > 0));

    $contactRows = $system_data->dbcon->preparedQuery(
      "SELECT DISTINCT rc.contact AS contact_id
             FROM rehearsal_contact rc
             JOIN rehearsal r ON r.id = rc.rehearsal
             WHERE r.serie = ?
             ORDER BY rc.contact ASC",
      [["i", $id]],
    );
    $contactIds = array_map(fn($row) => intval($row["contact_id"] ?? 0), $contactRows);
    $contactIds = array_values(array_filter($contactIds, fn($cid) => $cid > 0));

    return [
      "id" => intval($series["id"]),
      "name" => strval($series["name"] ?? ""),
      "cycle" => $cycle,
      "firstSession" => ($first["begin"] ?? "") !== "" ? substr($first["begin"], 0, 10) : "",
      "lastSession" => ($last["begin"] ?? "") !== "" ? substr($last["begin"], 0, 10) : "",
      "defaultTime" => ($first["begin"] ?? "") !== "" ? substr($first["begin"], 11, 5) : "",
      "duration" => $duration,
      "status" => strval($first["status"] ?? "planned"),
      "location" => intval($first["location"] ?? 0),
      "conductor" => intval($first["conductor"] ?? 0),
      "notes" => strval($first["notes"] ?? ""),
      "groupIds" => $groupIds,
      "contacts" => $contactIds,
      "rehearsals" => array_map(function ($row) {
        return [
          "id" => intval($row["id"] ?? 0),
          "begin" => strval($row["begin"] ?? ""),
          "end" => strval($row["end"] ?? ""),
          "approve_until" => strval($row["approve_until"] ?? ""),
          "status" => strval($row["status"] ?? ""),
          "notes" => strval($row["notes"] ?? ""),
          "location" => intval($row["location"] ?? 0),
          "conductor" => intval($row["conductor"] ?? 0),
        ];
      }, $rehearsals),
    ];
  }

  private function listRehearsalsBySeries()
  {
    global $system_data;
    $seriesId = intval($_GET["seriesId"] ?? 0);
    if ($seriesId <= 0) {
      Response::error("js.rehearsals.series.error.invalidSeriesId", 400);
    }
    $rows = $system_data->dbcon->preparedQuery(
      "SELECT r.id, r.begin, r.end, r.approve_until, r.notes, r.status, r.conductor, l.name AS location_name
             FROM rehearsal r
             JOIN location l ON r.location = l.id
             WHERE r.serie = ?
             ORDER BY r.begin ASC",
      [["i", $seriesId]],
    );
    $result = [];
    foreach ($rows as $r) {
      $result[] = [
        "id" => intval($r["id"] ?? 0),
        "begin" => strval($r["begin"] ?? ""),
        "end" => strval($r["end"] ?? ""),
        "approve_until" => strval($r["approve_until"] ?? ""),
        "location_name" => strval($r["location_name"] ?? ""),
        "notes" => strval($r["notes"] ?? ""),
        "status" => strval($r["status"] ?? ""),
        "conductor" => isset($r["conductor"]) ? intval($r["conductor"]) : null,
        "participationStats" => $this->getParticipationStatsForRehearsal($r["id"] ?? null),
      ];
    }
    return $result;
  }

  private function updateSeries()
  {
    global $system_data;
    $payload = $this->getRequestData();
    $seriesId = intval($payload["id"] ?? 0);
    if ($seriesId <= 0) {
      Response::error("js.rehearsals.series.error.invalidSeriesId", 400);
    }
    $exists = intval(
      $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM rehearsalserie WHERE id = ?", "cnt", [
        ["i", $seriesId],
      ]),
    );
    if ($exists <= 0) {
      Response::error("js.rehearsals.series.error.seriesNotFound", 404);
    }

    $seriesName = trim(strval($payload["name"] ?? ""));
    if ($seriesName === "") {
      Response::error("js.rehearsals.series.error.requiredName", 400);
    }
    if (strlen($seriesName) > 190) {
      Response::error("js.rehearsals.series.error.nameTooLong", 400);
    }

    $cycle = intval($payload["cycle"] ?? 0);
    if ($cycle !== 1 && $cycle !== 2) {
      Response::error("js.rehearsals.series.error.invalidCycle", 400);
    }
    $firstSession = trim(strval($payload["firstSession"] ?? ""));
    $lastSession = trim(strval($payload["lastSession"] ?? ""));
    if ($firstSession === "" || $lastSession === "") {
      Response::error("js.rehearsals.series.error.requiredDates", 400);
    }
    if (strtotime($firstSession) === false || strtotime($lastSession) === false) {
      Response::error("js.rehearsals.series.error.invalidDate", 400);
    }
    if (strtotime($lastSession) < strtotime($firstSession)) {
      Response::error("js.rehearsals.series.error.dateRange", 400);
    }
    $duration = intval($payload["duration"] ?? 0);
    if ($duration <= 0) {
      Response::error("js.rehearsals.series.error.durationPositive", 400);
    }
    $defaultTime = trim(strval($payload["defaultTime"] ?? ""));
    if (!preg_match('/^\d{2}:\d{2}$/', $defaultTime)) {
      Response::error("js.rehearsals.series.error.invalidTime", 400);
    }
    $groupIds = array_values(array_filter(array_map("intval", $payload["groupIds"] ?? []), fn($id) => $id > 0));
    if (count($groupIds) === 0) {
      Response::error("js.rehearsals.series.error.requiredGroups", 400);
    }
    $locationId = intval($payload["location"] ?? 0);
    if ($locationId <= 0) {
      Response::error("js.rehearsals.series.error.requiredLocation", 400);
    }
    $conductorId = intval($payload["conductor"] ?? 0);
    $statusRaw = trim(strval($payload["status"] ?? "planned"));
    $status = $statusRaw !== "" ? $statusRaw : "planned";
    $notes = strval($payload["notes"] ?? "");

    $dates = $this->generateSeriesDates($firstSession, $lastSession, $cycle);
    if (count($dates) === 0) {
      Response::error("js.rehearsals.series.error.noSessionsGenerated", 400);
    }

    try {
      $system_data->dbcon->execute("UPDATE rehearsalserie SET name = ? WHERE id = ?", [
        ["s", $seriesName],
        ["i", $seriesId],
      ]);
      $existingRows = $system_data->dbcon->preparedQuery(
        "SELECT id, begin FROM rehearsal WHERE serie = ? ORDER BY begin ASC, id ASC",
        [["i", $seriesId]],
      );
      $existingByDate = [];
      foreach ($existingRows as $row) {
        $dateKey = substr(strval($row["begin"] ?? ""), 0, 10);
        if ($dateKey === "") {
          $dateKey = "__unknown__";
        }
        if (!array_key_exists($dateKey, $existingByDate)) {
          $existingByDate[$dateKey] = [];
        }
        $existingByDate[$dateKey][] = intval($row["id"] ?? 0);
      }

      $groupsForChildren = $groupIds;
      $contactsForChildren = $this->buildContactsForGroups($groupIds, $payload["contacts"] ?? []);
      $updatedCount = 0;
      $createdCount = 0;
      $usedIds = [];

      foreach ($dates as $sessionDate) {
        $beginIso = $sessionDate . "T" . $defaultTime . ":00";
        $endIso = date("Y-m-d\TH:i:s", strtotime($beginIso) + $duration * 60);
        $fields = [
          "begin" => $beginIso,
          "end" => $endIso,
          "approve_until" => $beginIso,
          "status" => $status,
          "notes" => $notes,
          "location" => $locationId,
          "conductor" => $conductorId,
        ];
        $candidateIds = $existingByDate[$sessionDate] ?? [];
        $matchedId = null;
        if (count($candidateIds) > 0) {
          $matchedId = intval(array_shift($candidateIds));
          $existingByDate[$sessionDate] = $candidateIds;
        }
        if ($matchedId !== null && $matchedId > 0) {
          $system_data->dbcon->execute(
            "UPDATE rehearsal
                         SET begin = ?, end = ?, approve_until = ?, status = ?, notes = ?, location = ?, conductor = ?
                         WHERE id = ?",
            [
              ["s", $fields["begin"]],
              ["s", $fields["end"]],
              ["s", $fields["approve_until"]],
              ["s", $fields["status"]],
              ["s", $fields["notes"]],
              ["i", intval($fields["location"])],
              ["i", intval($fields["conductor"])],
              ["i", $matchedId],
            ],
          );
          $this->syncRehearsalRelations($matchedId, $groupsForChildren, $contactsForChildren);
          $usedIds[$matchedId] = true;
          $updatedCount++;
        } else {
          $payloadForChildren = [
            "groups" => $groupsForChildren,
            "contacts" => $contactsForChildren,
            "songs" => [],
            "participants" => [],
          ];
          $newId = $this->insertRehearsalWithRelations($payloadForChildren, $fields, $seriesId);
          $usedIds[intval($newId)] = true;
          $createdCount++;
        }
      }

      $removeIds = [];
      foreach ($existingByDate as $ids) {
        foreach ($ids as $id) {
          $idInt = intval($id);
          if ($idInt > 0 && !isset($usedIds[$idInt])) {
            $removeIds[] = $idInt;
          }
        }
      }
      $removedCount = 0;
      foreach ($removeIds as $removeId) {
        $system_data->dbcon->execute("DELETE FROM rehearsal WHERE id = ?", [["i", $removeId]]);
        $removedCount++;
      }
      $totalInSeries = intval(
        $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM rehearsal WHERE serie = ?", "cnt", [
          ["i", $seriesId],
        ]),
      );
      return [
        "seriesId" => $seriesId,
        "updated" => true,
        "updatedRehearsals" => $updatedCount,
        "createdRehearsals" => $createdCount,
        "removedRehearsals" => $removedCount,
        "totalRehearsals" => $totalInSeries,
      ];
    } catch (Throwable $e) {
      error_log("update_series failed: " . $e->getMessage());
      Response::error("js.rehearsals.series.error.updateFailed: " . $e->getMessage(), 500);
    }
    return ["seriesId" => $seriesId, "updated" => true];
  }

  private function deleteSeries()
  {
    global $system_data;
    $payload = $this->getRequestData();
    $seriesId = intval($payload["seriesId"] ?? 0);
    if ($seriesId <= 0) {
      Response::error("js.rehearsals.series.error.invalidSeriesId", 400);
    }
    $exists = intval(
      $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM rehearsalserie WHERE id = ?", "cnt", [
        ["i", $seriesId],
      ]),
    );
    if ($exists <= 0) {
      Response::error("js.rehearsals.series.error.seriesNotFound", 404);
    }

    $deletedCount = 0;
    try {
      $deletedCount = intval(
        $system_data->dbcon->colValue("SELECT COUNT(*) AS cnt FROM rehearsal WHERE serie = ?", "cnt", [
          ["i", $seriesId],
        ]),
      );
      $system_data->dbcon->execute("DELETE FROM rehearsal WHERE serie = ?", [["i", $seriesId]]);
      $system_data->dbcon->execute("DELETE FROM rehearsalserie WHERE id = ?", [["i", $seriesId]]);
    } catch (Throwable $e) {
      error_log("delete_series failed: " . $e->getMessage());
      Response::error("js.rehearsals.series.error.deleteFailed: " . $e->getMessage(), 500);
    }

    return ["deletedCount" => $deletedCount];
  }

  private function generateSeriesDates($firstSession, $lastSession, $cycle)
  {
    $result = [];
    $first = DateTime::createFromFormat("Y-m-d", $firstSession);
    $last = DateTime::createFromFormat("Y-m-d", $lastSession);
    if (!$first || !$last) {
      return $result;
    }
    $cursor = clone $first;
    $stepDays = $cycle === 2 ? 14 : 7;
    $safety = 0;
    while ($cursor <= $last && $safety < 520) {
      $result[] = $cursor->format("Y-m-d");
      $cursor->modify("+" . $stepDays . " day");
      $safety++;
    }
    return $result;
  }

  private function buildContactsForGroups($groupIds, $explicitContacts = [])
  {
    global $system_data;
    $contacts = [];
    foreach ($explicitContacts as $contactId) {
      $cid = intval($contactId);
      if ($cid > 0) {
        $contacts[$cid] = true;
      }
    }
    if (count($groupIds) === 0) {
      return array_map("intval", array_keys($contacts));
    }
    $where = implode(",", array_fill(0, count($groupIds), "?"));
    $params = [];
    foreach ($groupIds as $groupId) {
      $params[] = ["i", intval($groupId)];
    }
    $rows = $system_data->dbcon->preparedQuery("SELECT contact FROM contact_group WHERE `group` IN ($where)", $params);
    foreach ($rows as $row) {
      $cid = intval($row["contact"] ?? 0);
      if ($cid > 0) {
        $contacts[$cid] = true;
      }
    }
    return array_map("intval", array_keys($contacts));
  }

  private function syncRehearsalRelations($rehearsalId, $groups, $contacts)
  {
    global $system_data;
    $rid = intval($rehearsalId);
    if ($rid <= 0) {
      return;
    }
    $previousContactIds = $this->rehearsalContactIds($rid);
    $system_data->dbcon->execute("DELETE FROM rehearsal_group WHERE rehearsal = ?", [["i", $rid]]);
    $groupIds = array_values(array_filter(array_map("intval", $groups ?? []), fn($id) => $id > 0));
    if (count($groupIds) > 0) {
      $tuples = [];
      $params = [];
      foreach ($groupIds as $groupId) {
        $tuples[] = "(?, ?)";
        $params[] = ["i", $rid];
        $params[] = ["i", $groupId];
      }
      $system_data->dbcon->execute(
        "INSERT INTO rehearsal_group (rehearsal, `group`) VALUES " . join(",", $tuples),
        $params,
      );
    }

    $system_data->dbcon->execute("DELETE FROM rehearsal_contact WHERE rehearsal = ?", [["i", $rid]]);
    $contactIds = array_values(array_filter(array_map("intval", $contacts ?? []), fn($id) => $id > 0));
    if (count($contactIds) > 0) {
      $tuples = [];
      $params = [];
      foreach ($contactIds as $contactId) {
        $tuples[] = "(?, ?)";
        $params[] = ["i", $rid];
        $params[] = ["i", $contactId];
      }
      $system_data->dbcon->execute("INSERT INTO rehearsal_contact VALUES " . join(",", $tuples), $params);
      $placeholders = implode(",", array_fill(0, count($contactIds), "?"));
      $params = [["i", $rid]];
      foreach ($contactIds as $contactId) {
        $params[] = ["i", $contactId];
      }
      $system_data->dbcon->execute(
        "DELETE ru FROM rehearsal_user ru JOIN user u ON ru.user = u.id
                 WHERE ru.rehearsal = ? AND u.contact NOT IN ($placeholders)",
        $params,
      );
    } else {
      $system_data->dbcon->execute("DELETE FROM rehearsal_user WHERE rehearsal = ?", [["i", $rid]]);
    }

    $addedContacts = array_values(array_diff($contactIds, $previousContactIds));
    if (count($addedContacts) > 0) {
      EventParticipantNotifier::sendSafe($system_data, new StartData(), "R", $rid, $addedContacts);
    }
  }

  /** @return list<int> */
  private function rehearsalContactIds(int $rehearsalId): array
  {
    global $system_data;
    $sel = $system_data->dbcon->getSelection("SELECT contact FROM rehearsal_contact WHERE rehearsal = ?", [
      ["i", $rehearsalId],
    ]);
    if (!is_array($sel) || count($sel) < 2) {
      return [];
    }
    $ids = [];
    for ($i = 1; $i < count($sel); $i++) {
      $ids[] = (int) ($sel[$i]["contact"] ?? 0);
    }

    return $ids;
  }

  private function insertRehearsalWithRelations($payload, $fields, $seriesId = null)
  {
    global $system_data;
    $values = [
      "begin" => $fields["begin"] ?? "",
      "end" => $fields["end"] ?? "",
      "approve_until" => $fields["approve_until"] ?? "",
      "status" => "",
      "notes" => $fields["notes"] ?? "",
      "location" => intval($fields["location"] ?? 0),
      "conductor" => intval($fields["conductor"] ?? 0),
    ];
    $statusRaw = trim(strval($fields["status"] ?? "planned"));
    $values["status"] = $statusRaw !== "" ? $statusRaw : "planned";

    if (empty($values["approve_until"]) && !empty($values["begin"])) {
      $values["approve_until"] = $values["begin"];
    }

    $notesBackup = $values["notes"];
    $values["notes"] = "";
    $this->data->validate($values);
    $values["notes"] = $notesBackup;

    if ($seriesId !== null) {
      $newId = $system_data->dbcon->prepStatement(
        "INSERT INTO rehearsal (begin, end, approve_until, status, notes, location, conductor, serie)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
          ["s", $values["begin"]],
          ["s", $values["end"]],
          ["s", $values["approve_until"]],
          ["s", $values["status"]],
          ["s", $values["notes"]],
          ["i", intval($values["location"])],
          ["i", intval($values["conductor"])],
          ["i", intval($seriesId)],
        ],
      );
    } else {
      $newId = $system_data->dbcon->prepStatement(
        "INSERT INTO rehearsal (begin, end, approve_until, status, notes, location, conductor)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
          ["s", $values["begin"]],
          ["s", $values["end"]],
          ["s", $values["approve_until"]],
          ["s", $values["status"]],
          ["s", $values["notes"]],
          ["i", intval($values["location"])],
          ["i", intval($values["conductor"])],
        ],
      );
    }
    if (!$newId || intval($newId) <= 0) {
      Response::error("js.rehearsals.series.error.createFailed", 500);
    }
    $newId = intval($newId);

    if (array_key_exists("groups", $payload)) {
      $groups = array_values(array_filter(array_map("intval", $payload["groups"] ?? []), fn($id) => $id > 0));
      if (count($groups) > 0) {
        $tuples = [];
        $params = [];
        foreach ($groups as $groupId) {
          $tuples[] = "(?, ?)";
          $params[] = ["i", $newId];
          $params[] = ["i", $groupId];
        }
        $query = "INSERT INTO rehearsal_group (rehearsal, `group`) VALUES " . join(",", $tuples);
        $system_data->dbcon->execute($query, $params);
      }
    }

    if (array_key_exists("contacts", $payload)) {
      $contacts = array_values(array_filter(array_map("intval", $payload["contacts"] ?? []), fn($id) => $id > 0));
      if (count($contacts) > 0) {
        $tuples = [];
        $params = [];
        foreach ($contacts as $contactId) {
          $tuples[] = "(?, ?)";
          $params[] = ["i", $newId];
          $params[] = ["i", $contactId];
        }
        $query = "INSERT INTO rehearsal_contact VALUES " . join(",", $tuples);
        $system_data->dbcon->execute($query, $params);
      }
    }

    if (array_key_exists("songs", $payload)) {
      $songs = $payload["songs"] ?? [];
      if (count($songs) > 0) {
        $tuples = [];
        $params = [];
        foreach ($songs as $song) {
          $songId = intval($song["id"] ?? 0);
          if ($songId <= 0) {
            continue;
          }
          $tuples[] = "(?, ?, ?)";
          $params[] = ["i", $songId];
          $params[] = ["i", $newId];
          $params[] = ["s", $song["notes"] ?? ""];
        }
        if (count($tuples) > 0) {
          $query = "INSERT INTO rehearsal_song (song, rehearsal, notes) VALUES " . join(",", $tuples);
          $system_data->dbcon->execute($query, $params);
        }
      }
    }

    if (array_key_exists("participants", $payload)) {
      $participants = $payload["participants"] ?? [];
      foreach ($participants as $participant) {
        $userId = intval($participant["userId"] ?? 0);
        if ($userId <= 0 || !$this->isUserInvitedToRehearsal($newId, $userId)) {
          continue;
        }
        $participate = $participant["participate"] ?? null;
        if ($participate === null || $participate === "") {
          continue;
        }
        $system_data->dbcon->execute(
          "INSERT INTO rehearsal_user (rehearsal, user, participate, replyon) VALUES (?, ?, ?, NOW())",
          [["i", $newId], ["i", $userId], ["i", intval($participate)]],
        );
      }
    }

    EventParticipantNotifier::sendSafe($system_data, new StartData(), "R", $newId, null);

    return $newId;
  }

  private function getRequestData()
  {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    if (!$data) {
      $data = $_POST;
    }
    return $data;
  }

  private function emailInfoDraft()
  {
    global $system_data;
    $payload = $this->getRequestData();
    $id = intval($payload["id"] ?? 0);
    if ($id <= 0) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }
    $locale = trim(
      (string) ($payload["locale"] ?? (method_exists($system_data, "getLang") ? $system_data->getLang() : "en")),
    );
    $user = Auth::getUserInfo();
    $senderName = trim((string) (($user["name"] ?? "") . " " . ($user["surname"] ?? "")));
    return EventInfoMailService::draft($system_data, "R", $id, $locale !== "" ? $locale : "en", $senderName);
  }

  private function emailInfoPreview()
  {
    global $system_data;
    $payload = $this->getRequestData();
    $id = intval($payload["id"] ?? 0);
    if ($id <= 0) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }
    $locale = trim(
      (string) ($payload["locale"] ?? (method_exists($system_data, "getLang") ? $system_data->getLang() : "en")),
    );
    $user = Auth::getUserInfo();
    $senderName = trim((string) (($user["name"] ?? "") . " " . ($user["surname"] ?? "")));
    $recipientIds = array_values(
      array_filter(array_map("intval", $payload["recipientIds"] ?? []), fn($value) => $value > 0),
    );
    $manualEmails = array_values(
      array_filter(array_map("strval", $payload["manualEmails"] ?? []), fn($value) => trim($value) !== ""),
    );
    $subject = trim((string) ($payload["subject"] ?? ""));
    $body = (string) ($payload["body"] ?? "");
    try {
      $html = EventInfoMailService::previewHtml(
        $system_data,
        "R",
        $id,
        $locale !== "" ? $locale : "en",
        $recipientIds,
        $manualEmails,
        $subject,
        $body,
        $senderName,
      );
    } catch (Throwable $e) {
      Response::error($e->getMessage(), 400);
    }
    return ["html" => $html];
  }

  private function emailInfoSend()
  {
    global $system_data;
    $payload = $this->getRequestData();
    $id = intval($payload["id"] ?? 0);
    if ($id <= 0) {
      Response::error("Invalid rehearsal ID", 400);
    }
    $userId = Auth::getUserId();
    if (!$this->userHasAccessToRehearsal($id, $userId)) {
      Response::error("Access denied to this rehearsal", 403);
    }
    $locale = trim(
      (string) ($payload["locale"] ?? (method_exists($system_data, "getLang") ? $system_data->getLang() : "en")),
    );
    $user = Auth::getUserInfo();
    $senderName = trim((string) (($user["name"] ?? "") . " " . ($user["surname"] ?? "")));
    $recipientIds = array_values(
      array_filter(array_map("intval", $payload["recipientIds"] ?? []), fn($value) => $value > 0),
    );
    $manualEmails = array_values(
      array_filter(array_map("strval", $payload["manualEmails"] ?? []), fn($value) => trim($value) !== ""),
    );
    $subject = trim((string) ($payload["subject"] ?? ""));
    $body = (string) ($payload["body"] ?? "");
    if ($subject === "") {
      Response::error("mail_subject_required", 400);
    }
    if (trim($body) === "") {
      Response::error("mail_body_required", 400);
    }
    try {
      return EventInfoMailService::send(
        $system_data,
        "R",
        $id,
        $locale !== "" ? $locale : "en",
        $recipientIds,
        $manualEmails,
        $subject,
        $body,
        $senderName,
      );
    } catch (InvalidArgumentException $e) {
      Response::error($e->getMessage(), 400);
    } catch (Throwable $e) {
      Response::error($e->getMessage(), 500);
    }
  }

  /**
   * Check if user has access to a rehearsal
   * Allows access to both past and future rehearsals
   */
  private function userHasAccessToRehearsal($rehearsalId, $userId)
  {
    global $system_data;

    $rehearsalId = intval($rehearsalId);

    // Users with Rehearsals module permission can always access all rehearsals.
    $moduleId = getLegacyModuleId($system_data, LegacyModuleKey::REHEARSALS);
    if ($moduleId && $system_data->userHasPermission($moduleId)) {
      $rehearsal = $this->data->findByIdNoRef($rehearsalId);
      return $rehearsal !== null && count($rehearsal) > 0;
    }

    // Super users see all rehearsals (past and future)
    if ($system_data->isUserSuperUser($userId)) {
      // Check if rehearsal exists (regardless of date)
      $rehearsal = $this->data->findByIdNoRef($rehearsalId);
      return $rehearsal !== null && count($rehearsal) > 0;
    }

    // Get rehearsals from groups and phases (includes past and future)
    try {
      $startData = new StartData();
      $usersPhases = $startData->adp()->getUsersPhases($userId);
      $rehearsalIds = array_merge($this->getRehearsalsForUser($userId), $this->getRehearsalsForPhases($usersPhases));

      $rehearsalIds = array_map("intval", array_unique($rehearsalIds));
      return in_array($rehearsalId, $rehearsalIds);
    } catch (Exception $e) {
      error_log("Error checking rehearsal access: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get rehearsals for user (from contact)
   */
  private function getRehearsalsForUser($userId)
  {
    global $system_data;
    $query = "SELECT rehearsal 
                    FROM rehearsal_contact rc 
                        JOIN contact c ON rc.contact = c.id 
                        JOIN user u ON u.contact = c.id 
                    WHERE u.id = ?";
    $sel = $system_data->dbcon->getSelection($query, [["i", $userId]]);
    return Database::flattenSelection($sel, "rehearsal");
  }

  /**
   * Get rehearsals for phases
   */
  private function getRehearsalsForPhases($phases)
  {
    if (count($phases) == 0) {
      return [];
    }

    global $system_data;
    $params = [];
    $whereQ = [];
    foreach ($phases as $p) {
      $whereQ[] = "rehearsalphase = ?";
      $params[] = ["i", $p];
    }
    $query = "SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE " . join(" OR ", $whereQ);
    $sel = $system_data->dbcon->getSelection($query, $params);
    return Database::flattenSelection($sel, "rehearsal");
  }
}
