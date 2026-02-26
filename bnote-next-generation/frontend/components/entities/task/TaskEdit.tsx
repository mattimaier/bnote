/**
 * BNote Next Generation - Task edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { tasksApi, type Task } from "@/lib/tasks-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { DatePicker } from "@/components/DatePicker";
import { NotesEditor } from "@/components/NotesEditor";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { SelectPicker } from "@/components/SelectPicker";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

export function TaskEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const isNew = id === "new";

  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [dueAt, setDueAt] = useState("");
  const [assignedTo, setAssignedTo] = useState(0);
  const [contacts, setContacts] = useState<Array<{ id: number; name?: string | null }>>([]);
  const [tours, setTours] = useState<Array<{ id: number; name: string }>>([]);
  const [tourId, setTourId] = useState(0);
  const [item, setItem] = useState<Task | null>(null);
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const loadTask = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    tasksApi
      .get(numId)
      .then((task: Task) => {
        setItem(task);
        setTitle(task.title ?? "");
        setDescription(task.description ?? "");
        setDueAt(task.due_at ? String(task.due_at).slice(0, 16) : "");
        setAssignedTo(task.assigned_to ?? 0);
        setTourId(Array.isArray(task.tourIds) && task.tourIds.length > 0 ? task.tourIds[0] : 0);
      })
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadTask();
    tasksApi.getContacts().then((list) => {
      const opts = (list ?? []).map((c) => ({
        id: c.id,
        name: c.name ?? "",
        email: c.email ?? null,
        instrument: c.instrument ?? null,
      }));
      setContacts([{ id: 0, name: t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "—" }, ...opts]);
    }).catch(() => setContacts([{ id: 0, name: "—" }]));
    tasksApi.getTours().then((list) => {
      const opts = (list ?? []).map((tour) => ({ id: tour.id, name: tour.name ?? "" }));
      setTours([{ id: 0, name: t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "—" }, ...opts]);
    }).catch(() => setTours([{ id: 0, name: "—" }]));
  }, [ready, loadTask]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) {
      setError(t("js.tasks.titleRequired") !== "js.tasks.titleRequired" ? t("js.tasks.titleRequired") : "Title is required");
      showToast(t("js.tasks.titleRequired") !== "js.tasks.titleRequired" ? t("js.tasks.titleRequired") : "Title is required", "error");
      return;
    }
    setSaving(true);
    setError("");
    try {
      const due_at = dueAt ? dueAt.replace(" ", "T") : null;
      const assigned_to = assignedTo > 0 ? assignedTo : null;
      const tour_id = tourId > 0 ? tourId : null;
      if (isNew) {
        const res = await tasksApi.create({
          title: title.trim(),
          description: isEmptyEditorJson(description) ? undefined : (description.trim() || undefined),
          due_at,
          assigned_to,
          tour_id,
        });
        showToast(
          t("js.tasks.created") !== "js.tasks.created" ? t("js.tasks.created") : "Task created",
          "success"
        );
        router.replace(getEntityPath("task", res.id, "view"));
      } else {
        await tasksApi.update(parseInt(id!, 10), {
          title: title.trim(),
          description: isEmptyEditorJson(description) ? undefined : (description.trim() || undefined),
          due_at,
          assigned_to,
        });
        showToast(t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved", "success");
        router.replace(getEntityPath("task", id, "view"));
      }
    } catch (err) {
      const msg = getErrorMessage(err, t, "js.common.saveFailed");
      setError(msg);
      showToast(msg, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = useCallback(() => {
    if (isNew) router.push("/tasks");
    else router.push(getEntityPath("task", id!, "view"));
  }, [isNew, id, router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "task-edit-form",
      onCancel: () => onCancelRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isNew, saving, setEditingBar, clearEditingBar]);

  if (!ready) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && error && !title) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">{error}</p>
      </div>
    );
  }

  const pageTitle = isNew
    ? (t("js.tasks.addTask") !== "js.tasks.addTask" ? t("js.tasks.addTask") : "Add Task")
    : (item?.title ?? (t("js.common.edit") !== "js.common.edit" ? t("js.common.edit") : "Edit"));
  const pageSubtitle = t("js.tasks.subtitle") !== "js.tasks.subtitle" ? t("js.tasks.subtitle") : "Assign and track tasks";

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div>
          <h1 className="text-2xl font-bold text-base-content">{pageTitle}</h1>
          <p className="mt-1 text-sm text-base-content/60">{pageSubtitle}</p>
        </div>
      </div>

      <form id="task-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">
            {error}
          </div>
        )}

        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.title") !== "js.tasks.title" ? t("js.tasks.title") : "Title"} *
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.description") !== "js.tasks.description" ? t("js.tasks.description") : "Description"}
              </label>
              <NotesEditor
                value={description}
                onChange={setDescription}
                placeholder={t("js.news.editorPlaceholder") !== "js.news.editorPlaceholder" ? t("js.news.editorPlaceholder") : "Type or paste content…"}
                id="task-description-editor"
              />
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.assignedTo") !== "js.tasks.assignedTo" ? t("js.tasks.assignedTo") : "Assigned to"}
              </label>
              <SelectPicker
                options={contacts}
                value={assignedTo}
                onChange={setAssignedTo}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel={emptyText}
              />
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.tour") !== "js.tasks.tour" ? t("js.tasks.tour") : "Link to tour"}
              </label>
              <SelectPicker
                options={tours}
                value={tourId}
                onChange={setTourId}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel={emptyText}
              />
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.dueAt") !== "js.tasks.dueAt" ? t("js.tasks.dueAt") : "Due date"}
              </label>
              <DatePicker
                value={dueAt.slice(0, 16)}
                onChange={setDueAt}
                mode="datetime"
                locale={lang}
                appendSeconds
                className="input input-sm w-full text-base-content"
              />
            </div>
          </div>
        </div>
      </form>

      {!isNew && (
        <DetailDeleteSection
          canDelete
          entityTitle={title || undefined}
          onDelete={async () => {
            await tasksApi.delete(parseInt(id!, 10));
            showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
            router.push("/tasks");
          }}
        />
      )}
    </div>
  );
}
