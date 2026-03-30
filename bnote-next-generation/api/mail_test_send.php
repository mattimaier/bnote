<?php
/**
 * Send one test message via NextGenMailer (Apache env). Loopback only — remove on public hosts.
 */
declare(strict_types=1);

require_once __DIR__ . '/mail_loopback_guard.php';
mail_loopback_guard();

header('Content-Type: application/json; charset=UTF-8');

$toRaw = isset($_GET['to']) && is_string($_GET['to']) ? trim($_GET['to']) : '';
if ($toRaw === '' || !filter_var($toRaw, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(
        ['ok' => false, 'error' => $toRaw === '' ? 'missing_to' : 'invalid_to', 'hint' => 'Pass ?to=you@example.com'],
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

require_once __DIR__ . '/mail/MailEnv.php';
require_once __DIR__ . '/mail/NextGenMailMessage.php';
require_once __DIR__ . '/mail/NextGenMailer.php';

$subject = 'BNote Next Gen — SMTP test';
$html = '<p>This is a test message from your local BNote Next Gen mail setup.</p>'
    . '<p>If you received this, outbound SMTP from PHP is working.</p>';
$msg = new NextGenMailMessage(
    [$toRaw],
    [],
    $subject,
    $html,
    strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)),
    'test_smtp'
);

$ok = NextGenMailer::send($msg);
echo json_encode(
    [
        'ok' => $ok,
        'to' => $toRaw,
        'from' => MailEnv::fromAddress(),
    ],
    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
