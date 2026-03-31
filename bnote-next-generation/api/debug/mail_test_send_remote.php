<?php
/**
 * Send one test message via NextGenMailer using a real transactional template (MailPreviewRegistry).
 * Remote-capable, but requires an authenticated admin session.
 *
 * Query: to (required), template (optional, default password_reset), locale (optional, default en).
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

require_once __DIR__ . '/../paths.php';
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

global $system_data;
$userId = method_exists($system_data, 'getUserId') ? (int) $system_data->getUserId() : 0;
$isAdmin = $userId > 0
    && (method_exists($system_data, 'isUserSuperUser') && $system_data->isUserSuperUser($userId)
        || method_exists($system_data, 'isUserMemberGroup') && $system_data->isUserMemberGroup(1, $userId));

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(
        [
            'ok' => false,
            'error' => 'forbidden',
            'hint' => 'Admin session required.',
        ],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

$toRaw = isset($_GET['to']) && is_string($_GET['to']) ? trim($_GET['to']) : '';
if ($toRaw === '' || !filter_var($toRaw, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(
        ['ok' => false, 'error' => $toRaw === '' ? 'missing_to' : 'invalid_to', 'hint' => 'Pass ?to=you@example.com'],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

$templateId = isset($_GET['template']) && is_string($_GET['template']) ? trim($_GET['template']) : 'password_reset';
$localeRaw = isset($_GET['locale']) && is_string($_GET['locale']) ? strtolower(trim($_GET['locale'])) : 'en';
$allowedLocales = ['en', 'de', 'es', 'fr'];
if (!in_array($localeRaw, $allowedLocales, true)) {
    http_response_code(400);
    echo json_encode(
        [
            'ok' => false,
            'error' => 'invalid_locale',
            'hint' => 'Use locale=en|de|es|fr',
            'allowed_locales' => $allowedLocales,
        ],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

require_once __DIR__ . '/../mail/MailPreviewRegistry.php';
require_once __DIR__ . '/../mail/MailEnv.php';
require_once __DIR__ . '/../mail/NextGenMailer.php';

if (!MailPreviewRegistry::isValidTemplate($templateId)) {
    http_response_code(400);
    echo json_encode(
        [
            'ok' => false,
            'error' => 'invalid_template',
            'hint' => 'Use a template id from MailPreviewRegistry / mail_debug.php index',
            'templates' => MailPreviewRegistry::templates(),
        ],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

try {
    $msg = MailPreviewRegistry::build($templateId, $localeRaw);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        [
            'ok' => false,
            'error' => 'build_failed',
            'message' => $e->getMessage(),
        ],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

$msg->to = [$toRaw];
$msg->bcc = [];

$ok = NextGenMailer::send($msg);
echo json_encode(
    [
        'ok' => $ok,
        'to' => $toRaw,
        'from' => MailEnv::fromAddress(),
        'template' => $templateId,
        'locale' => $localeRaw,
        'subject' => $msg->subject,
        'template_id' => $msg->templateId,
    ],
    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
