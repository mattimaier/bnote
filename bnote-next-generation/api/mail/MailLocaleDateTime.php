<?php
/**
 * Locale-aware dates/times for mail — matches frontend lib/date-time.ts (date-fns "P", "p", "Pp")
 * and band timezone (PHP default, usually Europe/Berlin from BNote main).
 */
declare(strict_types=1);

final class MailLocaleDateTime {
    public static function icuLocale(string $lang): string {
        $key = strtolower(explode('-', $lang)[0] ?? 'en');

        return match ($key) {
            'de' => 'de_DE',
            'en' => 'en_US',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            default => 'en_US',
        };
    }

    public static function defaultTimezone(): string {
        $z = @date_default_timezone_get();
        if ($z !== '' && strcasecmp($z, 'UTC') !== 0) {
            return $z;
        }

        return 'Europe/Berlin';
    }

    public static function parseInBandTimezone(string $raw): ?DateTimeImmutable {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $tz = new DateTimeZone(self::defaultTimezone());
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $pattern) {
            $dt = DateTimeImmutable::createFromFormat($pattern, $raw, $tz);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
        }
        try {
            return new DateTimeImmutable($raw, $tz);
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function formatDateShort(DateTimeInterface $dt, string $lang): string {
        if (!extension_loaded('intl')) {
            return $dt->format('Y-m-d');
        }
        $f = new IntlDateFormatter(
            self::icuLocale($lang),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            $dt->getTimezone()
        );

        return (string) $f->format($dt);
    }

    public static function formatTimeShort(DateTimeInterface $dt, string $lang): string {
        if (!extension_loaded('intl')) {
            return $dt->format('H:i');
        }
        $f = new IntlDateFormatter(
            self::icuLocale($lang),
            IntlDateFormatter::NONE,
            IntlDateFormatter::SHORT,
            $dt->getTimezone()
        );

        return (string) $f->format($dt);
    }

    /** Same as I18n formatDateTimeShort (date-fns "Pp"). */
    public static function formatDateTimeShort(DateTimeInterface $dt, string $lang): string {
        if (!extension_loaded('intl')) {
            return $dt->format('Y-m-d H:i');
        }
        $f = new IntlDateFormatter(
            self::icuLocale($lang),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::SHORT,
            $dt->getTimezone()
        );

        return (string) $f->format($dt);
    }

    /** Comment row: DB created_at → localized date + time. */
    public static function formatCommentCreatedAt(string $createdAt, string $lang): string {
        $dt = self::parseInBandTimezone($createdAt);
        if ($dt === null) {
            return $createdAt;
        }

        return self::formatDateTimeShort($dt, $lang);
    }

    /**
     * Event header meta: same shape as before (date · start - end), localized.
     * Time segment only if begin has a time component (≥ 16 chars in DB string).
     */
    public static function formatEventMetaLine(string $begin, string $end, string $lang): string {
        $begin = trim($begin);
        if ($begin === '' || strlen($begin) < 10) {
            return '';
        }
        $dateSource = substr($begin, 0, 10) . ' 12:00:00';
        $dateDt = self::parseInBandTimezone($dateSource);
        if ($dateDt === null) {
            return '';
        }
        $datePart = self::formatDateShort($dateDt, $lang);
        $meta = $datePart;
        if (strlen($begin) < 16) {
            return $meta;
        }
        $beginDt = self::parseInBandTimezone($begin);
        if ($beginDt === null) {
            return $meta;
        }
        $t0 = self::formatTimeShort($beginDt, $lang);
        if ($t0 === '') {
            return $meta;
        }
        $meta .= ' · ' . $t0;
        $end = trim($end);
        if ($end !== '' && strlen($end) >= 16) {
            $endDt = self::parseInBandTimezone($end);
            if ($endDt !== null) {
                $t1 = self::formatTimeShort($endDt, $lang);
                if ($t1 !== '') {
                    $meta .= ' - ' . $t1;
                }
            }
        }

        return $meta;
    }

    /** Vote end date/datetime line */
    public static function formatVoteEndLine(string $end, string $lang): string {
        $end = trim($end);
        if ($end === '') {
            return '';
        }
        $dt = self::parseInBandTimezone(strlen($end) <= 10 ? $end . ' 00:00:00' : $end);
        if ($dt === null) {
            return $end;
        }
        if (strlen($end) <= 10) {
            return self::formatDateShort($dt, $lang);
        }

        return self::formatDateTimeShort($dt, $lang);
    }
}
