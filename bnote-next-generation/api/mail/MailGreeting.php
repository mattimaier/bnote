<?php
declare(strict_types=1);

require_once __DIR__ . '/MailI18n.php';

/**
 * Optional first-name salutation for transactional mail (not used for password reset).
 */
final class MailGreeting {
    public static function htmlLeadParagraph(string $locale, string $firstName): string {
        $t = trim($firstName);
        if ($t === '') {
            return '';
        }
        $firstEsc = htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $line = MailI18n::interpolate(MailI18n::t('mail.greeting.salutation', $locale), ['firstName' => $firstEsc]);

        return '<p class="em-greeting" style="margin:0 0 12px;font-size:16px;">' . $line . '</p>';
    }

    /** Plain-text salutation plus blank line, or empty string. */
    public static function plainPrefix(string $locale, string $firstName): string {
        $t = trim($firstName);
        if ($t === '') {
            return '';
        }
        $line = MailI18n::interpolate(MailI18n::t('mail.greeting.salutation', $locale), ['firstName' => $t]);

        return $line . "\n\n";
    }
}
