<?php
declare(strict_types=1);

require_once __DIR__ . "/MailI18n.php";
require_once __DIR__ . "/MailSubject.php";
require_once __DIR__ . "/MailLocaleDateTime.php";
require_once __DIR__ . "/MailRecipientPolicy.php";
require_once __DIR__ . "/NextGenMailer.php";
require_once __DIR__ . "/NextGenMailPolicy.php";
require_once __DIR__ . "/MailEnv.php";
require_once __DIR__ . "/MailPreviewHtml.php";
require_once __DIR__ . "/CommentDiscussionEntitySummary.php";
require_once __DIR__ . "/MailRecipientDirectory.php";
require_once __DIR__ . "/builders/EventInfoMailBuilder.php";
require_once __DIR__ . "/CommentDiscussionEntityUrl.php";

final class EventInfoMailService
{
  /**
   * @param 'R'|'C' $otype
   * @return array{
   *   recipients:list<array{id:int,name:string,email:string}>,
   *   additionalContacts:list<array{id:int,name:string,email:string}>,
   *   declinedRecipientIds:list<int>,
   *   fromEmail:string,
   *   toEmail:string,
   *   selectedRecipientIds:list<int>,
   *   subject:string,
   *   body:string
   * }
   */
  public static function draft($system_data, string $otype, int $oid, string $locale, string $senderName): array
  {
    $event = self::loadEventData($system_data, $otype, $oid, $locale);
    $recipients = self::loadRecipients($system_data, $otype, $oid);
    $additionalContacts = self::loadAdditionalContacts($system_data, array_map(fn($r) => (int) $r["id"], $recipients));
    $declinedRecipientIds = self::loadDeclinedRecipientIds($system_data, $otype, $oid);
    $selectedIds = array_map(fn($r) => (int) $r["id"], $recipients);
    $body = self::defaultBody($locale, $event, $senderName);
    $systemFromEmail = self::resolveFromEmail();

    return [
      "recipients" => $recipients,
      "additionalContacts" => $additionalContacts,
      "declinedRecipientIds" => $declinedRecipientIds,
      "fromEmail" => $systemFromEmail,
      "toEmail" => $systemFromEmail,
      "selectedRecipientIds" => $selectedIds,
      "subject" => self::defaultSubject($locale, $event, $system_data),
      "body" => $body,
    ];
  }

  /**
   * @param 'R'|'C' $otype
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   */
  public static function previewHtml(
    $system_data,
    string $otype,
    int $oid,
    string $locale,
    array $recipientIds,
    array $manualEmails,
    string $subject,
    string $body,
    string $senderName,
  ): string {
    $message = self::buildMessage(
      $system_data,
      $otype,
      $oid,
      $locale,
      $recipientIds,
      $manualEmails,
      $subject,
      $body,
      $senderName,
    );
    return MailPreviewHtml::replaceCidLogoWithDataUri($message->htmlBody);
  }

  /**
   * @param 'R'|'C' $otype
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   * @return array{sent:int,skipped:int}
   */
  public static function send(
    $system_data,
    string $otype,
    int $oid,
    string $locale,
    array $recipientIds,
    array $manualEmails,
    string $subject,
    string $body,
    string $senderName,
  ): array {
    if (self::isBodyEmpty($body)) {
      throw new InvalidArgumentException("mail_body_required");
    }
    $message = self::buildMessage(
      $system_data,
      $otype,
      $oid,
      $locale,
      $recipientIds,
      $manualEmails,
      $subject,
      $body,
      $senderName,
    );
    $deliverableCount = count($message->bcc);
    $requestedCount = count(self::resolveOutgoingRecipients($system_data, $otype, $oid, $recipientIds, $manualEmails));
    if ($deliverableCount < 1) {
      return ["sent" => 0, "skipped" => $requestedCount];
    }

    $ok = NextGenMailer::send($message);
    if (!$ok) {
      throw new RuntimeException("mail_send_failed");
    }

    return ["sent" => $deliverableCount, "skipped" => max(0, $requestedCount - $deliverableCount)];
  }

  private static function isBodyEmpty(string $body): bool
  {
    $trimmed = trim($body);
    if ($trimmed === "") {
      return true;
    }
    if ($trimmed[0] !== "{") {
      return false;
    }
    $parsed = json_decode($trimmed, true);
    if (!is_array($parsed) || !isset($parsed["blocks"]) || !is_array($parsed["blocks"])) {
      return $trimmed === "";
    }
    return count($parsed["blocks"]) < 1;
  }

