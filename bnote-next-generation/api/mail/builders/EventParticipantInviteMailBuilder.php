<?php
/**
 * Event invite mail: entity card + optional magic-link participation (yes/maybe/no).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailEnv.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailDesignTokens.php';
require_once dirname(__DIR__) . '/MailEntityCard.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';
require_once dirname(__DIR__) . '/CommentDiscussionEntityUrl.php';
require_once dirname(__DIR__) . '/../nextgen_participation_token.php';

final class EventParticipantInviteMailBuilder {
    /**
     * @param array<string,mixed>|null $entityCard same shape as CommentDiscussionEntitySummary::load
     * @param string|null $magicLinkExpiresDisplay Localized “valid until” line for mail (same moment as token TTL).
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build(
        $system_data,
        string $locale,
        string $otype,
        int $oid,
        string $entityTitle,
        ?array $entityCard,
        ?string $recipientFirstName,
        string $plainToken,
        bool $allowMaybe,
        ?string $magicLinkExpiresDisplay,
        array $to,
        array $bcc
    ): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $hasMagic = $plainToken !== '';

        $border = MailDesignTokens::get('border');
        $cardBg = MailDesignTokens::get('cardBg');
        $textMuted = MailDesignTokens::get('textMuted');
        $text = MailDesignTokens::get('text');
        $fsSmall = MailDesignTokens::get('fontSizeSmall');
        $fsBody = MailDesignTokens::get('fontSizeBody');

        $greetingHtml = '';
        if ($recipientFirstName !== null && trim($recipientFirstName) !== '') {
            $fnEsc = htmlspecialchars(trim($recipientFirstName), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $greetingHtml = '<p class="em-lead" style="margin:0 0 10px;">'
                . MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.greetingHi', $locale), [
                    'firstName' => $fnEsc,
                ]) . '</p>';
        }

        $entityEsc = htmlspecialchars($entityTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $intro = '<p class="em-lead" style="margin:0 0 16px;">'
            . MailI18n::interpolate(MailI18n::t('mail.eventInvite.intro', $locale), [
                'entityTitle' => $entityEsc,
            ]) . '</p>';

        $openUrl = CommentDiscussionEntityUrl::openEntityUrl(strtoupper($otype), $oid);
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

        $participationBlock = self::participationBlockHtml(
            $locale,
            $plainToken,
            $allowMaybe,
            $hasMagic,
            $hasMagic ? $magicLinkExpiresDisplay : null
        );

        $inner = $greetingHtml . $intro . $headerHtml . $participationBlock;

        $headline = htmlspecialchars(MailI18n::t('mail.shell.headlineEventInvite', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $ctaHref = $openUrl !== '' ? htmlspecialchars($openUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $ctaLabel = $openUrl !== ''
            ? htmlspecialchars(MailI18n::t('mail.eventInvite.ctaOpenEvent', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
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

        $orgPrefix = MailSubject::orgPrefix($company);
        $subject = MailI18n::interpolate(MailI18n::t('mail.eventInvite.subject', $locale), [
            'orgPrefix' => $orgPrefix,
            'entityTitle' => $entityTitle,
        ]);

        $plain = self::buildPlain(
            $locale,
            $recipientFirstName,
            $entityTitle,
            $entityCard,
            $plainToken,
            $allowMaybe,
            $hasMagic,
            strtoupper($otype),
            $oid,
            $openUrl,
            $hasMagic ? $magicLinkExpiresDisplay : null
        );

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            'event_participant_invite',
            MailAssets::defaultLogoEmbeds()
        );
    }

    private static function participationBlockHtml(
        string $locale,
        string $plainToken,
        bool $allowMaybe,
        bool $hasMagic,
        ?string $magicExpiresDisplay
    ): string {
        $mutedEsc = htmlspecialchars(MailDesignTokens::get('textMuted'), ENT_QUOTES, 'UTF-8');
        $fsSmallEsc = htmlspecialchars(MailDesignTokens::get('fontSizeSmall'), ENT_QUOTES, 'UTF-8');
        $widgetLabel = htmlspecialchars(MailI18n::t('js.event.participation', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $labelRow = '<p style="margin:0 0 8px;font-size:12px;font-weight:500;line-height:1.25;color:' . $mutedEsc . ';">'
            . $widgetLabel . '</p>';

        if (!$hasMagic) {
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;">'
                . '<tr><td align="left" style="padding:0;">' . $labelRow
                . '<p class="em-muted" style="font-size:13px;margin:0;">'
                . htmlspecialchars(MailI18n::t('mail.eventInvite.noMagicLinks', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p></td></tr></table>';
        }

        $specs = [];
        foreach (self::choiceSpecs($allowMaybe) as $spec) {
            $url = self::absoluteParticipationUrl($plainToken, $spec['choice']);
            if ($url === '') {
                $url = MailEnv::nextgenParticipationRespondRelativeUrl($plainToken, $spec['choice']);
            }
            if ($url !== '') {
                $specs[] = array_merge($spec, ['url' => $url]);
            }
        }

        $btnSize = (int) MailDesignTokens::get('participationBtnSizePx', '48');
        if ($btnSize < 40) {
            $btnSize = 48;
        }
        $gapPx = (int) MailDesignTokens::get('participationBtnGapPx', '12');
        if ($gapPx < 0) {
            $gapPx = 12;
        }
        $gapHalf = (string) (int) ($gapPx / 2);

        $cells = [];
        $n = count($specs);
        foreach ($specs as $i => $spec) {
            $href = htmlspecialchars($spec['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $label = MailI18n::t($spec['labelKey'], $locale);
            $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $bg = htmlspecialchars($spec['bg'], ENT_QUOTES, 'UTF-8');
            $bd = htmlspecialchars($spec['border'], ENT_QUOTES, 'UTF-8');
            $sz = (string) $btnSize;
            $iconHtml = self::trafficLightIconSvg($spec['choice'], $spec['stroke']);
            $pl = $i === 0 ? '0' : $gapHalf;
            $pr = $i === $n - 1 ? '0' : $gapHalf;
            $iconWrap = '<table role="presentation" width="' . $sz . '" height="' . $sz . '" cellpadding="0" cellspacing="0" border="0" '
                . 'style="width:' . $sz . 'px;height:' . $sz . 'px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">'
                . '<tr><td align="center" valign="middle" width="' . $sz . '" height="' . $sz . '" '
                . 'style="width:' . $sz . 'px;height:' . $sz . 'px;padding:0;line-height:0;font-size:0;mso-line-height-rule:exactly;">'
                . $iconHtml . '</td></tr></table>';
            $cells[] = '<td style="padding:0 ' . $pr . 'px 0 ' . $pl . 'px;vertical-align:middle;">'
                . '<a href="' . $href . '" title="' . $labelEsc . '" aria-label="' . $labelEsc . '" '
                . 'style="display:inline-block;width:' . $sz . 'px;height:' . $sz . 'px;'
                . 'border-radius:9999px;border-width:1px;border-style:solid;border-color:' . $bd . ';'
                . 'background-color:' . $bg . ';text-decoration:none;vertical-align:middle;box-sizing:border-box;overflow:hidden;">'
                . $iconWrap . '</a></td>';
        }

        if (count($cells) === 0) {
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;">'
                . '<tr><td align="left" style="padding:0;">' . $labelRow
                . '<p class="em-muted" style="font-size:13px;margin:0;">'
                . htmlspecialchars(MailI18n::t('mail.commentDiscussion.noDeepLink', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p></td></tr></table>';
        }

        $lightsInner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">'
            . '<tr>' . implode('', $cells) . '</tr></table>';

        $hintRow = '';
        if ($magicExpiresDisplay !== null && trim($magicExpiresDisplay) !== '') {
            $msg = MailI18n::interpolate(MailI18n::t('mail.eventInvite.magicLinksValidity', $locale), [
                'until' => trim($magicExpiresDisplay),
            ]);
            $msgEsc = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $hintRow = '<tr><td align="left" style="padding:10px 0 0;">'
                . '<p style="margin:0;font-size:' . $fsSmallEsc . ';line-height:1.45;color:' . $mutedEsc . ';">' . $msgEsc . '</p>'
                . '</td></tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 16px;">'
            . '<tr><td align="left" style="padding:0;">' . $labelRow . '</td></tr>'
            . '<tr><td align="left" style="padding:0;">' . $lightsInner . '</td></tr>'
            . $hintRow
            . '</table>';
    }

    /**
     * @return list<array{choice:string,labelKey:string,bg:string,border:string,stroke:string}>
     */
    private static function choiceSpecs(bool $allowMaybe): array {
        $out = [
            [
                'choice' => 'yes',
                'labelKey' => 'mail.eventInvite.ctaYes',
                'bg' => MailDesignTokens::get('participationSuccessBgMuted'),
                'border' => MailDesignTokens::get('participationSuccessBorderMuted'),
                'stroke' => MailDesignTokens::get('participationSuccess'),
            ],
        ];
        if ($allowMaybe) {
            $out[] = [
                'choice' => 'maybe',
                'labelKey' => 'mail.eventInvite.ctaMaybe',
                'bg' => MailDesignTokens::get('participationWarningBgMuted'),
                'border' => MailDesignTokens::get('participationWarningBorderMuted'),
                'stroke' => MailDesignTokens::get('participationWarning'),
            ];
        }
        $out[] = [
            'choice' => 'no',
            'labelKey' => 'mail.eventInvite.ctaNo',
            'bg' => MailDesignTokens::get('participationDestructiveBgMuted'),
            'border' => MailDesignTokens::get('participationDestructiveBorderMuted'),
            'stroke' => MailDesignTokens::get('participationDestructive'),
        ];

        return $out;
    }

    private static function absoluteParticipationUrl(string $plainToken, string $choice): string {
        return MailEnv::nextgenParticipationRespondAbsoluteUrl($plainToken, $choice);
    }

    /**
     * Heroicons-style paths (same as ParticipationWidget / ParticipationTrafficLight).
     */
    private static function trafficLightIconSvg(string $choice, string $strokeHex): string {
        $w = htmlspecialchars($strokeHex, ENT_QUOTES, 'UTF-8');
        $inner = match ($choice) {
            'yes' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'maybe' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" '
                . 'd="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'no' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
            default => '',
        };
        if ($inner === '') {
            return '';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" '
            . 'stroke="' . $w . '" role="img" aria-hidden="true" style="display:block;margin:0 auto;">'
            . $inner . '</svg>';
    }

    /**
     * @param array<string,mixed>|null $entityCard
     */
    private static function buildPlain(
        string $locale,
        ?string $recipientFirstName,
        string $entityTitle,
        ?array $entityCard,
        string $plainToken,
        bool $allowMaybe,
        bool $hasMagic,
        string $otype,
        int $oid,
        string $openUrl,
        ?string $magicExpiresDisplay
    ): string {
        $plain = '';
        if ($recipientFirstName !== null && trim($recipientFirstName) !== '') {
            $plain .= MailI18n::interpolate(MailI18n::t('mail.commentDiscussion.greetingHi', $locale), [
                'firstName' => trim($recipientFirstName),
            ]) . "\n\n";
        }
        $plain .= MailI18n::interpolate(MailI18n::t('mail.eventInvite.intro', $locale), [
            'entityTitle' => $entityTitle,
        ]) . "\n\n";
        if ($entityCard !== null) {
            $plain .= ($entityCard['title'] ?? '') . "\n"
                . ($entityCard['meta_line'] ?? '') . "\n"
                . ($entityCard['location_line'] ?? '') . "\n\n";
        }
        if ($hasMagic) {
            $plain .= MailI18n::t('js.event.participation', $locale) . "\n";
            foreach (self::choiceSpecs($allowMaybe) as $spec) {
                $u = MailEnv::nextgenParticipationRespondAbsoluteUrl($plainToken, $spec['choice']);
                if ($u === '') {
                    $u = MailEnv::nextgenParticipationRespondRelativeUrl($plainToken, $spec['choice']);
                }
                $plain .= MailI18n::t($spec['labelKey'], $locale) . ': ' . $u . "\n";
            }
            if ($magicExpiresDisplay !== null && trim($magicExpiresDisplay) !== '') {
                $plain .= MailI18n::interpolate(MailI18n::t('mail.eventInvite.magicLinksValidityPlain', $locale), [
                    'until' => trim($magicExpiresDisplay),
                ]) . "\n";
            }
            $plain .= "\n";
        } else {
            $plain .= MailI18n::t('mail.eventInvite.noMagicLinks', $locale) . "\n\n";
        }
        if ($openUrl !== '') {
            $plain .= MailI18n::t('mail.eventInvite.ctaOpenEvent', $locale) . ': ' . $openUrl . "\n";
        }

        return MailBodyText::appendInstanceLink($plain, $locale);
    }

    /**
     * @param array{
     *   otype:string,
     *   oid:int,
     *   entityTitle:string,
     *   entityCard?:array|null,
     *   recipientFirstName?:string|null,
     *   plainToken:string,
     *   allowMaybe:bool,
     *   magicLinkExpiresDisplay?:string|null
     * } $ctx
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function buildForPreview($system_data, string $locale, array $ctx, array $to, array $bcc): NextGenMailMessage {
        $entityCard = $ctx['entityCard'] ?? null;
        if (!is_array($entityCard)) {
            $entityCard = null;
        }

        $magic = isset($ctx['magicLinkExpiresDisplay']) ? trim((string) $ctx['magicLinkExpiresDisplay']) : '';
        if ($magic === '') {
            $magic = NextGenParticipationToken::formatApproxExpiryForMail($locale, NextGenParticipationToken::defaultMaxTtlSeconds());
        }

        return self::build(
            $system_data,
            $locale,
            (string) ($ctx['otype'] ?? 'R'),
            (int) ($ctx['oid'] ?? 1),
            (string) ($ctx['entityTitle'] ?? ''),
            $entityCard,
            isset($ctx['recipientFirstName']) ? (string) $ctx['recipientFirstName'] : null,
            (string) ($ctx['plainToken'] ?? 'a' . str_repeat('0', 63)),
            !empty($ctx['allowMaybe']),
            $magic,
            $to,
            $bcc
        );
    }
}
