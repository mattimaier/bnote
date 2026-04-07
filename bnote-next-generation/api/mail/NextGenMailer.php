<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

final class NextGenMailer {
    private static string $lastError = '';

    public static function getLastError(): string {
        return self::$lastError;
    }

    /**
     * Send several messages in order, pausing between sends when bulk delay is non-zero (see MailEnv::bulkSendDelayMicroseconds).
     *
     * @param list<NextGenMailMessage> $messages
     * @return int Number of messages for which {@see send()} returned true
     */
    public static function sendBulk(array $messages): int {
        require_once __DIR__ . '/MailEnv.php';
        $delayUs = MailEnv::bulkSendDelayMicroseconds();
        $ok = 0;
        $last = count($messages) - 1;
        foreach ($messages as $i => $message) {
            if (!$message instanceof NextGenMailMessage) {
                continue;
            }
            if (self::send($message)) {
                $ok++;
            }
            if ($i < $last && $delayUs > 0) {
                usleep($delayUs);
            }
        }

        return $ok;
    }

    public static function send(NextGenMailMessage $message): bool {
        self::$lastError = '';
        require_once __DIR__ . '/bootstrap.php';
        require_once dirname(__DIR__) . '/nextgen_stats_audit_schema.php';
        if (!defined('BNOTE_ROOT')) {
            require_once dirname(__DIR__) . '/paths.php';
        }
        require_once __DIR__ . '/MailRecipientPolicy.php';
        global $system_data;
        if (!class_exists(PHPMailer::class)) {
            self::$lastError = 'PHPMailer not installed';
            error_log('NextGenMailer: ' . self::$lastError . ' (run composer install in bnote-next-generation/api).');
            return false;
        }
        if ($message->subject === '' || $message->htmlBody === '') {
            self::$lastError = 'Missing subject or HTML body';
            return false;
        }

        $to = [];
        foreach ($message->to as $addr) {
            $addr = trim((string) $addr);
            if (
                $addr !== ''
                && !MailRecipientPolicy::shouldSkipOutboundDelivery($addr)
                && !MailRecipientPolicy::shouldSkipInactiveUserRecipient($addr, $system_data)
            ) {
                $to[] = $addr;
            }
        }
        $bcc = [];
        foreach ($message->bcc as $addr) {
            $addr = trim((string) $addr);
            if (
                $addr !== ''
                && !MailRecipientPolicy::shouldSkipOutboundDelivery($addr)
                && !MailRecipientPolicy::shouldSkipInactiveUserRecipient($addr, $system_data)
            ) {
                $bcc[] = $addr;
            }
        }
        if (count($to) < 1 && count($bcc) < 1) {
            return true;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->isSMTP();
            $mail->Host = MailEnv::host();
            $mail->Port = MailEnv::port();
            $enc = MailEnv::encryption();
            if ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $user = MailEnv::username();
            if ($user !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = MailEnv::password();
            }

            $from = MailEnv::fromAddress();
            $fromName = MailEnv::fromName();
            $mail->setFrom($from, $fromName !== '' ? $fromName : $from);

            foreach ($to as $addr) {
                $mail->addAddress($addr);
            }
            foreach ($bcc as $addr) {
                $mail->addBCC($addr);
            }

            foreach ($message->embeds as $embed) {
                $path = isset($embed['path']) ? (string) $embed['path'] : '';
                $cid = isset($embed['cid']) ? (string) $embed['cid'] : '';
                if ($path !== '' && $cid !== '' && is_readable($path)) {
                    $mail->addEmbeddedImage($path, $cid, basename($path));
                }
            }

            $mail->Subject = $message->subject;
            $mail->isHTML(true);
            $mail->Body = $message->htmlBody;
            $text = $message->textBody !== '' ? $message->textBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message->htmlBody));
            $mail->AltBody = $text;

            $mail->send();
            try {
                $templateId = trim($message->templateId);
                $module = self::moduleForTemplateId($templateId);
                NextGenStatsAuditSchema::logMailDelivery(
                    $system_data->dbcon,
                    $templateId,
                    $module,
                    count($to),
                    count($bcc),
                    true
                );
            } catch (Throwable $e) {
                error_log('NextGenMailer audit log failed: ' . $e->getMessage());
            }
            return true;
        } catch (Throwable $e) {
            $msg = trim((string) $e->getMessage());
            self::$lastError = $msg !== '' ? $msg : 'Unknown SMTP error';
            if (isset($mail) && $mail instanceof PHPMailer && trim((string) $mail->ErrorInfo) !== '') {
                self::$lastError .= ' | ' . trim((string) $mail->ErrorInfo);
            }
            error_log('NextGenMailer: ' . self::$lastError);
            return false;
        }
    }

    private static function moduleForTemplateId(string $templateId): string {
        $normalized = strtolower(trim($templateId));
        if ($normalized === '') {
            return 'mailer';
        }

        return match ($normalized) {
            'event_participant_invite', 'event_info' => 'events',
            'task_notification' => 'tasks',
            'generic_email_composer' => 'email',
            'password_reset', 'new_user_admin', 'user_welcome' => 'auth',
            'comment_discussion' => 'comments',
            'reminder_digest', 'escalation_alert', 'escalation_resolved' => 'reminders',
            'beta_bug_report' => 'bugreport',
            default => 'mailer',
        };
    }
}
