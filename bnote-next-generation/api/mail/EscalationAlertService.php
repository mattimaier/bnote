<?php
/**
 * Escalation alerts for at-risk rehearsals/concerts.
 */
declare(strict_types=1);

require_once __DIR__ . "/ReminderSchema.php";
require_once __DIR__ . "/ReminderConfig.php";
require_once __DIR__ . "/NextGenMailPolicy.php";
require_once __DIR__ . "/NextGenMailer.php";
require_once __DIR__ . "/MailEnv.php";
require_once __DIR__ . "/MailI18n.php";
require_once __DIR__ . "/builders/EscalationAlertMailBuilder.php";
require_once __DIR__ . "/builders/EscalationResolvedMailBuilder.php";

final class EscalationAlertService
{
  private const SIMPLE_ESCALATION_PAIR_PARAM = "nextgen_simple_escalation_pair";
  private const NOTIFICATION_TYPE_ALERT = "alert";
  private const NOTIFICATION_TYPE_RESOLVED = "resolved";
  /**
   * @param array{
   *   dryRun?:bool,
   *   force?:bool,
   *   mode?:string,
   *   isTest?:bool,
   *   overrideRecipients?:list<string>,
   *   onlyEvent?:array{otype:string,oid:int},
   *   triggerKind?:string,
   *   dropoutSource?:string,
   *   dropoutContactId?:int
   * } $options
   * @return array<string,mixed>
   */
  public static function runScheduled($system_data, array $options = []): array
  {
    $dryRun = !empty($options["dryRun"]);
    $force = !empty($options["force"]);
    $mode = isset($options["mode"]) ? (string) $options["mode"] : "scheduled";
    $isTest = !empty($options["isTest"]) || $dryRun;
    $overrideRecipients = self::normalizeEmails($options["overrideRecipients"] ?? []);
    $triggerKind = isset($options["triggerKind"]) ? (string) $options["triggerKind"] : "scheduled";
    $onlyEvent = isset($options["onlyEvent"]) && is_array($options["onlyEvent"]) ? $options["onlyEvent"] : null;
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    $dropoutMeta = null;
    if ($triggerKind === "dropout") {
      $dropoutMeta = [
        "source" => (string) ($options["dropoutSource"] ?? "unknown"),
        "contact_id" => (int) ($options["dropoutContactId"] ?? 0),
      ];
    }

    $db = $system_data->dbcon;
    if (!ReminderSchema::ensureTables($db)) {
      throw new RuntimeException("reminder_schema_unavailable");
    }

    $cfg = ReminderConfig::get($db);
    $esc = self::escalationCfg($cfg);
    if (empty($esc["enabled"]) && !$force) {
      return ["status" => "disabled", "config" => $esc];
    }

    if (!NextGenMailPolicy::shouldSendPublicMail($system_data) && !$dryRun) {
      return ["status" => "mail_disabled", "config" => $esc];
    }

    $events = self::loadAtRiskEvents($system_data, $esc, $locale, $onlyEvent, $dropoutMeta);
    $result = [
      "status" => "ok",
      "dryRun" => $dryRun,
      "events_scanned" => count($events),
      "alerts_sent" => 0,
      "resolved_sent" => 0,
      "details" => [],
      "resolved_details" => [],
      "config" => $esc,
    ];

    foreach ($events as $event) {
      $acceptance = self::syncAcceptanceForCurrentRisk($db, $event);
      $eligibility = self::resolveEligibleRecipients($system_data, $esc, $event);
      $recipientEmails = $overrideRecipients;
      if (count($recipientEmails) < 1) {
        $recipientEmails = array_values(
          array_unique(array_map(static fn(array $r): string => (string) $r["email"], $eligibility["included"])),
        );
      }
      $recipientEmails = self::normalizeEmails($recipientEmails);

      $eventUrl = self::eventAbsoluteUrl((string) $event["otype"], (int) $event["oid"]);
      $eventBegin = (string) ($event["begin"] ?? "");
      $windowsForType = self::deadlineWindowsForType($esc, (string) ($event["otype"] ?? "R"));
      $urgency = self::urgencyForEvent($eventBegin, $windowsForType);
      $detail = [
        "event" => $event,
        "urgency" => $urgency,
        "eligibility" => $eligibility,
        "recipient_count" => count($recipientEmails),
        "override_recipients" => count($overrideRecipients) > 0,
        "status" => "dry_run",
        "acceptance" => $acceptance,
      ];

      if (!$dryRun && !empty($acceptance["accepted"])) {
        $detail["status"] = "suppressed_accepted";
        $detail["emails_sent"] = 0;
      } elseif (!$dryRun && count($recipientEmails) > 0) {
        $messages = [];
        foreach ($recipientEmails as $email) {
          $messages[] = EscalationAlertMailBuilder::build(
            $system_data,
            $locale,
            (string) ($event["title"] ?? ""),
            (string) ($event["otype"] ?? "R"),
            $eventBegin,
            "",
            "",
            $urgency,
            (array) ($event["reasons"] ?? []),
            (array) ($event["instrument_gaps"] ?? []),
            $eventUrl,
            [$email],
            [],
            isset($event["counts"]) && is_array($event["counts"]) ? $event["counts"] : null,
          );
        }
        $sent = NextGenMailer::sendBulk($messages);
        $result["alerts_sent"] += $sent;
        $detail["status"] = $sent > 0 ? "sent" : "send_failed";
        $detail["emails_sent"] = $sent;
      } elseif (!$dryRun && count($recipientEmails) < 1) {
        $detail["status"] = "no_recipients";
      }

      ReminderSchema::addEscalationAudit($db, [
        "is_test" => $isTest || count($overrideRecipients) > 0,
        "trigger_kind" => $triggerKind,
        "delivery_mode" => $mode,
        "notification_type" => self::NOTIFICATION_TYPE_ALERT,
        "otype" => (string) ($event["otype"] ?? ""),
        "oid" => (int) ($event["oid"] ?? 0),
        "event_title" => (string) ($event["title"] ?? ""),
        "reason_summary" => implode("; ", (array) ($event["reasons"] ?? [])),
        "event" => $event,
        "urgency" => $urgency,
        "override_recipients" => $overrideRecipients,
        "resolved_recipients" => $recipientEmails,
        "eligibility" => $eligibility,
        "result" => $detail["status"],
        "acceptance" => $acceptance,
      ]);

      $result["details"][] = $detail;
    }

    if (!$dryRun && $triggerKind === "scheduled" && $onlyEvent === null) {
      $resolved = self::runScheduledResolutionTransitions($system_data, $esc, $locale);
      $result["resolved_sent"] = (int) ($resolved["resolved_sent"] ?? 0);
      $result["resolved_details"] =
        isset($resolved["resolved_details"]) && is_array($resolved["resolved_details"])
          ? $resolved["resolved_details"]
          : [];
    }

    return $result;
  }

  /**
   * @return array<string,mixed>
   */
  public static function triggerImmediateDropout(
    $system_data,
    string $otype,
    int $oid,
    int $contactId,
    string $source,
    bool $dryRun = false,
    array $overrideRecipients = [],
  ): array {
    $otype = strtoupper(trim($otype));
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return ["status" => "invalid_event"];
    }
    ReminderSchema::deactivateEscalationAcceptance($system_data->dbcon, $otype, $oid);
    $cfg = ReminderConfig::get($system_data->dbcon);
    $esc = self::escalationCfg($cfg);
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    $event = self::buildDropoutEvent($system_data, $esc, $locale, $otype, $oid, $source, $contactId);
    if ($event === null) {
      return ["status" => "event_not_found"];
    }
    $hoursToBegin = self::hoursUntil((string) ($event["begin"] ?? ""));
    if ($hoursToBegin !== null && $hoursToBegin > self::dropoutWindowForType($esc, $otype)) {
      return ["status" => "outside_dropout_window", "hours_to_begin" => $hoursToBegin];
    }

