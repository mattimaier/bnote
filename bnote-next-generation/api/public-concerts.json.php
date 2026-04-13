<?php
declare(strict_types=1);

if (!ob_get_level()) {
    ob_start();
}
ini_set('display_errors', '0');

set_exception_handler(static function (Throwable $e): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode([
        'success' => false,
        'error' => 'internal_error',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

register_shutdown_function(static function (): void {
    $last = error_get_last();
    if (!is_array($last)) {
        return;
    }
    $type = (int) ($last['type'] ?? 0);
    if (!in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode([
        'success' => false,
        'error' => 'internal_error',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/nextgen_public_concerts_feed_token.php';
require_once __DIR__ . '/public_concerts_feed_rate_limit.php';
require_once __DIR__ . '/nextgen_public_event_visibility.php';

$requiredConfigFiles = [
    BNOTE_ROOT . '/config/config.xml',
    BNOTE_ROOT . '/config/company.xml',
];
foreach ($requiredConfigFiles as $cfgPath) {
    if (!is_file($cfgPath) || !is_readable($cfgPath)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        echo json_encode([
            'success' => false,
            'error' => 'internal_error',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$oldErrorReporting = error_reporting(E_ALL & ~E_NOTICE);
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', '0');

$projectRoot = BNOTE_ROOT;
chdir($projectRoot);
if (!isset($GLOBALS['dir_prefix'])) {
    $GLOBALS['dir_prefix'] = '';
}

require_once $projectRoot . '/dirs.php';
require_once $projectRoot . '/src/logic/init.php';
require_once __DIR__ . '/bootstrap.php';

error_reporting($oldErrorReporting);
ini_set('display_errors', (string) $oldDisplayErrors);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'OPTIONS') {
    http_response_code(204);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    exit;
}
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

global $system_data;
$db = $system_data->dbcon;
if (!PublicConcertsFeedRateLimit::consume()) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'public_concerts_feed_rate_limited'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$enabledRaw = $system_data->getDynamicConfigParameter('public_gigs_feed_enabled');
$feedEnabled = in_array((string) $enabledRaw, ['1', 'true', 'on', 'yes'], true);
if (!$feedEnabled) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'public_concerts_feed_disabled'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$tokenRaw = isset($_GET['token']) && is_string($_GET['token']) ? trim($_GET['token']) : '';
if (!NextGenPublicConcertsFeedToken::isValidToken($tokenRaw, $db)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'public_concerts_feed_token_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$fromRaw = isset($_GET['from']) && is_string($_GET['from']) ? trim($_GET['from']) : '';
$toRaw = isset($_GET['to']) && is_string($_GET['to']) ? trim($_GET['to']) : '';
$fromStart = '';
$toEnd = '';

if ($fromRaw !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromRaw)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_date_range'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $parts = explode('-', $fromRaw);
    if (count($parts) !== 3 || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_date_range'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $fromStart = $fromRaw . ' 00:00:00';
}

if ($toRaw !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toRaw)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_date_range'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $parts = explode('-', $toRaw);
    if (count($parts) !== 3 || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_date_range'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $toEnd = $toRaw . ' 23:59:59';
}

if ($fromStart !== '' && $toEnd !== '' && strtotime($fromStart) > strtotime($toEnd)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_date_range'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$where = [
    'LOWER(TRIM(COALESCE(c.status, ""))) = "confirmed"',
    'LOWER(TRIM(COALESCE(c.status, ""))) NOT IN ("cancelled", "hidden")',
];
$params = [];
if ($fromStart !== '') {
    $where[] = '(CASE
        WHEN c.`end` IS NULL
             OR TRIM(CONCAT("", c.`end`)) = ""
             OR CONCAT("", c.`end`) = "0000-00-00 00:00:00"
        THEN CONCAT("", c.`begin`)
        ELSE CONCAT("", c.`end`)
    END) >= ?';
    $params[] = ['s', $fromStart];
}
if ($toEnd !== '') {
    $where[] = 'c.`begin` <= ?';
    $params[] = ['s', $toEnd];
}

$sel = $db->getSelection(
    'SELECT c.`id`, c.`title`, c.`begin`, c.`end`, l.`name` AS location_name,
            a.`street` AS location_street, a.`zip` AS location_zip, a.`city` AS location_city,
            a.`state` AS location_state, a.`country` AS location_country,
            COALESCE(v.`is_published`, 1) AS is_published
     FROM `concert` c
     LEFT JOIN `location` l ON c.`location` = l.`id`
     LEFT JOIN `address` a ON l.`address` = a.`id`
     LEFT JOIN `nextgen_public_event_visibility` v ON v.`otype` = "C" AND v.`oid` = c.`id`
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY c.`begin` ASC, c.`id` ASC',
    $params
);
$items = [];
$privateTitle = '';
try {
    $langRaw = trim((string) ($system_data->getDynamicConfigParameter('language') ?? 'en'));
    $lang = strtolower(substr($langRaw, 0, 2));
    if ($lang === 'de') {
        $privateTitle = 'Privater Termin';
    } elseif ($lang === 'es') {
        $privateTitle = 'Evento privado';
    } elseif ($lang === 'fr') {
        $privateTitle = 'Événement privé';
    } else {
        $privateTitle = 'Private event';
    }
} catch (Throwable $e) {
    $privateTitle = 'Private event';
}
if (is_array($sel) && count($sel) >= 2) {
    for ($i = 1; $i < count($sel); $i++) {
        $row = $sel[$i];
        $isPublished = (int) ($row['is_published'] ?? 1) === 1;
        $locationAddress = [
            'street' => $isPublished ? trim((string) ($row['location_street'] ?? '')) : '',
            'zip' => $isPublished ? trim((string) ($row['location_zip'] ?? '')) : '',
            'city' => $isPublished ? trim((string) ($row['location_city'] ?? '')) : '',
            'state' => $isPublished ? trim((string) ($row['location_state'] ?? '')) : '',
            'country' => $isPublished ? trim((string) ($row['location_country'] ?? '')) : '',
        ];
        $items[] = [
            'id' => (int) ($row['id'] ?? 0),
            'title' => $isPublished ? trim((string) ($row['title'] ?? '')) : $privateTitle,
            'begin' => trim((string) ($row['begin'] ?? '')),
            'end' => trim((string) ($row['end'] ?? '')),
            'locationName' => $isPublished ? trim((string) ($row['location_name'] ?? '')) : '',
            'locationAddress' => $locationAddress,
        ];
    }
}
while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode([
    'success' => true,
    'data' => [
        'generatedAt' => gmdate('c'),
        'items' => $items,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
