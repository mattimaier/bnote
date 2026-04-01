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
require_once __DIR__ . '/../mail/ReminderSchema.php';
require_once __DIR__ . '/../mail/EscalationAlertService.php';
require_once __DIR__ . '/../text_normalizer.php';

class RemindersModule {
    private const CALENDAR_TIMEZONE_PARAM = 'calendar_timezone';

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
            case 'getEscalationGroups':
                return $this->getEscalationGroups();
            case 'runEscalationNow':
                return $this->runEscalationNow();
            case 'simulateEscalationDropout':
                return $this->simulateEscalationDropout();
            case 'getEscalationEligibility':
                return $this->getEscalationEligibility();
            case 'getEscalationAudit':
                return $this->getEscalationAudit();
            case 'getCalendarTimezone':
                return $this->getCalendarTimezone();
            case 'updateCalendarTimezone':
                return $this->updateCalendarTimezone();
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
        $ignoreLimits = !empty($payload['ignoreLimits']);
        $onlyUserId = isset($payload['onlyUserId']) ? (int) $payload['onlyUserId'] : 0;
        return ReminderDigestService::runScheduled($system_data, [
            'dryRun' => $dryRun,
            'force' => $force,
            'mode' => 'admin',
            'ignoreLimits' => $ignoreLimits,
            'onlyUserId' => $onlyUserId > 0 ? $onlyUserId : null,
        ]);
    }

    private function getEscalationGroups() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $rows = $system_data->dbcon->getSelection(
            "SELECT id, name FROM `group` WHERE is_active = 1 ORDER BY name",
            []
        );
        $groups = [];
        if (is_array($rows)) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $groups[] = ['id' => (int) ($r['id'] ?? 0), 'name' => (string) ($r['name'] ?? '')];
            }
        }
        $stats = ['count' => 0, 'samples' => []];
        $groups = TextNormalizer::normalizeFieldsRecursive($groups, ['name'], $stats, true);
        TextNormalizer::logStats('reminders', 'getEscalationGroups', $stats);
        return ['groups' => $groups];
    }

    private function runEscalationNow() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $payload = $this->readPayload();
        $dryRun = !empty($payload['dryRun']);
        $force = !empty($payload['force']);
        $isTest = !empty($payload['isTest']) || $dryRun;
        $overrideRecipients = [];
        if (isset($payload['testRecipients']) && is_array($payload['testRecipients'])) {
            foreach ($payload['testRecipients'] as $email) {
                $v = trim((string) $email);
                if ($v !== '') {
                    $overrideRecipients[] = $v;
                }
            }
        }
        if (!$dryRun && count($overrideRecipients) < 1) {
            Response::error('testRecipients required for real send from developer tools', 400);
        }

        $onlyEvent = null;
        if (!empty($payload['eventType']) && !empty($payload['eventId'])) {
            $onlyEvent = [
                'otype' => strtoupper((string) $payload['eventType']),
                'oid' => (int) $payload['eventId'],
            ];
        }

        return EscalationAlertService::runScheduled($system_data, [
            'dryRun' => $dryRun,
            'force' => $force || $dryRun,
            'mode' => 'developer',
            'isTest' => $isTest,
            'overrideRecipients' => $overrideRecipients,
            'onlyEvent' => $onlyEvent,
            'triggerKind' => 'developer_manual',
        ]);
    }

    private function simulateEscalationDropout() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $payload = $this->readPayload();
        $otype = strtoupper((string) ($payload['eventType'] ?? ''));
        $oid = (int) ($payload['eventId'] ?? 0);
        $contactId = (int) ($payload['contactId'] ?? 0);
        if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
            Response::error('eventType and eventId are required', 400);
        }
        $dryRun = !empty($payload['dryRun']);
        $overrideRecipients = [];
        if (isset($payload['testRecipients']) && is_array($payload['testRecipients'])) {
            foreach ($payload['testRecipients'] as $email) {
                $v = trim((string) $email);
                if ($v !== '') {
                    $overrideRecipients[] = $v;
                }
            }
        }
        if (!$dryRun && count($overrideRecipients) < 1) {
            Response::error('testRecipients required for real send from developer tools', 400);
        }
        return EscalationAlertService::triggerImmediateDropout(
            $system_data,
            $otype,
            $oid,
            $contactId,
            'participation_no',
            $dryRun,
            $overrideRecipients
        );
    }

    private function getEscalationEligibility() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $otype = strtoupper((string) ($_GET['eventType'] ?? $_POST['eventType'] ?? ''));
        $oid = (int) ($_GET['eventId'] ?? $_POST['eventId'] ?? 0);
        if (($otype !== 'R' && $otype !== 'C') || $oid < 1) {
            Response::error('eventType and eventId are required', 400);
        }
        return EscalationAlertService::getEligibilityForEvent($system_data, $otype, $oid);
    }

    private function getEscalationAudit() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);
        $limit = (int) ($_GET['limit'] ?? $_POST['limit'] ?? 50);
        if (!ReminderSchema::ensureTables($system_data->dbcon)) {
            Response::error('reminder_schema_unavailable', 500);
        }
        return [
            'entries' => ReminderSchema::listEscalationAudit($system_data->dbcon, $limit),
        ];
    }

    private function getCalendarTimezone() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);

        $stored = $system_data->dbcon->colValue(
            "SELECT value FROM configuration WHERE param = ?",
            "value",
            [['s', self::CALENDAR_TIMEZONE_PARAM]]
        );
        $timezone = $this->normalizeTimezone(is_string($stored) ? $stored : '');
        if ($timezone === '') {
            $timezone = 'Europe/Berlin';
        }

        return [
            'timezone' => $timezone,
        ];
    }

    private function updateCalendarTimezone() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        ReminderAdmin::requireAdmin($system_data);

        $payload = $this->readPayload();
        $timezoneRaw = isset($payload['timezone']) ? (string) $payload['timezone'] : '';
        $timezone = $this->normalizeTimezone($timezoneRaw);
        if ($timezone === '') {
            Response::error('Invalid timezone', 400);
        }

        $existing = $system_data->dbcon->colValue(
            "SELECT value FROM configuration WHERE param = ?",
            "value",
            [['s', self::CALENDAR_TIMEZONE_PARAM]]
        );
        if ($existing !== null && $existing !== false) {
            $system_data->dbcon->execute(
                "UPDATE configuration SET value = ? WHERE param = ?",
                [['s', $timezone], ['s', self::CALENDAR_TIMEZONE_PARAM]]
            );
        } else {
            $system_data->dbcon->prepStatement(
                "INSERT INTO configuration (param, value, is_active) VALUES (?, ?, 1)",
                [['s', self::CALENDAR_TIMEZONE_PARAM], ['s', $timezone]]
            );
        }

        return [
            'success' => true,
            'timezone' => $timezone,
        ];
    }

    private function normalizeTimezone(string $timezone): string {
        $timezone = trim($timezone);
        if ($timezone === '') {
            return '';
        }
        if (strcasecmp($timezone, 'CET') === 0 || strcasecmp($timezone, 'CEST') === 0) {
            return 'Europe/Berlin';
        }
        return in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : '';
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
