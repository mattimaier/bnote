<?php
/**
 * BNote transactional HTML mail shell (table layout, FlyonUI-aligned tokens).
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
        $tm = $t['textMuted'];
        $border = $t['border'];
        $pageBg = $t['pageBg'];
        $cardBg = $t['cardBg'];
        $shadow = $t['shadowCard'];
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

        $styleBlock = '<style type="text/css">'
            . '.em-root{font-family:' . $ff . ';font-size:' . $fs . ';line-height:' . $lh . ';color:' . $tc . ';}'
            . '.em-muted{color:' . $tm . ';}'
            . '.em-foot{font-size:' . $fsFoot . ';color:' . $tm . ';}'
            . 'a.em-link{color:' . $primary . ';text-decoration:underline;}'
            . '</style>';

        $sublineBlock = '';
        if ($companySublineHtml !== '') {
            $sublineBlock = '<p class="em-muted" style="margin:8px 0 0;font-size:' . $fsSmall . ';color:' . $tm . ';">'
                . $companySublineHtml . '</p>';
        }

        $logoBlock = '';
        if ($logoImgSrc !== '') {
            $escSrc = htmlspecialchars($logoImgSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $logoBlock = '<img src="' . $escSrc . '" width="' . htmlspecialchars($lw, ENT_QUOTES, 'UTF-8') . '" height="'
                . htmlspecialchars($lhLogo, ENT_QUOTES, 'UTF-8') . '" alt="BNote" style="display:block;border:0;outline:none;">';
        }

        $ctaBlock = '';
        if ($ctaHref !== null && $ctaLabel !== null && $ctaHref !== '' && $ctaLabel !== '') {
            $ctaBlock = '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0 0;">'
                . '<tr><td style="border-radius:' . $rBtn . ';background:' . $primary . ';">'
                . '<a href="' . $ctaHref . '" style="display:inline-block;padding:' . $py . ' ' . $px . ';color:' . $onPrimary
                . ';text-decoration:none;font-weight:600;font-size:' . $fsSmall . ';">' . $ctaLabel . '</a>'
                . '</td></tr></table>';
        }

        $base = MailEnv::nextgenPublicBaseUrl();
        $appBlock = '';
        if ($base !== '') {
            $urlEsc = htmlspecialchars($base, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $openLabel = htmlspecialchars(MailI18n::t('mail.footer.openApp', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $appBlock = '<p style="margin:12px 0 0;font-size:' . $fsFoot . ';">'
                . '<a class="em-link" href="' . $urlEsc . '" style="color:' . $primary . ';font-weight:600;">' . $openLabel . '</a></p>';
        } else {
            $hint = htmlspecialchars(MailI18n::t('mail.footer.openAppUnavailable', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $appBlock = '<p class="em-muted" style="margin:12px 0 0;font-size:' . $fsFoot . ';color:' . $tm . ';">' . $hint . '</p>';
        }

        $footerInner = '<div class="em-foot" style="font-size:' . $fsFoot . ';color:' . $tm . ';line-height:' . $lh . ';">'
            . $footerContextHtml . $appBlock . '</div>';

        $htmlLang = strtolower(explode('-', $locale)[0] ?? 'en');
        if (!preg_match('/^[a-z]{2}$/', $htmlLang)) {
            $htmlLang = 'en';
        }
        $logoColW = (string) ((int) $lw + 8);

        return '<!DOCTYPE html><html lang="' . htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') . '">'
            . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"/>'
            . '<title>' . $headlineHtml . '</title>' . $styleBlock . '</head>'
            . '<body class="em-root" style="margin:0;padding:0;font-family:' . $ff . ';font-size:' . $fs . ';line-height:' . $lh . ';color:' . $tc . ';background:' . $pageBg . ';">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $pageBg . ';padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:' . $cardBg . ';border:1px solid ' . $border . ';border-radius:' . $rCard . ';box-shadow:' . $shadow . ';">'
            . '<tr><td style="padding:24px 24px 16px;border-bottom:1px solid ' . $border . ';">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td valign="middle" style="padding-right:12px;">'
            . '<h1 style="margin:0;font-size:20px;font-weight:700;color:' . $tc . ';line-height:1.25;">' . $headlineHtml . '</h1>'
            . $sublineBlock
            . '</td>'
            . ($logoBlock !== '' ? '<td valign="top" width="' . htmlspecialchars($logoColW, ENT_QUOTES, 'UTF-8') . '" align="right">' . $logoBlock . '</td>' : '')
            . '</tr></table></td></tr>'
            . '<tr><td style="padding:24px;color:' . $tc . ';">' . $bodyHtml . $ctaBlock . '</td></tr>'
            . '<tr><td style="padding:16px 24px 24px;border-top:1px solid ' . $border . ';">' . $footerInner . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
