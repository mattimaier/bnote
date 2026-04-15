<?php
/**
 * SMTP / sender configuration for Next Gen mail (env-first).
 */
declare(strict_types=1);

final class MailEnv
{
  /** @var null|array<string,string> */
  private static $htaccessSetEnvCache = null;
  /** @var null|array<string,string> */
  private static $localConfigCache = null;
  /** @var null|array<string,string> */
  private static $deployEnvCache = null;

  public static function host(): string
  {
    return self::getenvTrim("MAIL_HOST");
  }

  public static function port(): int
  {
    $p = self::getenvTrim("MAIL_PORT");
    return $p !== "" ? max(1, (int) $p) : 587;
  }

  public static function username(): string
  {
    return self::getenvTrim("MAIL_USERNAME");
  }

  public static function password(): string
  {
    return self::getenvRaw("MAIL_PASSWORD");
  }

  /** '', 'tls', or 'ssl' */
  public static function encryption(): string
  {
    $e = strtolower(self::getenvTrim("MAIL_ENCRYPTION"));
    return in_array($e, ["tls", "ssl"], true) ? $e : "";
  }

  public static function fromAddress(): string
  {
    return self::getenvTrim("MAIL_FROM_ADDRESS");
  }

  public static function fromName(): string
  {
    return self::getenvTrim("MAIL_FROM_NAME");
  }

  /**
   * Public base URL of the Next Gen SPA (no trailing slash), e.g. https://example.com/bnote-next-generation
   */
  public static function nextgenPublicBaseUrl(): string
  {
    $full = trim(self::getenvFirst(["BNOTE_NEXT_GENERATION_PUBLIC_URL"]));
    if ($full !== "") {
      return rtrim($full, "/");
    }
    if (self::isLocalDevContext()) {
      $inferredOrigin = self::inferRequestOrigin();
      if ($inferredOrigin !== "") {
        $path = self::nextgenAppPathPrefix();
        return rtrim($inferredOrigin, "/") . ($path !== "" ? $path : "");
      }
    }
    $origin = trim(self::getenvFirst(["BNOTE_NEXT_GENERATION_PUBLIC_ORIGIN"]));
    $path = self::getenvTrim("NEXT_PUBLIC_BASE_PATH");
    if ($origin !== "" && $path !== "") {
      return rtrim($origin, "/") . "/" . trim($path, "/");
    }
    return "";
  }

  /**
   * Next.js base path only (leading slash, no trailing slash), for same-origin links when no absolute URL is configured.
   * Matches frontend default in next.config.ts when NEXT_PUBLIC_BASE_PATH is unset in PHP.
   */
  public static function nextgenAppPathPrefix(): string
  {
    $v = self::getenvRaw("NEXT_PUBLIC_BASE_PATH");
    if ($v === "") {
      return "/bnote-next-generation";
    }
    $p = trim($v);
    if ($p === "") {
      return "";
    }
    return "/" . trim($p, "/");
  }

  /**
   * Root-relative password reset URL (always usable on the same host as the SPA).
   */
  public static function nextgenPasswordResetRelativeUrl(string $plainToken): string
  {
    $prefix = self::nextgenAppPathPrefix();
    $suffix = "/reset-password/confirm/?token=" . rawurlencode($plainToken);
    return ($prefix === "" ? "" : $prefix) . $suffix;
  }

  /**
   * Absolute URL for participation magic-link landing page (token + choice in query).
   */
  public static function nextgenParticipationRespondAbsoluteUrl(string $plainToken, string $choice): string
  {
    $base = self::nextgenPublicBaseUrl();
    if ($base === "") {
      return "";
    }
    return $base .
      "/participation/respond/?" .
      http_build_query([
        "token" => $plainToken,
        "choice" => $choice,
      ]);
  }

  /** Root-relative participation respond URL (same host as SPA). */
  public static function nextgenParticipationRespondRelativeUrl(string $plainToken, string $choice): string
  {
    $prefix = self::nextgenAppPathPrefix();
    $suffix =
      "/participation/respond/?" .
      http_build_query([
        "token" => $plainToken,
        "choice" => $choice,
      ]);
    return ($prefix === "" ? "" : $prefix) . $suffix;
  }

  /**
   * Absolute task deep link for mail CTA, or '' if not configured.
   */
  public static function nextgenTaskEntityAbsoluteUrl(int $taskId): string
  {
    $base = self::nextgenPublicBaseUrl();
    if ($base === "") {
      return "";
    }
    return $base .
      "/entity?" .
      http_build_query([
        "type" => "task",
        "id" => (string) $taskId,
      ]);
  }

