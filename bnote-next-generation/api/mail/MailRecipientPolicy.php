<?php
/**
 * Rules for outbound mail recipients (non-deliverable / reserved domains).
 */

class MailRecipientPolicy
{
  /**
   * @param string|null $email
   * @return bool True if outbound mail must not be sent to this address.
   */
  public static function shouldSkipOutboundDelivery($email)
  {
    if ($email === null || $email === "") {
      return false;
    }
    $email = strtolower(trim($email));
    $pos = strrpos($email, "@");
    if ($pos === false) {
      return false;
    }
    $host = substr($email, $pos + 1);
    if ($host === "example.com") {
      return true;
    }
    $hlen = strlen($host);
    if ($hlen > 12 && substr($host, -12) === ".example.com") {
      return true;
    }
    return false;
  }

  /**
   * Skip delivery when the address belongs to BNote user account(s) and all matching
   * accounts are inactive. If at least one matching active user exists, allow delivery.
   * If no user matches, allow delivery (contact-only recipients).
   *
   * @param string|null $email
   * @param mixed $system_data
   */
  public static function shouldSkipInactiveUserRecipient($email, $system_data): bool
  {
    $normalized = strtolower(trim((string) $email));
    if ($normalized === "" || !$system_data || !isset($system_data->dbcon)) {
      return false;
    }
    if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
      return false;
    }

    try {
      $params = [["s", $normalized], ["s", $normalized]];
      $activeUid = $system_data->dbcon->colValue(
        'SELECT u.id
                 FROM user u
                 LEFT JOIN contact c ON c.id = u.contact
                 WHERE (LOWER(TRIM(COALESCE(c.email, \'\'))) = ? OR LOWER(TRIM(COALESCE(u.login, \'\'))) = ?)
                   AND u.isActive = 1
                 LIMIT 1',
        "id",
        $params,
      );
      if ($activeUid !== null) {
        return false;
      }

      $anyUid = $system_data->dbcon->colValue(
        'SELECT u.id
                 FROM user u
                 LEFT JOIN contact c ON c.id = u.contact
                 WHERE (LOWER(TRIM(COALESCE(c.email, \'\'))) = ? OR LOWER(TRIM(COALESCE(u.login, \'\'))) = ?)
                 LIMIT 1',
        "id",
        $params,
      );
      return $anyUid !== null;
    } catch (Throwable $e) {
      error_log("MailRecipientPolicy::shouldSkipInactiveUserRecipient " . $e->getMessage());
      return false;
    }
  }
}
