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
require_once __DIR__ . '/../../../src/data/modules/probendata.php';
require_once __DIR__ . '/../../../src/data/modules/startdata.php';
require_once __DIR__ . '/../../../src/data/modules/locationsdata.php';
require_once __DIR__ . '/../../../src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class RehearsalsModule {
    private $data;
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        // Check module permission
        global $system_data;
        $moduleId = $system_data->getModuleId('Proben');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Rehearsals', 403);
        }
        
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
        
        // Handle explicit actions if needed in the future
        if ($action) {
            Response::error('Unknown action: ' . $action, 400);
        }
        
        Response::error('Method not supported or missing ID', 400);
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
     * Check if user has access to a rehearsal
     * Uses the same logic as dashboard
     */
    private function userHasAccessToRehearsal($rehearsalId, $userId) {
        global $system_data;
        
        $rehearsalId = intval($rehearsalId);
        
        // Super users see all
        if ($system_data->isUserSuperUser($userId)) {
            $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
            for ($i = 1; $i < count($allRehearsals); $i++) {
                if (intval($allRehearsals[$i]['id']) == $rehearsalId) {
                    return true;
                }
            }
            return false;
        }
        
        // Get rehearsals from groups and phases
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
