<?php
declare(strict_types=1);

require_once __DIR__ . "/MailPreviewFixtures.php";
require_once __DIR__ . "/builders/PasswordResetMailBuilder.php";
require_once __DIR__ . "/builders/NewUserAdminMailBuilder.php";
require_once __DIR__ . "/builders/LongDemoMailBuilder.php";
require_once __DIR__ . "/builders/CommentDiscussionMailBuilder.php";
require_once __DIR__ . "/builders/EventParticipantInviteMailBuilder.php";
require_once __DIR__ . "/builders/EventInfoMailBuilder.php";
require_once __DIR__ . "/builders/TaskNotificationMailBuilder.php";
require_once __DIR__ . "/builders/ReminderDigestMailBuilder.php";
require_once __DIR__ . "/builders/EscalationAlertMailBuilder.php";
require_once __DIR__ . "/builders/EscalationResolvedMailBuilder.php";
require_once __DIR__ . "/builders/UserWelcomeMailBuilder.php";
require_once __DIR__ . "/NextGenMailMessage.php";

final class MailPreviewRegistry
{
  /** @return list<array{id: string, label: string}> */
  public static function templates(): array
  {
    return [
      ["id" => "password_reset", "label" => "Password reset"],
      ["id" => "new_user_admin", "label" => "New user (admin notification)"],
      ["id" => "user_welcome", "label" => "User welcome (admin activation)"],
      ["id" => "long_demo", "label" => "Long layout demo (lorem)"],
      ["id" => "comment_discussion_rehearsal_short", "label" => "Comment discussion (rehearsal, short thread)"],
      ["id" => "comment_discussion_rehearsal_long", "label" => "Comment discussion (rehearsal, long thread)"],
      ["id" => "comment_discussion_concert", "label" => "Comment discussion (concert, short thread)"],
      ["id" => "comment_discussion_vote", "label" => "Comment discussion (vote)"],
      ["id" => "comment_discussion_single_new", "label" => "Comment discussion (single new message)"],
      ["id" => "event_invite_rehearsal", "label" => "Event invite (rehearsal, maybe on)"],
      ["id" => "event_invite_rehearsal_no_maybe", "label" => "Event invite (rehearsal, maybe off)"],
      ["id" => "event_invite_concert", "label" => "Event invite (concert)"],
      ["id" => "event_info_concert", "label" => "Event info (concert)"],
      ["id" => "reminder_digest_weekly", "label" => "Reminder digest (weekly summary)"],
      ["id" => "reminder_digest_empty", "label" => "Reminder digest (empty sections)"],
      ["id" => "escalation_deadline_pending", "label" => "Escalation alert (deadline pending)"],
      ["id" => "escalation_instrument_gap", "label" => "Escalation alert (instrument minimum gap)"],
      ["id" => "escalation_dropout_critical", "label" => "Escalation alert (late dropout, critical)"],
      ["id" => "escalation_resolved", "label" => "Escalation resolved (requirements met)"],
      ["id" => "task_assigned", "label" => "Task assigned (create)"],
      ["id" => "task_updated", "label" => "Task updated"],
    ];
  }

