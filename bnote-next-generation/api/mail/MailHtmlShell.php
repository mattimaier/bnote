<?php
/**
 * BNote transactional HTML mail shell (table layout, FlyonUI-aligned tokens).
 * Light + dark (prefers-color-scheme) where clients support it.
 */
declare(strict_types=1);

require_once __DIR__ . '/MailDesignTokens.php';
require_once __DIR__ . '/MailEnv.php';
require_once __DIR__ . '/MailI18n.php';

final class MailHtmlShell {
    /**
     * @param string $headlineHtml Escaped headline (visible title + &lt;title&gt;)
     * @param string $companySublineHtml Escaped organization name; empty to omit
     * @param string $bodyHtml Main content (trusted HTML from builders)
     * @param string $footerContextHtml Context line(s), HTML-safe from i18n
     * @param string $locale For footer strings
     * @param string|null $ctaHref Escaped absolute URL; null skips CTA block
     * @param string|null $ctaLabel Escaped button label
     * @param string $logoImgSrc e.g. cid:bnote-logo or empty to hide logo
     */
    public static function wrapTransactional(
        string $headlineHtml,
        string $companySublineHtml,
        string $bodyHtml,
        string $footerContextHtml,
        string $locale,
        ?string $ctaHref,
        ?string $ctaLabel,
        string $logoImgSrc = ''
    ): string {
        $t = MailDesignTokens::tokens();
        $ff = $t['fontFamily'];
        $fs = $t['fontSizeBody'];
        $lh = $t['lineHeight'];
        $tc = $t['text'];
        $ts = $t['textSecondary'];
        $tm = $t['textMuted'];
        $border = $t['border'];
        $pageBg = $t['pageBg'];
        $cardBg = $t['cardBg'];
        $shadow = $t['shadowCard'];
        $mwc = $t['maxWidthCard'] ?? '680px';
        $rCard = $t['radiusCard'];
        $primary = $t['primary'];
        $onPrimary = $t['primaryContent'];
        $rBtn = $t['radiusButton'];
        $py = $t['buttonPaddingY'];
        $px = $t['buttonPaddingX'];
        $fsSmall = $t['fontSizeSmall'];
        $fsFoot = $t['fontSizeFooter'];
        $lw = $t['logoWidth'];
        $lhLogo = $t['logoHeight'];

        $pageD = $t['pageBgDark'] ?? '#12151c';
        $cardD = $t['cardBgDark'] ?? '#1e232c';
        $textD = $t['textDark'] ?? '#eceff4';
        $tsD = $t['textSecondaryDark'] ?? '#b8c0cc';
        $tmD = $t['textMutedDark'] ?? '#8b95a5';
        $bdD = $t['borderDark'] ?? '#3d4654';
        $shD = $t['shadowCardDark'] ?? '0 1px 3px rgba(0,0,0,0.45)';

        $pageBgEsc = htmlspecialchars($pageBg, ENT_QUOTES, 'UTF-8');
        $styleBlock = '<style type="text/css">'
            . 'html{color-scheme:light dark;}'
            . 'html,body{margin:0!important;padding:0!important;width:100%!important;background-color:' . $pageBg . ' !important;-webkit-text-size-adjust:100%;text-size-adjust:100%;}'
            . '.em-root{font-family:' . $ff . ';font-size:' . $fs . ';line-height:' . $lh . ';color:' . $tc . ';'
            . 'word-wrap:break-word;overflow-wrap:break-word;-webkit-text-size-adjust:100%;text-size-adjust:100%;}'
            . '.em-muted{color:' . $tm . ';}'
            . '.em-foot{font-size:' . $fsFoot . ';color:' . $tm . ';}'
            . '.em-body .em-lead{color:' . $tc . ';}'
            . '.em-body .em-text-secondary{color:' . $ts . ';}'
            . '.em-body .em-section-title{color:' . $tc . ';font-size:17px;font-weight:700;line-height:1.3;}'
            . '.em-body .em-section-p{color:' . $tc . ';}'
            . '.em-body .em-section-p-muted{color:' . $tm . ';font-size:14px;}'
            . '.em-body .em-list{color:' . $tc . ';}'
            . '.em-body .em-list-plain{list-style:none!important;padding-left:0!important;margin:16px 0;color:' . $tc . ';}'
            . '.em-body .em-list-plain li{margin:0 0 8px;}'
            . '.em-hr{border:none;border-top:1px solid ' . $border . ';margin:24px 0;}'
            . '.em-body p a,.em-body p a:link,.em-body p a:visited{color:' . $primary . ' !important;text-decoration:underline !important;font-weight:600 !important;}'
            . 'a.em-link,a.em-link:link,a.em-link:visited{color:' . $primary . ' !important;text-decoration:underline !important;font-weight:600 !important;}'
            . '.em-card{max-width:' . $mwc . ';width:100%;}'
            . '.em-shell-pad{padding:24px 16px !important;}'
            . '.em-head-pad{padding:24px 24px 16px !important;}'
            . '.em-body-pad{padding:24px !important;}'
            . '.em-foot-pad{padding:16px 24px 24px !important;}'
            . '.em-h1{margin:0;font-size:20px;font-weight:700;color:' . $tc . ';line-height:1.25;}'
            . '.em-cta-td{text-align:center !important;vertical-align:middle;}'
            . '.em-cta-td a,.em-cta-td a:link,.em-cta-td a:visited{color:' . $onPrimary . ' !important;text-decoration:none !important;font-weight:600 !important;text-align:center !important;}'
            . '.em-cta-inner{margin:0 auto !important;}'
            . '@media only screen and (max-width:600px){'
            . '.em-shell-pad{padding:16px 12px !important;}'
            . '.em-head-pad{padding:20px 16px 14px !important;}'
            . '.em-body-pad{padding:18px 16px !important;}'
            . '.em-foot-pad{padding:14px 16px 20px !important;}'
            . '.em-h1{font-size:18px !important;line-height:1.3 !important;}'
            . '}'
            . '@media (prefers-color-scheme:dark){'
            . 'html{color-scheme:dark;}'
            . 'html,body{background-color:' . $pageD . ' !important;}'
            . '.em-root{color:' . $textD . ' !important;}'
            . '.em-shell-pad{background-color:' . $pageD . ' !important;}'
            . '.em-card{background-color:' . $cardD . ' !important;background:' . $cardD . ' !important;border-color:' . $bdD . ' !important;box-shadow:' . $shD . ' !important;}'
            . '.em-head-pad{border-bottom-color:' . $bdD . ' !important;}'
            . '.em-body-pad.em-body{color:' . $textD . ' !important;}'
            . '.em-foot-pad{border-top-color:' . $bdD . ' !important;}'
            . '.em-h1{color:' . $textD . ' !important;}'
            . '.em-muted{color:' . $tmD . ' !important;}'
            . '.em-foot{color:' . $tmD . ' !important;}'
            . '.em-body .em-lead,.em-body .em-section-p,.em-body .em-list,.em-body .em-list-plain,.em-body .em-list-plain li{color:' . $textD . ' !important;}'
            . '.em-body .em-text-secondary{color:' . $tsD . ' !important;}'
            . '.em-body .em-section-title{color:' . $textD . ' !important;}'
            . '.em-body .em-section-p-muted{color:' . $tmD . ' !important;}'
            . '.em-hr{border-top-color:' . $bdD . ' !important;}'
            . '}'
            . '</style>';

        $sublineBlock = '';
        if ($companySublineHtml !== '') {
            $sublineBlock = '<p class="em-muted" style="margin:8px 0 0;font-size:' . $fsSmall . ';">'
                . $companySublineHtml . '</p>';
        }

        $logoBlock = '';
        if ($logoImgSrc !== '') {
            $escSrc = htmlspecialchars($logoImgSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $logoBlock = '<img src="' . $escSrc . '" width="' . htmlspecialchars($lw, ENT_QUOTES, 'UTF-8') . '" height="'
                . htmlspecialchars($lhLogo, ENT_QUOTES, 'UTF-8') . '" alt="BNote" style="display:block;border:0;outline:none;max-width:100%;height:auto;">';
        }

        $ctaBlock = '';
        if ($ctaHref !== null && $ctaLabel !== null && $ctaHref !== '' && $ctaLabel !== '') {
            $ctaBlock = '<table role="presentation" class="em-cta" cellspacing="0" cellpadding="0" width="100%" style="margin:24px 0 0;">'
                . '<tr><td align="center" style="padding:0;">'
                . '<table role="presentation" class="em-cta-inner" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">'
                . '<tr><td class="em-cta-td" align="center" valign="middle" style="text-align:center;border-radius:' . $rBtn . ';background-color:' . $primary . ';">'
                . '<a href="' . $ctaHref . '" style="display:inline-block;padding:' . $py . ' ' . $px . ';color:' . $onPrimary
                . ' !important;text-decoration:none !important;font-weight:600;font-size:' . $fs . ';text-align:center;line-height:1.35;">' . $ctaLabel . '</a>'
                . '</td></tr></table></td></tr></table>';
        }

        $base = MailEnv::nextgenPublicBaseUrl();
        $appBlock = '';
        if ($base !== '') {
            $urlEsc = htmlspecialchars($base, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $openLabel = htmlspecialchars(MailI18n::t('mail.footer.openApp', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $appBlock = '<p style="margin:12px 0 0;font-size:' . $fsFoot . ';">'
                . '<a class="em-link" href="' . $urlEsc . '" style="color:' . $primary . ' !important;font-weight:600;text-decoration:underline !important;word-break:break-all;">' . $openLabel . '</a></p>';
        } else {
            $hint = htmlspecialchars(MailI18n::t('mail.footer.openAppUnavailable', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $appBlock = '<p class="em-muted" style="margin:12px 0 0;font-size:' . $fsFoot . ';">' . $hint . '</p>';
        }

        $footerInner = '<div class="em-foot" style="font-size:' . $fsFoot . ';line-height:' . $lh . ';">'
            . $footerContextHtml . $appBlock . '</div>';

        $htmlLang = strtolower(explode('-', $locale)[0] ?? 'en');
        if (!preg_match('/^[a-z]{2}$/', $htmlLang)) {
            $htmlLang = 'en';
        }
        $logoColW = (string) ((int) $lw + 8);

        return '<!DOCTYPE html><html lang="' . htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') . '" style="margin:0;padding:0;background-color:' . $pageBgEsc . ';">'
            . '<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>'
            . '<meta name="color-scheme" content="light dark"/>'
            . '<meta name="supported-color-schemes" content="light dark"/>'
            . '<title>' . $headlineHtml . '</title>' . $styleBlock . '</head>'
            . '<body class="em-root" style="margin:0;padding:0;width:100%;font-family:' . $ff . ';font-size:' . $fs . ';line-height:' . $lh . ';color:' . $tc . ';background-color:' . $pageBgEsc . ';">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background-color:' . $pageBgEsc . ';" bgcolor="' . $pageBgEsc . '">'
            . '<tr><td align="center" class="em-shell-pad" style="padding:24px 16px;background-color:' . $pageBgEsc . ';" bgcolor="' . $pageBgEsc . '">'
            . '<table role="presentation" class="em-card" width="100%" cellpadding="0" cellspacing="0" style="max-width:' . htmlspecialchars($mwc, ENT_QUOTES, 'UTF-8') . ';width:100%;background:' . $cardBg . ';border:1px solid ' . $border . ';border-radius:' . $rCard . ';box-shadow:' . $shadow . ';">'
            . '<tr><td class="em-head-pad" style="padding:24px 24px 16px;border-bottom:1px solid ' . $border . ';">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td valign="middle" style="padding-right:12px;">'
            . '<h1 class="em-h1" style="margin:0;font-size:20px;font-weight:700;color:' . $tc . ';line-height:1.25;">' . $headlineHtml . '</h1>'
            . $sublineBlock
            . '</td>'
            . ($logoBlock !== '' ? '<td valign="top" width="' . htmlspecialchars($logoColW, ENT_QUOTES, 'UTF-8') . '" align="right">' . $logoBlock . '</td>' : '')
            . '</tr></table></td></tr>'
            . '<tr><td class="em-body-pad em-body" style="padding:24px;color:' . $tc . ';">' . $bodyHtml . $ctaBlock . '</td></tr>'
            . '<tr><td class="em-foot-pad" style="padding:16px 24px 24px;border-top:1px solid ' . $border . ';">' . $footerInner . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
