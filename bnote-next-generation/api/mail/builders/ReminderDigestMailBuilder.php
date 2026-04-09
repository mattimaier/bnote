<?php
/**
 * Weekly reminder digest mail with UI-aligned list rows and participation traffic lights for events.
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
require_once dirname(__DIR__) . '/MailEntityColors.php';
require_once dirname(__DIR__) . '/MailEntityIcons.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';

final class ReminderDigestMailBuilder {
    /**
     * @param list<array<string,mixed>> $events
     * @param list<array<string,mixed>> $votes
     * @param list<array<string,mixed>> $tasks
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build(
        $system_data,
        string $locale,
        ?string $recipientFirstName,
        array $eventsUpcoming,
        array $eventsPendingResponse,
        array $votes,
        array $tasks,
        array $to,
        array $bcc,
        array $options = []
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $headlineKey = isset($options['headlineKey']) ? (string) $options['headlineKey'] : 'mail.shell.headlineReminderDigest';
        $subjectKey = isset($options['subjectKey']) ? (string) $options['subjectKey'] : 'mail.reminder.subject';
        $introKey = isset($options['introKey']) ? (string) $options['introKey'] : 'mail.reminder.intro';
        $ctaLabelKey = isset($options['ctaLabelKey']) ? (string) $options['ctaLabelKey'] : 'mail.reminder.ctaOpenDashboard';
        $ctaPath = isset($options['ctaPath']) ? trim((string) $options['ctaPath']) : '/dashboard';
        $templateKey = isset($options['templateKey']) ? (string) $options['templateKey'] : 'reminder_digest_weekly';

        $headline = htmlspecialchars(MailI18n::t($headlineKey, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $subject = MailI18n::interpolate(MailI18n::t($subjectKey, $locale), [
            'orgPrefix' => MailSubject::orgPrefix($company),
        ]);

        $intro = '';
        if ($recipientFirstName !== null && trim($recipientFirstName) !== '') {
            $intro .= '<p class="em-lead" style="margin:0 0 8px;">'
                . MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.greetingHi', $locale), ['firstName' => htmlspecialchars(trim($recipientFirstName), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')])
                . '</p>';
        }
        $intro .= '<p class="em-lead" style="margin:0 0 18px;">'
            . htmlspecialchars(MailI18n::t($introKey, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p>';

        $body = $intro
            . self::sectionHtml($locale, 'mail.reminder.sectionEventsUpcoming', $eventsUpcoming, true)
            . self::sectionHtml($locale, 'mail.reminder.sectionEventsPendingResponse', $eventsPendingResponse, true)
            . self::sectionHtml($locale, 'mail.reminder.sectionVotes', $votes, false)
            . self::sectionHtml($locale, 'mail.reminder.sectionTasks', $tasks, false);

        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $openUrl = MailEnv::nextgenPublicBaseUrl();
        $ctaHref = null;
        if ($openUrl !== '') {
            $path = $ctaPath !== '' ? ('/' . ltrim($ctaPath, '/')) : '/dashboard';
            $ctaHref = htmlspecialchars($openUrl . $path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $ctaLabel = $openUrl !== ''
            ? htmlspecialchars(MailI18n::t($ctaLabelKey, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
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

        $plain = self::buildPlain($locale, $recipientFirstName, $eventsUpcoming, $eventsPendingResponse, $votes, $tasks, $introKey);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            $templateKey,
            MailAssets::defaultLogoEmbeds()
        );
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    private static function sectionHtml(string $locale, string $titleKey, array $items, bool $withTraffic): string {
        if (count($items) === 0) {
            return '';
        }
        $title = htmlspecialchars(MailI18n::t($titleKey, $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $border = htmlspecialchars(MailDesignTokens::get('border'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $rows = [];
        foreach ($items as $item) {
            $rows[] = self::rowHtml($locale, $item, $withTraffic);
        }

        return '<div style="margin:0 0 20px;">'
            . '<p class="em-section-title" style="margin:0 0 10px;">' . $title . '</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid ' . $border . ';border-radius:12px;overflow:hidden;">'
            . implode('', $rows)
            . '</table>'
            . '</div>';
    }

    /**
     * @param array<string,mixed> $item
     */
    private static function rowHtml(string $locale, array $item, bool $withTraffic): string {
        $otype = (string) ($item['otype'] ?? '');
        $titleRaw = (string) ($item['title'] ?? '');
        $title = htmlspecialchars($titleRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rawStatus = is_string($item['status'] ?? null) ? (string) $item['status'] : null;
        $status = self::statusLabel($locale, $rawStatus);
        $openUrl = self::entityUrl($otype, (int) ($item['oid'] ?? 0));
        $icon = self::iconBubbleForType($otype);
        $border = htmlspecialchars(MailDesignTokens::get('border'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $showStatusPill = in_array(strtolower((string) $rawStatus), ['confirmed', 'cancelled', 'canceled', 'hidden'], true);
        $statusPill = ($status !== '' && $showStatusPill) ? self::statusPillHtml($status, $rawStatus) : '';
        $primaryHref = $openUrl !== '' ? htmlspecialchars($openUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
        $traffic = $withTraffic
            ? self::participationLightsHtml(
                (int) ($item['participation'] ?? -1),
                isset($item['traffic_urls']) && is_array($item['traffic_urls']) ? $item['traffic_urls'] : [],
                array_key_exists('allow_maybe', $item) ? !empty($item['allow_maybe']) : true
            )
            : '';
        if ($withTraffic && ($otype === 'R' || $otype === 'C')) {
            $eventDate = self::eventDateLabel($item);
            $eventTime = self::eventTimeLabel($item);
            $location = isset($item['location']) ? trim((string) $item['location']) : '';
            $eventTypeBadge = self::eventTypeBadgeHtml($locale, $otype);
            $participationLabel = htmlspecialchars(MailI18n::t('js.event.participation', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $eventDateEsc = htmlspecialchars($eventDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $eventTimeEsc = htmlspecialchars($eventTime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $locationEsc = htmlspecialchars($location, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $hasDifferentTitle = trim($titleRaw) !== '' && strtolower(trim($titleRaw)) !== strtolower(trim(self::eventTypeLabel($locale, $otype)));
            $titleLine = '';
            if ($hasDifferentTitle && $eventDate !== $titleRaw) {
                $titleLine = '<div style="margin-top:2px;font-size:14px;line-height:1.35;font-weight:600;color:'
                    . htmlspecialchars(MailDesignTokens::get('textPrimary'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . ';">' . $title . '</div>';
            }
            if ($location === '') {
                $location = MailI18n::t('mail.reminder.metaFallback', $locale);
            }
            $leftMain = '<div style="font-size:16px;line-height:1.3;font-weight:700;color:' . htmlspecialchars(MailDesignTokens::get('textPrimary'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
                . $eventDateEsc . '</div>'
                . $titleLine
                . '<div style="margin-top:8px;font-size:14px;line-height:1.35;color:' . htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
                . self::metaIconClock() . '<span style="vertical-align:middle;margin-left:6px;">' . $eventTimeEsc . '</span>'
                . '</div>'
                . '<div style="margin-top:4px;font-size:14px;line-height:1.35;color:' . htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
                . self::metaIconMapPin() . '<span style="vertical-align:middle;margin-left:6px;">' . $locationEsc . '</span>'
                . '</div>';
            if ($primaryHref !== '') {
                $leftMain = '<a href="' . $primaryHref . '" style="text-decoration:none;color:inherit;">' . $leftMain . '</a>';
            }
            $rightMain = '<div style="text-align:right;font-size:14px;line-height:1.3;font-weight:600;color:' . htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';margin-bottom:8px;">'
                . $participationLabel . '</div>'
                . $traffic;
            return '<tr>'
                . '<td style="padding:14px 12px;border-bottom:1px solid ' . $border . ';">'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td width="42" valign="top" style="padding-right:10px;">' . $icon . '</td>'
                . '<td valign="top" style="padding-right:8px;">'
                . '<div style="margin-bottom:6px;">' . $eventTypeBadge . $statusPill . '</div>'
                . $leftMain
                . '</td>'
                . '<td valign="top" width="184">' . $rightMain . '</td>'
                . '</tr></table>'
                . '</td>'
                . '</tr>';
        }
        $meta = self::metaLine($locale, $item);
        $metaEsc = htmlspecialchars($meta, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $linkStart = $primaryHref !== '' ? '<a class="em-link" href="' . $primaryHref . '" style="text-decoration:none;color:inherit;">' : '';
        $linkEnd = $primaryHref !== '' ? '</a>' : '';
        return '<tr>'
            . '<td style="padding:12px;border-bottom:1px solid ' . $border . ';">'
            . $linkStart
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td width="42" valign="top" style="padding-right:10px;">' . $icon . '</td>'
            . '<td valign="top">'
            . '<div style="font-size:16px;line-height:1.3;font-weight:600;">' . $title . ' ' . $statusPill . '</div>'
            . '<div style="font-size:13px;color:' . htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';margin-top:4px;">' . $metaEsc . '</div>'
            . ($traffic !== '' ? '<div style="margin-top:8px;">' . $traffic . '</div>' : '')
            . '</td>'
            . '</tr></table>'
            . $linkEnd
            . '</td>'
            . '</tr>';
    }

    /**
     * @param array<string,mixed> $item
     */
    private static function eventDateLabel(array $item): string {
        $raw = isset($item['eventBegin']) ? (string) $item['eventBegin'] : '';
        if ($raw === '' && isset($item['replyUntil'])) {
            $raw = (string) $item['replyUntil'];
        }
        if ($raw !== '') {
            try {
                return (new DateTimeImmutable($raw))->format('d.m.Y');
            } catch (Throwable $e) {
                // Fall back to title if parsing fails.
            }
        }
        return (string) ($item['title'] ?? '');
    }

    /**
     * @param array<string,mixed> $item
     */
    private static function eventTimeLabel(array $item): string {
        $raw = isset($item['eventBegin']) ? (string) $item['eventBegin'] : '';
        if ($raw === '' && isset($item['replyUntil'])) {
            $raw = (string) $item['replyUntil'];
        }
        if ($raw !== '') {
            try {
                return (new DateTimeImmutable($raw))->format('H:i');
            } catch (Throwable $e) {
                // Continue to fallback.
            }
        }
        return '—';
    }

    private static function eventTypeLabel(string $locale, string $otype): string {
        if ($otype === 'R') {
            return MailI18n::t('js.event.rehearsal', $locale);
        }
        if ($otype === 'C') {
            return MailI18n::t('js.event.performance', $locale);
        }
        return '';
    }

    private static function eventTypeBadgeHtml(string $locale, string $otype): string {
        $label = self::eventTypeLabel($locale, $otype);
        if ($label === '') {
            return '';
        }
        $entityKey = $otype === 'C' ? 'concert' : 'rehearsal';
        $acc = MailEntityColors::commentDiscussionCardAccents($entityKey);
        $badgeBg = (string) ($acc['badge_bg'] ?? MailEntityColors::mixWithWhite(MailEntityColors::solidHex($entityKey), 0.12));
        $badgeBorder = (string) ($acc['badge_border'] ?? MailEntityColors::mixWithWhite(MailEntityColors::solidHex($entityKey), 0.24));
        $badgeText = (string) ($acc['badge_text'] ?? MailEntityColors::solidHex($entityKey));
        return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:8px;border:1px solid '
            . htmlspecialchars($badgeBorder, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';background-color:' . htmlspecialchars($badgeBg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';font-size:12px;font-weight:500;color:' . htmlspecialchars($badgeText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
            . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</span>';
    }

    private static function metaIconClock(): string {
        $stroke = htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" aria-hidden="true" style="vertical-align:middle;">'
            . '<circle cx="12" cy="12" r="9" stroke-width="2"/>'
            . '<path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>'
            . '</svg>';
    }

    private static function metaIconMapPin(): string {
        $stroke = htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" aria-hidden="true" style="vertical-align:middle;">'
            . '<path d="M12 21s7-4.5 7-10a7 7 0 10-14 0c0 5.5 7 10 7 10z" stroke-width="2"/>'
            . '<circle cx="12" cy="11" r="2.5" stroke-width="2"/>'
            . '</svg>';
    }

    /**
     * @param array<string,mixed> $item
     */
    private static function metaLine(string $locale, array $item): string {
        $parts = [];
        $replyUntil = isset($item['replyUntil']) ? (string) $item['replyUntil'] : '';
        if ($replyUntil !== '') {
            $parts[] = self::fmtDateTime($replyUntil);
        } elseif (!empty($item['dueDateFormatted'])) {
            $parts[] = (string) $item['dueDateFormatted'];
        }
        if (!empty($item['location'])) {
            $parts[] = (string) $item['location'];
        }
        if (count($parts) === 0) {
            return MailI18n::t('mail.reminder.metaFallback', $locale);
        }
        return implode(' · ', $parts);
    }

    private static function fmtDateTime(string $dbDate): string {
        try {
            $dt = new DateTimeImmutable($dbDate);
            return $dt->format('d.m.Y H:i');
        } catch (Throwable $e) {
            return $dbDate;
        }
    }

    private static function entityUrl(string $otype, int $oid): string {
        if ($oid < 1) {
            return '';
        }
        $base = MailEnv::nextgenPublicBaseUrl();
        if ($base === '') {
            return '';
        }
        $type = match ($otype) {
            'R' => 'rehearsal',
            'C' => 'concert',
            'V' => 'vote',
            'T' => 'task',
            default => '',
        };
        if ($type === '') {
            return '';
        }
        return $base . '/entity?' . http_build_query(['type' => $type, 'id' => (string) $oid]);
    }

    private static function statusLabel(string $locale, $status): string {
        if (!is_string($status) || trim($status) === '') {
            return '';
        }
        $key = 'js.event.status.' . strtolower(trim($status));
        $label = MailI18n::t($key, $locale);
        if ($label === $key) {
            return ucfirst(trim($status));
        }
        return $label;
    }

    private static function statusPillHtml(string $label, ?string $rawStatus): string {
        $txt = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        [$bg, $border, $text] = self::statusPillColors($rawStatus);
        return '<span style="display:inline-block;margin-left:6px;padding:2px 10px;border-radius:8px;border:1px solid '
            . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';background-color:' . htmlspecialchars($bg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';font-size:12px;font-weight:600;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">'
            . $txt . '</span>';
    }

    private static function iconBubbleForType(string $otype): string {
        $entityKey = match ($otype) {
            'R' => 'rehearsal',
            'C' => 'concert',
            'V' => 'vote',
            'T' => 'task',
            default => 'rehearsal',
        };
        $acc = MailEntityColors::commentDiscussionCardAccents($entityKey);
        $iconInner = MailEntityIcons::inlineSvgForEntityKey($entityKey);
        return '<div style="width:36px;height:36px;border-radius:11px;background:'
            . htmlspecialchars((string) ($acc['icon_bg'] ?? MailDesignTokens::get('primary')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ';color:#ffffff;display:flex;align-items:center;justify-content:center;line-height:1;">'
            . $iconInner
            . '</div>';
    }

    /**
     * @param array<string,string> $urls
     */
    private static function participationLightsHtml(int $participation, array $urls, bool $allowMaybe): string {
        $isPending = $participation < 0;
        $states = ['yes' => $participation === 1, 'maybe' => $participation === 2, 'no' => $participation === 0];
        $specs = self::trafficSpecs();
        $size = self::trafficButtonSizePx();
        $iconSize = max(18, (int) round($size * 0.45));

        $cells = [];
        $choices = $allowMaybe ? ['yes', 'maybe', 'no'] : ['yes', 'no'];
        foreach ($choices as $choice) {
            $active = !$isPending && $states[$choice];
            $sp = $specs[$choice];
            $bg = $active ? $sp['strong'] : $sp['bgMuted'];
            $border = $active ? $sp['strong'] : $sp['borderMuted'];
            $stroke = $active ? '#ffffff' : $sp['strong'];
            $dot = '<div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:12px;border:1px solid ' . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';'
                . 'background:' . htmlspecialchars($bg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';display:flex;align-items:center;justify-content:center;">'
                . self::trafficIcon($choice, $stroke, $iconSize)
                . '</div>';
            $href = isset($urls[$choice]) ? trim((string) $urls[$choice]) : '';
            if ($href !== '') {
                $dot = '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" '
                    . 'style="display:inline-block;text-decoration:none;line-height:0;" '
                    . 'aria-label="' . htmlspecialchars($choice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
                    . $dot
                    . '</a>';
            }
            $cells[] = '<td style="padding-right:10px;">'
                . $dot
                . '</td>';
        }
        return '<table role="presentation" cellpadding="0" cellspacing="0"><tr>' . implode('', $cells) . '</tr></table>';
    }

    /**
     * @return array<string,array{strong:string,bgMuted:string,borderMuted:string}>
     */
    private static function trafficSpecs(): array {
        return [
            'yes' => [
                'strong' => MailDesignTokens::get('participationSuccess'),
                'bgMuted' => MailDesignTokens::get('participationSuccessBgMuted'),
                'borderMuted' => MailDesignTokens::get('participationSuccessBorderMuted'),
            ],
            'maybe' => [
                'strong' => MailDesignTokens::get('participationWarning'),
                'bgMuted' => MailDesignTokens::get('participationWarningBgMuted'),
                'borderMuted' => MailDesignTokens::get('participationWarningBorderMuted'),
            ],
            'no' => [
                'strong' => MailDesignTokens::get('participationDestructive'),
                'bgMuted' => MailDesignTokens::get('participationDestructiveBgMuted'),
                'borderMuted' => MailDesignTokens::get('participationDestructiveBorderMuted'),
            ],
        ];
    }

    private static function trafficIcon(string $choice, string $stroke, int $sizePx): string {
        $inner = match ($choice) {
            'yes' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'maybe' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'no' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
            default => '',
        };
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $sizePx . '" height="' . $sizePx . '" viewBox="0 0 24 24" fill="none" stroke="'
            . htmlspecialchars($stroke, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" aria-hidden="true">' . $inner . '</svg>';
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private static function statusPillColors(?string $rawStatus): array {
        $v = strtolower(trim((string) $rawStatus));
        $statusHex = MailEntityColors::statusHex($v);
        return [
            MailEntityColors::mixWithWhite($statusHex, 0.12),
            MailEntityColors::mixWithWhite($statusHex, 0.24),
            $statusHex,
        ];
    }

    private static function trafficButtonSizePx(): int {
        $s = (int) MailDesignTokens::get('participationBtnSizePx', '48');
        if ($s < 40) {
            $s = 40;
        }
        if ($s > 56) {
            $s = 56;
        }
        return $s;
    }

    /**
     * @param list<array<string,mixed>> $eventsUpcoming
     * @param list<array<string,mixed>> $eventsPendingResponse
     * @param list<array<string,mixed>> $votes
     * @param list<array<string,mixed>> $tasks
     */
    private static function buildPlain(
        string $locale,
        ?string $recipientFirstName,
        array $eventsUpcoming,
        array $eventsPendingResponse,
        array $votes,
        array $tasks,
        string $introKey = 'mail.reminder.intro'
    ): string {
        $plain = '';
        if ($recipientFirstName !== null && trim($recipientFirstName) !== '') {
            $plain .= MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.greetingHi', $locale), [
                'firstName' => trim($recipientFirstName),
            ]) . "\n\n";
        }
        $plain .= MailI18n::t($introKey, $locale) . "\n\n";
        $plain .= self::plainSection($locale, MailI18n::t('mail.reminder.sectionEventsUpcoming', $locale), $eventsUpcoming);
        $plain .= self::plainSection($locale, MailI18n::t('mail.reminder.sectionEventsPendingResponse', $locale), $eventsPendingResponse);
        $plain .= self::plainSection($locale, MailI18n::t('mail.reminder.sectionVotes', $locale), $votes);
        $plain .= self::plainSection($locale, MailI18n::t('mail.reminder.sectionTasks', $locale), $tasks);
        return MailBodyText::appendInstanceLink($plain, $locale);
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    private static function plainSection(string $locale, string $title, array $items): string {
        if (count($items) === 0) {
            return '';
        }
        $out = $title . "\n";
        foreach ($items as $item) {
            $meta = self::metaLine($locale, $item);
            $out .= '- ' . (string) ($item['title'] ?? '') . ' (' . $meta . ")\n";
        }
        return $out . "\n";
    }
}
