<?php
/**
 * BNote Next Generation - Appointments API Module
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
 * Appointments API module
 * CRUD for appointments (general calendar entries) with group invitations.
 * Uses BNote AppointmentData - no BNote modifications.
 */
require_once BNOTE_ROOT . '/src/data/modules/appointmentdata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class AppointmentsModule {
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

        $this->data = new AppointmentData();
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
                return $this->listAppointments();
            case 'get':
                return $this->getAppointment();
            case 'create':
                return $this->createAppointment();
            case 'update':
                return $this->updateAppointment();
            case 'delete':
                return $this->deleteAppointment();
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

    private function listAppointments() {
        $rows = $this->data->findAllNoRef();
        $result = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $this->enrichAppointmentRow($row);
                $result[] = $this->formatAppointment($row);
            }
        }
        foreach ($result as &$apt) {
            $apt['groups'] = $this->getGroupsForAppointment($apt['id']);
        }
        return $result;
    }

    private function enrichAppointmentRow(&$row) {
        $locationId = isset($row['location']) ? intval($row['location']) : 0;
        $contactId = isset($row['contact']) ? intval($row['contact']) : 0;
        if ($locationId > 0) {
            global $system_data;
            $loc = $system_data->dbcon->fetchRow('SELECT name FROM location WHERE id = ?', [['i', $locationId]]);
            $row['locationname'] = $loc['name'] ?? null;
        }
        if ($contactId > 0) {
            global $system_data;
            $con = $system_data->dbcon->fetchRow('SELECT name, surname, email FROM contact WHERE id = ?', [['i', $contactId]]);
            $row['contactname'] = $con['name'] ?? '';
            $row['contactsurname'] = $con['surname'] ?? '';
            $row['contactemail'] = $con['email'] ?? '';
        }
    }

    private function getAppointment() {
        $id = $_GET['id'] ?? $this->getPayload()['id'] ?? null;
        if ($id === null || $id === '' || !is_numeric($id)) {
            Response::error('Appointment ID required', 400);
        }

        $row = $this->data->findByIdNoRef(intval($id));
        if (!$row) {
            Response::error('Appointment not found', 404);
        }

        $this->enrichAppointmentRow($row);
        $out = $this->formatAppointment($row);
        $out['groups'] = $this->getGroupsForAppointment(intval($id));
        return $out;
    }

    private function formatAppointment($row) {
        return [
            'id' => intval($row['id'] ?? 0),
            'begin' => $row['begin'] ?? '',
            'end' => $row['end'] ?? '',
            'name' => $row['name'] ?? '',
            'location' => isset($row['location']) ? intval($row['location']) : null,
            'locationname' => $row['locationname'] ?? null,
            'contact' => isset($row['contact']) ? intval($row['contact']) : null,
            'contactname' => trim(($row['contactname'] ?? '') . ' ' . ($row['contactsurname'] ?? '')),
            'contactFirstName' => $row['contactname'] ?? '',
            'contactSurname' => $row['contactsurname'] ?? '',
            'contactEmail' => $row['contactemail'] ?? '',
            'notes' => $row['notes'] ?? '',
            'groups' => [],
        ];
    }

    private function getGroupsForAppointment($id) {
        global $system_data;
        $rows = $system_data->dbcon->getSelection(
            "SELECT `group` FROM appointment_group WHERE appointment = ?",
            [['i', intval($id)]]
        );
        $ids = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $ids[] = intval($rows[$i]['group'] ?? 0);
            }
        }
        return $ids;
    }

    private function preparePostForGroups($groupIds) {
        $post = $_POST;
        if (!is_array($groupIds)) {
            return $post;
        }
        foreach ($groupIds as $gid) {
            $post['group_' . intval($gid)] = 'on';
        }
        return $post;
    }

    private function createAppointment() {
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

        $groupIds = $data['groups'] ?? [];
        if (!is_array($groupIds)) {
            $groupIds = [];
        }

        try {
            $_POST = $this->preparePostForGroups($groupIds);
            $_POST = array_merge($_POST, $values);
            $id = $this->data->create($values);
            $id = intval($id);
            $row = $this->data->findByIdNoRef($id);
            if (!$row) {
                Response::error('Appointment created but could not be retrieved', 500);
            }
            $this->enrichAppointmentRow($row);
            $item = $this->formatAppointment($row);
            $item['groups'] = $this->getGroupsForAppointment($id);
            return [
                'success' => true,
                'id' => $id,
                'message' => 'Appointment created successfully',
                'item' => $item,
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function updateAppointment() {
        $this->requireEditPermission();
        $data = $this->getPayload();
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if ($id === null || $id === '' || !is_numeric($id) || intval($id) < 1) {
            Response::error('Appointment ID required', 400);
        }

        $id = intval($id);
        $row = $this->data->findByIdNoRef($id);
        if (!$row) {
            Response::error('Appointment not found', 404);
        }
        $this->enrichAppointmentRow($row);
        $existing = [
            'name' => $row['name'] ?? '',
            'begin' => $row['begin'] ?? '',
            'end' => $row['end'] ?? '',
            'location' => isset($row['location']) ? intval($row['location']) : null,
            'contact' => isset($row['contact']) ? intval($row['contact']) : null,
            'notes' => $row['notes'] ?? '',
        ];

        $values = [];
        foreach (['name', 'begin', 'end', 'location', 'contact', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $values[$field] = $field === 'location' || $field === 'contact'
                    ? ($data[$field] !== null && $data[$field] !== '' ? intval($data[$field]) : null)
                    : $data[$field];
            }
        }

        $groupIds = array_key_exists('groups', $data) ? $data['groups'] : null;
        if ($groupIds !== null && !is_array($groupIds)) {
            $groupIds = [];
        }

        try {
            $_POST = array_merge($existing, $values, ['id' => $id]);
            if ($groupIds !== null) {
                $_POST = $this->preparePostForGroups($groupIds);
                $_POST = array_merge($_POST, $values, ['id' => $id]);
            }
            $this->data->update($id, !empty($values) ? $values : $existing);
            return [
                'success' => true,
                'message' => 'Appointment updated successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function deleteAppointment() {
        $this->requireEditPermission();
        $data = $this->getPayload();
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if ($id === null || $id === '' || !is_numeric($id) || intval($id) < 1) {
            Response::error('Appointment ID required', 400);
        }

        try {
            $this->data->delete(intval($id));
            return [
                'success' => true,
                'message' => 'Appointment deleted successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
