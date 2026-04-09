/**
 * BNote Next Generation - Task detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { tasksApi, type Task } from "@/lib/tasks-api";
import { formatDateTimeShort } from "@/lib/date-time";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { NotesContent } from "@/components/NotesContent";
import { PersonIdentityRow } from "@/components/PersonIdentityRow";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { getErrorMessage } from "@/lib/error-utils";
import { Spinner } from "@/components/Spinner";

export function TaskDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const [item, setItem] = useState<Task | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const loadTask = () => {
    if (!id || id === "new" || !ready) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    tasksApi
      .get(numId)
      .then(setItem)
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadTask();
  }, [id, ready]);

  const handleComplete = async (complete: boolean) => {
    if (!item) return;
    setSubmitting(true);
    try {
      await tasksApi.complete(item.id, complete);
      showToast(
        complete
          ? (t("js.tasks.complete") !== "js.tasks.complete" ? t("js.tasks.complete") : "Task completed")
          : (t("js.tasks.reopened") !== "js.tasks.reopened" ? t("js.tasks.reopened") : "Task reopened"),
        "success"
      );
      loadTask();
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToSave"), "error");
    } finally {
      setSubmitting(false);
    }
  };

  if (!ready) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (id === "new") return null;

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">
          {error || (t("js.tasks.notFound") !== "js.tasks.notFound" ? t("js.tasks.notFound") : "Task not found.")}
        </p>
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader
        title={item.title || emptyText}
        right={
          <DetailEditButton onClick={() => router.push(getEntityPath("task", item.id, "edit"))} />
        }
      />

      <DetailCard>
        <div className="space-y-4">
          <div className="flex flex-wrap items-center gap-3">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={!!item.is_complete}
                disabled={submitting}
                onChange={(e) => handleComplete(e.target.checked)}
                className="checkbox checkbox-primary checkbox-sm"
              />
              <span className="text-sm font-medium">
                {item.is_complete
                  ? (t("js.tasks.completed") !== "js.tasks.completed" ? t("js.tasks.completed") : "Completed")
                  : (t("js.tasks.complete") !== "js.tasks.complete" ? t("js.tasks.complete") : "Complete")}
              </span>
            </label>
          </div>

          {item.assignee && (
            <div>
              <span className="text-base-content/60 text-sm">
                {t("js.tasks.assignedTo") !== "js.tasks.assignedTo" ? t("js.tasks.assignedTo") : "Assigned to"}:
              </span>{" "}
              <PersonIdentityRow
                name={item.assigneeName || item.assignee}
                email={item.assigneeEmail}
                avatarSize={24}
                compact
                className="inline-flex align-middle"
                nameClassName="text-sm"
              />
            </div>
          )}

          <div>
            <span className="text-base-content/60 text-sm">
              {t("js.tasks.dueAt") !== "js.tasks.dueAt" ? t("js.tasks.dueAt") : "Due"}:
            </span>{" "}
            {item.due_at ? formatDateTimeShort(item.due_at, lang) : emptyText}
          </div>

          {item.description && !isEmptyEditorJson(item.description) && (
            <div>
              <span className="text-base-content/60 text-sm block mb-1">
                {t("js.tasks.description") !== "js.tasks.description" ? t("js.tasks.description") : "Description"}:
              </span>
              <NotesContent value={item.description} className="text-sm" />
            </div>
          )}

          {item.is_complete && item.completed_at && (
            <div className="text-sm text-base-content/60">
              {t("js.tasks.completedAt") !== "js.tasks.completedAt" ? t("js.tasks.completedAt") : "Completed at"}:
              {formatDateTimeShort(item.completed_at, lang)}
            </div>
          )}
        </div>
      </DetailCard>
    </div>
  );
}