  public static function nextgenTaskEntityRelativeUrl(int $taskId): string
  {
    $prefix = self::nextgenAppPathPrefix();
    $suffix =
      "/entity?" .
      http_build_query([
        "type" => "task",
        "id" => (string) $taskId,
      ]);
    return ($prefix === "" ? "" : $prefix) . $suffix;
  }

  /**
   * Delay between consecutive sends when using {@see NextGenMailer::sendBulk()} (comment / admin fan-out).
   * Env `BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS`: milliseconds, 0 = no pause. Unset defaults to 100 ms to reduce SMTP rate limits.
   */
  public static function bulkSendDelayMicroseconds(): int
  {
    $raw = trim(self::getenvFirst(["BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS"]));
    if ($raw === "") {
      return 100_000;
    }
    $s = $raw;
    if ($s === "") {
      return 100_000;
    }
    $ms = (int) $s;
    if ($ms < 0) {
      $ms = 0;
    }
    if ($ms > 10_000) {
      $ms = 10_000;
    }

    return $ms * 1000;
  }

  /** Shared HMAC secret for external reminder scheduler endpoint. */
  public static function reminderSharedSecret(): string
  {
    return self::getenvFirst(["BNOTE_NEXT_GENERATION_REMINDER_SECRET"]);
  }

  /** Allowed absolute timestamp skew in seconds for signed reminder endpoint requests. */
  public static function reminderAllowedSkewSeconds(): int
  {
    $raw = trim(self::getenvFirst(["BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS"]));
    if ($raw === "") {
      return 300;
    }
    $n = (int) $raw;
    if ($n < 60) {
      return 60;
    }
    if ($n > 3600) {
      return 3600;
    }
    return $n;
  }

  private static function getenvTrim(string $key): string
  {
    return trim(self::getenvRaw($key));
  }

  /**
   * Return first non-empty value for key candidates.
   *
   * @param list<string> $keys
   */
  private static function getenvFirst(array $keys): string
  {
    foreach ($keys as $k) {
      $v = self::getenvRaw($k);
      if ($v !== "") {
        return $v;
      }
    }
    return "";
  }

