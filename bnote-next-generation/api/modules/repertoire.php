<?php
/**
 * BNote Next Generation - Repertoire (Songs) API Module
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
 * Repertoire API module
 * Provides song/repertoire management endpoints
 */
require_once BNOTE_ROOT . '/src/data/modules/repertoiredata.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class RepertoireModule {
    private $data;

    public function __construct() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Repertoire');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Repertoire', 403);
        }

        $this->data = new RepertoireData();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return $this->listSongs();
            case 'get':
                return $this->getSong();
            case 'create':
                return $this->createSong();
            case 'update':
                return $this->updateSong();
            case 'delete':
                return $this->deleteSong();
            case 'meta':
                return $this->getMeta();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    /**
     * List songs with optional filters
     */
    private function listSongs() {
        $filters = [];
        if (isset($_GET['genre']) && $_GET['genre'] !== '' && $_GET['genre'] !== '-1') {
            $filters['genre'] = intval($_GET['genre']);
        }
        if (isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== '-1') {
            $filters['status'] = intval($_GET['status']);
        }
        if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
            $filters['is_active'] = $_GET['is_active'] === '1' || $_GET['is_active'] === true ? 1 : 0;
        }
        if (isset($_GET['title']) && trim($_GET['title']) !== '') {
            $filters['title'] = trim($_GET['title']);
        }

        $result = $this->data->getFilteredRepertoire($filters, 0, 9999);
        $data = $result['data'] ?? [];
        $list = [];
        if (is_array($data)) {
            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                $list[] = [
                    'id' => intval($row['id']),
                    'title' => $row['title'] ?? '',
                    'composer' => $row['composer'] ?? '',
                    'length' => $row['length'] ?? '',
                    'bpm' => isset($row['bpm']) ? intval($row['bpm']) : null,
                    'music_key' => $row['music_key'] ?? '',
                    'genre' => $row['genre'] ?? '',
                    'status' => $row['status'] ?? '',
                    'is_active' => !empty($row['is_active']),
                ];
            }
        }
        return $list;
    }

    /**
     * Get single song by ID
     */
    private function getSong() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Song ID required', 400);
        }

        $song = $this->data->getSong($id);
        if (!$song || empty($song)) {
            Response::error('Song not found', 404);
        }

        $title = $song['title'] ?? '';
        $notes = $song['notes'] ?? '';
        if (function_exists('urldecode')) {
            $title = urldecode($title);
            $notes = urldecode($notes);
        }

        return [
            'id' => intval($song['id']),
            'title' => $title,
            'length' => $song['length'] ?? '',
            'genre' => isset($song['genre']) ? intval($song['genre']) : null,
            'genrename' => $song['genrename'] ?? '',
            'bpm' => isset($song['bpm']) ? intval($song['bpm']) : null,
            'music_key' => $song['music_key'] ?? '',
            'composer' => $song['composername'] ?? '',
            'composer_id' => isset($song['composer']) ? intval($song['composer']) : null,
            'status' => isset($song['status']) ? intval($song['status']) : null,
            'statusname' => $song['statusname'] ?? '',
            'setting' => $song['setting'] ?? '',
            'notes' => $notes,
            'is_active' => !empty($song['is_active']),
        ];
    }

    /**
     * Create new song
     */
    private function createSong() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $values = [
            'title' => $data['title'] ?? '',
            'length' => $data['length'] ?? '',
            'genre' => isset($data['genre']) && $data['genre'] !== '' ? intval($data['genre']) : 0,
            'bpm' => isset($data['bpm']) && $data['bpm'] !== '' ? intval($data['bpm']) : 0,
            'music_key' => $data['music_key'] ?? '',
            'composer' => $data['composer'] ?? '',
            'status' => isset($data['status']) && $data['status'] !== '' ? intval($data['status']) : 0,
            'setting' => $data['setting'] ?? '',
            'notes' => $data['notes'] ?? '',
            'is_active' => isset($data['is_active']) && $data['is_active'] ? 1 : 0,
        ];

        try {
            $_POST = $values;
            $id = $this->data->create($values);
            return [
                'success' => true,
                'id' => intval($id),
                'message' => 'Song created successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update song
     */
    private function updateSong() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Song ID required', 400);
        }

        $values = [
            'title' => $data['title'] ?? '',
            'length' => $data['length'] ?? '',
            'genre' => isset($data['genre']) && $data['genre'] !== '' ? intval($data['genre']) : 0,
            'bpm' => isset($data['bpm']) && $data['bpm'] !== '' ? intval($data['bpm']) : 0,
            'music_key' => $data['music_key'] ?? '',
            'composer' => $data['composer'] ?? '',
            'status' => isset($data['status']) && $data['status'] !== '' ? intval($data['status']) : 0,
            'setting' => $data['setting'] ?? '',
            'notes' => $data['notes'] ?? '',
            'is_active' => isset($data['is_active']) && $data['is_active'] ? 1 : 0,
        ];

        try {
            $_POST = array_merge($values, ['id' => $id]);
            $this->data->update($id, $values);
            return [
                'success' => true,
                'message' => 'Song updated successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete song
     */
    private function deleteSong() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Song ID required', 400);
        }

        try {
            $this->data->delete($id);
            return [
                'success' => true,
                'message' => 'Song deleted successfully',
            ];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get genres, statuses, composers for pickers
     */
    private function getMeta() {
        $genres = [];
        $genreSel = $this->data->getGenres();
        if (is_array($genreSel)) {
            for ($i = 1; $i < count($genreSel); $i++) {
                $row = $genreSel[$i];
                $genres[] = ['id' => intval($row['id']), 'name' => $row['name'] ?? ''];
            }
        }
        $statuses = [];
        $statusSel = $this->data->getStatuses();
        if (is_array($statusSel)) {
            for ($i = 1; $i < count($statusSel); $i++) {
                $row = $statusSel[$i];
                $statuses[] = ['id' => intval($row['id']), 'name' => $row['name'] ?? ''];
            }
        }
        $composers = [];
        $compSel = $this->data->getComposers();
        if (is_array($compSel)) {
            for ($i = 1; $i < count($compSel); $i++) {
                $row = $compSel[$i];
                $composers[] = ['id' => intval($row['id']), 'name' => $row['name'] ?? ''];
            }
        }
        return [
            'genres' => $genres,
            'statuses' => $statuses,
            'composers' => $composers,
        ];
    }
}
