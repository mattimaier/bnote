<?php
/**
 * BNote Next Generation - Beta bug report API module.
 */
declare(strict_types=1);

require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../bug_report_rate_limit.php';
require_once __DIR__ . '/../mail/NextGenMailer.php';
require_once __DIR__ . '/../mail/NextGenMailMessage.php';
require_once __DIR__ . '/../mail/MailEnv.php';

class BugreportModule {
    private const MAX_MESSAGE = 6000;
    private const MAX_TITLE = 120;
    private const MAX_STEPS = 3000;
    private const MAX_EXPECTED = 1500;
    private const MAX_ACTUAL = 1500;
    private const MAX_FIRST_SEEN = 80;
    private const MAX_NETWORK_EVENTS = 20;
    private const MAX_LOG_EVENTS = 50;
    private const MAX_EVENT_TEXT = 1000;
    private const MAX_SCREENSHOT_BYTES = 2_097_152; // 2 MiB
    private const MAX_DIAGNOSTICS_BYTES = 262_144; // 256 KiB

    /** @var list<string> */
    private array $sensitiveNeedles = [
        'password',
        'token',
        'authorization',
        'cookie',
        'set-cookie',
        'secret',
        'apikey',
        'api_key',
    ];

    public function __construct() {
        if (!Auth::check()) {
            Response::error('Authentication required', 401);
        }
    }

    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'send';
        switch ($action) {
            case 'send':
                return $this->send();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }

    private function send(): array {
        global $system_data;

        if (!$this->isFeatureEnabled($system_data)) {
            Response::error('bug_report_feature_disabled', 403);
        }

        $recipient = $this->getRecipientEmail($system_data);
        if ($recipient === '') {
            Response::error('bug_report_recipient_not_configured', 400);
        }

        $userId = intval(Auth::getUserId() ?? 0);
        if ($userId < 1) {
            Response::error('Authentication required', 401);
        }
        BugReportRateLimit::checkOr429($userId);

        $mailPreflightError = $this->mailPreflightError();
        if ($mailPreflightError !== '') {
            Response::error('bug_report_mail_config_invalid: ' . $mailPreflightError, 500);
        }

        $payload = $this->readPayload();
        $message = $this->truncate((string) ($payload['message'] ?? ''), self::MAX_MESSAGE);
        $title = $this->truncate((string) ($payload['title'] ?? ''), self::MAX_TITLE);
        $steps = $this->truncate((string) ($payload['stepsToReproduce'] ?? ''), self::MAX_STEPS);
        $expected = $this->truncate((string) ($payload['expectedBehavior'] ?? ''), self::MAX_EXPECTED);
        $actual = $this->truncate((string) ($payload['actualBehavior'] ?? ''), self::MAX_ACTUAL);
        $severity = $this->normalizeSeverity((string) ($payload['severity'] ?? ''));
        $reproducibility = $this->normalizeReproducibility((string) ($payload['reproducibility'] ?? ''));
        $firstSeenVersion = $this->truncate((string) ($payload['firstSeenVersion'] ?? ''), self::MAX_FIRST_SEEN);

        if ($severity === '') {
            $severity = 'medium';
        }
        if ($title === '' && $message !== '') {
            $title = $this->truncate(strtok($message, "\n") ?: $message, self::MAX_TITLE);
        }
        if ($steps === '' && $message !== '') {
            $steps = $message;
        }
        if ($expected === '' && $message !== '') {
            $expected = 'n/a';
        }
        if ($actual === '' && $message !== '') {
            $actual = 'n/a';
        }

        if ($message === '' && ($title === '' || $steps === '' || $expected === '' || $actual === '')) {
            Response::error('bug_report_required_fields_missing', 400);
        }

        $clientContext = $this->sanitizeContext($payload['clientContext'] ?? []);
        $networkEvents = $this->sanitizeEventList($payload['networkEvents'] ?? [], self::MAX_NETWORK_EVENTS);
        $logEvents = $this->sanitizeEventList($payload['logEvents'] ?? [], self::MAX_LOG_EVENTS);

        $diagnosticsPayload = [
            'clientContext' => $clientContext,
            'networkEvents' => $networkEvents,
            'logEvents' => $logEvents,
        ];
        $diagnosticsJson = json_encode($diagnosticsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($diagnosticsJson) || strlen($diagnosticsJson) > self::MAX_DIAGNOSTICS_BYTES) {
            Response::error('bug_report_payload_too_large', 400);
        }

        $reportId = $this->generateReportId();
        $reporter = $this->resolveReporter($system_data, $userId);
        $serverSummary = $this->buildServerSummary($reportId, $userId);

        $screenshot = $this->decodeScreenshot((string) ($payload['screenshotDataUrl'] ?? ''));
        $embeds = [];
        $screenshotHtml = '';
        $screenshotText = 'none';
        $tempScreenshotPath = null;
        if ($screenshot !== null) {
            $tempScreenshotPath = $this->writeTempScreenshot($reportId, $screenshot['ext'], $screenshot['bytes']);
            if ($tempScreenshotPath !== null) {
                $embeds[] = ['path' => $tempScreenshotPath, 'cid' => 'bugreport-screenshot'];
                $screenshotHtml = '<p><strong>Screenshot:</strong></p><p><img src="cid:bugreport-screenshot" alt="Bug report screenshot" style="max-width:100%;height:auto;border:1px solid #ddd;border-radius:8px;" /></p>';
                $screenshotText = 'included';
            }
        }

        $subject = '[BNote Beta Bug] ' . $reportId . ' ' . strtoupper($severity) . ' - ' . $title;
        $htmlBody = $this->buildHtmlBody(
            $reportId,
            $severity,
            $title,
            $message,
            $steps,
            $expected,
            $actual,
            $reproducibility,
            $firstSeenVersion,
            $reporter,
            $clientContext,
            $networkEvents,
            $logEvents,
            $serverSummary,
            $screenshotHtml
        );
        $textBody = $this->buildTextBody(
            $reportId,
            $severity,
            $title,
            $message,
            $steps,
            $expected,
            $actual,
            $reproducibility,
            $firstSeenVersion,
            $reporter,
            $clientContext,
            $networkEvents,
            $logEvents,
            $serverSummary,
            $screenshotText
        );

        $message = new NextGenMailMessage(
            [$recipient],
            [],
            $subject,
            $htmlBody,
            $textBody,
            'beta_bug_report',
            $embeds
        );

        $ok = NextGenMailer::send($message);

        if ($tempScreenshotPath !== null && is_file($tempScreenshotPath)) {
            @unlink($tempScreenshotPath);
        }

        if (!$ok) {
            $detail = trim(NextGenMailer::getLastError());
            if ($detail === '') {
                $detail = 'unknown_mailer_error';
            }
            Response::error('bug_report_send_failed: ' . $detail, 500);
        }

        BugReportRateLimit::commit($userId);

        return [
            'sent' => true,
            'reportId' => $reportId,
        ];
    }

    private function isFeatureEnabled($system_data): bool {
        return strval($system_data->getDynamicConfigParameter('beta_bug_report_enabled')) === '1';
    }

    private function getRecipientEmail($system_data): string {
        $raw = trim((string) $system_data->getDynamicConfigParameter('beta_bug_report_email'));
        if ($raw === '' || !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        return $raw;
    }

    private function mailPreflightError(): string {
        $host = trim((string) MailEnv::host());
        $from = trim((string) MailEnv::fromAddress());
        if ($host === '') {
            return 'MAIL_HOST_missing';
        }
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return 'MAIL_FROM_ADDRESS_invalid';
        }
        return '';
    }