  /**
   * @param 'R'|'C' $otype
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   */
  private static function buildMessage(
    $system_data,
    string $otype,
    int $oid,
    string $locale,
    array $recipientIds,
    array $manualEmails,
    string $subject,
    string $body,
    string $senderName,
  ): NextGenMailMessage {
    if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
      throw new RuntimeException("mail_disabled");
    }

    $event = self::loadEventData($system_data, $otype, $oid, $locale);
    $outgoing = self::resolveOutgoingRecipients($system_data, $otype, $oid, $recipientIds, $manualEmails);
    $deliverable = [];
    foreach ($outgoing as $email) {
      if (!MailRecipientPolicy::shouldSkipOutboundDelivery($email)) {
        $deliverable[] = $email;
      }
    }

    $ctx = [
      "subject" => trim($subject) !== "" ? trim($subject) : self::defaultSubject($locale, $event, $system_data),
      "eventTitle" => $event["title"],
      "eventTypeLabel" => $event["typeLabel"],
      "eventDateLine" => $event["dateLine"],
      "eventLocationName" => $event["locationName"],
      "eventAddressLine" => $event["addressLine"],
      "eventLink" => $event["eventLink"],
      "detailLines" => $event["detailLines"],
      "entityCard" => $event["entityCard"],
      "customBody" => $body,
      "senderName" => $senderName,
      "senderEmail" => self::resolveFromEmail(),
    ];

    $senderEmail = self::resolveFromEmail();
    if ($senderEmail === "" || MailRecipientPolicy::shouldSkipOutboundDelivery($senderEmail)) {
      throw new InvalidArgumentException("mail_sender_email_required");
    }
    $to = [$senderEmail];
    $bcc = $deliverable;

