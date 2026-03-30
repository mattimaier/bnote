<?php
declare(strict_types=1);

require_once __DIR__ . '/MailEnv.php';

/**
 * Policy for “public” / outbound mail from the Next Gen API.
 *
 * Transactional mail (event invites, tasks, discussion on entities): contacts with no login still receive
 * mail; deactivated accounts do not; active accounts follow {@see Systemdata::userEmailNotificationOn}.
 * {@see Systemdata::contactEmailNotificationOn} is legacy and returns false when no user exists — do not use
 * for Next Gen fan-out; use {@see self::contactAllowsTransactionalNotification} instead.
 *
 * This policy must not gate system mail: password reset, self-registration activation links, admin
 * activation notices, or similar.
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

    /**
     * Event invites, task assignee mail, etc.: allow contact-only recipients; block inactive logins;
     * respect email_notification for active users.
     *
     * @param mixed $system_data Systemdata
     */
    public static function contactAllowsTransactionalNotification($system_data, int $contactId): bool {
        return self::contactTransactionalMailDenyReason($system_data, $contactId) === null;
    }

    /**
     * @param mixed $system_data Systemdata
     * @return null|string null = send; else inactive_bnote_user | user_email_notification_disabled | invalid_contact
     */
    public static function contactTransactionalMailDenyReason($system_data, int $contactId): ?string {
        if ($contactId < 1 || !$system_data || !isset($system_data->dbcon)) {
            return 'invalid_contact';
        }
        $db = $system_data->dbcon;
        $activeUid = $db->colValue(
            'SELECT id FROM user WHERE contact = ? AND isActive = 1',
            'id',
            [['i', $contactId]]
        );
        if ($activeUid !== null) {
            if (!$system_data->userEmailNotificationOn((int) $activeUid)) {
                return 'user_email_notification_disabled';
            }

            return null;
        }
        $anyUid = $db->colValue(
            'SELECT id FROM user WHERE contact = ? LIMIT 1',
            'id',
            [['i', $contactId]]
        );
        if ($anyUid !== null) {
            return 'inactive_bnote_user';
        }

        return null;
    }
}
