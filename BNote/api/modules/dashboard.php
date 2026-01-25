<?php
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
require_once __DIR__ . '/../../src/data/modules/startdata.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class DashboardModule {
    private $data;
    
    public function __construct() {
        // Check module permission (Start module is usually public, but check anyway)
        global $system_data;
        $moduleId = $system_data->getModuleId('Start');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Dashboard', 403);
        }
        
        $this->data = new StartData();
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
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    private function getDashboard() {
        global $system_data;
        
        $news = $this->data->getNews();
        $inboxItems = $this->data->getInboxItems();
        
        // Format inbox items (inboxItems is already an array, not a database selection)
        $formattedInbox = [];
        foreach ($inboxItems as $item) {
            // Get raw database date (YYYY-MM-DD HH:MM:SS format) for JavaScript parsing
            // The 'due' field is already formatted by convertDateFromDb for display
            $dueDateRaw = $item['replyUntil'] ?? null;
            // JavaScript can parse YYYY-MM-DD HH:MM:SS format directly
            // Only include if it's a valid date string (not null, not '-', has minimum length)
            if ($dueDateRaw && $dueDateRaw !== '-' && strlen(trim($dueDateRaw)) >= 10) {
                $dueDateISO = trim($dueDateRaw);
            } else {
                $dueDateISO = null;
            }
            
            $formattedInbox[] = [
                'otype' => $item['otype'] ?? null,
                'oid' => $item['oid'] ?? null,
                'title' => $item['title'] ?? '',
                'preview' => $item['preview'] ?? '',
                'dueDate' => $dueDateISO, // ISO format for JavaScript parsing
                'dueDateFormatted' => $item['due'] ?? null, // Human-readable format (already formatted)
                'participation' => $item['participation'] ?? null,
                'eventBegin' => $item['eventBegin'] ?? null,
                'replyUntil' => $item['replyUntil'] ?? null
            ];
        }
        
        // Get stats
        $futureRehearsals = $this->data->adp()->getFutureRehearsals();
        $futureConcerts = $this->data->adp()->getFutureConcerts();
        
        return [
            'news' => $news ?: [],
            'inbox' => $formattedInbox,
            'stats' => [
                'upcoming_rehearsals' => max(0, count($futureRehearsals) - 1),
                'upcoming_concerts' => max(0, count($futureConcerts) - 1)
            ]
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