    return EventInfoMailBuilder::build($system_data, $locale, $ctx, $to, $bcc);
  }

  /**
   * @param 'R'|'C' $otype
   * @return array{
   *   otype:string,
   *   title:string,
   *   typeLabel:string,
   *   dateLine:string,
   *   locationName:string,
   *   addressLine:string,
   *   eventLink:string,
   *   detailLines:list<array{label:string,value:string,href?:string}>,
   *   entityCard:array<string,mixed>|null,
   *   beginDate:string
   * }
   */
  private static function loadEventData($system_data, string $otype, int $oid, string $locale): array
  {
    $otype = strtoupper($otype);
    if ($otype !== "R" && $otype !== "C") {
      throw new InvalidArgumentException("invalid_event_type");
    }
    if ($oid <= 0) {
      throw new InvalidArgumentException("invalid_event_id");
    }

    $db = $system_data->dbcon;
    if ($otype === "R") {
      $row = $db->fetchRow(
        "SELECT r.begin, r.end, r.notes, l.name AS location_name, a.street, a.zip, a.city
                 FROM rehearsal r
                 LEFT JOIN location l ON r.location = l.id
                 LEFT JOIN address a ON l.address = a.id
                 WHERE r.id = ?",
        [["i", $oid]],
      );
    } else {
      $row = $db->fetchRow(
        "SELECT c.title, c.begin, c.end, c.meetingtime, c.notes, c.conditions, c.organizer,
                        l.name AS location_name, a.street, a.zip, a.city
                 FROM concert c
                 LEFT JOIN location l ON c.location = l.id
                 LEFT JOIN address a ON l.address = a.id
                 WHERE c.id = ?",
        [["i", $oid]],
      );
    }
    if (!is_array($row) || count($row) < 1) {
      throw new RuntimeException("event_not_found");
    }

    $beginRaw = trim((string) ($row["begin"] ?? ""));
    $endRaw = trim((string) ($row["end"] ?? ""));
    $dtBegin = $beginRaw !== "" ? MailLocaleDateTime::parseInBandTimezone($beginRaw) : null;
    $dtEnd = $endRaw !== "" ? MailLocaleDateTime::parseInBandTimezone($endRaw) : null;
    $dateLine = $dtBegin !== null ? MailLocaleDateTime::formatEventMetaLine($beginRaw, $endRaw, $locale) : "";
    $dateOnly = $dtBegin !== null ? MailLocaleDateTime::formatDateShort($dtBegin, $locale) : "";
    $sameDay = $dtBegin !== null && $dtEnd !== null ? $dtBegin->format("Y-m-d") === $dtEnd->format("Y-m-d") : false;

    $locationName = trim((string) ($row["location_name"] ?? ""));
    $addrParts = array_filter([
      trim((string) ($row["street"] ?? "")),
      trim(implode(" ", array_filter([trim((string) ($row["zip"] ?? "")), trim((string) ($row["city"] ?? ""))]))),
    ]);
    $addressLine = implode(", ", $addrParts);
    $mapsUrl = self::googleMapsUrl($locationName, $addressLine);

    $detailLines = [];
    if ($sameDay && $dtBegin !== null) {
      $detailLines[] = [
        "label" => MailI18n::t("mail.eventInfo.detailDate", $locale),
        "value" => MailLocaleDateTime::formatDateShort($dtBegin, $locale),
      ];
    }
    if ($locationName !== "" || $addressLine !== "") {
      $locationValue = $locationName;
      if ($addressLine !== "") {
        $locationValue = $locationValue !== "" ? $locationValue . " - " . $addressLine : $addressLine;
      }
      $row = [
        "label" => MailI18n::t("mail.eventInfo.detailLocation", $locale),
        "value" => $locationValue,
      ];
      if ($mapsUrl !== "") {
        $row["href"] = $mapsUrl;
      }
      $detailLines[] = $row;
    }
    if ($otype === "C") {
      $meetingRaw = trim((string) ($row["meetingtime"] ?? ""));
      $meetingSource = $meetingRaw !== "" ? $meetingRaw : $beginRaw;
      if ($meetingSource !== "") {
        $meetingDt = MailLocaleDateTime::parseInBandTimezone($meetingSource);
        if ($meetingDt !== null) {
          $meetingSameDay = $dtBegin !== null && $meetingDt->format("Y-m-d") === $dtBegin->format("Y-m-d");
          $meetingValue = MailLocaleDateTime::formatDateTimeShort($meetingDt, $locale);
          if ($sameDay && $meetingSameDay) {
            $meetingValue = MailLocaleDateTime::formatTimeShort($meetingDt, $locale);
          }
          $detailLines[] = [
            "label" => MailI18n::t("mail.eventInfo.detailMeeting", $locale),
            "value" => $meetingValue,
          ];
        }
      }
    }
    if ($dtBegin !== null) {
      $detailLines[] = [
        "label" => MailI18n::t("mail.eventInfo.detailStart", $locale),
        "value" => $sameDay
          ? MailLocaleDateTime::formatTimeShort($dtBegin, $locale)
          : MailLocaleDateTime::formatDateTimeShort($dtBegin, $locale),
      ];
    }
    if ($dtEnd !== null) {
      $detailLines[] = [
        "label" => MailI18n::t("mail.eventInfo.detailEnd", $locale),
        "value" => $sameDay
          ? MailLocaleDateTime::formatTimeShort($dtEnd, $locale)
          : MailLocaleDateTime::formatDateTimeShort($dtEnd, $locale),
      ];
    }
    $organizer = trim((string) ($row["organizer"] ?? ""));
    if ($organizer !== "") {
      $detailLines[] = ["label" => MailI18n::t("mail.eventInfo.detailOrganizer", $locale), "value" => $organizer];
    }

    $title = "";
    if ($otype === "C") {
      $title = trim((string) ($row["title"] ?? ""));
    }
    if ($title === "") {
      if ($locationName !== "") {
        $title = $locationName;
      } elseif ($dateOnly !== "") {
        $title = $dateOnly;
      } else {
        $title =
          $otype === "R" ? MailI18n::t("js.event.rehearsal", $locale) : MailI18n::t("js.event.performance", $locale);
      }
    }

    return [
      "otype" => $otype,
      "title" => $title,
      "typeLabel" =>
        $otype === "R" ? MailI18n::t("js.event.rehearsal", $locale) : MailI18n::t("js.event.performance", $locale),
      "dateLine" => $dateLine,
      "locationName" => $locationName,
      "addressLine" => $addressLine,
      "eventLink" => CommentDiscussionEntityUrl::openEntityUrl($otype, $oid),
      "detailLines" => $detailLines,
      "entityCard" => CommentDiscussionEntitySummary::load($otype, $oid, $system_data, $locale),
      "beginDate" => $dateOnly,
    ];
  }

  private static function googleMapsUrl(string $locationName, string $addressLine): string
  {
    $query = trim($addressLine !== "" ? $addressLine : $locationName);
    if ($query === "") {
      return "";
    }
    return "https://www.google.com/maps/search/?api=1&query=" . rawurlencode($query);
  }

  /**
   * @param array<string,mixed> $event
   */
  private static function defaultSubject(string $locale, array $event, $system_data): string
  {
    $company = method_exists($system_data, "getCompany") ? (string) $system_data->getCompany() : "";
    $orgPrefix = MailSubject::orgPrefix($company);
    $date = (string) ($event["beginDate"] ?? "");
    $otype = strtoupper((string) ($event["otype"] ?? ""));
    if ($otype === "R") {
      return MailI18n::interpolate(MailI18n::t("mail.eventInfo.subjectRehearsal", $locale), [
        "orgPrefix" => $orgPrefix,
        "date" => $date,
      ]);
    }

    return MailI18n::interpolate(MailI18n::t("mail.eventInfo.subjectConcert", $locale), [
      "orgPrefix" => $orgPrefix,
      "date" => $date,
    ]);
  }

  /**
   * @param array<string,mixed> $event
   */
  private static function defaultBody(string $locale, array $event, string $senderName): string
  {
    return "";
  }

  private static function resolveFromEmail(): string
  {
    $from = trim((string) MailEnv::fromAddress());
    return filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : "";
  }

  /**
   * @param 'R'|'C' $otype
   * @return list<array{id:int,name:string,email:string}>
   */
  private static function loadRecipients($system_data, string $otype, int $oid): array
  {
    $otype = strtoupper($otype);
    $db = $system_data->dbcon;
    if ($otype === "R") {
      $rows = $db->preparedQuery(
        "SELECT c.id, TRIM(CONCAT(COALESCE(c.name,''), ' ', COALESCE(c.surname,''))) AS fullname, c.email
                 FROM rehearsal_contact rc
                 JOIN contact c ON c.id = rc.contact
                 WHERE rc.rehearsal = ?
                 ORDER BY fullname ASC",
        [["i", $oid]],
      );
    } else {
      $rows = $db->preparedQuery(
        "SELECT c.id, TRIM(CONCAT(COALESCE(c.name,''), ' ', COALESCE(c.surname,''))) AS fullname, c.email
                 FROM concert_contact cc
                 JOIN contact c ON c.id = cc.contact
                 WHERE cc.concert = ?
                 ORDER BY fullname ASC",
        [["i", $oid]],
      );
    }

    $out = [];
    foreach ($rows as $row) {
      $id = (int) ($row["id"] ?? 0);
      $email = trim((string) ($row["email"] ?? ""));
      if ($id <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        continue;
      }
      $name = trim((string) ($row["fullname"] ?? ""));
      if ($name === "") {
        $name = $email;
      }
      $out[] = ["id" => $id, "name" => $name, "email" => $email];
    }
    return $out;
  }

  /**
   * @param 'R'|'C' $otype
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   * @return list<string>
   */
  private static function resolveOutgoingRecipients(
    $system_data,
    string $otype,
    int $oid,
    array $recipientIds,
    array $manualEmails,
  ): array {
    $known = self::loadRecipients($system_data, $otype, $oid);
    $known = array_merge(
      $known,
      self::loadAdditionalContacts($system_data, array_map(fn($r) => (int) $r["id"], $known)),
    );
    return MailRecipientDirectory::resolveOutgoingEmails($known, $recipientIds, $manualEmails);
  }

  /**
   * @param list<int> $excludeContactIds
   * @return list<array{id:int,name:string,email:string}>
   */
  private static function loadAdditionalContacts($system_data, array $excludeContactIds): array
  {
    $exclude = [];
    foreach ($excludeContactIds as $id) {
      $exclude[(int) $id] = true;
    }

    $out = [];
    foreach (MailRecipientDirectory::loadContacts($system_data) as $row) {
      $id = (int) ($row["id"] ?? 0);
      if ($id <= 0 || isset($exclude[$id])) {
        continue;
      }
      $out[] = [
        "id" => $id,
        "name" => (string) ($row["name"] ?? ""),
        "email" => (string) ($row["email"] ?? ""),
      ];
    }

    return $out;
  }

  /**
   * @param 'R'|'C' $otype
   * @return list<int>
   */
  private static function loadDeclinedRecipientIds($system_data, string $otype, int $oid): array
  {
    $otype = strtoupper($otype);
    $db = $system_data->dbcon;
    if ($otype === "R") {
      $rows = $db->preparedQuery(
        "SELECT c.id
                 FROM rehearsal_contact rc
                 JOIN contact c ON c.id = rc.contact
                 JOIN user u ON u.contact = c.id
                 LEFT JOIN rehearsal_user ru ON ru.user = u.id AND ru.rehearsal = rc.rehearsal
                 WHERE rc.rehearsal = ? AND ru.participate = 0",
        [["i", $oid]],
      );
    } else {
      $rows = $db->preparedQuery(
        "SELECT c.id
                 FROM concert_contact cc
                 JOIN contact c ON c.id = cc.contact
                 JOIN user u ON u.contact = c.id
                 LEFT JOIN concert_user cu ON cu.user = u.id AND cu.concert = cc.concert
                 WHERE cc.concert = ? AND cu.participate = 0",
        [["i", $oid]],
      );
    }

    $uniq = [];
    foreach ($rows as $row) {
      $id = (int) ($row["id"] ?? 0);
      if ($id > 0) {
        $uniq[$id] = true;
      }
    }
    return array_map("intval", array_keys($uniq));
  }
}
