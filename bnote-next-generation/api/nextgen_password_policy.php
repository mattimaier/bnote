<?php
/**
 * Password rules aligned with NextGenRegistration (same regex as legacy Registration).
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

require_once BNOTE_ROOT . "/src/data/regex.php";

final class NextGenPasswordPolicy
{
  /**
   * @return non-empty-string
   */
  public static function passwordPatternRegex(): string
  {
    $sc = Regex::$SPECIALCHARACTERS;
    return "/^[[:alpha:]" . $sc . '0-9\ \.\-\,\;\:\_\+\&\#\'\/\!\$]{6,45}$/';
  }

  public static function passwordsValid(string $pw1, string $pw2): bool
  {
    $re = self::passwordPatternRegex();
    return preg_match($re, $pw1) && preg_match($re, $pw2) && $pw1 === $pw2;
  }
}
