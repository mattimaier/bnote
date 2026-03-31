<?php
/**
 * BNote Next Generation - Search API Module
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
 * Search API module
 * Handles full-text search across all modules
 * 
 * Note: This file is loaded after api/index.php has changed working directory to project root
 */
require_once __DIR__ . '/../searchdata.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class SearchModule {
    private $data;
    private $categoryPermissions = [];
    private $hasRehearsalsModule = false;
    private $hasConcertsModule = false;
    private $hasContactsModule = false;
    private $hasMembersModule = false;
    private $visibleRehearsalIds = [];
    private $visibleConcertIds = [];
    private $visibleContactIds = [];

    private function filterValidItems($items) {
        if (!is_array($items)) {
            return array();
        }
        $filtered = array();
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (!isset($item['id'])) continue;
            $id = intval($item['id']);
            if ($id > 0) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        try {
            $this->data = new SearchData();
            $this->initVisibilityContext((int) Auth::getUserId());
            $this->categoryPermissions = $this->buildCategoryPermissions();
        } catch (Exception $e) {
            error_log('Failed to create SearchData: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            Response::error('Failed to initialize search: ' . $e->getMessage(), 500);
        }
    }

    private function hasAnyModulePermission($moduleNames) {
        global $system_data;
        if (!is_array($moduleNames)) {
            $moduleNames = [$moduleNames];
        }
        foreach ($moduleNames as $moduleName) {
            $moduleId = $system_data->getModuleId($moduleName);
            if ($moduleId && $system_data->userHasPermission($moduleId)) {
                return true;
            }
        }
        return false;
    }

    private function buildCategoryPermissions() {
        $contactVisible = $this->hasContactsModule || $this->hasMembersModule || count($this->visibleContactIds) > 0;
        return [
            'rehearsal' => $this->hasRehearsalsModule || count($this->visibleRehearsalIds) > 0,
            'concert' => $this->hasConcertsModule || count($this->visibleConcertIds) > 0,
            'performance' => $this->hasConcertsModule || count($this->visibleConcertIds) > 0,
            'user' => $this->hasAnyModulePermission(['User']),
            'contact' => $contactVisible,
            'task' => $this->hasAnyModulePermission(['Aufgaben']),
            'repertoire' => $this->hasAnyModulePermission(['Repertoire']),
            'location' => $this->hasAnyModulePermission(['Locations']),
            'equipment' => $this->hasAnyModulePermission(['Equipment']),
            'outfit' => $this->hasAnyModulePermission(['Outfits']),
            'song' => $this->hasAnyModulePermission(['Repertoire']),
            'vote' => $this->hasAnyModulePermission(['Abstimmung']),
        ];
    }

    private function canSearchCategory($category) {
        return !empty($this->categoryPermissions[$category]);
    }

    private function initVisibilityContext($userId) {
        global $system_data;
        $rehearsalsModuleId = $system_data->getModuleId('Proben');
        $concertsModuleId = $system_data->getModuleId('Konzerte');
        $contactsModuleId = $system_data->getModuleId('Kontakte');
        $membersModuleId = $system_data->getModuleId('Mitspieler');

        $this->hasRehearsalsModule = $rehearsalsModuleId ? $system_data->userHasPermission($rehearsalsModuleId) : false;
        $this->hasConcertsModule = $concertsModuleId ? $system_data->userHasPermission($concertsModuleId) : false;
        $this->hasContactsModule = $contactsModuleId ? $system_data->userHasPermission($contactsModuleId) : false;
        $this->hasMembersModule = $membersModuleId ? $system_data->userHasPermission($membersModuleId) : false;

        $this->visibleRehearsalIds = $this->getUserRehearsalIds($userId);
        $this->visibleConcertIds = $this->getConcertIdsForUser($userId);
        $this->visibleContactIds = $this->getSameGroupContactIds($userId);
    }

    private function filterByAllowedIds($items, $allowedIds) {
        if (!is_array($items)) {
            return [];
        }
        if (count($allowedIds) === 0) {
            return [];
        }
        $allowed = array_fill_keys(array_map('intval', $allowedIds), true);
        $filtered = [];
        foreach ($items as $item) {
            $id = isset($item['id']) ? (int) $item['id'] : 0;
            if ($id > 0 && isset($allowed[$id])) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    private function applyVisibilityFilter($category, $items) {
        if ($category === 'rehearsal' && !$this->hasRehearsalsModule) {
            return $this->filterByAllowedIds($items, $this->visibleRehearsalIds);
        }
        if ($category === 'concert' && !$this->hasConcertsModule) {
            return $this->filterByAllowedIds($items, $this->visibleConcertIds);
        }
        if ($category === 'contact' && !$this->hasContactsModule) {
            return $this->filterByAllowedIds($items, $this->visibleContactIds);
        }
        return $items;
    }

    private function getUserRehearsalIds($userId) {
        global $system_data;
        if ($system_data->isUserSuperUser($userId) || $this->hasRehearsalsModule) {
            $allRehearsals = $this->data->adp()->getFutureRehearsals(true);
            $ids = [];
            for ($i = 1; $i < count($allRehearsals); $i++) {
                $ids[] = (int) ($allRehearsals[$i]['id'] ?? 0);
            }
            return array_values(array_unique(array_filter($ids)));
        }

        $usersPhases = $this->data->adp()->getUsersPhases($userId);
        $rehearsals = array_merge(
            $this->getRehearsalsForUser($userId),
            $this->getRehearsalsForPhases($usersPhases)
        );
        return array_values(array_unique(array_map('intval', array_filter($rehearsals))));
    }

    private function getRehearsalsForUser($userId) {
        global $system_data;
        $query = "SELECT rehearsal FROM rehearsal_contact rc JOIN contact c ON rc.contact = c.id JOIN user u ON u.contact = c.id WHERE u.id = ?";
        $sel = $system_data->dbcon->getSelection($query, [['i', (int) $userId]]);
        $ids = [];
        if (is_array($sel)) {
            for ($i = 1; $i < count($sel); $i++) {
                $ids[] = (int) ($sel[$i]['rehearsal'] ?? 0);
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function getRehearsalsForPhases($phases) {
        if (!is_array($phases) || count($phases) === 0) return [];

        global $system_data;
        $params = [];
        $whereQ = [];
        foreach ($phases as $p) {
            $whereQ[] = 'rehearsalphase = ?';
            $params[] = ['i', (int) $p];
        }
        $query = 'SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ);
        $sel = $system_data->dbcon->getSelection($query, $params);
        $ids = [];
        if (is_array($sel)) {
            for ($i = 1; $i < count($sel); $i++) {
                $ids[] = (int) ($sel[$i]['id'] ?? 0);
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function getConcertIdsForUser($uid) {
        global $system_data;
        $concerts = ($system_data->isUserSuperUser($uid) || $this->hasConcertsModule)
            ? $this->data->adp()->getFutureConcerts()
            : $this->data->adp()->getFutureConcerts($uid);
        $ids = [];
        if (is_array($concerts)) {
            for ($i = 1; $i < count($concerts); $i++) {
                $ids[] = (int) ($concerts[$i]['id'] ?? 0);
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function getSameGroupContactIds($userId) {
        global $system_data;
        $rows = $system_data->dbcon->getSelection(
            'SELECT DISTINCT cg2.contact as id
             FROM user u
             JOIN contact_group cg1 ON cg1.contact = u.contact
             JOIN contact_group cg2 ON cg2.`group` = cg1.`group`
             WHERE u.id = ?',
            [['i', (int) $userId]]
        );
        $ids = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $ids[] = (int) ($rows[$i]['id'] ?? 0);
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }
    
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if ($method !== 'GET') {
            Response::error('Method not supported', 405);
        }
        
        // Check for special actions
        $action = $_GET['action'] ?? null;
        if ($action === 'years') {
            // Return available years from events
            if (!$this->canSearchCategory('rehearsal') && !$this->canSearchCategory('concert')) {
                Response::error('Access denied to search years', 403);
            }
            try {
                $years = $this->data->getAvailableYears();
                Response::success($years);
            } catch (Exception $e) {
                error_log('Failed to get available years: ' . $e->getMessage());
                Response::error('Failed to get available years: ' . $e->getMessage(), 500);
            }
            return;
        }
        
        // Get search query
        $query = $_GET['q'] ?? $_GET['query'] ?? '';
        
        // Validate query length (minimum 2 characters)
        if (strlen(trim($query)) < 2) {
            Response::error('Search query must be at least 2 characters', 400);
        }
        
        // Get filters
        $filters = array();
        if (isset($_GET['filter']['date_year']) && $_GET['filter']['date_year']) {
            $filters['date_year'] = intval($_GET['filter']['date_year']);
        }
        if (isset($_GET['filter']['date_month']) && $_GET['filter']['date_month']) {
            $filters['date_month'] = intval($_GET['filter']['date_month']);
        }
        if (isset($_GET['filter']['module_type']) && $_GET['filter']['module_type']) {
            $filters['module_type'] = $_GET['filter']['module_type'];
        }
        
        // Get limit per category
        // If limit is not provided in URL, return all results (unlimited)
        // If limit is provided, use it (typically 5 for overlay typeahead)
        if (!isset($_GET['limit']) || $_GET['limit'] === '') {
            // No limit parameter - return all results
            $limit = 999999; // Effectively unlimited for practical purposes
        } else {
            $limit = intval($_GET['limit']);
            // If limit is 0, treat as unlimited
            if ($limit === 0) {
                $limit = 999999;
            } else if ($limit > 50) {
                $limit = 50; // Cap at 50 for safety
            }
        }
        
        // Perform searches across all modules
        $results = array(
            'rehearsals' => array(),
            'concerts' => array(),
            'users' => array(),
            'contacts' => array(),
            'tasks' => array(),
            'repertoire' => array(),
            'locations' => array(),
            'equipment' => array(),
            'outfits' => array(),
            'songs' => array(),
            'votes' => array()
        );
        
        try {
            // Filter by module type if specified
            $moduleType = isset($filters['module_type']) ? $filters['module_type'] : null;
            if ($moduleType && !$this->canSearchCategory($moduleType)) {
                Response::error('Access denied to this search category', 403);
            }
            
            // Store totals for each category
            $totals = array(
                'rehearsals' => 0,
                'concerts' => 0,
                'users' => 0,
                'contacts' => 0,
                'tasks' => 0,
                'repertoire' => 0,
                'locations' => 0,
                'equipment' => 0,
                'outfits' => 0,
                'songs' => 0,
                'votes' => 0
            );
            
            if ((!$moduleType || $moduleType === 'rehearsal') && $this->canSearchCategory('rehearsal')) {
                $rehearsalResult = $this->data->searchRehearsals($query, $filters, $limit);
                // Handle both old format (array) and new format (array with 'items' and 'total')
                if (isset($rehearsalResult['items'])) {
                    $results['rehearsals'] = $this->applyVisibilityFilter('rehearsal', $this->filterValidItems($rehearsalResult['items']));
                    $totals['rehearsals'] = count($results['rehearsals']);
                } else {
                    $results['rehearsals'] = $this->applyVisibilityFilter('rehearsal', $this->filterValidItems($rehearsalResult));
                    $totals['rehearsals'] = count($results['rehearsals']);
                }
            }
            if ((!$moduleType || $moduleType === 'concert' || $moduleType === 'performance') && $this->canSearchCategory('concert')) {
                $concertResult = $this->data->searchConcerts($query, $filters, $limit);
                if (isset($concertResult['items'])) {
                    $results['concerts'] = $this->applyVisibilityFilter('concert', $this->filterValidItems($concertResult['items']));
                    $totals['concerts'] = count($results['concerts']);
                } else {
                    $results['concerts'] = $this->applyVisibilityFilter('concert', $this->filterValidItems($concertResult));
                    $totals['concerts'] = count($results['concerts']);
                }
            }
            if ((!$moduleType || $moduleType === 'user') && $this->canSearchCategory('user')) {
                $userResult = $this->data->searchUsers($query, $filters, $limit);
                if (isset($userResult['items'])) {
                    $results['users'] = $this->filterValidItems($userResult['items']);
                    $totals['users'] = count($results['users']);
                } else {
                    $results['users'] = $this->filterValidItems($userResult);
                    $totals['users'] = count($results['users']);
                }
            }
            if ((!$moduleType || $moduleType === 'contact') && $this->canSearchCategory('contact')) {
                $contactResult = $this->data->searchContacts($query, $filters, $limit);
                if (isset($contactResult['items'])) {
                    $results['contacts'] = $this->applyVisibilityFilter('contact', $this->filterValidItems($contactResult['items']));
                    $totals['contacts'] = count($results['contacts']);
                } else {
                    $results['contacts'] = $this->applyVisibilityFilter('contact', $this->filterValidItems($contactResult));
                    $totals['contacts'] = count($results['contacts']);
                }
            }
            if ((!$moduleType || $moduleType === 'task') && $this->canSearchCategory('task')) {
                $taskResult = $this->data->searchTasks($query, $filters, $limit);
                if (isset($taskResult['items'])) {
                    $results['tasks'] = $this->filterValidItems($taskResult['items']);
                    $totals['tasks'] = count($results['tasks']);
                } else {
                    $results['tasks'] = $this->filterValidItems($taskResult);
                    $totals['tasks'] = count($results['tasks']);
                }
            }
            if ((!$moduleType || $moduleType === 'repertoire') && $this->canSearchCategory('repertoire')) {
                $repertoireResult = $this->data->searchRepertoire($query, $filters, $limit);
                if (isset($repertoireResult['items'])) {
                    $results['repertoire'] = $this->filterValidItems($repertoireResult['items']);
                    $totals['repertoire'] = count($results['repertoire']);
                } else {
                    $results['repertoire'] = $this->filterValidItems($repertoireResult);
                    $totals['repertoire'] = count($results['repertoire']);
                }
            }
            if ((!$moduleType || $moduleType === 'location') && $this->canSearchCategory('location')) {
                $locationResult = $this->data->searchLocations($query, $filters, $limit);
                if (isset($locationResult['items'])) {
                    $results['locations'] = $this->filterValidItems($locationResult['items']);
                    $totals['locations'] = count($results['locations']);
                } else {
                    $results['locations'] = $this->filterValidItems($locationResult);
                    $totals['locations'] = count($results['locations']);
                }
            }
            if ((!$moduleType || $moduleType === 'equipment') && $this->canSearchCategory('equipment')) {
                $equipmentResult = $this->data->searchEquipment($query, $filters, $limit);
                if (isset($equipmentResult['items'])) {
                    $results['equipment'] = $this->filterValidItems($equipmentResult['items']);
                    $totals['equipment'] = count($results['equipment']);
                } else {
                    $results['equipment'] = $this->filterValidItems($equipmentResult);
                    $totals['equipment'] = count($results['equipment']);
                }
            }
            if ((!$moduleType || $moduleType === 'outfit') && $this->canSearchCategory('outfit')) {
                $outfitsResult = $this->data->searchOutfits($query, $filters, $limit);
                if (isset($outfitsResult['items'])) {
                    $results['outfits'] = $this->filterValidItems($outfitsResult['items']);
                    $totals['outfits'] = count($results['outfits']);
                } else {
                    $results['outfits'] = $this->filterValidItems($outfitsResult);
                    $totals['outfits'] = count($results['outfits']);
                }
            }
            if ((!$moduleType || $moduleType === 'song') && $this->canSearchCategory('song')) {
                $songsResult = $this->data->searchSongs($query, $filters, $limit);
                if (isset($songsResult['items'])) {
                    $results['songs'] = $this->filterValidItems($songsResult['items']);
                    $totals['songs'] = count($results['songs']);
                } else {
                    $results['songs'] = $this->filterValidItems($songsResult);
                    $totals['songs'] = count($results['songs']);
                }
            }
            if ((!$moduleType || $moduleType === 'vote') && $this->canSearchCategory('vote')) {
                $votesResult = $this->data->searchVotes($query, $filters, $limit);
                if (isset($votesResult['items'])) {
                    $results['votes'] = $this->filterValidItems($votesResult['items']);
                    $totals['votes'] = count($results['votes']);
                } else {
                    $results['votes'] = $this->filterValidItems($votesResult);
                    $totals['votes'] = count($results['votes']);
                }
            }
            
            // Add totals to results
            $results['_totals'] = $totals;
            
            // Calculate total (sum of all category totals)
            $total = array_sum($totals);
            $results['total'] = $total;
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Search error: ' . $e->getMessage());
            Response::error('Search failed: ' . $e->getMessage(), 500);
        }
    }
}
