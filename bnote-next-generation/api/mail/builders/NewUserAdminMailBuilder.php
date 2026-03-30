<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailEnv.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';

final class NewUserAdminMailBuilder {
    /**
     * @param array{userId:int,contactId:int,name:string,surname:string,email:string,login:string,autoUserActivation:bool} $ctx
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build($system_data, string $locale, array $ctx, array $to, array $bcc): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $fullName = trim(($ctx['name'] ?? '') . ' ' . ($ctx['surname'] ?? ''));
        $email = (string) ($ctx['email'] ?? '');
        $login = (string) ($ctx['login'] ?? $email);
        $base = MailEnv::nextgenPublicBaseUrl();
        $dashboardUrl = $base !== '' ? $base . '/dashboard/' : '';
        $integrationUrl = $base !== '' ? $base . '/contacts/integration/' : '';

        $activationNote = ($ctx['autoUserActivation'] ?? false)
            ? MailI18n::t('mail.newUserAdmin.noteAuto', $locale)
            : MailI18n::t('mail.newUserAdmin.noteManual', $locale);

        $intro = MailI18n::interpolate(MailI18n::t('mail.newUserAdmin.intro', $locale), [
            'fullName' => htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'email' => htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'login' => htmlspecialchars($login, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'company' => htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);

        $lines = '<ul style="margin:16px 0;padding-left:20px;">';
        $lines .= '<li><strong>' . htmlspecialchars(MailI18n::t('mail.newUserAdmin.labelName', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> ' . htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        $lines .= '<li><strong>' . htmlspecialchars(MailI18n::t('mail.newUserAdmin.labelEmail', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> ' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        $lines .= '<li><strong>' . htmlspecialchars(MailI18n::t('mail.newUserAdmin.labelLogin', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> ' . htmlspecialchars($login, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        $lines .= '<li><strong>' . htmlspecialchars(MailI18n::t('mail.newUserAdmin.labelUserId', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> ' . (int) ($ctx['userId'] ?? 0) . '</li>';
        $lines .= '</ul>';

        $links = '';
        if ($dashboardUrl !== '') {
            $links .= '<p style="margin:16px 0;"><a href="' . htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="color:#0d6efd;">'
                . htmlspecialchars(MailI18n::t('mail.newUserAdmin.linkDashboard', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';
        }
        if ($integrationUrl !== '') {
            $links .= '<p style="margin:16px 0;"><a href="' . htmlspecialchars($integrationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="color:#0d6efd;">'
                . htmlspecialchars(MailI18n::t('mail.newUserAdmin.linkIntegration', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';
        }
        if ($base === '') {
            $links .= '<p style="color:#666;font-size:13px;">' . htmlspecialchars(MailI18n::t('mail.newUserAdmin.noDeepLink', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }

        $inner = '<p style="margin:0 0 12px;">' . $intro . '</p>' . $lines
            . '<p style="margin:16px 0 0;">' . htmlspecialchars($activationNote, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>' . $links;

        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'company' => htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
        $html = MailHtmlShell::wrap($inner, $company !== '' ? $company : 'BNote', $footer);

        $subject = MailI18n::interpolate(MailI18n::t('mail.newUserAdmin.subject', $locale), [
            'fullName' => $fullName,
        ]);

        return new NextGenMailMessage($to, $bcc, $subject, $html, strip_tags($inner), 'new_user_admin');
    }
}
