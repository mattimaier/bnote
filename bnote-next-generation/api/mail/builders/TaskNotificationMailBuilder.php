<?php
/**
 * Next Gen transactional mail for task assignee (create / update).
 */
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

final class TaskNotificationMailBuilder {
    public const MODE_CREATE = 'create';
    public const MODE_UPDATE = 'update';

    /**
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build(
        $system_data,
        string $locale,
        string $mode,
        string $title,
        string $description,
        int $taskId,
        array $to,
        array $bcc
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $orgPrefix = MailSubject::orgPrefix($company);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $descRaw = trim($description);
        $descHtml = $descRaw !== ''
            ? '<p style="margin:12px 0 0;font-size:' . htmlspecialchars(MailDesignTokens::get('fontSizeBody'), ENT_QUOTES, 'UTF-8')
                . ';line-height:1.5;color:' . htmlspecialchars(MailDesignTokens::get('text'), ENT_QUOTES, 'UTF-8') . ';">'
                . nl2br(htmlspecialchars($descRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) . '</p>'
            : '';

        if ($mode === self::MODE_UPDATE) {
            $introKey = 'mail.taskNotify.introUpdate';
            $headlineKey = 'mail.shell.headlineTaskUpdated';
            $subjectKey = 'mail.taskNotify.subjectUpdate';
        } else {
            $introKey = 'mail.taskNotify.introCreate';
            $headlineKey = 'mail.shell.headlineTaskAssigned';
            $subjectKey = 'mail.taskNotify.subjectCreate';
        }

        $intro = '<p class="em-lead" style="margin:0 0 8px;">'
            . MailI18n::interpolate(MailI18n::t($introKey, $locale), [
                'title' => $titleEsc,
            ]) . '</p>'
            . $descHtml;

        $headline = htmlspecialchars(MailI18n::t($headlineKey, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $openAbs = MailEnv::nextgenTaskEntityAbsoluteUrl($taskId);
        $ctaHref = $openAbs !== '' ? htmlspecialchars($openAbs, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $ctaLabel = $openAbs !== ''
            ? htmlspecialchars(MailI18n::t('mail.taskNotify.ctaOpen', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : null;

        $html = MailHtmlShell::wrapTransactional(
            $headline,
            $companyLine,
            $intro,
            $footer,
            $locale,
            $ctaHref,
            $ctaLabel,
            MailAssets::logoImgSrcForEmail()
        );

        $subject = MailI18n::interpolate(MailI18n::t($subjectKey, $locale), [
            'orgPrefix' => $orgPrefix,
            'title' => $title,
        ]);

        $plainUrl = $openAbs !== '' ? $openAbs : MailEnv::nextgenTaskEntityRelativeUrl($taskId);
        $plain = MailI18n::interpolate(MailI18n::t($introKey, $locale), ['title' => $title]);
        if ($descRaw !== '') {
            $plain .= "\n\n" . $descRaw;
        }
        if ($plainUrl !== '') {
            $plain .= "\n\n" . MailI18n::t('mail.taskNotify.ctaOpen', $locale) . ': ' . $plainUrl;
        }
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            'task_notification',
            MailAssets::defaultLogoEmbeds()
        );
    }

    /**
     * @param array{mode:string,title:string,description:string,taskId:int} $ctx
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function buildForPreview($system_data, string $locale, array $ctx, array $to, array $bcc): NextGenMailMessage {
        return self::build(
            $system_data,
            $locale,
            (string) ($ctx['mode'] ?? self::MODE_CREATE),
            (string) ($ctx['title'] ?? 'Preview task'),
            (string) ($ctx['description'] ?? ''),
            (int) ($ctx['taskId'] ?? 1),
            $to,
            $bcc
        );
    }
}
