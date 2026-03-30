<?php
declare(strict_types=1);

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
