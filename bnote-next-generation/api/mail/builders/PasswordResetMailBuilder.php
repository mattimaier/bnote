<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailEnv.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailDesignTokens.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';

final class PasswordResetMailBuilder {
    /**
     * @param string $resetUrl Absolute HTTPS URL with token (link-only; no password in mail).
     */
    public static function build($system_data, string $locale, string $toEmail, string $resetUrl): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';

        $intro = htmlspecialchars(MailI18n::t('mail.passwordReset.intro', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $expiry = htmlspecialchars(MailI18n::t('mail.passwordReset.expiryNote', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ctaText = htmlspecialchars(MailI18n::t('mail.passwordReset.cta', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $bodyHtml = '<p class="em-lead" style="margin:0 0 12px;">' . $intro . '</p>'
            . '<p class="em-text-secondary" style="margin:0 0 8px;font-size:14px;">' . $expiry . '</p>';

        if ($resetUrl === '') {
            $bodyHtml .= '<p class="em-muted" style="margin:16px 0 0;font-size:13px;">'
                . htmlspecialchars(MailI18n::t('mail.passwordReset.noDeepLink', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }

        $headline = htmlspecialchars(MailI18n::t('mail.shell.headlinePasswordReset', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);

        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.passwordReset', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $ctaHref = $resetUrl !== '' ? htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $ctaLabel = $resetUrl !== '' ? $ctaText : null;

        $html = MailHtmlShell::wrapTransactional(
            $headline,
            $companyLine,
            $bodyHtml,
            $footer,
            $locale,
            $ctaHref,
            $ctaLabel,
            MailAssets::logoImgSrcForEmail()
        );

        $subject = MailI18n::interpolate(MailI18n::t('mail.passwordReset.subject', $locale), [
            'orgPrefix' => MailSubject::orgPrefix($company),
        ]);

        $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml));
        if ($resetUrl !== '') {
            $plain .= "\n\n" . MailI18n::t('mail.passwordReset.cta', $locale) . ': ' . $resetUrl;
        }
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            [trim($toEmail)],
            [],
            $subject,
            $html,
            $plain,
            'password_reset',
            MailAssets::defaultLogoEmbeds()
        );
    }
}
