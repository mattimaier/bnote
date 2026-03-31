<?php
/**
 * BNote Next Generation - Locations API Module
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
 * Locations API module
 * Provides location (venue) management endpoints
 *
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
require_once BNOTE_ROOT . '/src/data/modules/locationsdata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class LocationsModule {
    private $data;

    public function __construct() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Locations');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Locations', 403);
        }

        $this->data = new LocationsData();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return $this->listLocations();
            case 'get':
                return $this->getLocation();
            case 'events':
                return $this->getLocationEvents();
            case 'create':
                return $this->createLocation();
            case 'update':
                return $this->updateLocation();
            case 'delete':
                return $this->deleteLocation();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /**
     * List all locations with address fields (using public data API only)
     */
    private function listLocations() {
        $colExchange = ['address' => ['street', 'city', 'zip', 'state', 'country']];
        $rows = $this->data->findAllJoinedOrdered($colExchange, 'name');
        $result = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                // AbstractData join aliases: address.street -> "addressstreet", etc.
                $result[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'] ?? '',
                    'notes' => $row['notes'] ?? '',
                    'address' => isset($row['address']) ? intval($row['address']) : null,
                    'location_type' => isset($row['location_type']) ? intval($row['location_type']) : null,
                    'street' => $row['addressstreet'] ?? $row['street'] ?? '',
                    'city' => $row['addresscity'] ?? $row['city'] ?? '',
                    'zip' => $row['addresszip'] ?? $row['zip'] ?? '',
                    'state' => $row['addressstate'] ?? $row['state'] ?? '',
                    'country' => $row['addresscountry'] ?? $row['country'] ?? '',
                ];
            }
        }
        return $result;
    }

    /**
     * Get single location by ID with address and custom data
     */
    private function getLocation() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Location ID required', 400);
        }

        $location = $this->data->getLocation($id);
        if (!$location) {
            Response::error('Location not found', 404);
        }

        return [
            'id' => intval($location['id']),
            'name' => $location['name'] ?? '',
            'notes' => $location['notes'] ?? '',
            'address' => isset($location['address']) ? intval($location['address']) : null,
            'location_type' => isset($location['location_type']) ? intval($location['location_type']) : null,
            'street' => $location['street'] ?? '',
            'city' => $location['city'] ?? '',
            'zip' => $location['zip'] ?? '',
            'state' => $location['state'] ?? '',
            'country' => $location['country'] ?? '',
        ];
    }

    /**
     * Create new location
     */
    private function createLocation() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $values = [
            'name' => $data['name'] ?? '',
            'notes' => $data['notes'] ?? '',
            'street' => $data['street'] ?? '',
            'city' => $data['city'] ?? '',
            'zip' => $data['zip'] ?? '',
            'state' => $data['state'] ?? '',
            'country' => $data['country'] ?? '',
        ];
        if (isset($data['location_type'])) {
            $values['location_type'] = $data['location_type'];
        }

        try {
            $_POST = $values;
            $id = $this->data->create($values);
            return [
                'success' => true,
                'id' => intval($id),
                'message' => 'Location created successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update location
     */
    private function updateLocation() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Location ID required', 400);
        }

        $values = [];
        foreach (['name', 'notes', 'street', 'city', 'zip', 'state', 'country', 'location_type'] as $field) {
            if (array_key_exists($field, $data)) {
                $values[$field] = $data[$field];
            }
        }

        try {
            $_POST = array_merge($values, ['id' => $id]);
            $this->data->update($id, $values);
            return [
                'success' => true,
                'message' => 'Location updated successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete location
     */
    private function deleteLocation() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Location ID required', 400);
        }

        try {
            $this->data->delete($id);
            return [
                'success' => true,
                'message' => 'Location deleted successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get rehearsals and concerts at this location (access-controlled, same rules as rehearsals/concerts modules).
     */
    private function getLocationEvents() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Location ID required', 400);
        }
        $locationId = intval($id);

        $location = $this->data->getLocation($locationId);
        if (!$location) {
            Response::error('Location not found', 404);
        }

        $rehearsals = $this->getRehearsalsAtLocation($locationId);
        $concerts = $this->getConcertsAtLocation($locationId);

        $events = [];
        foreach ($rehearsals as $r) {
            $events[] = [
                'type' => 'rehearsal',
                'id' => intval($r['id']),
                'begin' => $r['begin'] ?? '',
                'end' => $r['end'] ?? '',
                'title' => '',
                'status' => $r['status'] ?? '',
                'notes' => $r['notes'] ?? '',
                'participationStats' => $r['participationStats'] ?? null,
            ];
        }
        foreach ($concerts as $c) {
            $events[] = [
                'type' => 'concert',
                'id' => intval($c['id']),
                'begin' => $c['begin'] ?? '',
                'end' => $c['end'] ?? '',
                'title' => $c['title'] ?? '',
                'status' => $c['status'] ?? '',
                'notes' => $c['notes'] ?? '',
                'participationStats' => $c['participationStats'] ?? null,
            ];
        }

        usort($events, function ($a, $b) {
            $tA = !empty($a['begin']) ? strtotime($a['begin']) : 0;
            $tB = !empty($b['begin']) ? strtotime($b['begin']) : 0;
            return $tB - $tA;
        });

        return $events;
    }

    private function getRehearsalsAtLocation($locationId) {
        global $system_data;
        $userId = Auth::getUserId();
        $uid = intval($userId);
        $rehearsalsModuleId = $system_data->getModuleId('Proben');
        $hasRehearsalsModule = $rehearsalsModuleId ? $system_data->userHasPermission($rehearsalsModuleId) : false;

        if ($system_data->isUserSuperUser($uid) || $hasRehearsalsModule) {
            $query = "SELECT r.id, r.begin, r.end, r.notes, r.status
                      FROM rehearsal r
                      WHERE r.location = ?
                      ORDER BY r.begin DESC";
            $rows = $system_data->dbcon->getSelection($query, [['i', $locationId]]);
        } else {
            $startData = new StartData();
            $usersPhases = $startData->adp()->getUsersPhases($uid);
            $rehearsalIds = array_merge(
                $this->getRehearsalsForUser($uid),
                $this->getRehearsalsForPhases($usersPhases)
            );
            $rehearsalIds = array_map('intval', array_unique($rehearsalIds));
            if (count($rehearsalIds) === 0) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($rehearsalIds), '?'));
            $params = array_map(fn($id) => ['i', $id], $rehearsalIds);
            $params[] = ['i', $locationId];
            $query = "SELECT r.id, r.begin, r.end, r.notes, r.status
                      FROM rehearsal r
                      WHERE r.id IN ($placeholders) AND r.location = ?
                      ORDER BY r.begin DESC";
            $rows = $system_data->dbcon->getSelection($query, $params);
        }

        $list = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $participationStats = $this->getParticipationStatsForRehearsal($r['id'] ?? null);
                $list[] = [
                    'id' => $r['id'],
                    'begin' => $r['begin'] ?? '',
                    'end' => $r['end'] ?? '',
                    'notes' => $r['notes'] ?? '',
                    'status' => $r['status'] ?? '',
                    'participationStats' => $participationStats,
                ];
            }
        }
        return $list;
    }

    private function getConcertsAtLocation($locationId) {
        global $system_data;
        $userId = Auth::getUserId();
        $uid = intval($userId);
        $concertsModuleId = $system_data->getModuleId('Konzerte');
        $hasConcertsModule = $concertsModuleId ? $system_data->userHasPermission($concertsModuleId) : false;

        if ($system_data->isUserSuperUser($uid) || $hasConcertsModule) {
            $query = "SELECT c.id, c.title, c.begin, c.end, c.notes, c.status
                      FROM concert c
                      WHERE c.location = ?
                      ORDER BY c.begin DESC";
            $rows = $system_data->dbcon->getSelection($query, [['i', $locationId]]);
        } else {
            $startData = new StartData();
            $phases = $startData->adp()->getUsersPhases($uid);
            $contactId = $startData->adp()->getUserContact($uid);
            $params = [];
            if (count($phases) > 0) {
                $phaseWhere = [];
                foreach ($phases as $p) {
                    $phaseWhere[] = 'rehearsalphase = ?';
                    $params[] = ['i', $p];
                }
                $phaseQuery = "SELECT concert FROM rehearsalphase_concert WHERE " . join(' OR ', $phaseWhere);
            } else {
                $phaseQuery = "SELECT concert FROM rehearsalphase_concert WHERE 0 = 1";
            }
            $params[] = ['i', $contactId];
            $params[] = ['i', $locationId];
            $query = "SELECT DISTINCT c.id, c.title, c.begin, c.end, c.notes, c.status
                      FROM concert c
                      JOIN (
                        $phaseQuery
                        UNION ALL
                        SELECT concert FROM concert_contact WHERE contact = ?
                      ) AS concerts ON c.id = concerts.concert
                      WHERE c.location = ?
                      ORDER BY c.begin DESC";
            $rows = $system_data->dbcon->getSelection($query, $params);
        }

        $list = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $c = $rows[$i];
                $participationStats = $this->getParticipationStatsForConcert($c['id'] ?? null);
                $list[] = [
                    'id' => $c['id'],
                    'title' => $c['title'] ?? '',
                    'begin' => $c['begin'] ?? '',
                    'end' => $c['end'] ?? '',
                    'notes' => $c['notes'] ?? '',
                    'status' => $c['status'] ?? '',
                    'participationStats' => $participationStats,
                ];
            }
        }
        return $list;
    }

    private function getRehearsalsForUser($userId) {
        global $system_data;
        $query = "SELECT rehearsal
                    FROM rehearsal_contact rc
                    JOIN contact c ON rc.contact = c.id
                    JOIN user u ON u.contact = c.id
                    WHERE u.id = ?";
        $sel = $system_data->dbcon->getSelection($query, [['i', $userId]]);
        return Database::flattenSelection($sel, 'rehearsal');
    }

    private function getRehearsalsForPhases($phases) {
        if (count($phases) == 0) {
            return [];
        }
        global $system_data;
        $params = [];
        $whereQ = [];
        foreach ($phases as $p) {
            $whereQ[] = 'rehearsalphase = ?';
            $params[] = ['i', $p];
        }
        $query = 'SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ);
        $sel = $system_data->dbcon->getSelection($query, $params);
        return Database::flattenSelection($sel, 'id');
    }

    private function getParticipationStatsForRehearsal($rehearsalId) {
        global $system_data;
        if (!$rehearsalId || !is_numeric($rehearsalId)) {
            return ['yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0, 'total' => 0];
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
        $rows = $system_data->dbcon->getSelection($query, [['i', $rid], ['i', $rid]]);
        $row = is_array($rows) && isset($rows[1]) ? $rows[1] : null;
        $yes = isset($row['yes']) ? intval($row['yes']) : 0;
        $maybe = isset($row['maybe']) ? intval($row['maybe']) : 0;
        $no = isset($row['no']) ? intval($row['no']) : 0;
        $pending = isset($row['pending']) ? intval($row['pending']) : 0;
        $total = $yes + $maybe + $no + $pending;
        return ['yes' => $yes, 'maybe' => $maybe, 'no' => $no, 'pending' => $pending, 'total' => $total];
    }

    private function getParticipationStatsForConcert($concertId) {
        global $system_data;
        if (!$concertId || !is_numeric($concertId)) {
            return ['yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0, 'total' => 0];
        }
        $cid = intval($concertId);
        $query = "SELECT
                    SUM(CASE WHEN cu.participate = 1 THEN 1 ELSE 0 END) as yes,
                    SUM(CASE WHEN cu.participate = 2 THEN 1 ELSE 0 END) as maybe,
                    SUM(CASE WHEN cu.participate = 0 THEN 1 ELSE 0 END) as no,
                    SUM(CASE WHEN cu.participate IS NULL OR cu.participate < 0 THEN 1 ELSE 0 END) as pending
                  FROM concert_contact cc
                  JOIN contact ct ON cc.contact = ct.id
                  JOIN user u ON u.contact = ct.id
                  LEFT JOIN concert_user cu ON cu.user = u.id AND cu.concert = ?
                  WHERE cc.concert = ?";
        $rows = $system_data->dbcon->getSelection($query, [['i', $cid], ['i', $cid]]);
        $row = is_array($rows) && isset($rows[1]) ? $rows[1] : null;
        $yes = isset($row['yes']) ? intval($row['yes']) : 0;
        $maybe = isset($row['maybe']) ? intval($row['maybe']) : 0;
        $no = isset($row['no']) ? intval($row['no']) : 0;
        $pending = isset($row['pending']) ? intval($row['pending']) : 0;
        $total = $yes + $maybe + $no + $pending;
        return ['yes' => $yes, 'maybe' => $maybe, 'no' => $no, 'pending' => $pending, 'total' => $total];
    }
}
