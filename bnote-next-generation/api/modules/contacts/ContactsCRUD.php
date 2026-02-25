<?php
/**
 * BNote Next Generation - Contacts CRUD handler
 *
 * Copyright (C) 2026 BNote Contributors
 */

require_once __DIR__ . '/../../response.php';

class ContactsCRUD {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function listContacts() {
        $groupId = $_GET['group'] ?? null;

        if ($groupId === 'all' || $groupId === null) {
            $contacts = $this->data->getAllContacts();
        } else {
            $contacts = $this->data->getGroupContacts($groupId);
        }

        $result = [];
        for ($i = 1; $i < count($contacts); $i++) {
            $contact = $contacts[$i];
            $result[] = [
                'id' => intval($contact['id']),
                'name' => $contact['name'] ?? '',
                'surname' => $contact['surname'] ?? '',
                'nickname' => $contact['nickname'] ?? '',
                'company' => $contact['company'] ?? '',
                'phone' => $contact['phone'] ?? '',
                'mobile' => $contact['mobile'] ?? '',
                'business' => $contact['business'] ?? '',
                'email' => $contact['email'] ?? '',
                'web' => $contact['web'] ?? '',
                'instrumentname' => $contact['instrumentname'] ?? '',
                'instrument' => intval($contact['instrument'] ?? 0),
                'is_conductor' => intval($contact['is_conductor'] ?? 0) === 1,
                'birthday' => $contact['birthday'] ?? null,
                'status' => $contact['status'] ?? '',
                'street' => $contact['street'] ?? '',
                'city' => $contact['city'] ?? '',
                'zip' => $contact['zip'] ?? '',
                'address' => intval($contact['address'] ?? 0)
            ];
        }

        return $result;
    }

    public function getContact() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            Response::error('Contact ID required', 400);
        }

        $contact = $this->data->getContact($id);
        if (!$contact) {
            Response::error('Contact not found', 404);
        }

        $groups = $this->data->getContactGroupsArray($id);
        $groupIds = [];
        if (is_array($groups)) {
            foreach ($groups as $group) {
                if (is_array($group)) {
                    if (isset($group['id'])) {
                        $groupIds[] = intval($group['id']);
                    } elseif (isset($group['group_id'])) {
                        $groupIds[] = intval($group['group_id']);
                    }
                } elseif (is_numeric($group)) {
                    $groupIds[] = intval($group);
                }
            }
        }

        return [
            'id' => intval($contact['id']),
            'name' => $contact['name'] ?? '',
            'surname' => $contact['surname'] ?? '',
            'nickname' => $contact['nickname'] ?? '',
            'company' => $contact['company'] ?? '',
            'phone' => $contact['phone'] ?? '',
            'mobile' => $contact['mobile'] ?? '',
            'business' => $contact['business'] ?? '',
            'email' => $contact['email'] ?? '',
            'web' => $contact['web'] ?? '',
            'notes' => $contact['notes'] ?? '',
            'instrument' => intval($contact['instrument'] ?? 0),
            'instrumentname' => $contact['instrumentname'] ?? '',
            'is_conductor' => intval($contact['is_conductor'] ?? 0) === 1,
            'birthday' => $contact['birthday'] ?? null,
            'status' => $contact['status'] ?? '',
            'address' => intval($contact['address'] ?? 0),
            'street' => $contact['street'] ?? '',
            'city' => $contact['city'] ?? '',
            'zip' => $contact['zip'] ?? '',
            'groups' => $groupIds,
            'share_address' => intval($contact['share_address'] ?? 0) === 1,
            'share_phones' => intval($contact['share_phones'] ?? 0) === 1,
            'share_birthday' => intval($contact['share_birthday'] ?? 0) === 1,
            'share_email' => intval($contact['share_email'] ?? 0) === 1
        ];
    }

    public function createContact() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        if (empty($data['name']) && empty($data['surname']) && empty($data['nickname'])) {
            Response::error('At least one of name, surname, or nickname is required', 400);
        }

        $values = [];
        foreach (['name', 'surname', 'nickname', 'company', 'phone', 'mobile', 'business', 'email', 'web', 'notes', 'instrument', 'is_conductor', 'birthday', 'status'] as $field) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field];
            }
        }

        if (isset($data['street']) || isset($data['city']) || isset($data['zip'])) {
            $values['street'] = $data['street'] ?? '';
            $values['city'] = $data['city'] ?? '';
            $values['zip'] = $data['zip'] ?? '';
        }

        foreach (['share_address', 'share_phones', 'share_birthday', 'share_email'] as $field) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field] ? 'on' : '';
            }
        }

        $groupIds = $data['groups'] ?? [];

        try {
            $_POST = $values;
            foreach ($groupIds as $gid) {
                $_POST['group_' . $gid] = 'on';
            }

            $contactId = $this->data->create($values);

            return [
                'success' => true,
                'id' => intval($contactId),
                'message' => 'Contact created successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateContact() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            Response::error('Contact ID required', 400);
        }

        $values = [];
        foreach (['name', 'surname', 'nickname', 'company', 'phone', 'mobile', 'business', 'email', 'web', 'notes', 'instrument', 'is_conductor', 'birthday', 'status'] as $field) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field];
            }
        }

        if (isset($data['street']) || isset($data['city']) || isset($data['zip'])) {
            $values['street'] = $data['street'] ?? '';
            $values['city'] = $data['city'] ?? '';
            $values['zip'] = $data['zip'] ?? '';
        }

        foreach (['share_address', 'share_phones', 'share_birthday', 'share_email'] as $field) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field] ? 'on' : '';
            }
        }

        $groupIds = $data['groups'] ?? [];

        try {
            $_POST = $values;
            foreach ($groupIds as $gid) {
                $_POST['group_' . $gid] = 'on';
            }
            $_GET['id'] = $id;

            $this->data->update($id, $values);

            return [
                'success' => true,
                'message' => 'Contact updated successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteContact() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            Response::error('Contact ID required', 400);
        }

        try {
            $this->data->delete($id);

            return [
                'success' => true,
                'message' => 'Contact deleted successfully'
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
