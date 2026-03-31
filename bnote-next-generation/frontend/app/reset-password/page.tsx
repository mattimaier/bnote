/**
 * BNote Next Generation - Request password reset
 */

"use client";

import Link from "next/link";
import { Suspense, useEffect, useMemo, useState } from "react";
import { I18nProvider, useI18n } from "@/contexts/I18nContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { BNoteLogo } from "@/components/BNoteLogo";
import { Spinner } from "@/components/Spinner";
import { LegalFooter } from "@/components/auth/LegalFooter";
import { requestPasswordReset } from "@/lib/auth";
import { translatePasswordResetApiError } from "@/lib/password-reset-errors";
import { api } from "@/lib/api";

function ResetPasswordInner() {
  const { t, ready } = useI18n();
  const [identifier, setIdentifier] = useState("");
  const [error, setError] = useState("");
  const [done, setDone] = useState(false);
  const [devUrl, setDevUrl] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [demoMode, setDemoMode] = useState(false);

  useEffect(() => {
    if (!ready) return;
    api
      .get<{ demo_mode?: boolean }>("auth", "getPublicConfig")
      .then((c) => setDemoMode(Boolean(c?.demo_mode)))
      .catch(() => setDemoMode(false));
  }, [ready]);

  /** API may return a root-relative URL when BNOTE_NEXT_GENERATION_PUBLIC_URL is unset; show absolute for copy/open. */
  const devUrlAbsolute = useMemo(() => {
    if (!devUrl) return null;
    if (typeof window === "undefined") return devUrl;
    if (devUrl.startsWith("http://") || devUrl.startsWith("https://")) return devUrl;
    if (devUrl.startsWith("/")) return `${window.location.origin}${devUrl}`;
    return devUrl;
  }, [devUrl]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setSubmitting(true);
    setDevUrl(null);
    try {
      const data = await requestPasswordReset(identifier.trim());
      setDone(Boolean(data?.ok));
      if (data?.dev_reset_url) {
        setDevUrl(data.dev_reset_url);
      }
    } catch (err) {
      const e = err as Error & { status?: number };
      setDone(false);
      setError(translatePasswordResetApiError(e.message || "", t));
    } finally {
      setSubmitting(false);
    }
  }

  if (!ready) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="w-full max-w-md pb-6 sm:mx-4 sm:pb-0">
      <div className="fixed top-3 right-3 z-50 sm:top-4 sm:right-4">
        <ThemeToggle />
      </div>
      <div className="construction-tape fixed right-0 bottom-0 left-0 z-40 sm:static">
        <div className="construction-tape-text">
          <span className="font-bold">BNote Next Generation</span>
          <span>Under Construction</span>
        </div>
      </div>

      <div className="login-form rounded-xl bg-base-200 p-4 shadow-none sm:relative sm:overflow-hidden sm:rounded-b-lg sm:bg-base-200 sm:p-8 sm:shadow-lg">
        <div className="mb-6 text-center sm:mb-8">
          <div className="mx-auto mb-4 flex justify-center">
            <BNoteLogo size="lg" padding="tight" />
          </div>
          <h1 className="mb-2 text-2xl font-bold text-base-content">{t("js.resetPassword.title")}</h1>
          <p className="text-base-content/60">{t("js.resetPassword.subtitle")}</p>
        </div>

        {done ? (
          <div className="space-y-4">
            <p className="text-sm text-base-content/85">{t("js.resetPassword.requestSent")}</p>
            {devUrl && devUrlAbsolute ? (
              <div className="rounded-lg border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-base-content">
                <p className="mb-2 font-medium">{t("js.resetPassword.demoLinkHint")}</p>
                <p className="mb-1 text-xs text-base-content/70">{t("js.resetPassword.demoUrlLabel")}</p>
                <pre className="mb-3 max-h-40 overflow-auto whitespace-pre-wrap break-all rounded-md bg-base-200 p-3 font-mono text-xs leading-snug text-base-content">
                  <code id="demo-password-reset-url">{devUrlAbsolute}</code>
                </pre>
                <a href={devUrl} className="btn btn-secondary btn-block btn-sm">
                  {t("js.resetPassword.demoOpenLink")}
                </a>
              </div>
            ) : demoMode && done ? (
              <p className="rounded-lg border border-base-300 bg-base-200/80 px-4 py-3 text-xs text-base-content/80">
                {t("js.resetPassword.demoNoUrlHint")}
              </p>
            ) : null}
            <Link href="/login/" className="btn btn-primary btn-block">
              {t("js.resetPassword.backToLogin")}
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-5 sm:space-y-6">
            <div>
              <label
                htmlFor="reset-identifier"
                className="mb-2 block text-sm font-medium text-base-content"
              >
                {t("js.resetPassword.identifierLabel")}
              </label>
              <input
                id="reset-identifier"
                name="identifier"
                type="text"
                autoComplete="username"
                value={identifier}
                onChange={(e) => setIdentifier(e.target.value)}
                required
                placeholder={t("js.resetPassword.identifierPlaceholder")}
                className="input input-md w-full"
              />
            </div>
            {error ? (
              <div className="rounded-lg border border-error/20 bg-error/10 px-4 py-3 text-sm text-error">
                {error}
              </div>
            ) : null}
            <button type="submit" disabled={submitting} className="btn btn-primary btn-lg btn-block">
              {submitting ? t("js.resetPassword.submitting") : t("js.resetPassword.submit")}
            </button>
          </form>
        )}
      </div>
      <LegalFooter />
    </div>
  );
}

function ResetPasswordForm() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
          <Spinner />
        </div>
      }
    >
      <ResetPasswordInner />
    </Suspense>
  );
}

export default function ResetPasswordPage() {
  return (
    <I18nProvider>
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100 px-0 sm:px-4">
        <ResetPasswordForm />
      </div>
    </I18nProvider>
  );
}
