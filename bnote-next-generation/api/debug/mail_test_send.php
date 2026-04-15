<?php
/**
 * Send one test message via NextGenMailer using a real transactional template (MailPreviewRegistry).
 * Loopback only — remove on public hosts.
 *
 * Query: to (required), template (optional, default password_reset), locale (optional, default en).
 */
declare(strict_types=1);

require_once __DIR__ . "/mail_loopback_guard.php";
mail_loopback_guard();

header("Content-Type: application/json; charset=UTF-8");

$toRaw = isset($_GET["to"]) && is_string($_GET["to"]) ? trim($_GET["to"]) : "";
if ($toRaw === "" || !filter_var($toRaw, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(
    ["ok" => false, "error" => $toRaw === "" ? "missing_to" : "invalid_to", "hint" => "Pass ?to=you@example.com"],
    JSON_UNESCAPED_SLASHES,
  );
  exit();
}

$templateId = isset($_GET["template"]) && is_string($_GET["template"]) ? trim($_GET["template"]) : "password_reset";
$localeRaw = isset($_GET["locale"]) && is_string($_GET["locale"]) ? strtolower(trim($_GET["locale"])) : "en";
$allowedLocales = ["en", "de", "es", "fr"];
if (!in_array($localeRaw, $allowedLocales, true)) {
  http_response_code(400);
  echo json_encode(
    [
      "ok" => false,
      "error" => "invalid_locale",
      "hint" => "Use locale=en|de|es|fr",
      "allowed_locales" => $allowedLocales,
    ],
    JSON_UNESCAPED_SLASHES,
  );
  exit();
}

require_once __DIR__ . "/../mail/MailPreviewRegistry.php";
require_once __DIR__ . "/../mail/MailEnv.php";
require_once __DIR__ . "/../mail/NextGenMailer.php";

if (!MailPreviewRegistry::isValidTemplate($templateId)) {
  http_response_code(400);
  echo json_encode(
    [
      "ok" => false,
      "error" => "invalid_template",
      "hint" => "Use a template id from MailPreviewRegistry / mail_debug.php index",
      "templates" => MailPreviewRegistry::templates(),
    ],
    JSON_UNESCAPED_SLASHES,
  );
  exit();
}

try {
  $msg = MailPreviewRegistry::build($templateId, $localeRaw);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(
    [
      "ok" => false,
      "error" => "build_failed",
      "message" => $e->getMessage(),
    ],
    JSON_UNESCAPED_SLASHES,
  );
  exit();
}

$msg->to = [$toRaw];
$msg->bcc = [];

$ok = NextGenMailer::send($msg);
echo json_encode(
  [
    "ok" => $ok,
    "to" => $toRaw,
    "from" => MailEnv::fromAddress(),
    "template" => $templateId,
    "locale" => $localeRaw,
    "subject" => $msg->subject,
    "template_id" => $msg->templateId,
  ],
  JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
);
