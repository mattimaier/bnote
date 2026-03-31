<?php
/**
 * Reminder settings and admin trigger API module.
 */
declare(strict_types=1);

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../mail/ReminderAdmin.php';
require_once __DIR__ . '/../mail/ReminderConfig.php';
require_once __DIR__ . '/../mail/ReminderDigestService.php';

class RemindersModule {
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'getConfig';
        switch ($action) {
            case 'getConfig':
                return $this->getConfig();
            case 'getRecipients':
                return $this->getRecipients();
            case 'updateConfig':
                return $this->updateConfig();
            case 'runNow':
                return $this->runNow();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function getConfig() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        $isAdmin = ReminderAdmin::isAdmin($system_data);
        if (!$isAdmin) {
            return [
                'isAdmin' => false,
                'config' => null,
            ];
        }
        $cfg = ReminderConfig::get($system_data->dbcon);
        return [
            'isAdmin' => true,
            'config' => $cfg,
        ];
    }

    private function updateConfig() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $payload = $this->readPayload();
        $next = ReminderConfig::update($system_data->dbcon, $payload);
        return [
            'success' => true,
            'config' => $next,
        ];
    }

    private function getRecipients() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        return [
            'recipients' => ReminderDigestService::listRecipientsForAdmin($system_data),
        ];
    }

    private function runNow() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $payload = $this->readPayload();
        $dryRun = !empty($payload['dryRun']);
        $force = !empty($payload['force']);
        $onlyUserId = isset($payload['onlyUserId']) ? (int) $payload['onlyUserId'] : 0;
        return ReminderDigestService::runScheduled($system_data, [
            'dryRun' => $dryRun,
            'force' => $force,
            'mode' => 'admin',
            'ignoreLimits' => true,
            'onlyUserId' => $onlyUserId > 0 ? $onlyUserId : null,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function readPayload(): array {
        $rawInput = file_get_contents('php://input');
        $data = json_decode(is_string($rawInput) ? $rawInput : '', true);
        if (is_array($data)) {
            return $data;
        }
        return is_array($_POST) ? $_POST : [];
    }
}
