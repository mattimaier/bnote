<?php
/**
 * Server-side mail strings from bnote-next-generation/lang/&lt;locale&gt;.json (flat keys).
 */
declare(strict_types=1);

final class MailI18n
{
  /** @var array<string, array<string, string>> */
  private static array $cache = [];

  public static function t(string $key, string $locale): string
  {
    $loc = self::normalizeLocale($locale);
    if (!isset(self::$cache[$loc])) {
      self::$cache[$loc] = self::loadFile($loc);
    }
    if (isset(self::$cache[$loc][$key])) {
      return self::$cache[$loc][$key];
    }
    if ($loc !== "en" && !isset(self::$cache["en"])) {
      self::$cache["en"] = self::loadFile("en");
    }
    return self::$cache["en"][$key] ?? $key;
  }

  public static function interpolate(string $template, array $vars): string
  {
    $repl = [];
    foreach ($vars as $k => $v) {
      $repl["{" . $k . "}"] = (string) $v;
    }
    return strtr($template, $repl);
  }

  private static function normalizeLocale(string $locale): string
  {
    $locale = strtolower(explode("-", $locale)[0] ?? "en");
    return preg_match('/^[a-z]{2}$/', $locale) ? $locale : "en";
  }

  /** @return array<string, string> */
  private static function loadFile(string $loc): array
  {
    // api/mail → bnote-next-generation root is two levels up
    $root = dirname(__DIR__, 2);
    $path = $root . "/lang/" . $loc . ".json";
    if (!is_readable($path)) {
      return [];
    }
    $json = file_get_contents($path);
    if ($json === false) {
      return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
  }
}
