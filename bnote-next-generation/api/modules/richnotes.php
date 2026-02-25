<?php
/**
 * BNote Next Generation - Rich notes API module
 *
 * GET/POST EditorJS JSON for entities. Plain text stays in the main notes columns
 * for the old app; this table stores JSON for the new app only.
 * See docs/RICH_NOTES.md. Table is created on first use.
 *
 * Copyright (C) 2026 BNote Contributors
 */

require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rich_notes_helper.php';

class RichnotesModule {

    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
    }

    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $entityType = $_GET['entity_type'] ?? null;
        $entityId = $_GET['entity_id'] ?? null;

        if ($method === 'POST') {
            $raw = file_get_contents('php://input');
            $body = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (is_array($body)) {
                $entityType = $entityType ?? ($body['entity_type'] ?? null);
                $entityId = $entityId ?? (array_key_exists('entity_id', $body) ? (string) $body['entity_id'] : null);
            }
        }

        $entityType = $entityType ? preg_replace('/[^a-z0-9_]/', '', (string) $entityType) : '';
        $entityId = $entityId !== null && $entityId !== '' ? (string) $entityId : null;

        if ($entityType === '' || $entityId === null) {
            Response::error('entity_type and entity_id required', 400);
        }

        rich_notes_ensure_table();

        if (!$this->canEditEntity($entityType, $entityId)) {
            Response::error('Access denied to this entity', 403);
        }

        if ($method === 'GET') {
            return $this->get($entityType, $entityId);
        }
        if ($method === 'POST') {
            return $this->save($entityType, $entityId);
        }

        Response::error('Method not allowed', 405);
    }

    private function get($entityType, $entityId) {
        global $system_data;
        $row = $system_data->dbcon->fetchRow(
            "SELECT content FROM rich_notes WHERE entity_type = ? AND entity_id = ?",
            [['s', $entityType], ['s', $entityId]]
        );
        if (!$row || !isset($row['content'])) {
            Response::error('Not found', 404);
        }
        return ['content' => $row['content']];
    }

    private function save($entityType, $entityId) {
        global $system_data;
        $raw = file_get_contents('php://input');
        $body = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($body) || !array_key_exists('content', $body)) {
            Response::error('Missing content', 400);
        }
        $content = $body['content'];
        if (!is_string($content)) {
            Response::error('content must be a string', 400);
        }

        $system_data->dbcon->execute(
            "INSERT INTO rich_notes (entity_type, entity_id, content, updated_at) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()",
            [['s', $entityType], ['s', $entityId], ['s', $content]]
        );
        return ['ok' => true];
    }

    /**
     * Check whether the current user can edit this entity (same as edit permission for the entity).
     */
    private function canEditEntity($entityType, $entityId) {
        global $system_data;
        $userId = Auth::getUserId();
        $allowedTypes = [
            'rehearsal', 'concert', 'contact', 'song', 'equipment', 'location', 'program',
            'rehearsal_song', 'news', 'concert_conditions'
        ];
        if (!in_array($entityType, $allowedTypes, true)) {
            return false;
        }

        switch ($entityType) {
            case 'rehearsal':
                return $this->canEditRehearsal($entityId, $userId);
            case 'concert':
                return $this->canEditConcert($entityId, $userId);
            case 'concert_conditions':
                return $this->canEditConcert($entityId, $userId);
            case 'contact':
                return $this->canEditContact($entityId, $userId);
            case 'song':
                return $this->canEditSong($userId);
            case 'equipment':
                return $this->canEditEquipment($userId);
            case 'location':
                return $this->canEditLocation($userId);
            case 'program':
                return $this->canEditProgram($userId);
            case 'rehearsal_song':
                $parts = explode('_', $entityId, 2);
                return count($parts) >= 2 && $this->canEditRehearsal($parts[0], $userId);
            case 'news':
                return $this->canEditNews($userId);
            default:
                return false;
        }
    }

    private function canEditRehearsal($rehearsalId, $userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Proben');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            return false;
        }
        $rid = intval($rehearsalId);
        if ($system_data->isUserSuperUser($userId)) {
            require_once BNOTE_ROOT . '/src/data/modules/probendata.php';
            $data = new ProbenData();
            $r = $data->findByIdNoRef($rid);
            return $r !== null && count($r) > 0;
        }
        require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
        require_once BNOTE_ROOT . '/src/data/modules/probendata.php';
        require_once BNOTE_ROOT . '/src/data/database.php';
        $startData = new StartData();
        $phases = $startData->adp()->getUsersPhases($userId);
        $fromContact = $system_data->dbcon->getSelection(
            "SELECT rehearsal FROM rehearsal_contact rc JOIN user u ON u.contact = rc.contact WHERE u.id = ?",
            [['i', $userId]]
        );
        $rehearsalIds = Database::flattenSelection($fromContact, 'rehearsal');
        if (count($phases) > 0) {
            $params = [];
            $whereQ = [];
            foreach ($phases as $p) {
                $whereQ[] = 'rehearsalphase = ?';
                $params[] = ['i', $p];
            }
            $sel = $system_data->dbcon->getSelection(
                'SELECT rehearsal as rehearsal FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ),
                $params
            );
            $rehearsalIds = array_merge($rehearsalIds, Database::flattenSelection($sel, 'rehearsal'));
        }
        $rehearsalIds = array_map('intval', array_unique($rehearsalIds));
        return in_array($rid, $rehearsalIds);
    }

    private function canEditConcert($concertId, $userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Konzerte');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            return false;
        }
        $cid = intval($concertId);
        if ($system_data->isUserSuperUser($userId)) {
            require_once BNOTE_ROOT . '/src/data/modules/konzertedata.php';
            $data = new KonzerteData();
            $c = $data->findByIdNoRef($cid);
            return $c !== null && count($c) > 0;
        }
        $sel = $system_data->dbcon->getSelection(
            "SELECT concert FROM concert_contact cc JOIN user u ON u.contact = cc.contact WHERE u.id = ?",
            [['i', $userId]]
        );
        $concertIds = Database::flattenSelection($sel, 'concert');
        return in_array($cid, array_map('intval', $concertIds));
    }

    private function canEditContact($contactId, $userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Kontakte');
        return $moduleId && $system_data->userHasPermission($moduleId);
    }

    private function canEditSong($userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Repertoire');
        return $moduleId && $system_data->userHasPermission($moduleId);
    }

    private function canEditEquipment($userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Equipment');
        return $moduleId && $system_data->userHasPermission($moduleId);
    }

    private function canEditLocation($userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Locations');
        return $moduleId && $system_data->userHasPermission($moduleId);
    }

    private function canEditProgram($userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Konzerte');
        return $moduleId && $system_data->userHasPermission($moduleId);
    }

    private function canEditNews($userId) {
        global $system_data;
        $moduleId = $system_data->getModuleId('Nachrichten');
        return $moduleId && $system_data->userHasPermission($moduleId);
    }
}
