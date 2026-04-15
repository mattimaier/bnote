/**
 * BNote Next Generation - Complete password reset (token from email)
 */

"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useMemo, useState } from "react";
import { I18nProvider, useI18n } from "@/contexts/I18nContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { BNoteLogo } from "@/components/BNoteLogo";
import { Spinner } from "@/components/Spinner";
import { LegalFooter } from "@/components/auth/LegalFooter";
import { PasswordStrengthField } from "@/components/auth/PasswordStrengthField";
import { completePasswordReset, LOGIN_POST_RESET_BANNER_KEY } from "@/lib/auth";
import { translatePasswordResetApiError } from "@/lib/password-reset-errors";
import { registerPasswordValid } from "@/lib/register-field-validation";

function ConfirmInner() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t, ready } = useI18n();
  const token = (searchParams.get("token")?.trim() ?? "").toLowerCase();
  const [pw1, setPw1] = useState("");
  const [pw2, setPw2] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const canSubmit = useMemo(() => {
    if (!token || token.length !== 64) return false;
    if (pw1.length < 6 || !registerPasswordValid(pw1)) return false;
    if (pw1 !== pw2) return false;
    return true;
  }, [token, pw1, pw2]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    if (!token || token.length !== 64) {
      setError(t("js.resetPassword.missingToken"));
      return;
    }
    if (!canSubmit) {
      setError(t("js.resetPassword.validationError"));
      return;
    }
    setSubmitting(true);
    try {
      await completePasswordReset(token, pw1, pw2);
      if (typeof window !== "undefined") {
        sessionStorage.setItem(LOGIN_POST_RESET_BANNER_KEY, "1");
      }
      router.replace("/login/");
    } catch (err) {
      const ex = err as Error & { status?: number };
      setError(translatePasswordResetApiError(ex.message || "", t));
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

  const missingToken = !token || token.length !== 64;

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
          <h1 className="mb-2 text-2xl font-bold text-base-content">
            {missingToken ? t("js.resetPassword.confirmInvalidTitle") : t("js.resetPassword.confirmTitle")}
          </h1>
          <p className="text-base-content/60">
            {missingToken ? t("js.resetPassword.confirmInvalidSubtitle") : t("js.resetPassword.confirmSubtitle")}
          </p>
        </div>

        {missingToken ? (
          <div className="space-y-4">
            <Link href="/reset-password/" className="btn btn-primary btn-block">
              {t("js.resetPassword.title")}
            </Link>
            <Link href="/login/" className="btn btn-outline btn-block text-base-content">
              {t("js.resetPassword.backToLogin")}
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-5 sm:space-y-6">
            <div>
              <label htmlFor="reset-pw1" className="mb-2 block text-sm font-medium text-base-content">
                {t("js.resetPassword.newPassword")}
              </label>
              <PasswordStrengthField
                id="reset-pw1"
                value={pw1}
                onChange={setPw1}
                autoComplete="new-password"
                placeholder={t("js.login.passwordPlaceholder")}
                showMeter={false}
              />
            </div>
            <div>
              <label htmlFor="reset-pw2" className="mb-2 block text-sm font-medium text-base-content">
                {t("js.resetPassword.confirmPassword")}
              </label>
              <input
                id="reset-pw2"
                type="password"
                autoComplete="new-password"
                value={pw2}
                onChange={(e) => setPw2(e.target.value)}
                className="input input-md w-full"
              />
            </div>
            {error ? (
              <div className="rounded-lg border border-error/20 bg-error/10 px-4 py-3 text-sm text-error">{error}</div>
            ) : null}
            <button type="submit" disabled={submitting || !canSubmit} className="btn btn-primary btn-lg btn-block">
              {submitting ? t("js.resetPassword.saving") : t("js.resetPassword.save")}
            </button>
          </form>
        )}
      </div>
      <LegalFooter />
    </div>
  );
}

function ConfirmForm() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
          <Spinner />
        </div>
      }
    >
      <ConfirmInner />
    </Suspense>
  );
}

export default function ResetPasswordConfirmPage() {
  return (
    <I18nProvider>
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100 px-0 sm:px-4">
        <ConfirmForm />
      </div>
    </I18nProvider>
  );
}
