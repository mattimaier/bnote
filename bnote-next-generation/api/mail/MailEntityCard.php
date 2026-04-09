<?php
/**
 * Shared HTML for transactional mail entity header (comment discussion, event invites, etc.).
 */
declare(strict_types=1);

require_once __DIR__ . '/MailEntityColors.php';
require_once __DIR__ . '/MailEntityIcons.php';

final class MailEntityCard {
    /**
     * @param array{
     *   icon_bg:string,
     *   icon_entity_key?:string,
     *   icon_inner_html?:string,
     *   icon_char:string,
     *   title:string,
     *   badge_label:string,
     *   badge_bg:string,
     *   badge_border:string,
     *   badge_text:string,
     *   meta_line:string,
     *   location_line:string
     * } $card
     * @param string|null $openHref If set, the whole card (padding + inner layout) is wrapped in one link.
     * @param string|null $openLinkLabel Plain-text label for title + aria-label on the link (e.g. localized “Open …”).
     */
    public static function entityHeaderHtml(
        array $card,
        string $cardBg,
        string $borderOuter,
        string $text,
        string $textMuted,
        string $fsSmall,
        string $fsBody,
        ?string $openHref = null,
        ?string $openLinkLabel = null
    ): string {
        $defPill = MailEntityColors::commentDiscussionCardAccents('rehearsal');
        $iconBg = htmlspecialchars($card['icon_bg'] ?? $defPill['icon_bg'], ENT_QUOTES, 'UTF-8');
        $iconBgRaw = (string) ($card['icon_bg'] ?? $defPill['icon_bg']);
        $iconFill = htmlspecialchars(MailEntityColors::mixWithWhite($iconBgRaw, 0.20), ENT_QUOTES, 'UTF-8');
        $iconBorder = htmlspecialchars(MailEntityColors::mixWithWhite($iconBgRaw, 0.42), ENT_QUOTES, 'UTF-8');
        $iconChar = htmlspecialchars($card['icon_char'] ?? '♫', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $iconInner = '';
        if (isset($card['icon_inner_html']) && is_string($card['icon_inner_html']) && $card['icon_inner_html'] !== '') {
            $iconInner = $card['icon_inner_html'];
        } else {
            $iconInner = '<span style="font-size:20px;line-height:38px;display:block;text-align:center;">' . $iconChar . '</span>';
        }
        $iconEntityKey = strtolower(trim((string) ($card['icon_entity_key'] ?? '')));
        $iconBadgePng = $iconEntityKey !== '' ? MailEntityIcons::inlineBadgePngForEntityKey($iconEntityKey) : null;
        $title = htmlspecialchars($card['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $badgeLabel = htmlspecialchars($card['badge_label'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $badgeBg = htmlspecialchars($card['badge_bg'] ?? $defPill['badge_bg'], ENT_QUOTES, 'UTF-8');
        $badgeBorder = htmlspecialchars($card['badge_border'] ?? $defPill['badge_border'], ENT_QUOTES, 'UTF-8');
        $badgeText = htmlspecialchars($card['badge_text'] ?? $defPill['badge_text'], ENT_QUOTES, 'UTF-8');
        $meta = htmlspecialchars($card['meta_line'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $loc = htmlspecialchars($card['location_line'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $cardBgEsc = htmlspecialchars($cardBg, ENT_QUOTES, 'UTF-8');
        $borderEsc = htmlspecialchars($borderOuter, ENT_QUOTES, 'UTF-8');
        $textEsc = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $mutedEsc = htmlspecialchars($textMuted, ENT_QUOTES, 'UTF-8');

        $locBlock = '';
        if ($loc !== '') {
            $locBlock = '<p style="margin:4px 0 0;font-size:' . htmlspecialchars($fsSmall, ENT_QUOTES, 'UTF-8')
                . ';line-height:1.45;color:' . $mutedEsc . ';">' . $loc . '</p>';
        }

        $iconCell = $iconBadgePng !== null
            ? '<tr><td align="center" valign="middle" width="40" height="40" '
                . 'style="width:40px;height:40px;mso-line-height-rule:exactly;">'
                . $iconBadgePng . '</td></tr>'
            : '<tr><td align="center" valign="middle" width="40" height="40" '
                . 'style="width:40px;height:40px;border-radius:12px;border:1px solid ' . $iconBorder
                . ';background-color:' . $iconFill . ';color:' . $iconBg . ';mso-line-height-rule:exactly;">'
                . $iconInner . '</td></tr>';

        $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td valign="top" style="width:48px;padding:0 12px 0 0;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">'
            . $iconCell . '</table>'
            . '</td>'
            . '<td valign="top" style="padding:0;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="padding:0 0 4px 0;">'
            . '<span style="font-size:22px;font-weight:700;line-height:1.25;color:' . $textEsc . ';">' . $title . '</span>'
            . ' <span style="display:inline-block;margin-left:6px;vertical-align:middle;padding:2px 8px;border-radius:8px;'
            . 'border:1px solid ' . $badgeBorder . ';background-color:' . $badgeBg . ';color:' . $badgeText . ';'
            . 'font-size:12px;line-height:1.25;font-weight:500;">' . $badgeLabel . '</span>'
            . '</td></tr></table>'
            . '<p style="margin:6px 0 0;font-size:' . htmlspecialchars($fsSmall, ENT_QUOTES, 'UTF-8')
            . ';line-height:1.45;color:' . $mutedEsc . ';">' . $meta . '</p>'
            . $locBlock
            . '</td></tr></table>';

        $padded = $inner;
        $href = $openHref !== null ? trim($openHref) : '';
        if ($href !== '') {
            $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $label = $openLinkLabel !== null ? trim($openLinkLabel) : '';
            $labelEsc = htmlspecialchars($label !== '' ? $label : $href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $padded = '<a href="' . $hrefEsc . '" title="' . $labelEsc . '" aria-label="' . $labelEsc . '" '
                . 'style="display:block;text-decoration:none;color:inherit;outline:none;">' . $inner . '</a>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;border:1px solid '
            . $borderEsc . ';border-radius:16px;background-color:' . $cardBgEsc . ';">'
            . '<tr><td style="padding:16px 18px;">'
            . $padded
            . '</td></tr></table>';
    }
}
