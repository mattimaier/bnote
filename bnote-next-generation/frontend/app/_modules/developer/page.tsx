/**
 * BNote Next Generation — Developer tools hub (API debug, entity mocks, mail previews).
 * Copy is English-only; this module is not localized.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useCallback, useState, type ReactNode } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { ChevronRight, TablerIconByName } from "@/components/icons";
import { DeveloperSurfacesGuard } from "@/components/debug/DeveloperSurfacesGuard";
import { getApiDebugScriptUrl } from "@/lib/api";
import { getEntityConfig } from "@/lib/entity-config";
import mailDesignTokens from "@/mail-design-tokens.json";
import { MAIL_TEST_LOCALES, MAIL_TEST_TEMPLATES } from "@/lib/mail-test-templates";

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

  const tokensJson = JSON.stringify(mailDesignTokens, null, 2);

  return (
    <PageContent className="px-1 md:px-4 space-y-8">
      <AppPageHeader
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