    return self::runScheduled($system_data, [
      "dryRun" => $dryRun,
      "force" => true,
      "mode" => "immediate_dropout",
      "isTest" => $dryRun || count($overrideRecipients) > 0,
      "overrideRecipients" => $overrideRecipients,
      "onlyEvent" => ["otype" => $otype, "oid" => $oid],
      "triggerKind" => "dropout",
      "dropoutSource" => $source,
      "dropoutContactId" => $contactId,
    ]);
  }

  /**
   * @return array<string,mixed>
   */
  public static function triggerImmediateResolutionCheck(
    $system_data,
    string $otype,
    int $oid,
    string $source = "state_changed",
  ): array {
    $otype = strtoupper(trim($otype));
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return ["status" => "invalid_event"];
    }
    $db = $system_data->dbcon;
    if (!ReminderSchema::ensureTables($db)) {
      return ["status" => "reminder_schema_unavailable"];
    }
    if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
      return ["status" => "mail_disabled"];
    }
    $cfg = ReminderConfig::get($db);
    $esc = self::escalationCfg($cfg);
    if (empty($esc["enabled"])) {
      return ["status" => "disabled"];
    }
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    return self::resolveTransitionForEvent(
      $system_data,
      $esc,
      $locale,
      $otype,
      $oid,
      "immediate_resolution",
      "resolved_transition",
      $source,
    );
  }

  /**
   * @return array<string,mixed>
   */
  public static function getEligibilityForEvent($system_data, string $otype, int $oid): array
  {
    $cfg = ReminderConfig::get($system_data->dbcon);
    $esc = self::escalationCfg($cfg);
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    $event = self::buildRiskForEvent($system_data, $esc, $locale, strtoupper($otype), $oid);
    if ($event === null) {
      return ["event" => null, "eligibility" => ["included" => [], "excluded" => []]];
    }
    return [
      "event" => $event,
      "eligibility" => self::resolveEligibleRecipients($system_data, $esc, $event),
    ];
  }

  /**
   * Returns escalation warning payload for in-app UI rendering.
   * Uses the same risk/reason/urgency logic as escalation email generation.
   *
   * @return null|array<string,mixed>
   */
  public static function getWarningForEvent($system_data, string $otype, int $oid): ?array
  {
    $cfg = ReminderConfig::get($system_data->dbcon);
    $esc = self::escalationCfg($cfg);
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    $event = self::buildRiskForEvent($system_data, $esc, $locale, strtoupper($otype), $oid);
    if ($event === null) {
      ReminderSchema::deactivateEscalationAcceptance($system_data->dbcon, strtoupper($otype), $oid);
      return null;
    }
    $hoursToBegin = self::hoursUntil((string) ($event["begin"] ?? ""));
    if ($hoursToBegin === null || $hoursToBegin < 0) {
      ReminderSchema::deactivateEscalationAcceptance($system_data->dbcon, strtoupper($otype), $oid);
      return null;
    }
    $acceptance = self::syncAcceptanceForCurrentRisk($system_data->dbcon, $event);
    $urgency = self::urgencyForEvent(
      (string) ($event["begin"] ?? ""),
      self::deadlineWindowsForType($esc, (string) ($event["otype"] ?? $otype)),
    );
    return self::uiWarningPayload($event, $urgency, $acceptance);
  }

  /**
   * @return array<string,mixed>
   */
  public static function acceptRiskForEvent(
    $system_data,
    string $otype,
    int $oid,
    int $acceptedByUserId,
    string $acceptedByName,
  ): array {
    $otype = strtoupper(trim($otype));
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return ["status" => "invalid_event", "warning" => null];
    }
    $db = $system_data->dbcon;
    if (!ReminderSchema::ensureTables($db)) {
      return ["status" => "reminder_schema_unavailable", "warning" => null];
    }
    $cfg = ReminderConfig::get($db);
    $esc = self::escalationCfg($cfg);
    $locale = method_exists($system_data, "getLang") ? (string) ($system_data->getLang() ?: "en") : "en";
    $event = self::buildRiskForEvent($system_data, $esc, $locale, $otype, $oid);
    if ($event === null) {
      ReminderSchema::deactivateEscalationAcceptance($db, $otype, $oid);
      return ["status" => "not_at_risk", "warning" => null];
    }
    $fingerprint = self::riskFingerprint($event);
    $ok = ReminderSchema::saveEscalationAcceptance(
      $db,
      $otype,
      $oid,
      max(0, $acceptedByUserId),
      $acceptedByName,
      $fingerprint,
    );
    if (!$ok) {
      return ["status" => "save_failed", "warning" => null];
    }
    $acceptance = self::syncAcceptanceForCurrentRisk($db, $event);
    $urgency = self::urgencyForEvent(
      (string) ($event["begin"] ?? ""),
      self::deadlineWindowsForType($esc, (string) ($event["otype"] ?? $otype)),
    );
    return [
      "status" => "accepted",
      "warning" => self::uiWarningPayload($event, $urgency, $acceptance),
    ];
  }

  /**
   * @return array<string,mixed>
   */
  public static function resetRiskAcceptanceForEvent($system_data, string $otype, int $oid): array
  {
    $otype = strtoupper(trim($otype));
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return ["status" => "invalid_event", "warning" => null];
    }
    $db = $system_data->dbcon;
    if (!ReminderSchema::ensureTables($db)) {
      return ["status" => "reminder_schema_unavailable", "warning" => null];
    }
    ReminderSchema::deactivateEscalationAcceptance($db, $otype, $oid);
    return [
      "status" => "reset",
      "warning" => self::getWarningForEvent($system_data, $otype, $oid),
    ];
  }

  /**
   * @param array<string,mixed> $esc
   * @return array<string,mixed>
   */
  private static function runScheduledResolutionTransitions($system_data, array $esc, string $locale): array
  {
    $db = $system_data->dbcon;
    $result = [
      "resolved_sent" => 0,
      "resolved_details" => [],
    ];
    foreach (self::eventsWithLatestRealAlert($db) as $eventRef) {
      $otype = strtoupper((string) ($eventRef["otype"] ?? ""));
      $oid = (int) ($eventRef["oid"] ?? 0);
      if (($otype !== "R" && $otype !== "C") || $oid < 1) {
        continue;
      }
      $detail = self::resolveTransitionForEvent(
        $system_data,
        $esc,
        $locale,
        $otype,
        $oid,
        "scheduled_resolution",
        "resolved_transition",
        "scheduled_scan",
      );
      $result["resolved_details"][] = $detail;
      if ((string) ($detail["status"] ?? "") === "sent") {
        $result["resolved_sent"] += (int) ($detail["emails_sent"] ?? 0);
      }
    }
    return $result;
  }

  /**
   * @param array<string,mixed> $esc
   * @return array<string,mixed>
   */
  private static function resolveTransitionForEvent(
    $system_data,
    array $esc,
    string $locale,
    string $otype,
    int $oid,
    string $deliveryMode,
    string $triggerKind,
    string $source,
  ): array {
    $otype = strtoupper(trim($otype));
    $db = $system_data->dbcon;
    $latestState = self::latestNotificationState($db, $otype, $oid);
    if ($latestState !== self::NOTIFICATION_TYPE_ALERT) {
      return [
        "otype" => $otype,
        "oid" => $oid,
        "status" => "no_prior_alert",
      ];
    }

    $base = self::loadSingleEventBase($db, $otype, $oid);
    if ($base === null) {
      return [
        "otype" => $otype,
        "oid" => $oid,
        "status" => "event_not_found",
      ];
    }
    $hoursToBegin = self::hoursUntil((string) ($base["begin"] ?? ""));
    if ($hoursToBegin !== null && $hoursToBegin < 0) {
      return [
        "otype" => $otype,
        "oid" => $oid,
        "status" => "event_in_past",
      ];
    }

    $risk = self::buildRiskForEvent($system_data, $esc, $locale, $otype, $oid, $base);
    if ($risk !== null) {
      return [
        "otype" => $otype,
        "oid" => $oid,
        "status" => "still_at_risk",
      ];
    }

    $event = self::buildResolvedEventPayload($db, $otype, $oid, $base);
    $eligibility = self::resolveEligibleRecipients($system_data, $esc, $event);
    $recipientEmails = self::normalizeEmails(
      array_values(
        array_unique(array_map(static fn(array $r): string => (string) $r["email"], $eligibility["included"])),
      ),
    );

    $detail = [
      "event" => $event,
      "eligibility" => $eligibility,
      "recipient_count" => count($recipientEmails),
      "status" => "dry_run",
      "source" => $source,
    ];
    if (count($recipientEmails) > 0) {
      $messages = [];
      foreach ($recipientEmails as $email) {
        $messages[] = EscalationResolvedMailBuilder::build(
          $system_data,
          $locale,
          (string) ($event["title"] ?? ""),
          $otype,
          (string) ($event["begin"] ?? ""),
          "",
          "",
          (string) ($event["event_url"] ?? ""),
          [$email],
          [],
          isset($event["counts"]) && is_array($event["counts"]) ? $event["counts"] : null,
        );
      }
      $sent = NextGenMailer::sendBulk($messages);
      $detail["status"] = $sent > 0 ? "sent" : "send_failed";
      $detail["emails_sent"] = $sent;
    } else {
      $detail["status"] = "no_recipients";
    }

    ReminderSchema::addEscalationAudit($db, [
      "is_test" => false,
      "trigger_kind" => $triggerKind,
      "delivery_mode" => $deliveryMode,
      "notification_type" => self::NOTIFICATION_TYPE_RESOLVED,
      "otype" => (string) ($event["otype"] ?? ""),
      "oid" => (int) ($event["oid"] ?? 0),
      "event_title" => (string) ($event["title"] ?? ""),
      "reason_summary" => "resolved_transition",
      "event" => $event,
      "source" => $source,
      "resolved_recipients" => $recipientEmails,
      "eligibility" => $eligibility,
      "result" => (string) ($detail["status"] ?? ""),
    ]);

    return $detail;
  }

  /**
   * @return list<array{otype:string,oid:int}>
   */
  private static function eventsWithLatestRealAlert(object $db): array
  {
    $rows = $db->getSelection(
      'SELECT otype, oid, MAX(id) AS max_id
             FROM nextgen_escalation_audit
             WHERE is_test = 0 AND otype IN (\'R\', \'C\') AND oid > 0
             GROUP BY otype, oid',
      [],
    );
    if (!is_array($rows) || count($rows) < 2) {
      return [];
    }
    $out = [];
    for ($i = 1; $i < count($rows); $i++) {
      $otype = strtoupper((string) ($rows[$i]["otype"] ?? ""));
      $oid = (int) ($rows[$i]["oid"] ?? 0);
      if (($otype !== "R" && $otype !== "C") || $oid < 1) {
        continue;
      }
      if (self::latestNotificationState($db, $otype, $oid) !== self::NOTIFICATION_TYPE_ALERT) {
        continue;
      }
      $out[] = ["otype" => $otype, "oid" => $oid];
    }
    return $out;
  }

  private static function latestNotificationState(object $db, string $otype, int $oid): ?string
  {
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return null;
    }
    $rows = $db->getSelection(
      'SELECT payload_json
             FROM nextgen_escalation_audit
             WHERE is_test = 0 AND otype = ? AND oid = ?
             ORDER BY id DESC
             LIMIT 25',
      [["s", $otype], ["i", $oid]],
    );
    if (!is_array($rows) || count($rows) < 2) {
      return null;
    }
    for ($i = 1; $i < count($rows); $i++) {
      $payloadRaw = (string) ($rows[$i]["payload_json"] ?? "");
      $payload = json_decode($payloadRaw, true);
      if (!is_array($payload)) {
        continue;
      }
      $result = strtolower(trim((string) ($payload["result"] ?? "")));
      if ($result !== "sent") {
        continue;
      }
      $notificationType = strtolower(trim((string) ($payload["notification_type"] ?? self::NOTIFICATION_TYPE_ALERT)));
      if ($notificationType === self::NOTIFICATION_TYPE_RESOLVED) {
        return self::NOTIFICATION_TYPE_RESOLVED;
      }
      return self::NOTIFICATION_TYPE_ALERT;
    }
    return null;
  }

  /**
   * @param array<string,mixed> $base
   * @return array<string,mixed>
   */
  private static function buildResolvedEventPayload(object $db, string $otype, int $oid, array $base): array
  {
    return [
      "otype" => $otype,
      "oid" => $oid,
      "title" => (string) ($base["title"] ?? ""),
      "begin" => (string) ($base["begin"] ?? ""),
      "approve_until" => self::effectiveDeadline(
        (string) ($base["approve_until"] ?? ""),
        (string) ($base["begin"] ?? ""),
      ),
      "counts" => self::participationCounts($db, $otype, $oid),
      "event_url" => self::eventAbsoluteUrl($otype, $oid),
    ];
  }

  /**
   * @param array<string,mixed> $cfg
   * @return array<string,mixed>
   */
  private static function escalationCfg(array $cfg): array
  {
    $esc = isset($cfg["escalation"]) && is_array($cfg["escalation"]) ? $cfg["escalation"] : [];
    $rawWindows = $esc["deadline_windows_hours"] ?? [168, 48];
    $legacyWindows = self::normalizeWindowList($rawWindows, [168, 48]);
    if (
      is_array($rawWindows) &&
      (array_key_exists("rehearsal", $rawWindows) || array_key_exists("concert", $rawWindows))
    ) {
      $rehWindows = self::normalizeWindowList($rawWindows["rehearsal"] ?? null, $legacyWindows);
      $conWindows = self::normalizeWindowList($rawWindows["concert"] ?? null, $legacyWindows);
    } else {
      $rehWindows = $legacyWindows;
      $conWindows = $legacyWindows;
    }
    return [
      "enabled" => !empty($esc["enabled"]),
      "deadline_windows_hours" => [
        "rehearsal" => $rehWindows,
        "concert" => $conWindows,
      ],
      "dropout_window_hours" => self::normalizeThresholdByType($esc["dropout_window_hours"] ?? 24, 24, 1, 240),
      "pending_threshold_percent" => self::normalizeThresholdByType(
        $esc["pending_threshold_percent"] ?? 20,
        20,
        1,
        100,
      ),
      "escalation_target_group_id" => max(0, (int) ($esc["escalation_target_group_id"] ?? 0)),
      "include_event_organizer" => !empty($esc["include_event_organizer"]),
    ];
  }

  /**
   * @param array<string,mixed> $esc
   * @param string $locale
   * @param null|array{otype:string,oid:int} $onlyEvent
   * @param null|array{source:string,contact_id:int} $dropoutMeta
   * @return list<array<string,mixed>>
   */
  private static function loadAtRiskEvents(
    $system_data,
    array $esc,
    string $locale,
    ?array $onlyEvent = null,
    ?array $dropoutMeta = null,
  ): array {
    $events = [];
    if ($onlyEvent !== null) {
      $otype = strtoupper((string) ($onlyEvent["otype"] ?? ""));
      $oid = (int) ($onlyEvent["oid"] ?? 0);
      if ($dropoutMeta !== null) {
        $event = self::buildDropoutEvent(
          $system_data,
          $esc,
          $locale,
          $otype,
          $oid,
          (string) ($dropoutMeta["source"] ?? "unknown"),
          (int) ($dropoutMeta["contact_id"] ?? 0),
        );
        return $event ? [$event] : [];
      }
      $event = self::buildRiskForEvent($system_data, $esc, $locale, $otype, $oid);
      return $event ? [$event] : [];
    }

    foreach (["R", "C"] as $otype) {
      $baseRows = self::loadFutureEventsBase($system_data, $otype);
      foreach ($baseRows as $row) {
        $oid = (int) ($row["id"] ?? 0);
        if ($oid < 1) {
          continue;
        }
        $event = self::buildRiskForEvent($system_data, $esc, $locale, $otype, $oid, $row);
        if ($event !== null) {
          $events[] = $event;
        }
      }
    }
    usort(
      $events,
      static fn(array $a, array $b): int => strcmp((string) ($a["begin"] ?? ""), (string) ($b["begin"] ?? "")),
    );
    return $events;
  }

  /**
   * @return list<array<string,mixed>>
   */
  private static function loadFutureEventsBase($system_data, string $otype): array
  {
    $db = $system_data->dbcon;
    if ($otype === "R") {
      // Rehearsal table has no stable `name` column in legacy schema.
      $query = "SELECT id, begin, approve_until, begin AS title FROM rehearsal WHERE begin >= NOW()";
    } else {
      $query = "SELECT id, begin, approve_until, title FROM concert WHERE begin >= NOW()";
    }
    $rows = $db->getSelection($query, []);
    if (!is_array($rows) || count($rows) < 2) {
      return [];
    }
    $out = [];
    for ($i = 1; $i < count($rows); $i++) {
      $out[] = $rows[$i];
    }
    return $out;
  }

  /**
   * @param array<string,mixed> $esc
   * @param string $locale
   * @param null|array<string,mixed> $base
   * @return null|array<string,mixed>
   */
  private static function buildRiskForEvent(
    $system_data,
    array $esc,
    string $locale,
    string $otype,
    int $oid,
    ?array $base = null,
  ): ?array {
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return null;
    }
    $db = $system_data->dbcon;
    $base = $base ?? self::loadSingleEventBase($db, $otype, $oid);
    if ($base === null) {
      return null;
    }
    $deadline = self::effectiveDeadline((string) ($base["approve_until"] ?? ""), (string) ($base["begin"] ?? ""));
    $hoursToDeadline = self::hoursUntil($deadline);
    $windows = self::deadlineWindowsForType($esc, $otype);
    $maxWindow = count($windows) > 0 ? max($windows) : 48;
    if ($hoursToDeadline !== null && $hoursToDeadline > $maxWindow) {
      return null;
    }

    $counts = self::participationCounts($db, $otype, $oid);
    $pendingPct =
      $counts["invited_users"] > 0 ? (int) round(($counts["pending_users"] * 100) / $counts["invited_users"]) : 0;
    $coverage = self::loadInstrumentMinimums($system_data, $otype);
    $minimums = is_array($coverage["minimums"] ?? null) ? $coverage["minimums"] : [];
    $coverageMode = ($coverage["mode"] ?? "instrument") === "section" ? "section" : "instrument";
    $gaps = self::instrumentGaps($system_data, $db, $otype, $oid, $minimums, $coverageMode);
    $reasons = [];
    $pendingThreshold = self::pendingThresholdForType($esc, $otype);
    if ($pendingPct >= $pendingThreshold) {
      $reasons[] = MailI18n::interpolate(MailI18n::t("mail.escalation.reasonPendingThreshold", $locale), [
        "pending" => (string) $counts["pending_users"],
        "total" => (string) $counts["invited_users"],
        "percent" => (string) $pendingPct,
      ]);
    }
    if (count($gaps) > 0) {
      $reasons[] = MailI18n::interpolate(MailI18n::t("mail.escalation.reasonInstrumentGaps", $locale), [
        "count" => (string) count($gaps),
      ]);
    }
    if (count($reasons) < 1) {
      return null;
    }

    return [
      "otype" => $otype,
      "oid" => $oid,
      "title" => (string) ($base["title"] ?? ""),
      "begin" => (string) ($base["begin"] ?? ""),
      "approve_until" => $deadline,
      "hours_to_deadline" => $hoursToDeadline,
      "pending_threshold_percent" => $pendingThreshold,
      "counts" => $counts,
      "pending_percent" => $pendingPct,
      "instrument_gaps" => $gaps,
      "reasons" => $reasons,
    ];
  }

  /**
   * Build a forced event payload for dropout triggers.
   * Includes normal risk reasons when present, and always includes the dropout reason.
   *
   * @param array<string,mixed> $esc
   * @return null|array<string,mixed>
   */
  private static function buildDropoutEvent(
    $system_data,
    array $esc,
    string $locale,
    string $otype,
    int $oid,
    string $source,
    int $contactId,
  ): ?array {
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return null;
    }
    $db = $system_data->dbcon;
    $base = self::loadSingleEventBase($db, $otype, $oid);
    if ($base === null) {
      return null;
    }
    $counts = self::participationCounts($db, $otype, $oid);
    $pendingPct =
      $counts["invited_users"] > 0 ? (int) round(($counts["pending_users"] * 100) / $counts["invited_users"]) : 0;
    $coverage = self::loadInstrumentMinimums($system_data, $otype);
    $minimums = is_array($coverage["minimums"] ?? null) ? $coverage["minimums"] : [];
    $coverageMode = ($coverage["mode"] ?? "instrument") === "section" ? "section" : "instrument";
    $gaps = self::instrumentGaps($system_data, $db, $otype, $oid, $minimums, $coverageMode);

    $reasons = [self::dropoutReasonText($db, $locale, $source, $contactId)];
    $pendingThreshold = self::pendingThresholdForType($esc, $otype);
    if ($pendingPct >= $pendingThreshold) {
      $reasons[] = MailI18n::interpolate(MailI18n::t("mail.escalation.reasonPendingThreshold", $locale), [
        "pending" => (string) $counts["pending_users"],
        "total" => (string) $counts["invited_users"],
        "percent" => (string) $pendingPct,
      ]);
    }
    if (count($gaps) > 0) {
      $reasons[] = MailI18n::interpolate(MailI18n::t("mail.escalation.reasonInstrumentGaps", $locale), [
        "count" => (string) count($gaps),
      ]);
    }

    return [
      "otype" => $otype,
      "oid" => $oid,
      "title" => (string) ($base["title"] ?? ""),
      "begin" => (string) ($base["begin"] ?? ""),
      "approve_until" => (string) ($base["approve_until"] ?? ""),
      "hours_to_deadline" => self::hoursUntil(
        self::effectiveDeadline((string) ($base["approve_until"] ?? ""), (string) ($base["begin"] ?? "")),
      ),
      "pending_threshold_percent" => $pendingThreshold,
      "counts" => $counts,
      "pending_percent" => $pendingPct,
      "instrument_gaps" => $gaps,
      "reasons" => $reasons,
    ];
  }

  /**
   * @return null|array<string,mixed>
   */
  private static function loadSingleEventBase(object $db, string $otype, int $oid): ?array
  {
    if ($otype === "R") {
      $row = $db->fetchRow("SELECT id, begin, approve_until, begin AS title FROM rehearsal WHERE id = ?", [
        ["i", $oid],
      ]);
    } else {
      $row = $db->fetchRow("SELECT id, begin, approve_until, title FROM concert WHERE id = ?", [["i", $oid]]);
    }
    return is_array($row) ? $row : null;
  }

  /**
   * @return array{invited_users:int,pending_users:int,yes:int,maybe:int,no:int}
   */
  private static function participationCounts(object $db, string $otype, int $oid): array
  {
    $tblContact = $otype === "R" ? "rehearsal_contact" : "concert_contact";
    $tblUser = $otype === "R" ? "rehearsal_user" : "concert_user";
    $entityCol = $otype === "R" ? "rehearsal" : "concert";
    $query = "SELECT
                    COUNT(DISTINCT u.id) AS invited_users,
                    SUM(CASE WHEN ev.participate IS NULL OR ev.participate < 0 THEN 1 ELSE 0 END) AS pending_users,
                    SUM(CASE WHEN ev.participate = 1 THEN 1 ELSE 0 END) AS yes_count,
                    SUM(CASE WHEN ev.participate = 2 THEN 1 ELSE 0 END) AS maybe_count,
                    SUM(CASE WHEN ev.participate = 0 THEN 1 ELSE 0 END) AS no_count
                  FROM {$tblContact} ec
                  JOIN contact c ON ec.contact = c.id
                  JOIN user u ON u.contact = c.id AND u.isActive = 1
                  LEFT JOIN {$tblUser} ev ON ev.user = u.id AND ev.{$entityCol} = ?
                  WHERE ec.{$entityCol} = ?";
    $row = $db->fetchRow($query, [["i", $oid], ["i", $oid]]);
    return [
      "invited_users" => (int) ($row["invited_users"] ?? 0),
      "pending_users" => (int) ($row["pending_users"] ?? 0),
      "yes" => (int) ($row["yes_count"] ?? 0),
      "maybe" => (int) ($row["maybe_count"] ?? 0),
      "no" => (int) ($row["no_count"] ?? 0),
    ];
  }

  /**
   * @return array{mode:string,minimums:array<string,int>}
   */
  private static function loadInstrumentMinimums($system_data, string $otype): array
  {
    $val = (string) ($system_data->getDynamicConfigParameter("instrument_minimums") ?? "");
    if ($val === "") {
      return ["mode" => "instrument", "minimums" => []];
    }
    $decoded = json_decode($val, true);
    if (!is_array($decoded)) {
      return ["mode" => "instrument", "minimums" => []];
    }
    $mode =
      ($decoded["mode"] ?? "instrument") === "section" && self::isSectionCoverageEnabled($system_data)
        ? "section"
        : "instrument";
    if (isset($decoded["rehearsal"]) || isset($decoded["concert"])) {
      $typeKey = strtoupper($otype) === "C" ? "concert" : "rehearsal";
      $selected = $decoded[$typeKey] ?? [];
      if (!is_array($selected)) {
        $selected = [];
      }
      $decoded = $selected;
    }
    $out = [];
    foreach ($decoded as $id => $min) {
      $instMin = is_numeric($min) ? max(0, (int) $min) : 0;
      $key = trim((string) $id);
      if ($instMin < 1 || $key === "") {
        continue;
      }
      if (is_numeric($key) && (int) $key > 0) {
        $out[(string) ((int) $key)] = $instMin;
        continue;
      }
      if ($mode === "section" && str_starts_with($key, "section:")) {
        $sectionId = trim(substr($key, 8));
        if ($sectionId !== "") {
          $out["section:" . $sectionId] = $instMin;
        }
      }
    }
    return ["mode" => $mode, "minimums" => $out];
  }

  /**
   * @param array<string,int> $minimums
   * @return list<array{instrument_name:string,current:int,minimum:int}>
   */
  private static function instrumentGaps(
    $system_data,
    object $db,
    string $otype,
    int $oid,
    array $minimums,
    string $mode = "instrument",
  ): array {
    $simplePairs = self::loadSimpleEscalationPairs($system_data);
    $pairActive = $mode === "instrument" && count($simplePairs) > 0;
    if (count($minimums) < 1 && !$pairActive) {
      return [];
    }
    $tblContact = $otype === "R" ? "rehearsal_contact" : "concert_contact";
    $tblUser = $otype === "R" ? "rehearsal_user" : "concert_user";
    $entityCol = $otype === "R" ? "rehearsal" : "concert";
    $query = "SELECT i.id AS instrument_id, i.name AS instrument_name,
                    SUM(CASE WHEN ev.participate IN (1,2) THEN 1 ELSE 0 END) AS attending
                  FROM {$tblContact} ec
                  JOIN contact c ON ec.contact = c.id
                  LEFT JOIN instrument i ON c.instrument = i.id
                  LEFT JOIN user u ON u.contact = c.id AND u.isActive = 1
                  LEFT JOIN {$tblUser} ev ON ev.user = u.id AND ev.{$entityCol} = ?
                  WHERE ec.{$entityCol} = ?
                  GROUP BY i.id, i.name";
    $sel = $db->getSelection($query, [["i", $oid], ["i", $oid]]);
    $gaps = [];
    $attendingByInstrument = [];
    $instrumentNamesById = [];
    $pairedInstrumentIds = [];
    if ($pairActive) {
      foreach ($simplePairs as $pair) {
        $pairedInstrumentIds[(int) ($pair["instrument_a_id"] ?? 0)] = true;
        $pairedInstrumentIds[(int) ($pair["instrument_b_id"] ?? 0)] = true;
      }
    }
    if (!is_array($sel)) {
      $sel = [];
    }
    for ($i = 1; $i < count($sel); $i++) {
      $row = $sel[$i];
      $instId = (int) ($row["instrument_id"] ?? 0);
      if ($instId < 1) {
        continue;
      }
      $attending = (int) ($row["attending"] ?? 0);
      $attendingByInstrument[$instId] = $attending;
      $instrumentNamesById[$instId] = trim((string) ($row["instrument_name"] ?? ""));
      if ($pairActive && isset($pairedInstrumentIds[$instId])) {
        continue;
      }
      $min = $minimums[(string) $instId] ?? 0;
      if ($min < 1) {
        continue;
      }
      if ($attending < $min) {
        $gaps[] = [
          "instrument_name" => $instrumentNamesById[$instId] ?? "",
          "current" => $attending,
          "minimum" => $min,
        ];
      }
    }
    $missingNameIds = [];
    foreach ($minimums as $instrumentId => $_minRaw) {
      if (!is_numeric((string) $instrumentId)) {
        continue;
      }
      $iid = (int) $instrumentId;
      if ($iid < 1) {
        continue;
      }
      if (!isset($instrumentNamesById[$iid]) || $instrumentNamesById[$iid] === "") {
        $missingNameIds[] = $iid;
      }
    }
    if ($pairActive) {
      foreach ($simplePairs as $pair) {
        $pairA = (int) ($pair["instrument_a_id"] ?? 0);
        $pairB = (int) ($pair["instrument_b_id"] ?? 0);
        if ($pairA > 0 && (!isset($instrumentNamesById[$pairA]) || $instrumentNamesById[$pairA] === "")) {
          $missingNameIds[] = $pairA;
        }
        if ($pairB > 0 && (!isset($instrumentNamesById[$pairB]) || $instrumentNamesById[$pairB] === "")) {
          $missingNameIds[] = $pairB;
        }
      }
    }
    if (count($missingNameIds) > 0) {
      $resolved = self::loadInstrumentNamesByIds($db, $missingNameIds);
      foreach ($resolved as $iid => $name) {
        if ($iid > 0 && $name !== "") {
          $instrumentNamesById[$iid] = $name;
        }
      }
    }
    if ($pairActive) {
      foreach ($simplePairs as $pair) {
        $pairA = (int) ($pair["instrument_a_id"] ?? 0);
        $pairB = (int) ($pair["instrument_b_id"] ?? 0);
        $requiredFallback = max(1, (int) ($pair["required"] ?? 1));
        $pairRequired =
          strtoupper($otype) === "C"
            ? max(1, (int) ($pair["required_concert"] ?? $requiredFallback))
            : max(1, (int) ($pair["required_rehearsal"] ?? $requiredFallback));
        $pairCurrent = (int) ($attendingByInstrument[$pairA] ?? 0) + (int) ($attendingByInstrument[$pairB] ?? 0);
        if ($pairCurrent >= $pairRequired) {
          continue;
        }
        $pairNameA = trim((string) ($instrumentNamesById[$pairA] ?? ""));
        $pairNameB = trim((string) ($instrumentNamesById[$pairB] ?? ""));
        $pairLabel = trim($pairNameA . " / " . $pairNameB, " /");
        if ($pairLabel === "") {
          $pairLabel = (string) $pairA . " / " . (string) $pairB;
        }
        $gaps[] = [
          "instrument_name" => $pairLabel,
          "current" => $pairCurrent,
          "minimum" => $pairRequired,
        ];
      }
    }
    if ($mode === "section") {
      $sections = self::loadInstrumentSections($system_data);
      $assignedInstrumentIds = [];
      foreach ($sections as $section) {
        $sectionId = (string) ($section["id"] ?? "");
        if ($sectionId === "") {
          continue;
        }
        foreach ($section["instrument_ids"] ?? [] as $rawInstrumentId) {
          $instrumentId = (int) $rawInstrumentId;
          if ($instrumentId > 0) {
            $assignedInstrumentIds[$instrumentId] = true;
          }
        }
        if (
          strtoupper($otype) === "C" &&
          is_array($section["concert_instrument_targets"] ?? null) &&
          count($section["concert_instrument_targets"]) > 0
        ) {
          foreach ($section["concert_instrument_targets"] as $targetInstrumentId => $requiredRaw) {
            $targetId = (int) $targetInstrumentId;
            $required = (int) $requiredRaw;
            if ($targetId < 1 || $required < 1) {
              continue;
            }
            $current = (int) ($attendingByInstrument[$targetId] ?? 0);
            if ($current < $required) {
              $gaps[] = [
                "instrument_name" => (string) ($section["name"] ?? $sectionId),
                "current" => $current,
                "minimum" => $required,
                "section_id" => $sectionId,
              ];
            }
          }
          continue;
        }
        $min =
          strtoupper($otype) === "C"
            ? max((int) ($section["concert_min_total"] ?? 0), (int) ($minimums["section:" . $sectionId] ?? 0))
            : max((int) ($section["rehearsal_min_total"] ?? 0), (int) ($minimums["section:" . $sectionId] ?? 0));
        if ($min < 1) {
          continue;
        }
        $current = 0;
        foreach ($section["instrument_ids"] ?? [] as $rawInstrumentId) {
          $instrumentId = (int) $rawInstrumentId;
          if ($instrumentId > 0) {
            $current += (int) ($attendingByInstrument[$instrumentId] ?? 0);
          }
        }
        if ($current < $min) {
          $gaps[] = [
            "instrument_name" => (string) ($section["name"] ?? $sectionId),
            "current" => $current,
            "minimum" => $min,
            "section_id" => $sectionId,
          ];
        }
      }
      foreach ($minimums as $instrumentId => $minRaw) {
        if (!is_numeric((string) $instrumentId)) {
          continue;
        }
        $iid = (int) $instrumentId;
        if ($iid < 1 || isset($assignedInstrumentIds[$iid])) {
          continue;
        }
        if (array_key_exists($iid, $attendingByInstrument)) {
          continue;
        }
        $min = (int) $minRaw;
        $current = (int) ($attendingByInstrument[$iid] ?? 0);
        if ($min > 0 && $current < $min) {
          $gaps[] = [
            "instrument_name" => $instrumentNamesById[$iid] ?? "",
            "current" => $current,
            "minimum" => $min,
          ];
        }
      }
      return $gaps;
    }
    foreach ($minimums as $instrumentId => $minRaw) {
      if (!is_numeric((string) $instrumentId)) {
        continue;
      }
      $iid = (int) $instrumentId;
      if ($iid < 1) {
        continue;
      }
      if ($pairActive && isset($pairedInstrumentIds[$iid])) {
        continue;
      }
      $min = (int) $minRaw;
      if ($min < 1) {
        continue;
      }
      if (array_key_exists($iid, $attendingByInstrument)) {
        continue;
      }
      $current = (int) ($attendingByInstrument[$iid] ?? 0);
      if ($current < $min) {
        $gaps[] = [
          "instrument_name" => $instrumentNamesById[$iid] ?? "",
          "current" => $current,
          "minimum" => $min,
        ];
      }
    }
    return $gaps;
  }

  /**
   * @return list<array{instrument_a_id:int,instrument_b_id:int,required_rehearsal:int,required_concert:int}>
   */
  private static function loadSimpleEscalationPairs($system_data): array
  {
    $raw = (string) ($system_data->getDynamicConfigParameter(self::SIMPLE_ESCALATION_PAIR_PARAM) ?? "");
    if ($raw === "") {
      return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return [];
    }
    $pairsRaw = isset($decoded["instrument_a_id"]) ? [$decoded] : $decoded;
    if (!is_array($pairsRaw)) {
      return [];
    }
    $out = [];
    $seenPairs = [];
    $usedInstruments = [];
    foreach ($pairsRaw as $pairRaw) {
      if (!is_array($pairRaw)) {
        continue;
      }
      $instrumentAId = (int) ($pairRaw["instrument_a_id"] ?? 0);
      $instrumentBId = (int) ($pairRaw["instrument_b_id"] ?? 0);
      $requiredFallback = max(1, (int) ($pairRaw["required"] ?? 1));
      $requiredRehearsal = max(1, (int) ($pairRaw["required_rehearsal"] ?? $requiredFallback));
      $requiredConcert = max(1, (int) ($pairRaw["required_concert"] ?? $requiredFallback));
      if ($instrumentAId < 1 || $instrumentBId < 1 || $instrumentAId === $instrumentBId) {
        continue;
      }
      if (isset($usedInstruments[$instrumentAId]) || isset($usedInstruments[$instrumentBId])) {
        continue;
      }
      $ordered = [$instrumentAId, $instrumentBId];
      sort($ordered, SORT_NUMERIC);
      $pairKey = $ordered[0] . ":" . $ordered[1];
      if (isset($seenPairs[$pairKey])) {
        continue;
      }
      $seenPairs[$pairKey] = true;
      $usedInstruments[$instrumentAId] = true;
      $usedInstruments[$instrumentBId] = true;
      $out[] = [
        "instrument_a_id" => $instrumentAId,
        "instrument_b_id" => $instrumentBId,
        "required_rehearsal" => $requiredRehearsal,
        "required_concert" => $requiredConcert,
      ];
    }
    return $out;
  }

  /**
   * @param list<int> $instrumentIds
   * @return array<int,string>
   */
  private static function loadInstrumentNamesByIds(object $db, array $instrumentIds): array
  {
    $ids = [];
    foreach ($instrumentIds as $rawId) {
      $id = (int) $rawId;
      if ($id > 0) {
        $ids[$id] = true;
      }
    }
    $ids = array_keys($ids);
    if (count($ids) < 1) {
      return [];
    }
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $params = [];
    foreach ($ids as $id) {
      $params[] = ["i", $id];
    }
    $rows = $db->getSelection("SELECT id, name FROM instrument WHERE id IN ({$placeholders})", $params);
    $out = [];
    if (!is_array($rows)) {
      return $out;
    }
    for ($i = 1; $i < count($rows); $i++) {
      $id = (int) ($rows[$i]["id"] ?? 0);
      $name = trim((string) ($rows[$i]["name"] ?? ""));
      if ($id > 0 && $name !== "") {
        $out[$id] = $name;
      }
    }
    return $out;
  }

  /**
   * @return list<array{id:string,name:string,instrument_ids:list<int>,rehearsal_min_total:int,concert_min_total:int,concert_instrument_targets:array<int,int>}>
   */
  private static function loadInstrumentSections($system_data): array
  {
    if (!self::isSectionCoverageEnabled($system_data)) {
      return [];
    }
    $raw = (string) ($system_data->getDynamicConfigParameter("nextgen_instrument_sections") ?? "");
    if ($raw === "") {
      return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return [];
    }
    $out = [];
    foreach ($decoded as $section) {
      if (!is_array($section)) {
        continue;
      }
      $id = trim((string) ($section["id"] ?? ""));
      $name = trim((string) ($section["name"] ?? ""));
      if ($id === "" || $name === "") {
        continue;
      }
      $ids = [];
      foreach ($section["instrument_ids"] ?? [] as $rawInstrumentId) {
        $instrumentId = (int) $rawInstrumentId;
        if ($instrumentId > 0) {
          $ids[] = $instrumentId;
        }
      }
      $targetMap = [];
      foreach ($section["concert_instrument_targets"] ?? [] as $target) {
        if (!is_array($target)) {
          continue;
        }
        $targetInstrumentId = (int) ($target["instrument_id"] ?? 0);
        $targetRequired = max(0, (int) ($target["required"] ?? 0));
        if ($targetInstrumentId > 0 && $targetRequired > 0) {
          $targetMap[$targetInstrumentId] = $targetRequired;
          $ids[] = $targetInstrumentId;
        }
      }
      $out[] = [
        "id" => $id,
        "name" => $name,
        "instrument_ids" => array_values(array_unique($ids)),
        "rehearsal_min_total" => max(0, (int) ($section["rehearsal_min_total"] ?? 0)),
        "concert_min_total" => max(0, (int) ($section["concert_min_total"] ?? 0)),
        "concert_instrument_targets" => $targetMap,
      ];
    }
    return $out;
  }

  private static function isSectionCoverageEnabled($system_data): bool
  {
    return (string) ($system_data->getDynamicConfigParameter("beta_section_coverage_enabled") ?? "") === "1";
  }

  /**
   * @return list<array{id:string,name:string,instrument_ids:list<int>}>
   */
  private static function loadInstrumentAliasPools($system_data): array
  {
    $raw = (string) ($system_data->getDynamicConfigParameter("nextgen_instrument_alias_pools") ?? "");
    if ($raw === "") {
      return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return [];
    }
    $out = [];
    foreach ($decoded as $pool) {
      if (!is_array($pool)) {
        continue;
      }
      $id = trim((string) ($pool["id"] ?? ""));
      $name = trim((string) ($pool["name"] ?? ""));
      $instrumentIdsRaw =
        isset($pool["instrument_ids"]) && is_array($pool["instrument_ids"]) ? $pool["instrument_ids"] : [];
      $instrumentIds = [];
      foreach ($instrumentIdsRaw as $rawInstrumentId) {
        $instrumentId = (int) $rawInstrumentId;
        if ($instrumentId > 0) {
          $instrumentIds[] = $instrumentId;
        }
      }
      if ($id === "" || $name === "" || count($instrumentIds) < 1) {
        continue;
      }
      $out[] = [
        "id" => $id,
        "name" => $name,
        "instrument_ids" => array_values(array_unique($instrumentIds)),
      ];
    }
    return $out;
  }

  /**
   * @param array<string,mixed> $esc
   * @param array<string,mixed> $event
   * @return array{included:list<array<string,mixed>>,excluded:list<array<string,mixed>>}
   */
  private static function resolveEligibleRecipients($system_data, array $esc, array $event): array
  {
    $db = $system_data->dbcon;
    $included = [];
    $excluded = [];

    $groupId = (int) ($esc["escalation_target_group_id"] ?? 0);
    if ($groupId > 0) {
      $sel = $db->getSelection(
        'SELECT c.id AS contact_id, c.name, c.surname, c.email, u.id AS user_id, u.isActive, u.email_notification
                 FROM contact_group cg
                 JOIN contact c ON c.id = cg.contact
                 LEFT JOIN user u ON u.contact = c.id
                 WHERE cg.group = ?',
        [["i", $groupId]],
      );
      if (is_array($sel)) {
        for ($i = 1; $i < count($sel); $i++) {
          $row = $sel[$i];
          $decision = self::recipientDecision($row);
          if ($decision["include"]) {
            $included[] = $decision["recipient"];
          } else {
            $excluded[] = $decision["recipient"];
          }
        }
      }
    }

    if (!empty($esc["include_event_organizer"])) {
      $org = self::resolveEventOrganizer($db, (string) ($event["otype"] ?? ""), (int) ($event["oid"] ?? 0));
      if ($org !== null) {
        $decision = self::recipientDecision($org);
        if ($decision["include"]) {
          $included[] = $decision["recipient"];
        } else {
          $excluded[] = $decision["recipient"];
        }
      }
    }

    $seen = [];
    $dedup = [];
    foreach ($included as $r) {
      $email = strtolower(trim((string) ($r["email"] ?? "")));
      if ($email === "" || isset($seen[$email])) {
        continue;
      }
      $seen[$email] = true;
      $dedup[] = $r;
    }
    return ["included" => $dedup, "excluded" => $excluded];
  }

  /**
   * @param array<string,mixed> $row
   * @return array{include:bool,recipient:array<string,mixed>}
   */
  private static function recipientDecision(array $row): array
  {
    $email = trim((string) ($row["email"] ?? ""));
    $name = trim(((string) ($row["name"] ?? "")) . " " . ((string) ($row["surname"] ?? "")));
    $recipient = [
      "contact_id" => (int) ($row["contact_id"] ?? 0),
      "user_id" => isset($row["user_id"]) ? (int) $row["user_id"] : null,
      "name" => $name,
      "email" => $email,
    ];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $recipient["reason"] = "invalid_email";
      return ["include" => false, "recipient" => $recipient];
    }
    $uid = isset($row["user_id"]) ? (int) $row["user_id"] : 0;
    if ($uid < 1) {
      $recipient["reason"] = "no_user_link";
      return ["include" => false, "recipient" => $recipient];
    }
    if ((int) ($row["isActive"] ?? 0) !== 1) {
      $recipient["reason"] = "inactive_user";
      return ["include" => false, "recipient" => $recipient];
    }
    if ((int) ($row["email_notification"] ?? 0) !== 1) {
      $recipient["reason"] = "email_notifications_off";
      return ["include" => false, "recipient" => $recipient];
    }
    $recipient["reason"] = "eligible";
    return ["include" => true, "recipient" => $recipient];
  }

  /**
   * @return null|array<string,mixed>
   */
  private static function resolveEventOrganizer(object $db, string $otype, int $oid): ?array
  {
    if ($otype === "C") {
      $row = $db->fetchRow(
        'SELECT c.id AS contact_id, c.name, c.surname, c.email, u.id AS user_id, u.isActive, u.email_notification
                 FROM concert e
                 LEFT JOIN contact c ON c.id = e.contact
                 LEFT JOIN user u ON u.contact = c.id
                 WHERE e.id = ?',
        [["i", $oid]],
      );
      return is_array($row) ? $row : null;
    }
    return null;
  }

  private static function eventAbsoluteUrl(string $otype, int $oid): string
  {
    $base = MailEnv::nextgenPublicBaseUrl();
    if ($base === "" || $oid < 1) {
      return "";
    }
    $type = strtoupper($otype) === "C" ? "concert" : "rehearsal";
    return $base . "/entity?" . http_build_query(["type" => $type, "id" => (string) $oid]);
  }

  /**
   * @param list<string> $emails
   * @return list<string>
   */
  private static function normalizeEmails(array $emails): array
  {
    $out = [];
    $seen = [];
    foreach ($emails as $e) {
      $email = strtolower(trim((string) $e));
      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
        continue;
      }
      $seen[$email] = true;
      $out[] = $email;
    }
    return $out;
  }

  private static function hoursUntil(string $datetime): ?int
  {
    $raw = trim($datetime);
    if ($raw === "") {
      return null;
    }
    try {
      $target = new DateTimeImmutable($raw, new DateTimeZone("UTC"));
      $now = new DateTimeImmutable("now", new DateTimeZone("UTC"));
      return (int) floor(($target->getTimestamp() - $now->getTimestamp()) / 3600);
    } catch (Throwable $e) {
      return null;
    }
  }

  private static function effectiveDeadline(string $approveUntil, string $begin): string
  {
    $approveRaw = trim($approveUntil);
    $beginRaw = trim($begin);
    if ($approveRaw === "") {
      return $beginRaw;
    }
    if ($beginRaw === "") {
      return $approveRaw;
    }
    try {
      $approveAt = new DateTimeImmutable($approveRaw, new DateTimeZone("UTC"));
      $beginAt = new DateTimeImmutable($beginRaw, new DateTimeZone("UTC"));
      return $approveAt->getTimestamp() <= $beginAt->getTimestamp() ? $approveRaw : $beginRaw;
    } catch (Throwable $e) {
      return $beginRaw !== "" ? $beginRaw : $approveRaw;
    }
  }

  /**
   * @param list<int> $windows
   */
  private static function urgencyForEvent(string $begin, array $windows): string
  {
    $hours = self::hoursUntil($begin);
    if ($hours === null) {
      return "soon";
    }
    $criticalThreshold = count($windows) > 0 ? min($windows) : 12;
    return $hours <= $criticalThreshold ? "critical" : "soon";
  }

  /**
   * @param array<string,mixed> $esc
   * @return list<int>
   */
  private static function deadlineWindowsForType(array $esc, string $otype): array
  {
    $key = strtoupper($otype) === "C" ? "concert" : "rehearsal";
    $raw = $esc["deadline_windows_hours"] ?? null;
    if (is_array($raw) && isset($raw[$key]) && is_array($raw[$key])) {
      return self::normalizeWindowList($raw[$key], [168, 48]);
    }
    return self::normalizeWindowList($raw, [168, 48]);
  }

  /**
   * @param array<string,mixed> $esc
   */
  private static function pendingThresholdForType(array $esc, string $otype): int
  {
    return self::thresholdForType($esc["pending_threshold_percent"] ?? null, $otype, 20, 1, 100);
  }

  /**
   * @param array<string,mixed> $esc
   */
  private static function dropoutWindowForType(array $esc, string $otype): int
  {
    return self::thresholdForType($esc["dropout_window_hours"] ?? null, $otype, 24, 1, 240);
  }

  /**
   * @param mixed $raw
   */
  private static function thresholdForType($raw, string $otype, int $default, int $min, int $max): int
  {
    $typeKey = strtoupper($otype) === "C" ? "concert" : "rehearsal";
    if (is_array($raw) && array_key_exists($typeKey, $raw) && is_numeric($raw[$typeKey])) {
      return max($min, min($max, (int) $raw[$typeKey]));
    }
    if (is_numeric($raw)) {
      return max($min, min($max, (int) $raw));
    }
    return max($min, min($max, $default));
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

  /**
   * @param array<string,mixed> $event
   * @return string
   */
  private static function riskFingerprint(array $event): string
  {
    $gaps = [];
    foreach ((array) ($event["instrument_gaps"] ?? []) as $gap) {
      if (!is_array($gap)) {
        continue;
      }
      $gaps[] = [
        "name" => trim((string) ($gap["instrument_name"] ?? "")),
        "current" => (int) ($gap["current"] ?? 0),
        "minimum" => (int) ($gap["minimum"] ?? 0),
        "section_id" => trim((string) ($gap["section_id"] ?? "")),
      ];
    }
    usort(
      $gaps,
      static fn(array $a, array $b): int => strcmp(
        json_encode($a, JSON_UNESCAPED_SLASHES) ?: "",
        json_encode($b, JSON_UNESCAPED_SLASHES) ?: "",
      ),
    );

    $countsRaw = is_array($event["counts"] ?? null) ? $event["counts"] : [];
    $counts = [
      "invited_users" => (int) ($countsRaw["invited_users"] ?? 0),
      "pending_users" => (int) ($countsRaw["pending_users"] ?? 0),
      "yes" => (int) ($countsRaw["yes"] ?? 0),
      "maybe" => (int) ($countsRaw["maybe"] ?? 0),
      "no" => (int) ($countsRaw["no"] ?? 0),
    ];

    $signature = [
      "otype" => (string) ($event["otype"] ?? ""),
      "oid" => (int) ($event["oid"] ?? 0),
      "begin" => (string) ($event["begin"] ?? ""),
      "approve_until" => (string) ($event["approve_until"] ?? ""),
      "pending_threshold_percent" => (int) ($event["pending_threshold_percent"] ?? 0),
      "pending_percent" => (int) ($event["pending_percent"] ?? 0),
      "counts" => $counts,
      "instrument_gaps" => $gaps,
    ];
    $encoded = json_encode($signature, JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
      $encoded = "{}";
    }
    return hash("sha256", $encoded);
  }

  /**
   * @param array<string,mixed> $event
   * @return array<string,mixed>
   */
  private static function syncAcceptanceForCurrentRisk(object $db, array $event): array
  {
    $otype = strtoupper((string) ($event["otype"] ?? ""));
    $oid = (int) ($event["oid"] ?? 0);
    if (($otype !== "R" && $otype !== "C") || $oid < 1) {
      return ["accepted" => false];
    }
    $row = ReminderSchema::getEscalationAcceptance($db, $otype, $oid);
    if (!is_array($row) || empty($row["is_active"])) {
      return ["accepted" => false];
    }
    $fingerprint = self::riskFingerprint($event);
    $storedFingerprint = strtolower(trim((string) ($row["accepted_risk_fingerprint"] ?? "")));
    return [
      "accepted" => true,
      "acceptedByUserId" => (int) ($row["accepted_by_user_id"] ?? 0),
      "acceptedByName" => (string) ($row["accepted_by_name"] ?? ""),
      "acceptedAt" => (string) ($row["accepted_at"] ?? ""),
      "fingerprint" => $storedFingerprint,
      "matchesCurrentRisk" => $storedFingerprint === $fingerprint,
    ];
  }

  /**
   * @param array<string,mixed> $event
   * @param array<string,mixed>|null $acceptance
   * @return array<string,mixed>
   */
  private static function uiWarningPayload(array $event, string $urgency, ?array $acceptance = null): array
  {
    $accepted = !empty($acceptance["accepted"]);
    return [
      "severity" => $urgency === "critical" ? "critical" : "soon",
      "urgency" => $urgency === "critical" ? "critical" : "soon",
      "reasons" => array_values(
        array_map(static fn($reason): string => (string) $reason, (array) ($event["reasons"] ?? [])),
      ),
      "instrument_gaps" =>
        isset($event["instrument_gaps"]) && is_array($event["instrument_gaps"])
          ? array_values($event["instrument_gaps"])
          : [],
      "counts" => isset($event["counts"]) && is_array($event["counts"]) ? $event["counts"] : null,
      "pending_percent" => isset($event["pending_percent"]) ? (int) $event["pending_percent"] : 0,
      "pending_threshold_percent" => isset($event["pending_threshold_percent"])
        ? (int) $event["pending_threshold_percent"]
        : 0,
      "hours_to_deadline" => isset($event["hours_to_deadline"]) ? (int) $event["hours_to_deadline"] : null,
      "accepted" => $accepted,
      "acceptedByName" => $accepted ? (string) ($acceptance["acceptedByName"] ?? "") : null,
      "acceptedAt" => $accepted ? (string) ($acceptance["acceptedAt"] ?? "") : null,
      "event" => [
        "otype" => (string) ($event["otype"] ?? ""),
        "oid" => (int) ($event["oid"] ?? 0),
        "title" => (string) ($event["title"] ?? ""),
        "begin" => (string) ($event["begin"] ?? ""),
        "approve_until" => (string) ($event["approve_until"] ?? ""),
      ],
    ];
  }

  private static function dropoutSourceLabel(string $locale, string $source): string
  {
    $key = match (trim($source)) {
      "participation_no" => "mail.escalation.sourceParticipationNo",
      "contact_removed_from_event" => "mail.escalation.sourceContactRemoved",
      "developer_simulation" => "mail.escalation.sourceDeveloperSimulation",
      default => "mail.escalation.sourceUnknown",
    };
    return MailI18n::t($key, $locale);
  }

  private static function dropoutReasonText(object $db, string $locale, string $source, int $contactId): string
  {
    $contactLabel = self::contactDisplayLabel($db, $contactId);
    return MailI18n::interpolate(MailI18n::t("mail.escalation.reasonDropoutDetected", $locale), [
      "source" => self::dropoutSourceLabel($locale, $source),
      "contact" => $contactLabel,
    ]);
  }

  private static function contactDisplayLabel(object $db, int $contactId): string
  {
    if ($contactId < 1) {
      return "#" . (string) max(0, $contactId);
    }
    $row = $db->fetchRow("SELECT name, surname FROM contact WHERE id = ?", [["i", $contactId]]);
    if (!is_array($row)) {
      return "#" . (string) $contactId;
    }
    $display = trim(trim((string) ($row["name"] ?? "")) . " " . trim((string) ($row["surname"] ?? "")));
    return $display !== "" ? $display : "#" . (string) $contactId;
  }
}
