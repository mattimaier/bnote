/**
 * BNote Next Generation - News Module Page
 * Admin-only: edit news shown on the dashboard (shared NotesEditor).
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";
import { useToast } from "@/contexts/ToastContext";
import { api } from "@/lib/api";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { NotesEditor } from "@/components/NotesEditor";
import { AppPageHeader } from "@/components/AppPageHeader";
import { Spinner } from "@/components/Spinner";

interface NewsGetResponse {
  content: string;
}

export default function NewsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const router = useRouter();
  const [content, setContent] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [forbidden, setForbidden] = useState(false);
  const autosaveTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const skipNextAutosaveRef = useRef(true);

  useEffect(() => {
    if (!ready) return;

    let cancelled = false;
    setLoading(true);
    setError("");
    setForbidden(false);

    api
      .get<NewsGetResponse>("news", "get")
      .then((res) => {
        if (cancelled) return;
        const raw = res?.content ?? "";
        setContent(isEmptyEditorJson(raw) ? "" : raw);
      })
      .catch((err: unknown) => {
        if (cancelled) return;
        const status = (err as { status?: number })?.status;
        if (status === 403) {
          setForbidden(true);
          setError("");
        } else {
          setError(
            t("js.news.loadError") !== "js.news.loadError"
              ? t("js.news.loadError")
              : err instanceof Error
                ? err.message
                : "Failed to load news"
          );
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
      if (autosaveTimeoutRef.current) {
        clearTimeout(autosaveTimeoutRef.current);
        autosaveTimeoutRef.current = null;
      }
    };
  }, [ready, t]);

  useEffect(() => {
    if (loading) return;
    if (skipNextAutosaveRef.current) {
      skipNextAutosaveRef.current = false;
      return;
    }
    if (autosaveTimeoutRef.current) clearTimeout(autosaveTimeoutRef.current);
    autosaveTimeoutRef.current = setTimeout(() => {
      autosaveTimeoutRef.current = null;
      const payload = isEmptyEditorJson(content) ? "" : content;
      api.post("news", "save", { content: payload }).catch(() => {
        // Silent fail for autosave
      });
    }, 2000);
    return () => {
      if (autosaveTimeoutRef.current) {
        clearTimeout(autosaveTimeoutRef.current);
        autosaveTimeoutRef.current = null;
      }
    };
  }, [content, loading]);

  const handleSave = useCallback(async () => {
    setSaving(true);
    try {
      const payload = isEmptyEditorJson(content) ? "" : content;
      await api.post("news", "save", { content: payload });
      showToast(
        t("js.news.saved") !== "js.news.saved" ? t("js.news.saved") : "News saved",
        "success"
      );
    } catch (err: unknown) {
      showToast(
        t("js.news.saveError") !== "js.news.saveError"
          ? t("js.news.saveError")
          : err instanceof Error
            ? err.message
            : "Failed to save",
        "error"
      );
    } finally {
      setSaving(false);
    }
  }, [content, showToast, t]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (forbidden) {
    return (
      <div className={PAGE_CONTENT_BASE_CLASS}>
        <div
          className="rounded-lg border border-border/40 p-6 bg-card text-card-foreground"
          style={{ borderColor: "var(--destructive)" }}
        >
          <p className="text-destructive font-medium">
            {t("js.news.accessDenied") !== "js.news.accessDenied"
              ? t("js.news.accessDenied")
              : "You do not have permission to edit news."}
          </p>
          <button
            type="button"
            onClick={() => router.push("/dashboard")}
            className="btn btn-primary btn-sm mt-3"
          >
            {t("js.news.backToDashboard") !== "js.news.backToDashboard"
              ? t("js.news.backToDashboard")
              : t("js.common.back") !== "js.common.back"
                ? t("js.common.back")
                : "Back to dashboard"}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_BASE_CLASS}>
      <AppPageHeader
        moduleKey="news"
        title={t("js.sidebar.news") !== "js.sidebar.news" ? t("js.sidebar.news") : "News"}
        subtitle={t("js.news.subtitle") !== "js.news.subtitle" ? t("js.news.subtitle") : "Edit the message shown on the dashboard."}
      />

      {error && (
        <div
          className="rounded-lg border px-4 py-3 mb-4"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {error}
        </div>
      )}

      <div className="card card-border shadow-none rounded-xl border border-border/40 overflow-hidden">
        <div className="card-body p-0">
          <div className="bg-base-100 shadow-base-300/20 rounded-box w-full p-4 min-h-[280px]">
            {loading && (
              <div className="flex items-center justify-center py-12">
                <Spinner />
              </div>
            )}
            {!loading && (
              <NotesEditor
                key="news-editor"
                value={isEmptyEditorJson(content) ? "" : content}
                onChange={setContent}
                placeholder={
                  t("js.news.editorPlaceholder") !== "js.news.editorPlaceholder"
                    ? t("js.news.editorPlaceholder")
                    : "Type or paste content…"
                }
                id="news-editor-holder"
                minHeight="280px"
              />
            )}
          </div>
          <div className="card-actions justify-end px-4 py-3 border-t border-border/30">
            <button
              type="button"
              onClick={handleSave}
              disabled={loading || saving}
              className="btn btn-primary"
            >
              {saving ? (
                <span className="flex items-center gap-2">
                  <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                  {t("js.common.saving") !== "js.common.saving"
                    ? t("js.common.saving")
                    : "Saving…"}
                </span>
              ) : (
                t("js.news.save") !== "js.news.save" ? t("js.news.save") : "Save"
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
