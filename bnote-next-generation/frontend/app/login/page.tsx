/**
 * BNote Next Generation - Login Page
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState, useEffect, Suspense } from "react";
import { checkSession, login, LOGIN_POST_RESET_BANNER_KEY } from "@/lib/auth";
import { api } from "@/lib/api";
import { I18nProvider, useI18n } from "@/contexts/I18nContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { BNoteLogo } from "@/components/BNoteLogo";
import { safeString } from "@/lib/string-utils";
import { Spinner } from "@/components/Spinner";
import { LegalFooter } from "@/components/auth/LegalFooter";

interface PublicConfig {
  lang?: string;
  country?: string | null;
  company?: unknown;
  user_registration?: boolean;
  auto_user_activation?: boolean;
}

function LoginFormInner() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t, ready } = useI18n();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [welcomeText, setWelcomeText] = useState("Welcome");
  const [userRegistration, setUserRegistration] = useState(false);
  const [resetSuccessBanner, setResetSuccessBanner] = useState(false);

  useEffect(() => {
    if (typeof window !== "undefined" && sessionStorage.getItem(LOGIN_POST_RESET_BANNER_KEY)) {
      setResetSuccessBanner(true);
      sessionStorage.removeItem(LOGIN_POST_RESET_BANNER_KEY);
    }
  }, []);

  useEffect(() => {
    checkSession().then((session) => {
      if (session.authenticated) {
        const redirect = searchParams.get("redirect") ?? "/dashboard";
        router.replace(redirect);
      }
    });
  }, [router, searchParams]);

  useEffect(() => {
    if (!ready) return;
    api
      .get<PublicConfig>("auth", "getPublicConfig")
      .then((config) => {
        const company = safeString(config?.company);
        setWelcomeText(
          company ? t("js.dashboard.subtitle", [company]) : t("js.common.appName")
        );
        setUserRegistration(Boolean(config?.user_registration));
      })
      .catch(() => {
        setWelcomeText(t("js.common.appName"));
      });
  }, [ready, t]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await login(username, password);
      const redirect = searchParams.get("redirect") ?? "/dashboard";
      router.replace(redirect);
    } catch (err) {
      setError(
        err instanceof Error
          ? err.message
          : t("js.login.loginFailed")
      );
    } finally {
      setLoading(false);
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
      {/* Construction tape banner */}
      <div className="construction-tape fixed right-0 bottom-0 left-0 z-40 sm:static">
        <div className="construction-tape-text">
          <span className="font-bold">BNote Next Generation</span>
          <svg
            className="inline-block h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
            />
          </svg>
          <span>Under Construction</span>
        </div>
      </div>

      <div className="login-form rounded-xl bg-base-200 p-4 shadow-none sm:relative sm:overflow-hidden sm:rounded-b-lg sm:bg-base-200 sm:p-8 sm:shadow-lg">
        <div className="mb-6 text-center sm:mb-8">
          <div className="mx-auto mb-4 flex justify-center">
            <BNoteLogo size="lg" padding="tight" />
          </div>
          <h1 className="mb-2 text-2xl font-bold text-base-content">
            {t("js.common.appName")}
          </h1>
          <p className="text-base-content/60">{welcomeText}</p>
        </div>

        {resetSuccessBanner ? (
          <div className="mb-4 rounded-lg border border-success/25 bg-success/10 px-4 py-3 text-sm text-success">
            {t("js.login.passwordResetSuccess")}
          </div>
        ) : null}

        <form onSubmit={handleSubmit} className="space-y-5 sm:space-y-6">
          <div>
            <label
              htmlFor="username"
              className="mb-2 block text-sm font-medium text-base-content"
            >
              {t("js.login.usernameLabel")}
            </label>
            <input
              id="username"
              name="username"
              type="text"
              autoComplete="username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              placeholder={t("js.login.usernamePlaceholder")}
              className="input input-md w-full"
            />
          </div>
          <div>
            <label
              htmlFor="password"
              className="mb-2 block text-sm font-medium text-base-content"
            >
              {t("js.login.passwordLabel")}
            </label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder={t("js.login.passwordPlaceholder")}
              className="input input-md w-full"
            />
          </div>
          {error && (
            <div className="rounded-lg border border-error/20 bg-error/10 px-4 py-3 text-sm text-error">
              {error}
            </div>
          )}
          <button
            type="submit"
            disabled={loading}
            className="btn btn-primary btn-lg btn-block"
          >
            {loading ? t("js.login.loggingIn") : t("js.login.login")}
          </button>
        </form>
        <div className="mt-6 space-y-3 text-center text-sm text-base-content/70">
          <p>
            <Link href="/reset-password/" className="link link-primary font-medium">
              {t("js.login.forgotPasswordLink")}
            </Link>
          </p>
          {userRegistration ? (
            <p>
              {t("js.login.signUpQuestion")}{" "}
              <Link href="/register/" className="link link-primary font-medium">
                {t("js.login.signUpLink")}
              </Link>
            </p>
          ) : null}
        </div>
      </div>
      <LegalFooter />
    </div>
  );
}

function LoginForm() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
          <Spinner />
        </div>
      }
    >
      <LoginFormInner />
    </Suspense>
  );
}

export default function LoginPage() {
  return (
    <I18nProvider>
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100 px-0 sm:px-4">
        <LoginForm />
      </div>
    </I18nProvider>
  );
}
