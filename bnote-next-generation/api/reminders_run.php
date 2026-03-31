<?php
/**
 * Public scheduler endpoint for digest/escalation jobs.
 * Authentication is HMAC header based (no session required).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$phase = 'bootstrap';
$responseSent = false;

/**
 * Emit a JSON error response exactly once.
 *
 * @param array<string,mixed> $extra
 */
function reminder_fail_json(int $statusCode, string $error, array $extra = []): void
{
    global $responseSent, $phase;
    if ($responseSent) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    $payload = [
        'ok' => false,
        'error' => $error,
        'phase' => $phase,
    ];
    foreach ($extra as $k => $v) {
        $payload[$k] = $v;
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    $responseSent = true;
}

set_exception_handler(function (Throwable $e): void {
    reminder_fail_json(500, 'uncaught_exception', [
        'message' => $e->getMessage(),
    ]);
});

register_shutdown_function(function (): void {
    $last = error_get_last();
    if (!is_array($last)) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    $type = (int) ($last['type'] ?? 0);
    if (!in_array($type, $fatalTypes, true)) {
        return;
    }
    reminder_fail_json(500, 'fatal_error', [
        'message' => (string) ($last['message'] ?? 'unknown fatal error'),
    ]);
});

if (!ob_get_level()) {
    ob_start();
}
ini_set('display_errors', '0');
$oldErrorReporting = error_reporting(E_ALL & ~E_NOTICE);
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', '0');

require_once __DIR__ . '/paths.php';
$projectRoot = BNOTE_ROOT;
chdir($projectRoot);
if (!isset($GLOBALS['dir_prefix'])) {
    $GLOBALS['dir_prefix'] = '';
}
require_once $projectRoot . '/dirs.php';

$requiredConfigFiles = ['config/config.xml', 'config/company.xml'];
$missingConfigFiles = [];
foreach ($requiredConfigFiles as $configFile) {
    $absolutePath = $projectRoot . '/' . $configFile;
    if (!file_exists($configFile) && !file_exists($absolutePath)) {
        $missingConfigFiles[] = $configFile;
    }
}
if (!empty($missingConfigFiles)) {
    reminder_fail_json(500, 'missing_config', ['files' => $missingConfigFiles]);
    exit;
}

$phase = 'init';
require_once $projectRoot . '/src/logic/init.php';
error_reporting($oldErrorReporting);
ini_set('display_errors', (string) $oldDisplayErrors);

$phase = 'api_bootstrap';
require_once __DIR__ . '/bootstrap.php';

$phase = 'load_dependencies';
require_once __DIR__ . '/mail/ReminderSchema.php';
require_once __DIR__ . '/mail/ReminderConfig.php';
require_once __DIR__ . '/mail/ReminderEndpointAuth.php';
require_once __DIR__ . '/mail/ReminderDigestService.php';
require_once __DIR__ . '/mail/EscalationAlertService.php';

global $system_data;
$db = $system_data->dbcon;

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    reminder_fail_json(405, 'method_not_allowed');
    exit;
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody)) {
    $rawBody = '';
}
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/api/reminders_run.php'), PHP_URL_PATH);
if ($requestPath === '') {
    $requestPath = '/api/reminders_run.php';
}

try {
    $phase = 'auth_verify';
    ReminderEndpointAuth::verify($db, $method, $requestPath, $rawBody);
} catch (Throwable $e) {
    $sigHeader = strtolower(trim((string) ($_SERVER['HTTP_X_REMINDER_SIGNATURE'] ?? '')));
    $tsHeader = trim((string) ($_SERVER['HTTP_X_REMINDER_TIMESTAMP'] ?? ''));
    $nonceHeader = trim((string) ($_SERVER['HTTP_X_REMINDER_NONCE'] ?? ''));
    reminder_fail_json(401, (string) $e->getMessage(), [
        'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        'request_path' => $requestPath,
        'timestamp_header' => $tsHeader,
        'nonce_header' => $nonceHeader,
        'signature_header_length' => strlen($sigHeader),
        'signature_header_prefix' => substr($sigHeader, 0, 12),
        'body_sha256' => hash('sha256', $rawBody),
    ]);
    exit;
}

$json = json_decode($rawBody, true);
if (!is_array($json)) {
    $json = [];
}

$dryRun = !empty($json['dryRun']);
$force = !empty($json['force']);
$job = strtolower(trim((string) ($json['job'] ?? 'digest')));
if (!in_array($job, ['digest', 'escalation', 'all'], true)) {
    reminder_fail_json(400, 'invalid_job');
    exit;
}
$onlyUserId = 0;
if (isset($json['onlyUserId'])) {
    $rawOnlyUserId = trim((string) $json['onlyUserId']);
    if ($rawOnlyUserId !== '') {
        if (!preg_match('/^\d+$/', $rawOnlyUserId)) {
            reminder_fail_json(400, 'invalid_only_user_id');
            exit;
        }
        $onlyUserId = (int) $rawOnlyUserId;
        if ($onlyUserId < 1) {
            reminder_fail_json(400, 'invalid_only_user_id');
            exit;
        }
    }
}

$onlyEvent = null;
$rawOnlyEventType = strtoupper(trim((string) ($json['onlyEventType'] ?? '')));
$rawOnlyEventId = trim((string) ($json['onlyEventId'] ?? ''));
if ($rawOnlyEventType !== '' || $rawOnlyEventId !== '') {
    if (!in_array($rawOnlyEventType, ['R', 'C'], true)) {
        reminder_fail_json(400, 'invalid_only_event_type');
        exit;
    }
    if (!preg_match('/^\d+$/', $rawOnlyEventId)) {
        reminder_fail_json(400, 'invalid_only_event_id');
        exit;
    }
    $oid = (int) $rawOnlyEventId;
    if ($oid < 1) {
        reminder_fail_json(400, 'invalid_only_event_id');
        exit;
    }
    if ($job === 'digest') {
        reminder_fail_json(400, 'only_event_requires_escalation_job');
        exit;
    }
    $onlyEvent = [
        'otype' => $rawOnlyEventType,
        'oid' => $oid,
    ];
}

try {
    $phase = 'run_scheduled';
    $result = [];
    if ($job === 'digest' || $job === 'all') {
        $result['digest'] = ReminderDigestService::runScheduled($system_data, [
            'dryRun' => $dryRun,
            'force' => $force,
            'mode' => 'scheduled',
            'onlyUserId' => $onlyUserId,
        ]);
    }
    if ($job === 'escalation' || $job === 'all') {
        $result['escalation'] = EscalationAlertService::runScheduled($system_data, [
            'dryRun' => $dryRun,
            'force' => $force,
            'mode' => 'scheduled',
            'triggerKind' => 'scheduled',
            'isTest' => $dryRun,
            'onlyEvent' => $onlyEvent,
        ]);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['ok' => true, 'result' => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $responseSent = true;
} catch (Throwable $e) {
    reminder_fail_json(500, 'run_failed', [
        'message' => $e->getMessage(),
    ]);
}
