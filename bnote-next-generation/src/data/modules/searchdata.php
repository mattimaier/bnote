<?php

/**
 * Data Access Class for search functionality.
 * Provides full-text search across all modules.
 * @author BNote Contributors
 */
class SearchData extends AbstractData {
    
    private $probenData;
    private $konzerteData;
    private $kontakteData;
    private $userData;
    private $aufgabenData;
    private $repertoireData;
    private $locationsData;
    
    function __construct($dir_prefix = "") {
        $this->init($dir_prefix);
        
        // Initialize data modules
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "probendata.php";
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "konzertedata.php";
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "kontaktedata.php";
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "userdata.php";
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "aufgabendata.php";
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "repertoiredata.php";
        require_once $dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "locationsdata.php";
        
        $this->probenData = new ProbenData($dir_prefix);
        $this->konzerteData = new KonzerteData($dir_prefix);
        $this->kontakteData = new KontakteData($dir_prefix);
        $this->userData = new UserData($dir_prefix);
        $this->aufgabenData = new AufgabenData($dir_prefix);
        $this->repertoireData = new RepertoireData($dir_prefix);
        $this->locationsData = new LocationsData($dir_prefix);
    }
    
    /**
     * Search rehearsals (past and future)
     * @param string $query Search query
     * @param array $filters Optional filters (date_year, date_month)
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchRehearsals($query, $filters = array(), $limit = 5) {
        global $system_data;
        $userId = $system_data->getUserId();
        
        // Base query with location join
        $baseQuery = "SELECT DISTINCT r.id, r.begin, r.end, r.approve_until, r.notes, r.status, 
                      l.name as location_name, a.city as location_city,
                      CONCAT_WS(' ', c.name, c.surname) as conductor_name,
                      CONCAT('Probe am ', DATE_FORMAT(r.begin, '%d.%m.%Y %H:%i')) as title
                      FROM rehearsal r
                      LEFT JOIN location l ON r.location = l.id
                      LEFT JOIN address a ON l.address = a.id
                      LEFT JOIN contact c ON r.conductor = c.id
                      WHERE (r.notes LIKE CONCAT('%',?,'%') OR l.name LIKE CONCAT('%',?,'%') OR a.city LIKE CONCAT('%',?,'%') OR CONCAT('Probe am ', DATE_FORMAT(r.begin, '%d.%m.%Y %H:%i')) LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query)
        );
        
        // Apply date filters
        if (isset($filters['date_year']) && $filters['date_year']) {
            $baseQuery .= " AND YEAR(r.begin) = ?";
            $params[] = array("i", intval($filters['date_year']));
        }
        
        if (isset($filters['date_month']) && $filters['date_month']) {
            $baseQuery .= " AND MONTH(r.begin) = ?";
            $params[] = array("i", intval($filters['date_month']));
        }
        
        // Apply user permissions - get accessible rehearsals
        $accessibleRehearsalIds = $this->getAccessibleRehearsalIds($userId);
        if (count($accessibleRehearsalIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($accessibleRehearsalIds), '?'));
            $baseQuery .= " AND r.id IN ($placeholders)";
            foreach ($accessibleRehearsalIds as $rid) {
                $params[] = array("i", $rid);
            }
        } else {
            // No accessible rehearsals
            return array('items' => array(), 'total' => 0);
        }
        
        // Get total count first (before LIMIT) - build count query from base query
        $countQuery = "SELECT COUNT(DISTINCT r.id) as total FROM rehearsal r
                      LEFT JOIN location l ON r.location = l.id
                      LEFT JOIN address a ON l.address = a.id
                      LEFT JOIN contact c ON r.conductor = c.id
                      WHERE (r.notes LIKE CONCAT('%',?,'%') OR l.name LIKE CONCAT('%',?,'%') OR a.city LIKE CONCAT('%',?,'%') OR CONCAT('Probe am ', DATE_FORMAT(r.begin, '%d.%m.%Y %H:%i')) LIKE CONCAT('%',?,'%'))";
        $countParams = array(
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query)
        );
        
        // Apply same filters to count query
        if (isset($filters['date_year']) && $filters['date_year']) {
            $countQuery .= " AND YEAR(r.begin) = ?";
            $countParams[] = array("i", intval($filters['date_year']));
        }
        if (isset($filters['date_month']) && $filters['date_month']) {
            $countQuery .= " AND MONTH(r.begin) = ?";
            $countParams[] = array("i", intval($filters['date_month']));
        }
        if (count($accessibleRehearsalIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($accessibleRehearsalIds), '?'));
            $countQuery .= " AND r.id IN ($placeholders)";
            foreach ($accessibleRehearsalIds as $rid) {
                $countParams[] = array("i", $rid);
            }
        }
        
        $countResult = $this->database->getSelection($countQuery, $countParams);
        $totalCount = isset($countResult[1]) ? intval($countResult[1]['total']) : 0;
        
        $baseQuery .= " ORDER BY r.begin DESC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]); // Remove header row
        
        // Format results
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row['id']),
                'otype' => 'R',
                'oid' => intval($row['id']),
                'title' => $row['title'],
                'begin' => $row['begin'],
                'end' => $row['end'] ?? null,
                'location' => $row['location_name'] ?? null,
                'locationCity' => $row['location_city'] ?? null,
                'conductor' => $row['conductor_name'] ?? null,
                'status' => $row['status'] ?? 'planned'
            );
        }
        
        return array('items' => $formatted, 'total' => $totalCount);
    }
    
    /**
     * Search concerts (past and future)
     * @param string $query Search query
     * @param array $filters Optional filters (date_year, date_month)
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchConcerts($query, $filters = array(), $limit = 5) {
        global $system_data;
        $userId = $system_data->getUserId();
        
        // Base query
        $baseQuery = "SELECT DISTINCT c.id, c.title, c.begin, c.end, c.approve_until, c.notes, c.status,
                      l.name as location_name, a.city as location_city
                      FROM concert c
                      LEFT JOIN location l ON c.location = l.id
                      LEFT JOIN address a ON l.address = a.id
                      WHERE (c.title LIKE CONCAT('%',?,'%') OR c.notes LIKE CONCAT('%',?,'%') OR l.name LIKE CONCAT('%',?,'%') OR a.city LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query)
        );
        
        // Apply date filters
        if (isset($filters['date_year']) && $filters['date_year']) {
            $baseQuery .= " AND YEAR(c.begin) = ?";
            $params[] = array("i", intval($filters['date_year']));
        }
        
        if (isset($filters['date_month']) && $filters['date_month']) {
            $baseQuery .= " AND MONTH(c.begin) = ?";
            $params[] = array("i", intval($filters['date_month']));
        }
        
        // Get accessible concerts (same logic as dashboard)
        $accessibleConcertIds = $this->getAccessibleConcertIds($userId);
        if (count($accessibleConcertIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($accessibleConcertIds), '?'));
            $baseQuery .= " AND c.id IN ($placeholders)";
            foreach ($accessibleConcertIds as $cid) {
                $params[] = array("i", $cid);
            }
        } else {
            return array();
        }
        
        $baseQuery .= " ORDER BY c.begin DESC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]);
        
        // Format results
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row['id']),
                'otype' => 'C',
                'oid' => intval($row['id']),
                'title' => $row['title'] ?? 'Concert',
                'begin' => $row['begin'],
                'end' => $row['end'] ?? null,
                'location' => $row['location_name'] ?? null,
                'locationCity' => $row['location_city'] ?? null,
                'status' => $row['status'] ?? 'planned'
            );
        }
        
        return $formatted;
    }
    
    /**
     * Search users
     * @param string $query Search query
     * @param array $filters Optional filters
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchUsers($query, $filters = array(), $limit = 5) {
        global $system_data;
        
        // Check permission
        $moduleId = $system_data->getModuleId('User');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            return array();
        }
        
        $baseQuery = "SELECT DISTINCT u.id, u.login, u.isActive,
                      CONCAT_WS(' ', c.name, c.surname) as name,
                      c.email, c.phone, c.mobile
                      FROM user u
                      LEFT JOIN contact c ON u.contact = c.id
                      WHERE (u.login LIKE CONCAT('%',?,'%') OR c.name LIKE CONCAT('%',?,'%') OR c.surname LIKE CONCAT('%',?,'%') OR c.email LIKE CONCAT('%',?,'%') OR c.phone LIKE CONCAT('%',?,'%') OR c.mobile LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query)
        );
        
        // Filter out super users if current user is not super user
        if (!$system_data->isUserSuperUser() && count($system_data->getSuperUsers()) > 0) {
            $superUsers = $system_data->getSuperUsers();
            $placeholders = implode(',', array_fill(0, count($superUsers), '?'));
            $baseQuery .= " AND u.id NOT IN ($placeholders)";
            foreach ($superUsers as $su) {
                $params[] = array("i", $su);
            }
        }
        
        $baseQuery .= " ORDER BY name ASC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]);
        
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row['id']),
                'type' => 'user',
                'name' => $row['name'] ?? '',
                'login' => $row['login'] ?? '',
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'isActive' => intval($row['isActive']) === 1
            );
        }
        
        return $formatted;
    }
    
    /**
     * Search contacts
     * @param string $query Search query
     * @param array $filters Optional filters
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchContacts($query, $filters = array(), $limit = 5) {
        global $system_data;
        
        // Check permission
        $moduleId = $system_data->getModuleId('Kontakte');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            return array();
        }
        
        $baseQuery = "SELECT DISTINCT c.id, c.name, c.surname, c.nickname, c.company,
                      c.email, c.phone, c.mobile, c.business,
                      i.name as instrument_name
                      FROM contact c
                      LEFT JOIN instrument i ON c.instrument = i.id
                      WHERE (c.name LIKE CONCAT('%',?,'%') OR c.surname LIKE CONCAT('%',?,'%') OR c.nickname LIKE CONCAT('%',?,'%') OR c.company LIKE CONCAT('%',?,'%') 
                             OR c.email LIKE CONCAT('%',?,'%') OR c.phone LIKE CONCAT('%',?,'%') OR c.mobile LIKE CONCAT('%',?,'%') OR c.business LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query)
        );
        
        $baseQuery .= " ORDER BY c.surname, c.name ASC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]);
        
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row['id']),
                'type' => 'contact',
                'name' => trim(($row['name'] ?? '') . ' ' . ($row['surname'] ?? '')),
                'nickname' => $row['nickname'] ?? null,
                'company' => $row['company'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'mobile' => $row['mobile'] ?? null,
                'instrument' => $row['instrument_name'] ?? null
            );
        }
        
        return $formatted;
    }
    
    /**
     * Search tasks
     * @param string $query Search query
     * @param array $filters Optional filters
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchTasks($query, $filters = array(), $limit = 5) {
        global $system_data;
        
        // Check permission
        $moduleId = $system_data->getModuleId('Aufgaben');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            return array();
        }
        
        $baseQuery = "SELECT DISTINCT t.id, t.title, t.description, t.due_at, t.is_complete,
                      CONCAT_WS(' ', c1.name, c1.surname) as creator,
                      CONCAT_WS(' ', c2.name, c2.surname) as assignee
                      FROM task t
                      LEFT JOIN contact c1 ON t.created_by = c1.id
                      LEFT JOIN contact c2 ON t.assigned_to = c2.id
                      WHERE (t.title LIKE CONCAT('%',?,'%') OR t.description LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $query),
            array("s", $query)
        );
        
        $baseQuery .= " ORDER BY t.due_at ASC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]);
        
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row['id']),
                'type' => 'task',
                'title' => $row['title'] ?? '',
                'description' => $row['description'] ?? null,
                'dueAt' => $row['due_at'] ?? null,
                'isComplete' => intval($row['is_complete']) === 1,
                'creator' => $row['creator'] ?? null,
                'assignee' => $row['assignee'] ?? null
            );
        }
        
        return $formatted;
    }
    
    /**
     * Search repertoire
     * @param string $query Search query
     * @param array $filters Optional filters
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchRepertoire($query, $filters = array(), $limit = 5) {
        global $system_data;
        
        // Check permission
        $moduleId = $system_data->getModuleId('Repertoire');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            return array();
        }
        
        // Decode URL-encoded title/notes for search
        $decodedQuery = urldecode($query);
        
        $baseQuery = "SELECT DISTINCT s.id, s.title, s.composer, s.notes, s.is_active,
                      g.name as genre_name, st.name as status_name
                      FROM song s
                      LEFT JOIN genre g ON s.genre = g.id
                      LEFT JOIN status st ON s.status = st.id
                      WHERE (s.title LIKE CONCAT('%',?,'%') OR s.composer LIKE CONCAT('%',?,'%') OR s.notes LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $decodedQuery),
            array("s", $decodedQuery),
            array("s", $decodedQuery)
        );
        
        $baseQuery .= " ORDER BY s.title ASC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]);
        
        $formatted = array();
        foreach ($results as $row) {
            // Decode URL-encoded fields
            $title = urldecode($row['title'] ?? '');
            $notes = urldecode($row['notes'] ?? '');
            
            $formatted[] = array(
                'id' => intval($row['id']),
                'type' => 'repertoire',
                'title' => $title,
                'composer' => $row['composer'] ?? null,
                'notes' => $notes,
                'genre' => $row['genre_name'] ?? null,
                'status' => $row['status_name'] ?? null,
                'isActive' => intval($row['is_active']) === 1
            );
        }
        
        return $formatted;
    }
    
    /**
     * Search locations
     * @param string $query Search query
     * @param array $filters Optional filters
     * @param int $limit Maximum results to return
     * @return array Search results
     */
    function searchLocations($query, $filters = array(), $limit = 5) {
        global $system_data;
        
        // Check permission
        $moduleId = $system_data->getModuleId('Locations');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            return array();
        }
        
