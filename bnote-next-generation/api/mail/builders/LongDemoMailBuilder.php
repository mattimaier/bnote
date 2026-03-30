<?php
declare(strict_types=1);

/**
 * Long layout demo for mail_preview / mail_debug only (not sent by production features).
 */
require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailEnv.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailDesignTokens.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/MailGreeting.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';

final class LongDemoMailBuilder {
    public static function build($system_data, string $locale, string $toEmail, string $recipientFirstName = ''): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';

        $h2First = 'margin:0 0 10px;';
        $h2 = 'margin:28px 0 10px;';
        $p = 'margin:0 0 14px;';

        $esc = static fn (string $key) => htmlspecialchars(MailI18n::t($key, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $bodyHtml = MailGreeting::htmlLeadParagraph($locale, $recipientFirstName)
            . '<h2 class="em-section-title" style="' . $h2First . '">' . $esc('mail.longDemo.section1Title') . '</h2>'
            . '<p class="em-section-p" style="' . $p . '">' . $esc('mail.longDemo.section1Body') . '</p>'
            . '<p class="em-section-p-muted" style="margin:0 0 14px;">' . $esc('mail.longDemo.section1Aside') . '</p>'
            . '<hr class="em-hr"/>'
            . '<h2 class="em-section-title" style="' . $h2 . '">' . $esc('mail.longDemo.section2Title') . '</h2>'
            . '<p class="em-section-p" style="' . $p . '">' . $esc('mail.longDemo.section2Body') . '</p>'
            . '<p class="em-section-p" style="' . $p . '">' . $esc('mail.longDemo.section2Body2') . '</p>'
            . '<hr class="em-hr"/>'
            . '<h2 class="em-section-title" style="' . $h2 . '">' . $esc('mail.longDemo.section3Title') . '</h2>'
            . '<p class="em-section-p" style="' . $p . '">' . $esc('mail.longDemo.section3Intro') . '</p>'
            . '<p class="em-section-p" style="' . $p . '"><strong>' . $esc('mail.longDemo.listTitle') . '</strong></p>'
            . '<ul class="em-list" style="margin:0 0 16px;padding-left:22px;">'
            . '<li style="margin:0 0 8px;">' . $esc('mail.longDemo.listItem1') . '</li>'
            . '<li style="margin:0 0 8px;">' . $esc('mail.longDemo.listItem2') . '</li>'
            . '<li style="margin:0 0 8px;">' . $esc('mail.longDemo.listItem3') . '</li>'
            . '</ul>'
            . '<p class="em-section-p-muted" style="margin:0 0 14px;">' . $esc('mail.longDemo.section3Closing') . '</p>';

        $headline = $esc('mail.shell.headlineLongDemo');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $footer = htmlspecialchars(MailI18n::t('mail.footer.longDemo', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $ctaHref = null;
        $ctaLabel = null;
        $base = MailEnv::nextgenPublicBaseUrl();
        if ($base !== '') {
            $ctaHref = htmlspecialchars(rtrim($base, '/') . '/dashboard/', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $ctaLabel = $esc('mail.longDemo.cta');
        }

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

        $subject = MailI18n::interpolate(MailI18n::t('mail.longDemo.subject', $locale), [
            'orgPrefix' => MailSubject::orgPrefix($company),
        ]);

        $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml));
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            [trim($toEmail)],
            [],
            $subject,
            $html,
            $plain,
            'long_demo',
            MailAssets::defaultLogoEmbeds()
        );
    }
}
