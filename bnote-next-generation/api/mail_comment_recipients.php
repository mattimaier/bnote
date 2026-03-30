<?php
/**
 * Local JSON: who would receive comment-discussion mail for an entity (same rules as CommentDiscussionNotifier).
 * Loopback only. Optional author_uid = commenting user id (excluded from list like the real send).
 *
 * Example: mail_comment_recipients.php?otype=R&id=641&author_uid=5
 */
declare(strict_types=1);

require_once __DIR__ . '/mail_loopback_guard.php';
mail_loopback_guard();

header('Content-Type: application/json; charset=UTF-8');

if (!ob_get_level()) {
    ob_start();
}
ini_set('display_errors', 0);
$oldErrorReporting = error_reporting(E_ALL & ~E_NOTICE);
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', 0);

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
    echo json_encode(['error' => 'missing_config', 'files' => $missingConfigFiles], JSON_UNESCAPED_SLASHES);

    exit;
}

require_once $projectRoot . '/src/logic/init.php';
error_reporting($oldErrorReporting);
ini_set('display_errors', $oldDisplayErrors);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/mail/CommentDiscussionNotifier.php';

$otype = strtoupper((string) ($_GET['otype'] ?? 'R'));
$oid = (int) ($_GET['id'] ?? $_GET['oid'] ?? 0);
$authorUid = (int) ($_GET['author_uid'] ?? 0);

if (!in_array($otype, ['R', 'C', 'V'], true) || $oid < 1) {
    http_response_code(400);
    echo json_encode(
        [
            'error' => 'invalid_parameters',
            'hint' => 'Use ?otype=R&id=641 — optional &author_uid=<userId> to mirror author exclusion',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

global $system_data;
$startData = new StartData();
$result = CommentDiscussionNotifier::describeRecipients($system_data, $startData, $otype, $oid, $authorUid);
$result['hint'] = 'Rows mirror rehearsal_contact / concert_contact / vote groups. '
    . 'Contacts without a BNote user now qualify if they have a valid email; '
    . 'linked users still need user email_notification enabled.';

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
