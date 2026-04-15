<?php
/**
 * HMAC auth for public reminder endpoint.
 */
declare(strict_types=1);

require_once __DIR__ . "/ReminderSchema.php";
require_once __DIR__ . "/MailEnv.php";

final class ReminderEndpointAuth
{
  /**
   * @return array{timestamp:int,nonce:string}
   */
  public static function verify(object $db, string $method, string $path, string $body): array
  {
    $secret = MailEnv::reminderSharedSecret();
    if ($secret === "") {
      throw new RuntimeException("reminder_secret_missing");
    }

    if (strtoupper($method) !== "POST") {
      throw new RuntimeException("method_not_allowed");
    }

    if (!ReminderSchema::ensureTables($db)) {
      throw new RuntimeException("reminder_schema_unavailable");
    }

    $headers = self::headersLower();
    $tsRaw = trim((string) ($headers["x-reminder-timestamp"] ?? ""));
    $nonce = trim((string) ($headers["x-reminder-nonce"] ?? ""));
    $signature = strtolower(trim((string) ($headers["x-reminder-signature"] ?? "")));

    if ($tsRaw === "" || $nonce === "" || $signature === "") {
      throw new RuntimeException("missing_signature_headers");
    }
    if (!preg_match('/^\d{10}$/', $tsRaw)) {
      throw new RuntimeException("invalid_timestamp");
    }
    if (!preg_match('/^[a-zA-Z0-9._:-]{16,128}$/', $nonce)) {
      throw new RuntimeException("invalid_nonce");
    }
    if (!preg_match('/^[a-f0-9]{64}$/', $signature)) {
      throw new RuntimeException("invalid_signature_format");
    }

    $ts = (int) $tsRaw;
    $now = time();
    $skew = MailEnv::reminderAllowedSkewSeconds();
    if (abs($now - $ts) > $skew) {
      throw new RuntimeException("timestamp_out_of_window");
    }

    $canonical = strtoupper($method) . "|" . $path . "|" . $tsRaw . "|" . $nonce . "|" . $body;
    $expected = hash_hmac("sha256", $canonical, $secret);
    if (!hash_equals($expected, $signature)) {
      throw new RuntimeException("signature_mismatch");
    }

    ReminderSchema::purgeExpiredNonces($db);
    $recent = ReminderSchema::countRecentNonceRequests($db, 60);
    if ($recent > 120) {
      throw new RuntimeException("rate_limited");
    }

    $nonceHash = hash("sha256", "reminder_nonce|" . $nonce);
    if (!ReminderSchema::rememberNonce($db, $nonceHash, $skew * 2)) {
      throw new RuntimeException("nonce_replayed");
    }

    return [
      "timestamp" => $ts,
      "nonce" => $nonce,
    ];
  }

  /** @return array<string,string> */
  private static function headersLower(): array
  {
    $out = [];
    $source = [];
    if (function_exists("getallheaders")) {
      $hdr = getallheaders();
      if (is_array($hdr)) {
        $source = $hdr;
      }
    }
    if (count($source) === 0) {
      foreach ($_SERVER as $k => $v) {
        if (!is_string($k) || strpos($k, "HTTP_") !== 0 || !is_string($v)) {
          continue;
        }
        $name = strtolower(str_replace("_", "-", substr($k, 5)));
        $source[$name] = $v;
      }
    }
    foreach ($source as $k => $v) {
      if (is_string($k) && is_string($v)) {
        $out[strtolower($k)] = $v;
      }
    }
    return $out;
  }
}
