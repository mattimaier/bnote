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
     *   senderName:string,
     *   senderEmail?:string
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
        $senderName = trim((string) ($ctx['senderName'] ?? ''));
        if ($senderName !== '') {
            $senderLine = MailI18n::interpolate(MailI18n::t('mail.footer.sentBy', $locale), [
                'name' => htmlspecialchars($senderName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ]);
            $footer .= '<br>' . $senderLine;
        }

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
    public static function renderEditorBody(string $value): array {
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
                $level = max(1, min(6, (int) ($data['level'] ?? 2)));
                $txt = self::sanitizeInline((string) ($data['text'] ?? ''));
                if ($txt === '') {
                    continue;
                }
                $fontSize = self::headerFontSizeEm($level);
                $htmlParts[] = '<h' . $level . ' style="margin:0.83em 0;font-size:' . $fontSize . 'em;line-height:1.25;font-weight:500;">' . $txt . '</h' . $level . '>';
                $textParts[] = self::plainInline((string) ($data['text'] ?? ''));
                continue;
            }
            if ($type === 'list') {
                $items = is_array($data['items'] ?? null) ? $data['items'] : [];
                if (count($items) < 1) {
                    continue;
                }
                $tag = (($data['style'] ?? 'unordered') === 'ordered') ? 'ol' : 'ul';
                $renderedList = self::renderEditorList($items, $tag, 0);
                if ($renderedList['html'] === '') {
                    continue;
                }
                $htmlParts[] = $renderedList['html'];
                $textParts[] = implode("\n", $renderedList['textLines']);
                continue;
            }
            if ($type === 'code') {
                $codeRaw = (string) ($data['code'] ?? '');
                $codeEsc = htmlspecialchars($codeRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if (trim($codeEsc) === '') {
                    continue;
                }
                $htmlParts[] = '<pre style="margin:0 0 1em;padding:10px 12px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;overflow:auto;"><code style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,monospace;font-size:13px;line-height:1.45;color:#111827;">' . $codeEsc . '</code></pre>';
                $textParts[] = $codeRaw;
                continue;
            }
            if ($type === 'quote') {
                $txtRaw = (string) ($data['text'] ?? '');
                $txt = self::sanitizeInline($txtRaw);
                if ($txt === '') {
                    continue;
                }
                $htmlParts[] = '<blockquote style="margin:0 0 1em;padding:8px 12px;border-left:3px solid #d1d5db;color:#374151;font-style:italic;">' . $txt . '</blockquote>';
                $textParts[] = '> ' . self::plainInline($txtRaw);
                continue;
            }
            $txtRaw = (string) ($data['text'] ?? '');
            $txt = self::sanitizeInline($txtRaw);
            if ($txt === '') {
                continue;
            }
            $htmlParts[] = '<p style="margin:0 0 1em;font-size:1em;line-height:1.5;font-weight:400;">' . $txt . '</p>';
            $textParts[] = self::plainInline($txtRaw);
        }

        if (count($htmlParts) < 1) {
            return ['html' => '', 'text' => ''];
        }

        return ['html' => implode('', $htmlParts), 'text' => implode("\n\n", $textParts)];
    }

    /**
     * @return array{html:string,text:string}
     */
    private static function renderBody(string $value): array {
        return self::renderEditorBody($value);
    }

    private static function sanitizeInline(string $text): string {
        $normalized = str_replace(["\r\n", "\r"], "\n", (string) $text);
        if (trim(strip_tags($normalized)) === '') {
            return '';
        }

        // Keep a safe subset of inline tags emitted by EditorJS.
        $safe = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $normalized) ?? $normalized;
        $safe = strip_tags($safe, '<b><strong><i><em><u><s><mark><code><a><br>');
        $safe = self::sanitizeAnchors($safe);
        $safe = self::stripAttributesFromInlineTags($safe);
        $safe = self::styleInlineTags($safe);

        if (trim(strip_tags($safe)) === '') {
            return '';
        }
        return $safe;
    }

    private static function plainInline(string $text): string {
        $plain = trim(strip_tags((string) $text));
        return html_entity_decode($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function sanitizeAnchors(string $html): string {
        return preg_replace_callback('/<a\b[^>]*>(.*?)<\/a>/is', function (array $matches): string {
            $rawTag = (string) ($matches[0] ?? '');
            $inner = (string) ($matches[1] ?? '');

            $href = '';
            if (preg_match('/href\s*=\s*([\'"])(.*?)\1/i', $rawTag, $hrefMatch)) {
                $href = trim((string) ($hrefMatch[2] ?? ''));
            } elseif (preg_match('/href\s*=\s*([^\s>]+)/i', $rawTag, $hrefMatch)) {
                $href = trim((string) ($hrefMatch[1] ?? ''));
            }

            $decodedHref = html_entity_decode($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $allowed = false;
            if ($decodedHref !== '') {
                $scheme = strtolower((string) parse_url($decodedHref, PHP_URL_SCHEME));
                $allowed = in_array($scheme, ['http', 'https', 'mailto'], true);
            }

            $cleanInner = strip_tags($inner, '<b><strong><i><em><u><s><mark><code><br>');
            if (!$allowed) {
                return $cleanInner;
            }

            $hrefEsc = htmlspecialchars($decodedHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<a class="em-link" href="' . $hrefEsc . '" target="_blank" rel="noopener noreferrer">' . $cleanInner . '</a>';
        }, $html) ?? $html;
    }

    private static function stripAttributesFromInlineTags(string $html): string {
        return preg_replace_callback('/<(\/?)(b|strong|i|em|u|s|mark|code|br)\b[^>]*>/i', function (array $matches): string {
            $closing = (string) ($matches[1] ?? '');
            $tag = strtolower((string) ($matches[2] ?? ''));
            if ($tag === 'br') {
                return '<br>';
            }
            return '<' . $closing . $tag . '>';
        }, $html) ?? $html;
    }

    private static function styleInlineTags(string $html): string {
        $out = preg_replace('/<code>/i', '<code style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,monospace;font-size:0.92em;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:4px;padding:0 4px;">', $html) ?? $html;
        $out = preg_replace('/<mark>/i', '<mark style="background:#fef08a;color:inherit;padding:0 2px;border-radius:2px;">', $out) ?? $out;
        $out = preg_replace('/<strong>/i', '<strong style="font-weight:700;">', $out) ?? $out;
        $out = preg_replace('/<b>/i', '<b style="font-weight:700;">', $out) ?? $out;
        return $out;
    }

    private static function headerFontSizeEm(int $level): float {
        switch ($level) {
            case 1:
                return 2.25;
            case 2:
                return 1.875;
            case 3:
                return 1.5;
            case 4:
                return 1.25;
            case 5:
                return 1.125;
            default:
                return 1.0;
        }
    }

    /**
     * @param list<mixed> $items
     * @return array{html:string,textLines:list<string>}
     */
    private static function renderEditorList(array $items, string $tag, int $depth): array {
        $itemsHtml = '';
        $textLines = [];
        $position = 0;

        foreach ($items as $item) {
            [$contentRaw, $children, $childTag] = self::normalizeEditorListItem($item, $tag);
            $contentHtml = self::sanitizeInline($contentRaw);
            $contentText = self::plainInline($contentRaw);
            $childRendered = count($children) > 0 ? self::renderEditorList($children, $childTag, $depth + 1) : ['html' => '', 'textLines' => []];

            if ($contentHtml === '' && $childRendered['html'] === '') {
                continue;
            }

            $position++;
            $liInner = '';
            if ($contentHtml !== '') {
                $liInner .= $contentHtml;
            }
            if ($childRendered['html'] !== '') {
                $liInner .= $childRendered['html'];
            }
            $itemsHtml .= '<li style="margin:0 0 6px;">' . $liInner . '</li>';

            if ($contentText !== '') {
                $prefix = $tag === 'ol' ? ($position . '.') : '-';
                $textLines[] = str_repeat('  ', $depth) . $prefix . ' ' . $contentText;
            }
            foreach ($childRendered['textLines'] as $line) {
                $textLines[] = $line;
            }
        }

        if ($itemsHtml === '') {
            return ['html' => '', 'textLines' => []];
        }

        $listStyle = $tag === 'ol' ? 'decimal' : 'disc';
        $marginStyle = $depth > 0 ? 'margin:0.35em 0 0 1.5em;padding:0;list-style-type:' . $listStyle . ';' : 'margin:0 0 1em 1.5em;padding:0;list-style-type:' . $listStyle . ';';
        return [
            'html' => '<' . $tag . ' style="' . $marginStyle . '">' . $itemsHtml . '</' . $tag . '>',
            'textLines' => $textLines,
        ];
    }

    /**
     * @param mixed $item
     * @param 'ul'|'ol' $fallbackTag
     * @return array{0:string,1:list<mixed>,2:'ul'|'ol'}
     */
    private static function normalizeEditorListItem($item, string $fallbackTag): array {
        if (is_string($item)) {
            return [$item, [], $fallbackTag === 'ol' ? 'ol' : 'ul'];
        }
        if (!is_array($item)) {
            return ['', [], $fallbackTag === 'ol' ? 'ol' : 'ul'];
        }

        $content = trim((string) ($item['content'] ?? $item['text'] ?? ''));
        $children = is_array($item['items'] ?? null) ? $item['items'] : [];
        $style = strtolower(trim((string) ($item['style'] ?? (($item['meta']['style'] ?? '') ?: ''))));
        $tag = $style === 'ordered' ? 'ol' : ($style === 'unordered' ? 'ul' : ($fallbackTag === 'ol' ? 'ol' : 'ul'));

        return [$content, $children, $tag];
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