    /** @return array<string,mixed> */
    private function readPayload(): array {
        $rawInput = file_get_contents('php://input');
        $decoded = json_decode(is_string($rawInput) ? $rawInput : '', true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return is_array($_POST) ? $_POST : [];
    }

    private function truncate(string $value, int $maxLen): string {
        $trimmed = trim($value);
        if (function_exists('mb_substr')) {
            return mb_substr($trimmed, 0, $maxLen, 'UTF-8');
        }
        return substr($trimmed, 0, $maxLen);
    }

    private function normalizeSeverity(string $value): string {
        $v = strtolower(trim($value));
        if (in_array($v, ['low', 'medium', 'high', 'critical'], true)) {
            return $v;
        }
        return '';
    }

    private function normalizeReproducibility(string $value): string {
        $v = strtolower(trim($value));
        if (in_array($v, ['always', 'sometimes', 'once'], true)) {
            return $v;
        }
        return '';
    }

    /** @param mixed $context @return array<string,mixed> */
    private function sanitizeContext($context): array {
        if (!is_array($context)) {
            return [];
        }
        /** @var array<string,mixed> $redacted */
        $redacted = $this->redactRecursive($context, '');
        return $this->truncateRecursive($redacted);
    }

    /** @param mixed $raw @return list<array<string,mixed>> */
    private function sanitizeEventList($raw, int $maxItems): array {
        if (!is_array($raw)) {
            return [];
        }
        $items = array_slice($raw, 0, $maxItems);
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            /** @var array<string,mixed> $redacted */
            $redacted = $this->redactRecursive($item, '');
            $out[] = $this->truncateRecursive($redacted);
        }
        return $out;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function redactRecursive($value, string $key) {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $childKey = is_string($k) ? $k : strval($k);
                $out[$childKey] = $this->redactRecursive($v, $childKey);
            }
            return $out;
        }
        if (is_string($value)) {
            if ($this->isSensitive($key) || $this->isSensitive($value)) {
                return '[REDACTED]';
            }
            return $value;
        }
        return $value;
    }

    private function isSensitive(string $value): bool {
        $v = strtolower($value);
        foreach ($this->sensitiveNeedles as $needle) {
            if (strpos($v, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function truncateRecursive($value) {
        if (is_array($value)) {
            $out = [];
            $count = 0;
            foreach ($value as $k => $v) {
                if ($count >= 100) {
                    break;
                }
                $out[$k] = $this->truncateRecursive($v);
                $count++;
            }
            return $out;
        }
        if (is_string($value)) {
            return $this->truncate($value, self::MAX_EVENT_TEXT);
        }
        return $value;
    }

    /**
     * @return null|array{bytes:string,ext:string}
     */
    private function decodeScreenshot(string $dataUrl): ?array {
        $value = trim($dataUrl);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpeg);base64,(.+)$/i', $value, $m)) {
            return null;
        }
        $ext = strtolower((string) $m[1]) === 'jpeg' ? 'jpg' : 'png';
        $encoded = (string) $m[2];
        $bytes = base64_decode($encoded, true);
        if (!is_string($bytes) || $bytes === '') {
            return null;
        }
        if (strlen($bytes) > self::MAX_SCREENSHOT_BYTES) {
            Response::error('bug_report_screenshot_too_large', 400);
        }
        return ['bytes' => $bytes, 'ext' => $ext];
    }

    private function writeTempScreenshot(string $reportId, string $ext, string $bytes): ?string {
        $name = 'bnote_bugreport_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $reportId) . '.' . $ext;
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
        $ok = @file_put_contents($path, $bytes);
        if ($ok === false) {
            return null;
        }
        return $path;
    }

    private function generateReportId(): string {
        $ts = gmdate('Ymd-His');
        $suffix = bin2hex(random_bytes(2));
        return 'BUG-' . $ts . '-' . $suffix;
    }

    /** @return array<string,mixed> */
    private function resolveReporter($system_data, int $userId): array {
        $user = Auth::getUserInfo();
        $isAdmin = $system_data->isUserSuperUser($userId) || $system_data->isUserMemberGroup(1, $userId);
        return [
            'userId' => $userId,
            'name' => trim((string) ($user['name'] ?? '')),
            'surname' => trim((string) ($user['surname'] ?? '')),
            'email' => trim((string) ($user['email'] ?? '')),
            'isAdmin' => $isAdmin,
        ];
    }

    /** @return array<string,string> */
    private function buildServerSummary(string $reportId, int $userId): array {
        $envLabel = trim((string) (getenv('BNOTE_NEXT_GENERATION_ENV_LABEL') ?: getenv('APP_ENV') ?: ''));
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? php_uname('n')));
        return [
            'reportId' => $reportId,
            'serverTimeUtc' => gmdate('c'),
            'serverTimezone' => date_default_timezone_get(),
            'apiModule' => 'bugreport',
            'apiAction' => 'send',
            'authenticatedUserId' => strval($userId),
            'host' => $host,
            'environment' => $envLabel !== '' ? $envLabel : 'unknown',
        ];
    }

    /**
     * @param array<string,mixed> $reporter
     * @param array<string,mixed> $clientContext
     * @param list<array<string,mixed>> $networkEvents
     * @param list<array<string,mixed>> $logEvents
     * @param array<string,string> $serverSummary
     */
    private function buildHtmlBody(
        string $reportId,
        string $severity,
        string $title,
        string $message,
        string $steps,
        string $expected,
        string $actual,
        string $reproducibility,
        string $firstSeenVersion,
        $reporter,
        $clientContext,
        $networkEvents,
        $logEvents,
        $serverSummary,
        string $screenshotHtml
    ): string {
        if (!is_array($reporter)) {
            $reporter = [];
        }
        if (!is_array($clientContext)) {
            $clientContext = [];
        }
        if (!is_array($networkEvents)) {
            $networkEvents = [];
        }
        if (!is_array($logEvents)) {
            $logEvents = [];
        }
        if (!is_array($serverSummary)) {
            $serverSummary = [];
        }
        $repName = trim((string) (($reporter['name'] ?? '') . ' ' . ($reporter['surname'] ?? '')));
        if ($repName === '') {
            $repName = 'Unknown';
        }

        $clientJson = htmlspecialchars(json_encode($clientContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $networkJson = htmlspecialchars(json_encode($networkEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logsJson = htmlspecialchars(json_encode($logEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $serverJson = htmlspecialchars(json_encode($serverSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $reproLine = $reproducibility !== '' && $reproducibility !== 'n/a'
            ? '<p><strong>Reproducibility:</strong> ' . htmlspecialchars($reproducibility, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            : '';
        $firstSeenLine = $firstSeenVersion !== '' && $firstSeenVersion !== 'n/a'
            ? '<p><strong>First seen version:</strong> ' . htmlspecialchars($firstSeenVersion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            : '';
        $expectedBlock = $expected !== '' && $expected !== 'n/a'
            ? '<p><strong>Expected behavior</strong><br>' . nl2br(htmlspecialchars($expected, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>'
            : '';
        $actualBlock = $actual !== '' && $actual !== 'n/a'
            ? '<p><strong>Actual behavior</strong><br>' . nl2br(htmlspecialchars($actual, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>'
            : '';

        return '<!doctype html><html><body style="font-family:Arial,sans-serif;line-height:1.45;color:#1f2937;">'
            . '<h2 style="margin:0 0 8px;">Beta Bug Report</h2>'
            . '<p style="margin:0 0 16px;"><strong>Report ID:</strong> ' . htmlspecialchars($reportId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<p><strong>Severity:</strong> ' . htmlspecialchars(strtoupper($severity), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<p><strong>Title:</strong> ' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<p><strong>Message</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>'
            . '<h3>Reporter</h3>'
            . '<ul>'
            . '<li>Name: ' . htmlspecialchars($repName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
            . '<li>User ID: ' . htmlspecialchars(strval($reporter['userId'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
            . '<li>Email: ' . htmlspecialchars((string) ($reporter['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
            . '<li>Is admin: ' . (!empty($reporter['isAdmin']) ? 'yes' : 'no') . '</li>'
            . '</ul>'
            . '<h3>Reproduction</h3>'
            . '<p><strong>Steps to reproduce</strong><br>' . nl2br(htmlspecialchars($steps, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>'
            . $expectedBlock
            . $actualBlock
            . $reproLine
            . $firstSeenLine
            . $screenshotHtml
            . '<h3>Client context (redacted)</h3><pre style="background:#f3f4f6;padding:10px;border-radius:8px;white-space:pre-wrap;">' . $clientJson . '</pre>'
            . '<h3>Network trace (redacted)</h3><pre style="background:#f3f4f6;padding:10px;border-radius:8px;white-space:pre-wrap;">' . $networkJson . '</pre>'
            . '<h3>Log trace (redacted)</h3><pre style="background:#f3f4f6;padding:10px;border-radius:8px;white-space:pre-wrap;">' . $logsJson . '</pre>'
            . '<h3>Server summary</h3><pre style="background:#f3f4f6;padding:10px;border-radius:8px;white-space:pre-wrap;">' . $serverJson . '</pre>'
            . '</body></html>';
    }

    /**
     * @param array<string,mixed> $reporter
     * @param array<string,mixed> $clientContext
     * @param list<array<string,mixed>> $networkEvents
     * @param list<array<string,mixed>> $logEvents
     * @param array<string,string> $serverSummary
     */
    private function buildTextBody(
        string $reportId,
        string $severity,
        string $title,
        string $message,
        string $steps,
        string $expected,
        string $actual,
        string $reproducibility,
        string $firstSeenVersion,
        $reporter,
        $clientContext,
        $networkEvents,
        $logEvents,
        $serverSummary,
        string $screenshotText
    ): string {
        if (!is_array($reporter)) {
            $reporter = [];
        }
        if (!is_array($clientContext)) {
            $clientContext = [];
        }
        if (!is_array($networkEvents)) {
            $networkEvents = [];
        }
        if (!is_array($logEvents)) {
            $logEvents = [];
        }
        if (!is_array($serverSummary)) {
            $serverSummary = [];
        }
        $repName = trim((string) (($reporter['name'] ?? '') . ' ' . ($reporter['surname'] ?? '')));
        if ($repName === '') {
            $repName = 'Unknown';
        }
        $expectedBlock = ($expected !== '' && $expected !== 'n/a')
            ? "Expected behavior:\n{$expected}\n\n"
            : '';
        $actualBlock = ($actual !== '' && $actual !== 'n/a')
            ? "Actual behavior:\n{$actual}\n\n"
            : '';
        $reproLine = ($reproducibility !== '' && $reproducibility !== 'n/a')
            ? "Reproducibility: {$reproducibility}\n"
            : '';
        $firstSeenLine = ($firstSeenVersion !== '' && $firstSeenVersion !== 'n/a')
            ? "First seen version: {$firstSeenVersion}\n"
            : '';
        return "Beta Bug Report\n"
            . "Report ID: {$reportId}\n"
            . "Severity: " . strtoupper($severity) . "\n"
            . "Title: {$title}\n\n"
            . "Message:\n{$message}\n\n"
            . "Reporter:\n"
            . "- Name: {$repName}\n"
            . "- User ID: " . strval($reporter['userId'] ?? '') . "\n"
            . "- Email: " . strval($reporter['email'] ?? '') . "\n"
            . "- Is admin: " . (!empty($reporter['isAdmin']) ? 'yes' : 'no') . "\n\n"
            . "Steps to reproduce:\n{$steps}\n\n"
            . $expectedBlock
            . $actualBlock
            . $reproLine
            . $firstSeenLine
            . "Screenshot: {$screenshotText}\n\n"
            . "Client context (redacted):\n" . (json_encode($clientContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "{}") . "\n\n"
            . "Network trace (redacted):\n" . (json_encode($networkEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "[]") . "\n\n"
            . "Log trace (redacted):\n" . (json_encode($logEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "[]") . "\n\n"
            . "Server summary:\n" . (json_encode($serverSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: "{}") . "\n";
    }
}
