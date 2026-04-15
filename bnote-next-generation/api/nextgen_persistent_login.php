<?php
/**
 * Long-lived persistent login token handling for Next Gen.
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

declare(strict_types=1);

require_once __DIR__ . "/persistent_login_token_schema.php";

final class NextGenPersistentLogin
{
  public const COOKIE_NAME = "bnote_persistent_login";
  private const TOKEN_BYTES = 32;
  private const TTL_SECONDS = 2592000; // 30 days

  /**
   * Create a long-lived token for this user and store cookie.
   */
  public static function issueAndSetCookie(int $userId, object $db): void
  {
    if ($userId < 1) {
      return;
    }
    if (!PersistentLoginTokenSchema::ensureTable($db)) {
      return;
    }
    if (!self::isActiveUser($userId, $db)) {
      return;
    }

    self::cleanupRows($userId, $db);
    $plain = bin2hex(random_bytes(self::TOKEN_BYTES));
    $hash = hash("sha256", $plain, false);
    $ttl = (int) self::TTL_SECONDS;

    $db->execute(
      "INSERT INTO persistent_login_token (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " .
        $ttl .
        " SECOND))",
      [["i", $userId], ["s", $hash]],
    );

    self::setCookieValue($plain, time() + $ttl);
  }

  /**
   * Restore session from persistent token cookie if possible.
   * Returns the authenticated user id (0 if not restored).
   */
  public static function restoreSessionIfPossible(object $db): int
  {
    if (isset($_SESSION["user"]) && (int) $_SESSION["user"] > 0) {
      return (int) $_SESSION["user"];
    }
    $plain = self::readCookieToken();
    if ($plain === "") {
      return 0;
    }
    if (!PersistentLoginTokenSchema::ensureTable($db)) {
      return 0;
    }

    $hash = hash("sha256", $plain, false);
    $row = $db->fetchRow(
      'SELECT plt.id, plt.user_id
             FROM persistent_login_token plt
             JOIN user u ON u.id = plt.user_id
             WHERE plt.token_hash = ? AND plt.revoked_at IS NULL AND plt.expires_at > NOW() AND u.isActive = 1
             LIMIT 1',
      [["s", $hash]],
    );

    if (!is_array($row) || empty($row["user_id"])) {
      self::clearCookie();
      return 0;
    }

    $uid = (int) $row["user_id"];
    if ($uid < 1) {
      self::clearCookie();
      return 0;
    }

    // One-time token rotation on use reduces replay window for stolen cookies.
    $db->execute("UPDATE persistent_login_token SET revoked_at = NOW(), last_used_at = NOW() WHERE id = ? LIMIT 1", [
      ["i", (int) $row["id"]],
    ]);

    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
    $_SESSION["user"] = $uid;

    self::issueAndSetCookie($uid, $db);
    return $uid;
  }

  /**
   * Revoke the currently presented persistent token and clear cookie.
   */
  public static function revokeCurrentTokenAndClearCookie(object $db): void
  {
    $plain = self::readCookieToken();
    if ($plain !== "" && PersistentLoginTokenSchema::ensureTable($db)) {
      $hash = hash("sha256", $plain, false);
      $db->execute("UPDATE persistent_login_token SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL", [
        ["s", $hash],
      ]);
    }
    self::clearCookie();
  }

  private static function cleanupRows(int $userId, object $db): void
  {
    $db->execute(
      "DELETE FROM persistent_login_token WHERE user_id = ? AND (expires_at <= NOW() OR revoked_at IS NOT NULL)",
      [["i", $userId]],
    );
  }

  private static function isActiveUser(int $userId, object $db): bool
  {
    $row = $db->fetchRow("SELECT isActive FROM user WHERE id = ? LIMIT 1", [["i", $userId]]);
    if (!is_array($row) || !array_key_exists("isActive", $row)) {
      return false;
    }
    return (int) $row["isActive"] === 1;
  }

  private static function readCookieToken(): string
  {
    $raw = $_COOKIE[self::COOKIE_NAME] ?? "";
    if (!is_string($raw)) {
      return "";
    }
    $token = strtolower(trim($raw));
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
      return "";
    }
    return $token;
  }

  private static function clearCookie(): void
  {
    self::setCookieValue("", time() - 3600);
    unset($_COOKIE[self::COOKIE_NAME]);
  }

  private static function setCookieValue(string $value, int $expiresAt): void
  {
    $secure = self::isSecureRequest();
    setcookie(self::COOKIE_NAME, $value, [
      "expires" => $expiresAt,
      "path" => "/",
      "secure" => $secure,
      "httponly" => true,
      "samesite" => "Lax",
    ]);

    if ($expiresAt > time() && $value !== "") {
      $_COOKIE[self::COOKIE_NAME] = $value;
    }
  }

  private static function isSecureRequest(): bool
  {
    if (!empty($_SERVER["HTTPS"]) && strtolower((string) $_SERVER["HTTPS"]) !== "off") {
      return true;
    }
    if (!empty($_SERVER["SERVER_PORT"]) && (int) $_SERVER["SERVER_PORT"] === 443) {
      return true;
    }
    if (
      !empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
      strtolower((string) $_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"
    ) {
      return true;
    }
    return false;
  }
}
