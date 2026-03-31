<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/MailI18n.php';
require_once dirname(__DIR__) . '/MailHtmlShell.php';
require_once dirname(__DIR__) . '/MailBodyText.php';
require_once dirname(__DIR__) . '/MailBranding.php';
require_once dirname(__DIR__) . '/MailSubject.php';
require_once dirname(__DIR__) . '/MailAssets.php';
require_once dirname(__DIR__) . '/MailEntityCard.php';
require_once dirname(__DIR__) . '/MailDesignTokens.php';
require_once dirname(__DIR__) . '/NextGenMailMessage.php';

final class EventInfoMailBuilder {
    /**
     * @param array{
     *   subject:string,
     *   eventTitle:string,
     *   eventTypeLabel:string,
     *   eventDateLine:string,
     *   eventLocationName:string,
     *   eventAddressLine:string,
     *   eventLink:string,
     *   detailLines:list<array{label:string,value:string,href?:string}>,
     *   entityCard?:array<string,mixed>|null,
     *   customBody:string,
     *   senderName:string
     * } $ctx
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function build($system_data, string $locale, array $ctx, array $to, array $bcc): NextGenMailMessage {
        $company = method_exists($system_data, 'getCompany') ? (string) $system_data->getCompany() : '';
        $headline = htmlspecialchars(MailI18n::t('mail.shell.headlineEventInfo', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $companyLine = MailBranding::bnoteBandLine($locale, $company);
        $footer = MailI18n::interpolate(MailI18n::t('mail.footer.generic', $locale), [
            'sender' => MailBranding::bnoteBandLine($locale, $company),
        ]);

        $custom = self::renderBody($ctx['customBody'] ?? '');
        $eventTitleEsc = htmlspecialchars((string) ($ctx['eventTitle'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eventLinkRaw = trim((string) ($ctx['eventLink'] ?? ''));
        $eventLinkEsc = htmlspecialchars($eventLinkRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $detailsRows = '';
        $detailLines = $ctx['detailLines'] ?? [];
        if (is_array($detailLines) && count($detailLines) > 0) {
            foreach ($detailLines as $row) {
                $label = htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $valueText = (string) ($row['value'] ?? '');
                $value = htmlspecialchars($valueText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if ($label === '' || $value === '') {
                    continue;
                }
                $hrefRaw = trim((string) ($row['href'] ?? ''));
                if ($hrefRaw !== '') {
                    $href = htmlspecialchars($hrefRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $value = '<a class="em-link" href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $value . '</a>';
                }
                $detailsRows .= '<tr><td style="padding:0 0 8px;vertical-align:top;width:34%;font-weight:600;">' . $label . '</td>'
                    . '<td style="padding:0 0 8px;vertical-align:top;">' . $value . '</td></tr>';
            }
        }

        $cardBg = MailDesignTokens::get('cardBg');
        $border = MailDesignTokens::get('border');
        $text = MailDesignTokens::get('text');
        $textMuted = MailDesignTokens::get('textMuted');
        $fsSmall = MailDesignTokens::get('fontSizeSmall');
        $fsBody = MailDesignTokens::get('fontSizeBody');
        $entityCard = (isset($ctx['entityCard']) && is_array($ctx['entityCard'])) ? $ctx['entityCard'] : null;
        $eventCard = '';
        if ($entityCard !== null) {
            $eventCard = MailEntityCard::entityHeaderHtml(
                $entityCard,
                $cardBg,
                $border,
                $text,
                $textMuted,
                $fsSmall,
                $fsBody,
                $eventLinkRaw !== '' ? $eventLinkRaw : null,
                $eventLinkRaw !== '' ? MailI18n::interpolate(MailI18n::t('mail.entityCard.linkAria', $locale), [
                    'title' => trim((string) ($entityCard['title'] ?? $ctx['eventTitle'] ?? '')),
                ]) : null
            );
        } else {
            $eventCard = '<p class="em-lead" style="margin:0 0 12px;font-weight:700;">' . $eventTitleEsc . '</p>';
        }

        $detailsBlock = '';
        if ($detailsRows !== '') {
            $detailsTitle = htmlspecialchars(MailI18n::t('mail.eventInfo.detailsTitle', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $detailsBlock = '<p class="em-section-title" style="margin:0 0 10px;">' . $detailsTitle . '</p>'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">'
                . $detailsRows
                . '</table>';
        }

        $inner = $eventCard
            . $detailsBlock
            . '<div style="margin:0 0 10px;">' . $custom['html'] . '</div>';

        $ctaHref = $eventLinkRaw !== '' ? $eventLinkEsc : null;
        $ctaLabel = $eventLinkRaw !== '' ? htmlspecialchars(MailI18n::t('mail.eventInfo.ctaOpen', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;

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

        $subjectRaw = trim((string) ($ctx['subject'] ?? ''));
        $subject = $subjectRaw !== ''
            ? $subjectRaw
            : MailI18n::interpolate(MailI18n::t('mail.eventInfo.subjectFallback', $locale), [
                'orgPrefix' => MailSubject::orgPrefix($company),
                'eventTitle' => (string) ($ctx['eventTitle'] ?? ''),
            ]);

        $plain = self::plainText($ctx, $custom['text'], $locale);

        return new NextGenMailMessage(
            $to,
            $bcc,
            $subject,
            $html,
            $plain,
            'event_info',
            MailAssets::defaultLogoEmbeds()
        );
    }

    /**
     * @param array{
     *   subject:string,
     *   eventTitle:string,
     *   eventTypeLabel:string,
     *   eventDateLine:string,
     *   eventLocationName:string,
     *   eventAddressLine:string,
     *   eventLink:string,
     *   detailLines:list<array{label:string,value:string,href?:string}>,
     *   entityCard?:array<string,mixed>|null,
     *   customBody:string,
     *   senderName:string
     * } $ctx
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public static function buildForPreview($system_data, string $locale, array $ctx, array $to, array $bcc): NextGenMailMessage {
        return self::build($system_data, $locale, $ctx, $to, $bcc);
    }

    /**
     * @return array{html:string,text:string}
     */
    private static function renderBody(string $value): array {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return ['html' => '', 'text' => ''];
        }
        if ($trimmed[0] !== '{') {
            $text = $trimmed;
            return [
                'html' => '<p style="margin:0;">' . nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) . '</p>',
                'text' => $text,
            ];
        }

        $parsed = json_decode($trimmed, true);
        if (!is_array($parsed) || !isset($parsed['blocks']) || !is_array($parsed['blocks'])) {
            return [
                'html' => '<p style="margin:0;">' . nl2br(htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) . '</p>',
                'text' => $trimmed,
            ];
        }

        $htmlParts = [];
        $textParts = [];
        foreach ($parsed['blocks'] as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            if ($type === 'header') {
                $level = max(2, min(4, (int) ($data['level'] ?? 2)));
                $txt = self::sanitizeInline((string) ($data['text'] ?? ''));
                if ($txt === '') {
                    continue;
                }
                $htmlParts[] = '<h' . $level . ' style="margin:18px 0 8px;font-size:18px;line-height:1.3;">' . $txt . '</h' . $level . '>';
                $textParts[] = self::plainInline((string) ($data['text'] ?? ''));
                continue;
            }
            if ($type === 'list') {
                $items = is_array($data['items'] ?? null) ? $data['items'] : [];
                if (count($items) < 1) {
                    continue;
                }
                $tag = (($data['style'] ?? 'unordered') === 'ordered') ? 'ol' : 'ul';
                $listItemsHtml = '';
                $listItemsText = [];
                foreach ($items as $idx => $item) {
                    $itemRaw = is_string($item) ? $item : '';
                    $itemHtml = self::sanitizeInline($itemRaw);
                    if ($itemHtml === '') {
                        continue;
                    }
                    $listItemsHtml .= '<li style="margin:0 0 6px;">' . $itemHtml . '</li>';
                    $prefix = $tag === 'ol' ? ($idx + 1) . '.' : '-';
                    $listItemsText[] = $prefix . ' ' . self::plainInline($itemRaw);
                }
                if ($listItemsHtml === '') {
                    continue;
                }
                $htmlParts[] = '<' . $tag . ' style="margin:0 0 12px 18px;padding:0;">' . $listItemsHtml . '</' . $tag . '>';
                $textParts[] = implode("\n", $listItemsText);
                continue;
            }
            if ($type === 'quote') {
                $txtRaw = (string) ($data['text'] ?? '');
                $txt = self::sanitizeInline($txtRaw);
                if ($txt === '') {
                    continue;
                }
                $htmlParts[] = '<blockquote style="margin:0 0 12px;padding:8px 12px;border-left:3px solid #d1d5db;color:#374151;">' . $txt . '</blockquote>';
                $textParts[] = '> ' . self::plainInline($txtRaw);
                continue;
            }
            $txtRaw = (string) ($data['text'] ?? '');
            $txt = self::sanitizeInline($txtRaw);
            if ($txt === '') {
                continue;
            }
            $htmlParts[] = '<p style="margin:0 0 12px;">' . $txt . '</p>';
            $textParts[] = self::plainInline($txtRaw);
        }

        if (count($htmlParts) < 1) {
            return ['html' => '', 'text' => ''];
        }

        return ['html' => implode('', $htmlParts), 'text' => implode("\n\n", $textParts)];
    }

    private static function sanitizeInline(string $text): string {
        $stripped = trim(strip_tags($text));
        if ($stripped === '') {
            return '';
        }
        return nl2br(htmlspecialchars($stripped, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
    }

    private static function plainInline(string $text): string {
        return trim(strip_tags($text));
    }

    /**
     * @param array<string,mixed> $ctx
     */
    private static function plainText(array $ctx, string $customText, string $locale): string {
        $lines = [];
        $eventType = trim((string) ($ctx['eventTypeLabel'] ?? ''));
        $eventTitle = trim((string) ($ctx['eventTitle'] ?? ''));
        if ($eventType !== '' || $eventTitle !== '') {
            $lines[] = trim($eventType . ': ' . $eventTitle, ': ');
        }
        $dateLine = trim((string) ($ctx['eventDateLine'] ?? ''));
        if ($dateLine !== '') {
            $lines[] = $dateLine;
        }
        $location = trim((string) ($ctx['eventLocationName'] ?? ''));
        if ($location !== '') {
            $lines[] = $location;
        }
        $address = trim((string) ($ctx['eventAddressLine'] ?? ''));
        if ($address !== '') {
            $lines[] = $address;
        }
        $detailLines = $ctx['detailLines'] ?? [];
        if (is_array($detailLines) && count($detailLines) > 0) {
            foreach ($detailLines as $row) {
                $label = trim((string) ($row['label'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }
                $lines[] = $label . ': ' . $value;
            }
            $lines[] = '';
        }
        if ($customText !== '') {
            $lines[] = $customText;
            $lines[] = '';
        }
        $eventLink = trim((string) ($ctx['eventLink'] ?? ''));
        if ($eventLink !== '') {
            $lines[] = MailI18n::t('mail.eventInfo.openEvent', $locale) . ': ' . $eventLink;
            $lines[] = '';
        }
        return MailBodyText::appendInstanceLink(implode("\n", $lines), $locale);
    }
}
