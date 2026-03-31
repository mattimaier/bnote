<?php
/**
 * BNote Next Generation - Comments API Module
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
 * Comments API module
 * List, add, and delete comments for rehearsals (R), concerts (C), and votes (V).
 * Respects discussion_on and DemoMode (skip email when in demo).
 */
require_once BNOTE_ROOT . '/src/data/applicationdataprovider.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/data/modules/probendata.php';
require_once BNOTE_ROOT . '/src/data/modules/konzertedata.php';
require_once BNOTE_ROOT . '/src/data/modules/abstimmungdata.php';
require_once BNOTE_ROOT . '/src/data/database.php';
require_once __DIR__ . '/../mail/CommentDiscussionNotifier.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../text_normalizer.php';

class CommentsModule {
    /** @var ApplicationDataProvider */
    private $adp;
    /** @var StartData */
    private $startData;
    /** @var ProbenData */
    private $probenData;
    /** @var KonzerteData */
    private $konzerteData;
    /** @var AbstimmungData */
    private $abstimmungData;

    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
        $this->startData = new StartData();
        $this->adp = $this->startData->adp();
        $this->probenData = new ProbenData();
        $this->konzerteData = new KonzerteData();
        $this->abstimmungData = new AbstimmungData();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? null;
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($action === 'list') {
            return $this->normalizeResponse($this->listComments(), $action);
        }
        if ($action === 'add') {
            return $this->normalizeResponse($this->addComment(), $action);
        }
        if ($action === 'delete') {
            return $this->normalizeResponse($this->deleteComment(), $action);
        }

