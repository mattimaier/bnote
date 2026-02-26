/**
 * BNote Next Generation - Group task create form (full page, standard edit mode pattern)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { tasksApi } from "@/lib/tasks-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { MultiSelect } from "@/components/entities/event/MultiSelect";
import { DatePicker } from "@/components/DatePicker";
import { NotesEditor } from "@/components/NotesEditor";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";

export function GroupTaskEdit() {
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const { showToast } = useToast();

  const [groupIds, setGroupIds] = useState<number[]>([]);
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [dueAt, setDueAt] = useState("");
  const [groups, setGroups] = useState<Array<{ id: number; name: string }>>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!ready) return;
    tasksApi.getGroups().then((list) => setGroups(list ?? [])).catch(() => setGroups([]));
  }, [ready]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (groupIds.length === 0 || !title.trim()) {
      const msg =
        t("js.tasks.groupsAndTitleRequired") !== "js.tasks.groupsAndTitleRequired"
          ? t("js.tasks.groupsAndTitleRequired")
          : "Please select at least one group and enter a title";
      setError(msg);
      showToast(msg, "error");
      return;
    }
    setSaving(true);
    setError("");
    try {
      await tasksApi.createGroupTasks({
        groupIds,
        title: title.trim(),
        description: isEmptyEditorJson(description) ? undefined : (description.trim() || undefined),
        due_at: dueAt.trim() || null,
      });
      showToast(
        t("js.tasks.groupTaskSuccess") !== "js.tasks.groupTaskSuccess"
          ? t("js.tasks.groupTaskSuccess")
          : "Tasks created successfully",
        "success"
      );
      router.push("/tasks");
    } catch (err) {
      const msg = getErrorMessage(err, t, "js.common.failedToSave");
      setError(msg);
      showToast(msg, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = useCallback(() => {
    router.push("/tasks");
  }, [router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew: true,
      saving,
      submitFormId: "group-task-edit-form",
      onCancel: () => onCancelRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [saving, setEditingBar, clearEditingBar]);

  if (!ready) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  const pageTitle =
    t("js.tasks.addGroupTask") !== "js.tasks.addGroupTask" ? t("js.tasks.addGroupTask") : "Add group task";
  const pageSubtitle =
    t("js.tasks.subtitle") !== "js.tasks.subtitle" ? t("js.tasks.subtitle") : "Assign and track tasks";

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div>
          <h1 className="text-2xl font-bold text-base-content">{pageTitle}</h1>
          <p className="mt-1 text-sm text-base-content/60">{pageSubtitle}</p>
        </div>
      </div>

      <form id="group-task-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">
            {error}
          </div>
        )}

        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.groups") !== "js.tasks.groups" ? t("js.tasks.groups") : "Groups"} *
              </label>
              <MultiSelect
                options={groups.map((g) => ({ id: g.id, name: g.name }))}
                selected={groupIds}
                onChange={setGroupIds}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                showChips
              />
            </div>

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
                placeholder={
                  t("js.news.editorPlaceholder") !== "js.news.editorPlaceholder"
                    ? t("js.news.editorPlaceholder")
                    : "Type or paste content…"
                }
                id="group-task-description-editor"
              />
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.tasks.dueAt") !== "js.tasks.dueAt" ? t("js.tasks.dueAt") : "Due"}
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
    </div>
  );
}
