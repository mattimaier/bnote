<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

final class NextGenMailer {
    public static function send(NextGenMailMessage $message): bool {
        require_once __DIR__ . '/bootstrap.php';
        if (!class_exists(PHPMailer::class)) {
            error_log('NextGenMailer: PHPMailer not installed (run composer install in bnote-next-generation/api).');
            return false;
        }
        if ($message->subject === '' || $message->htmlBody === '') {
            return false;
        }
        if (count($message->to) < 1) {
            return false;
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

            foreach ($message->to as $addr) {
                $addr = trim($addr);
                if ($addr !== '') {
                    $mail->addAddress($addr);
                }
            }
            foreach ($message->bcc as $addr) {
                $addr = trim($addr);
                if ($addr !== '') {
                    $mail->addBCC($addr);
                }
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
            return true;
        } catch (Throwable $e) {
            error_log('NextGenMailer: ' . $e->getMessage());
            return false;
        }
    }
}
