<?php
declare(strict_types=1);

require_once __DIR__ . '/MailI18n.php';

/**
 * Shared "BNote - band name" copy for mail subtitles and sender lines.
 */
final class MailBranding {
    /**
     * HTML-safe line: "BNote - {band}" or "BNote" when no organization name is set.
     */
    public static function bnoteBandLine(string $locale, string $companyRaw): string {
        $company = trim($companyRaw);
        if ($company !== '') {
            $bandEsc = htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return MailI18n::interpolate(MailI18n::t('mail.shell.subtitleWithBand', $locale), [
                'band' => $bandEsc,
            ]);
        }

        return htmlspecialchars(MailI18n::t('mail.shell.subtitleBnoteOnly', $locale), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
