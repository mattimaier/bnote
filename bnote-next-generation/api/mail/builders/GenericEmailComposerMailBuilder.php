<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';
require_once __DIR__ . '/EventInfoMailBuilder.php';

final class GenericEmailComposerMailBuilder {
    /**
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build(
        $system_data,
        string $locale,
        string $subject,
        string $subjectHeadline,
        string $body,
        string $senderName,
        array $to,
        array $bcc
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $orgPrefix = MailSubject::orgPrefix($company);
        $subjectFinal = trim($subject) !== '' ? trim($subject) : MailI18n::interpolate(MailI18n::t('mail.genericComposer.subjectFallback', $locale), [
            'orgPrefix' => $orgPrefix,
        ]);

        $headlineRaw = trim($subjectHeadline) !== '' ? trim($subjectHeadline) : MailI18n::t('mail.shell.headlineGenericEmail', $locale);
        $headline = htmlspecialchars($headlineRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);
        $senderNameTrimmed = trim($senderName);
        if ($senderNameTrimmed !== '') {
            $senderLine = MailI18n::interpolate(MailI18n::t('mail.footer.sentBy', $locale), [
                'name' => htmlspecialchars($senderNameTrimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ]);
            $footer .= '<br>' . $senderLine;
        }

        $rendered = EventInfoMailBuilder::renderEditorBody($body);
        $bodyHtml = $rendered['html'];

        $html = MailHtmlShell::wrapTransactional(
            $headline,
            $companyLine,
            $bodyHtml,
            $footer,
            $locale,
            null,
            null,
            MailAssets::logoImgSrcForEmail()
        );

        $plain = trim($rendered['text']);
        if ($plain === '') {
            $plain = trim(strip_tags($body));
        }
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subjectFinal,
            $html,
            $plain,
            'generic_email_composer',
            MailAssets::defaultLogoEmbeds()
        );
    }
}
