<?php
/**
 * Concerts API module
 * Handles concert detail endpoints with participants grouped by instruments and all metadata
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
require_once __DIR__ . '/../../src/data/modules/konzertedata.php';
require_once __DIR__ . '/../../src/data/modules/startdata.php';
require_once __DIR__ . '/../../src/data/modules/locationsdata.php';
require_once __DIR__ . '/../../src/data/database.php';
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
                        'zip' => $address['zip'] ?? null
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
                        'zip' => $accData['zip'] ?? null
                    ]
                ];
            }
        }
        
        // Get all instruments used
        $usedInstruments = $this->data->getUsedInstruments();
        unset($usedInstruments[0]); // Remove header
        
        // Group participants by instrument
        $participantsByInstrument = [];
        $totalStats = ['yes' => 0, 'maybe' => 0, 'no' => 0, 'pending' => 0];
        
        foreach ($usedInstruments as $instrument) {
            $instrumentId = $instrument['id'];
            $participants = $this->data->getParticipantOverview($id, $instrumentId, false);
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
                        'participate' => $participate
                    ];
                }
                
                $participantsByInstrument[] = [
                    'instrument' => [
                        'id' => intval($instrumentId),
                        'name' => $instrument['name']
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
        
        // Get all future concerts (same as dashboard)
        try {
            $allConcerts = $this->data->adp()->getFutureConcerts($userId);
            for ($i = 1; $i < count($allConcerts); $i++) {
                if (intval($allConcerts[$i]['id']) == $concertId) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Error checking concert access: " . $e->getMessage());
            return false;
        }
    }
}
