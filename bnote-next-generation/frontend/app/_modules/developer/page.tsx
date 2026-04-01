/**
 * BNote Next Generation — Developer tools hub (API debug, entity mocks, mail previews).
 * Copy is English-only; this module is not localized.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useCallback, useEffect, useState, type ReactNode } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { ChevronRight, TablerIconByName } from "@/components/icons";
import { DeveloperSurfacesGuard } from "@/components/debug/DeveloperSurfacesGuard";
import { SelectPicker } from "@/components/SelectPicker";
import { getApiDebugScriptUrl } from "@/lib/api";
import { getEntityConfig } from "@/lib/entity-config";
import mailDesignTokens from "@/mail-design-tokens.json";
import { MAIL_TEST_LOCALES, MAIL_TEST_TEMPLATES } from "@/lib/mail-test-templates";
import { remindersApi, type EscalationAuditEntry, type ReminderRecipient } from "@/lib/reminders-api";
import { calendarApi, type CalendarSubscriptionLink } from "@/lib/calendar-api";

const COMMENT_ENTITY_KINDS = [
  { otype: "R" as const, label: "Rehearsal" },
  { otype: "C" as const, label: "Concert" },
  { otype: "V" as const, label: "Vote" },
];

function HubCard({
  title,
  description,
  href,
  external,
  iconName,
  accentColor,
}: {
  title: string;
  description: string;
  href?: string;
  external?: boolean;
  iconName: string;
  accentColor?: string;
}) {
  const content = (
    <div
      className={`flex items-start gap-4 rounded-box border border-base-300 p-4 transition-colors ${
        href ? "hover:bg-base-200/70 cursor-pointer" : "opacity-90"
      }`}
    >
      <div
        className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-base-content"
        style={{
          background: accentColor ? `color-mix(in oklch, ${accentColor} 22%, transparent)` : "var(--muted)",
          color: accentColor ?? undefined,
        }}
      >
        <TablerIconByName name={iconName} className="h-5 w-5" />
      </div>
      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <span className="font-medium text-base-content">{title}</span>
          {href && (
            <span className="text-base-content/50">
              {external ? <TablerIconByName name="external-link" className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
            </span>
          )}
        </div>
        <p className="mt-1 text-sm text-base-content/70">{description}</p>
      </div>
    </div>
  );

  if (!href) {
    return content;
  }

  if (external) {
    return (
      <a href={href} target="_blank" rel="noopener noreferrer" className="block no-underline text-inherit">
        {content}
      </a>
    );
  }

  return (
    <Link href={href} prefetch={false} className="block no-underline text-inherit">
      {content}
    </Link>
  );
}

function DevPanel({
  title,
  description,
  iconName,
  accentColor,
  children,
}: {
  title: string;
  description: string;
  iconName: string;
  accentColor?: string;
  children: ReactNode;
}) {
  return (
    <div className="rounded-box border border-base-300 p-4 space-y-3">
      <div className="flex items-start gap-4">
        <div
          className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-base-content"
          style={{
            background: accentColor ? `color-mix(in oklch, ${accentColor} 22%, transparent)` : "var(--muted)",
            color: accentColor ?? undefined,
          }}
        >
          <TablerIconByName name={iconName} className="h-5 w-5" />
        </div>
        <div className="min-w-0 flex-1">
          <h3 className="font-medium text-base-content">{title}</h3>
          <p className="mt-1 text-sm text-base-content/70">{description}</p>
        </div>
      </div>
      {children}
    </div>
  );
}

function DeveloperModulePageContent() {
  const devEntity = getEntityConfig("developer");
  const accent = devEntity?.color;

  const [smtpTo, setSmtpTo] = useState("");
  const [smtpTemplate, setSmtpTemplate] = useState("password_reset");
  const [smtpLocale, setSmtpLocale] = useState<string>("en");
  const [smtpLoading, setSmtpLoading] = useState(false);
  const [smtpOutput, setSmtpOutput] = useState<string | null>(null);
  const [remoteSmtpTo, setRemoteSmtpTo] = useState("");
  const [remoteSmtpTemplate, setRemoteSmtpTemplate] = useState("password_reset");
  const [remoteSmtpLocale, setRemoteSmtpLocale] = useState<string>("en");
  const [remoteSmtpLoading, setRemoteSmtpLoading] = useState(false);
  const [remoteSmtpOutput, setRemoteSmtpOutput] = useState<string | null>(null);

  const [commentOtype, setCommentOtype] = useState<(typeof COMMENT_ENTITY_KINDS)[number]["otype"]>("R");
  const [commentId, setCommentId] = useState("641");
  const [commentAuthorUid, setCommentAuthorUid] = useState("");
  const [commentLoading, setCommentLoading] = useState(false);
  const [commentOutput, setCommentOutput] = useState<string | null>(null);
  const [reminderRunLoading, setReminderRunLoading] = useState(false);
  const [reminderRunDry, setReminderRunDry] = useState(true);
  const [reminderRunIgnoreLimits, setReminderRunIgnoreLimits] = useState(false);
  const [reminderRunOutput, setReminderRunOutput] = useState<string | null>(null);
  const [reminderRecipients, setReminderRecipients] = useState<ReminderRecipient[]>([]);
  const [reminderSelectedUserId, setReminderSelectedUserId] = useState<number>(0);
  const [escEventType, setEscEventType] = useState<"R" | "C">("R");
  const [escEventId, setEscEventId] = useState("0");
  const [escContactId, setEscContactId] = useState("0");
  const [escTestRecipients, setEscTestRecipients] = useState("");
  const [escLoading, setEscLoading] = useState(false);
  const [escOutput, setEscOutput] = useState<string | null>(null);
  const [escAuditLoading, setEscAuditLoading] = useState(false);
  const [escAuditEntries, setEscAuditEntries] = useState<EscalationAuditEntry[]>([]);
  const [escAuditOutput, setEscAuditOutput] = useState<string | null>(null);
  const [calendarSubLoading, setCalendarSubLoading] = useState(false);
  const [calendarSubData, setCalendarSubData] = useState<CalendarSubscriptionLink | null>(null);
  const [calendarSubOutput, setCalendarSubOutput] = useState<string | null>(null);

  const runSmtpTest = useCallback(async () => {
    const to = smtpTo.trim();
    const params = new URLSearchParams({
      to,
      template: smtpTemplate,
      locale: smtpLocale,
    });
    const url = `${getApiDebugScriptUrl("mail_test_send.php")}?${params.toString()}`;
    setSmtpLoading(true);
    setSmtpOutput(null);
    try {
      const res = await fetch(url, { credentials: "same-origin" });
      const text = await res.text();
      setSmtpOutput(text);
    } catch (e) {
      setSmtpOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setSmtpLoading(false);
    }
  }, [smtpTo, smtpTemplate, smtpLocale]);

  const runRemoteSmtpTest = useCallback(async () => {
    const to = remoteSmtpTo.trim();
    const params = new URLSearchParams({
      to,
      template: remoteSmtpTemplate,
      locale: remoteSmtpLocale,
    });
    const url = `${getApiDebugScriptUrl("mail_test_send_remote.php")}?${params.toString()}`;
    setRemoteSmtpLoading(true);
    setRemoteSmtpOutput(null);
    try {
      const res = await fetch(url, { credentials: "same-origin" });
      const text = await res.text();
      setRemoteSmtpOutput(text);
    } catch (e) {
      setRemoteSmtpOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setRemoteSmtpLoading(false);
    }
  }, [remoteSmtpTo, remoteSmtpTemplate, remoteSmtpLocale]);

  const runCommentRecipients = useCallback(async () => {
    const id = parseInt(commentId.trim(), 10);
    if (!Number.isFinite(id) || id < 1) {
      setCommentOutput(JSON.stringify({ error: "invalid_id", hint: "Enter a positive numeric entity id." }, null, 2));
      return;
    }
    const params = new URLSearchParams({ otype: commentOtype, id: String(id) });
    const author = commentAuthorUid.trim();
    if (author !== "" && /^\d+$/.test(author)) {
      params.set("author_uid", author);
    }
    const url = `${getApiDebugScriptUrl("mail_comment_recipients.php")}?${params.toString()}`;
    setCommentLoading(true);
    setCommentOutput(null);
    try {
      const res = await fetch(url, { credentials: "same-origin" });
      const text = await res.text();
      setCommentOutput(text);
    } catch (e) {
      setCommentOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setCommentLoading(false);
    }
  }, [commentOtype, commentId, commentAuthorUid]);

  const runReminderTrigger = useCallback(async () => {
    setReminderRunLoading(true);
    setReminderRunOutput(null);
    try {
      const out = await remindersApi.runNow(
        reminderRunDry,
        true,
        reminderSelectedUserId > 0 ? reminderSelectedUserId : undefined,
        reminderRunIgnoreLimits
      );
      setReminderRunOutput(JSON.stringify(out, null, 2));
    } catch (e) {
      setReminderRunOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setReminderRunLoading(false);
    }
  }, [reminderRunDry, reminderSelectedUserId, reminderRunIgnoreLimits]);

  const parseTestRecipients = useCallback((): string[] => {
    return escTestRecipients
      .split(/[,\s;]/g)
      .map((x) => x.trim())
      .filter((x) => x.length > 0);
  }, [escTestRecipients]);

  const runEscalationScheduledDryRun = useCallback(async () => {
    setEscLoading(true);
    setEscOutput(null);
    try {
      const out = await remindersApi.runEscalationNow({
        dryRun: true,
        force: true,
      });
      setEscOutput(JSON.stringify(out, null, 2));
    } catch (e) {
      setEscOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setEscLoading(false);
    }
  }, []);

  const runEscalationRealForEvent = useCallback(async () => {
    const eventIdNum = parseInt(escEventId, 10);
    if (!Number.isFinite(eventIdNum) || eventIdNum < 1) {
      setEscOutput("Event id must be a positive number.");
      return;
    }
    const recipients = parseTestRecipients();
    if (recipients.length < 1) {
      setEscOutput("At least one test recipient is required for real send.");
      return;
    }
    setEscLoading(true);
    setEscOutput(null);
    try {
      const out = await remindersApi.runEscalationNow({
        dryRun: false,
        force: true,
        eventType: escEventType,
        eventId: eventIdNum,
        testRecipients: recipients,
        isTest: true,
      });
      setEscOutput(JSON.stringify(out, null, 2));
    } catch (e) {
      setEscOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setEscLoading(false);
    }
  }, [escEventId, escEventType, parseTestRecipients]);

  const runEscalationDropoutSimulation = useCallback(async () => {
    const eventIdNum = parseInt(escEventId, 10);
    const contactIdNum = parseInt(escContactId, 10);
    if (!Number.isFinite(eventIdNum) || eventIdNum < 1) {
      setEscOutput("Event id must be a positive number.");
      return;
    }
    setEscLoading(true);
    setEscOutput(null);
    try {
      const out = await remindersApi.simulateEscalationDropout({
        dryRun: true,
        eventType: escEventType,
        eventId: eventIdNum,
        contactId: Number.isFinite(contactIdNum) && contactIdNum > 0 ? contactIdNum : undefined,
      });
      setEscOutput(JSON.stringify(out, null, 2));
    } catch (e) {
      setEscOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setEscLoading(false);
    }
  }, [escContactId, escEventId, escEventType]);

  const runEscalationEligibility = useCallback(async () => {
    const eventIdNum = parseInt(escEventId, 10);
    if (!Number.isFinite(eventIdNum) || eventIdNum < 1) {
      setEscOutput("Event id must be a positive number.");
      return;
    }
    setEscLoading(true);
    setEscOutput(null);
    try {
      const out = await remindersApi.getEscalationEligibility(escEventType, eventIdNum);
      setEscOutput(JSON.stringify(out, null, 2));
    } catch (e) {
      setEscOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setEscLoading(false);
    }
  }, [escEventId, escEventType]);

  const loadEscalationAudit = useCallback(async () => {
    setEscAuditLoading(true);
    setEscAuditOutput(null);
    try {
      const out = await remindersApi.getEscalationAudit(30);
      const entries = Array.isArray(out.entries) ? out.entries : [];
      setEscAuditEntries(entries);
      setEscAuditOutput(`Loaded ${entries.length} audit entries.`);
    } catch (e) {
      setEscAuditOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setEscAuditLoading(false);
    }
  }, []);

  const loadReminderRecipients = useCallback(async () => {
    try {
      const res = await remindersApi.getRecipients();
      const list = Array.isArray(res.recipients) ? res.recipients : [];
      setReminderRecipients(list);
      if (list.length > 0 && reminderSelectedUserId === 0) {
        setReminderSelectedUserId(list[0].id);
      }
    } catch {
      // Keep panel usable with manual all-users trigger.
    }
  }, [reminderSelectedUserId]);

  const loadCalendarSubscription = useCallback(async () => {
    setCalendarSubLoading(true);
    setCalendarSubOutput(null);
    try {
      const data = await calendarApi.getSubscriptionLink();
      setCalendarSubData(data);
      setCalendarSubOutput("Loaded stable tokenized calendar links for current user.");
    } catch (e) {
      setCalendarSubOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setCalendarSubLoading(false);
    }
  }, []);

  const regenerateCalendarSubscription = useCallback(async () => {
    setCalendarSubLoading(true);
    setCalendarSubOutput(null);
    try {
      const data = await calendarApi.regenerateSubscriptionLink();
      setCalendarSubData(data);
      setCalendarSubOutput("Regenerated token. Previous subscription URL should now be invalid.");
    } catch (e) {
      setCalendarSubOutput(e instanceof Error ? e.message : String(e));
    } finally {
      setCalendarSubLoading(false);
    }
  }, []);

  const copyText = useCallback(async (value: string) => {
    if (!value) {
      setCalendarSubOutput("Nothing to copy.");
      return;
    }
    try {
      await navigator.clipboard.writeText(value);
      setCalendarSubOutput("Copied to clipboard.");
    } catch {
      setCalendarSubOutput("Clipboard copy failed.");
    }
  }, []);

  const nextItems = [
    { key: "debugMain", href: "/debug/", icon: "file-code", title: "API & debug home", desc: "API tester, entity shortcuts, and icon grid." },
    { key: "debugEntity", href: "/debug/entity/", icon: "layout-list", title: "Entity debug views", desc: "Mock view and edit flows for all entity types." },
  ] as const;

  const localMailItems = [
    { key: "mailDebug", href: getApiDebugScriptUrl("mail_debug.php"), icon: "mail", title: "Mail template index", desc: "HTML table of transactional templates and locales." },
    { key: "mailPreview", href: getApiDebugScriptUrl("mail_preview.php?template=password_reset&locale=en"), icon: "mail", title: "Mail preview (sample)", desc: "Single-template HTML (password reset, English)." },
    { key: "mailConfig", href: getApiDebugScriptUrl("mail_config_check.php"), icon: "settings", title: "Mail environment (JSON)", desc: "SMTP-related env as seen by PHP plus preview links." },
  ] as const;

  const remoteMailItems = [
    {
      key: "mailRemoteDiagnostics",
      href: getApiDebugScriptUrl("mail_remote_diagnostics.php"),
      icon: "activity",
      title: "Mail remote diagnostics (JSON)",
      desc: "Remote-safe SMTP/env checks (no loopback required). Includes DNS and TCP probe.",
    },
    {
      key: "mailRemoteSendEndpoint",
      href: getApiDebugScriptUrl("mail_test_send_remote.php"),
      icon: "send",
      title: "Mail remote send endpoint",
      desc: "Admin-protected SMTP test-send endpoint (expects query params to/template/locale).",
    },
  ] as const;
  const escalationPreviewItems = [
    {
      key: "escPreviewRehearsalSoon",
      href: getApiDebugScriptUrl("mail_preview.php?template=escalation_deadline_pending&locale=en"),
      label: "Rehearsal example (pending threshold / soon)",
    },
    {
      key: "escPreviewConcertSoon",
      href: getApiDebugScriptUrl("mail_preview.php?template=escalation_instrument_gap&locale=en"),
      label: "Concert example (instrument gap / soon)",
    },
    {
      key: "escPreviewConcertCritical",
      href: getApiDebugScriptUrl("mail_preview.php?template=escalation_dropout_critical&locale=en"),
      label: "Concert example (dropout / critical)",
    },
  ] as const;

  const tokensJson = JSON.stringify(mailDesignTokens, null, 2);
  const reminderRecipientOptions = [
    { id: 0, name: "All eligible users" },
    ...reminderRecipients.map((u) => ({
      id: u.id,
      name: (u.name ?? "").trim() || `User #${u.id}`,
      email: u.email ?? null,
      subtitle: `User #${u.id}`,
      instrument: `${(u.email ?? "").trim()}${(u.email ?? "").trim() ? " · " : ""}User #${u.id}`,
    })),
  ];

  useEffect(() => {
    void loadReminderRecipients();
  }, [loadReminderRecipients]);

  return (
    <PageContent className="px-1 md:px-4 space-y-8">
      <AppPageHeader
        moduleKey="developer"
        title="Developer tools"
        subtitle="Admin only. Debug utilities, API tester, entity mocks, and local mail previews. English-only; not localized."
      />

      <section className="space-y-3">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-base-content/60">Next app</h2>
        <div className="grid gap-3 sm:grid-cols-1 md:grid-cols-2">
          {nextItems.map((item) => (
            <HubCard
              key={item.key}
              title={item.title}
              description={item.desc}
              href={item.href}
              iconName={item.icon}
              accentColor={accent}
            />
          ))}
        </div>
      </section>

      <section className="space-y-4">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-base-content/60">Calendar feed test</h2>
        <div className="grid gap-3 sm:grid-cols-1 md:grid-cols-2">
          <DevPanel
            title="ICS subscription sanity check"
            description="Quickly validate token lifecycle, download URL, and webcal URL for the current logged-in user."
            iconName="calendar-event"
            accentColor={accent}
          >
            <div className="flex flex-wrap gap-2">
              <button type="button" className="btn btn-soft btn-sm btn-primary" disabled={calendarSubLoading} onClick={() => void loadCalendarSubscription()}>
                {calendarSubLoading ? "Loading…" : "Load links"}
              </button>
              <button type="button" className="btn btn-soft btn-sm btn-warning" disabled={calendarSubLoading} onClick={() => void regenerateCalendarSubscription()}>
                {calendarSubLoading ? "Regenerating…" : "Regenerate token"}
              </button>
            </div>
            {calendarSubData && (
              <div className="space-y-2">
                <div className="rounded-box bg-base-300/40 p-3 text-xs">
                  <p className="font-semibold mb-1">Subscription (webcal)</p>
                  <p className="break-all font-mono">{calendarSubData.subscriptionUrl}</p>
                  <div className="mt-2 flex flex-wrap gap-2">
                    <button type="button" className="btn btn-xs btn-soft" onClick={() => void copyText(calendarSubData.subscriptionUrl)}>
                      Copy
                    </button>
                    <a href={calendarSubData.subscriptionUrl} className="btn btn-xs btn-soft" target="_blank" rel="noopener noreferrer">
                      Open
                    </a>
                  </div>
                </div>
                <div className="rounded-box bg-base-300/40 p-3 text-xs">
                  <p className="font-semibold mb-1">Download URL</p>
                  <p className="break-all font-mono">{calendarSubData.downloadUrl}</p>
                  <div className="mt-2 flex flex-wrap gap-2">
                    <button type="button" className="btn btn-xs btn-soft" onClick={() => void copyText(calendarSubData.downloadUrl)}>
                      Copy
                    </button>
                    <a href={calendarSubData.downloadUrl} className="btn btn-xs btn-soft" target="_blank" rel="noopener noreferrer">
                      Download
                    </a>
                  </div>
                </div>
              </div>
            )}
            {calendarSubOutput !== null && (
              <pre className="max-h-48 overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed whitespace-pre-wrap">{calendarSubOutput}</pre>
            )}
          </DevPanel>
        </div>
      </section>

      <section className="space-y-4">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-base-content/60">Mail debug tools</h2>
        <div className="space-y-1">
          <h3 className="text-xs font-semibold uppercase tracking-wide text-base-content/60">Local (loopback only)</h3>
          <p className="text-xs text-base-content/60">
            These endpoints answer only on 127.0.0.1 / ::1. Set <code className="text-xs">NEXT_PUBLIC_API_BASE</code> if the API is not same-origin as this app.
          </p>
        </div>

        <div className="grid gap-3 sm:grid-cols-1 md:grid-cols-2">
          <DevPanel
            title="SMTP test send"
            description="Sends a real transactional template (same HTML as mail preview / production builders) via NextGenMailer. Loopback only."
            iconName="send"
            accentColor={accent}
          >
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Template</span>
                <select
                  className="select select-bordered select-sm w-full text-sm"
                  value={smtpTemplate}
                  onChange={(e) => setSmtpTemplate(e.target.value)}
                >
                  {MAIL_TEST_TEMPLATES.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Locale</span>
                <select
                  className="select select-bordered select-sm w-full font-mono text-sm uppercase"
                  value={smtpLocale}
                  onChange={(e) => setSmtpLocale(e.target.value)}
                >
                  {MAIL_TEST_LOCALES.map((loc) => (
                    <option key={loc} value={loc}>
                      {loc}
                    </option>
                  ))}
                </select>
              </label>
            </div>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
              <label className="flex min-w-0 flex-1 flex-col gap-1 text-sm">
                <span className="text-base-content/80">Recipient email</span>
                <input
                  type="email"
                  value={smtpTo}
                  onChange={(e) => setSmtpTo(e.target.value)}
                  placeholder="you@example.com"
                  className="input input-bordered input-sm w-full font-mono text-sm"
                  autoComplete="email"
                />
              </label>
              <button type="button" className="btn btn-soft btn-sm btn-primary shrink-0" disabled={smtpLoading} onClick={() => void runSmtpTest()}>
                {smtpLoading ? "Sending…" : "Send test email"}
              </button>
            </div>
            {smtpOutput !== null && (
              <pre className="max-h-48 overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed">{smtpOutput}</pre>
            )}
          </DevPanel>

          <DevPanel
            title="Comment discussion recipients"
            description="JSON list of who would receive comment-discussion mail for an entity (same rules as production). Pick entity kind and id from your database."
            iconName="users"
            accentColor={accent}
          >
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Entity kind</span>
                <select
                  className="select select-bordered select-sm w-full"
                  value={commentOtype}
                  onChange={(e) => setCommentOtype(e.target.value as (typeof COMMENT_ENTITY_KINDS)[number]["otype"])}
                >
                  {COMMENT_ENTITY_KINDS.map((k) => (
                    <option key={k.otype} value={k.otype}>
                      {k.label} ({k.otype})
                    </option>
                  ))}
                </select>
              </label>
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Entity id</span>
                <input
                  type="text"
                  inputMode="numeric"
                  value={commentId}
                  onChange={(e) => setCommentId(e.target.value)}
                  placeholder="641"
                  className="input input-bordered input-sm w-full font-mono"
                />
              </label>
            </div>
            <label className="flex flex-col gap-1 text-sm">
              <span className="text-base-content/80">Author user id (optional)</span>
              <input
                type="text"
                inputMode="numeric"
                value={commentAuthorUid}
                onChange={(e) => setCommentAuthorUid(e.target.value)}
                placeholder="Exclude this user like the real notifier"
                className="input input-bordered input-sm w-full font-mono"
              />
            </label>
            <button type="button" className="btn btn-soft btn-sm" disabled={commentLoading} onClick={() => void runCommentRecipients()}>
              {commentLoading ? "Fetching…" : "Fetch recipients (JSON)"}
            </button>
            {commentOutput !== null && (
              <pre className="max-h-64 overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed whitespace-pre-wrap break-words">
                {commentOutput}
              </pre>
            )}
          </DevPanel>
        </div>

        <div className="grid gap-3 sm:grid-cols-1 md:grid-cols-2">
          {localMailItems.map((item) => (
            <HubCard
              key={item.key}
              title={item.title}
              description={item.desc}
              href={item.href}
              external
              iconName={item.icon}
              accentColor={accent}
            />
          ))}
        </div>

        <div className="space-y-1 pt-1">
          <h3 className="text-xs font-semibold uppercase tracking-wide text-base-content/60">Remote (deployed server)</h3>
          <p className="text-xs text-base-content/60">
            Use these tools against your live deployment. The remote SMTP send endpoint requires an authenticated admin session.
          </p>
        </div>

        <div className="grid gap-3 sm:grid-cols-1 md:grid-cols-2">
          <DevPanel
            title="SMTP test send (remote)"
            description="Same test flow as local SMTP send, but for deployed servers. Requires admin session."
            iconName="send"
            accentColor={accent}
          >
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Template</span>
                <select
                  className="select select-bordered select-sm w-full text-sm"
                  value={remoteSmtpTemplate}
                  onChange={(e) => setRemoteSmtpTemplate(e.target.value)}
                >
                  {MAIL_TEST_TEMPLATES.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Locale</span>
                <select
                  className="select select-bordered select-sm w-full font-mono text-sm uppercase"
                  value={remoteSmtpLocale}
                  onChange={(e) => setRemoteSmtpLocale(e.target.value)}
                >
                  {MAIL_TEST_LOCALES.map((loc) => (
                    <option key={loc} value={loc}>
                      {loc}
                    </option>
                  ))}
                </select>
              </label>
            </div>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
              <label className="flex min-w-0 flex-1 flex-col gap-1 text-sm">
                <span className="text-base-content/80">Recipient email</span>
                <input
                  type="email"
                  value={remoteSmtpTo}
                  onChange={(e) => setRemoteSmtpTo(e.target.value)}
                  placeholder="you@example.com"
                  className="input input-bordered input-sm w-full font-mono text-sm"
                  autoComplete="email"
                />
              </label>
              <button type="button" className="btn btn-soft btn-sm btn-primary shrink-0" disabled={remoteSmtpLoading} onClick={() => void runRemoteSmtpTest()}>
                {remoteSmtpLoading ? "Sending…" : "Send remote test email"}
              </button>
            </div>
            {remoteSmtpOutput !== null && (
              <pre className="max-h-48 overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed">{remoteSmtpOutput}</pre>
            )}
          </DevPanel>
          <DevPanel
            title="Reminder scheduler trigger (admin)"
            description="Trigger the same reminder service path used by the external signed scheduler endpoint. Use dry-run for safe checks."
            iconName="clock-play"
            accentColor={accent}
          >
            <div className="space-y-1">
              <span className="text-sm text-base-content/80">Recipient scope for this run</span>
              <SelectPicker
                options={reminderRecipientOptions}
                value={reminderSelectedUserId}
                onChange={setReminderSelectedUserId}
                placeholder="Search user…"
                emptyLabel="All eligible users"
                labelSelect="Select user"
                labelNoMatches="No matches"
                labelClose="Close"
              />
            </div>
            <label className="flex cursor-pointer items-center gap-2 text-sm">
              <input
                type="checkbox"
                className="checkbox checkbox-primary checkbox-sm"
                checked={reminderRunDry}
                onChange={(e) => setReminderRunDry(e.target.checked)}
              />
              <span className="text-base-content/80">Dry-run only (no emails)</span>
            </label>
            <label className="flex cursor-pointer items-center gap-2 text-sm">
              <input
                type="checkbox"
                className="checkbox checkbox-warning checkbox-sm"
                checked={reminderRunIgnoreLimits}
                onChange={(e) => setReminderRunIgnoreLimits(e.target.checked)}
              />
              <span className="text-base-content/80">Ignore limits (debug)</span>
            </label>
            <button type="button" className="btn btn-soft btn-sm btn-primary" disabled={reminderRunLoading} onClick={() => void runReminderTrigger()}>
              {reminderRunLoading ? "Running…" : "Run reminder trigger"}
            </button>
            {reminderRunOutput !== null && (
              <pre className="max-h-56 overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed whitespace-pre-wrap">{reminderRunOutput}</pre>
            )}
          </DevPanel>
          <DevPanel
            title="Escalation alert test tools"
            description="Dry-run previews, real send (test-recipient override), dropout simulation, recipient eligibility, and audit log."
            iconName="alert-triangle"
            accentColor={accent}
          >
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Event type</span>
                <select className="select select-bordered select-sm" value={escEventType} onChange={(e) => setEscEventType(e.target.value as "R" | "C")}>
                  <option value="R">Rehearsal (R)</option>
                  <option value="C">Concert (C)</option>
                </select>
              </label>
              <label className="flex flex-col gap-1 text-sm">
                <span className="text-base-content/80">Event id</span>
                <input
                  type="text"
                  inputMode="numeric"
                  className="input input-bordered input-sm font-mono"
                  value={escEventId}
                  onChange={(e) => setEscEventId(e.target.value)}
                  placeholder="123"
                />
              </label>
            </div>
            <label className="flex flex-col gap-1 text-sm">
              <span className="text-base-content/80">Contact id for dropout simulation (optional)</span>
              <input
                type="text"
                inputMode="numeric"
                className="input input-bordered input-sm font-mono"
                value={escContactId}
                onChange={(e) => setEscContactId(e.target.value)}
                placeholder="456"
              />
            </label>
            <label className="flex flex-col gap-1 text-sm">
              <span className="text-base-content/80">Test recipients for real send (comma/space separated)</span>
              <input
                type="text"
                className="input input-bordered input-sm font-mono"
                value={escTestRecipients}
                onChange={(e) => setEscTestRecipients(e.target.value)}
                placeholder="you@example.com teammate@example.com"
              />
            </label>
            <div className="flex flex-wrap gap-2">
              <button type="button" className="btn btn-soft btn-sm" disabled={escLoading} onClick={() => void runEscalationScheduledDryRun()}>
                {escLoading ? "Running…" : "Scheduled dry-run"}
              </button>
              <button type="button" className="btn btn-soft btn-sm btn-primary" disabled={escLoading} onClick={() => void runEscalationRealForEvent()}>
                {escLoading ? "Sending…" : "Real send for event (test recipients)"}
              </button>
              <button type="button" className="btn btn-soft btn-sm" disabled={escLoading} onClick={() => void runEscalationDropoutSimulation()}>
                {escLoading ? "Simulating…" : "Simulate dropout trigger"}
              </button>
              <button type="button" className="btn btn-soft btn-sm" disabled={escLoading} onClick={() => void runEscalationEligibility()}>
                {escLoading ? "Checking…" : "Check eligibility"}
              </button>
            </div>
            {escOutput !== null && (
              <pre className="max-h-56 overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed whitespace-pre-wrap">{escOutput}</pre>
            )}
            <div className="flex items-center justify-between gap-2 pt-1">
              <span className="text-xs text-base-content/70">Audit log (read-only)</span>
              <button type="button" className="btn btn-soft btn-xs" disabled={escAuditLoading} onClick={() => void loadEscalationAudit()}>
                {escAuditLoading ? "Loading…" : "Load audit"}
              </button>
            </div>
            {escAuditOutput && <p className="text-xs text-base-content/70">{escAuditOutput}</p>}
            {escAuditEntries.length > 0 && (
              <div className="max-h-56 overflow-auto rounded-box bg-base-300/40 p-2">
                <table className="table table-xs">
                  <thead>
                    <tr>
                      <th>At</th>
                      <th>Event</th>
                      <th>Result</th>
                      <th>Test</th>
                    </tr>
                  </thead>
                  <tbody>
                    {escAuditEntries.map((entry) => (
                      <tr key={entry.id}>
                        <td>{entry.created_at}</td>
                        <td>{entry.event_title || `${entry.otype}${entry.oid}`}</td>
                        <td>{entry.reason_summary || "—"}</td>
                        <td>{entry.is_test ? "yes" : "no"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            <div className="rounded-box bg-base-300/40 p-2">
              <p className="text-xs font-semibold text-base-content/80 mb-1">Escalation examples (mail + UI parity)</p>
              <div className="flex flex-col gap-1">
                {escalationPreviewItems.map((item) => (
                  <a
                    key={item.key}
                    href={item.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-xs underline text-primary"
                  >
                    {item.label}
                  </a>
                ))}
              </div>
            </div>
          </DevPanel>
        </div>

        <div className="grid gap-3 sm:grid-cols-1 md:grid-cols-2">
          {remoteMailItems.map((item) => (
            <HubCard
              key={item.key}
              title={item.title}
              description={item.desc}
              href={item.href}
              external
              iconName={item.icon}
              accentColor={accent}
            />
          ))}
        </div>
      </section>

      <section className="space-y-3">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-base-content/60">Mail design</h2>
        <div className="rounded-box border border-base-300 p-4 space-y-2">
          <div className="flex items-start gap-4">
            <div
              className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-base-content"
              style={{
                background: accent ? `color-mix(in oklch, ${accent} 22%, transparent)` : "var(--muted)",
                color: accent ?? undefined,
              }}
            >
              <TablerIconByName name="file-code" className="h-5 w-5" />
            </div>
            <div>
              <h3 className="font-medium text-base-content">Mail design tokens</h3>
              <p className="mt-1 text-sm text-base-content/70">
                Source: <code className="text-xs">frontend/mail-design-tokens.json</code> — sync into PHP with{" "}
                <code className="text-xs">npm run sync:mail-design</code>.
              </p>
            </div>
          </div>
          <pre className="max-h-[min(70vh,32rem)] overflow-auto rounded-box bg-base-300/40 p-3 text-xs leading-relaxed">{tokensJson}</pre>
        </div>
      </section>
    </PageContent>
  );
}

export default function DeveloperModulePage() {
  return (
    <DeveloperSurfacesGuard>
      <DeveloperModulePageContent />
    </DeveloperSurfacesGuard>
  );
}
