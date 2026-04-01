<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailEnv.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailDesignTokens.php';
require_once dirname(__DIR__) . '/MailEntityColors.php';
require_once dirname(__DIR__) . '/MailEntityCard.php';
require_once dirname(__DIR__) . '/MailLocaleDateTime.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';
require_once dirname(__DIR__) . '/CommentDiscussionEntityUrl.php';

final class CommentDiscussionMailBuilder {
    private const THREAD_MAX = 15;

    /**
     * @param list<array{author:string,message:string,created_at:string,is_new:bool}> $thread Chronological, oldest first
     * @param array{
     *   icon_bg:string,
     *   icon_inner_html?:string,
     *   icon_char:string,
     *   title:string,
     *   badge_label:string,
     *   badge_bg:string,
     *   badge_border:string,
     *   badge_text:string,
     *   meta_line:string,
     *   location_line:string
     * }|null $entityCard
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build(
        $system_data,
        string $locale,
        string $otype,
        int $oid,
        string $entityTitle,
        string $authorLine,
        array $thread,
        ?array $entityCard,
        array $to,
        array $bcc,
        ?string $recipientFirstName = null
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $openUrl = CommentDiscussionEntityUrl::openDiscussionUrl($otype, $oid);

        $primary = MailDesignTokens::get('primary');
        $border = MailDesignTokens::get('border');
        $pageBg = MailDesignTokens::get('pageBg');
        $cardBg = MailDesignTokens::get('cardBg');
        $textMuted = MailDesignTokens::get('textMuted');
        $text = MailDesignTokens::get('text');
        $fsSmall = MailDesignTokens::get('fontSizeSmall');
        $fsBody = MailDesignTokens::get('fontSizeBody');

        $accent = $primary;
        if ($entityCard !== null) {
            $ib = trim((string) ($entityCard['icon_bg'] ?? ''));
            if ($ib !== '') {
                $accent = $ib;
            }
        }
        $accentEsc = htmlspecialchars($accent, ENT_QUOTES, 'UTF-8');
        $newCommentFrameBgEsc = htmlspecialchars(MailEntityColors::mailMutedFill($accent, 0.085), ENT_QUOTES, 'UTF-8');
        $authorEsc = htmlspecialchars($authorLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $intro = self::resolvedIntro($locale, $authorEsc, $entityTitle, $otype, $entityCard);

        $greetingHtml = '';
        if ($recipientFirstName !== null && trim($recipientFirstName) !== '') {
            $fnEsc = htmlspecialchars(trim($recipientFirstName), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $greetingHtml = '<p class="em-lead" style="margin:0 0 10px;">'
                . MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.greetingHi', $locale), [
                    'firstName' => $fnEsc,
                ]) . '</p>';
        }

        $headerHtml = $entityCard !== null
            ? MailEntityCard::entityHeaderHtml(
                $entityCard,
                $cardBg,
                $border,
                $text,
                $textMuted,
                $fsSmall,
                $fsBody,
                $openUrl !== '' ? $openUrl : null,
                $openUrl !== ''
                    ? MailI18n::interpolate(MailI18n::t('mail.entityCard.linkAria', $locale), [
                        'title' => trim((string) ($entityCard['title'] ?? $entityTitle)),
                    ])
                    : null
            )
            : '';

        $threadTitle = '';
        if (count($thread) > 1) {
            $threadTitle = '<p class="em-section-title" style="margin:20px 0 12px;font-size:17px;font-weight:700;">'
                . htmlspecialchars(MailI18n::t('mail.commentDiscussion.threadHeading', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p>';
        }

        $bubbles = '';
        foreach ($thread as $row) {
            $bubbles .= self::bubbleRow(
                $row,
                $locale,
                $textMuted,
                $text,
                $fsSmall,
                $fsBody,
                $accentEsc,
                $newCommentFrameBgEsc
            );
        }

        $noLink = '';
        if ($openUrl === '') {
            $noLink = '<p class="em-muted" style="font-size:13px;margin:16px 0 0;">'
                . htmlspecialchars(MailI18n::t('mail.commentDiscussion.noDeepLink', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p>';
        }

        $inner = $greetingHtml . '<p class="em-lead" style="margin:0 0 16px;">' . $intro . '</p>'
            . $headerHtml
            . $threadTitle
            . $bubbles
            . $noLink;

        $headline = htmlspecialchars(MailI18n::t('mail.shell.headlineCommentDiscussion', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $ctaHref = $openUrl !== '' ? htmlspecialchars($openUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $ctaLabel = $openUrl !== ''
            ? htmlspecialchars(MailI18n::t('mail.commentDiscussion.ctaOpen', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : null;

        $html = MailHtmlShell::wrapTransactional(
            $headline,
            $companyLine,
            $inner,
            $footer,
            $locale,
            $ctaHref,
            $ctaLabel,
            MailAssets::logoImgSrcForEmail()
        );

        $subject = self::resolvedSubject($locale, $company, $entityTitle, $otype, $entityCard);

        $plain = '';
        if ($recipientFirstName !== null && trim($recipientFirstName) !== '') {
            $plain .= MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.greetingHi', $locale), [
                'firstName' => trim($recipientFirstName),
            ]) . "\n\n";
        }
        $plain .= strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $intro)) . "\n\n";
        if ($entityCard !== null) {
            $plain .= ($entityCard['title'] ?? '') . "\n"
                . ($entityCard['meta_line'] ?? '') . "\n"
                . ($entityCard['location_line'] ?? '') . "\n\n";
        }
        foreach ($thread as $row) {
            $mark = !empty($row['is_new']) ? ('[' . MailI18n::t('mail.commentDiscussion.badgeNew', $locale) . '] ') : '';
            $plain .= $mark . ($row['author'] ?? '') . ' — '
                . MailLocaleDateTime::formatCommentCreatedAt((string) ($row['created_at'] ?? ''), $locale) . "\n"
                . ($row['message'] ?? '') . "\n\n";
        }
        if ($openUrl !== '') {
            $plain .= MailI18n::t('mail.commentDiscussion.ctaOpen', $locale) . ': ' . $openUrl . "\n";
        }
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            'comment_discussion',
            MailAssets::defaultLogoEmbeds()
        );
    }

    private static function isGermanLocale(string $locale): bool {
        $l = strtolower(explode('-', $locale)[0] ?? '');

        return $l === 'de';
    }

    /**
     * Date segment before middle dot (e.g. "02.04.2026 · 03:30 - 05:09") or full meta if no dot.
     */
    private static function extractEventDateFromCard(?array $entityCard): string {
        if ($entityCard === null) {
            return '';
        }
        $meta = trim((string) ($entityCard['meta_line'] ?? ''));
        if ($meta === '') {
            return '';
        }
        if (strpos($meta, '·') !== false) {
            $parts = preg_split('/\s*·\s*/u', $meta, 2);

            return trim($parts[0] ?? '');
        }

        return $meta;
    }

