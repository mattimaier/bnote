<?php
/**
 * Notify admin-group contacts when a new user registers via Next Gen API.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/kontaktedata.php';
require_once BNOTE_ROOT . '/src/logic/mailrecipientpolicy.php';
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
            /** @var list<array{email:string,firstName:string}> $recipients */
            $recipients = [];
            $seen = [];
            $n = is_array($admins) ? count($admins) : 0;
            for ($i = 1; $i < $n; $i++) {
                $row = $admins[$i];
                $e = trim((string) ($row['email'] ?? ''));
                if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL) || isset($seen[$e])) {
                    continue;
                }
                if (MailRecipientPolicy::shouldSkipOutboundDelivery($e)) {
                    continue;
                }
                $seen[$e] = true;
                $recipients[] = [
                    'email' => $e,
                    'firstName' => trim((string) ($row['name'] ?? '')),
                ];
            }
            if (count($recipients) < 1) {
                return;
            }

            $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';

            require_once __DIR__ . '/builders/NewUserAdminMailBuilder.php';
            $messages = [];
            foreach ($recipients as $r) {
                $messages[] = NewUserAdminMailBuilder::build(
                    $system_data,
                    $locale,
                    $ctx,
                    [$r['email']],
                    [],
                    $r['firstName']
                );
            }
            NextGenMailer::sendBulk($messages);
        } catch (Throwable $e) {
            error_log('RegistrationAdminNotifier: ' . $e->getMessage());
        }
    }
}
