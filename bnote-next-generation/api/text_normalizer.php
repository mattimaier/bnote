<?php
/**
 * BNote Next Generation - Text Normalizer
 *
 * Normalizes common mojibake and HTML entity artifacts in user-facing text.
 * The logic is pattern-gated to avoid changing already-correct UTF-8 strings.
 */

class TextNormalizer
{
  /**
   * Characters/sequences commonly seen in UTF-8/latin1 mojibake.
   * @var array<string, string>
   */
  private static $mojibakeMap = [
    "Ã„" => "Ä",
    "Ã¤" => "ä",
    "Ã–" => "Ö",
    "Ã¶" => "ö",
    "Ãœ" => "Ü",
    "Ã¼" => "ü",
    "ÃŸ" => "ß",
    "â‚¬" => "€",
    "â€“" => "–",
    "â€”" => "—",
    "â€¦" => "…",
    "â€ž" => "„",
    "â€œ" => "“",
    "â€�" => "”",
    "â€˜" => "‘",
    "â€™" => "’",
    "Â " => " ",
    "Â" => "",
  ];

  private static function containsSuspiciousPattern($value)
  {
    return preg_match("/(Ã.|â.|Â|&[A-Za-z]+;)/u", $value) === 1;
  }

  public static function normalizeText($value, &$changed = false, $decodeEntities = true)
  {
    if (!is_string($value) || $value === "" || !self::containsSuspiciousPattern($value)) {
      return $value;
    }

    $original = $value;

    if ($decodeEntities && strpos($value, "&") !== false) {
      $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, "UTF-8");
      if (is_string($decoded) && $decoded !== "") {
        $value = $decoded;
      }
    }

    $value = strtr($value, self::$mojibakeMap);
    if ($value !== $original) {
      $changed = true;
    }

    return $value;
  }

  public static function normalizeFieldsRecursive($payload, $fields, &$stats, $decodeEntities = true)
  {
    if (!is_array($stats)) {
      $stats = ["count" => 0, "samples" => []];
    }

    if (is_array($payload)) {
      $isAssoc = array_keys($payload) !== range(0, count($payload) - 1);
      foreach ($payload as $key => $value) {
        if ($isAssoc && is_string($value) && in_array($key, $fields, true)) {
          $changed = false;
          $normalized = self::normalizeText($value, $changed, $decodeEntities);
          $payload[$key] = $normalized;
          if ($changed) {
            $stats["count"]++;
            if (count($stats["samples"]) < 5) {
              $stats["samples"][] = (string) $key;
            }
          }
          continue;
        }

        if (is_array($value)) {
          $payload[$key] = self::normalizeFieldsRecursive($value, $fields, $stats, $decodeEntities);
        }
      }
    }

    return $payload;
  }

  public static function normalizeAllStringsRecursive($payload, &$stats, $decodeEntities = true)
  {
    if (!is_array($stats)) {
      $stats = ["count" => 0, "samples" => []];
    }

    if (is_array($payload)) {
      foreach ($payload as $key => $value) {
        if (is_string($value)) {
          $changed = false;
          $normalized = self::normalizeText($value, $changed, $decodeEntities);
          $payload[$key] = $normalized;
          if ($changed) {
            $stats["count"]++;
            if (count($stats["samples"]) < 5) {
              $stats["samples"][] = is_string($key) ? $key : "idx:" . strval($key);
            }
          }
          continue;
        }

        if (is_array($value)) {
          $payload[$key] = self::normalizeAllStringsRecursive($value, $stats, $decodeEntities);
        }
      }
    }

    return $payload;
  }

  public static function logStats($module, $action, $stats)
  {
    $count = isset($stats["count"]) ? intval($stats["count"]) : 0;
    if ($count <= 0) {
      return;
    }

    $samples = isset($stats["samples"]) && is_array($stats["samples"]) ? implode(",", $stats["samples"]) : "";

    error_log(
      "TextNormalizer: module=" . $module . " action=" . $action . " normalized=" . $count . " fields=" . $samples,
    );

    if (class_exists("ApiLogger") && method_exists("ApiLogger", "logNormalization")) {
      ApiLogger::logNormalization($module, $action, $count, $stats["samples"] ?? []);
    }
  }
}
