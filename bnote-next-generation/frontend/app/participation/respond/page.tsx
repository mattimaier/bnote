/**
 * Landing page for event invite participation links (?token=&choice=).
 */

"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useCallback, useEffect, useLayoutEffect, useRef, useState } from "react";
import { I18nProvider, useI18n } from "@/contexts/I18nContext";
import { ToastProvider } from "@/contexts/ToastContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { BNoteLogo } from "@/components/BNoteLogo";
import { Spinner } from "@/components/Spinner";
import { LegalFooter } from "@/components/auth/LegalFooter";
import { BugReportModal } from "@/components/bug-report/BugReportModal";
import { ToastContainer } from "@/components/ToastContainer";
import {
  applyParticipationToken,
  getParticipationTokenInfo,
  parseParticipationChoice,
  participationEntityHref,
  type ApplyParticipationResult,
} from "@/lib/participation-magic";
import { api } from "@/lib/api";
import { checkSession } from "@/lib/auth";
import { formatDateTimeShort } from "@/lib/date-time";
import { initBugReportDiagnostics } from "@/lib/bug-report-diagnostics";

interface PublicConfig {
  beta_bug_report_enabled?: boolean;
}

function ParticipateInner() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t, ready, lang } = useI18n();
  const token = (searchParams.get("token")?.trim() ?? "").toLowerCase();
  const choice = parseParticipationChoice(searchParams.get("choice"));
  const [state, setState] = useState<"idle" | "form" | "loading" | "ok" | "err">("idle");
  const [errMsg, setErrMsg] = useState("");
  const [comment, setComment] = useState("");
  const [entityHref, setEntityHref] = useState<string | null>(null);
  const [expiryLabel, setExpiryLabel] = useState<string | null>(null);
  const [bugReportEnabled, setBugReportEnabled] = useState(false);
  const [bugReportOpen, setBugReportOpen] = useState(false);
  const ranYes = useRef(false);

  const needsCommentStep = choice === "maybe" || choice === "no";

  useLayoutEffect(() => {
    if (!ready) return;
    if (token.length !== 64 || !choice) {
      setState("err");
      setErrMsg(t("js.participation.respond.missingParams"));
      return;
    }
    if (needsCommentStep) {
      setState("form");
    } else if (choice === "yes") {
      setState("loading");
    }
  }, [ready, token, choice, needsCommentStep, t]);

  useEffect(() => {
    initBugReportDiagnostics();
  }, []);

  useEffect(() => {
    if (!ready) return;
    api
      .get<PublicConfig>("auth", "getPublicConfig")
      .then((config) => {
        setBugReportEnabled(Boolean(config?.beta_bug_report_enabled));
      })
      .catch(() => {
        setBugReportEnabled(false);
      });
  }, [ready]);

  useEffect(() => {
    if (!ready || token.length !== 64 || !choice) return;
    let cancelled = false;
    getParticipationTokenInfo(token)
      .then((info) => {
        if (cancelled || !info.expiresAt) return;
        const formatted = formatDateTimeShort(info.expiresAt, lang);
        if (formatted) setExpiryLabel(formatted);
      })
      .catch(() => {});
    return () => {
      cancelled = true;
    };
  }, [ready, token, choice, lang]);

  const finishSuccess = useCallback(
    async (result: ApplyParticipationResult) => {
      const href = participationEntityHref(result.eventType, result.eventId);
      setEntityHref(href);
      try {
        const session = await checkSession();
        if (session.authenticated && href) {
          router.replace(href);
          return;
        }
      } catch {
        /* treat as logged out */
      }
      setState("ok");
    },
    [router]
  );

  useEffect(() => {
    if (!ready) return;
    if (token.length !== 64 || !choice) return;
    if (needsCommentStep) return;
    if (ranYes.current) return;
    ranYes.current = true;
    setState("loading");
    applyParticipationToken(token, choice)
      .then(finishSuccess)
      .catch((err: Error & { status?: number }) => {
        setState("err");
        setErrMsg(t("js.participation.respond.error"));
        if (process.env.NODE_ENV === "development") {
          console.warn("applyParticipationToken", err?.message, err?.status);
        }
      });
  }, [ready, token, choice, needsCommentStep, t, finishSuccess]);

  const submitWithComment = () => {
    if (!choice || (choice !== "maybe" && choice !== "no")) return;
    setState("loading");
    applyParticipationToken(token, choice, comment.trim() || undefined)
      .then(finishSuccess)
      .catch((err: Error & { status?: number }) => {
        setState("err");
        setErrMsg(t("js.participation.respond.error"));
        if (process.env.NODE_ENV === "development") {
          console.warn("applyParticipationToken", err?.message, err?.status);
        }
      });
  };

  if (!ready) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
        <Spinner />
      </div>
    );
  }

  const loginHref = "/login/";
  const loginHrefWithEntityRedirect =
    entityHref !== null && entityHref !== ""
      ? `${loginHref}?redirect=${encodeURIComponent(entityHref)}`
      : loginHref;
  const bugReportLabel =
    t("js.bugReport.openButton") !== "js.bugReport.openButton"
      ? t("js.bugReport.openButton")
      : "Report bug";

  return (
    <div className="w-full max-w-md pb-6 sm:mx-4 sm:pb-0">
      <div className="fixed top-3 right-3 z-50 flex items-center gap-2 sm:top-4 sm:right-4">
        <ThemeToggle inline />
        {bugReportEnabled ? (
          <button
            type="button"
            onClick={() => setBugReportOpen(true)}
            className="btn btn-soft btn-sm px-2.5 sm:px-3"
            title={bugReportLabel}
            aria-label={bugReportLabel}
          >
            <span className="icon-[tabler--bug] h-4 w-4" aria-hidden />
            <span className="hidden sm:inline">{bugReportLabel}</span>
          </button>
        ) : null}
      </div>
      <div className="construction-tape fixed right-0 bottom-0 left-0 z-40 sm:static">
        <div className="construction-tape-text">
          <span className="font-bold">BNote Next Generation</span>
        </div>
      </div>
      <div className="card bg-base-100 border-base-300 mx-auto mt-8 border shadow-sm sm:mt-12">
        <div className="card-body gap-4">
          <div className="flex flex-col items-center gap-2">
            <BNoteLogo className="h-10 w-auto" />
            <h1 className="text-center text-xl font-semibold">{t("js.participation.respond.title")}</h1>
            {expiryLabel ? (
              <p className="text-base-content/70 max-w-sm px-1 text-center text-xs">
                {t("js.participation.respond.linkValidity", [expiryLabel])}
              </p>
            ) : null}
          </div>
          {state === "form" && choice && (
            <div className="flex flex-col gap-3">
              <p className="text-base-content/80 text-center text-sm">{t("js.participation.respond.commentHint")}</p>
              <textarea
                className="textarea textarea-bordered w-full min-h-[100px] text-sm"
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                placeholder={t("js.participation.respond.commentPlaceholder")}
                maxLength={2000}
                rows={4}
              />
              <button type="button" className="btn btn-primary btn-sm" onClick={submitWithComment}>
                {t("js.participation.respond.submit")}
              </button>
            </div>
          )}
          {state === "loading" && (
            <div className="text-base-content/80 flex items-center justify-center gap-2 text-center text-sm">
              <Spinner size="sm" />
              <span>{t("js.participation.respond.working")}</span>
            </div>
          )}
          {state === "ok" && (
            <>
              <p className="text-success text-center text-sm">{t("js.participation.respond.success")}</p>
              <div className="card-actions justify-center pt-2">
                <Link href={loginHrefWithEntityRedirect} className="btn btn-primary btn-sm">
                  {t("js.participation.respond.signInToOpenEvent")}
                </Link>
              </div>
            </>
          )}
          {state === "err" && (
            <>
              <p className="text-error text-center text-sm">{errMsg}</p>
              <div className="card-actions justify-center pt-2">
                <Link href={loginHref} className="btn btn-primary btn-sm">
                  {t("js.participation.respond.backToLogin")}
                </Link>
              </div>
            </>
          )}
        </div>
      </div>
      <div className="mt-8 px-2">
        <LegalFooter />
      </div>
      <BugReportModal open={bugReportOpen} onClose={() => setBugReportOpen(false)} />
    </div>
  );
}

export default function ParticipationRespondPage() {
  return (
    <I18nProvider>
      <ToastProvider>
        <div className="flex min-h-screen flex-col items-center justify-start bg-base-200 px-3 py-8 sm:bg-base-100 sm:py-12">
          <Suspense
            fallback={
              <div className="flex min-h-[40vh] items-center justify-center">
                <Spinner />
              </div>
            }
          >
            <ParticipateInner />
          </Suspense>
        </div>
        <ToastContainer />
      </ToastProvider>
    </I18nProvider>
  );
}
