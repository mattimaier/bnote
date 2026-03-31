<?php
/**
 * Public scheduler endpoint for weekly reminder digest.
 * Authentication is HMAC header based (no session required).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

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
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'missing_config', 'files' => $missingConfigFiles], JSON_UNESCAPED_SLASHES);
    exit;
}

require_once $projectRoot . '/src/logic/init.php';
error_reporting($oldErrorReporting);
ini_set('display_errors', (string) $oldDisplayErrors);

require_once __DIR__ . '/mail/ReminderSchema.php';
require_once __DIR__ . '/mail/ReminderConfig.php';
require_once __DIR__ . '/mail/ReminderEndpointAuth.php';
require_once __DIR__ . '/mail/ReminderDigestService.php';

global $system_data;
$db = $system_data->dbcon;

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_SLASHES);
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
    ReminderEndpointAuth::verify($db, $method, $requestPath, $rawBody);
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode(
        [
            'ok' => false,
            'error' => $e->getMessage(),
        ],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

$json = json_decode($rawBody, true);
if (!is_array($json)) {
    $json = [];
}

$dryRun = !empty($json['dryRun']);
$force = !empty($json['force']);

try {
    $result = ReminderDigestService::runScheduled($system_data, [
        'dryRun' => $dryRun,
        'force' => $force,
        'mode' => 'scheduled',
    ]);
    echo json_encode(['ok' => true, 'result' => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        [
            'ok' => false,
            'error' => 'run_failed',
            'message' => $e->getMessage(),
        ],
        JSON_UNESCAPED_SLASHES
    );
}
