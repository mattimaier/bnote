<?php
/**
 * Deep links to entity discussion in the Next Gen app (mirrors frontend lib/entities/paths.ts).
 */
declare(strict_types=1);

require_once __DIR__ . "/MailEnv.php";

final class CommentDiscussionEntityUrl
{
  /**
   * Absolute URL to open the entity with comments in focus, or '' if BNOTE_NEXT_GENERATION_PUBLIC_URL is not set.
   */
  public static function openDiscussionUrl(string $otype, int $oid): string
  {
    $base = MailEnv::nextgenPublicBaseUrl();
    if ($base === "") {
      return "";
    }
    $otype = strtoupper($otype);
    if ($otype === "V") {
      return $base .
        "/entity?" .
        http_build_query([
          "type" => "vote",
          "id" => (string) $oid,
          "focus" => "comments",
        ]);
    }
    if ($otype === "R") {
      $type = "rehearsal";
    } elseif ($otype === "C") {
      $type = "concert";
    } else {
      return "";
    }

    return $base .
      "/entity?" .
      http_build_query([
        "type" => $type,
        "id" => (string) $oid,
        "focus" => "comments",
      ]);
  }

  /**
   * Absolute URL to open the entity (no focus), or '' if public base URL is not set.
   */
  public static function openEntityUrl(string $otype, int $oid): string
  {
    $base = MailEnv::nextgenPublicBaseUrl();
    if ($base === "") {
      return "";
    }
    $otype = strtoupper($otype);
    if ($otype === "V") {
      return $base .
        "/entity?" .
        http_build_query([
          "type" => "vote",
          "id" => (string) $oid,
        ]);
    }
    if ($otype === "R") {
      $type = "rehearsal";
    } elseif ($otype === "C") {
      $type = "concert";
    } else {
      return "";
    }

    return $base .
      "/entity?" .
      http_build_query([
        "type" => $type,
        "id" => (string) $oid,
      ]);
  }
}
