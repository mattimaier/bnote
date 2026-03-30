<?php
/**
 * Notify admin-group contacts when a new user registers via Next Gen API.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/kontaktedata.php';
require_once __DIR__ . '/MailEnv.php';
require_once __DIR__ . '/MailI18n.php';
require_once __DIR__ . '/MailHtmlShell.php';
require_once __DIR__ . '/NextGenMailMessage.php';
require_once __DIR__ . '/NextGenMailPolicy.php';
require_once __DIR__ . '/NextGenMailer.php';

final class RegistrationAdminNotifier {
    /**
     * @param array{userId:int,contactId:int,name:string,surname:string,email:string,login:string,autoUserActivation:bool} $ctx
     */
    public static function sendSafe($system_data, array $ctx): void {
        try {
            if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
                return;
            }
            $kd = new KontakteData();
            $admins = $kd->getAdmins();
            $emails = [];
            $n = is_array($admins) ? count($admins) : 0;
            for ($i = 1; $i < $n; $i++) {
                $row = $admins[$i];
                $e = trim((string) ($row['email'] ?? ''));
                if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $e;
                }
            }
            $emails = array_values(array_unique($emails));
            if (count($emails) < 1) {
                return;
            }

            $toAddr = array_shift($emails);
            $bcc = $emails;
            $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';

            require_once __DIR__ . '/builders/NewUserAdminMailBuilder.php';
            $msg = NewUserAdminMailBuilder::build($system_data, $locale, $ctx, [$toAddr], $bcc);
            NextGenMailer::send($msg);
        } catch (Throwable $e) {
            error_log('RegistrationAdminNotifier: ' . $e->getMessage());
        }
    }
}
