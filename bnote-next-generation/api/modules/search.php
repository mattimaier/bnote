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
    
    public function __construct() {
        // Check authentication
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        
        try {
            $this->data = new SearchData();
        } catch (Exception $e) {
            error_log('Failed to create SearchData: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            Response::error('Failed to initialize search: ' . $e->getMessage(), 500);
        }
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
            'locations' => array()
        );
        
        try {
            // Filter by module type if specified
            $moduleType = isset($filters['module_type']) ? $filters['module_type'] : null;
            
            // Store totals for each category
            $totals = array(
                'rehearsals' => 0,
                'concerts' => 0,
                'users' => 0,
                'contacts' => 0,
                'tasks' => 0,
                'repertoire' => 0,
                'locations' => 0
            );
            
            if (!$moduleType || $moduleType === 'rehearsal') {
                $rehearsalResult = $this->data->searchRehearsals($query, $filters, $limit);
                // Handle both old format (array) and new format (array with 'items' and 'total')
                if (isset($rehearsalResult['items'])) {
                    $results['rehearsals'] = $rehearsalResult['items'];
                    $totals['rehearsals'] = $rehearsalResult['total'];
                } else {
                    $results['rehearsals'] = $rehearsalResult;
                    $totals['rehearsals'] = count($rehearsalResult);
                }
            }
            if (!$moduleType || $moduleType === 'concert' || $moduleType === 'performance') {
                $concertResult = $this->data->searchConcerts($query, $filters, $limit);
                if (isset($concertResult['items'])) {
                    $results['concerts'] = $concertResult['items'];
                    $totals['concerts'] = $concertResult['total'];
                } else {
                    $results['concerts'] = $concertResult;
                    $totals['concerts'] = count($concertResult);
                }
            }
            if (!$moduleType || $moduleType === 'user') {
                $userResult = $this->data->searchUsers($query, $filters, $limit);
                if (isset($userResult['items'])) {
                    $results['users'] = $userResult['items'];
                    $totals['users'] = $userResult['total'];
                } else {
                    $results['users'] = $userResult;
                    $totals['users'] = count($userResult);
                }
            }
            if (!$moduleType || $moduleType === 'contact') {
                $contactResult = $this->data->searchContacts($query, $filters, $limit);
                if (isset($contactResult['items'])) {
                    $results['contacts'] = $contactResult['items'];
                    $totals['contacts'] = $contactResult['total'];
                } else {
                    $results['contacts'] = $contactResult;
                    $totals['contacts'] = count($contactResult);
                }
            }
            if (!$moduleType || $moduleType === 'task') {
                $taskResult = $this->data->searchTasks($query, $filters, $limit);
                if (isset($taskResult['items'])) {
                    $results['tasks'] = $taskResult['items'];
                    $totals['tasks'] = $taskResult['total'];
                } else {
                    $results['tasks'] = $taskResult;
                    $totals['tasks'] = count($taskResult);
                }
            }
            if (!$moduleType || $moduleType === 'repertoire') {
                $repertoireResult = $this->data->searchRepertoire($query, $filters, $limit);
                if (isset($repertoireResult['items'])) {
                    $results['repertoire'] = $repertoireResult['items'];
                    $totals['repertoire'] = $repertoireResult['total'];
                } else {
                    $results['repertoire'] = $repertoireResult;
                    $totals['repertoire'] = count($repertoireResult);
                }
            }
            if (!$moduleType || $moduleType === 'location') {
                $locationResult = $this->data->searchLocations($query, $filters, $limit);
                if (isset($locationResult['items'])) {
                    $results['locations'] = $locationResult['items'];
                    $totals['locations'] = $locationResult['total'];
                } else {
                    $results['locations'] = $locationResult;
                    $totals['locations'] = count($locationResult);
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
