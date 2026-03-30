<?php
declare(strict_types=1);

/** Subject-line prefix: "{company} · BNote · " or "BNote · " */
final class MailSubject {
    public static function orgPrefix(string $company): string {
        if ($company !== '') {
            return $company . ' · BNote · ';
        }
        return 'BNote · ';
    }
}
