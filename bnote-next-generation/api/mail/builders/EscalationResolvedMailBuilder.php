<?php
/**
 * Escalation resolved mail for events that returned to a healthy state.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailDesignTokens.php';
require_once dirname(__DIR__) . '/MailEntityCard.php';
require_once dirname(__DIR__) . '/MailEntityColors.php';
require_once dirname(__DIR__) . '/MailEntityIcons.php';
require_once dirname(__DIR__) . '/MailLocaleDateTime.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';

final class EscalationResolvedMailBuilder {
    /**
     * @param list<string> $to
     * @param list<string> $bcc
     * @param null|array{invited_users?:int,pending_users?:int,yes?:int,maybe?:int,no?:int} $participationCounts
     */
    public static function build(
        $system_data,
        string $locale,
        string $eventTitle,
        string $otype,
        string $eventBegin,
        string $eventEnd,
        string $eventLocation,
        string $eventUrl,
        array $to,
        array $bcc,
        ?array $participationCounts = null
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $headline = htmlspecialchars(MailI18n::t('mail.shell.headlineEscalationResolved', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $otypeNorm = strtoupper(trim($otype)) === 'C' ? 'C' : 'R';
        $entityKey = $otypeNorm === 'C' ? 'concert' : 'rehearsal';
        $eventTypeLabel = $otypeNorm === 'C'
            ? MailI18n::t('js.event.performance', $locale)
            : MailI18n::t('js.event.rehearsal', $locale);
        $beginDt = MailLocaleDateTime::parseInBandTimezone($eventBegin);
        $dateOnly = $beginDt !== null ? MailLocaleDateTime::formatDateShort($beginDt, $locale) : '';
        $displayEventTitle = trim($eventTitle);
        if ($otypeNorm === 'R' && $dateOnly !== '') {
            $displayEventTitle = MailI18n::interpolate(MailI18n::t('mail.escalation.rehearsalTitle', $locale), [
                'date' => $dateOnly,
            ]);
        }

        $metaLine = MailLocaleDateTime::formatEventMetaLine($eventBegin, $eventEnd, $locale);
        if ($metaLine === '') {
            if ($beginDt !== null) {
                $metaLine = MailLocaleDateTime::formatDateTimeShort($beginDt, $locale);
            } else {
                $metaLine = $eventBegin;
            }
        }
        $timeEsc = htmlspecialchars($metaLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $titleEsc = htmlspecialchars($displayEventTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $typeEsc = htmlspecialchars($eventTypeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $subject = MailI18n::interpolate(MailI18n::t('mail.escalationResolved.subject', $locale), [
            'orgPrefix' => MailSubject::orgPrefix($company),
            'eventTitle' => $displayEventTitle,
        ]);

        $statusBanner = self::resolvedBannerHtml($locale);
        $intro = '<p class="em-lead" style="margin:0 0 10px;">'
            . MailI18n::interpolate(MailI18n::t('mail.escalationResolved.intro', $locale), [
                'eventType' => $typeEsc,
                'eventTitle' => $titleEsc,
                'eventBegin' => $timeEsc,
            ])
            . '</p>';
        $statusLine = '<p style="margin:0 0 12px;font-weight:600;">'
            . htmlspecialchars(MailI18n::t('mail.escalationResolved.requirementsMet', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>';

        $entityHeader = MailEntityCard::entityHeaderHtml(
            [
                'icon_bg' => MailEntityColors::solidHex($entityKey),
                'icon_entity_key' => $entityKey,
                'icon_inner_html' => MailEntityIcons::inlineSvgForEntityKey($entityKey),
                'icon_char' => $otypeNorm === 'C' ? "\u{266A}" : "\u{266B}",
                'title' => $displayEventTitle,
                'badge_label' => $eventTypeLabel,
                'badge_bg' => MailEntityColors::mixWithWhite(MailEntityColors::solidHex($entityKey), 0.10),
                'badge_border' => MailEntityColors::mixWithWhite(MailEntityColors::solidHex($entityKey), 0.22),
                'badge_text' => MailEntityColors::solidHex($entityKey),
                'meta_line' => $metaLine,
                'location_line' => $eventLocation,
            ],
            MailDesignTokens::get('cardBg'),
            MailDesignTokens::get('border'),
            MailDesignTokens::get('text'),
            MailDesignTokens::get('textMuted'),
            MailDesignTokens::get('fontSizeSmall'),
            MailDesignTokens::get('fontSizeBody'),
            $eventUrl !== '' ? $eventUrl : null,
            $eventUrl !== ''
                ? MailI18n::interpolate(MailI18n::t('mail.entityCard.linkAria', $locale), ['title' => $displayEventTitle])
                : null
        );

        $participation = self::participationLine($locale, $participationCounts);
        $body = $statusBanner . $intro . $statusLine . $entityHeader . $participation;

        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);
        $ctaHref = $eventUrl !== '' ? htmlspecialchars($eventUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $ctaLabel = $eventUrl !== ''
            ? htmlspecialchars(MailI18n::t('mail.escalationResolved.ctaOpenEvent', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : null;

        $html = MailHtmlShell::wrapTransactional(
            $headline,
            $companyLine,
            $body,
            $footer,
            $locale,
            $ctaHref,
            $ctaLabel,
            MailAssets::logoImgSrcForEmail()
        );

        $plain = MailI18n::interpolate(MailI18n::t('mail.escalationResolved.introPlain', $locale), [
            'eventType' => $eventTypeLabel,
            'eventTitle' => $displayEventTitle,
            'eventBegin' => $metaLine,
        ]);
        $plain .= "\n" . MailI18n::t('mail.escalationResolved.requirementsMet', $locale);
        if (is_array($participationCounts) && (int) ($participationCounts['invited_users'] ?? 0) > 0) {
            $plain .= "\n" . self::participationPlainText($locale, $participationCounts);
        }
        if ($eventUrl !== '') {
            $plain .= "\n" . MailI18n::t('mail.escalationResolved.ctaOpenEvent', $locale) . ': ' . $eventUrl;
        }
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            'escalation_resolved',
            MailAssets::defaultLogoEmbeds()
        );
    }

    private static function resolvedBannerHtml(string $locale): string {
        $bg = MailDesignTokens::get('participationSuccessBgMuted');
        $border = MailDesignTokens::get('participationSuccessBorderMuted');
        $text = MailDesignTokens::get('participationSuccess');
        return '<div style="margin:0 0 12px;padding:10px 12px;border:1px solid ' . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';background:' . htmlspecialchars($bg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';border-radius:10px;">'
            . '<span style="display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:999px;border:1px solid ' . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';font-size:12px;line-height:1.2;font-weight:700;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
            . '<span>' . htmlspecialchars(MailI18n::t('mail.escalationResolved.statusBadge', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>'
            . '</span>'
            . '</div>';
    }

    /**
     * @param null|array{invited_users?:int,pending_users?:int,yes?:int,maybe?:int,no?:int} $counts
     */
    private static function participationLine(string $locale, ?array $counts): string {
        if (!is_array($counts) || (int) ($counts['invited_users'] ?? 0) < 1) {
            return '';
        }
        return '<p style="margin:10px 0 0;color:' . htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';font-size:13px;">'
            . htmlspecialchars(self::participationPlainText($locale, $counts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>';
    }

    /**
     * @param array{invited_users?:int,pending_users?:int,yes?:int,maybe?:int,no?:int} $counts
     */
    private static function participationPlainText(string $locale, array $counts): string {
        return MailI18n::t('js.event.participation', $locale) . ': '
            . MailI18n::t('js.common.yes', $locale) . ' ' . (int) ($counts['yes'] ?? 0) . ', '
            . MailI18n::t('js.participation.maybe', $locale) . ' ' . (int) ($counts['maybe'] ?? 0) . ', '
            . MailI18n::t('js.common.no', $locale) . ' ' . (int) ($counts['no'] ?? 0) . ', '
            . MailI18n::t('mail.escalation.pendingLabel', $locale) . ' ' . (int) ($counts['pending_users'] ?? 0);
    }
}