  public static function build(string $templateId, string $locale): NextGenMailMessage
  {
    $sd = MailPreviewFixtures::systemData();
    switch ($templateId) {
      case "password_reset":
        return PasswordResetMailBuilder::build(
          $sd,
          $locale,
          MailPreviewFixtures::previewToEmail(),
          MailPreviewFixtures::demoResetUrl(),
        );
      case "new_user_admin":
        return NewUserAdminMailBuilder::build(
          $sd,
          $locale,
          MailPreviewFixtures::newUserCtx(),
          [MailPreviewFixtures::previewToEmail()],
          [],
          MailPreviewFixtures::recipientPreviewFirstName(),
        );
      case "user_welcome":
        $welcome = MailPreviewFixtures::userWelcomeCtx();
        return UserWelcomeMailBuilder::build($sd, $locale, $welcome["toEmail"], $welcome["firstName"]);
      case "long_demo":
        return LongDemoMailBuilder::build(
          $sd,
          $locale,
          MailPreviewFixtures::previewToEmail(),
          MailPreviewFixtures::recipientPreviewFirstName(),
        );
      case "comment_discussion_rehearsal_short":
        $ctxShort = MailPreviewFixtures::commentDiscussionRehearsalShort($locale);
        $ctxShort["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return CommentDiscussionMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxShort,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "comment_discussion_rehearsal_long":
        $ctxLong = MailPreviewFixtures::commentDiscussionRehearsalLong($locale);
        $ctxLong["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return CommentDiscussionMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxLong,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "comment_discussion_concert":
        $ctxConcert = MailPreviewFixtures::commentDiscussionConcert($locale);
        $ctxConcert["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return CommentDiscussionMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxConcert,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "comment_discussion_vote":
        $ctxVote = MailPreviewFixtures::commentDiscussionVote($locale);
        $ctxVote["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return CommentDiscussionMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxVote,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "comment_discussion_single_new":
        $ctxSingle = MailPreviewFixtures::commentDiscussionSingleNew($locale);
        $ctxSingle["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return CommentDiscussionMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxSingle,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "event_invite_rehearsal":
        $ctxEv = MailPreviewFixtures::eventInviteRehearsal($locale);
        $ctxEv["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return EventParticipantInviteMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxEv,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "event_invite_rehearsal_no_maybe":
        $ctxEvNm = MailPreviewFixtures::eventInviteRehearsalNoMaybe($locale);
        $ctxEvNm["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return EventParticipantInviteMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxEvNm,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "event_invite_concert":
        $ctxCon = MailPreviewFixtures::eventInviteConcert($locale);
        $ctxCon["recipientFirstName"] = MailPreviewFixtures::recipientPreviewFirstName();

        return EventParticipantInviteMailBuilder::buildForPreview(
          $sd,
          $locale,
          $ctxCon,
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "event_info_concert":
        return EventInfoMailBuilder::buildForPreview(
          $sd,
          $locale,
          MailPreviewFixtures::eventInfoConcert($locale),
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "reminder_digest_weekly":
        $digest = MailPreviewFixtures::reminderDigestMixed($locale);
        return ReminderDigestMailBuilder::build(
          $sd,
          $locale,
          MailPreviewFixtures::recipientPreviewFirstName(),
          $digest["events_upcoming"],
          $digest["events_pending_response"],
          $digest["votes"],
          $digest["tasks"],
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "reminder_digest_empty":
        $empty = MailPreviewFixtures::reminderDigestEmpty();
        return ReminderDigestMailBuilder::build(
          $sd,
          $locale,
          MailPreviewFixtures::recipientPreviewFirstName(),
          $empty["events_upcoming"],
          $empty["events_pending_response"],
          $empty["votes"],
          $empty["tasks"],
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "escalation_deadline_pending":
        $escDeadline = MailPreviewFixtures::escalationDeadlinePending($locale);
        return EscalationAlertMailBuilder::build(
          $sd,
          $locale,
          $escDeadline["eventTitle"],
          $escDeadline["otype"],
          $escDeadline["eventBegin"],
          $escDeadline["eventEnd"],
          $escDeadline["eventLocation"],
          $escDeadline["urgency"],
          $escDeadline["reasons"],
          $escDeadline["gaps"],
          $escDeadline["eventUrl"],
          [MailPreviewFixtures::previewToEmail()],
          [],
          $escDeadline["counts"],
        );
      case "escalation_instrument_gap":
        $escGap = MailPreviewFixtures::escalationInstrumentGap($locale);
        return EscalationAlertMailBuilder::build(
          $sd,
          $locale,
          $escGap["eventTitle"],
          $escGap["otype"],
          $escGap["eventBegin"],
          $escGap["eventEnd"],
          $escGap["eventLocation"],
          $escGap["urgency"],
          $escGap["reasons"],
          $escGap["gaps"],
          $escGap["eventUrl"],
          [MailPreviewFixtures::previewToEmail()],
          [],
          $escGap["counts"],
        );
      case "escalation_dropout_critical":
        $escDropout = MailPreviewFixtures::escalationDropoutCritical($locale);
        return EscalationAlertMailBuilder::build(
          $sd,
          $locale,
          $escDropout["eventTitle"],
          $escDropout["otype"],
          $escDropout["eventBegin"],
          $escDropout["eventEnd"],
          $escDropout["eventLocation"],
          $escDropout["urgency"],
          $escDropout["reasons"],
          $escDropout["gaps"],
          $escDropout["eventUrl"],
          [MailPreviewFixtures::previewToEmail()],
          [],
          $escDropout["counts"],
        );
      case "escalation_resolved":
        $escResolved = MailPreviewFixtures::escalationResolved($locale);
        return EscalationResolvedMailBuilder::build(
          $sd,
          $locale,
          $escResolved["eventTitle"],
          $escResolved["otype"],
          $escResolved["eventBegin"],
          $escResolved["eventEnd"],
          $escResolved["eventLocation"],
          $escResolved["eventUrl"],
          [MailPreviewFixtures::previewToEmail()],
          [],
          $escResolved["counts"],
        );
      case "task_assigned":
        return TaskNotificationMailBuilder::buildForPreview(
          $sd,
          $locale,
          MailPreviewFixtures::taskNotifyCreate(),
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      case "task_updated":
        return TaskNotificationMailBuilder::buildForPreview(
          $sd,
          $locale,
          MailPreviewFixtures::taskNotifyUpdate(),
          [MailPreviewFixtures::previewToEmail()],
          [],
        );
      default:
        throw new InvalidArgumentException("Unknown template: " . $templateId);
    }
  }

  public static function isValidTemplate(string $id): bool
  {
    foreach (self::templates() as $t) {
      if ($t["id"] === $id) {
        return true;
      }
    }
    return false;
  }
}
