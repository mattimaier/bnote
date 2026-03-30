<?php
declare(strict_types=1);

/**
 * Policy for “public” / outbound mail from the Next Gen API.
 *
 * User preference {@see Systemdata::userEmailNotificationOn} / contactEmailNotificationOn applies only to
 * activity mail (tasks, discussion comments, etc.). It must not gate system mail: password reset,
 * self-registration activation links, admin activation notices, or similar.
 */
final class NextGenMailPolicy {
    public static function shouldSendPublicMail($system_data): bool {
        if ($system_data && method_exists($system_data, 'inDemoMode') && $system_data->inDemoMode()) {
            return false;
        }
        if (MailEnv::host() === '' || MailEnv::fromAddress() === '') {
            return false;
        }
        return true;
    }
}
