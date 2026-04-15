<?php
/**
 * App-configurable reminder settings (stored in DB, no deploy required).
 */
declare(strict_types=1);

require_once __DIR__ . "/ReminderSchema.php";

final class ReminderConfig
{
  /** @return array<string,mixed> */
  public static function defaults(): array
  {
    return [
      "enabled" => true,
      "recipient_scope" => "actionable_only",
      "event_window_days" => 90,
      "max_events" => 99,
      "include_votes" => true,
      "max_votes" => 99,
      "include_tasks" => true,
      "max_tasks" => 99,
      "escalation" => [
        "enabled" => false,
        "deadline_windows_hours" => [
          "rehearsal" => [168, 48],
          "concert" => [168, 48],
        ],
        "dropout_window_hours" => [
          "rehearsal" => 24,
          "concert" => 24,
        ],
        "pending_threshold_percent" => [
          "rehearsal" => 20,
          "concert" => 20,
        ],
        "escalation_target_group_id" => 0,
        "include_event_organizer" => true,
      ],
    ];
  }

  /** @return array<string,mixed> */
  public static function get(object $db): array
  {
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
  public static function update(object $db, array $input): array
  {
    if (!ReminderSchema::ensureTables($db)) {
      throw new RuntimeException("reminder_schema_unavailable");
    }
    $current = self::get($db);
    $merged = array_merge($current, $input);
    $next = self::normalize($merged);
    foreach ($next as $k => $v) {
      $encoded = self::encodeValue($v);
      $db->execute(
        'INSERT INTO nextgen_reminder_config (config_key, config_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = UTC_TIMESTAMP()',
        [["s", $k], ["s", $encoded]],
      );
    }
    return $next;
  }

  /**
   * @param array<string,mixed> $cfg
   */
  public static function isScheduleDueNow(array $cfg, DateTimeImmutable $nowUtc): bool
  {
    if (empty($cfg["enabled"])) {
      return false;
    }
    $weekday = (int) ($cfg["weekday_utc"] ?? 1);
    $hm = (string) ($cfg["time_utc"] ?? "08:00");
    if (!preg_match('/^\d{2}:\d{2}$/', $hm)) {
      return false;
    }
    [$hh, $mm] = array_map("intval", explode(":", $hm));
    $currentWeekday = (int) $nowUtc->format("N");
    $currentMinutes = ((int) $nowUtc->format("G")) * 60 + (int) $nowUtc->format("i");
    $configuredMinutes = $hh * 60 + $mm;
    return $currentWeekday === $weekday && $currentMinutes >= $configuredMinutes;
  }

  /**
   * @param array<string,mixed> $raw
   * @return array<string,mixed>
   */
  private static function normalize(array $raw): array
  {
    $d = self::defaults();
    $x = array_merge($d, $raw);

    $scope = (string) ($x["recipient_scope"] ?? $d["recipient_scope"]);
    if (!in_array($scope, ["actionable_only", "all_opted_in"], true)) {
      $scope = (string) $d["recipient_scope"];
    }

    $escInput = isset($x["escalation"]) && is_array($x["escalation"]) ? $x["escalation"] : [];
    $escDefault = isset($d["escalation"]) && is_array($d["escalation"]) ? $d["escalation"] : [];
    $esc = array_merge($escDefault, $escInput);
    $deadlineWindows = self::normalizeDeadlineWindows($esc["deadline_windows_hours"] ?? null);
    $dropoutWindows = self::normalizeThresholdByType($esc["dropout_window_hours"] ?? null, 24, 1, 240);
    $pendingThresholds = self::normalizeThresholdByType($esc["pending_threshold_percent"] ?? null, 20, 1, 100);

    return [
      "enabled" => self::toBool($x["enabled"]),
      "recipient_scope" => $scope,
      "event_window_days" => max(1, min(180, (int) ($x["event_window_days"] ?? $d["event_window_days"]))),
      "max_events" => max(1, min(99, (int) ($x["max_events"] ?? $d["max_events"]))),
      "include_votes" => self::toBool($x["include_votes"]),
      "max_votes" => max(1, min(99, (int) ($x["max_votes"] ?? $d["max_votes"]))),
      "include_tasks" => self::toBool($x["include_tasks"]),
      "max_tasks" => max(1, min(99, (int) ($x["max_tasks"] ?? $d["max_tasks"]))),
      "escalation" => [
        "enabled" => self::toBool($esc["enabled"] ?? false),
        "deadline_windows_hours" => $deadlineWindows,
        "dropout_window_hours" => $dropoutWindows,
        "pending_threshold_percent" => $pendingThresholds,
        "escalation_target_group_id" => max(0, (int) ($esc["escalation_target_group_id"] ?? 0)),
        "include_event_organizer" => self::toBool($esc["include_event_organizer"] ?? true),
      ],
    ];
  }

  /**
   * Accepts both legacy list shape [warning, critical] and new object shape:
   * { rehearsal:[warning,critical], concert:[warning,critical] }.
   *
   * @param mixed $raw
   * @return array{rehearsal:list<int>,concert:list<int>}
   */
  private static function normalizeDeadlineWindows($raw): array
  {
    $default = [168, 48];
    $legacy = self::normalizeWindowList($raw, $default);
    if (is_array($raw) && (array_key_exists("rehearsal", $raw) || array_key_exists("concert", $raw))) {
      $reh = self::normalizeWindowList($raw["rehearsal"] ?? null, $legacy);
      $con = self::normalizeWindowList($raw["concert"] ?? null, $legacy);
      return [
        "rehearsal" => $reh,
        "concert" => $con,
      ];
    }
    return [
      "rehearsal" => $legacy,
      "concert" => $legacy,
    ];
  }

  /**
   * @param mixed $raw
   * @param list<int> $fallback
   * @return list<int>
   */
  private static function normalizeWindowList($raw, array $fallback): array
  {
    $out = [];
    if (is_array($raw)) {
      foreach ($raw as $w) {
        $n = (int) $w;
        if ($n > 0 && $n <= 240) {
          $out[] = $n;
        }
      }
    } elseif (is_numeric($raw)) {
      $n = (int) $raw;
      if ($n > 0 && $n <= 240) {
        $out[] = $n;
      }
    }
    if (count($out) < 1) {
      $out = $fallback;
    }
    rsort($out);
    if (count($out) > 2) {
      $out = array_slice($out, 0, 2);
    }
    return array_values($out);
  }

  /**
   * @param mixed $raw
   * @return array{rehearsal:int,concert:int}
   */
  private static function normalizeThresholdByType($raw, int $default, int $min, int $max): array
  {
    $legacy = is_numeric($raw) ? (int) $raw : $default;
    $legacy = max($min, min($max, $legacy));
    if (is_array($raw) && (array_key_exists("rehearsal", $raw) || array_key_exists("concert", $raw))) {
      $reh = is_numeric($raw["rehearsal"] ?? null) ? (int) $raw["rehearsal"] : $legacy;
      $con = is_numeric($raw["concert"] ?? null) ? (int) $raw["concert"] : $legacy;
      return [
        "rehearsal" => max($min, min($max, $reh)),
        "concert" => max($min, min($max, $con)),
      ];
    }
    return [
      "rehearsal" => $legacy,
      "concert" => $legacy,
    ];
  }

  private static function toBool($v): bool
  {
    return $v === true || $v === 1 || $v === "1" || $v === "true" || $v === "on";
  }

  /**
   * @return array<string,mixed>
   */
  private static function readAllRaw(object $db): array
  {
    $out = [];
    $sel = $db->getSelection("SELECT config_key, config_value FROM nextgen_reminder_config", []);
    if (!is_array($sel) || count($sel) < 2) {
      return $out;
    }
    for ($i = 1; $i < count($sel); $i++) {
      $row = $sel[$i];
      $k = isset($row["config_key"]) ? trim((string) $row["config_key"]) : "";
      if ($k === "") {
        continue;
      }
      $v = isset($row["config_value"]) ? (string) $row["config_value"] : "";
      $decoded = json_decode($v, true);
      $out[$k] = json_last_error() === JSON_ERROR_NONE ? $decoded : $v;
    }
    return $out;
  }

  private static function encodeValue($v): string
  {
    if (is_bool($v) || is_int($v) || is_float($v) || is_array($v)) {
      return json_encode($v);
    }
    return (string) $v;
  }
}
