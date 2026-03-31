/**
 * Template ids allowed for `api/debug/mail_test_send.php`.
 * Keep in sync with `MailPreviewRegistry::templates()` in `api/mail/MailPreviewRegistry.php`.
 */

export const MAIL_TEST_LOCALES = ["en", "de", "es", "fr"] as const;

export type MailTestLocale = (typeof MAIL_TEST_LOCALES)[number];

export const MAIL_TEST_TEMPLATES: readonly { id: string; label: string }[] = [
  { id: "password_reset", label: "Password reset" },
  { id: "new_user_admin", label: "New user (admin notification)" },
  { id: "long_demo", label: "Long layout demo (lorem)" },
  { id: "comment_discussion_rehearsal_short", label: "Comment discussion (rehearsal, short thread)" },
  { id: "comment_discussion_rehearsal_long", label: "Comment discussion (rehearsal, long thread)" },
  { id: "comment_discussion_concert", label: "Comment discussion (concert, short thread)" },
  { id: "comment_discussion_vote", label: "Comment discussion (vote)" },
  { id: "comment_discussion_single_new", label: "Comment discussion (single new message)" },
  { id: "event_invite_rehearsal", label: "Event invite (rehearsal, maybe on)" },
  { id: "event_invite_rehearsal_no_maybe", label: "Event invite (rehearsal, maybe off)" },
  { id: "event_invite_concert", label: "Event invite (concert)" },
  { id: "event_info_concert", label: "Event info (concert)" },
  { id: "task_assigned", label: "Task assigned (create)" },
  { id: "task_updated", label: "Task updated" },
];
