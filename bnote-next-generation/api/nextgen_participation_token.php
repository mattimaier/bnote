<?php
/**
 * Magic-link tokens for email participation (rehearsal/concert). SHA-256 at rest.
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

declare(strict_types=1);

require_once __DIR__ . "/participation_response_token_schema.php";
require_once __DIR__ . "/mail/MailLocaleDateTime.php";

final class NextGenParticipationToken
{
  private const TOKEN_BYTES = 32;

  /**
   * Default / backup validity when the event has no participation deadline (`approve_until`).
   * Optional override: configuration parameter `invitation_link_validity_days` (integer days, 1–365).
   */
  public const DEFAULT_INVITATION_LINK_VALIDITY_DAYS = 30;

  /** Hard ceiling for any magic-link TTL (deadline-driven or backup). */
  private const ABSOLUTE_MAX_LINK_TTL_SECONDS = 365 * 86400;

  public static function defaultMaxTtlSeconds(): int
  {
    return self::DEFAULT_INVITATION_LINK_VALIDITY_DAYS * 86400;
  }

  /**
   * Backup TTL when `approve_until` is unset (see ttlSecondsForEvent).
   *
   * @param object $system_data BNote system_data with getDynamicConfigParameter()
   */
  public static function maxTtlSecondsFromConfig($system_data): int
  {
    $raw = $system_data->getDynamicConfigParameter("invitation_link_validity_days");
    if ($raw === null || $raw === "") {
      return self::defaultMaxTtlSeconds();
    }
    $days = (int) $raw;
    if ($days < 1) {
      return self::defaultMaxTtlSeconds();
    }
    if ($days > 365) {
      $days = 365;
    }

    return $days * 86400;
  }

  /**
   * Resolve user id for a contact, or 0.
   */
  public static function userIdForContact(int $contactId, $db): int
  {
    if ($contactId < 1) {
      return 0;
    }
    $row = $db->fetchRow("SELECT id FROM user WHERE contact = ? LIMIT 1", [["i", $contactId]]);
    return is_array($row) && !empty($row["id"]) ? (int) $row["id"] : 0;
  }

  /**
   * TTL until token expiry (MySQL DATE_ADD).
   * If `approve_until` is set and in the future, the link lasts until that deadline (or event start if sooner —
   * participation is locked after begin). Not limited by the backup window.
   * Without a participation deadline, uses min(backupCap, time until begin) if begin is future, else backupCap only.
   *
   * @param int|null $backupTtlSeconds when no `approve_until`; from maxTtlSecondsFromConfig / default 30 days
   */
  public static function ttlSecondsForEvent(
    ?string $approveUntil,
    ?string $eventBegin,
    ?int $backupTtlSeconds = null,
  ): int {
    $backup = $backupTtlSeconds ?? self::defaultMaxTtlSeconds();
    $now = time();

    $approveEnd = self::futureEpochEnd($approveUntil, $now);
    $beginEnd = self::futureEpochEnd($eventBegin, $now);

    if ($approveEnd !== null) {
      $end = $approveEnd;
      if ($beginEnd !== null) {
        $end = min($end, $beginEnd);
      }
      $ttl = $end - $now;

      return max(3600, min(self::ABSOLUTE_MAX_LINK_TTL_SECONDS, $ttl));
    }

    if ($beginEnd !== null) {
      $ttl = $beginEnd - $now;

      return min($backup, max(3600, min(self::ABSOLUTE_MAX_LINK_TTL_SECONDS, $ttl)));
    }

    return min(self::ABSOLUTE_MAX_LINK_TTL_SECONDS, $backup);
  }

  /**
   * Human-readable “valid until” for mail (band timezone), ~DATE_ADD(NOW(), ttl) when the token is minted.
   */
  public static function formatApproxExpiryForMail(string $locale, int $ttlSeconds): string
  {
    $ttlSeconds = max(300, min(self::ABSOLUTE_MAX_LINK_TTL_SECONDS, $ttlSeconds));
    $tz = new DateTimeZone(MailLocaleDateTime::defaultTimezone());
    $until = (new DateTimeImmutable("now", $tz))->add(new DateInterval("PT" . $ttlSeconds . "S"));

    return MailLocaleDateTime::formatDateTimeShort($until, $locale);
  }

  /**
   * @return array{plainToken:string,tokenHash:string}
   */
  public static function newTokenRow($db, string $eventType, int $eventId, int $contactId, int $ttlSeconds): array
  {
    if (!ParticipationResponseTokenSchema::ensureTable($db)) {
      throw new RuntimeException("participation_token_schema_failed");
    }
    $eventType = strtoupper($eventType);
    if ($eventType !== "R" && $eventType !== "C") {
      throw new InvalidArgumentException("invalid_event_type");
    }
    if ($eventId < 1 || $contactId < 1) {
      throw new InvalidArgumentException("invalid_ids");
    }

    $userId = self::userIdForContact($contactId, $db);
    $ttlSeconds = max(300, min(self::ABSOLUTE_MAX_LINK_TTL_SECONDS, $ttlSeconds));

    // Keep existing still-valid tokens so previously sent emails remain usable.
    // Clean only expired rows for the same event/identity to limit table growth.
    if ($userId > 0) {
      $db->execute(
        "DELETE FROM participation_response_token WHERE event_type = ? AND event_id = ? AND user_id = ? AND expires_at <= NOW()",
        [["s", $eventType], ["i", $eventId], ["i", $userId]],
      );
    } else {
      $db->execute(
        "DELETE FROM participation_response_token WHERE event_type = ? AND event_id = ? AND contact_id = ? AND user_id IS NULL AND expires_at <= NOW()",
        [["s", $eventType], ["i", $eventId], ["i", $contactId]],
      );
    }

    $plain = bin2hex(random_bytes(self::TOKEN_BYTES));
    $tokenHash = hash("sha256", $plain, false);

    if ($userId > 0) {
      $db->execute(
        "INSERT INTO participation_response_token (token_hash, event_type, event_id, user_id, contact_id, expires_at) VALUES (?, ?, ?, ?, NULL, DATE_ADD(NOW(), INTERVAL " .
          (int) $ttlSeconds .
          " SECOND))",
        [["s", $tokenHash], ["s", $eventType], ["i", $eventId], ["i", $userId]],
      );
    } else {
      $db->execute(
        "INSERT INTO participation_response_token (token_hash, event_type, event_id, user_id, contact_id, expires_at) VALUES (?, ?, ?, NULL, ?, DATE_ADD(NOW(), INTERVAL " .
          (int) $ttlSeconds .
          " SECOND))",
        [["s", $tokenHash], ["s", $eventType], ["i", $eventId], ["i", $contactId]],
      );
    }

    return ["plainToken" => $plain, "tokenHash" => $tokenHash];
  }

  /**
   * @return array{event_type:string,event_id:int,user_id:int,contact_id:int,expiresAt:string}|null
   */
  public static function loadValidTokenRow(string $plainToken, $db): ?array
  {
    $plainToken = strtolower(trim($plainToken));
    if ($plainToken === "" || strlen($plainToken) !== 64 || !ctype_xdigit($plainToken)) {
      return null;
    }
    if (!ParticipationResponseTokenSchema::ensureTable($db)) {
      return null;
    }
    $tokenHash = hash("sha256", $plainToken, false);
    $row = $db->fetchRow(
      "SELECT event_type, event_id, user_id, contact_id, expires_at FROM participation_response_token WHERE token_hash = ? AND expires_at > NOW() LIMIT 1",
      [["s", $tokenHash]],
    );
    if (!is_array($row) || empty($row["event_id"])) {
      return null;
    }
    $uid = isset($row["user_id"]) && $row["user_id"] !== null ? (int) $row["user_id"] : 0;
    $cid = isset($row["contact_id"]) && $row["contact_id"] !== null ? (int) $row["contact_id"] : 0;
    $expiresAt = self::expiresAtIso8601($row["expires_at"] ?? null);

    return [
      "event_type" => strtoupper((string) ($row["event_type"] ?? "")),
      "event_id" => (int) $row["event_id"],
      "user_id" => $uid,
      "contact_id" => $cid,
      "expiresAt" => $expiresAt,
    ];
  }

  /**
   * @return int|null Unix timestamp when participation / link relevance ends, or null if unset / not in the future
   */
  private static function futureEpochEnd(?string $dt, int $now): ?int
  {
    if ($dt === null || $dt === "" || $dt === "-") {
      return null;
    }
    $ts = strtotime((string) $dt);
    if ($ts === false || $ts <= $now) {
      return null;
    }

    return $ts;
  }

  private static function expiresAtIso8601($mysqlRaw): string
  {
    if ($mysqlRaw === null) {
      return "";
    }
    $s = trim((string) $mysqlRaw);
    if ($s === "") {
      return "";
    }
    $tz = new DateTimeZone(MailLocaleDateTime::defaultTimezone());
    try {
      $dt = new DateTimeImmutable($s, $tz);

      return $dt->format(\DateTimeInterface::ATOM);
    } catch (Throwable $e) {
      return "";
    }
  }
}