  /**
   * Reads a variable from common PHP runtime sources in descending priority.
   * Some shared-hosting CGI/FastCGI setups expose SetEnv values via $_SERVER (or REDIRECT_*) instead of getenv().
   */
  private static function getenvRaw(string $key): string
  {
    $v = getenv($key);
    if (is_string($v) && $v !== "") {
      return $v;
    }
    if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== "") {
      return $_SERVER[$key];
    }
    if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== "") {
      return $_ENV[$key];
    }
    $redirectKey = "REDIRECT_" . $key;
    if (isset($_SERVER[$redirectKey]) && is_string($_SERVER[$redirectKey]) && $_SERVER[$redirectKey] !== "") {
      return $_SERVER[$redirectKey];
    }
    if (isset($_ENV[$redirectKey]) && is_string($_ENV[$redirectKey]) && $_ENV[$redirectKey] !== "") {
      return $_ENV[$redirectKey];
    }
    $fromLocalConfig = self::getFromLocalConfig($key);
    if ($fromLocalConfig !== "") {
      return $fromLocalConfig;
    }
    $fromDeployEnv = self::getFromDeployEnv($key);
    if ($fromDeployEnv !== "") {
      return $fromDeployEnv;
    }
    $fromHtaccess = self::getSetEnvFromHtaccess($key);
    if ($fromHtaccess !== "") {
      return $fromHtaccess;
    }
    return "";
  }

  private static function getSetEnvFromHtaccess(string $key): string
  {
    $all = self::getAllSetEnvFromHtaccess();
    return isset($all[$key]) ? $all[$key] : "";
  }

  /**
   * Parse SetEnv lines from deployed .htaccess files.
   *
   * Search order:
   * 1) api/.htaccess
   * 2) ../.htaccess (app root)
   *
   * Later files do not overwrite already parsed keys so api/.htaccess has priority.
   *
   * @return array<string,string>
   */
  private static function getAllSetEnvFromHtaccess(): array
  {
    if (is_array(self::$htaccessSetEnvCache)) {
      return self::$htaccessSetEnvCache;
    }

    $map = [];
    $files = [dirname(__DIR__) . "/.htaccess", dirname(__DIR__) . "/../.htaccess"];

    foreach ($files as $file) {
      if (!is_file($file) || !is_readable($file)) {
        continue;
      }
      $content = @file_get_contents($file);
      if (!is_string($content) || $content === "") {
        continue;
      }

      $lines = preg_split("/\R/", $content);
      if (!is_array($lines)) {
        continue;
      }

      foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === "" || strpos($line, "#") === 0) {
          continue;
        }

        if (!preg_match('/^SetEnv\s+([A-Z0-9_]+)\s+(.+)$/i', $line, $m)) {
          continue;
        }

        $name = strtoupper(trim((string) $m[1]));
        if ($name === "" || isset($map[$name])) {
          continue;
        }

        $raw = trim((string) $m[2]);
        // Strip inline comments for unquoted values.
        if ($raw !== "" && $raw[0] !== '"' && $raw[0] !== "'") {
          $hashPos = strpos($raw, "#");
          if ($hashPos !== false) {
            $raw = rtrim(substr($raw, 0, $hashPos));
          }
        }

        // Remove matching surrounding quotes.
        if (strlen($raw) >= 2) {
          $first = $raw[0];
          $last = $raw[strlen($raw) - 1];
          if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $raw = substr($raw, 1, -1);
          }
        }

        $map[$name] = trim($raw);
      }
    }

    self::$htaccessSetEnvCache = $map;
    return $map;
  }

  private static function getFromLocalConfig(string $key): string
  {
    $cfg = self::loadLocalMailConfig();
    return isset($cfg[$key]) ? $cfg[$key] : "";
  }

  private static function getFromDeployEnv(string $key): string
  {
    $cfg = self::loadDeployEnvConfig();
    return isset($cfg[$key]) ? $cfg[$key] : "";
  }

  /**
   * Optional file-based mail configuration fallback for shared hosting where env passthrough is unavailable.
   *
   * Expected file: api/config/mail.local.php
   * Example return value:
   *   return ['MAIL_HOST' => 'smtp.strato.de', ...];
   *
   * @return array<string,string>
   */
  private static function loadLocalMailConfig(): array
  {
    if (is_array(self::$localConfigCache)) {
      return self::$localConfigCache;
    }

    $path = dirname(__DIR__) . "/config/mail.local.php";
    if (!is_file($path) || !is_readable($path)) {
      self::$localConfigCache = [];
      return self::$localConfigCache;
    }

    $data = require $path;
    if (!is_array($data)) {
      self::$localConfigCache = [];
      return self::$localConfigCache;
    }

    $allowed = [
      "MAIL_HOST",
      "MAIL_PORT",
      "MAIL_ENCRYPTION",
      "MAIL_USERNAME",
      "MAIL_PASSWORD",
      "MAIL_FROM_ADDRESS",
      "MAIL_FROM_NAME",
      "BNOTE_NEXT_GENERATION_PUBLIC_URL",
      "BNOTE_NEXT_GENERATION_PUBLIC_ORIGIN",
      "BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS",
      "BNOTE_NEXT_GENERATION_REMINDER_SECRET",
      "BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS",
      "NEXT_PUBLIC_BASE_PATH",
    ];
    $allowedSet = array_flip($allowed);

    $out = [];
    foreach ($data as $k => $v) {
      if (!is_string($k) || !isset($allowedSet[$k])) {
        continue;
      }
      if (!is_string($v) && !is_numeric($v)) {
        continue;
      }
      $out[$k] = trim((string) $v);
    }

    self::$localConfigCache = $out;
    return self::$localConfigCache;
  }

  /**
   * Optional fallback: parse bnote-next-generation/.deploy.env for local/dev runs.
   *
   * @return array<string,string>
   */
  private static function loadDeployEnvConfig(): array
  {
    if (is_array(self::$deployEnvCache)) {
      return self::$deployEnvCache;
    }

    $path = dirname(__DIR__, 2) . "/.deploy.env";
    if (!is_file($path) || !is_readable($path)) {
      self::$deployEnvCache = [];
      return self::$deployEnvCache;
    }
    $content = @file_get_contents($path);
    if (!is_string($content) || $content === "") {
      self::$deployEnvCache = [];
      return self::$deployEnvCache;
    }

    $allowed = [
      "MAIL_HOST",
      "MAIL_PORT",
      "MAIL_ENCRYPTION",
      "MAIL_USERNAME",
      "MAIL_PASSWORD",
      "MAIL_FROM_ADDRESS",
      "MAIL_FROM_NAME",
      "BNOTE_NEXT_GENERATION_PUBLIC_URL",
      "BNOTE_NEXT_GENERATION_PUBLIC_ORIGIN",
      "BNOTE_NEXT_GENERATION_MAIL_BULK_DELAY_MS",
      "BNOTE_NEXT_GENERATION_REMINDER_SECRET",
      "BNOTE_NEXT_GENERATION_REMINDER_ALLOWED_SKEW_SECONDS",
      "NEXT_PUBLIC_BASE_PATH",
    ];
    $allowedSet = array_flip($allowed);

    $out = [];
    $lines = preg_split("/\R/", $content);
    if (!is_array($lines)) {
      self::$deployEnvCache = [];
      return self::$deployEnvCache;
    }
    foreach ($lines as $line) {
      $line = trim((string) $line);
      if ($line === "" || strpos($line, "#") === 0) {
        continue;
      }
      if (!preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/', $line, $m)) {
        continue;
      }
      $key = strtoupper(trim((string) $m[1]));
      if (!isset($allowedSet[$key])) {
        continue;
      }
      $val = trim((string) $m[2]);
      if (strlen($val) >= 2) {
        $first = $val[0];
        $last = $val[strlen($val) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
          $val = substr($val, 1, -1);
        }
      }
      $out[$key] = trim($val);
    }

    if (self::isLocalDevContext()) {
      // In local dev, keep MAIL_* fallbacks from .deploy.env, but never reuse
      // deployment public URLs for link generation inside locally sent emails.
      unset($out["BNOTE_NEXT_GENERATION_PUBLIC_URL"], $out["BNOTE_NEXT_GENERATION_PUBLIC_ORIGIN"]);
    }

    self::$deployEnvCache = $out;
    return self::$deployEnvCache;
  }

  private static function inferRequestOrigin(): string
  {
    $originRaw = trim((string) ($_SERVER["HTTP_ORIGIN"] ?? ""));
    $origin = self::normalizeHttpOrigin($originRaw);
    if ($origin !== "") {
      return $origin;
    }

    $refererRaw = trim((string) ($_SERVER["HTTP_REFERER"] ?? ""));
    if ($refererRaw !== "") {
      $scheme = strtolower((string) parse_url($refererRaw, PHP_URL_SCHEME));
      $host = (string) parse_url($refererRaw, PHP_URL_HOST);
      $port = parse_url($refererRaw, PHP_URL_PORT);
      if (($scheme === "http" || $scheme === "https") && $host !== "") {
        $p = is_int($port) ? ":" . $port : "";
        return $scheme . "://" . $host . $p;
      }
    }

    $hostRaw = trim((string) ($_SERVER["HTTP_HOST"] ?? ""));
    if ($hostRaw === "") {
      return "";
    }
    $scheme = self::isHttpsRequest() ? "https" : "http";
    return $scheme . "://" . $hostRaw;
  }

  private static function normalizeHttpOrigin(string $origin): string
  {
    if ($origin === "") {
      return "";
    }
    $scheme = strtolower((string) parse_url($origin, PHP_URL_SCHEME));
    $host = (string) parse_url($origin, PHP_URL_HOST);
    $port = parse_url($origin, PHP_URL_PORT);
    if (($scheme !== "http" && $scheme !== "https") || $host === "") {
      return "";
    }
    $p = is_int($port) ? ":" . $port : "";
    return $scheme . "://" . $host . $p;
  }

  private static function isHttpsRequest(): bool
  {
    $https = strtolower((string) ($_SERVER["HTTPS"] ?? ""));
    if ($https === "on" || $https === "1") {
      return true;
    }
    $forwardedProto = strtolower((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? ""));
    if ($forwardedProto === "https") {
      return true;
    }
    $serverPort = (string) ($_SERVER["SERVER_PORT"] ?? "");
    return $serverPort === "443";
  }

  private static function isLocalDevContext(): bool
  {
    $host = strtolower((string) ($_SERVER["HTTP_HOST"] ?? ""));
    if ($host !== "") {
      $host = preg_replace('/:\d+$/', "", $host) ?? $host;
    }
    if ($host === "localhost" || $host === "127.0.0.1" || $host === "::1") {
      return true;
    }

    $serverAddr = strtolower((string) ($_SERVER["SERVER_ADDR"] ?? ""));
    if ($serverAddr === "127.0.0.1" || $serverAddr === "::1") {
      return true;
    }

    $remoteAddr = strtolower((string) ($_SERVER["REMOTE_ADDR"] ?? ""));
    if ($remoteAddr === "127.0.0.1" || $remoteAddr === "::1") {
      return true;
    }

    return false;
  }
}
