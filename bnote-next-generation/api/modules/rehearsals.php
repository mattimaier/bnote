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
require_once BNOTE_ROOT . '/src/data/modules/probendata.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/locationsdata.php';
require_once BNOTE_ROOT . '/src/data/modules/gruppendata.php';
require_once BNOTE_ROOT . '/src/data/modules/repertoiredata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class RehearsalsModule {
    private $data;
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        // Module permission is not required for read access (GET rehearsal).
        // Write actions (getMeta, update) check permission in handle().
        $this->data = new ProbenData();
    }
    
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? $_POST['action'] ?? null;
        $id = $_GET['id'] ?? null;
        
        // If no action specified but ID is provided, treat as GET request
        if ($method === 'GET' && $id && ($action === null || $action === '')) {
            return $this->getRehearsal($id);
        }
        
        if ($action === 'list') {
            return $this->listRehearsals();
        }
        
        if ($action === 'meta') {
            $this->requireRehearsalsModulePermission();
            return $this->getMeta();
        }

        if ($action === 'update') {
            $this->requireRehearsalsModulePermission();
            return $this->updateRehearsal();
        }

        // Handle explicit actions if needed in the future
        if ($action) {
            Response::error('Unknown action: ' . $action, 400);
        }
        
        Response::error('Method not supported or missing ID', 400);
    }
    
    /**
     * List rehearsals the current user can see (future only, access-controlled).
     */
    private function listRehearsals() {
        $userId = Auth::getUserId();
        $rehearsals = $this->getAccessibleRehearsals($userId);
        $list = [];
        if (is_array($rehearsals)) {
            for ($i = 1; $i < count($rehearsals); $i++) {
                $r = $rehearsals[$i];
                $participationStats = $this->getParticipationStatsForRehearsal($r['id'] ?? null);
                $list[] = [
                    'id' => intval($r['id']),
                    'begin' => $r['begin'] ?? '',
                    'end' => $r['end'] ?? '',
                    'approve_until' => $r['approve_until'] ?? '',
                    'location_name' => $r['location_name'] ?? '',
                    'notes' => $r['notes'] ?? '',
                    'status' => $r['status'] ?? '',
                    'conductor' => isset($r['conductor']) ? intval($r['conductor']) : null,
                    'participationStats' => $participationStats
                ];
            }
        }
        return $list;
    }

    private function getAccessibleRehearsals($userId) {
        global $system_data;
        $uid = intval($userId);
        if ($system_data->isUserSuperUser($uid)) {
            $query = "SELECT r.id, r.begin, r.end, r.approve_until, r.conductor, r.notes, r.status, l.name as location_name
                      FROM rehearsal r
                      JOIN location l ON r.location = l.id
                      ORDER BY r.begin DESC";
            return $system_data->dbcon->getSelection($query);
        }

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
        $query = "SELECT r.id, r.begin, r.end, r.approve_until, r.conductor, r.notes, r.status, l.name as location_name
                  FROM rehearsal r
                  JOIN location l ON r.location = l.id
                  WHERE r.id IN ($placeholders)
                  ORDER BY r.begin DESC";
        return $system_data->dbcon->getSelection($query, $params);
    }

    private function getParticipationStatsForRehearsal($rehearsalId) {
        global $system_data;
        if (!$rehearsalId || !is_numeric($rehearsalId)) {
            return [
                'yes' => 0,
                'maybe' => 0,
                'no' => 0,
                'pending' => 0,
                'total' => 0
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
        $rows = $system_data->dbcon->getSelection($query, [['i', $rid], ['i', $rid]]);
        $row = is_array($rows) && isset($rows[1]) ? $rows[1] : null;
        $yes = isset($row['yes']) ? intval($row['yes']) : 0;
        $maybe = isset($row['maybe']) ? intval($row['maybe']) : 0;
        $no = isset($row['no']) ? intval($row['no']) : 0;
        $pending = isset($row['pending']) ? intval($row['pending']) : 0;
        $total = $yes + $maybe + $no + $pending;
        return [
            'yes' => $yes,
            'maybe' => $maybe,
            'no' => $no,
            'pending' => $pending,
            'total' => $total
        ];
    }
    
    /**
     * Require Rehearsals (Proben) module permission for write operations.
     * Users without the module still have read access to rehearsals they are allowed to see.
     */
    private function requireRehearsalsModulePermission() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Proben');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Rehearsals', 403);
        }
    }
    
    /**
     * Get single rehearsal with full details including participants grouped by instruments
     * GET /api/index.php?module=rehearsals&id={id}
     */
    private function getRehearsal($id) {
        global $system_data;
        
        // Validate ID
        if (!is_numeric($id)) {
            Response::error('Invalid rehearsal ID', 400);
        }
        $id = intval($id);
        
        // Get rehearsal data
        $rehearsal = $this->data->findByIdNoRef($id);
        if (!$rehearsal) {
            Response::error('Rehearsal not found', 404);
        }
        
        // Check user access (similar to dashboard logic)
        $userId = Auth::getUserId();
        if (!$this->userHasAccessToRehearsal($id, $userId)) {
            Response::error('Access denied to this rehearsal', 403);
        }
        
        $moduleId = $system_data->getModuleId('Proben');
        $canEdit = $moduleId ? $system_data->userHasPermission($moduleId) : false;

        // Get location with address
        $location = null;
        if ($rehearsal['location']) {
            $locData = new LocationsData();
            $loc = $locData->findByIdNoRef($rehearsal['location']);
            if ($loc) {
                $address = $locData->getAddress($loc['address']);
                $location = [
                    'id' => intval($loc['id']),
                    'name' => $loc['name'],
                    'address' => [
                        'street' => $address['street'] ?? null,
                        'city' => $address['city'] ?? null,
                        'zip' => $address['zip'] ?? null,
                        'state' => $address['state'] ?? null,
                        'country' => $address['country'] ?? null
                    ]
                ];
            }
        }
        
        // Get conductor
        $conductor = null;
        if ($rehearsal['conductor'] && $rehearsal['conductor'] > 0) {
            $conductorName = $this->data->adp()->getConductorname($rehearsal['conductor']);
            $conductor = [
                'id' => intval($rehearsal['conductor']),
                'name' => $conductorName
            ];
        }
        
        // Get songs/pieces to practice
        $songs = $this->data->getSongsForRehearsal($id);
        unset($songs[0]); // Remove header
        $songsToPractice = [];
        foreach ($songs as $song) {
            $songsToPractice[] = [
                'id' => intval($song['id']),
                'title' => $song['title'],
                'notes' => $song['notes'] ?? null
            ];
        }

        // Get groups
        $groups = $this->data->getRehearsalGroups($id);
        unset($groups[0]); // Remove header
        $groupList = [];
        foreach ($groups as $group) {
            $groupList[] = [
                'id' => intval($group['id']),
                'name' => $group['name'] ?? null
            ];
        }

        // Get event contacts
        $eventContacts = [];
        $contacts = $this->data->getRehearsalContacts($id);
        unset($contacts[0]); // Remove header
        foreach ($contacts as $contact) {
            $eventContacts[] = [
                'id' => intval($contact['id']),
                'name' => $contact['name'] ?? null
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
        $usedInstruments = $system_data->dbcon->getSelection($query, [['i', $id]]);
        unset($usedInstruments[0]); // Remove header
        
        // Group participants by instrument
        $participantsByInstrument = [];
        $totalStats = ['yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0];
        
        foreach ($usedInstruments as $instrument) {
            $instrumentId = $instrument['id'];
            
            // Custom query to get participants with reason and category info
            $partQuery = "SELECT i.id as instrument_id, i.name as instrument, i.category as category_id, c.name as category_name,
                         ct.id as contact_id, CONCAT(ct.name, ' ', ct.surname) as contactname, 
                         u.id as user_id, IFNULL(ru.participate, -1) as participate, ru.reason
                         FROM rehearsal_contact rc
                         JOIN contact ct ON rc.contact = ct.id
                         JOIN user u ON u.contact = ct.id
                         JOIN instrument i ON ct.instrument = i.id
                         LEFT JOIN category c ON i.category = c.id
                         LEFT OUTER JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
                         WHERE rc.rehearsal = ? AND ct.instrument = ?
                         ORDER BY instrument, contactname";
            $participants = $system_data->dbcon->getSelection($partQuery, [
                ['i', $id],
                ['i', $id],
                ['i', $instrumentId]
            ]);
            unset($participants[0]); // Remove header
            
            if (count($participants) > 0) {
                $instrumentParticipants = [];
                $instrumentStats = ['yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0];
                
                foreach ($participants as $participant) {
                    $participate = $participant['participate'];
                    if ($participate === null || $participate === '' || $participate < 0) {
                        $participate = null; // Pending
                        $instrumentStats['pending']++;
                        $totalStats['pending']++;
                    } else {
                        $participate = intval($participate);
                        if ($participate === 1) {
                            $instrumentStats['yes']++;
                            $totalStats['yes']++;
                        } elseif ($participate === 2) {
                            $instrumentStats['maybe']++;
                            $totalStats['maybe']++;
                        } else {
                            $instrumentStats['no']++;
                            $totalStats['no']++;
                        }
                    }
                    
                    $instrumentParticipants[] = [
                        'id' => intval($participant['contact_id']),
                        'userId' => intval($participant['user_id']),
                        'name' => $participant['contactname'],
                        'participate' => $participate,
                        'reason' => $participant['reason'] ?? null
                    ];
                }
                
                $participantsByInstrument[] = [
                    'instrument' => [
                        'id' => intval($instrumentId),
                        'name' => $instrument['name'],
                        'category' => [
                            'id' => intval($instrument['category_id'] ?? 0),
                            'name' => $instrument['category_name'] ?? 'Uncategorized'
                        ]
                    ],
                    'participants' => $instrumentParticipants,
                    'stats' => $instrumentStats
                ];
            }
        }
        
        // Format response
        $response = [
            'id' => intval($rehearsal['id']),
            'type' => 'R',
            'begin' => $rehearsal['begin'],
            'end' => $rehearsal['end'] ?? null,
            'approve_until' => $rehearsal['approve_until'] ?? null,
            'status' => $rehearsal['status'] ?? 'planned',
            'notes' => $rehearsal['notes'] ?? null,
            'location' => $location,
            'conductor' => $conductor,
            'songsToPractice' => $songsToPractice,
            'groups' => $groupList,
            'eventContacts' => $eventContacts,
            'canEdit' => $canEdit,
            'canEditParticipation' => $canEdit,
            'participantsByInstrument' => $participantsByInstrument,
            'participationStats' => [
                'yes' => $totalStats['yes'],
                'maybe' => $totalStats['maybe'],
                'no' => $totalStats['no'],
                'pending' => $totalStats['pending'],
                'total' => $totalStats['yes'] + $totalStats['maybe'] + $totalStats['no'] + $totalStats['pending']
            ]
        ];
        
        return $response;
    }

    private function getMeta() {
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
                'id' => intval($locationsSel[$i]['id']),
                'name' => $locationsSel[$i]['name'] ?? null
            ];
        }

        $groups = [];
        for ($i = 1; $i < count($groupsSel); $i++) {
            $groups[] = [
                'id' => intval($groupsSel[$i]['id']),
                'name' => $groupsSel[$i]['name'] ?? null
            ];
        }

        $songs = [];
        for ($i = 1; $i < count($songsSel); $i++) {
            $songs[] = [
                'id' => intval($songsSel[$i]['id']),
                'title' => urldecode($songsSel[$i]['title'] ?? '')
            ];
        }

        $conductors = [];
        for ($i = 1; $i < count($conductorsSel); $i++) {
            $conductors[] = [
                'id' => intval($conductorsSel[$i]['id']),
                'name' => trim(($conductorsSel[$i]['name'] ?? '') . ' ' . ($conductorsSel[$i]['surname'] ?? ''))
            ];
        }

        $contacts = [];
        for ($i = 1; $i < count($contactsSel); $i++) {
            $instrumentName = null;
            $instrumentId = $contactsSel[$i]['instrument'] ?? null;
            if ($instrumentId && $instrumentId > 0) {
                $instrumentName = $system_data->dbcon->colValue(
                    "SELECT name FROM instrument WHERE id = ?",
                    "name",
                    [['i', $instrumentId]]
                );
            }
            $contacts[] = [
                'id' => intval($contactsSel[$i]['id']),
                'name' => $contactsSel[$i]['fullname'] ?? trim(($contactsSel[$i]['name'] ?? '') . ' ' . ($contactsSel[$i]['surname'] ?? '')),
                'subtitle' => $instrumentName
            ];
        }

        $groupMembers = [];
        $groupMembersSel = $system_data->dbcon->getSelection(
            "SELECT `group` as group_id, contact as contact_id FROM contact_group",
            []
        );
        unset($groupMembersSel[0]);
        foreach ($groupMembersSel as $row) {
            $groupId = intval($row['group_id'] ?? 0);
            $contactId = intval($row['contact_id'] ?? 0);
            if ($groupId <= 0 || $contactId <= 0) {
                continue;
            }
            if (!array_key_exists(strval($groupId), $groupMembers)) {
                $groupMembers[strval($groupId)] = [];
            }
            $groupMembers[strval($groupId)][] = $contactId;
        }

        return [
            'locations' => $locations,
            'groups' => $groups,
            'songs' => $songs,
            'conductors' => $conductors,
            'contacts' => $contacts,
            'statusOptions' => $this->data->getStatusOptions(),
            'groupMembers' => $groupMembers
        ];
    }

    private function updateRehearsal() {
        global $system_data;

        $payload = $this->getRequestData();
        $id = $payload['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Invalid rehearsal ID', 400);
        }
        $id = intval($id);

        $userId = Auth::getUserId();
        if (!$this->userHasAccessToRehearsal($id, $userId)) {
            Response::error('Access denied to this rehearsal', 403);
        }

        $fields = $payload['fields'] ?? [];
        $values = [
            'begin' => $fields['begin'] ?? '',
            'end' => $fields['end'] ?? '',
            'approve_until' => $fields['approve_until'] ?? '',
            'status' => $fields['status'] ?? 'planned',
            'notes' => $fields['notes'] ?? '',
            'location' => $fields['location'] ?? 0,
            'conductor' => $fields['conductor'] ?? 0
        ];

        if (empty($values['approve_until']) && !empty($values['begin'])) {
            $values['approve_until'] = $values['begin'];
        }

        $this->data->validate($values);
        $this->data->update($id, $values);

        if (array_key_exists('groups', $payload)) {
            $groups = array_map('intval', $payload['groups'] ?? []);
            $system_data->dbcon->execute("DELETE FROM rehearsal_group WHERE rehearsal = ?", [['i', $id]]);
            if (count($groups) > 0) {
                $tuples = [];
                $params = [];
                foreach ($groups as $groupId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $id];
                    $params[] = ['i', $groupId];
                }
                $query = "INSERT INTO rehearsal_group (rehearsal, `group`) VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }
        }

        if (array_key_exists('contacts', $payload)) {
            $contacts = array_map('intval', $payload['contacts'] ?? []);
            $system_data->dbcon->execute("DELETE FROM rehearsal_contact WHERE rehearsal = ?", [['i', $id]]);
            if (count($contacts) > 0) {
                $tuples = [];
                $params = [];
                foreach ($contacts as $contactId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $id];
                    $params[] = ['i', $contactId];
                }
                $query = "INSERT INTO rehearsal_contact VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }

            if (count($contacts) > 0) {
                $placeholders = implode(",", array_fill(0, count($contacts), "?"));
                $params = [['i', $id]];
                foreach ($contacts as $contactId) {
                    $params[] = ['i', $contactId];
                }
                $query = "DELETE ru FROM rehearsal_user ru JOIN user u ON ru.user = u.id WHERE ru.rehearsal = ? AND u.contact NOT IN ($placeholders)";
                $system_data->dbcon->execute($query, $params);
            } else {
                $system_data->dbcon->execute("DELETE FROM rehearsal_user WHERE rehearsal = ?", [['i', $id]]);
            }
        }

        if (array_key_exists('songs', $payload)) {
            $songs = $payload['songs'] ?? [];
            $system_data->dbcon->execute("DELETE FROM rehearsal_song WHERE rehearsal = ?", [['i', $id]]);
            if (count($songs) > 0) {
                $tuples = [];
                $params = [];
                foreach ($songs as $song) {
                    $songId = intval($song['id'] ?? 0);
                    if ($songId <= 0) continue;
                    $tuples[] = "(?, ?, ?)";
                    $params[] = ['i', $songId];
                    $params[] = ['i', $id];
                    $params[] = ['s', $song['notes'] ?? ''];
                }
                if (count($tuples) > 0) {
                    $query = "INSERT INTO rehearsal_song (song, rehearsal, notes) VALUES " . join(",", $tuples);
                    $system_data->dbcon->execute($query, $params);
                }
            }
        }

        if (array_key_exists('participants', $payload)) {
            $participants = $payload['participants'] ?? [];
            foreach ($participants as $participant) {
                $userId = intval($participant['userId'] ?? 0);
                if ($userId <= 0) continue;
                $participate = $participant['participate'] ?? null;
                if ($participate === null || $participate === '') {
                    $system_data->dbcon->execute(
                        "DELETE FROM rehearsal_user WHERE rehearsal = ? AND user = ?",
                        [['i', $id], ['i', $userId]]
                    );
                    continue;
                }
                $participate = intval($participate);
                $exists = $system_data->dbcon->colValue(
                    "SELECT count(*) as cnt FROM rehearsal_user WHERE rehearsal = ? AND user = ?",
                    "cnt",
                    [['i', $id], ['i', $userId]]
                );
                if (intval($exists) > 0) {
                    $system_data->dbcon->execute(
                        "UPDATE rehearsal_user SET participate = ? WHERE rehearsal = ? AND user = ?",
                        [['i', $participate], ['i', $id], ['i', $userId]]
                    );
                } else {
                    $system_data->dbcon->execute(
                        "INSERT INTO rehearsal_user (rehearsal, user, participate, replyon) VALUES (?, ?, ?, NOW())",
                        [['i', $id], ['i', $userId], ['i', $participate]]
                    );
                }
            }
        }

        return ['success' => true];
    }

    private function getRequestData() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        return $data;
    }
    
    /**
     * Check if user has access to a rehearsal
     * Allows access to both past and future rehearsals
     */
    private function userHasAccessToRehearsal($rehearsalId, $userId) {
        global $system_data;
        
        $rehearsalId = intval($rehearsalId);
        
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
            $rehearsalIds = array_merge(
                $this->getRehearsalsForUser($userId),
                $this->getRehearsalsForPhases($usersPhases)
            );
            
            $rehearsalIds = array_map('intval', array_unique($rehearsalIds));
            return in_array($rehearsalId, $rehearsalIds);
        } catch (Exception $e) {
            error_log("Error checking rehearsal access: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get rehearsals for user (from contact)
     */
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
    
    /**
     * Get rehearsals for phases
     */
    private function getRehearsalsForPhases($phases) {
        if (count($phases) == 0) return [];
        
        global $system_data;
        $params = [];
        $whereQ = [];
        foreach ($phases as $p) {
            $whereQ[] = 'rehearsalphase = ?';
            $params[] = ['i', $p];
        }
        $query = 'SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ);
        $sel = $system_data->dbcon->getSelection($query, $params);
        return Database::flattenSelection($sel, 'rehearsal');
    }
}