    private static function resolvedIntro(
        string $locale,
        string $authorEsc,
        string $entityTitle,
        string $otype,
        ?array $entityCard
    ): string {
        $entityEsc = htmlspecialchars($entityTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $otypeU = strtoupper($otype);
        if (self::isGermanLocale($locale)) {
            $date = self::extractEventDateFromCard($entityCard);
            if ($otypeU === 'R' && $date !== '') {
                $dateEsc = htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $typeLabelEsc = htmlspecialchars(MailI18n::t('js.event.rehearsal', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.introDeRehearsal', $locale), [
                    'author' => $authorEsc,
                    'entityDate' => $dateEsc,
                    'entityTypeLabel' => $typeLabelEsc,
                ]);
            }
            if ($otypeU === 'C' && $date !== '') {
                $dateEsc = htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $typeLabelEsc = htmlspecialchars(MailI18n::t('js.event.performance', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.introDeConcert', $locale), [
                    'author' => $authorEsc,
                    'entityDate' => $dateEsc,
                    'entityTypeLabel' => $typeLabelEsc,
                ]);
            }
            if ($otypeU === 'V') {
                $vt = '';
                if ($entityCard !== null) {
                    $vt = trim((string) ($entityCard['title'] ?? ''));
                }
                if ($vt === '') {
                    $vt = $entityTitle;
                }
                $voteEsc = htmlspecialchars($vt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.introDeVote', $locale), [
                    'author' => $authorEsc,
                    'voteTitle' => $voteEsc,
                ]);
            }
        }

        return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.intro', $locale), [
            'author' => $authorEsc,
            'entityTitle' => $entityEsc,
        ]);
    }

    private static function resolvedSubject(
        string $locale,
        string $company,
        string $entityTitle,
        string $otype,
        ?array $entityCard
    ): string {
        $orgPrefix = MailSubject::orgPrefix($company);
        $otypeU = strtoupper($otype);
        if (self::isGermanLocale($locale)) {
            $date = self::extractEventDateFromCard($entityCard);
            if ($otypeU === 'R' && $date !== '') {
                return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.subjectDeRehearsal', $locale), [
                    'orgPrefix' => $orgPrefix,
                    'entityDate' => $date,
                    'entityTypeLabel' => MailI18n::t('js.event.rehearsal', $locale),
                ]);
            }
            if ($otypeU === 'C') {
                $concertTitle = '';
                if ($entityCard !== null) {
                    $concertTitle = trim((string) ($entityCard['title'] ?? ''));
                }
                if ($concertTitle === '') {
                    $concertTitle = $entityTitle;
                }

                return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.subjectDeConcert', $locale), [
                    'orgPrefix' => $orgPrefix,
                    'concertTitle' => $concertTitle,
                ]);
            }
        }

        return MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.subject', $locale), [
            'orgPrefix' => $orgPrefix,
            'entityTitle' => $entityTitle,
        ]);
    }

    /**
     * @param array{author:string,message:string,created_at:string,is_new:bool} $row
     */
    private static function bubbleRow(
        array $row,
        string $locale,
        string $textMuted,
        string $text,
        string $fsSmall,
        string $fsBody,
        string $accentEsc,
        string $newCommentFrameBgEsc
    ): string {
        $isNew = !empty($row['is_new']);
        $authorRaw = (string) ($row['author'] ?? '');
        $author = htmlspecialchars($authorRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $when = htmlspecialchars(
            MailLocaleDateTime::formatCommentCreatedAt((string) ($row['created_at'] ?? ''), $locale),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $msgRaw = (string) ($row['message'] ?? '');
        $msgHtml = nl2br(htmlspecialchars($msgRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);

        $nameForInitial = trim($authorRaw) !== '' ? trim($authorRaw) : '?';
        if (function_exists('mb_substr')) {
            $initial = mb_substr($nameForInitial, 0, 1, 'UTF-8');
            $initial = function_exists('mb_strtoupper') ? mb_strtoupper($initial, 'UTF-8') : strtoupper($initial);
        } else {
            $initial = strtoupper(substr($nameForInitial, 0, 1));
        }
        $initialEsc = htmlspecialchars($initial, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $avatarBg = htmlspecialchars(MailDesignTokens::get('chatAvatarBg', '#dbeafe'), ENT_QUOTES, 'UTF-8');
        $avatarFg = htmlspecialchars(MailDesignTokens::get('chatAvatarText', '#1d4ed8'), ENT_QUOTES, 'UTF-8');
        $bubbleBg = htmlspecialchars(MailDesignTokens::get('surfaceBg', '#f3f4f6'), ENT_QUOTES, 'UTF-8');
        $bubbleBd = htmlspecialchars(MailDesignTokens::get('surfaceBorder', '#e5e7eb'), ENT_QUOTES, 'UTF-8');
        $labelColor = htmlspecialchars($textMuted, ENT_QUOTES, 'UTF-8');
        $textColor = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $fsSmallEsc = htmlspecialchars($fsSmall, ENT_QUOTES, 'UTF-8');
        $fsBodyEsc = htmlspecialchars($fsBody, ENT_QUOTES, 'UTF-8');

        $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td valign="top" style="width:44px;padding:0 10px 0 0;">'
            . '<div class="em-chat-avatar" style="width:36px;height:36px;border-radius:9999px;background-color:' . $avatarBg . ';color:' . $avatarFg
            . ';font-weight:700;font-size:14px;line-height:36px;text-align:center;">' . $initialEsc . '</div>'
            . '</td>'
            . '<td valign="top" style="padding:0;">'
            . '<p style="margin:0 0 6px;font-size:' . $fsSmallEsc . ';line-height:1.4;color:' . $labelColor . ';">'
            . '<span style="font-weight:600;color:' . $textColor . ';">' . $author . '</span>'
            . ' <span style="color:' . $labelColor . ';">· ' . $when . '</span></p>'
            . '<div class="em-chat-bubble" style="display:inline-block;max-width:100%;border-radius:16px;padding:10px 14px;background-color:' . $bubbleBg
            . ';border:1px solid ' . $bubbleBd . ';color:' . $textColor . ';font-size:' . $fsBodyEsc
            . ';line-height:1.5;word-wrap:break-word;">' . $msgHtml . '</div>'
            . '</td></tr></table>';

        if ($isNew) {
            $newLabel = htmlspecialchars(MailI18n::t('mail.commentDiscussion.newCommentLabel', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Single bordered div (not border+radius on <td>): avoids WebKit mail ghosting from box-shadow + border on rounded corners.
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">'
                . '<tr><td style="padding:0;">'
                . '<div style="border:2px solid ' . $accentEsc . ';border-radius:18px;background-color:' . $newCommentFrameBgEsc . ';overflow:hidden;">'
                . '<div class="em-new-comment-head" style="padding:12px 14px 10px;border-bottom:1px solid ' . $accentEsc . ';">'
                . '<span style="font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:' . $accentEsc . ';">'
                . $newLabel . '</span>'
                . '</div>'
                . '<div style="padding:12px 14px 14px;">' . $inner . '</div>'
                . '</div>'
                . '</td></tr></table>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">'
            . '<tr><td style="padding:0;">' . $inner . '</td></tr></table>';
    }

    /**
     * @param array{
     *   otype:string,
     *   oid:int,
     *   entityTitle:string,
     *   authorLine:string,
     *   thread:list<array{author:string,message:string,created_at:string,is_new:bool}>,
     *   entityCard?:array|null,
     *   recipientFirstName?:string|null
     * } $ctx
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function buildForPreview($system_data, string $locale, array $ctx, array $to, array $bcc): NextGenMailMessage {
        $entityCard = $ctx['entityCard'] ?? null;
        if (!is_array($entityCard)) {
            $entityCard = null;
        }
        $previewFirst = isset($ctx['recipientFirstName']) ? (string) $ctx['recipientFirstName'] : null;
        if ($previewFirst === '') {
            $previewFirst = null;
        }

        return self::build(
            $system_data,
            $locale,
            (string) ($ctx['otype'] ?? 'R'),
            (int) ($ctx['oid'] ?? 0),
            (string) ($ctx['entityTitle'] ?? ''),
            (string) ($ctx['authorLine'] ?? ''),
            $ctx['thread'] ?? [],
            $entityCard,
            $to,
            $bcc,
            $previewFirst
        );
    }

    public static function threadMax(): int {
        return self::THREAD_MAX;
    }
}
