<?php
/**
 * Local HTML preview of Next Gen transactional mail (no send). Loopback only.
 */
declare(strict_types=1);

require_once __DIR__ . '/mail_loopback_guard.php';
mail_loopback_guard();

$template = isset($_GET['template']) && is_string($_GET['template']) ? trim($_GET['template']) : 'password_reset';
$locale = isset($_GET['locale']) && is_string($_GET['locale']) ? trim($_GET['locale']) : 'en';
if ($locale === '') {
    $locale = 'en';
}

require_once __DIR__ . '/../mail/MailPreviewRegistry.php';
require_once __DIR__ . '/../mail/MailPreviewHtml.php';

if (!MailPreviewRegistry::isValidTemplate($template)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Unknown template';
    exit;
}

try {
    $msg = MailPreviewRegistry::build($template, $locale);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Preview failed';
    exit;
}

$html = MailPreviewHtml::replaceCidLogoWithDataUri($msg->htmlBody);
header('Content-Type: text/html; charset=UTF-8');
echo $html;
