<?php
/**
 * Remote-safe diagnostics for Next Gen mail configuration and SMTP reachability.
 * Does not expose secrets and is intended for developer-enabled deployments.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../mail/MailEnv.php';

if (!defined('BNOTE_ROOT')) {
    $paths = dirname(__DIR__) . '/paths.php';
    if (is_file($paths)) {
        require_once $paths;
    }
}

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

/**
 * Mask mailbox usernames in diagnostics (keep domain visible).
 */
function mask_mail_identity(string $value): string
{
    $v = trim($value);
    if ($v === '') {
        return '';
    }
    if (strpos($v, '@') === false) {
        if (strlen($v) <= 2) {
            return str_repeat('*', strlen($v));
        }
        return substr($v, 0, 2) . str_repeat('*', max(1, strlen($v) - 2));
    }

    [$local, $domain] = explode('@', $v, 2);
    if ($local === '') {
        return '*@' . $domain;
    }
    if (strlen($local) <= 2) {
        $maskedLocal = str_repeat('*', strlen($local));
    } else {
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));
    }
    return $maskedLocal . '@' . $domain;
}

/**
 * Best-effort TCP connect probe.
 *
 * @return array<string,mixed>
 */
function probe_smtp_tcp(string $host, int $port, float $timeoutSeconds = 5.0): array
{
    if ($host === '' || $port < 1) {
        return [
            'ok' => false,
            'error' => 'missing_host_or_port',
        ];
    }

    $errno = 0;
    $errstr = '';
    $start = microtime(true);
    $socket = @stream_socket_client(
        'tcp://' . $host . ':' . $port,
        $errno,
        $errstr,
        $timeoutSeconds,
        STREAM_CLIENT_CONNECT
    );
    $elapsedMs = (int) round((microtime(true) - $start) * 1000);

    if ($socket === false) {
        return [
            'ok' => false,
            'elapsed_ms' => $elapsedMs,
            'error_number' => $errno,
            'error_message' => $errstr,
        ];
    }

    stream_set_timeout($socket, 2);
    $banner = fgets($socket, 512);
    fclose($socket);

    return [
        'ok' => true,
        'elapsed_ms' => $elapsedMs,
        'banner' => is_string($banner) ? trim($banner) : '',
    ];
}

$host = MailEnv::host();
$port = MailEnv::port();
$enc = MailEnv::encryption();
$username = MailEnv::username();
$from = MailEnv::fromAddress();
$nextgenUrl = MailEnv::nextgenPublicBaseUrl();

$mailLocalConfigPath = dirname(__DIR__) . '/config/mail.local.php';
$mailLocalConfigExists = is_file($mailLocalConfigPath);
$mailLocalConfigReadable = is_readable($mailLocalConfigPath);

$rawEnvPresence = [];
foreach ([
    'MAIL_HOST',
    'MAIL_PORT',
    'MAIL_ENCRYPTION',
    'MAIL_USERNAME',
    'MAIL_PASSWORD',
    'MAIL_FROM_ADDRESS',
    'MAIL_FROM_NAME',
    'NEXTGEN_PUBLIC_URL',
] as $k) {
    $rawEnvPresence[$k] = [
        'getenv' => is_string(getenv($k)) && (string) getenv($k) !== '',
        'server' => isset($_SERVER[$k]) && is_string($_SERVER[$k]) && $_SERVER[$k] !== '',
        'server_redirect' => isset($_SERVER['REDIRECT_' . $k]) && is_string($_SERVER['REDIRECT_' . $k]) && $_SERVER['REDIRECT_' . $k] !== '',
        'env' => isset($_ENV[$k]) && is_string($_ENV[$k]) && $_ENV[$k] !== '',
        'env_redirect' => isset($_ENV['REDIRECT_' . $k]) && is_string($_ENV['REDIRECT_' . $k]) && $_ENV['REDIRECT_' . $k] !== '',
    ];
}

$dnsIp = $host !== '' ? gethostbyname($host) : '';
$dnsLooksResolved = $host !== '' && $dnsIp !== '' && $dnsIp !== $host;

$smtpProbe = probe_smtp_tcp($host, $port);

$phpMailerInstalled = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);

echo json_encode(
    [
        'endpoint' => 'mail_remote_diagnostics',
        'timestamp_utc' => gmdate('c'),
        'server' => [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'server_software' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? ''),
            'remote_addr' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'host_header' => (string) ($_SERVER['HTTP_HOST'] ?? ''),
            'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        ],
        'mail' => [
            'MAIL_HOST' => $host,
            'MAIL_PORT' => $port,
            'MAIL_ENCRYPTION' => $enc,
            'MAIL_USERNAME_masked' => mask_mail_identity($username),
            'MAIL_PASSWORD_set' => MailEnv::password() !== '',
            'MAIL_FROM_ADDRESS' => $from,
            'MAIL_FROM_NAME' => MailEnv::fromName(),
            'NEXTGEN_PUBLIC_URL' => $nextgenUrl,
            'NEXTGEN_MAIL_BULK_DELAY_MS_effective' => (int) (MailEnv::bulkSendDelayMicroseconds() / 1000),
        ],
        'checks' => [
            'host_set' => $host !== '',
            'from_set' => $from !== '',
            'auth_ready' => $username !== '' && MailEnv::password() !== '',
            'encryption_valid' => in_array($enc, ['tls', 'ssl'], true),
            'strato_ssl_pair' => $port === 465 && $enc === 'ssl',
            'strato_tls_pair' => $port === 587 && $enc === 'tls',
            'nextgen_public_url_set' => $nextgenUrl !== '',
            'php_openssl_loaded' => extension_loaded('openssl'),
            'phpmailer_installed' => $phpMailerInstalled,
            'smtp_host_dns_resolved' => $dnsLooksResolved,
        ],
        'network' => [
            'smtp_host_dns_ip' => $dnsLooksResolved ? $dnsIp : '',
            'smtp_tcp_probe' => $smtpProbe,
        ],
        'file_fallback' => [
            'mail_local_config_path' => $mailLocalConfigPath,
            'mail_local_config_exists' => $mailLocalConfigExists,
            'mail_local_config_readable' => $mailLocalConfigReadable,
        ],
        'raw_env_presence' => $rawEnvPresence,
        'notes' => [
            'No secrets are returned by this endpoint.',
            'A successful TCP probe does not guarantee SMTP authentication success.',
            'If host/set checks fail, verify .htaccess SetEnv visibility for PHP on your host.',
            'This endpoint checks getenv(), $_SERVER, $_ENV, and REDIRECT_* variants for env presence.',
            'MailEnv fallbacks: api/config/mail.local.php, then SetEnv parsing from api/.htaccess and ../.htaccess.',
        ],
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
