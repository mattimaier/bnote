<?php
/**
 * BNote Next Generation - Equipment API Module
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
 * Equipment API module
 * Provides equipment (inventory) management endpoints
 */
require_once BNOTE_ROOT . '/src/data/modules/equipmentdata.php';
require_once __DIR__ . '/../data/NextGenEquipmentData.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class EquipmentModule {
    private $data;

    public function __construct() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Equipment');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Equipment', 403);
        }

        $this->data = new NextGenEquipmentData();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return $this->listEquipment();
            case 'get':
                return $this->getEquipment();
            case 'create':
                return $this->createEquipment();
            case 'update':
                return $this->updateEquipment();
            case 'delete':
                return $this->deleteEquipment();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /**
     * List all equipment
     */
    private function listEquipment() {
        $selection = $this->data->findAllEquipment();
        $result = [];
        if (is_array($selection)) {
            for ($i = 1; $i < count($selection); $i++) {
                $row = $selection[$i];
                $result[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'] ?? '',
                    'make' => $row['make'] ?? '',
                    'model' => $row['model'] ?? '',
                    'quantity' => isset($row['quantity']) ? intval($row['quantity']) : null,
                    'purchase_price' => isset($row['purchase_price']) ? $row['purchase_price'] : null,
                    'current_value' => isset($row['current_value']) ? $row['current_value'] : null,
                    'notes' => $row['notes'] ?? '',
                ];
            }
        }
        return $result;
    }

    /**
     * Get single equipment by ID (with custom fields)
     */
    private function getEquipment() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Equipment ID required', 400);
        }

        $eq = $this->data->findEquipmentById($id);
        if (!$eq || empty($eq)) {
            Response::error('Equipment not found', 404);
        }

        return [
            'id' => intval($eq['id']),
            'name' => $eq['name'] ?? '',
            'make' => $eq['make'] ?? '',
            'model' => $eq['model'] ?? '',
            'quantity' => isset($eq['quantity']) ? intval($eq['quantity']) : null,
            'purchase_price' => isset($eq['purchase_price']) ? $eq['purchase_price'] : null,
            'current_value' => isset($eq['current_value']) ? $eq['current_value'] : null,
            'notes' => $eq['notes'] ?? '',
        ];
    }

    /**
     * Create new equipment
     */
    private function createEquipment() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $values = [
            'name' => $data['name'] ?? '',
            'make' => $data['make'] ?? '',
            'model' => $data['model'] ?? '',
            'notes' => $data['notes'] ?? '',
        ];
        if (array_key_exists('quantity', $data)) {
            $values['quantity'] = $data['quantity'] !== '' && $data['quantity'] !== null ? intval($data['quantity']) : null;
        }
        if (array_key_exists('purchase_price', $data)) {
            $values['purchase_price'] = $data['purchase_price'] ?? '';
        }
        if (array_key_exists('current_value', $data)) {
            $values['current_value'] = $data['current_value'] ?? '';
        }

        try {
            $id = $this->data->create($values);
            return [
                'success' => true,
                'id' => intval($id),
                'message' => 'Equipment created successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update equipment
     */
    private function updateEquipment() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Equipment ID required', 400);
        }

        $values = [];
        foreach (['name', 'make', 'model', 'quantity', 'purchase_price', 'current_value', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'quantity') {
                    $values[$field] = $data[$field] !== '' && $data[$field] !== null ? intval($data[$field]) : null;
                } else {
                    $values[$field] = $data[$field];
                }
            }
        }

        try {
            $this->data->update($id, $values);
            return [
                'success' => true,
                'message' => 'Equipment updated successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete equipment
     */
    private function deleteEquipment() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Equipment ID required', 400);
        }

        try {
            $this->data->delete($id);
            return [
                'success' => true,
                'message' => 'Equipment deleted successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
