<?php
/**
 * BNote Next Generation - Concerts API Module
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
 * Concerts API module
 * Handles concert detail endpoints with participants grouped by instruments and all metadata
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
// Use BNOTE_ROOT constant from paths.php (loaded by api/index.php)
require_once BNOTE_ROOT . '/src/data/modules/konzertedata.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/locationsdata.php';
require_once BNOTE_ROOT . '/src/data/modules/gruppendata.php';
require_once BNOTE_ROOT . '/src/data/modules/programdata.php';
require_once BNOTE_ROOT . '/src/data/modules/outfitsdata.php';
require_once BNOTE_ROOT . '/src/data/modules/equipmentdata.php';
require_once BNOTE_ROOT . '/src/data/modules/kontaktedata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../text_normalizer.php';
require_once __DIR__ . '/../mail/EventParticipantNotifier.php';
require_once __DIR__ . '/../mail/EventInfoMailService.php';

class ConcertsModule {
    private $data;
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        // Module permission is not required for read access (GET concert).
        // Write actions (getMeta, update) check permission in handle().
        $this->data = new KonzerteData();
    }
    
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? $_POST['action'] ?? null;
        $id = $_GET['id'] ?? null;
        
        // If no action specified but ID is provided, treat as GET request
        if ($method === 'GET' && $id && ($action === null || $action === '')) {
            return $this->normalizeResponse($this->getConcert($id), 'get');
        }
        
        if ($action === 'list') {
            return $this->normalizeResponse($this->listConcerts(), $action);
        }
        
        if ($action === 'meta') {
            $this->requireConcertsModulePermission();
            return $this->normalizeResponse($this->getMeta(), $action);
        }

        if ($action === 'update') {
            $this->requireConcertsModulePermission();
            return $this->normalizeResponse($this->updateConcert(), $action);
        }
        
        if ($action === 'create') {
            $this->requireConcertsModulePermission();
            return $this->normalizeResponse($this->createConcert(), $action);
        }

        if ($action === 'delete') {
            $this->requireConcertsModulePermission();
            return $this->normalizeResponse($this->deleteConcert(), $action);
        }

        if ($action === 'emailInfoDraft') {
            $this->requireConcertsModulePermission();
            return $this->normalizeResponse($this->emailInfoDraft(), $action);
        }

        if ($action === 'emailInfoPreview') {
            $this->requireConcertsModulePermission();
            return $this->emailInfoPreview();
        }

        if ($action === 'emailInfoSend') {
            $this->requireConcertsModulePermission();
            return $this->normalizeResponse($this->emailInfoSend(), $action);
        }

        // Handle explicit actions if needed in the future
        if ($action) {
            Response::error('Unknown action: ' . $action, 400);
        }
        
        Response::error('Method not supported or missing ID', 400);
    }

    private function normalizeResponse($payload, $action) {
        $stats = ['count' => 0, 'samples' => []];
        $normalized = TextNormalizer::normalizeAllStringsRecursive($payload, $stats, true);
        TextNormalizer::logStats('concerts', $action, $stats);
        return $normalized;
    }
    
    /**
     * List concerts the current user can see (future only, access-controlled via adp).
     */
    private function listConcerts() {
        $userId = Auth::getUserId();
        $concerts = $this->getAccessibleConcerts($userId);
        $list = [];
        if (is_array($concerts)) {
            for ($i = 1; $i < count($concerts); $i++) {
                $c = $concerts[$i];
                $participationStats = $this->getParticipationStatsForConcert($c['id'] ?? null);
                $list[] = [
                    'id' => intval($c['id']),
                    'title' => $c['title'] ?? '',
                    'begin' => $c['begin'] ?? '',
                    'end' => $c['end'] ?? '',
                    'approve_until' => $c['approve_until'] ?? '',
                    'location_name' => $c['location_name'] ?? '',
                    'notes' => $c['notes'] ?? '',
                    'status' => $c['status'] ?? '',
                    'participationStats' => $participationStats
                ];
            }
        }
        return $list;
    }

    private function getAccessibleConcerts($userId) {
        global $system_data;
        $uid = intval($userId);
        if ($system_data->isUserSuperUser($uid)) {
            $query = "SELECT c.id, c.title, c.begin, c.end, c.approve_until, c.notes, c.status, l.name as location_name
                      FROM concert c
                      LEFT JOIN location l ON c.location = l.id
                      ORDER BY c.begin DESC";
            return $system_data->dbcon->getSelection($query);
        }

        $phases = $this->data->adp()->getUsersPhases($uid);
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

        $contactId = $this->data->adp()->getUserContact($uid);
        $params[] = ['i', $contactId];

        $query = "SELECT DISTINCT c.id, c.title, c.begin, c.end, c.approve_until, c.notes, c.status, l.name as location_name
                  FROM concert c
                  LEFT JOIN location l ON c.location = l.id
                  JOIN (
                    $phaseQuery
                    UNION ALL
                    SELECT concert FROM concert_contact WHERE contact = ?
                  ) AS concerts ON c.id = concerts.concert
                  ORDER BY c.begin DESC";
        return $system_data->dbcon->getSelection($query, $params);
    }

    private function getParticipationStatsForConcert($concertId) {
        global $system_data;
        if (!$concertId || !is_numeric($concertId)) {
            return [
                'yes' => 0,
                'maybe' => 0,
                'no' => 0,
                'pending' => 0,
                'total' => 0
            ];
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
        return [
            'yes' => $yes,
            'maybe' => $maybe,
            'no' => $no,
            'pending' => $pending,
            'total' => $total
        ];
    }
    
    /**
     * Require Concerts (Konzerte) module permission for write operations.
     * Users without the module still have read access to concerts they are allowed to see.
     */
    private function requireConcertsModulePermission() {
        $userId = Auth::getUserId();
        if (!$this->canManageConcertParticipation($userId)) {
            Response::error('Access denied to Concerts', 403);
        }
    }

    private function hasConcertsModulePermission() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Konzerte');
        return $moduleId ? $system_data->userHasPermission($moduleId) : false;
    }

    private function canManageConcertParticipation($userId) {
        global $system_data;
        $uid = intval($userId);
        return $system_data->isUserSuperUser($uid)
            || $system_data->isUserMemberGroup(1, $uid)
            || $this->hasConcertsModulePermission();
    }

    private function isUserInvitedToConcert($concertId, $userId) {
        global $system_data;
        $cid = intval($concertId);
        $uid = intval($userId);
        if ($cid <= 0 || $uid <= 0) {
            return false;
        }

        $count = $system_data->dbcon->colValue(
            "SELECT COUNT(*) AS cnt
             FROM concert_contact cc
             JOIN user u ON u.contact = cc.contact
             WHERE cc.concert = ? AND u.id = ?",
            "cnt",
            [['i', $cid], ['i', $uid]]
        );
        return intval($count) > 0;
    }
    
    /**
     * Get single concert with full details including participants grouped by instruments
     * GET /api/index.php?module=concerts&id={id}
     */
    private function getConcert($id) {
        global $system_data;
        
        // Validate ID
        if (!is_numeric($id)) {
            Response::error('Invalid concert ID', 400);
        }
        $id = intval($id);
        
        // Get concert data
        $concert = $this->data->getConcert($id);
        if (!$concert) {
            Response::error('Concert not found', 404);
        }
        
        // Check user access
        $userId = Auth::getUserId();
        if (!$this->userHasAccessToConcert($id, $userId)) {
            Response::error('Access denied to this concert', 403);
        }
        
        $canEdit = $this->canManageConcertParticipation($userId);
        $canEditParticipation = $this->canManageConcertParticipation($userId);

        // Get location with address
        $location = null;
        if ($concert['location']) {
            $locData = new LocationsData();
            $loc = $locData->findByIdNoRef($concert['location']);
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
        
        // Get contact
        $contact = null;
        if ($concert['contact'] && $concert['contact'] > 0) {
            $contactData = $this->data->getContact($concert['contact']);
            if ($contactData) {
                $firstName = $contactData['name'] ?? null;
                $lastName = $contactData['surname'] ?? null;
                $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
                $contact = [
                    'id' => intval($concert['contact']),
                    'name' => $contactData['name'] ?? null,
                    'firstname' => $firstName,
                    'surname' => $lastName,
                    'fullname' => $fullName !== '' ? $fullName : ($contactData['name'] ?? null),
                    'phone' => $contactData['phone'] ?? null,
                    'mobile' => $contactData['mobile'] ?? null,
                    'email' => $contactData['email'] ?? null
                ];
            }
        }
        
        // Get program
        $program = null;
        if ($concert['program'] && $concert['program'] > 0) {
            $programData = $this->data->getProgram($concert['program']);
            if ($programData) {
                $program = [
                    'id' => intval($concert['program']),
                    'name' => $programData['name'] ?? null,
                    'notes' => $programData['notes'] ?? null
                ];
            }
        }
        
        // Get outfit
        $outfit = null;
        if ($concert['outfit'] && $concert['outfit'] > 0) {
            $outfitData = $this->data->getOutfit($concert['outfit']);
            if ($outfitData) {
                $outfit = [
                    'id' => intval($concert['outfit']),
                    'name' => $outfitData['name'] ?? null
                ];
            }
        }
        
        // Get equipment
        $equipment = [];
        $equipmentData = $this->data->getConcertEquipment($id);
        unset($equipmentData[0]); // Remove header
        foreach ($equipmentData as $eq) {
            $equipment[] = [
                'id' => intval($eq['id']),
                'name' => $eq['name'] ?? null
            ];
        }
        
        // Get groups (occupation/besetzung)
        $groups = [];
        $groupsData = $this->data->getConcertGroups($id);
        unset($groupsData[0]); // Remove header
        foreach ($groupsData as $group) {
            $groups[] = [
                'id' => intval($group['id']),
                'name' => $group['name'] ?? null
            ];
        }

        // Get event contacts
        $eventContacts = [];
        $contacts = $this->data->getConcertContacts($id);
        unset($contacts[0]); // Remove header
        foreach ($contacts as $contactRow) {
            $eventContacts[] = [
                'id' => intval($contactRow['id']),
                'name' => $contactRow['fullname'] ?? null
            ];
        }
        
        // Get accommodation
        $accommodation = null;
        if ($concert['accommodation'] && $concert['accommodation'] > 0) {
            $accData = $this->data->adp()->getAccommodationLocation($concert['accommodation']);
            if ($accData) {
                $accommodation = [
                    'id' => intval($concert['accommodation']),
                    'name' => $accData['name'] ?? null,
                    'address' => [
                        'street' => $accData['street'] ?? null,
                        'city' => $accData['city'] ?? null,
                        'zip' => $accData['zip'] ?? null,
                        'state' => $accData['state'] ?? null,
                        'country' => $accData['country'] ?? null
                    ]
                ];
            }
        }
        
        // Get all instruments used with category information
        global $system_data;
        $query = "SELECT DISTINCT i.id, i.name, i.rank, i.category as category_id, c.name as category_name 
                  FROM instrument i 
                  JOIN contact ct ON ct.instrument = i.id
                  JOIN concert_contact cc ON cc.contact = ct.id
                  LEFT JOIN category c ON i.category = c.id
                  WHERE cc.concert = ?
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
                         ct.id as contact_id, CONCAT(ct.name, ' ', ct.surname) as contactname, ct.email as contact_email,
                         u.id as user_id, IFNULL(cu.participate, -1) as participate, cu.reason
                         FROM concert_contact cc
                         JOIN contact ct ON cc.contact = ct.id
                         JOIN user u ON u.contact = ct.id
                         JOIN instrument i ON ct.instrument = i.id
                         LEFT JOIN category c ON i.category = c.id
                         LEFT OUTER JOIN concert_user cu ON cu.user = u.id AND cu.concert = ?
                         WHERE cc.concert = ? AND ct.instrument = ?
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
                        'email' => $participant['contact_email'] ?? null,
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
            'id' => intval($concert['id']),
            'type' => 'C',
            'title' => $concert['title'] ?? null,
            'begin' => $concert['begin'],
            'end' => $concert['end'] ?? null,
            'meetingtime' => $concert['meetingtime'] ?? null,
            'approve_until' => $concert['approve_until'] ?? null,
            'status' => $concert['status'] ?? 'planned',
            'notes' => $concert['notes'] ?? null,
            'organizer' => $concert['organizer'] ?? null,
            'payment' => $concert['payment'] ?? null ? floatval($concert['payment']) : null,
            'conditions' => $concert['conditions'] ?? null,
            'location' => $location,
            'contact' => $contact,
            'program' => $program,
            'outfit' => $outfit,
            'equipment' => $equipment,
            'groups' => $groups,
            'accommodation' => $accommodation,
            'participantsByInstrument' => $participantsByInstrument,
            'eventContacts' => $eventContacts,
            'canEdit' => $canEdit,
            'canEditParticipation' => $canEditParticipation,
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
        $programData = new ProgramData();
        $outfitData = new OutfitsData();
        $equipmentData = new EquipmentData();
        $contactData = new KontakteData();

        $locationsSel = $locationsData->findAllNoRef();
        $groupsSel = $groupData->findAllNoRef();
        $programsSel = $programData->findAllNoRef();
        $outfitsSel = $outfitData->findAllNoRef();
        $equipmentSel = $equipmentData->findAllNoRef();
        $contactsSel = $contactData->getAllContacts();

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

        $programs = [];
        for ($i = 1; $i < count($programsSel); $i++) {
            $programs[] = [
                'id' => intval($programsSel[$i]['id']),
                'name' => $programsSel[$i]['name'] ?? null
            ];
        }

        $outfits = [];
        for ($i = 1; $i < count($outfitsSel); $i++) {
            $outfits[] = [
                'id' => intval($outfitsSel[$i]['id']),
                'name' => $outfitsSel[$i]['name'] ?? null
            ];
        }

        $equipment = [];
        for ($i = 1; $i < count($equipmentSel); $i++) {
            $equipment[] = [
                'id' => intval($equipmentSel[$i]['id']),
                'name' => $equipmentSel[$i]['name'] ?? null
            ];
        }

        $contacts = [];
        for ($i = 1; $i < count($contactsSel); $i++) {
            $c = $contactsSel[$i];
            $instrumentName = null;
            $instrumentId = $c['instrument'] ?? null;
            if ($instrumentId && $instrumentId > 0) {
                $instrumentName = $system_data->dbcon->colValue(
                    "SELECT name FROM instrument WHERE id = ?",
                    "name",
                    [['i', $instrumentId]]
                );
            }
            $contacts[] = [
                'id' => intval($c['id']),
                'name' => trim(($c['name'] ?? '') . ' ' . ($c['surname'] ?? '')),
                'subtitle' => $instrumentName,
                'email' => $c['email'] ?? null,
                'instrument' => $instrumentName,
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
            'programs' => $programs,
            'outfits' => $outfits,
            'equipment' => $equipment,
            'contacts' => $contacts,
            'statusOptions' => $this->data->getStatusOptions(),
            'groupMembers' => $groupMembers
        ];
    }

    private function updateConcert() {
        global $system_data;

        $payload = $this->getRequestData();
        $id = $payload['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Invalid concert ID', 400);
        }
        $id = intval($id);

        $userId = Auth::getUserId();
        if (!$this->userHasAccessToConcert($id, $userId)) {
            Response::error('Access denied to this concert', 403);
        }

        $fields = $payload['fields'] ?? [];
        $values = [
            'title' => $fields['title'] ?? '',
            'begin' => $fields['begin'] ?? '',
            'end' => $fields['end'] ?? '',
            'meetingtime' => $fields['meetingtime'] ?? '',
            'approve_until' => $fields['approve_until'] ?? '',
            'status' => $fields['status'] ?? 'planned',
            'notes' => $fields['notes'] ?? '',
            'organizer' => $fields['organizer'] ?? '',
            'payment' => $fields['payment'] ?? '',
            'conditions' => $fields['conditions'] ?? '',
            'location' => $fields['location'] ?? 0,
            'contact' => $fields['contact'] ?? 0,
            'program' => $fields['program'] ?? 0,
            'outfit' => $fields['outfit'] ?? 0,
            'accommodation' => $fields['accommodation'] ?? 0
        ];

        if ($values['payment'] === '' || $values['payment'] === null) {
            $values['payment'] = 0;
        }
        if (empty($values['meetingtime']) && empty($values['approve_until']) && !empty($values['begin'])) {
            $values['meetingtime'] = $values['begin'];
            $values['approve_until'] = $values['begin'];
        }
        if (empty($values['approve_until']) && !empty($values['begin'])) {
            $values['approve_until'] = $values['begin'];
        }

        // Legacy KonzertData::validate uses Regex::isText() which rejects " and \ (EditorJS JSON).
        // Validate with notes/conditions cleared, then restore so update() stores the real values.
        $notesBackup = $values['notes'];
        $conditionsBackup = $values['conditions'];
        $values['notes'] = '';
        $values['conditions'] = '';
        $this->data->validate($values);
        $values['notes'] = $notesBackup;
        $values['conditions'] = $conditionsBackup;
        $this->data->update($id, $values);

        if (array_key_exists('groups', $payload)) {
            $groups = array_map('intval', $payload['groups'] ?? []);
            $system_data->dbcon->execute("DELETE FROM concert_group WHERE concert = ?", [['i', $id]]);
            if (count($groups) > 0) {
                $tuples = [];
                $params = [];
                foreach ($groups as $groupId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $id];
                    $params[] = ['i', $groupId];
                }
                $query = "INSERT INTO concert_group (concert, `group`) VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }
        }

        if (array_key_exists('equipment', $payload)) {
            $equipment = array_map('intval', $payload['equipment'] ?? []);
            $system_data->dbcon->execute("DELETE FROM concert_equipment WHERE concert = ?", [['i', $id]]);
            if (count($equipment) > 0) {
                $tuples = [];
                $params = [];
                foreach ($equipment as $equipmentId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $id];
                    $params[] = ['i', $equipmentId];
                }
                $query = "INSERT INTO concert_equipment (concert, `equipment`) VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }
        }

        if (array_key_exists('contacts', $payload)) {
            $previousContactIds = $this->concertContactIds($id);
            $contacts = array_map('intval', $payload['contacts'] ?? []);
            $system_data->dbcon->execute("DELETE FROM concert_contact WHERE concert = ?", [['i', $id]]);
            if (count($contacts) > 0) {
                $tuples = [];
                $params = [];
                foreach ($contacts as $contactId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $id];
                    $params[] = ['i', $contactId];
                }
                $query = "INSERT INTO concert_contact VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }

            if (count($contacts) > 0) {
                $placeholders = implode(",", array_fill(0, count($contacts), "?"));
                $params = [['i', $id]];
                foreach ($contacts as $contactId) {
                    $params[] = ['i', $contactId];
                }
                $query = "DELETE cu FROM concert_user cu JOIN user u ON cu.user = u.id WHERE cu.concert = ? AND u.contact NOT IN ($placeholders)";
                $system_data->dbcon->execute($query, $params);
            } else {
                $system_data->dbcon->execute("DELETE FROM concert_user WHERE concert = ?", [['i', $id]]);
            }

            $addedContacts = array_values(array_diff($contacts, $previousContactIds));
            if (count($addedContacts) > 0) {
                EventParticipantNotifier::sendSafe($system_data, new StartData(), 'C', $id, $addedContacts);
            }
        }

        if (array_key_exists('participants', $payload)) {
            $participants = $payload['participants'] ?? [];
            foreach ($participants as $participant) {
                $userId = intval($participant['userId'] ?? 0);
                if ($userId <= 0) continue;
                if (!$this->isUserInvitedToConcert($id, $userId)) continue;
                $participate = $participant['participate'] ?? null;
                if ($participate === null || $participate === '') {
                    $system_data->dbcon->execute(
                        "DELETE FROM concert_user WHERE concert = ? AND user = ?",
                        [['i', $id], ['i', $userId]]
                    );
                    continue;
                }
                $participate = intval($participate);
                $exists = $system_data->dbcon->colValue(
                    "SELECT count(*) as cnt FROM concert_user WHERE concert = ? AND user = ?",
                    "cnt",
                    [['i', $id], ['i', $userId]]
                );
                if (intval($exists) > 0) {
                    $system_data->dbcon->execute(
                        "UPDATE concert_user SET participate = ? WHERE concert = ? AND user = ?",
                        [['i', $participate], ['i', $id], ['i', $userId]]
                    );
                } else {
                    $system_data->dbcon->execute(
                        "INSERT INTO concert_user (participate, user, concert, replyon) VALUES (?, ?, ?, NOW())",
                        [['i', $participate], ['i', $userId], ['i', $id]]
                    );
                }
            }
        }

        return ['success' => true];
    }

    private function createConcert() {
        global $system_data;

        $payload = $this->getRequestData();
        $fields = $payload['fields'] ?? [];
        $values = [
            'title' => $fields['title'] ?? '',
            'begin' => $fields['begin'] ?? '',
            'end' => $fields['end'] ?? '',
            'meetingtime' => $fields['meetingtime'] ?? '',
            'approve_until' => $fields['approve_until'] ?? '',
            'status' => $fields['status'] ?? 'planned',
            'notes' => $fields['notes'] ?? '',
            'organizer' => $fields['organizer'] ?? '',
            'payment' => $fields['payment'] ?? '',
            'conditions' => $fields['conditions'] ?? '',
            'location' => $fields['location'] ?? 0,
            'contact' => $fields['contact'] ?? 0,
            'program' => $fields['program'] ?? 0,
            'outfit' => $fields['outfit'] ?? 0,
            'accommodation' => $fields['accommodation'] ?? 0
        ];

        if ($values['payment'] === '' || $values['payment'] === null) {
            $values['payment'] = 0;
        }
        if (empty($values['meetingtime']) && empty($values['approve_until']) && !empty($values['begin'])) {
            $values['meetingtime'] = $values['begin'];
            $values['approve_until'] = $values['begin'];
        }
        if (empty($values['approve_until']) && !empty($values['begin'])) {
            $values['approve_until'] = $values['begin'];
        }

        // Legacy KonzertData::validate uses Regex::isText() which rejects " and \ (EditorJS JSON).
        // Validate with notes/conditions cleared, then restore so insert stores the real values.
        $notesBackup = $values['notes'];
        $conditionsBackup = $values['conditions'];
        $values['notes'] = '';
        $values['conditions'] = '';
        $this->data->validate($values);
        $values['notes'] = $notesBackup;
        $values['conditions'] = $conditionsBackup;

        $newId = $system_data->dbcon->prepStatement(
            "INSERT INTO concert (title, begin, end, meetingtime, approve_until, status, notes, organizer, payment, conditions, location, contact, program, outfit, accommodation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                ['s', $values['title']],
                ['s', $values['begin']],
                ['s', $values['end']],
                ['s', $values['meetingtime']],
                ['s', $values['approve_until']],
                ['s', $values['status']],
                ['s', $values['notes']],
                ['s', $values['organizer']],
                ['d', floatval($values['payment'])],
                ['s', $values['conditions']],
                ['i', intval($values['location'])],
                ['i', intval($values['contact'])],
                ['i', intval($values['program'])],
                ['i', intval($values['outfit'])],
                ['i', intval($values['accommodation'])],
            ]
        );
        if (!$newId || intval($newId) <= 0) {
            Response::error('Failed to create concert', 500);
        }
        $newId = intval($newId);

        if (array_key_exists('groups', $payload)) {
            $groups = array_map('intval', $payload['groups'] ?? []);
            if (count($groups) > 0) {
                $tuples = [];
                $params = [];
                foreach ($groups as $groupId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $newId];
                    $params[] = ['i', $groupId];
                }
                $query = "INSERT INTO concert_group (concert, `group`) VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }
        }

        if (array_key_exists('equipment', $payload)) {
            $equipment = array_map('intval', $payload['equipment'] ?? []);
            if (count($equipment) > 0) {
                $tuples = [];
                $params = [];
                foreach ($equipment as $equipmentId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $newId];
                    $params[] = ['i', $equipmentId];
                }
                $query = "INSERT INTO concert_equipment (concert, `equipment`) VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }
        }

        if (array_key_exists('contacts', $payload)) {
            $contacts = array_map('intval', $payload['contacts'] ?? []);
            if (count($contacts) > 0) {
                $tuples = [];
                $params = [];
                foreach ($contacts as $contactId) {
                    $tuples[] = "(?, ?)";
                    $params[] = ['i', $newId];
                    $params[] = ['i', $contactId];
                }
                $query = "INSERT INTO concert_contact VALUES " . join(",", $tuples);
                $system_data->dbcon->execute($query, $params);
            }
        }

        if (array_key_exists('participants', $payload)) {
            $participants = $payload['participants'] ?? [];
            foreach ($participants as $participant) {
                $userId = intval($participant['userId'] ?? 0);
                if ($userId <= 0) continue;
                if (!$this->isUserInvitedToConcert($newId, $userId)) continue;
                $participate = $participant['participate'] ?? null;
                if ($participate === null || $participate === '') continue;
                $participate = intval($participate);
                $system_data->dbcon->execute(
                    "INSERT INTO concert_user (participate, user, concert, replyon) VALUES (?, ?, ?, NOW())",
                    [['i', $participate], ['i', $userId], ['i', $newId]]
                );
            }
        }

        EventParticipantNotifier::sendSafe($system_data, new StartData(), 'C', $newId, null);

        return ['id' => $newId];
    }

    /** @return list<int> */
    private function concertContactIds(int $concertId): array {
        global $system_data;
        $sel = $system_data->dbcon->getSelection(
            'SELECT contact FROM concert_contact WHERE concert = ?',
            [['i', $concertId]]
        );
        if (!is_array($sel) || count($sel) < 2) {
            return [];
        }
        $ids = [];
        for ($i = 1; $i < count($sel); $i++) {
            $ids[] = (int) ($sel[$i]['contact'] ?? 0);
        }

        return $ids;
    }

    private function deleteConcert() {
        global $system_data;
        $payload = $this->getRequestData();
        $id = intval($payload['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid concert ID', 400);
        }

        $userId = Auth::getUserId();
        if (!$this->userHasAccessToConcert($id, $userId)) {
            Response::error('Access denied to this concert', 403);
        }

        $system_data->dbcon->execute("DELETE FROM concert_group WHERE concert = ?", [['i', $id]]);
        $system_data->dbcon->execute("DELETE FROM concert_contact WHERE concert = ?", [['i', $id]]);
        $system_data->dbcon->execute("DELETE FROM concert_equipment WHERE concert = ?", [['i', $id]]);
        $system_data->dbcon->execute("DELETE FROM concert_user WHERE concert = ?", [['i', $id]]);
        $system_data->dbcon->execute("DELETE FROM concert WHERE id = ?", [['i', $id]]);

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

    private function emailInfoDraft() {
        global $system_data;
        $payload = $this->getRequestData();
        $id = intval($payload['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid concert ID', 400);
        }
        $userId = Auth::getUserId();
        if (!$this->userHasAccessToConcert($id, $userId)) {
            Response::error('Access denied to this concert', 403);
        }
        $locale = trim((string) ($payload['locale'] ?? (method_exists($system_data, 'getLang') ? $system_data->getLang() : 'en')));
        $user = Auth::getUserInfo();
        $senderName = trim((string) (($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')));
        return EventInfoMailService::draft($system_data, 'C', $id, $locale !== '' ? $locale : 'en', $senderName);
    }

    private function emailInfoPreview() {
        global $system_data;
        $payload = $this->getRequestData();
        $id = intval($payload['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid concert ID', 400);
        }
        $userId = Auth::getUserId();
        if (!$this->userHasAccessToConcert($id, $userId)) {
            Response::error('Access denied to this concert', 403);
        }
        $locale = trim((string) ($payload['locale'] ?? (method_exists($system_data, 'getLang') ? $system_data->getLang() : 'en')));
        $user = Auth::getUserInfo();
        $senderName = trim((string) (($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')));
        $recipientIds = array_values(array_filter(array_map('intval', $payload['recipientIds'] ?? []), fn($value) => $value > 0));
        $manualEmails = array_values(array_filter(array_map('strval', $payload['manualEmails'] ?? []), fn($value) => trim($value) !== ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = (string) ($payload['body'] ?? '');
        try {
            $html = EventInfoMailService::previewHtml(
                $system_data,
                'C',
                $id,
                $locale !== '' ? $locale : 'en',
                $recipientIds,
                $manualEmails,
                $subject,
                $body,
                $senderName
            );
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 400);
        }
        return ['html' => $html];
    }

    private function emailInfoSend() {
        global $system_data;
        $payload = $this->getRequestData();
        $id = intval($payload['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Invalid concert ID', 400);
        }
        $userId = Auth::getUserId();
        if (!$this->userHasAccessToConcert($id, $userId)) {
            Response::error('Access denied to this concert', 403);
        }
        $locale = trim((string) ($payload['locale'] ?? (method_exists($system_data, 'getLang') ? $system_data->getLang() : 'en')));
        $user = Auth::getUserInfo();
        $senderName = trim((string) (($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')));
        $recipientIds = array_values(array_filter(array_map('intval', $payload['recipientIds'] ?? []), fn($value) => $value > 0));
        $manualEmails = array_values(array_filter(array_map('strval', $payload['manualEmails'] ?? []), fn($value) => trim($value) !== ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = (string) ($payload['body'] ?? '');
        if ($subject === '') {
            Response::error('mail_subject_required', 400);
        }
        if (trim($body) === '') {
            Response::error('mail_body_required', 400);
        }
        try {
            return EventInfoMailService::send(
                $system_data,
                'C',
                $id,
                $locale !== '' ? $locale : 'en',
                $recipientIds,
                $manualEmails,
                $subject,
                $body,
                $senderName
            );
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
    
    /**
     * Check if user has access to a concert
     */
    private function userHasAccessToConcert($concertId, $userId) {
        global $system_data;
        
        $concertId = intval($concertId);
        
        // Users with Concerts module permission can always access all concerts.
        $moduleId = $system_data->getModuleId('Konzerte');
        if ($moduleId && $system_data->userHasPermission($moduleId)) {
            $concert = $this->data->findByIdNoRef($concertId);
            return $concert !== null && count($concert) > 0;
        }
        
        // Super users see all concerts (past and future)
        if ($system_data->isUserSuperUser($userId)) {
            // Check if concert exists (regardless of date)
            $concert = $this->data->findByIdNoRef($concertId);
            return $concert !== null && count($concert) > 0;
        }
        
        // Check if user has access to this concert (past or future)
        // Check if user's contact is associated with this concert
        try {
            // Get user's contact ID
            $query = "SELECT contact FROM user WHERE id = ?";
            $user = $system_data->dbcon->fetchRow($query, [['i', $userId]]);
            if (!$user || !$user['contact'] || intval($user['contact']) <= 0) {
                return false;
            }
            $contactId = intval($user['contact']);
            
            // Check if this contact is associated with the concert (regardless of date)
            $query = "SELECT COUNT(*) as cnt FROM concert_contact WHERE concert = ? AND contact = ?";
            $result = $system_data->dbcon->fetchRow($query, [
                ['i', $concertId],
                ['i', $contactId]
            ]);
            
            if ($result && intval($result['cnt']) > 0) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error checking concert access: " . $e->getMessage());
            return false;
        }
    }
}
