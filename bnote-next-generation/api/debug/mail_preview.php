<?php
/**
 * Local HTML preview of Next Gen transactional mail (no send). Loopback only.
 */
declare(strict_types=1);

require_once __DIR__ . "/mail_loopback_guard.php";
mail_loopback_guard();

$template = isset($_GET["template"]) && is_string($_GET["template"]) ? trim($_GET["template"]) : "password_reset";
$locale = isset($_GET["locale"]) && is_string($_GET["locale"]) ? trim($_GET["locale"]) : "en";
$theme = isset($_GET["theme"]) && is_string($_GET["theme"]) ? strtolower(trim($_GET["theme"])) : "auto";
if ($locale === "") {
  $locale = "en";
}
if (!in_array($theme, ["auto", "light", "dark"], true)) {
  $theme = "auto";
}

require_once __DIR__ . "/../mail/MailPreviewRegistry.php";
require_once __DIR__ . "/../mail/MailPreviewHtml.php";

if (!MailPreviewRegistry::isValidTemplate($template)) {
  http_response_code(404);
  header("Content-Type: text/plain; charset=UTF-8");
  echo "Unknown template";
  exit();
}

try {
  $msg = MailPreviewRegistry::build($template, $locale);
} catch (Throwable $e) {
  http_response_code(500);
  header("Content-Type: text/plain; charset=UTF-8");
  echo "Preview failed";
  exit();
}

$html = MailPreviewHtml::replaceCidLogoWithDataUri($msg->htmlBody);

if ($theme !== "auto") {
  // Debug-only override to simulate client color-scheme behavior in the browser preview.
  $search = ["@media (prefers-color-scheme:dark){", "@media (prefers-color-scheme: dark){"];
  $replace = $theme === "dark" ? ["@media all{", "@media all{"] : ["@media not all{", "@media not all{"];
  $html = str_replace($search, $replace, $html);

  $forcedScheme = $theme === "dark" ? "dark" : "light";
  $forceStyle = '<style id="debug-theme-force">html,body{color-scheme:' . $forcedScheme . " !important;}</style>";
  if (stripos($html, "</head>") !== false) {
    $html = preg_replace("/<\/head>/i", $forceStyle . "</head>", $html, 1) ?? $html;
  }
}

header("Content-Type: text/html; charset=UTF-8");
echo $html;
