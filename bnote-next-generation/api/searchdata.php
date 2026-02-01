<?php
/**
 * BNote Next Generation - Search Data
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Unified search across rehearsals, concerts, users, contacts, tasks, repertoire, locations.
 * Uses BNote database (AbstractData/database). Loaded by api/modules/search.php after bootstrap.
 */

// AbstractData is already loaded by api/bootstrap.php
class SearchData extends AbstractData {

    public function __construct($dir_prefix = "") {
        $this->fields = array(
            "id" => array("id", FieldType::INTEGER)
        );
        $this->references = array();
        $this->table = "rehearsal"; // dummy for init; we only use $this->database
        $this->init($dir_prefix);
    }

    /**
     * Search rehearsals by notes or location name; optional date filter.
     */
    public function searchRehearsals($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $params = array(array("s", $like), array("s", $like));
        $where = " (r.notes LIKE ? OR l.name LIKE ?) ";
        if (!empty($filters['date_year'])) {
            $where .= " AND YEAR(r.begin) = ? ";
            $params[] = array("i", $filters['date_year']);
        }
        if (!empty($filters['date_month'])) {
            $where .= " AND MONTH(r.begin) = ? ";
            $params[] = array("i", $filters['date_month']);
        }
        $params[] = array("i", $limit);
        $sql = "SELECT r.id, r.begin as begin, r.end, r.approve_until as approve_until, l.name as locationName, r.notes as title
                FROM rehearsal r
                LEFT JOIN location l ON r.location = l.id
                WHERE " . $where . "
                ORDER BY r.begin DESC LIMIT ?";
        $rows = $this->database->getSelection($sql, $params);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Search concerts by title, notes, organizer; optional date filter.
     */
    public function searchConcerts($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $params = array(array("s", $like), array("s", $like), array("s", $like));
        $where = " (c.title LIKE ? OR c.notes LIKE ? OR c.organizer LIKE ?) ";
        if (!empty($filters['date_year'])) {
            $where .= " AND YEAR(c.begin) = ? ";
            $params[] = array("i", $filters['date_year']);
        }
        if (!empty($filters['date_month'])) {
            $where .= " AND MONTH(c.begin) = ? ";
            $params[] = array("i", $filters['date_month']);
        }
        $params[] = array("i", $limit);
        $sql = "SELECT c.id, c.begin as begin, c.end, c.title, c.organizer, l.name as locationName
                FROM concert c
                LEFT JOIN location l ON c.location = l.id
                WHERE " . $where . "
                ORDER BY c.begin DESC LIMIT ?";
        $rows = $this->database->getSelection($sql, $params);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Search users by login or contact name.
     */
    public function searchUsers($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $sql = "SELECT u.id, CONCAT_WS(' ', c.name, c.surname) as name, c.email as email
                FROM user u
                LEFT JOIN contact c ON u.contact = c.id
                WHERE u.isActive = 1 AND (u.login LIKE ? OR c.name LIKE ? OR c.surname LIKE ? OR c.email LIKE ?)
                ORDER BY name LIMIT ?";
        $rows = $this->database->getSelection($sql, array(
            array("s", $like), array("s", $like), array("s", $like), array("s", $like), array("i", $limit)
        ));
        return is_array($rows) ? $rows : array();
    }

    /**
     * Search contacts by name, email, company.
     */
    public function searchContacts($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $sql = "SELECT c.id, CONCAT_WS(' ', c.name, c.surname) as name, c.email, c.phone, c.mobile, c.company
                FROM contact c
                WHERE c.name LIKE ? OR c.surname LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.nickname LIKE ?
                ORDER BY c.surname, c.name LIMIT ?";
        $rows = $this->database->getSelection($sql, array(
            array("s", $like), array("s", $like), array("s", $like), array("s", $like), array("s", $like), array("i", $limit)
        ));
        return is_array($rows) ? $rows : array();
    }

    /**
     * Search tasks by title or description.
     */
    public function searchTasks($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $params = array(array("s", $like), array("s", $like));
        $where = " (t.title LIKE ? OR t.description LIKE ?) ";
        if (!empty($filters['date_year'])) {
            $where .= " AND YEAR(t.due_at) = ? ";
            $params[] = array("i", $filters['date_year']);
        }
        if (!empty($filters['date_month'])) {
            $where .= " AND MONTH(t.due_at) = ? ";
            $params[] = array("i", $filters['date_month']);
        }
        $params[] = array("i", $limit);
        $sql = "SELECT t.id, t.title as title, t.due_at as dueAt, CONCAT_WS(' ', c.name, c.surname) as assignee
                FROM task t
                LEFT JOIN contact c ON t.assigned_to = c.id
                WHERE " . $where . "
                ORDER BY t.due_at DESC LIMIT ?";
        $rows = $this->database->getSelection($sql, $params);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Search repertoire (programs) by name or notes.
     */
    public function searchRepertoire($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $sql = "SELECT id, name as title, notes FROM program WHERE name LIKE ? OR notes LIKE ? ORDER BY name LIMIT ?";
        $rows = $this->database->getSelection($sql, array(array("s", $like), array("s", $like), array("i", $limit)));
        return is_array($rows) ? $rows : array();
    }

    /**
     * Search locations by name or notes.
     */
    public function searchLocations($query, $filters, $limit) {
        $like = "%" . $query . "%";
        $sql = "SELECT l.id, l.name, a.street as street, a.city as city, a.zip
                FROM location l
                LEFT JOIN address a ON l.address = a.id
                WHERE l.name LIKE ? OR l.notes LIKE ?
                ORDER BY l.name LIMIT ?";
        $rows = $this->database->getSelection($sql, array(array("s", $like), array("s", $like), array("i", $limit)));
        return is_array($rows) ? $rows : array();
    }

    /**
     * Distinct years from rehearsal and concert begin dates (for filter dropdown).
     */
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT y FROM (
                SELECT YEAR(begin) as y FROM rehearsal
                UNION
                SELECT YEAR(begin) as y FROM concert
                ) u ORDER BY y DESC";
        $rows = $this->database->getSelection($sql, array());
        if (!is_array($rows)) return array();
        $years = array();
        foreach ($rows as $row) {
            if (!empty($row['y'])) $years[] = (int) $row['y'];
        }
        return $years;
    }
}
