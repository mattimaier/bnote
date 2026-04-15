<?php
declare(strict_types=1);

require_once __DIR__ . "/MailPreviewHtml.php";
require_once __DIR__ . "/MailRecipientPolicy.php";
require_once __DIR__ . "/NextGenMailPolicy.php";
require_once __DIR__ . "/NextGenMailer.php";
require_once __DIR__ . "/MailEnv.php";
require_once __DIR__ . "/MailI18n.php";
require_once __DIR__ . "/MailSubject.php";
require_once __DIR__ . "/MailRecipientDirectory.php";
require_once __DIR__ . "/builders/GenericEmailComposerMailBuilder.php";

final class GenericEmailComposerService
{
  /**
   * @return array{
   *   fromEmail:string,
   *   toEmail:string,
   *   selectedRecipientIds:list<int>,
   *   subject:string,
   *   subjectPrefix:string,
   *   body:string
   * }
   */
  public static function draft($system_data, string $locale = "en"): array
  {
    $fromEmail = self::resolveFromEmail();
    $prefix = self::subjectPrefix($system_data);
    return [
      "fromEmail" => $fromEmail,
      "toEmail" => $fromEmail,
      "selectedRecipientIds" => [],
      "subject" => $prefix,
      "subjectPrefix" => $prefix,
      "body" => "",
    ];
  }

  /**
   * @param list<array{id:int,name:string,email:string,instrument?:string}> $directory
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   */
  public static function previewHtml(
    $system_data,
    string $locale,
    array $directory,
    array $recipientIds,
    array $manualEmails,
    string $subject,
    string $body,
    string $senderName,
  ): string {
    $message = self::buildMessage(
      $system_data,
      $locale,
      $directory,
      $recipientIds,
      $manualEmails,
      $subject,
      $body,
      $senderName,
    );
    return MailPreviewHtml::replaceCidLogoWithDataUri($message->htmlBody);
  }

  /**
   * @param list<array{id:int,name:string,email:string,instrument?:string}> $directory
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   * @return array{sent:int,skipped:int}
   */
  public static function send(
    $system_data,
    string $locale,
    array $directory,
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
      $locale,
      $directory,
      $recipientIds,
      $manualEmails,
      $subject,
      $body,
      $senderName,
    );
    $deliverableCount = count($message->bcc);
    $requestedCount = count(MailRecipientDirectory::resolveOutgoingEmails($directory, $recipientIds, $manualEmails));
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
   * @param list<array{id:int,name:string,email:string,instrument?:string}> $directory
   * @param list<int> $recipientIds
   * @param list<string> $manualEmails
   */
  private static function buildMessage(
    $system_data,
    string $locale,
    array $directory,
    array $recipientIds,
    array $manualEmails,
    string $subject,
    string $body,
    string $senderName,
  ): NextGenMailMessage {
    if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
      throw new RuntimeException("mail_disabled");
    }

    $outgoing = MailRecipientDirectory::resolveOutgoingEmails($directory, $recipientIds, $manualEmails);
    $deliverable = [];
    foreach ($outgoing as $email) {
      if (!MailRecipientPolicy::shouldSkipOutboundDelivery($email)) {
        $deliverable[] = $email;
      }
    }

    $senderEmail = self::resolveFromEmail();
    if ($senderEmail === "" || MailRecipientPolicy::shouldSkipOutboundDelivery($senderEmail)) {
      throw new InvalidArgumentException("mail_sender_email_required");
    }

    [$subjectFull, $subjectHeadline] = self::normalizeSubject($system_data, $subject);

    return GenericEmailComposerMailBuilder::build(
      $system_data,
      $locale,
      $subjectFull,
      $subjectHeadline,
      $body,
      $senderName,
      [$senderEmail],
      $deliverable,
    );
  }

  private static function resolveFromEmail(): string
  {
    $from = trim((string) MailEnv::fromAddress());
    return filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : "";
  }

  private static function defaultSubject($system_data, string $locale): string
  {
    $company = method_exists($system_data, "getCompany") ? trim((string) $system_data->getCompany()) : "";
    return MailI18n::interpolate(MailI18n::t("mail.genericComposer.subjectFallback", $locale), [
      "orgPrefix" => MailSubject::orgPrefix($company),
    ]);
  }

  private static function subjectPrefix($system_data): string
  {
    $company = method_exists($system_data, "getCompany") ? trim((string) $system_data->getCompany()) : "";
    if ($company === "") {
      return "BNote";
    }
    return $company . " - BNote";
  }

  /**
   * @return array{0:string,1:string}
   */
  private static function normalizeSubject($system_data, string $subjectRaw): array
  {
    $prefix = self::subjectPrefix($system_data);
    $subject = trim($subjectRaw);
    if ($subject === "") {
      return [$prefix, ""];
    }

    $prefixLower = strtolower($prefix);
    $subjectLower = strtolower($subject);
    $suffix = $subject;
    if (str_starts_with($subjectLower, $prefixLower)) {
      $suffix = trim(substr($subject, strlen($prefix)));
      if (str_starts_with($suffix, "-")) {
        $suffix = trim(substr($suffix, 1));
      }
    }
    if ($suffix === "") {
      return [$prefix, ""];
    }
    return [$prefix . " - " . $suffix, $suffix];
  }
}
