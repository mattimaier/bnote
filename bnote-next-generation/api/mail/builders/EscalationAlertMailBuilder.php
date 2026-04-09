<?php
/**
 * Escalation alert mail for at-risk events (pending responses / instrument gaps / dropouts).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailEnv.php';
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

final class EscalationAlertMailBuilder {
    /**
     * @param list<string> $to
     * @param list<string> $bcc
     * @param list<string> $reasons
     * @param list<array{instrument_name:string,current:int,minimum:int}> $gaps
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
        string $urgency,
        array $reasons,
        array $gaps,
        string $eventUrl,
        array $to,
        array $bcc,
        ?array $participationCounts = null
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $headline = htmlspecialchars(MailI18n::t('mail.shell.headlineEscalationAlert', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

        $urgencyLabel = htmlspecialchars(self::urgencyLabel($locale, $urgency), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $titleEsc = htmlspecialchars($displayEventTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $typeEsc = htmlspecialchars($eventTypeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $timeEsc = htmlspecialchars($metaLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subject = MailI18n::interpolate(MailI18n::t('mail.escalation.subject', $locale), [
            'orgPrefix' => MailSubject::orgPrefix($company),
            'eventTitle' => $displayEventTitle,
        ]);

        $introKey = $otypeNorm === 'R' ? 'mail.escalation.introRehearsal' : 'mail.escalation.intro';
        $intro = '<p class="em-lead" style="margin:0 0 10px;">'
            . MailI18n::interpolate(MailI18n::t($introKey, $locale), [
                'eventType' => $typeEsc,
                'eventTitle' => $titleEsc,
                'eventBegin' => $timeEsc,
                'urgency' => $urgencyLabel,
            ])
            . '</p>';
        $banner = self::alertBannerHtml($locale, $urgency, $urgencyLabel);
        $participation = self::participationBarHtml($locale, $participationCounts);
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

        $reasonsHtml = '';
        if (count($reasons) > 0) {
            $reasonsHtml .= '<p style="margin:0 0 6px;font-weight:600;">'
                . htmlspecialchars(MailI18n::t('mail.escalation.reasonHeading', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p><ul style="margin:0 0 14px 18px;padding:0;">';
            foreach ($reasons as $reason) {
                $reasonsHtml .= '<li style="margin:0 0 4px;">' . htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
            }
            $reasonsHtml .= '</ul>';
        }

        $gapsHtml = '';
        if (count($gaps) > 0) {
            $gapsHtml .= '<p style="margin:0 0 6px;font-weight:600;">'
                . htmlspecialchars(MailI18n::t('mail.escalation.gapsHeading', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p><ul style="margin:0 0 14px 18px;padding:0;">';
            foreach ($gaps as $gap) {
                $gapsHtml .= '<li style="margin:0 0 4px;">'
                    . htmlspecialchars((string) ($gap['instrument_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . ': '
                    . (int) ($gap['current'] ?? 0)
                    . '/'
                    . (int) ($gap['minimum'] ?? 0)
                    . '</li>';
            }
            $gapsHtml .= '</ul>';
        }

        $body = $banner
            . $intro
            . $entityHeader
            . $participation
            . $reasonsHtml
            . $gapsHtml
            . '<p style="margin:8px 0 0;">'
            . htmlspecialchars(MailI18n::t('mail.escalation.nextAction', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>';

        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $ctaHref = $eventUrl !== '' ? htmlspecialchars($eventUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $ctaLabel = $eventUrl !== ''
            ? htmlspecialchars(MailI18n::t('mail.escalation.ctaOpenEvent', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
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

        $plainKey = $otypeNorm === 'R' ? 'mail.escalation.introPlainRehearsal' : 'mail.escalation.introPlain';
        $plain = MailI18n::interpolate(MailI18n::t($plainKey, $locale), [
            'eventType' => $eventTypeLabel,
            'eventTitle' => $displayEventTitle,
            'eventBegin' => $metaLine,
            'urgency' => self::urgencyLabel($locale, $urgency),
        ]);
        if (count($reasons) > 0) {
            $plain .= "\n\n" . MailI18n::t('mail.escalation.reasonHeading', $locale) . ":\n- " . implode("\n- ", $reasons);
        }
        if (is_array($participationCounts) && (int) ($participationCounts['invited_users'] ?? 0) > 0) {
            $plain .= "\n\n" . MailI18n::t('js.event.participation', $locale) . ': '
                . MailI18n::t('js.common.yes', $locale) . ' ' . (int) ($participationCounts['yes'] ?? 0) . ', '
                . MailI18n::t('js.participation.maybe', $locale) . ' ' . (int) ($participationCounts['maybe'] ?? 0) . ', '
                . MailI18n::t('js.common.no', $locale) . ' ' . (int) ($participationCounts['no'] ?? 0) . ', '
                . MailI18n::t('mail.escalation.pendingLabel', $locale) . ' ' . (int) ($participationCounts['pending_users'] ?? 0);
        }
        if (count($gaps) > 0) {
            $plain .= "\n\n" . MailI18n::t('mail.escalation.gapsHeading', $locale) . ":\n";
            foreach ($gaps as $gap) {
                $plain .= '- ' . (string) ($gap['instrument_name'] ?? '')
                    . ': ' . (int) ($gap['current'] ?? 0)
                    . '/' . (int) ($gap['minimum'] ?? 0) . "\n";
            }
        }
        if ($eventUrl !== '') {
            $plain .= "\n" . MailI18n::t('mail.escalation.ctaOpenEvent', $locale) . ': ' . $eventUrl;
        }
        $plain = MailBodyText::appendInstanceLink($plain, $locale);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            'escalation_alert',
            MailAssets::defaultLogoEmbeds()
        );
    }

    private static function urgencyLabel(string $locale, string $urgency): string {
        $u = strtolower(trim($urgency));
        if ($u === 'critical') {
            return MailI18n::t('mail.escalation.urgencyCritical', $locale);
        }
        return MailI18n::t('mail.escalation.urgencySoon', $locale);
    }

    private static function alertBannerHtml(string $locale, string $urgency, string $urgencyLabelEsc): string {
        $u = strtolower(trim($urgency));
        $isCritical = ($u === 'critical');
        $bg = $isCritical
            ? MailDesignTokens::get('participationDestructiveBgMuted')
            : MailDesignTokens::get('participationWarningBgMuted');
        $border = $isCritical
            ? MailDesignTokens::get('participationDestructiveBorderMuted')
            : MailDesignTokens::get('participationWarningBorderMuted');
        $text = $isCritical
            ? MailDesignTokens::get('participationDestructive')
            : MailDesignTokens::get('participationWarning');
        $headlineKey = $isCritical ? 'mail.escalation.headlineCritical' : 'mail.escalation.headlineSoon';
        $icon = self::alertTriangleSvg();

        return '<div style="margin:0 0 12px;padding:10px 12px;border:1px solid ' . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';background:' . htmlspecialchars($bg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';border-radius:10px;">'
            . '<span class="em-alert-chip" style="display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:999px;border:1px solid ' . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';font-size:12px;line-height:1.2;font-weight:700;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
            . '<span style="display:inline-flex;align-items:center;justify-content:center;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">' . $icon . '</span>'
            . '<span>' . htmlspecialchars(MailI18n::t('mail.escalation.alertBadge', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>'
            . '</span>'
            . '<span style="margin-left:8px;font-size:13px;font-weight:600;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
            . htmlspecialchars(MailI18n::t($headlineKey, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</span>'
            . '</div>';
    }

    private static function alertTriangleSvg(): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;">'
            . '<path d="M12 9v4" />'
            . '<path d="M12 17h.01" />'
            . '<path d="M10.29 3.86l-8 14a2 2 0 0 0 1.71 3h16a2 2 0 0 0 1.71-3l-8-14a2 2 0 0 0-3.42 0z" />'
            . '</svg>';
    }

    /**
     * @param null|array{invited_users?:int,pending_users?:int,yes?:int,maybe?:int,no?:int} $counts
     */
    private static function participationBarHtml(string $locale, ?array $counts): string {
        if (!is_array($counts)) {
            return '';
        }
        $yes = max(0, (int) ($counts['yes'] ?? 0));
        $maybe = max(0, (int) ($counts['maybe'] ?? 0));
        $no = max(0, (int) ($counts['no'] ?? 0));
        $pending = max(0, (int) ($counts['pending_users'] ?? 0));
        $total = max(0, (int) ($counts['invited_users'] ?? 0));
        if ($total < 1) {
            $total = $yes + $maybe + $no + $pending;
        }
        if ($total < 1) {
            return '';
        }

        $segments = [];
        if ($yes > 0) {
            $segments[] = ['key' => 'yes', 'count' => $yes, 'pct' => ($yes / $total) * 100];
        }
        if ($maybe > 0) {
            $segments[] = ['key' => 'maybe', 'count' => $maybe, 'pct' => ($maybe / $total) * 100];
        }
        if ($no > 0) {
            $segments[] = ['key' => 'no', 'count' => $no, 'pct' => ($no / $total) * 100];
        }
        if ($pending > 0) {
            $segments[] = ['key' => 'pending', 'count' => $pending, 'pct' => ($pending / $total) * 100];
        }
        if (count($segments) < 1) {
            return '';
        }

        $colors = [
            'yes' => MailDesignTokens::get('participationSuccess'),
            'maybe' => MailDesignTokens::get('participationWarning'),
            'no' => MailDesignTokens::get('participationDestructive'),
            // Match app-style neutral pending segment.
            'pending' => MailDesignTokens::get('pendingNeutral', '#9ca3af'),
        ];

        $parts = [];
        $len = count($segments);
        foreach ($segments as $i => $seg) {
            $isFirst = $i === 0;
            $isLast = $i === $len - 1;
            $radius = '';
            if ($len === 1) {
                $radius = 'border-radius:0.5rem;';
            } elseif ($isFirst) {
                $radius = 'border-top-left-radius:0.5rem;border-bottom-left-radius:0.5rem;';
            } elseif ($isLast) {
                $radius = 'border-top-right-radius:0.5rem;border-bottom-right-radius:0.5rem;';
            }
            $count = (int) $seg['count'];
            $pct = (float) $seg['pct'];
            $minPct = $count >= 10 ? 5 : 4;
            $showNumber = ($len === 1 || $pct >= $minPct);
            $parts[] = '<div style="height:32px;display:flex;align-items:center;justify-content:center;color:#ffffff;font-size:12px;font-weight:600;flex-shrink:0;'
                . $radius
                . 'width:' . number_format($pct, 6, '.', '') . '%;background-color:' . htmlspecialchars($colors[(string) $seg['key']] ?? '#9aa0a6', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
                . ($showNumber ? '<span style="white-space:nowrap;line-height:1;">' . $count . '</span>' : '')
                . '</div>';
        }

        return '<div style="margin:0 0 14px;">'
            . '<p style="margin:0 0 6px;font-weight:600;">'
            . htmlspecialchars(MailI18n::t('js.event.participation', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>'
            . '<div class="em-participation-track" style="display:flex;align-items:center;gap:0;border-radius:0.5rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);border:1px solid '
            . htmlspecialchars(MailDesignTokens::get('border'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';background:' . htmlspecialchars(MailDesignTokens::get('surfaceBorder', '#e5e7eb'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
            . implode('', $parts)
            . '</div>'
            . '</div>';
    }
}
