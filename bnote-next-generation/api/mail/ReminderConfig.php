<?php
/**
 * App-configurable reminder settings (stored in DB, no deploy required).
 */
declare(strict_types=1);

require_once __DIR__ . '/ReminderSchema.php';

final class ReminderConfig {
    /** @return array<string,mixed> */
    public static function defaults(): array {
        return [
            'enabled' => false,
            'weekday_utc' => 1,
            'time_utc' => '08:00',
            'recipient_scope' => 'actionable_only',
            'event_window_days' => 90,
            'max_events' => 8,
            'include_votes' => true,
            'max_votes' => 5,
            'include_tasks' => true,
            'max_tasks' => 5,
        ];
    }

    /** @return array<string,mixed> */
    public static function get(object $db): array {
        if (!ReminderSchema::ensureTables($db)) {
            return self::defaults();
        }
        $raw = self::readAllRaw($db);
        return self::normalize($raw);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function update(object $db, array $input): array {
        if (!ReminderSchema::ensureTables($db)) {
            throw new RuntimeException('reminder_schema_unavailable');
        }
        $current = self::get($db);
        $merged = array_merge($current, $input);
        $next = self::normalize($merged);
        foreach ($next as $k => $v) {
            $encoded = self::encodeValue($v);
            $db->execute(
                'INSERT INTO nextgen_reminder_config (config_key, config_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = UTC_TIMESTAMP()',
                [['s', $k], ['s', $encoded]]
            );
        }
        return $next;
    }

    /**
     * @param array<string,mixed> $cfg
     */
    public static function isScheduleDueNow(array $cfg, DateTimeImmutable $nowUtc): bool {
        if (empty($cfg['enabled'])) {
            return false;
        }
        $weekday = (int) ($cfg['weekday_utc'] ?? 1);
        $hm = (string) ($cfg['time_utc'] ?? '08:00');
        if (!preg_match('/^\d{2}:\d{2}$/', $hm)) {
            return false;
        }
        [$hh, $mm] = array_map('intval', explode(':', $hm));
        $currentWeekday = (int) $nowUtc->format('N');
        $currentMinutes = ((int) $nowUtc->format('G')) * 60 + (int) $nowUtc->format('i');
        $configuredMinutes = $hh * 60 + $mm;
        return $currentWeekday === $weekday && $currentMinutes >= $configuredMinutes;
    }

    /**
     * @param array<string,mixed> $raw
     * @return array<string,mixed>
     */
    private static function normalize(array $raw): array {
        $d = self::defaults();
        $x = array_merge($d, $raw);

        $weekday = (int) ($x['weekday_utc'] ?? $d['weekday_utc']);
        if ($weekday < 1 || $weekday > 7) {
            $weekday = (int) $d['weekday_utc'];
        }

        $time = trim((string) ($x['time_utc'] ?? $d['time_utc']));
        if (!preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time = (string) $d['time_utc'];
        }
        [$hhRaw, $mmRaw] = array_map('intval', explode(':', $time));
        $hh = max(0, min(23, $hhRaw));
        $mm = max(0, min(59, $mmRaw));
        $time = str_pad((string) $hh, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $mm, 2, '0', STR_PAD_LEFT);

        $scope = (string) ($x['recipient_scope'] ?? $d['recipient_scope']);
        if (!in_array($scope, ['actionable_only', 'all_opted_in'], true)) {
            $scope = (string) $d['recipient_scope'];
        }

        return [
            'enabled' => self::toBool($x['enabled']),
            'weekday_utc' => $weekday,
            'time_utc' => $time,
            'recipient_scope' => $scope,
            'event_window_days' => max(1, min(180, (int) ($x['event_window_days'] ?? $d['event_window_days']))),
            'max_events' => max(1, min(50, (int) ($x['max_events'] ?? $d['max_events']))),
            'include_votes' => self::toBool($x['include_votes']),
            'max_votes' => max(1, min(50, (int) ($x['max_votes'] ?? $d['max_votes']))),
            'include_tasks' => self::toBool($x['include_tasks']),
            'max_tasks' => max(1, min(50, (int) ($x['max_tasks'] ?? $d['max_tasks']))),
        ];
    }

    private static function toBool($v): bool {
        return $v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on';
    }

    /**
     * @return array<string,mixed>
     */
    private static function readAllRaw(object $db): array {
        $out = [];
        $sel = $db->getSelection('SELECT config_key, config_value FROM nextgen_reminder_config', []);
        if (!is_array($sel) || count($sel) < 2) {
            return $out;
        }
        for ($i = 1; $i < count($sel); $i++) {
            $row = $sel[$i];
            $k = isset($row['config_key']) ? trim((string) $row['config_key']) : '';
            if ($k === '') {
                continue;
            }
            $v = isset($row['config_value']) ? (string) $row['config_value'] : '';
            $decoded = json_decode($v, true);
            $out[$k] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $v;
        }
        return $out;
    }

    private static function encodeValue($v): string {
        if (is_bool($v) || is_int($v) || is_float($v) || is_array($v)) {
            return json_encode($v);
        }
        return (string) $v;
    }
}
