<?php
/**
 * Stable global token for public concerts feed URL.
 */
declare(strict_types=1);

require_once __DIR__ . "/public_concerts_feed_token_schema.php";

final class NextGenPublicConcertsFeedToken
{
  private const TOKEN_BYTES = 32;
  private const SCOPE_KEY = "global";

  /**
   * @return array{plainToken:string,tokenHash:string}
   */
  public static function getOrCreate(object $db): array
  {
    if (!PublicConcertsFeedTokenSchema::ensureTable($db)) {
      throw new RuntimeException("public_concerts_feed_token_schema_failed");
    }

    $row = $db->fetchRow("SELECT token_plain, token_hash FROM public_concerts_feed_token WHERE scope_key = ? LIMIT 1", [
      ["s", self::SCOPE_KEY],
    ]);
    if (is_array($row) && !empty($row["token_plain"]) && !empty($row["token_hash"])) {
      return [
        "plainToken" => (string) $row["token_plain"],
        "tokenHash" => (string) $row["token_hash"],
      ];
    }

    if (is_array($row)) {
      return self::regenerate($db);
    }

    try {
      return self::insertFreshToken($db);
    } catch (Throwable $e) {
      $row2 = $db->fetchRow(
        "SELECT token_plain, token_hash FROM public_concerts_feed_token WHERE scope_key = ? LIMIT 1",
        [["s", self::SCOPE_KEY]],
      );
      if (is_array($row2) && !empty($row2["token_plain"]) && !empty($row2["token_hash"])) {
        return [
          "plainToken" => (string) $row2["token_plain"],
          "tokenHash" => (string) $row2["token_hash"],
        ];
      }
      throw $e;
    }
  }

  /**
   * @return array{plainToken:string,tokenHash:string}
   */
  public static function regenerate(object $db): array
  {
    if (!PublicConcertsFeedTokenSchema::ensureTable($db)) {
      throw new RuntimeException("public_concerts_feed_token_schema_failed");
    }

    $db->execute("DELETE FROM public_concerts_feed_token WHERE scope_key = ?", [["s", self::SCOPE_KEY]]);

    return self::insertFreshToken($db);
  }

  public static function isValidToken(string $plainToken, object $db): bool
  {
    $plainToken = strtolower(trim($plainToken));
    if ($plainToken === "" || strlen($plainToken) !== 64 || !ctype_xdigit($plainToken)) {
      return false;
    }
    if (!PublicConcertsFeedTokenSchema::ensureTable($db)) {
      return false;
    }

    $tokenHash = hash("sha256", $plainToken, false);
    $row = $db->fetchRow("SELECT id FROM public_concerts_feed_token WHERE scope_key = ? AND token_hash = ? LIMIT 1", [
      ["s", self::SCOPE_KEY],
      ["s", $tokenHash],
    ]);

    return is_array($row) && !empty($row["id"]);
  }

  /**
   * @return array{plainToken:string,tokenHash:string}
   */
  private static function insertFreshToken(object $db): array
  {
    $plain = bin2hex(random_bytes(self::TOKEN_BYTES));
    $tokenHash = hash("sha256", $plain, false);
    $db->execute("INSERT INTO public_concerts_feed_token (scope_key, token_plain, token_hash) VALUES (?, ?, ?)", [
      ["s", self::SCOPE_KEY],
      ["s", $plain],
      ["s", $tokenHash],
    ]);
    return [
      "plainToken" => $plain,
      "tokenHash" => $tokenHash,
    ];
  }
}
