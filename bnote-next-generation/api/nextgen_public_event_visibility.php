<?php
/**
 * Public visibility read/write helpers for Next Gen events.
 */
declare(strict_types=1);

require_once __DIR__ . "/nextgen_public_event_visibility_schema.php";

final class NextGenPublicEventVisibility
{
  public static function isPublished(object $db, string $otype, int $oid): bool
  {
    $otype = self::normalizeOtype($otype);
    if ($otype === "" || $oid < 1) {
      return false;
    }
    if (!NextGenPublicEventVisibilitySchema::ensureTable($db)) {
      // Keep existing behavior intact when the sidecar table is unavailable.
      return true;
    }

    $row = $db->fetchRow(
      "SELECT is_published FROM nextgen_public_event_visibility WHERE otype = ? AND oid = ? LIMIT 1",
      [["s", $otype], ["i", $oid]],
    );
    if (!is_array($row)) {
      // No explicit sidecar row means visible in public feed.
      return true;
    }
    return (int) ($row["is_published"] ?? 0) === 1;
  }

  public static function setPublished(
    object $db,
    string $otype,
    int $oid,
    bool $isPublished,
    ?int $publishedByUserId = null,
  ): bool {
    $otype = self::normalizeOtype($otype);
    if ($otype === "" || $oid < 1) {
      return false;
    }
    if (!NextGenPublicEventVisibilitySchema::ensureTable($db)) {
      return false;
    }

    $by = $publishedByUserId !== null && $publishedByUserId > 0 ? $publishedByUserId : null;
    if ($isPublished) {
      $db->execute(
        'INSERT INTO nextgen_public_event_visibility
                    (otype, oid, is_published, published_at, published_by)
                 VALUES (?, ?, 1, UTC_TIMESTAMP(), ?)
                 ON DUPLICATE KEY UPDATE
                    is_published = 1,
                    published_at = UTC_TIMESTAMP(),
                    published_by = VALUES(published_by)',
        [["s", $otype], ["i", $oid], ["i", $by ?? 0]],
      );
      if ($by === null) {
        $db->execute("UPDATE nextgen_public_event_visibility SET published_by = NULL WHERE otype = ? AND oid = ?", [
          ["s", $otype],
          ["i", $oid],
        ]);
      }
      return true;
    }

    $db->execute(
      'INSERT INTO nextgen_public_event_visibility
                (otype, oid, is_published, published_at, published_by)
             VALUES (?, ?, 0, NULL, NULL)
             ON DUPLICATE KEY UPDATE
                is_published = 0,
                published_at = NULL,
                published_by = NULL',
      [["s", $otype], ["i", $oid]],
    );
    return true;
  }

  public static function deleteForEvent(object $db, string $otype, int $oid): void
  {
    $otype = self::normalizeOtype($otype);
    if ($otype === "" || $oid < 1) {
      return;
    }
    if (!NextGenPublicEventVisibilitySchema::ensureTable($db)) {
      return;
    }
    $db->execute("DELETE FROM nextgen_public_event_visibility WHERE otype = ? AND oid = ?", [
      ["s", $otype],
      ["i", $oid],
    ]);
  }

  /**
   * @return list<array{id:int,title:string,begin:string,end:string,locationName:string,status:string}>
   */
  public static function listPublishedConcerts(object $db): array
  {
    if (!NextGenPublicEventVisibilitySchema::ensureTable($db)) {
      return [];
    }

    $sel = $db->getSelection(
      'SELECT c.id, c.title, c.begin, c.end, c.status, l.name AS location_name
             FROM concert c
             LEFT JOIN location l ON c.location = l.id
             JOIN nextgen_public_event_visibility v ON v.otype = ? AND v.oid = c.id AND v.is_published = 1
             WHERE LOWER(TRIM(COALESCE(c.status, ""))) NOT IN ("cancelled", "hidden")
             ORDER BY c.begin ASC, c.id ASC',
      [["s", "C"]],
    );

    if (!is_array($sel) || count($sel) < 2) {
      return [];
    }

    $out = [];
    for ($i = 1; $i < count($sel); $i++) {
      $row = $sel[$i];
      $out[] = [
        "id" => (int) ($row["id"] ?? 0),
        "title" => trim((string) ($row["title"] ?? "")),
        "begin" => trim((string) ($row["begin"] ?? "")),
        "end" => trim((string) ($row["end"] ?? "")),
        "locationName" => trim((string) ($row["location_name"] ?? "")),
        "status" => trim((string) ($row["status"] ?? "")),
      ];
    }
    return $out;
  }

  private static function normalizeOtype(string $otype): string
  {
    $t = strtoupper(trim($otype));
    if ($t === "R" || $t === "C") {
      return $t;
    }
    return "";
  }
}
