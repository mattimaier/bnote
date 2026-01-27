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
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class ConcertsModule {
    private $data;
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        // Check module permission
        global $system_data;
        $moduleId = $system_data->getModuleId('Konzerte');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Concerts', 403);
        }
        
        $this->data = new KonzerteData();
    }
    
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? $_POST['action'] ?? null;
        $id = $_GET['id'] ?? null;
        
        // If no action specified but ID is provided, treat as GET request
        if ($method === 'GET' && $id && ($action === null || $action === '')) {
            return $this->getConcert($id);
        }
        
        // Handle explicit actions if needed in the future
        if ($action) {
            Response::error('Unknown action: ' . $action, 400);
        }
        
        Response::error('Method not supported or missing ID', 400);
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
                $contact = [
                    'id' => intval($concert['contact']),
                    'name' => $contactData['name'] ?? null,
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
                         ct.id as contact_id, CONCAT(ct.name, ' ', ct.surname) as contactname, 
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
    
    /**
     * Check if user has access to a concert
     */
    private function userHasAccessToConcert($concertId, $userId) {
        global $system_data;
        
        $concertId = intval($concertId);
        
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
