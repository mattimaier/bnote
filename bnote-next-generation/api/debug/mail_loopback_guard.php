<?php
declare(strict_types=1);

/**
 * Shared guard for local-only api/debug/* endpoints (127.0.0.1 / ::1).
 */
function mail_loopback_guard(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Forbidden';
        exit;
    }
}