        $baseQuery = "SELECT DISTINCT l.id, l.name, l.notes,
                      a.street, a.city, a.zip, a.state, a.country
                      FROM location l
                      LEFT JOIN address a ON l.address = a.id
                      WHERE (l.name LIKE CONCAT('%',?,'%') OR l.notes LIKE CONCAT('%',?,'%') OR a.street LIKE CONCAT('%',?,'%') OR a.city LIKE CONCAT('%',?,'%') OR a.zip LIKE CONCAT('%',?,'%'))";
        
        $params = array(
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query),
            array("s", $query)
        );
        
        $baseQuery .= " ORDER BY l.name ASC LIMIT ?";
        $params[] = array("i", $limit);
        
        $results = $this->database->getSelection($baseQuery, $params);
        unset($results[0]);
        
        $formatted = array();
        foreach ($results as $row) {
            $formatted[] = array(
                'id' => intval($row['id']),
                'type' => 'location',
                'name' => $row['name'] ?? '',
                'notes' => $row['notes'] ?? null,
                'street' => $row['street'] ?? null,
                'city' => $row['city'] ?? null,
                'zip' => $row['zip'] ?? null,
                'state' => $row['state'] ?? null,
                'country' => $row['country'] ?? null
            );
        }
        
        return $formatted;
    }
    
    /**
     * Get accessible rehearsal IDs for user
     * @param int $userId User ID
     * @return array Array of rehearsal IDs
     */
    private function getAccessibleRehearsalIds($userId) {
        global $system_data;
        
        // Super users see all
        if ($system_data->isUserSuperUser($userId)) {
            $query = "SELECT id FROM rehearsal";
            $results = $this->database->getSelection($query, array());
            unset($results[0]);
            return array_map(function($row) { return intval($row['id']); }, $results);
        }
        
        // Get rehearsals from groups and phases
        require_once $GLOBALS["DIR_DATA_MODULES"] . "startdata.php";
        $startData = new StartData();
        
        $rehearsalIds = array();
        
        // From user's groups
        $query = "SELECT DISTINCT rehearsal 
                  FROM rehearsal_contact rc 
                  JOIN contact c ON rc.contact = c.id 
                  JOIN user u ON u.contact = c.id 
                  WHERE u.id = ?";
        $sel = $this->database->getSelection($query, array(array("i", $userId)));
        foreach ($sel as $row) {
            if (isset($row['rehearsal'])) {
                $rehearsalIds[] = intval($row['rehearsal']);
            }
        }
        
        // From user's phases
        $usersPhases = $startData->adp()->getUsersPhases($userId);
        if (count($usersPhases) > 0) {
            $params = array();
            $whereQ = array();
            foreach ($usersPhases as $p) {
                $whereQ[] = 'rehearsalphase = ?';
                $params[] = array("i", $p);
            }
            $query = 'SELECT DISTINCT rehearsal as id FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ);
            $sel = $this->database->getSelection($query, $params);
            foreach ($sel as $row) {
                if (isset($row['id'])) {
                    $rehearsalIds[] = intval($row['id']);
                }
            }
        }
        
        return array_unique($rehearsalIds);
    }
    
    /**
     * Get accessible concert IDs for user
     * @param int $userId User ID
     * @return array Array of concert IDs
     */
    private function getAccessibleConcertIds($userId) {
        global $system_data;
        
        // Super users see all concerts
        if ($system_data->isUserSuperUser($userId)) {
            $query = "SELECT id FROM concert";
            $results = $this->database->getSelection($query, array());
            unset($results[0]);
            return array_map(function($row) { return intval($row['id']); }, $results);
        }
        
        // Get concerts from phases and contacts
        require_once $GLOBALS["DIR_DATA_MODULES"] . "startdata.php";
        $startData = new StartData();
        
        $concertIds = array();
        
        // From user's phases
        $usersPhases = $startData->adp()->getUsersPhases($userId);
        if (count($usersPhases) > 0) {
            $params = array();
            $whereQ = array();
            foreach ($usersPhases as $p) {
                $whereQ[] = 'rehearsalphase = ?';
                $params[] = array("i", $p);
            }
            $query = 'SELECT DISTINCT concert as id FROM rehearsalphase_concert WHERE ' . join(' OR ', $whereQ);
            $sel = $this->database->getSelection($query, $params);
            foreach ($sel as $row) {
                if (isset($row['id'])) {
                    $concertIds[] = intval($row['id']);
                }
            }
        }
        
        // From user's contact
        $contactId = $startData->adp()->getUserContact($userId);
        if ($contactId) {
            $query = "SELECT DISTINCT concert as id FROM concert_contact WHERE contact = ?";
            $sel = $this->database->getSelection($query, array(array("i", $contactId)));
            foreach ($sel as $row) {
                if (isset($row['id'])) {
                    $concertIds[] = intval($row['id']);
                }
            }
        }
        
        return array_unique($concertIds);
    }
    
    /**
     * Get available years from events (rehearsals and concerts)
     * @return array Array of years (as integers)
     */
    function getAvailableYears() {
        // Get years from rehearsals
        $rehearsalYears = array();
        $query = "SELECT DISTINCT YEAR(begin) as year FROM rehearsal ORDER BY year DESC";
        $rehearsalResults = $this->database->getSelection($query);
        foreach ($rehearsalResults as $row) {
            if (isset($row['year']) && $row['year']) {
                $rehearsalYears[] = intval($row['year']);
            }
        }
        
        // Get years from concerts
        $concertYears = array();
        $query = "SELECT DISTINCT YEAR(begin) as year FROM concert ORDER BY year DESC";
        $concertResults = $this->database->getSelection($query);
        foreach ($concertResults as $row) {
            if (isset($row['year']) && $row['year']) {
                $concertYears[] = intval($row['year']);
            }
        }
        
        // Merge and return unique years, sorted descending
        $allYears = array_unique(array_merge($rehearsalYears, $concertYears));
        rsort($allYears);
        
        return array_values($allYears);
    }
}
