<?php
/**
 * BNote Next Generation - Participation API Module
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
 * Participation API module
 * Handles participation status for rehearsals and concerts
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
// Use BNOTE_ROOT constant from paths.php (loaded by api/index.php)
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class ParticipationModule {
    private $data;
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        $this->data = new StartData();
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'get';
        
        switch ($action) {
            case 'get':
                return $this->getParticipation();
            case 'save':
                return $this->saveParticipation();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    /**
     * Get participation status for an event
     * GET /api/index.php?module=participation&action=get&event_id={id}&event_type={R|C}
     */
    private function getParticipation() {
        global $system_data;
        
        $eventId = $_GET['event_id'] ?? null;
        $eventType = $_GET['event_type'] ?? null;
        
        if (!$eventId || !$eventType) {
            Response::error('Missing required parameters: event_id, event_type', 400);
        }
        
        // Validate event type
        if ($eventType !== 'R' && $eventType !== 'C') {
            Response::error('Invalid event_type. Must be R (rehearsal) or C (concert)', 400);
        }
        
        // Validate event ID is numeric
        if (!is_numeric($eventId)) {
            Response::error('Invalid event_id', 400);
        }
        
        $eventId = intval($eventId);
        $userId = Auth::getUserId();
        
        // Check user has access to this event
        if (!$this->userHasAccessToEvent($eventType, $eventId, $userId)) {
            Response::error('Access denied to this event', 403);
        }
        
        // Get participation status
        $participation = null;
        $eventBegin = null;
        if ($eventType === 'R') {
            $participation = $this->data->doesParticipateInRehearsal($eventId);
            // Get rehearsal to check deadline and begin date
            $rehearsal = $this->data->getRehearsal($eventId);
            $deadline = $rehearsal['approve_until'] ?? null;
            $eventBegin = $rehearsal['begin'] ?? null;
        } else {
            $participation = $this->data->doesParticipateInConcert($eventId, $userId);
            // Get concert to check deadline and begin date
            $concert = $this->data->getConcert($eventId);
            $deadline = $concert['approve_until'] ?? null;
            $eventBegin = $concert['begin'] ?? null;
        }
        
        // Map participation integer to status string
        $status = $this->mapParticipationToStatus($participation['participate'] ?? -1);
        
        // Get config for allow_participation_maybe
        $allowMaybe = $system_data->getDynamicConfigParameter("allow_participation_maybe") == 1;
        
        // Check if deadline has passed
        $isLocked = false;
        if ($deadline && $deadline !== '-' && strlen(trim($deadline)) >= 10) {
            $deadlineTime = strtotime($deadline);
            $currentTime = time();
            $isLocked = $deadlineTime < $currentTime;
        }
        
        // Also lock if event begin date is in the past
        if (!$isLocked && $eventBegin && $eventBegin !== '-' && strlen(trim($eventBegin)) >= 10) {
            $eventBeginTime = strtotime($eventBegin);
            $currentTime = time();
            $isLocked = $eventBeginTime < $currentTime;
        }
        
        return [
            'status' => $status,
            'reason' => $participation['reason'] ?? null,
            'allow_maybe' => $allowMaybe,
            'deadline' => $deadline,
            'is_locked' => $isLocked
        ];
    }
    
    /**
     * Save participation status for an event
     * POST /api/index.php?module=participation&action=save
     * Body: {"event_id": 123, "event_type": "R|C", "status": "yes|maybe|no|undecided", "reason": "..."}
     */
    private function saveParticipation() {
        global $system_data;
        
        // Get POST data
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $eventId = $data['event_id'] ?? null;
        $eventType = $data['event_type'] ?? null;
        $status = $data['status'] ?? null;
        $reason = $data['reason'] ?? '';
        
        if (!$eventId || !$eventType || !$status) {
            Response::error('Missing required fields: event_id, event_type, status', 400);
        }
        
        // Validate event type
        if ($eventType !== 'R' && $eventType !== 'C') {
            Response::error('Invalid event_type. Must be R (rehearsal) or C (concert)', 400);
        }
        
        // Validate status
        $validStatuses = ['yes', 'maybe', 'no', 'undecided'];
        if (!in_array($status, $validStatuses)) {
            Response::error('Invalid status. Must be one of: ' . implode(', ', $validStatuses), 400);
        }
        
        // Check if maybe is allowed
        if ($status === 'maybe') {
            $allowMaybe = $system_data->getDynamicConfigParameter("allow_participation_maybe") == 1;
            if (!$allowMaybe) {
                Response::error('Maybe participation is not enabled', 400);
            }
        }
        
        // Validate event ID is numeric
        if (!is_numeric($eventId)) {
            Response::error('Invalid event_id', 400);
        }
        
        $eventId = intval($eventId);
        $userId = Auth::getUserId();
        
        // Check user has access to this event
        if (!$this->userHasAccessToEvent($eventType, $eventId, $userId)) {
            Response::error('Access denied to this event', 403);
        }
        
        // Check if deadline has passed or event is in the past (lock check)
        $deadline = null;
        $eventBegin = null;
        if ($eventType === 'R') {
            $rehearsal = $this->data->getRehearsal($eventId);
            $deadline = $rehearsal['approve_until'] ?? null;
            $eventBegin = $rehearsal['begin'] ?? null;
        } else {
            $concert = $this->data->getConcert($eventId);
            $deadline = $concert['approve_until'] ?? null;
            $eventBegin = $concert['begin'] ?? null;
        }
        
        // Check if deadline has passed
        if ($deadline && $deadline !== '-' && strlen(trim($deadline)) >= 10) {
            $deadlineTime = strtotime($deadline);
            $currentTime = time();
            if ($deadlineTime < $currentTime) {
                Response::error('Participation deadline has passed', 403);
            }
        }
        
        // Check if event begin date is in the past
        if ($eventBegin && $eventBegin !== '-' && strlen(trim($eventBegin)) >= 10) {
            $eventBeginTime = strtotime($eventBegin);
            $currentTime = time();
            if ($eventBeginTime < $currentTime) {
                Response::error('Event is in the past and cannot be changed', 403);
            }
        }
        
        // Map status string to participation integer
        $participate = $this->mapStatusToParticipation($status);
        
        // Validate reason if provided
        if ($reason && !empty(trim($reason))) {
            // Reason validation is done in saveParticipation method
        }
        
        // Save participation using StartData
        // Note: StartData::saveParticipation expects 'R' or 'C' as first parameter
        $this->data->saveParticipation($eventType, $userId, $eventId, $participate, $reason);
        
        return [
            'success' => true,
            'status' => $status
        ];
    }
    
    /**
     * Check if user has access to an event
     * Uses the same logic as dashboard to get ALL accessible events (not limited)
     */
    private function userHasAccessToEvent($eventType, $eventId, $userId) {
        global $system_data;
        
        $eventId = intval($eventId);
        
        if ($eventType === 'R') {
            // Check if user has access to this rehearsal
            // Use the same logic as dashboard to get ALL accessible rehearsals
            // Super users see all
            if ($system_data->isUserSuperUser($userId)) {
                $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
                for ($i = 1; $i < count($allRehearsals); $i++) {
                    if (intval($allRehearsals[$i]['id']) == $eventId) {
                        return true;
                    }
                }
                return false;
            }
            
            // Get rehearsals from groups and phases (same as dashboard)
            try {
                $usersPhases = $this->data->adp()->getUsersPhases($userId);
                $rehearsalIds = array_merge(
                    $this->getRehearsalsForUser($userId),
                    $this->getRehearsalsForPhases($usersPhases)
                );
                
                // Convert all IDs to int for comparison
                $rehearsalIds = array_map('intval', array_unique($rehearsalIds));
                return in_array($eventId, $rehearsalIds);
            } catch (Exception $e) {
                // Log error but don't expose to user
                error_log("Error checking rehearsal access: " . $e->getMessage());
                return false;
            }
        } else {
            // Check if user has access to this concert
            // Get all future concerts (same as dashboard)
            try {
                $allConcerts = $this->data->adp()->getFutureConcerts($userId);
                for ($i = 1; $i < count($allConcerts); $i++) {
                    if (intval($allConcerts[$i]['id']) == $eventId) {
                        return true;
                    }
                }
                return false;
            } catch (Exception $e) {
                // Log error but don't expose to user
                error_log("Error checking concert access: " . $e->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Get rehearsals for user (from contact)
     * Same helper as in dashboard.php
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
     * Same helper as in dashboard.php
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
    
    /**
     * Map participation integer to status string
     * 1 = yes, 2 = maybe, 0 = no, -1 = undecided
     */
    private function mapParticipationToStatus($participate) {
        switch ($participate) {
            case 1:
                return 'yes';
            case 2:
                return 'maybe';
            case 0:
                return 'no';
            case -1:
            default:
                return 'undecided';
        }
    }
    
    /**
     * Map status string to participation integer
     * yes = 1, maybe = 2, no = 0, undecided = -1
     */
    private function mapStatusToParticipation($status) {
        switch ($status) {
            case 'yes':
                return 1;
            case 'maybe':
                return 2;
            case 'no':
                return 0;
            case 'undecided':
            default:
                return -1;
        }
    }
}
