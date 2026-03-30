<?php
declare(strict_types=1);

require_once __DIR__ . '/MailEnv.php';
require_once __DIR__ . '/MailI18n.php';

final class MailBodyText {
    public static function appendInstanceLink(string $text, string $locale): string {
        $base = MailEnv::nextgenPublicBaseUrl();
        if ($base === '') {
            return $text;
        }
        $suffix = "\n\n" . MailI18n::interpolate(MailI18n::t('mail.footer.openAppPlain', $locale), [
            'url' => $base,
        ]);
        return $text . $suffix;
    }
}
