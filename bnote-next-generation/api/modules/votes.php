<?php
/**
 * BNote Next Generation - Votes API Module
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
 * Votes API module
 * Provides vote (poll/abstimmung) management and cast-vote flow
 */
require_once BNOTE_ROOT . '/src/data/modules/abstimmungdata.php';
require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class VotesModule {
    private $data;

    public function __construct() {
        global $system_data;
        $moduleId = $system_data->getModuleId('Abstimmung');
        if (!$moduleId || !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied to Votes', 403);
        }

        $this->data = new AbstimmungData();
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return $this->listVotes();
            case 'get':
                return $this->getVote();
            case 'create':
                return $this->createVote();
            case 'update':
                return $this->updateVote();
            case 'delete':
                return $this->deleteVote();
            case 'getOptions':
                return $this->getOptions();
            case 'addOption':
                return $this->addOption();
            case 'removeOption':
                return $this->removeOption();
            case 'finish':
                return $this->finish();
            case 'submit':
                return $this->submit();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function getUserId() {
        return Auth::getUserId();
    }

    /**
     * List votes for current user (active and finished)
     */
    private function listVotes() {
        $activeOnly = isset($_GET['active']) && $_GET['active'] === '1';
        $uid = $this->getUserId();
        $active = $this->data->getVotesForUser(true, $uid);
        $finished = $this->data->getVotesForUser(false, $uid);
        $list = [];
        $append = function ($sel) use (&$list) {
            if (!is_array($sel)) return;
            for ($i = 1; $i < count($sel); $i++) {
                $row = $sel[$i];
                $list[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'] ?? '',
                    'end' => $row['end'] ?? '',
                    'is_date' => !empty($row['is_date']),
                    'is_multi' => !empty($row['is_multi']),
                    'is_finished' => !empty($row['is_finished']),
                ];
            }
        };
        $append($active);
        if (!$activeOnly) {
            $append($finished);
        }
        return $list;
    }

    /**
     * Get single vote with options and (if finished) result
     */
    private function getVote() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Vote ID required', 400);
        }
        $vote = $this->data->findByIdNoRef($id);
        if (!$vote || empty($vote)) {
            Response::error('Vote not found', 404);
        }
        $options = $this->data->getOptions($id);
        $optionsList = [];
        if (is_array($options)) {
            for ($i = 1; $i < count($options); $i++) {
                $row = $options[$i];
                $optionsList[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'] ?? '',
                    'odate' => $row['odate'] ?? null,
                ];
            }
        }
        $result = null;
        if (!empty($vote['is_finished'])) {
            $result = $this->data->getResult($id);
        }
        $uid = $this->getUserId();
        return [
            'id' => intval($vote['id']),
            'name' => $vote['name'] ?? '',
            'end' => $vote['end'] ?? '',
            'is_date' => !empty($vote['is_date']),
            'is_multi' => !empty($vote['is_multi']),
            'is_finished' => !empty($vote['is_finished']),
            'author' => isset($vote['author']) ? intval($vote['author']) : null,
            'is_author' => $this->data->isUserAuthorOfVote($uid, $id),
            'is_active' => $this->data->isVoteActive($id),
            'options' => $optionsList,
            'result' => $result,
        ];
    }

    /**
     * Create vote
     */
    private function createVote() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $_POST['name'] = $data['name'] ?? '';
        $_POST['end'] = $data['end'] ?? '';
        $_POST['is_date'] = isset($data['is_date']) && $data['is_date'] ? 1 : 0;
        $_POST['is_multi'] = isset($data['is_multi']) && $data['is_multi'] ? 1 : 0;
        $groups = $data['groups'] ?? [];
        foreach ($groups as $gid) {
            $_POST['group_' . $gid] = 'on';
        }
        try {
            $id = $this->data->create([]);
            return ['success' => true, 'id' => intval($id), 'message' => 'Vote created'];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update vote
     */
    private function updateVote() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Vote ID required', 400);
        }
        $uid = $this->getUserId();
        if (!$this->data->isUserAuthorOfVote($uid, $id)) {
            Response::error('Only the author can update this vote', 403);
        }
        $values = [
            'name' => $data['name'] ?? '',
            'end' => $data['end'] ?? '',
        ];
        try {
            $this->data->update($id, $values);
            return ['success' => true, 'message' => 'Vote updated'];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete vote
     */
    private function deleteVote() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Vote ID required', 400);
        }
        $uid = $this->getUserId();
        if (!$this->data->isUserAuthorOfVote($uid, $id)) {
            Response::error('Only the author can delete this vote', 403);
        }
        try {
            $this->data->delete($id);
            return ['success' => true, 'message' => 'Vote deleted'];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function getOptions() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Vote ID required', 400);
        }
        $options = $this->data->getOptions($id);
        $list = [];
        if (is_array($options)) {
            for ($i = 1; $i < count($options); $i++) {
                $row = $options[$i];
                $list[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'] ?? '',
                    'odate' => $row['odate'] ?? null,
                ];
            }
        }
        return $list;
    }

    private function addOption() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $vid = $data['vote_id'] ?? $data['voteId'] ?? null;
        if (!$vid || !is_numeric($vid)) {
            Response::error('Vote ID required', 400);
        }
        $uid = $this->getUserId();
        if (!$this->data->isUserAuthorOfVote($uid, $vid)) {
            Response::error('Only the author can add options', 403);
        }
        $_POST['vote_id'] = $vid;
        $_POST['name'] = $data['name'] ?? '';
        $_POST['odate'] = $data['odate'] ?? '';
        try {
            $oid = $this->data->addOption($vid);
            return ['success' => true, 'id' => intval($oid), 'message' => 'Option added'];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function removeOption() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $oid = $data['option_id'] ?? $data['optionId'] ?? null;
        if (!$oid || !is_numeric($oid)) {
            Response::error('Option ID required', 400);
        }
        try {
            $this->data->deleteOption($oid);
            return ['success' => true, 'message' => 'Option removed'];
        } catch (BNoteError $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    private function finish() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            Response::error('Vote ID required', 400);
        }
        $uid = $this->getUserId();
        if (!$this->data->isUserAuthorOfVote($uid, $id)) {
            Response::error('Only the author can finish this vote', 403);
        }
        $this->data->finish($id);
        return ['success' => true, 'message' => 'Vote finished'];
    }

    /**
     * Submit current user's vote (choices)
     */
    private function submit() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }
        $vid = $data['vote_id'] ?? $data['voteId'] ?? null;
        if (!$vid || !is_numeric($vid)) {
            Response::error('Vote ID required', 400);
        }
        if (!$this->data->isVoteActive($vid)) {
            Response::error('Vote is not active', 400);
        }
        $uid = $this->getUserId();
        $vote = $this->data->findByIdNoRef($vid);
        $isMulti = !empty($vote['is_multi']);
        try {
            $startData = new StartData();
            if ($isMulti) {
                $values = $data['choices'] ?? [];
                $startData->saveVote($vid, $values, $uid);
            } else {
                $optionId = $data['uservote'] ?? $data['option_id'] ?? null;
                if ($optionId === null || $optionId === '') {
                    Response::error('Option required for single choice vote', 400);
                }
                $startData->saveVote($vid, ['uservote' => $optionId], $uid);
            }
            return ['success' => true, 'message' => 'Vote submitted'];
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
