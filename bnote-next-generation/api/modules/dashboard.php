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
require_once BNOTE_ROOT . '/src/data/modules/aufgabendata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../text_normalizer.php';
require_once __DIR__ . '/../mail/ReminderInboxSource.php';

class DashboardModule {
    private $data;
    private $voteData;
    private ReminderInboxSource $inboxSource;

    public function __construct() {
        // Check module permission (Start module is usually public, but check anyway)
        global $system_data;
        $moduleId = $system_data->getModuleId('Start');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Dashboard', 403);
        }
        
        $this->data = new StartData();
        $this->voteData = new AbstimmungData();
        $this->inboxSource = new ReminderInboxSource($this->data, $this->voteData, $system_data);
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
            case 'completeTask':
                return $this->completeTask();
            case 'getAdminOverview':
                return $this->getAdminOverview();
            case 'getActivityFeed':
                return $this->getActivityFeed();
            case 'getInstruments':
                return $this->getInstruments();
            case 'getInstrumentMinimums':
                return $this->getInstrumentMinimums();
            case 'setInstrumentMinimums':
                return $this->setInstrumentMinimums();
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
        
        // Get ALL inbox items (not limited) via reusable source
        $uid = (int) Auth::getUserId();
        $allInboxItems = $this->inboxSource->getAllInboxItemsForUser($uid);
        $formattedInbox = $this->inboxSource->formatInboxItems($allInboxItems);
        
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
                        if ($this->isSuppressedEventStatus($status)) {
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
                        if ($this->isSuppressedEventStatus($status)) {
                            continue;
                        }
                        if (!$locationName && isset($concert['location_name'])) {
                            $locationName = $concert['location_name'];
                            $location = ['name' => $locationName];
                        }
                    }
                } elseif ($otype === 'V') {
                    $eventName = $item['title'] ?? '';
                } elseif ($otype === 'T') {
                    $eventName = $item['title'] ?? '';
                } elseif ($otype === 'RS') {
                    $eventName = $item['title'] ?? '';
                    $locationName = $item['location'] ?? null;
                    $location = $locationName ? ['name' => $locationName] : null;
                } elseif ($otype === 'AP') {
                    $eventName = $item['title'] ?? '';
                    $locationName = $item['location'] ?? null;
                    $location = $locationName ? ['name' => $locationName] : null;
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
            if ($otype === 'T') {
                $row['assignee'] = $item['assignee'] ?? null;
                $row['assigneeFullName'] = $item['assigneeFullName'] ?? ($item['assignee'] ?? null);
                $row['is_complete'] = $item['is_complete'] ?? 0;
            }
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

    private function isSuppressedEventStatus($status): bool {
        if (!is_string($status)) {
            return false;
        }
        $normalized = strtolower(trim($status));
        return in_array($normalized, ['hidden', 'cancelled', 'canceled'], true);
    }
    
    /**
     * Get all inbox items without limits (bypasses getInboxItems limits)
     */
    private function getAllInboxItems() {
        $items = [];
        $userId = Auth::getUserId();
        global $system_data;
        
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
        $concertsModuleId = $system_data->getModuleId('Konzerte');
        $hasConcertsModule = $concertsModuleId ? $system_data->userHasPermission($concertsModuleId) : false;
        $allConcerts = $hasConcertsModule
            ? $this->data->adp()->getFutureConcerts()
            : $this->data->adp()->getFutureConcerts($userId);
        
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

        // Reservations: future reservations (Calendar module; show only if user has permission)
        $calendarModuleId = $system_data->getModuleId('Calendar');
        if ($calendarModuleId && $system_data->userHasPermission($calendarModuleId)) {
            $reservations = $this->data->getReservations();
            if (is_array($reservations)) {
                for ($i = 1; $i < count($reservations); $i++) {
                    $r = $reservations[$i];
                    $items[] = [
                        'otype' => 'RS',
                        'oid' => (int) $r['id'],
                        'title' => $r['name'] ?? '',
                        'preview' => ($r['name'] ?? '') . ', ' . ($r['locationname'] ?? ''),
                        'due' => null,
                        'eventBegin' => $r['begin'] ?? null,
                        'replyUntil' => $r['begin'] ?? null,
                        'status' => null,
                        'location' => $r['locationname'] ?? null,
                    ];
                }
            }
            // Appointments: where user is in invited group
            $appointments = $this->data->getAppointments(false);
            if (is_array($appointments)) {
                for ($i = 1; $i < count($appointments); $i++) {
                    $a = $appointments[$i];
                    $items[] = [
                        'otype' => 'AP',
                        'oid' => (int) $a['id'],
                        'title' => $a['name'] ?? '',
                        'preview' => ($a['name'] ?? '') . ', ' . ($a['locationname'] ?? ''),
                        'due' => null,
                        'eventBegin' => $a['begin'] ?? null,
                        'replyUntil' => $a['begin'] ?? null,
                        'status' => null,
                        'location' => $a['locationname'] ?? null,
                    ];
                }
            }
        }

        // Tasks: open tasks assigned to current user
        $tasks = $this->data->adp()->getUserTasks($userId);
        for ($i = 1; $i < count($tasks); $i++) {
            $t = $tasks[$i];
            $dueAt = $t['due_at'] ?? null;
            $eventBegin = $dueAt && $dueAt !== '-' ? $dueAt : ($t['created_at'] ?? null);
            $replyUntil = $dueAt && $dueAt !== '-' ? $dueAt : null;
            $items[] = [
                'otype' => 'T',
                'oid' => (int) $t['id'],
                'title' => $t['title'] ?? '',
                'preview' => isset($t['description']) ? substr($t['description'], 0, 50) : '',
                'due' => $dueAt ? Data::convertDateFromDb($dueAt) : null,
                'eventBegin' => $eventBegin,
                'replyUntil' => $replyUntil,
                'assignee' => trim($t['assignee'] ?? ''),
                'assigneeFullName' => trim($t['assignee'] ?? ''),
                'is_complete' => 0,
            ];
        }

        return $items;
    }
    
    /**
     * Get user's rehearsal IDs (for access control)
     */
    private function getUserRehearsalIds($userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Proben');
        $hasRehearsalsModule = $moduleId ? $system_data->userHasPermission($moduleId) : false;
        
        // Super users see all
        if ($system_data->isUserSuperUser($userId) || $hasRehearsalsModule) {
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
        
        // Get ALL inbox items (not limited) via reusable source
        $uid = (int) Auth::getUserId();
        $allInboxItems = $this->inboxSource->getAllInboxItemsForUser($uid);
        
        // Filter items where participation is -1 (not responded yet) or open tasks
        $eventsNeedingResponse = [];
        foreach ($allInboxItems as $item) {
            $participation = $item['participation'] ?? null;
            $otype = $item['otype'] ?? null;
            $isComplete = $item['is_complete'] ?? 0;

            // Include rehearsals, concerts, and votes that need response
            if (($otype === 'R' || $otype === 'C' || $otype === 'V') && $participation === -1) {
                if ($otype === 'V') {
                    $endRaw = $item['eventBegin'] ?? $item['replyUntil'] ?? null;
                    if ($endRaw && strtotime($endRaw) < time()) {
                        continue; // Exclude past votes from Response Needed
                    }
                }
                $eventsNeedingResponse[] = $item;
            } elseif ($otype === 'T' && $isComplete == 0) {
                // Include open (incomplete) tasks in the top section
                $eventsNeedingResponse[] = $item;
            }
        }
        
        // Format the items with location information
        $formatted = $this->inboxSource->formatInboxItems($eventsNeedingResponse);
        
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
            'vote' => 0,
            'task' => 0,
            'reservation' => 0,
            'appointment' => 0,
        ];

        foreach ($events as $event) {
            $otype = $event['otype'] ?? null;
            if ($otype === 'R') {
                $counts['rehearsal']++;
            } elseif ($otype === 'C') {
                $counts['performance']++;
            } elseif ($otype === 'V') {
                $counts['vote']++;
            } elseif ($otype === 'T') {
                $counts['task']++;
            } elseif ($otype === 'RS') {
                $counts['reservation']++;
            } elseif ($otype === 'AP') {
                $counts['appointment']++;
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
        // Get user ID
        $userId = (int) Auth::getUserId();
        if (!$userId) {
            Response::error('Not authenticated', 401);
        }
        
        // Get POST data
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $otype = isset($data['otype']) ? strtoupper(trim((string) $data['otype'])) : null;
        $oid = $data['oid'] ?? null;
        $attendingProvided = array_key_exists('attending', $data);
        $attending = $attendingProvided ? $data['attending'] : null;
        $reason = $data['reason'] ?? '';
        
        if (!$otype || !$oid || !$attendingProvided) {
            Response::error('Missing required fields: otype, oid, attending', 400);
        }
        
        // Validate otype
        if ($otype !== 'R' && $otype !== 'C') {
            Response::error('Invalid otype. Must be R (rehearsal) or C (concert)', 400);
        }

        if (!is_numeric($oid)) {
            Response::error('Invalid oid', 400);
        }
        $eventId = intval($oid);
        if ($eventId <= 0) {
            Response::error('Invalid oid', 400);
        }

        if (!$this->userHasAccessToEvent($otype, $eventId, $userId)) {
            Response::error('Access denied to this event', 403);
        }

        $this->assertParticipationWindowOpen($otype, $eventId);
        
        // Convert attending boolean to participation integer
        $attendingBool = $this->normalizeAttendingBoolean($attending);
        $participate = $attendingBool ? 1 : 0;
        
        // Save participation using StartData
        // Note: StartData::saveParticipation expects 'R' or 'C'
        $this->data->saveParticipation($otype, $userId, $eventId, $participate, $reason);
        
        return [
            'success' => true,
            'message' => 'Response saved successfully'
        ];
    }

    private function normalizeAttendingBoolean($attending): bool {
        if (is_bool($attending)) {
            return $attending;
        }
        if (is_int($attending) || is_float($attending)) {
            if ((int) $attending === 1) return true;
            if ((int) $attending === 0) return false;
        }
        if (is_string($attending)) {
            $normalized = strtolower(trim($attending));
            if ($normalized === 'true' || $normalized === '1' || $normalized === 'yes') return true;
            if ($normalized === 'false' || $normalized === '0' || $normalized === 'no') return false;
        }
        Response::error('Invalid attending value', 400);
        return false;
    }

    private function userHasAccessToEvent($eventType, $eventId, $userId): bool {
        if ($eventType === 'R') {
            $allowedIds = array_map('intval', $this->getUserRehearsalIds($userId));
            return in_array((int) $eventId, $allowedIds, true);
        }
        if ($eventType === 'C') {
            $allowedIds = array_map('intval', $this->getConcertIdsForUser($userId));
            return in_array((int) $eventId, $allowedIds, true);
        }
        return false;
    }

    private function assertParticipationWindowOpen($eventType, $eventId): void {
        if ($eventType === 'R') {
            $event = $this->data->getRehearsal($eventId);
        } else {
            $event = $this->data->getConcert($eventId);
        }

        if (!is_array($event) || count($event) === 0) {
            Response::error('Event not found', 404);
        }

        $deadline = $event['approve_until'] ?? null;
        $eventBegin = $event['begin'] ?? null;

        if ($deadline && $deadline !== '-' && strlen(trim($deadline)) >= 10) {
            $deadlineTime = strtotime($deadline);
            if ($deadlineTime !== false && $deadlineTime < time()) {
                Response::error('Participation deadline has passed', 403);
            }
        }

        if ($eventBegin && $eventBegin !== '-' && strlen(trim($eventBegin)) >= 10) {
            $eventBeginTime = strtotime($eventBegin);
            if ($eventBeginTime !== false && $eventBeginTime < time()) {
                Response::error('Event is in the past and cannot be changed', 403);
            }
        }
    }

    /**
     * Complete or reopen a task (otype T).
     * POST: { otype: 'T', oid: number, complete: boolean }
     */
    private function completeTask() {
        global $system_data;
        $userId = Auth::getUserId();
        if (!$userId) {
            Response::error('Not authenticated', 401);
        }

        $rawInput = file_get_contents('php://input');
        $data = $rawInput ? json_decode($rawInput, true) : $_POST;
        $otype = $data['otype'] ?? null;
        $oid = $data['oid'] ?? $data['id'] ?? null;
        $complete = isset($data['complete']) ? (bool) $data['complete'] : true;

        if ($otype !== 'T' || !$oid) {
            Response::error('Missing or invalid otype/oid for task completion', 400);
        }

        $taskId = (int) $oid;
        if ($taskId <= 0) {
            Response::error('Missing or invalid otype/oid for task completion', 400);
        }

        $canToggle = (int) ($system_data->dbcon->colValue(
            'SELECT COUNT(*) as cnt FROM task t JOIN user u ON u.contact = t.assigned_to WHERE t.id = ? AND u.id = ?',
            'cnt',
            [['i', $taskId], ['i', (int) $userId]]
        ) ?? 0) > 0;
        if (!$canToggle) {
            Response::error('Access denied to this task', 403);
        }

        require_once BNOTE_ROOT . '/src/data/modules/aufgabendata.php';
        $taskData = new AufgabenData();
        $taskData->markTask($taskId, $complete ? 1 : 0);

        return ['success' => true, 'is_complete' => $complete];
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

    /**
     * Get activity feed: recent comments (R/C/V) and task creation for the current user.
     */
    private function getActivityFeed() {
        global $system_data;
        $uid = Auth::getUserId();
        if (!$uid) {
            Response::error('Not authenticated', 401);
        }

        $discussionOn = $system_data->getDynamicConfigParameter('discussion_on') == 1;
        $items = [];

        if ($discussionOn) {
            $userRehearsals = $this->getUserRehearsalIds($uid);
            $userConcerts = $this->getConcertIdsForUser($uid);
            $userVotes = $this->getVoteIdsForUser($uid);

            $allOids = [];
            foreach ($userRehearsals as $rid) {
                $allOids[] = ['otype' => 'R', 'oid' => $rid];
            }
            foreach ($userConcerts as $cid) {
                $allOids[] = ['otype' => 'C', 'oid' => $cid];
            }
            foreach ($userVotes as $vid) {
                $allOids[] = ['otype' => 'V', 'oid' => $vid];
            }

            if (count($allOids) > 0) {
                $placeholders = [];
                $params = [];
                foreach ($allOids as $row) {
                    $placeholders[] = '(otype = ? AND oid = ?)';
                    $params[] = ['s', $row['otype']];
                    $params[] = ['i', $row['oid']];
                }
                $params[] = ['i', 15];
                $query = "SELECT c.id, c.otype, c.oid, c.author, c.message, c.created_at,
                          CONCAT(ct.name, ' ', ct.surname) as author_name
                          FROM comment c
                          JOIN user u ON c.author = u.id
                          JOIN contact ct ON u.contact = ct.id
                          WHERE (" . implode(' OR ', $placeholders) . ")
                          ORDER BY c.created_at DESC LIMIT ?";
                $rows = $system_data->dbcon->getSelection($query, $params);
                if (is_array($rows)) {
                    for ($i = 1; $i < count($rows); $i++) {
                        $r = $rows[$i];
                        $entityTitle = $this->getEntityTitleForActivity($r['otype'], $r['oid']);
                        $items[] = [
                            'activity_type' => 'comment',
                            'id' => (int) $r['id'],
                            'otype' => $r['otype'],
                            'oid' => (int) $r['oid'],
                            'author' => (int) $r['author'],
                            'author_name' => $r['author_name'] ?? '',
                            'message' => isset($r['message']) ? urldecode($r['message']) : '',
                            'created_at' => $r['created_at'] ?? '',
                            'entity_title' => $entityTitle,
                        ];
                    }
                }
            }
        }

        $taskQuery = "SELECT t.id, t.title, t.created_at, CONCAT(ct.name, ' ', ct.surname) as assignee_name
                      FROM task t
                      LEFT JOIN contact ct ON t.assigned_to = ct.id
                      ORDER BY t.created_at DESC LIMIT 5";
        $taskRows = $system_data->dbcon->getSelection($taskQuery, []);
        if (is_array($taskRows)) {
            for ($i = 1; $i < count($taskRows); $i++) {
                $r = $taskRows[$i];
                $items[] = [
                    'activity_type' => 'task_created',
                    'id' => (int) $r['id'],
                    'title' => $r['title'] ?? '',
                    'assignee_name' => $r['assignee_name'] ?? '',
                    'created_at' => $r['created_at'] ?? '',
                ];
            }
        }

        usort($items, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return ['items' => array_slice($items, 0, 15)];
    }

    private function getConcertIdsForUser($uid) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Konzerte');
        $hasConcertsModule = $moduleId ? $system_data->userHasPermission($moduleId) : false;
        $concerts = $hasConcertsModule
            ? $this->data->adp()->getFutureConcerts()
            : $this->data->adp()->getFutureConcerts($uid);
        $ids = [];
        if (is_array($concerts)) {
            for ($i = 1; $i < count($concerts); $i++) {
                $ids[] = (int) ($concerts[$i]['id'] ?? 0);
            }
        }
        return array_filter($ids);
    }

    private function getVoteIdsForUser($uid) {
        $votes = $this->data->getVotesForUser($uid);
        $ids = [];
        if (is_array($votes)) {
            for ($i = 1; $i < count($votes); $i++) {
                $vid = (int) ($votes[$i]['id'] ?? 0);
                if ($vid > 0) $ids[] = $vid;
            }
        }
        return $ids;
    }

    private function getEntityTitleForActivity($otype, $oid) {
        if ($otype === 'R') {
            $r = $this->data->getRehearsal($oid);
            return isset($r['name']) ? $r['name'] : "Rehearsal #$oid";
        }
        if ($otype === 'C') {
            $c = $this->data->getConcert($oid);
            return isset($c['title']) ? $c['title'] : "Concert #$oid";
        }
        if ($otype === 'V') {
            $v = $this->voteData->findByIdNoRef($oid);
            return isset($v['name']) ? $v['name'] : "Vote #$oid";
        }
        return '';
    }

    /**
     * Get aggregated admin overview data (admin-only).
     */
    private function getAdminOverview() {
        global $system_data;
        $uid = Auth::getUserId();
        if (!$uid) {
            Response::error('Not authenticated', 401);
        }
        if (!$system_data->isUserSuperUser($uid) && !$system_data->isUserMemberGroup(1, $uid)) {
            Response::error('Admin access required', 403);
        }

        $participationGaps = $this->getAdminParticipationGaps($uid);
        $missedDeadlines = $this->getAdminMissedDeadlines($uid);
        $votesSummary = $this->getAdminVotesSummary();
        $tasksOverview = $this->getAdminTasksOverview();
        $cancellations = $this->getAdminCancellations($uid);
        $instrumentGaps = $this->getAdminInstrumentGaps($uid);
        $pendingInvitations = $this->getAdminPendingInvitations($uid);
        $upcomingEvents = $this->getAdminUpcomingEvents($uid);
        $pendingAccounts = $this->getAdminPendingAccounts($system_data);

        $actionNeeded = count($participationGaps['events'])
            + count($votesSummary['votes_closing_soon'])
            + count($missedDeadlines['events'])
            + $cancellations['total_count']
            + $tasksOverview['overdue_count']
            + $tasksOverview['open_count']
            + (int) ($pendingAccounts['count'] ?? 0);

        return [
            'participation_gaps' => $participationGaps,
            'missed_deadlines' => $missedDeadlines,
            'votes_summary' => $votesSummary,
            'tasks_overview' => $tasksOverview,
            'cancellations' => $cancellations,
            'instrument_gaps' => $instrumentGaps,
            'pending_invitations' => $pendingInvitations,
            'upcoming_events' => $upcomingEvents,
            'pending_accounts' => $pendingAccounts,
            'action_needed_count' => $actionNeeded,
        ];
    }

    /**
     * Inactive users who still need “phase-in”: isActive = 0 and not linked to any rehearsal,
     * rehearsal phase, concert, tour, or vote roster (same dimensions as contacts integrate()).
     */
    private function getAdminPendingAccounts($system_data) {
        $db = $system_data->dbcon;
        $integrationUnassigned = ''
            . ' AND NOT EXISTS (SELECT 1 FROM rehearsal_contact rc WHERE rc.contact = c.id)'
            . ' AND NOT EXISTS (SELECT 1 FROM concert_contact cc WHERE cc.contact = c.id)'
            . ' AND NOT EXISTS (SELECT 1 FROM rehearsalphase_contact rpc WHERE rpc.contact = c.id)'
            . ' AND NOT EXISTS (SELECT 1 FROM tour_contact tc WHERE tc.contact = c.id)'
            . ' AND NOT EXISTS (SELECT 1 FROM vote_group vg WHERE vg.user = u.id)';

        $countRow = $db->getSelection(
            'SELECT COUNT(*) AS cnt FROM user u INNER JOIN contact c ON u.contact = c.id '
            . 'WHERE u.isActive = 0' . $integrationUnassigned,
            []
        );
        $total = 0;
        if (is_array($countRow) && isset($countRow[1]['cnt'])) {
            $total = (int) $countRow[1]['cnt'];
        }

        $q = 'SELECT u.id AS userId, c.id AS contactId, u.login, c.name, c.surname, c.email '
            . 'FROM user u INNER JOIN contact c ON u.contact = c.id '
            . 'WHERE u.isActive = 0' . $integrationUnassigned
            . ' ORDER BY u.id DESC LIMIT 20';
        $rows = $db->getSelection($q, []);
        $users = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $users[] = [
                    'userId' => (int) ($r['userId'] ?? 0),
                    'contactId' => (int) ($r['contactId'] ?? 0),
                    'login' => (string) ($r['login'] ?? ''),
                    'name' => (string) ($r['name'] ?? ''),
                    'surname' => (string) ($r['surname'] ?? ''),
                    'email' => (string) ($r['email'] ?? ''),
                ];
            }
        }

        $defGroup = $system_data->getDynamicConfigParameter('default_contact_group');
        if ($defGroup === null || $defGroup === '') {
            $defGroup = 2;
        }

        return [
            'count' => $total,
            'users' => $users,
            'default_integration_group' => (int) $defGroup,
        ];
    }

    private function getAdminParticipationGaps($uid) {
        global $system_data;
        $events = [];
        $threshold = 0.5; // low yes-rate threshold

        $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            $rid = (int) ($r['id'] ?? 0);
            if (!$rid) continue;
            $stats = $this->getParticipationStatsForRehearsal($rid);
            $pending = (int) ($stats['pending'] ?? 0);
            $yes = (int) ($stats['yes'] ?? 0);
            $total = (int) ($stats['total'] ?? 0);
            $lowYes = $total > 0 && ($yes / $total) < $threshold;
            if ($pending > 0 || $lowYes) {
                $events[] = [
                    'id' => $rid,
                    'otype' => 'R',
                    'title' => Lang::txt('StartData_inboxItems.rehearsalOn') . ' ' . Data::convertDateFromDb($r['begin'] ?? ''),
                    'begin' => $r['begin'] ?? '',
                    'approve_until' => $r['approve_until'] ?? null,
                    'participation_stats' => $stats,
                ];
            }
        }

        $allConcerts = $this->data->adp()->getFutureConcerts($uid);
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            $cid = (int) ($c['id'] ?? 0);
            if (!$cid) continue;
            $stats = $this->getParticipationStatsForConcert($cid);
            $pending = (int) ($stats['pending'] ?? 0);
            $yes = (int) ($stats['yes'] ?? 0);
            $total = (int) ($stats['total'] ?? 0);
            $lowYes = $total > 0 && ($yes / $total) < $threshold;
            if ($pending > 0 || $lowYes) {
                $events[] = [
                    'id' => $cid,
                    'otype' => 'C',
                    'title' => ($c['title'] ?? '') . ' – ' . Data::convertDateFromDb($c['begin'] ?? ''),
                    'begin' => $c['begin'] ?? '',
                    'approve_until' => $c['approve_until'] ?? null,
                    'participation_stats' => $stats,
                ];
            }
        }

        usort($events, function ($a, $b) {
            return strcmp($a['begin'] ?? '', $b['begin'] ?? '');
        });

        return ['count' => count($events), 'events' => array_slice($events, 0, 10)];
    }

    private function getAdminMissedDeadlines($uid) {
        global $system_data;
        $now = date('Y-m-d H:i:s');
        $events = [];

        $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            $approveUntil = $r['approve_until'] ?? null;
            if (!$approveUntil) continue;
            if ($approveUntil >= $now) continue;
            $rid = (int) ($r['id'] ?? 0);
            $stats = $this->getParticipationStatsForRehearsal($rid);
            if ((int) ($stats['pending'] ?? 0) > 0) {
                $events[] = [
                    'id' => $rid,
                    'otype' => 'R',
                    'title' => Lang::txt('StartData_inboxItems.rehearsalOn') . ' ' . Data::convertDateFromDb($r['begin'] ?? ''),
                    'begin' => $r['begin'] ?? '',
                    'approve_until' => $approveUntil,
                ];
            }
        }

        $allConcerts = $this->data->adp()->getFutureConcerts($uid);
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            $approveUntil = $c['approve_until'] ?? null;
            if (!$approveUntil) continue;
            if ($approveUntil >= $now) continue;
            $cid = (int) ($c['id'] ?? 0);
            $stats = $this->getParticipationStatsForConcert($cid);
            if ((int) ($stats['pending'] ?? 0) > 0) {
                $events[] = [
                    'id' => $cid,
                    'otype' => 'C',
                    'title' => ($c['title'] ?? '') . ' – ' . Data::convertDateFromDb($c['begin'] ?? ''),
                    'begin' => $c['begin'] ?? '',
                    'approve_until' => $approveUntil,
                ];
            }
        }

        usort($events, function ($a, $b) {
            return strcmp($a['approve_until'] ?? '', $b['approve_until'] ?? '');
        });

        return ['count' => count($events), 'events' => array_slice($events, 0, 10)];
    }

    private function getAdminVotesSummary() {
        $votesSel = $this->voteData->getAllActiveVotes();
        $openVotes = [];
        $closingSoon = [];
        $now = time();
        $closingSoonCutoff = $now + 48 * 3600;

        if (is_array($votesSel)) {
            for ($i = 1; $i < count($votesSel); $i++) {
                $v = $votesSel[$i];
                $end = $v['end'] ?? null;
                if (!$end) continue;
                $endTs = strtotime($end);
                if ($endTs <= $now) continue;
                $vid = (int) ($v['id'] ?? 0);
                $entry = [
                    'id' => $vid,
                    'name' => $v['name'] ?? '',
                    'end' => $end,
                ];
                $openVotes[] = $entry;
                if ($endTs <= $closingSoonCutoff) {
                    $closingSoon[] = $entry;
                }
            }
        }

        return [
            'open_count' => count($openVotes),
            'open_votes' => $openVotes,
            'votes_closing_soon' => $closingSoon,
        ];
    }

    private function getAdminTasksOverview() {
        $taskData = new AufgabenData();
        $rows = $taskData->getTasks(true);
        $openCount = 0;
        $overdueCount = 0;
        $now = date('Y-m-d H:i:s');

        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $t = $rows[$i];
                $openCount++;
                $dueAt = $t['due_at'] ?? null;
                if ($dueAt && $dueAt !== '-' && $dueAt < $now) {
                    $overdueCount++;
                }
            }
        }

        return [
            'open_count' => $openCount,
            'overdue_count' => $overdueCount,
        ];
    }

    private function getAdminCancellations($uid) {
        global $system_data;
        $totalCount = 0;

        $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            $stats = $this->getParticipationStatsForRehearsal((int) ($r['id'] ?? 0));
            $totalCount += (int) ($stats['no'] ?? 0);
        }

        $allConcerts = $this->data->adp()->getFutureConcerts($uid);
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            $stats = $this->getParticipationStatsForConcert((int) ($c['id'] ?? 0));
            $totalCount += (int) ($stats['no'] ?? 0);
        }

        return [
            'total_count' => $totalCount,
        ];
    }

    private function getAdminInstrumentGaps($uid) {
        global $system_data;
        $minimumsByType = $this->getInstrumentMinimumsFromConfig();
        $coverageMode = (($minimumsByType['mode'] ?? 'instrument') === 'section') ? 'section' : 'instrument';
        $rehearsalMinimums = is_array($minimumsByType['rehearsal'] ?? null) ? $minimumsByType['rehearsal'] : [];
        $concertMinimums = is_array($minimumsByType['concert'] ?? null) ? $minimumsByType['concert'] : [];
        if (count($rehearsalMinimums) < 1 && count($concertMinimums) < 1) {
            return ['count' => 0, 'events' => []];
        }

        $eventsWithGaps = [];
        $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            $gaps = $this->getInstrumentGapsForRehearsal((int) ($r['id'] ?? 0), $rehearsalMinimums, $coverageMode);
            if (!empty($gaps)) {
                $eventsWithGaps[] = [
                    'id' => (int) ($r['id'] ?? 0),
                    'otype' => 'R',
                    'title' => Lang::txt('StartData_inboxItems.rehearsalOn') . ' ' . Data::convertDateFromDb($r['begin'] ?? ''),
                    'begin' => $r['begin'] ?? '',
                    'gaps' => $gaps,
                ];
            }
        }

        $allConcerts = $this->data->adp()->getFutureConcerts($uid);
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            $gaps = $this->getInstrumentGapsForConcert((int) ($c['id'] ?? 0), $concertMinimums, $coverageMode);
            if (!empty($gaps)) {
                $eventsWithGaps[] = [
                    'id' => (int) ($c['id'] ?? 0),
                    'otype' => 'C',
                    'title' => ($c['title'] ?? '') . ' – ' . Data::convertDateFromDb($c['begin'] ?? ''),
                    'begin' => $c['begin'] ?? '',
                    'gaps' => $gaps,
                ];
            }
        }

        return ['count' => count($eventsWithGaps), 'events' => array_slice($eventsWithGaps, 0, 10)];
    }

    private function normalizeMinimumMap($input, $mode = 'instrument') {
        if ($mode === 'section' && !$this->isSectionCoverageEnabled()) {
            $mode = 'instrument';
        }
        $out = [];
        if (!is_array($input)) {
            return $out;
        }
        foreach ($input as $key => $value) {
            $min = is_numeric($value) ? max(0, (int) $value) : 0;
            if ($min < 1) {
                continue;
            }
            $keyStr = trim((string) $key);
            if ($keyStr === '') {
                continue;
            }
            if (is_numeric($keyStr) && (int) $keyStr > 0) {
                $out[(string) ((int) $keyStr)] = $min;
                continue;
            }
            if ($mode === 'section' && str_starts_with($keyStr, 'section:')) {
                $sectionId = trim(substr($keyStr, 8));
                if ($sectionId !== '') {
                    $out['section:' . $sectionId] = $min;
                }
            }
        }
        return $out;
    }

    private function getInstrumentMinimumsFromConfig() {
        global $system_data;
        $sectionCoverageEnabled = $this->isSectionCoverageEnabled();
        $val = $system_data->getDynamicConfigParameter('instrument_minimums');
        if (!$val) return ['mode' => 'instrument', 'rehearsal' => [], 'concert' => []];
        $decoded = json_decode($val, true);
        if (!is_array($decoded)) {
            return ['mode' => 'instrument', 'rehearsal' => [], 'concert' => []];
        }
        $mode = (($decoded['mode'] ?? 'instrument') === 'section' && $sectionCoverageEnabled) ? 'section' : 'instrument';
        if (isset($decoded['rehearsal']) || isset($decoded['concert'])) {
            $reh = $this->normalizeMinimumMap($decoded['rehearsal'] ?? [], $mode);
            $con = $this->normalizeMinimumMap($decoded['concert'] ?? [], $mode);
            if (count($con) < 1) {
                $con = $reh;
            }
            return ['mode' => $mode, 'rehearsal' => $reh, 'concert' => $con];
        }
        $legacy = $this->normalizeMinimumMap($decoded, 'instrument');
        return ['mode' => 'instrument', 'rehearsal' => $legacy, 'concert' => $legacy];
    }

    private function getInstrumentSections() {
        global $system_data;
        if (!$this->isSectionCoverageEnabled()) {
            return [];
        }
        $raw = (string) ($system_data->getDynamicConfigParameter('nextgen_instrument_sections') ?? '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isSectionCoverageEnabled(): bool {
        global $system_data;
        return (string) ($system_data->getDynamicConfigParameter('beta_section_coverage_enabled') ?? '') === '1';
    }

    private function getResolvedSectionsForCoverage() {
        $sections = $this->getInstrumentSections();
        $out = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $sectionId = trim((string) ($section['id'] ?? ''));
            $sectionName = trim((string) ($section['name'] ?? ''));
            if ($sectionId === '' || $sectionName === '') {
                continue;
            }
            $ids = [];
            foreach (($section['instrument_ids'] ?? []) as $rawId) {
                $iid = (int) $rawId;
                if ($iid > 0) {
                    $ids[] = $iid;
                }
            }
            $targetMap = [];
            foreach (($section['concert_instrument_targets'] ?? []) as $target) {
                if (!is_array($target)) {
                    continue;
                }
                $targetInstrumentId = (int) ($target['instrument_id'] ?? 0);
                $targetRequired = max(0, (int) ($target['required'] ?? 0));
                if ($targetInstrumentId > 0 && $targetRequired > 0) {
                    $targetMap[$targetInstrumentId] = $targetRequired;
                    $ids[] = $targetInstrumentId;
                }
            }
            $rehearsalMinTotal = max(0, (int) ($section['rehearsal_min_total'] ?? 0));
            $concertMinTotal = max(0, (int) ($section['concert_min_total'] ?? 0));
            if ($rehearsalMinTotal < 1) {
                $legacyMin = (int) ($section['minimum_total'] ?? 0);
                if ($legacyMin > 0) {
                    $rehearsalMinTotal = $legacyMin;
                }
            }
            $out[] = [
                'id' => $sectionId,
                'name' => $sectionName,
                'instrument_ids' => array_values(array_unique($ids)),
                'rehearsal_min_total' => $rehearsalMinTotal,
                'concert_min_total' => $concertMinTotal,
                'concert_instrument_targets' => $targetMap,
            ];
        }
        return $out;
    }

    /**
     * Get list of instruments (id, name, family/category). Available to any authenticated user.
     */
    private function getInstruments() {
        global $system_data;
        if (!Auth::check()) {
            Response::error('Not authenticated', 401);
        }
        $query = "SELECT i.id, i.name, c.id AS category_id, c.name AS category_name
                  FROM instrument i
                  LEFT JOIN category c ON i.category = c.id
                  ORDER BY c.name ASC, i.name ASC";
        $rows = $system_data->dbcon->getSelection($query, []);
        $list = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $list[] = [
                    'id' => (int) ($r['id'] ?? 0),
                    'name' => $r['name'] ?? '',
                    'category_id' => (int) ($r['category_id'] ?? 0),
                    'category_name' => $r['category_name'] ?? '',
                ];
            }
        }
        $stats = ['count' => 0, 'samples' => []];
        $list = TextNormalizer::normalizeFieldsRecursive($list, ['name', 'category_name'], $stats, true);
        TextNormalizer::logStats('dashboard', 'getInstruments', $stats);
        return ['instruments' => $list];
    }

    /**
     * Get instrument minimums config. Available to any authenticated user.
     */
    private function getInstrumentMinimums() {
        if (!Auth::check()) {
            Response::error('Not authenticated', 401);
        }
        $minimumsWithMode = $this->getInstrumentMinimumsFromConfig();
        return [
            'mode' => (string) ($minimumsWithMode['mode'] ?? 'instrument'),
            'minimums' => [
                'rehearsal' => is_array($minimumsWithMode['rehearsal'] ?? null) ? $minimumsWithMode['rehearsal'] : [],
                'concert' => is_array($minimumsWithMode['concert'] ?? null) ? $minimumsWithMode['concert'] : [],
            ],
            'aliasPools' => [],
            'sections' => $this->getInstrumentSections(),
        ];
    }

    /**
     * Set instrument minimums (admin-only). Expects POST body: { minimums: { [instrumentId]: minimum } }
     */
    private function setInstrumentMinimums() {
        global $system_data;
        $uid = Auth::getUserId();
        if (!$uid) {
            Response::error('Not authenticated', 401);
        }
        if (!$system_data->isUserSuperUser($uid) && !$system_data->isUserMemberGroup(1, $uid)) {
            Response::error('Admin access required', 403);
        }
        $rawInput = file_get_contents('php://input');
        $data = $rawInput ? json_decode($rawInput, true) : $_POST;
        $sectionCoverageEnabled = $this->isSectionCoverageEnabled();
        $mode = (($data['mode'] ?? 'instrument') === 'section' && $sectionCoverageEnabled) ? 'section' : 'instrument';
        $minimums = $data['minimums'] ?? [];
        if (!is_array($minimums)) {
            $minimums = [];
        }
        if (isset($minimums['rehearsal']) || isset($minimums['concert'])) {
            $rehRaw = is_array($minimums['rehearsal'] ?? null) ? $minimums['rehearsal'] : [];
            $conRaw = is_array($minimums['concert'] ?? null) ? $minimums['concert'] : [];
            $reh = $this->normalizeMinimumMap($rehRaw, $mode);
            $con = $this->normalizeMinimumMap($conRaw, $mode);
            if (count($con) < 1) {
                $con = $reh;
            }
            $sanitized = ['mode' => $mode, 'rehearsal' => $reh, 'concert' => $con];
        } else {
            $legacy = $this->normalizeMinimumMap($minimums);
            $sanitized = ['mode' => 'instrument', 'rehearsal' => $legacy, 'concert' => $legacy];
        }
        $json = json_encode($sanitized);
        $existing = $system_data->dbcon->colValue(
            "SELECT value FROM configuration WHERE param = ?",
            "value",
            [['s', 'instrument_minimums']]
        );
        if ($existing !== null && $existing !== false) {
            $system_data->dbcon->execute(
                "UPDATE configuration SET value = ? WHERE param = ?",
                [['s', $json], ['s', 'instrument_minimums']]
            );
        } else {
            $system_data->dbcon->prepStatement(
                "INSERT INTO configuration (param, value, is_active) VALUES (?, ?, 1)",
                [['s', 'instrument_minimums'], ['s', $json]]
            );
        }
        return ['success' => true, 'minimums' => $sanitized];
    }

    private function getInstrumentGapsForRehearsal($rid, $minimums, $mode = 'instrument') {
        global $system_data;
        $query = "SELECT i.id as instrument_id, i.name as instrument_name,
                  SUM(CASE WHEN ru.participate IN (1,2) THEN 1 ELSE 0 END) as attending
                  FROM rehearsal_contact rc
                  JOIN contact ct ON rc.contact = ct.id
                  LEFT JOIN instrument i ON ct.instrument = i.id
                  LEFT JOIN user u ON u.contact = ct.id
                  LEFT JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
                  WHERE rc.rehearsal = ?
                  GROUP BY i.id, i.name";
        $rows = $system_data->dbcon->getSelection($query, [['i', $rid], ['i', $rid]]);
        $gaps = [];
        $attendingByInstrument = [];
        $instrumentNamesById = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $instId = (int) ($row['instrument_id'] ?? 0);
                if ($instId <= 0) continue;
                $attending = (int) ($row['attending'] ?? 0);
                $attendingByInstrument[$instId] = $attending;
                $instrumentNamesById[$instId] = (string) ($row['instrument_name'] ?? '');
                $min = isset($minimums[$instId]) ? (int) $minimums[$instId] : null;
                if ($min === null || $min <= 0) continue;
                if ($attending < $min) {
                    $gaps[] = [
                        'instrument_id' => $instId,
                        'instrument_name' => $instrumentNamesById[$instId] ?? '',
                        'current' => $attending,
                        'minimum' => $min,
                    ];
                }
            }
        }
        if ($mode === 'section') {
            $assignedInstrumentIds = [];
            foreach ($this->getResolvedSectionsForCoverage() as $section) {
                $sectionId = (string) ($section['id'] ?? '');
                if ($sectionId === '') {
                    continue;
                }
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $assignedInstrumentIds[$iid] = true;
                    }
                }
                $min = max(
                    (int) ($section['rehearsal_min_total'] ?? 0),
                    isset($minimums['section:' . $sectionId]) ? (int) $minimums['section:' . $sectionId] : 0
                );
                if ($min < 1) {
                    continue;
                }
                $current = 0;
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $current += (int) ($attendingByInstrument[$iid] ?? 0);
                    }
                }
                if ($current < $min) {
                    $gaps[] = [
                        'instrument_id' => 'section:' . $sectionId,
                        'instrument_name' => (string) ($section['name'] ?? $sectionId),
                        'current' => $current,
                        'minimum' => $min,
                    ];
                }
            }
            foreach ($minimums as $instrumentId => $minRaw) {
                if (!is_numeric($instrumentId)) {
                    continue;
                }
                $iid = (int) $instrumentId;
                if ($iid < 1 || isset($assignedInstrumentIds[$iid])) {
                    continue;
                }
                $min = (int) $minRaw;
                $current = (int) ($attendingByInstrument[$iid] ?? 0);
                if ($min > 0 && $current < $min) {
                    $name = '';
                    if (is_array($rows)) {
                        for ($i = 1; $i < count($rows); $i++) {
                            if ((int) ($rows[$i]['instrument_id'] ?? 0) === $iid) {
                                $name = (string) ($rows[$i]['instrument_name'] ?? '');
                                break;
                            }
                        }
                    }
                    $gaps[] = [
                        'instrument_id' => $iid,
                        'instrument_name' => $name !== '' ? $name : ($instrumentNamesById[$iid] ?? ''),
                        'current' => $current,
                        'minimum' => $min,
                    ];
                }
            }
            return $gaps;
        }
        foreach ($minimums as $instrumentId => $minRaw) {
            if (!is_numeric($instrumentId)) {
                continue;
            }
            $iid = (int) $instrumentId;
            if ($iid < 1) {
                continue;
            }
            $min = (int) $minRaw;
            if ($min < 1) {
                continue;
            }
            $current = (int) ($attendingByInstrument[$iid] ?? 0);
            if ($current < $min) {
                $gaps[] = [
                    'instrument_id' => $iid,
                    'instrument_name' => $instrumentNamesById[$iid] ?? '',
                    'current' => $current,
                    'minimum' => $min,
                ];
            }
        }
        return $gaps;
    }

    private function getInstrumentGapsForConcert($cid, $minimums, $mode = 'instrument') {
        global $system_data;
        $query = "SELECT i.id as instrument_id, i.name as instrument_name,
                  SUM(CASE WHEN cu.participate IN (1,2) THEN 1 ELSE 0 END) as attending
                  FROM concert_contact cc
                  JOIN contact ct ON cc.contact = ct.id
                  LEFT JOIN instrument i ON ct.instrument = i.id
                  LEFT JOIN user u ON u.contact = ct.id
                  LEFT JOIN concert_user cu ON cu.user = u.id AND cu.concert = ?
                  WHERE cc.concert = ?
                  GROUP BY i.id, i.name";
        $rows = $system_data->dbcon->getSelection($query, [['i', $cid], ['i', $cid]]);
        $gaps = [];
        $attendingByInstrument = [];
        $instrumentNamesById = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $instId = (int) ($row['instrument_id'] ?? 0);
                if ($instId <= 0) continue;
                $attending = (int) ($row['attending'] ?? 0);
                $attendingByInstrument[$instId] = $attending;
                $instrumentNamesById[$instId] = (string) ($row['instrument_name'] ?? '');
                $min = isset($minimums[$instId]) ? (int) $minimums[$instId] : null;
                if ($min === null || $min <= 0) continue;
                if ($attending < $min) {
                    $gaps[] = [
                        'instrument_id' => $instId,
                        'instrument_name' => $instrumentNamesById[$instId] ?? '',
                        'current' => $attending,
                        'minimum' => $min,
                    ];
                }
            }
        }
        if ($mode === 'section') {
            $assignedInstrumentIds = [];
            foreach ($this->getResolvedSectionsForCoverage() as $section) {
                $sectionId = (string) ($section['id'] ?? '');
                if ($sectionId === '') {
                    continue;
                }
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $assignedInstrumentIds[$iid] = true;
                    }
                }

                $targets = is_array($section['concert_instrument_targets'] ?? null) ? $section['concert_instrument_targets'] : [];
                if (count($targets) > 0) {
                    foreach ($targets as $targetInstrumentId => $requiredRaw) {
                        $targetId = (int) $targetInstrumentId;
                        $required = (int) $requiredRaw;
                        if ($targetId < 1 || $required < 1) {
                            continue;
                        }
                        $current = (int) ($attendingByInstrument[$targetId] ?? 0);
                        if ($current < $required) {
                            $gaps[] = [
                                'instrument_id' => 'section:' . $sectionId,
                                'instrument_name' => (string) ($section['name'] ?? $sectionId),
                                'current' => $current,
                                'minimum' => $required,
                            ];
                        }
                    }
                    continue;
                }

                $min = max(
                    (int) ($section['concert_min_total'] ?? 0),
                    isset($minimums['section:' . $sectionId]) ? (int) $minimums['section:' . $sectionId] : 0
                );
                if ($min < 1) {
                    continue;
                }
                $currentTotal = 0;
                foreach (($section['instrument_ids'] ?? []) as $instrumentId) {
                    $iid = (int) $instrumentId;
                    if ($iid > 0) {
                        $currentTotal += (int) ($attendingByInstrument[$iid] ?? 0);
                    }
                }
                if ($currentTotal < $min) {
                    $gaps[] = [
                        'instrument_id' => 'section:' . $sectionId,
                        'instrument_name' => (string) ($section['name'] ?? $sectionId),
                        'current' => $currentTotal,
                        'minimum' => $min,
                    ];
                }
            }
            foreach ($minimums as $instrumentId => $minRaw) {
                if (!is_numeric($instrumentId)) {
                    continue;
                }
                $iid = (int) $instrumentId;
                if ($iid < 1 || isset($assignedInstrumentIds[$iid])) {
                    continue;
                }
                $min = (int) $minRaw;
                $current = (int) ($attendingByInstrument[$iid] ?? 0);
                if ($min > 0 && $current < $min) {
                    $gaps[] = [
                        'instrument_id' => $iid,
                        'instrument_name' => $instrumentNamesById[$iid] ?? '',
                        'current' => $current,
                        'minimum' => $min,
                    ];
                }
            }
            return $gaps;
        }
        foreach ($minimums as $instrumentId => $minRaw) {
            if (!is_numeric($instrumentId)) {
                continue;
            }
            $iid = (int) $instrumentId;
            if ($iid < 1) {
                continue;
            }
            $min = (int) $minRaw;
            if ($min < 1) {
                continue;
            }
            $current = (int) ($attendingByInstrument[$iid] ?? 0);
            if ($current < $min) {
                $gaps[] = [
                    'instrument_id' => $iid,
                    'instrument_name' => $instrumentNamesById[$iid] ?? '',
                    'current' => $current,
                    'minimum' => $min,
                ];
            }
        }
        return $gaps;
    }

    private function getAdminPendingInvitations($uid) {
        global $system_data;
        $events = [];
        $totalMembers = 0;

        $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
        for ($i = 1; $i < count($allRehearsals); $i++) {
            $r = $allRehearsals[$i];
            $rid = (int) ($r['id'] ?? 0);
            $pending = $this->getPendingUsersForRehearsal($rid);
            if (!empty($pending)) {
                $events[] = [
                    'id' => $rid,
                    'otype' => 'R',
                    'title' => Lang::txt('StartData_inboxItems.rehearsalOn') . ' ' . Data::convertDateFromDb($r['begin'] ?? ''),
                    'begin' => $r['begin'] ?? '',
                    'pending_users' => $pending,
                ];
                $totalMembers += count($pending);
            }
        }

        $allConcerts = $this->data->adp()->getFutureConcerts($uid);
        for ($i = 1; $i < count($allConcerts); $i++) {
            $c = $allConcerts[$i];
            $cid = (int) ($c['id'] ?? 0);
            $pending = $this->getPendingUsersForConcert($cid);
            if (!empty($pending)) {
                $events[] = [
                    'id' => $cid,
                    'otype' => 'C',
                    'title' => ($c['title'] ?? '') . ' – ' . Data::convertDateFromDb($c['begin'] ?? ''),
                    'begin' => $c['begin'] ?? '',
                    'pending_users' => $pending,
                ];
                $totalMembers += count($pending);
            }
        }

        usort($events, function ($a, $b) {
            return strcmp($a['begin'] ?? '', $b['begin'] ?? '');
        });

        return [
            'count' => count($events),
            'total_members' => $totalMembers,
            'events' => array_slice($events, 0, 10),
        ];
    }

    private function getPendingUsersForRehearsal($rid) {
        global $system_data;
        $query = "SELECT u.id, CONCAT(ct.name, ' ', ct.surname) as name
                  FROM rehearsal_contact rc
                  JOIN contact ct ON rc.contact = ct.id
                  JOIN user u ON u.contact = ct.id
                  LEFT JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = ?
                  WHERE rc.rehearsal = ? AND (ru.participate IS NULL OR ru.participate < 0)";
        $rows = $system_data->dbcon->getSelection($query, [['i', $rid], ['i', $rid]]);
        $list = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $list[] = ['id' => (int) $r['id'], 'name' => $r['name'] ?? ''];
            }
        }
        return $list;
    }

    private function getPendingUsersForConcert($cid) {
        global $system_data;
        $query = "SELECT u.id, CONCAT(ct.name, ' ', ct.surname) as name
                  FROM concert_contact cc
                  JOIN contact ct ON cc.contact = ct.id
                  JOIN user u ON u.contact = ct.id
                  LEFT JOIN concert_user cu ON cu.user = u.id AND cu.concert = ?
                  WHERE cc.concert = ? AND (cu.participate IS NULL OR cu.participate < 0)";
        $rows = $system_data->dbcon->getSelection($query, [['i', $cid], ['i', $cid]]);
        $list = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $list[] = ['id' => (int) $r['id'], 'name' => $r['name'] ?? ''];
            }
        }
        return $list;
    }

    private function getAdminUpcomingEvents($uid) {
        $allInbox = $this->getAllInboxItems();
        $formatted = $this->formatInboxItems($allInbox);
        usort($formatted, function ($a, $b) {
            $da = $a['eventBegin'] ?? $a['replyUntil'] ?? '';
            $db = $b['eventBegin'] ?? $b['replyUntil'] ?? '';
            return strcmp($da, $db);
        });
        return ['events' => array_slice($formatted, 0, 7)];
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
