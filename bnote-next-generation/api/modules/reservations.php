<?php
/**
 * BNote Next Generation - Reservations API Module
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
 * Reservations API module
 * CRUD for reservations (location bookings). Uses BNote CalendarData - no BNote modifications.
 * v1: base fields only (custom fields deferred).
 */
require_once BNOTE_ROOT . '/src/data/modules/calendardata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class ReservationsModule {
    private $data;
    private $canEditCalendar;

    public function __construct() {
        global $system_data;
        if (!Auth::check()) {
            Response::error('Access denied to Calendar', 403);
        }
        $uid = Auth::getUserId();
        $moduleId = $system_data->getModuleId('Calendar');
        $hasCalendarModule = $moduleId ? $system_data->userHasPermission($moduleId) : false;
        $isAdmin = $uid && ($system_data->isUserSuperUser($uid) || $system_data->isUserMemberGroup(1, $uid));
        $this->canEditCalendar = $isAdmin || $hasCalendarModule;

        $this->data = new CalendarData();
    }

    private function requireEditPermission() {
        if (!$this->canEditCalendar) {
            Response::error('Access denied to Calendar edit', 403);
        }
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return $this->listReservations();
            case 'get':
                return $this->getReservation();
            case 'create':
                return $this->createReservation();
            case 'update':
                return $this->updateReservation();
            case 'delete':
                return $this->deleteReservation();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function getPayload() {
        $data = $GLOBALS['API_REQUEST_BODY'] ?? null;
        if ($data !== null) {
            return $data;
        }
        $rawInput = file_get_contents('php://input');
        $data = $rawInput ? json_decode($rawInput, true) : null;
        return $data ?? $_POST;
    }

    private function listReservations() {
        $colExchange = ['contact' => ['name', 'surname'], 'location' => ['name']];
        $rows = $this->data->findAllJoined($colExchange);
        $result = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $result[] = $this->formatReservation($row);
            }
        }
        return $result;
    }

    private function getReservation() {
        $id = $_GET['id'] ?? $this->getPayload()['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Reservation ID required', 400);
        }

        $row = $this->data->findByIdNoRef(intval($id));
        if (!$row) {
            Response::error('Reservation not found', 404);
        }

        $locationId = isset($row['location']) ? intval($row['location']) : 0;
        $contactId = isset($row['contact']) ? intval($row['contact']) : 0;
        if ($locationId > 0) {
            global $system_data;
            $loc = $system_data->dbcon->fetchRow('SELECT name FROM location WHERE id = ?', [['i', $locationId]]);
            $row['locationname'] = $loc['name'] ?? null;
        }
        if ($contactId > 0) {
            global $system_data;
            $con = $system_data->dbcon->fetchRow('SELECT name, surname FROM contact WHERE id = ?', [['i', $contactId]]);
            $row['contactname'] = trim(($con['name'] ?? '') . ' ' . ($con['surname'] ?? ''));
            $row['contactsurname'] = $con['surname'] ?? '';
        }

        return $this->formatReservation($row);
    }

    private function formatReservation($row) {
        return [
            'id' => intval($row['id'] ?? 0),
            'begin' => $row['begin'] ?? '',
            'end' => $row['end'] ?? '',
            'name' => $row['name'] ?? '',
            'location' => isset($row['location']) ? intval($row['location']) : null,
            'locationname' => $row['locationname'] ?? null,
            'contact' => isset($row['contact']) ? intval($row['contact']) : null,
            'contactname' => trim(($row['contactname'] ?? '') . ' ' . ($row['contactsurname'] ?? '')),
            'notes' => $row['notes'] ?? '',
        ];
    }

    private function createReservation() {
        $this->requireEditPermission();
        $data = $this->getPayload();
        $values = [
            'name' => $data['name'] ?? '',
            'begin' => $data['begin'] ?? '',
            'end' => $data['end'] ?? '',
            'location' => isset($data['location']) ? intval($data['location']) : null,
            'contact' => isset($data['contact']) ? intval($data['contact']) : null,
            'notes' => $data['notes'] ?? '',
        ];

        $fields = $this->data->getFields();
        foreach (array_keys($fields) as $k) {
            if (!array_key_exists($k, $values) && array_key_exists($k, $data)) {
                $values[$k] = $data[$k];
            }
        }

        try {
            $_POST = $values;
            $id = $this->data->create($values);
            return [
                'success' => true,
                'id' => intval($id),
                'message' => 'Reservation created successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function updateReservation() {
        $this->requireEditPermission();
        $data = $this->getPayload();
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Reservation ID required', 400);
        }

        $id = intval($id);
        $existing = $this->data->findByIdNoRef($id);
        if (!$existing) {
            Response::error('Reservation not found', 404);
        }

        $values = [];
        foreach (['name', 'begin', 'end', 'location', 'contact', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $values[$field] = $field === 'location' || $field === 'contact'
                    ? ($data[$field] !== null && $data[$field] !== '' ? intval($data[$field]) : null)
                    : $data[$field];
            }
        }

        if (empty($values)) {
            return ['success' => true, 'message' => 'Nothing to update'];
        }

        try {
            $_POST = array_merge($existing, $values, ['id' => $id]);
            $this->data->update($id, $values);
            return [
                'success' => true,
                'message' => 'Reservation updated successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function deleteReservation() {
        $this->requireEditPermission();
        $data = $this->getPayload();
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Reservation ID required', 400);
        }

        try {
            $this->data->delete(intval($id));
            return [
                'success' => true,
                'message' => 'Reservation deleted successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
