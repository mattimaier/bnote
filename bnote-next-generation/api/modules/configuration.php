<?php
/**
 * BNote Next Generation - Configuration API Module
 */
declare(strict_types=1);

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class ConfigurationModule {
    /** @var array<string,array<string,mixed>> */
    private array $parameterMap = [
        'rehearsal_start' => ['type' => 'time', 'section' => 'calendar', 'caption' => 'Rehearsal start', 'used_in_nextgen' => false],
        'rehearsal_duration' => ['type' => 'integer', 'section' => 'calendar', 'caption' => 'Rehearsal duration', 'used_in_nextgen' => false],
        'default_contact_group' => ['type' => 'reference_group', 'section' => 'defaults', 'caption' => 'Default contact group', 'used_in_nextgen' => true],
        'auto_activation' => ['type' => 'boolean', 'section' => 'defaults', 'caption' => 'Auto activation', 'used_in_nextgen' => false],
        'user_registration' => ['type' => 'boolean', 'section' => 'defaults', 'caption' => 'User registration', 'used_in_nextgen' => true],
        'wrapped_module_enabled' => ['type' => 'boolean', 'section' => 'display', 'caption' => 'Enable wrapped module', 'used_in_nextgen' => true],
        'share_nonadmin_viewmode' => ['type' => 'boolean', 'section' => 'display', 'caption' => 'Share non-admin view mode', 'used_in_nextgen' => false],
        'rehearsal_show_length' => ['type' => 'boolean', 'section' => 'display', 'caption' => 'Rehearsal length visible', 'used_in_nextgen' => false],
        'allow_participation_maybe' => ['type' => 'boolean', 'section' => 'defaults', 'caption' => 'Allow participation maybe', 'used_in_nextgen' => true],
        'allow_zip_download' => ['type' => 'boolean', 'section' => 'system', 'caption' => 'Allow ZIP download', 'used_in_nextgen' => false],
        'appointments_show_max' => ['type' => 'integer', 'section' => 'display', 'caption' => 'Appointments list max', 'used_in_nextgen' => false],
        'rehearsal_show_max' => ['type' => 'integer', 'section' => 'display', 'caption' => 'Rehearsals list max', 'used_in_nextgen' => true],
        'discussion_on' => ['type' => 'boolean', 'section' => 'system', 'caption' => 'Discussion enabled', 'used_in_nextgen' => true],
        'updates_show_max' => ['type' => 'integer', 'section' => 'display', 'caption' => 'Updates list max', 'used_in_nextgen' => false],
        'language' => ['type' => 'char', 'section' => 'defaults', 'caption' => 'Language', 'used_in_nextgen' => false],
        'default_country' => ['type' => 'char', 'section' => 'defaults', 'caption' => 'Default country', 'used_in_nextgen' => true],
        'google_api_key' => ['type' => 'char', 'section' => 'system', 'caption' => 'Google API key', 'used_in_nextgen' => false],
        'trigger_key' => ['type' => 'char', 'section' => 'notifications', 'caption' => 'Trigger key', 'used_in_nextgen' => false],
        'trigger_cycle_days' => ['type' => 'integer', 'section' => 'notifications', 'caption' => 'Trigger cycle (days)', 'used_in_nextgen' => false],
        'trigger_repeat_count' => ['type' => 'integer', 'section' => 'notifications', 'caption' => 'Trigger repeats', 'used_in_nextgen' => false],
        'enable_trigger_service' => ['type' => 'boolean', 'section' => 'notifications', 'caption' => 'Enable trigger service', 'used_in_nextgen' => false],
        'default_conductor' => ['type' => 'reference_conductor', 'section' => 'defaults', 'caption' => 'Default conductor', 'used_in_nextgen' => true],
        'currency' => ['type' => 'char', 'section' => 'defaults', 'caption' => 'Currency', 'used_in_nextgen' => false],
        'concert_show_max' => ['type' => 'integer', 'section' => 'display', 'caption' => 'Concerts list max', 'used_in_nextgen' => true],
        'export_rehearsal_notes' => ['type' => 'boolean', 'section' => 'system', 'caption' => 'Export rehearsal notes', 'used_in_nextgen' => false],
        'export_rehearsalsong_notes' => ['type' => 'boolean', 'section' => 'system', 'caption' => 'Export rehearsal song notes', 'used_in_nextgen' => false],
        'enable_failed_login_log' => ['type' => 'boolean', 'section' => 'system', 'caption' => 'Enable failed login log', 'used_in_nextgen' => true],
        'beta_bug_report_enabled' => ['type' => 'boolean', 'section' => 'system', 'caption' => 'Enable beta bug reporting', 'used_in_nextgen' => true],
        'beta_bug_report_email' => ['type' => 'char', 'section' => 'system', 'caption' => 'Beta bug report recipient email', 'used_in_nextgen' => true],
    ];

    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 403);
        }
        global $system_data;
        if (!$this->hasConfigurationPermission($system_data)) {
            Response::error('Access denied to Configuration', 403);
        }
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'getConfig';
        switch ($action) {
            case 'canAccess':
                return ['canAccess' => true];
            case 'getConfig':
                return $this->getConfig();
            case 'updateConfig':
                return $this->updateConfig();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function getConfig(): array {
        global $system_data;
        $selection = $system_data->dbcon->getSelection(
            "SELECT param, value FROM configuration WHERE is_active = 1 ORDER BY param",
            []
        );
        $rawValues = [];
        if (is_array($selection)) {
            for ($i = 1; $i < count($selection); $i++) {
                $row = $selection[$i];
                $param = (string) ($row['param'] ?? '');
                if ($param === '' || !isset($this->parameterMap[$param])) {
                    continue;
                }
                $rawValues[$param] = (string) ($row['value'] ?? '');
            }
        }

        $values = [];
        $parameters = [];
        foreach ($this->parameterMap as $param => $meta) {
            $raw = isset($rawValues[$param]) ? (string) $rawValues[$param] : '';
            $values[$param] = $this->normalizeOutValue($meta['type'], $raw);
            $parameters[] = [
                'param' => $param,
                'type' => $meta['type'],
                'section' => $meta['section'],
                'caption' => $meta['caption'],
                'used_in_nextgen' => !empty($meta['used_in_nextgen']),
            ];
        }

        return [
            'parameters' => $parameters,
            'values' => $values,
            'options' => [
                'groups' => $this->getGroupOptions($system_data),
                'conductors' => $this->getConductorOptions($system_data),
            ],
        ];
    }

    private function updateConfig(): array {
        global $system_data;
        $payload = $this->readPayload();
        $values = isset($payload['values']) && is_array($payload['values']) ? $payload['values'] : null;
        if (!is_array($values)) {
            Response::error('values object is required', 400);
        }

        foreach ($values as $param => $value) {
            $key = (string) $param;
            if (!isset($this->parameterMap[$key])) {
                continue;
            }
            $meta = $this->parameterMap[$key];
            if (empty($meta['used_in_nextgen'])) {
                // Keep legacy-only parameters readonly in Next-Gen configuration UI.
                continue;
            }
            $normalized = $this->normalizeInputValue((string) $meta['type'], $value);
            // Upsert so newly introduced parameters (without existing row) persist correctly.
            $system_data->dbcon->execute(
                "INSERT INTO configuration (param, value, is_active)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE value = VALUES(value), is_active = 1",
                [['s', $key], ['s', $normalized]]
            );
        }

        return $this->getConfig();
    }

    private function hasConfigurationPermission($system_data): bool {
        $moduleNames = ['Konfiguration', 'Configuration'];
        foreach ($moduleNames as $name) {
            $moduleId = (int) $system_data->getModuleId($name);
            if ($moduleId > 0 && $system_data->userHasPermission($moduleId)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,array<string,mixed>> */
    private function getGroupOptions($system_data): array {
        $sel = $system_data->dbcon->getSelection(
            "SELECT id, name FROM `group` WHERE is_active = 1 ORDER BY name",
            []
        );
        $groups = [];
        if (is_array($sel)) {
            for ($i = 1; $i < count($sel); $i++) {
                $row = $sel[$i];
                $id = (int) ($row['id'] ?? 0);
                $name = trim((string) ($row['name'] ?? ''));
                if ($id > 0 && $name !== '') {
                    $groups[] = ['id' => $id, 'name' => $name];
                }
            }
        }
        return $groups;
    }

    /** @return array<int,array<string,mixed>> */
    private function getConductorOptions($system_data): array {
        $sel = $system_data->dbcon->getSelection(
            "SELECT id, name, surname FROM contact WHERE is_conductor = 1 ORDER BY surname, name",
            []
        );
        $conductors = [];
        if (is_array($sel)) {
            for ($i = 1; $i < count($sel); $i++) {
                $row = $sel[$i];
                $id = (int) ($row['id'] ?? 0);
                if ($id < 1) {
                    continue;
                }
                $label = trim(((string) ($row['name'] ?? '')) . ' ' . ((string) ($row['surname'] ?? '')));
                if ($label === '') {
                    continue;
                }
                $conductors[] = ['id' => $id, 'name' => $label];
            }
        }
        array_unshift($conductors, ['id' => 0, 'name' => '-']);
        return $conductors;
    }

    /** @param mixed $value */
    private function normalizeInputValue(string $type, $value): string {
        if ($type === 'boolean') {
            return ($value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'on') ? '1' : '0';
        }
        if ($type === 'integer' || $type === 'reference_group' || $type === 'reference_conductor') {
            return (string) intval($value);
        }
        if ($type === 'time') {
            $text = trim((string) $value);
            if (!preg_match('/^\d{1,2}:\d{2}$/', $text)) {
                return '19:30';
            }
            [$h, $m] = array_map('intval', explode(':', $text));
            $h = max(0, min(23, $h));
            $m = max(0, min(59, $m));
            return str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        }
        return trim((string) $value);
    }

    /** @return mixed */
    private function normalizeOutValue(string $type, string $raw) {
        if ($type === 'boolean') {
            return $raw === '1';
        }
        if ($type === 'integer' || $type === 'reference_group' || $type === 'reference_conductor') {
            return intval($raw);
        }
        return $raw;
    }

    /** @return array<string,mixed> */
    private function readPayload(): array {
        $rawInput = file_get_contents('php://input');
        $data = json_decode(is_string($rawInput) ? $rawInput : '', true);
        if (is_array($data)) {
            return $data;
        }
        return is_array($_POST) ? $_POST : [];
    }
}
