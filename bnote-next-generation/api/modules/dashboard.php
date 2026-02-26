<?php
/**
 * BNote Next Generation - Dashboard API Module
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
 * Dashboard API module
 * Provides dashboard data, inbox items, and news
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 * So relative paths in startdata.php will work correctly
 */
// dirs.php, init.php, and bootstrap.php are already loaded by api/index.php
// All base classes (FieldType, AbstractData, AbstractLocationData) are loaded
// Just load the module-specific data class
// Use BNOTE_ROOT constant from paths.php (loaded by api/index.php)
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/nachrichtendata.php';
require_once BNOTE_ROOT . '/src/data/modules/abstimmungdata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class DashboardModule {
    private $data;
    private $voteData;

    public function __construct() {
        // Check module permission (Start module is usually public, but check anyway)
        global $system_data;
        $moduleId = $system_data->getModuleId('Start');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Dashboard', 403);
        }
        
        $this->data = new StartData();
        $this->voteData = new AbstimmungData();
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                return $this->getDashboard();
            case 'inbox':
                return $this->getInbox();
            case 'news':
                return $this->getNews();
            case 'eventsNeedingResponse':
                return $this->getEventsNeedingResponse();
            case 'respondToEvent':
                return $this->respondToEvent();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /**
     * Get user's choices for a vote (optionId => "yes"|"no"|"maybe").
     * Implemented in API only (AbstimmungData::getUserChoices does not exist).
     */
    private function getUserChoicesForVote($vid, $uid) {
        global $system_data;
        $query = "SELECT vo.id as option_id, vou.choice
                  FROM vote_option vo
                  LEFT JOIN vote_option_user vou ON vo.id = vou.vote_option AND vou.user = ?
                  WHERE vo.vote = ?";
        $rows = $system_data->dbcon->getSelection($query, [['i', (int) $uid], ['i', (int) $vid]]);
        $choices = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $optId = (int) ($r['option_id'] ?? 0);
                $choice = isset($r['choice']) ? (int) $r['choice'] : 0;
                $choices[$optId] = $choice === 2 ? 'maybe' : ($choice === 1 ? 'yes' : 'no');
            }
        }
        return $choices;
    }

    /**
     * Check if user has voted for at least one option of this vote.
     * AbstimmungData has no hasUserVoted() - implemented in API only.
     */
    private function hasUserVoted($vid, $uid) {
        global $system_data;
        $query = "SELECT COUNT(*) as cnt FROM vote_option vo
                  JOIN vote_option_user vou ON vo.id = vou.vote_option AND vou.user = ?
                  WHERE vo.vote = ?";
        $rows = $system_data->dbcon->getSelection($query, [['i', (int) $uid], ['i', (int) $vid]]);
        return is_array($rows) && isset($rows[1]['cnt']) && (int) $rows[1]['cnt'] > 0;
    }

    /**
     * Check if user has voted for all options (for multi-date votes).
     * AbstimmungData has no hasUserVotedForAllOptions() - implemented in API only.
     */
    private function hasUserVotedForAllOptions($vid, $uid) {
        global $system_data;
        $optQuery = "SELECT COUNT(*) as cnt FROM vote_option WHERE vote = ?";
        $optRows = $system_data->dbcon->getSelection($optQuery, [['i', (int) $vid]]);
        $optCount = (is_array($optRows) && isset($optRows[1]['cnt'])) ? (int) $optRows[1]['cnt'] : 0;
        if ($optCount === 0) return true;
        $votedQuery = "SELECT COUNT(DISTINCT vo.id) as cnt FROM vote_option vo
                       JOIN vote_option_user vou ON vo.id = vou.vote_option
                       WHERE vo.vote = ? AND vou.user = ?";
        $votedRows = $system_data->dbcon->getSelection($votedQuery, [['i', (int) $vid], ['i', (int) $uid]]);
        $votedCount = (is_array($votedRows) && isset($votedRows[1]['cnt'])) ? (int) $votedRows[1]['cnt'] : 0;
        return $votedCount >= $optCount;
    }
    
    private function getDashboard() {
        global $system_data;
        
        // Raw news content (for EditorJS JSON or legacy HTML/text)
        $newsData = new NachrichtenData($GLOBALS['dir_prefix'] ?? '');
        $news = $newsData->fetchContent();
        
        // Get ALL inbox items (not limited)
        $allInboxItems = $this->getAllInboxItems();
        $formattedInbox = $this->formatInboxItems($allInboxItems);
        
        // Count events by type
        $counts = $this->countEventsByType($formattedInbox);
        
        // Get config values
        $rehearsalMax = intval($system_data->getDynamicConfigParameter('rehearsal_show_max'));
        $concertMax = intval($system_data->getDynamicConfigParameter('concert_show_max'));
        $maxShow = max($rehearsalMax, $concertMax, 5); // Default to 5 if both are 0
        $discussionOn = $system_data->getDynamicConfigParameter('discussion_on') == 1;
        
        // Get stats
        $futureRehearsals = $this->data->adp()->getFutureRehearsals();
        $futureConcerts = $this->data->adp()->getFutureConcerts();
        
        return [
            'news' => $news !== false ? (string) $news : '',
            'inbox' => $formattedInbox, // All formatted events
            'counts' => $counts,
            'config' => [
                'rehearsal_show_max' => $rehearsalMax,
                'concert_show_max' => $concertMax,
                'max_show' => $maxShow,
                'discussion_on' => $discussionOn
            ],
            'company' => (string)$system_data->getCompany(), // Band/company name for localization (cast from SimpleXMLElement)
            'total' => count($formattedInbox),
            'hasMore' => count($formattedInbox) > $maxShow,
            'stats' => [
                'upcoming_rehearsals' => max(0, count($futureRehearsals) - 1),
                'upcoming_concerts' => max(0, count($futureConcerts) - 1)
            ]
        ];
    }
    
    /**
     * Format inbox items with location information
     */
    private function formatInboxItems($inboxItems) {
        global $system_data;
        $formatted = [];
        
        foreach ($inboxItems as $item) {
            // Get raw database date (YYYY-MM-DD HH:MM:SS format) for JavaScript parsing
            $dueDateRaw = $item['replyUntil'] ?? null;
            if ($dueDateRaw && $dueDateRaw !== '-' && strlen(trim($dueDateRaw)) >= 10) {
                $dueDateISO = trim($dueDateRaw);
            } else {
                $dueDateISO = null;
            }
            
            // Get event name and location
            $eventName = $item['title'] ?? '';
            $locationName = null;
            $location = null;
            
            $otype = $item['otype'] ?? null;
            $oid = $item['oid'] ?? null;
            $status = null;
            
            if ($otype && $oid) {
                if ($otype === 'R') {
                    // Rehearsal - use translated label (Lang::txt is available from init.php)
                    // Use StartData key and remove " on", " am", " le" suffix
                    $rehearsalText = Lang::txt('StartData_inboxItems.rehearsalOn');
                    // Remove " on", " am", " le" suffix to get just "Rehearsal" / "Probe" / "Répétition"
                    $eventName = preg_replace('/\s+(on|am|le)$/i', '', $rehearsalText);
                    if (empty($eventName) || $eventName === 'StartData_inboxItems.rehearsalOn') {
                        // Fallback if translation not found
                        $eventName = 'Rehearsal';
                    }
                    
                    // Get location
                    $rehearsal = $this->data->getRehearsal($oid);
                    if ($rehearsal && isset($rehearsal['name'])) {
                        $status = $rehearsal['status'] ?? null;
                        if ($status === 'hidden') {
                            continue;
                        }
                        $locationName = $rehearsal['name'];
                        $location = [
                            'name' => $rehearsal['name'],
                            'street' => $rehearsal['street'] ?? null,
                            'city' => $rehearsal['city'] ?? null,
                            'zip' => $rehearsal['zip'] ?? null
                        ];
                    }
                } elseif ($otype === 'C') {
                    // Concert - extract name and location from preview
                    // Preview format: "title, location_name" (from startdata.php line 530)
                    $preview = $item['preview'] ?? '';
                    $parts = explode(', ', $preview);
                    if (count($parts) > 0) {
                        // Use the concert title (first part) as the event name
                        $eventName = trim($parts[0]);
                    }
                    if (count($parts) > 1) {
                        $locationName = trim($parts[1]);
                        $location = ['name' => $locationName];
                    }
                    // Always load concert to get status (and location fallback)
                    $concert = $this->data->getConcert($oid);
                    if ($concert) {
                        $status = $concert['status'] ?? null;
                        if ($status === 'hidden') {
                            continue;
                        }
                        if (!$locationName && isset($concert['location_name'])) {
                            $locationName = $concert['location_name'];
                            $location = ['name' => $locationName];
                        }
                    }
                } elseif ($otype === 'V') {
                    $eventName = $item['title'] ?? '';
                }
            }

            $row = [
                'otype' => $otype,
                'oid' => $oid,
                'title' => $eventName,
                'preview' => $item['preview'] ?? '',
                'dueDate' => $dueDateISO,
                'dueDateFormatted' => $item['due'] ?? null,
                'participation' => $item['participation'] ?? null,
                'eventBegin' => $item['eventBegin'] ?? null,
                'replyUntil' => $item['replyUntil'] ?? null,
                'status' => $status,
                'location' => $locationName,
                'locationData' => $location
            ];
            if ($otype === 'V') {
                $row['vote_options'] = $item['vote_options'] ?? [];
                $row['vote_user_choices'] = $item['vote_user_choices'] ?? [];
                $row['vote_is_date'] = !empty($item['vote_is_date']);
                $row['vote_is_multi'] = !empty($item['vote_is_multi']);
            }
            $formatted[] = $row;
        }
        
        return $formatted;
    }
    
    /**
     * Get all inbox items without limits (bypasses getInboxItems limits)
     */
    private function getAllInboxItems() {
        $items = [];
        $userId = Auth::getUserId();
        
        // Get all rehearsals (bypassing getUsersRehearsals which applies limits)
        $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
        $userRehearsals = $this->getUserRehearsalIds($userId);
        
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            // Only include rehearsals user has access to
            if (!in_array($r['id'], $userRehearsals)) {
                continue;
            }
            
            $previewItems = [];
            if (isset($r['groups'])) {
                $groupPreview = [];
                foreach ($r['groups'] as $group) {
                    $groupPreview[] = $group['name'];
                }
                $previewItems[] = join('|', $groupPreview);
            }
            $previewItems[] = $r['name'];
            if ($r['conductor'] > 0) {
                $previewItems[] = $this->data->adp()->getConductorname($r['conductor']);
            }
            
            $items[] = [
                'otype' => 'R',
                'oid' => $r['id'],
                'title' => Lang::txt('StartData_inboxItems.rehearsalOn') . ' ' . Data::convertDateFromDb($r['begin']),
                'preview' => join(', ', $previewItems),
                'due' => Data::convertDateFromDb($r['approve_until']),
                'eventBegin' => $r['begin'],
                'replyUntil' => $r['approve_until'],
                'participation' => $this->data->doesParticipateInRehearsal($r['id'])['participate'],
                'status' => $r['status']
            ];
        }
        
        // Get all concerts (bypassing getUsersConcerts which applies limits)
        $allConcerts = $this->data->adp()->getFutureConcerts($userId);
        
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            
            $items[] = [
                'otype' => 'C',
                'oid' => $c['id'],
                'title' => Lang::txt('StartData_inboxItems.concertOn') . ' ' . Data::convertDateFromDb($c['begin']),
                'preview' => $c['title'] . ', ' . $c['location_name'],
                'due' => Data::convertDateFromDb($c['approve_until']),
                'eventBegin' => $c['begin'],
                'replyUntil' => $c['approve_until'],
                'participation' => $this->data->doesParticipateInConcert($c['id'], $userId)['participate'],
                'status' => $c['status']
            ];
        }

        // Votes: get votes user is entitled to (vote_group), exclude 0-option votes
        $votesSel = $this->data->getVotesForUser($userId);
        for ($i = 1; $i < count($votesSel); $i++) {
            $v = $votesSel[$i];
            $vid = intval($v['id']);
            $opts = $this->voteData->getOptions($vid);
            $optCount = is_array($opts) ? max(0, count($opts) - 1) : 0;
            if ($optCount < 1) {
                continue;
            }
            // For multi-date: only "complete" when user has voted for ALL options
            $isMultiDate = !empty($v['is_date']) && !empty($v['is_multi']);
            $participation = $isMultiDate
                ? ($this->hasUserVotedForAllOptions($vid, $userId) ? 1 : -1)
                : ($this->hasUserVoted($vid, $userId) ? 1 : -1);
            $options = $this->voteData->getOptions($vid);
            $optionsList = [];
            if (is_array($options)) {
                for ($j = 1; $j < count($options); $j++) {
                    $row = $options[$j];
                    $optionsList[] = [
                        'id' => intval($row['id']),
                        'name' => $row['name'] ?? '',
                        'odate' => $row['odate'] ?? null,
                    ];
                }
            }
            $userChoices = $this->getUserChoicesForVote($vid, $userId);
            $items[] = [
                'otype' => 'V',
                'oid' => $vid,
                'title' => $v['name'] ?? '',
                'preview' => $v['name'] ?? '',
                'due' => Data::convertDateFromDb($v['end']),
                'eventBegin' => $v['end'],
                'replyUntil' => $v['end'],
                'participation' => $participation,
                'status' => null,
                'vote_options' => $optionsList,
                'vote_user_choices' => $userChoices,
                'vote_is_date' => !empty($v['is_date']),
                'vote_is_multi' => !empty($v['is_multi']),
            ];
        }

        return $items;
    }
    
    /**
     * Get user's rehearsal IDs (for access control)
     */
    private function getUserRehearsalIds($userId) {
        global $system_data;
        
        // Super users see all
        if ($system_data->isUserSuperUser($userId)) {
            $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
            $ids = [];
            for ($i = 1; $i < count($allRehearsals); $i++) {
                $ids[] = $allRehearsals[$i]['id'];
            }
            return $ids;
        }
        
        // Get rehearsals from groups and phases
        $usersPhases = $this->data->adp()->getUsersPhases($userId);
        $rehearsals = array_merge(
            $this->getRehearsalsForUser($userId),
            $this->getRehearsalsForPhases($usersPhases)
        );
        
        return array_unique($rehearsals);
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
    
    /**
     * Get events that need user response (participation === -1)
     */
    private function getEventsNeedingResponse() {
        global $system_data;
        
        // Get ALL inbox items (not limited)
        $allInboxItems = $this->getAllInboxItems();
        
        // Filter items where participation is -1 (not responded yet)
        // Only include rehearsals (R) and concerts (C)
        $eventsNeedingResponse = [];
        foreach ($allInboxItems as $item) {
            $participation = $item['participation'] ?? null;
            $otype = $item['otype'] ?? null;
            
            // Include rehearsals, concerts, and votes that need response
            if (($otype === 'R' || $otype === 'C' || $otype === 'V') && $participation === -1) {
                if ($otype === 'V') {
                    $endRaw = $item['eventBegin'] ?? $item['replyUntil'] ?? null;
                    if ($endRaw && strtotime($endRaw) < time()) {
                        continue; // Exclude past votes from Response Needed
                    }
                }
                $eventsNeedingResponse[] = $item;
            }
        }
        
        // Format the items with location information
        $formatted = $this->formatInboxItems($eventsNeedingResponse);
        
        // Count events by type
        $counts = $this->countEventsByType($formatted);
        
        // Get config values
        $rehearsalMax = intval($system_data->getDynamicConfigParameter('rehearsal_show_max'));
        $concertMax = intval($system_data->getDynamicConfigParameter('concert_show_max'));
        $maxShow = max($rehearsalMax, $concertMax, 5); // Default to 5 if both are 0
        $discussionOn = $system_data->getDynamicConfigParameter('discussion_on') == 1;
        
        return [
            'events' => $formatted, // ALL events, not limited
            'counts' => $counts,
            'config' => [
                'rehearsal_show_max' => $rehearsalMax,
                'concert_show_max' => $concertMax,
                'max_show' => $maxShow,
                'discussion_on' => $discussionOn
            ],
            'total' => count($formatted),
            'hasMore' => count($formatted) > $maxShow
        ];
    }
    
    /**
     * Count events by type
     */
    private function countEventsByType($events) {
        $counts = [
            'rehearsal' => 0,
            'performance' => 0,
            'meeting' => 0,
            'vote' => 0
        ];

        foreach ($events as $event) {
            $otype = $event['otype'] ?? null;
            if ($otype === 'R') {
                $counts['rehearsal']++;
            } elseif ($otype === 'C') {
                $counts['performance']++;
            } elseif ($otype === 'V') {
                $counts['vote']++;
            } else {
                $counts['meeting']++;
            }
        }

        return $counts;
    }
    
    /**
     * Respond to an event (rehearsal or concert)
     * POST data: { otype: 'R'|'C', oid: int, attending: bool, reason: string }
     */
    private function respondToEvent() {
        global $system_data;
        
        // Get user ID
        $userId = Auth::getUserId();
        if (!$userId) {
            Response::error('Not authenticated', 401);
        }
        
        // Get POST data
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $otype = $data['otype'] ?? null;
        $oid = $data['oid'] ?? null;
        $attending = $data['attending'] ?? null;
        $reason = $data['reason'] ?? '';
        
        if (!$otype || !$oid || $attending === null) {
            Response::error('Missing required fields: otype, oid, attending', 400);
        }
        
        // Validate otype
        if ($otype !== 'R' && $otype !== 'C') {
            Response::error('Invalid otype. Must be R (rehearsal) or C (concert)', 400);
        }
        
        // Convert attending boolean to participation integer
        // 1 = yes, 0 = no, 2 = maybe (if supported)
        $participate = $attending ? 1 : 0;
        
        // Save participation using StartData
        $this->data->saveParticipation($otype === 'R' ? 'rehearsal' : 'concert', $userId, $oid, $participate, $reason);
        
        return [
            'success' => true,
            'message' => 'Response saved successfully'
        ];
    }
    
    private function getInbox() {
        $inboxItems = $this->data->getInboxItems();
        
        // Filter by otype if provided
        $otype = $_GET['otype'] ?? $_GET['only'] ?? null;
        if ($otype) {
            $filtered = [];
            foreach ($inboxItems as $item) {
                if (($item['otype'] ?? null) == $otype) {
                    $filtered[] = $item;
                }
            }
            return $filtered;
        }
        
        // Return all
        return $inboxItems;
    }
    
    private function getNews() {
        return [
            'news' => $this->data->getNews()
        ];
    }
}
