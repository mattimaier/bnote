<?php
/**
 * Local diagnostic: shows what PHP sees for Next Gen mail env (via Apache SetEnv / FPM).
 * Loopback only. Remove or block in production.
 */
declare(strict_types=1);

require_once __DIR__ . '/mail_loopback_guard.php';
mail_loopback_guard();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/mail/MailEnv.php';

$host = MailEnv::host();
$from = MailEnv::fromAddress();
$enc = MailEnv::encryption();
$port = MailEnv::port();

$apiBase = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$apiBase = str_replace('\\', '/', $apiBase);
$apiBase = $apiBase === '/' ? '' : rtrim($apiBase, '/');

echo json_encode(
    [
        'sapi' => PHP_SAPI,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
        'mail' => [
            'MAIL_HOST' => $host,
            'MAIL_PORT' => $port,
            'MAIL_ENCRYPTION' => $enc,
            'MAIL_USERNAME' => MailEnv::username(),
            'MAIL_PASSWORD_set' => MailEnv::password() !== '',
            'MAIL_FROM_ADDRESS' => $from,
            'MAIL_FROM_NAME' => MailEnv::fromName(),
            'NEXTGEN_MAIL_BULK_DELAY_MS_effective' => (int) (MailEnv::bulkSendDelayMicroseconds() / 1000),
        ],
        'nextgenPublicBaseUrl' => MailEnv::nextgenPublicBaseUrl(),
        'checks' => [
            'host_set' => $host !== '',
            'from_set' => $from !== '',
            'auth_ready' => MailEnv::username() !== '' && MailEnv::password() !== '',
            'encryption_ok' => in_array($enc, ['tls', 'ssl'], true),
            'port_matches_strato_ssl' => $port === 465 && $enc === 'ssl',
            'port_matches_strato_tls' => $port === 587 && $enc === 'tls',
            'would_pass_NextGenMailPolicy' => $host !== '' && $from !== '',
        ],
        'mailDebug' => [
            'index' => $apiBase . '/mail_debug.php',
            'previewPasswordResetEn' => $apiBase . '/mail_preview.php?template=password_reset&locale=en',
            'previewNewUserAdminEn' => $apiBase . '/mail_preview.php?template=new_user_admin&locale=en',
            'previewLongDemoEn' => $apiBase . '/mail_preview.php?template=long_demo&locale=en',
            'previewCommentDiscussionRehearsalShortEn' => $apiBase . '/mail_preview.php?template=comment_discussion_rehearsal_short&locale=en',
            'previewCommentDiscussionConcertEn' => $apiBase . '/mail_preview.php?template=comment_discussion_concert&locale=en',
        ],
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