        Response::error('Unknown action', 400);
    }

    private function normalizeResponse($payload, $action) {
        $textFields = ['author', 'message', 'reason'];
        $stats = ['count' => 0, 'samples' => []];
        $normalized = TextNormalizer::normalizeFieldsRecursive($payload, $textFields, $stats, true);
        TextNormalizer::logStats('comments', $action, $stats);
        return $normalized;
    }

    private function discussionOn() {
        global $system_data;
        $v = $system_data->getDynamicConfigParameter('discussion_on');
        return $v == 1 || $v === '1' || $v === true;
    }

    /** getDiscussion returns author_id as contact id; resolve to user id for API response */
    private function getUserIdByContactId($system_data, $contactId) {
        if (!$contactId) return 0;
        $rows = $system_data->dbcon->preparedQuery(
            'SELECT id FROM user WHERE contact = ?',
            [['i', $contactId]]
        );
        return ($rows && count($rows) > 0 && isset($rows[0]['id'])) ? intval($rows[0]['id']) : 0;
    }

    /** Get contact email by contact id (getDiscussion returns contact id as author_id). */
    private function getContactEmailById($system_data, $contactId) {
        if (!$contactId) return null;
        $rows = $system_data->dbcon->preparedQuery(
            'SELECT email FROM contact WHERE id = ?',
            [['i', $contactId]]
        );
        if (!$rows || count($rows) === 0 || empty($rows[0]['email'])) return null;
        return $rows[0]['email'];
    }

    private function canAccessObject($otype, $oid) {
        $otype = strtoupper(trim((string) $otype));
        $oid = is_numeric($oid) ? intval($oid) : null;
        if (!in_array($otype, ['R', 'C', 'V'], true) || $oid === null || $oid <= 0) {
            return false;
        }
        $uid = Auth::getUserId();
        global $system_data;

        if ($otype === 'R') {
            $moduleId = $system_data->getModuleId('Proben');
            $hasRehearsalsModule = $moduleId ? $system_data->userHasPermission($moduleId) : false;
            if ($hasRehearsalsModule) {
                $reh = $this->probenData->findByIdNoRef($oid);
                return $reh !== null && count($reh) > 0;
            }
            if ($system_data->isUserSuperUser($uid)) {
                $reh = $this->probenData->findByIdNoRef($oid);
                return $reh !== null && count($reh) > 0;
            }
            $startData = new StartData();
            $usersPhases = $this->adp->getUsersPhases($uid);
            $rehearsalIds = array_merge(
                $this->getRehearsalsForUser($uid),
                $this->getRehearsalsForPhases($usersPhases)
            );
            $rehearsalIds = array_map('intval', array_unique($rehearsalIds));
            return in_array($oid, $rehearsalIds);
        }

        if ($otype === 'C') {
            $moduleId = $system_data->getModuleId('Konzerte');
            $hasConcertsModule = $moduleId ? $system_data->userHasPermission($moduleId) : false;
            if ($hasConcertsModule) {
                $con = $this->konzerteData->findByIdNoRef($oid);
                return $con !== null && count($con) > 0;
            }
            if ($system_data->isUserSuperUser($uid)) {
                $con = $this->konzerteData->findByIdNoRef($oid);
                return $con !== null && count($con) > 0;
            }
            $phases = $this->adp->getUsersPhases($uid);
            $contactId = $this->adp->getUserContact($uid);
            $params = [];
            if (count($phases) > 0) {
                $phaseWhere = [];
                foreach ($phases as $p) {
                    $phaseWhere[] = 'rehearsalphase = ?';
                    $params[] = ['i', $p];
                }
                $phaseQuery = 'SELECT concert FROM rehearsalphase_concert WHERE ' . join(' OR ', $phaseWhere);
            } else {
                $phaseQuery = 'SELECT concert FROM rehearsalphase_concert WHERE 0 = 1';
            }
            $params[] = ['i', $contactId];
            $params[] = ['i', $oid];
            $query = "SELECT 1 as ok FROM concert c
                      JOIN ( $phaseQuery UNION ALL SELECT concert FROM concert_contact WHERE contact = ? ) AS concerts ON c.id = concerts.concert
                      WHERE c.id = ?";
            $row = $system_data->dbcon->fetchRow($query, $params);
            return $row && !empty($row);
        }

        if ($otype === 'V') {
            $active = $this->abstimmungData->getVotesForUser(true, $uid);
            $finished = $this->abstimmungData->getVotesForUser(false, $uid);
            $ids = [];
            if (is_array($active)) {
                for ($i = 1; $i < count($active); $i++) {
                    $ids[] = intval($active[$i]['id'] ?? 0);
                }
            }
            if (is_array($finished)) {
                for ($i = 1; $i < count($finished); $i++) {
                    $ids[] = intval($finished[$i]['id'] ?? 0);
                }
            }
            return in_array($oid, $ids);
        }

        return false;
    }

    private function getRehearsalsForUser($userId) {
        global $system_data;
        $sel = $system_data->dbcon->getSelection(
            "SELECT rehearsal FROM rehearsal_contact rc JOIN contact c ON rc.contact = c.id JOIN user u ON u.contact = c.id WHERE u.id = ?",
            [['i', $userId]]
        );
        return Database::flattenSelection($sel, 'rehearsal');
    }

    private function getRehearsalsForPhases($phases) {
        if (count($phases) === 0) return [];
        global $system_data;
        $params = [];
        $whereQ = [];
        foreach ($phases as $p) {
            $whereQ[] = 'rehearsalphase = ?';
            $params[] = ['i', $p];
        }
        $sel = $system_data->dbcon->getSelection(
            'SELECT rehearsal as id FROM rehearsalphase_rehearsal WHERE ' . join(' OR ', $whereQ),
            $params
        );
        return Database::flattenSelection($sel, 'id');
    }

    private function listComments() {
        if (!$this->discussionOn()) {
            Response::error('Discussion is disabled', 403);
        }
        $otype = $_GET['otype'] ?? null;
        $oid = $_GET['oid'] ?? null;
        if (!in_array($otype, ['R', 'C', 'V'], true) || !is_numeric($oid) || intval($oid) <= 0) {
            Response::error('Invalid otype or oid', 400);
        }
        $oid = intval($oid);
        if (!$this->canAccessObject($otype, $oid)) {
            Response::error('Access denied to this object', 403);
        }

        $rows = $this->adp->getDiscussion($otype, $oid);
        $list = [];
        global $system_data;
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                // getDiscussion returns author_id as contact id (a.id); resolve to user id and email
                $contactId = isset($r['author_id']) ? intval($r['author_id']) : 0;
                $authorUserId = $this->getUserIdByContactId($system_data, $contactId);
                $authorEmail = $this->getContactEmailById($system_data, $contactId);
                $list[] = [
                    'id' => intval($r['id']),
                    'author' => $r['author'] ?? '',
                    'author_id' => $authorUserId,
                    'author_email' => $authorEmail,
                    'message' => isset($r['message']) ? urldecode($r['message']) : '',
                    'created_at' => $r['created_at'] ?? '',
                ];
            }
        }
        // Oldest first (getDiscussion returns DESC, so reverse)
        $list = array_reverse($list);
        return $list;
    }

    private function addComment() {
        if (!$this->discussionOn()) {
            Response::error('Discussion is disabled', 403);
        }
        $raw = file_get_contents('php://input');
        $body = $raw ? json_decode($raw, true) : [];
        if (!is_array($body)) $body = [];
        $otype = $body['otype'] ?? $_POST['otype'] ?? null;
        $oid = $body['oid'] ?? $_POST['oid'] ?? null;
        $message = $body['message'] ?? $_POST['message'] ?? '';

        if (!in_array($otype, ['R', 'C', 'V'], true) || !is_numeric($oid) || intval($oid) <= 0) {
            Response::error('Invalid otype or oid', 400);
        }
        $oid = intval($oid);
        if (!$this->canAccessObject($otype, $oid)) {
            Response::error('Access denied to this object', 403);
        }

        $message = is_string($message) ? trim($message) : '';
        if ($message === '') {
            Response::error('Message is required', 400);
        }
        if (strlen($message) > 2000) {
            Response::error('Message too long', 400);
        }
        try {
            $this->adp->checkMessage($message);
        } catch (BNoteError $e) {
            Response::error('Invalid message content', 400);
        }

        $uid = Auth::getUserId();
        $commentId = $this->adp->addComment($otype, $oid, $message, $uid);
        if (!$commentId) {
            Response::error('Failed to add comment', 500);
        }

        global $system_data;
        CommentDiscussionNotifier::sendSafe($system_data, $this->startData, $otype, $oid, $uid);

        $created = $this->getCommentById($commentId);
        $authorName = '';
        $authorEmail = null;
        $contact = $system_data->getUsersContact($uid);
        if (is_array($contact)) {
            $authorName = trim(($contact['name'] ?? '') . ' ' . ($contact['surname'] ?? ''));
            $authorEmail = !empty($contact['email']) ? $contact['email'] : null;
        }
        return [
            'id' => intval($commentId),
            'author' => $authorName,
            'author_id' => intval($uid),
            'author_email' => $authorEmail,
            'message' => $message,
            'created_at' => $created['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Load a single comment by ID (using dbcon; no changes to BNote codebase).
     */
    private function getCommentById($commentId) {
        global $system_data;
        $rows = $system_data->dbcon->preparedQuery(
            'SELECT id, author, otype, oid, message, created_at FROM comment WHERE id = ?',
            [['i', $commentId]]
        );
        if (!$rows || count($rows) === 0) return null;
        return $rows[0];
    }

    /**
     * Delete a comment (using dbcon; no changes to BNote codebase). Caller must verify author.
     */
    private function deleteCommentRow($commentId, $userId) {
        global $system_data;
        $system_data->dbcon->prepStatement(
            'DELETE FROM comment WHERE id = ? AND author = ?',
            [['i', $commentId], ['i', $userId]]
        );
    }

    private function deleteComment() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id) || intval($id) <= 0) {
            Response::error('Comment ID required', 400);
        }
        $id = intval($id);
        $comment = $this->getCommentById($id);
        if (!$comment) {
            Response::error('Comment not found', 404);
        }
        $uid = Auth::getUserId();
        if (intval($comment['author']) !== intval($uid)) {
            Response::error('You can only delete your own comment', 403);
        }
        $this->deleteCommentRow($id, $uid);
        return ['deleted' => true];
    }
}
