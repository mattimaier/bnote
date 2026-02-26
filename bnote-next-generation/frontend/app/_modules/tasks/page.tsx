/**
 * BNote Next Generation - Tasks List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { tasksApi, type Task } from "@/lib/tasks-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { getColor, getPillStyle, getDotStyle } from "@/lib/entity-config";
import { formatDateTimeShort } from "@/lib/date-time";
import { ArrowUp, ArrowDown, ArrowUpDown, Plus } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { notesToPlainText } from "@/lib/editorjs-notes";
import { PageContent } from "@/components/PageContent";

type SortKey = "title" | "assignee" | "due_at";

export default function TasksPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [openItems, setOpenItems] = useState<Task[]>([]);
  const [completedItems, setCompletedItems] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [openSortKey, setOpenSortKey] = useState<SortKey | null>(null);
  const [openSortDir, setOpenSortDir] = useState<SortDirection>("asc");
  const [completedSortKey, setCompletedSortKey] = useState<SortKey | null>(null);
  const [completedSortDir, setCompletedSortDir] = useState<SortDirection>("desc");

  const loadTasks = useCallback(async () => {
    setLoading(true);
    try {
      const [openList, completedList] = await Promise.all([
        tasksApi.list({ open: true }),
        tasksApi.list({ open: false }),
      ]);
      setOpenItems(openList ?? []);
      setCompletedItems(completedList ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(
          t("js.error.tasksAccessDenied") !== "js.error.tasksAccessDenied"
            ? t("js.error.tasksAccessDenied")
            : "Access denied",
          "error"
        );
      }
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    loadTasks();
  }, [ready, loadTasks]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    const all = [...openItems, ...completedItems];
    if (all.some((item) => item.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("task", id));
    }
  }, [ready, loading, openItems, completedItems, searchParams, router]);

  const handleRowClick = (id: number) => {
    router.push(getEntityPath("task", id));
  };

  const handleTaskComplete = async (e: React.MouseEvent, taskId: number, complete: boolean) => {
    e.stopPropagation();
    try {
      await tasksApi.complete(taskId, complete);
      loadTasks();
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToSave"), "error");
    }
  };

  const handleOpenSort = (key: SortKey) => {
    if (openSortKey === key) {
      setOpenSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setOpenSortKey(key);
      setOpenSortDir("asc");
    }
  };

  const handleCompletedSort = (key: SortKey) => {
    if (completedSortKey === key) {
      setCompletedSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setCompletedSortKey(key);
      setCompletedSortDir("desc");
    }
  };

  const filteredOpen = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return openItems;
    return openItems.filter(
      (item) =>
        [notesToPlainText(item.title ?? ""), item.assignee, notesToPlainText(item.description ?? "")].filter(Boolean).join(" ").toLowerCase().includes(q)
    );
  }, [openItems, search]);

  const filteredCompleted = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return completedItems;
    return completedItems.filter(
      (item) =>
        [notesToPlainText(item.title ?? ""), item.assignee, notesToPlainText(item.description ?? "")].filter(Boolean).join(" ").toLowerCase().includes(q)
    );
  }, [completedItems, search]);

  const sortedOpen = useMemo(() => {
    const key = openSortKey ?? "due_at";
    const dir = openSortDir ?? "asc";
    return [...filteredOpen].sort((a, b) => {
      switch (key) {
        case "title":
          return compareString(a.title ?? "", b.title ?? "", dir);
        case "assignee":
          return compareString(a.assignee ?? "", b.assignee ?? "", dir);
        case "due_at":
          return compareDate(a.due_at ?? "", b.due_at ?? "", dir);
        default:
          return 0;
      }
    });
  }, [filteredOpen, openSortKey, openSortDir]);

  const sortedCompleted = useMemo(() => {
    const key = completedSortKey ?? "completed_at";
    const dir = completedSortDir ?? "desc";
    return [...filteredCompleted].sort((a, b) => {
      switch (key) {
        case "title":
          return compareString(a.title ?? "", b.title ?? "", dir);
        case "assignee":
          return compareString(a.assignee ?? "", b.assignee ?? "", dir);
        case "due_at":
          return compareDate(a.completed_at ?? a.due_at ?? "", b.completed_at ?? b.due_at ?? "", dir);
        default:
          return compareDate(a.completed_at ?? "", b.completed_at ?? "", dir);
      }
    });
  }, [filteredCompleted, completedSortKey, completedSortDir]);

  if (!ready) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <PageContent>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
            {t("js.sidebar.tasks") !== "js.sidebar.tasks" ? t("js.sidebar.tasks") : "Tasks"}
          </h1>
          <p className="mt-1 text-sm text-base-content/60">
            {t("js.tasks.subtitle") !== "js.tasks.subtitle" ? t("js.tasks.subtitle") : "Assign and track tasks"}
          </p>
        </div>
        <div className="flex gap-2 shrink-0">
          <ActionButton variant="outline" href={getEntityPath("group_task", "new", "edit")}>
            {t("js.tasks.addGroupTask") !== "js.tasks.addGroupTask" ? t("js.tasks.addGroupTask") : "Add group task"}
          </ActionButton>
          <ActionButton href={getEntityPath("task", "new", "edit")}>
            <Plus className="h-4 w-4" />
            {t("js.tasks.addTask") !== "js.tasks.addTask" ? t("js.tasks.addTask") : "Add Task"}
          </ActionButton>
        </div>
      </div>

      {error && (
        <div
          className="rounded-lg border px-4 py-3 text-sm"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {error}
        </div>
      )}

      <div className="flex items-center gap-3 rounded-lg px-3 py-2" style={{ background: "var(--muted)" }}>
        <input
          type="search"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
          className="input input-sm w-full bg-transparent"
          style={{ color: "var(--foreground)" }}
        />
      </div>

      {loading ? (
        <div className="flex justify-center py-12">
          <Spinner />
        </div>
      ) : (
        <div className="space-y-6">
          <TasksTable
            title={t("js.tasks.open") !== "js.tasks.open" ? t("js.tasks.open") : "Open"}
            items={sortedOpen}
            emptyLabel={t("js.tasks.noTasks") !== "js.tasks.noTasks" ? t("js.tasks.noTasks") : "No tasks"}
            onRowClick={handleRowClick}
            onComplete={handleTaskComplete}
            sortKey={openSortKey}
            sortDir={openSortDir}
            onSort={handleOpenSort}
            defaultSortKey="due_at"
            defaultSortDir="asc"
            t={t}
            lang={lang}
            emptyText={emptyText}
            showCheckbox
          />

          <TasksTable
            title={t("js.tasks.completed") !== "js.tasks.completed" ? t("js.tasks.completed") : "Completed"}
            items={sortedCompleted}
            emptyLabel={t("js.tasks.noTasks") !== "js.tasks.noTasks" ? t("js.tasks.noTasks") : "No tasks"}
            onRowClick={handleRowClick}
            onComplete={handleTaskComplete}
            sortKey={completedSortKey}
            sortDir={completedSortDir}
            onSort={handleCompletedSort}
            defaultSortKey="due_at"
            defaultSortDir="desc"
            t={t}
            lang={lang}
            emptyText={emptyText}
            showCheckbox={false}
            isCompleted
            showUncompleteCheckbox
          />
        </div>
      )}
    </PageContent>
  );
}

function SortableTh({
  label,
  columnId,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
}: {
  label: string;
  columnId: string;
  sortKey: SortKey;
  currentSortKey: SortKey | null;
  sortDir: SortDirection;
  onSort: (k: SortKey) => void;
}) {
  const active = currentSortKey === sortKey;
  const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
  return (
    <ResizableTh columnId={columnId}>
      <button
        type="button"
        onClick={() => onSort(sortKey)}
        className="inline-flex items-center gap-1.5 transition-opacity hover:opacity-80"
        style={{ color: "var(--foreground)" }}
      >
        {label}
        <Icon className="h-4 w-4 opacity-70" />
      </button>
    </ResizableTh>
  );
}

function TasksTable({
  title,
  items,
  emptyLabel,
  onRowClick,
  onComplete,
  sortKey,
  sortDir,
  onSort,
  defaultSortKey,
  defaultSortDir,
  t,
  lang,
  emptyText,
  showCheckbox,
  isCompleted = false,
  showUncompleteCheckbox = false,
}: {
  title: string;
  items: Task[];
  emptyLabel: string;
  onRowClick: (id: number) => void;
  onComplete: (e: React.MouseEvent, id: number, complete: boolean) => void;
  sortKey: SortKey | null;
  sortDir: SortDirection;
  onSort: (key: SortKey) => void;
  defaultSortKey: SortKey;
  defaultSortDir: SortDirection;
  t: (k: string) => string;
  lang: string;
  emptyText: string;
  showCheckbox: boolean;
  isCompleted?: boolean;
  showUncompleteCheckbox?: boolean;
}) {
  const entityColor = getColor("task");
  const pillStyle = getPillStyle(entityColor);
  const dotStyle = getDotStyle(entityColor);
  const Icon = getIcon("check-square");

  return (
    <div>
      <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
        {title}
      </h2>
      <div
        className="mt-3 overflow-hidden rounded-xl border"
        style={{
          borderColor: "var(--border)",
          background: "var(--card)",
          color: "var(--card-foreground)",
        }}
      >
        <ResponsiveTable<Task, SortKey>
          rows={items}
          sortOptions={[
            { key: "title", label: t("js.tasks.title") !== "js.tasks.title" ? t("js.tasks.title") : "Title" },
            { key: "assignee", label: t("js.tasks.assignee") !== "js.tasks.assignee" ? t("js.tasks.assignee") : "Assignee" },
            { key: "due_at", label: t("js.tasks.dueAt") !== "js.tasks.dueAt" ? t("js.tasks.dueAt") : "Due" },
          ]}
          getRowKey={(row) => row.id}
          renderMobileRow={(row) => (
            <EntityListRow
              icon={
                <span
                  className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                  style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}
                >
                  <Icon className="h-3.5 w-3.5" />
                </span>
              }
              primary={notesToPlainText(row.title ?? "") || emptyText}
              secondary={
                <span>
                  {row.assignee && `${row.assignee}`}
                  {(isCompleted ? row.completed_at ?? row.due_at : row.due_at) &&
                    ` · ${formatDateTimeShort(isCompleted ? (row.completed_at ?? row.due_at ?? "") : (row.due_at ?? ""), lang)}`}
                </span>
              }
              onClick={() => onRowClick(row.id)}
            />
          )}
          onRowClick={(row) => onRowClick(row.id)}
          emptyMessage={emptyLabel}
          sortKey={sortKey}
          sortDir={sortDir}
          onSort={onSort}
        >
          <ResizableTable
          className="w-full text-sm"
          columns={[
            ...(showCheckbox || showUncompleteCheckbox ? [{ id: "complete", width: 50, minWidth: 44 }] : []),
            { id: "title", width: 260, minWidth: 180 },
            { id: "assignee", width: 160, minWidth: 120 },
            { id: "due_at", width: 160, minWidth: 120 },
          ]}
        >
            <thead>
                <tr
                  className="border-b"
                  style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}
                >
                  {(showCheckbox || showUncompleteCheckbox) && (
                  <ResizableTh columnId="complete">
                    <span className="sr-only">{t("js.tasks.complete")}</span>
                  </ResizableTh>
                )}
                <SortableTh
                  columnId="title"
                  label={t("js.tasks.title") !== "js.tasks.title" ? t("js.tasks.title") : "Title"}
                  sortKey="title"
                  currentSortKey={sortKey}
                  sortDir={sortDir}
                  onSort={onSort}
                />
                <SortableTh
                  columnId="assignee"
                  label={t("js.tasks.assignee") !== "js.tasks.assignee" ? t("js.tasks.assignee") : "Assignee"}
                  sortKey="assignee"
                  currentSortKey={sortKey}
                  sortDir={sortDir}
                  onSort={onSort}
                />
                <SortableTh
                  columnId="due_at"
                  label={t("js.tasks.dueAt") !== "js.tasks.dueAt" ? t("js.tasks.dueAt") : "Due"}
                  sortKey="due_at"
                  currentSortKey={sortKey}
                  sortDir={sortDir}
                  onSort={onSort}
                />
              </tr>
            </thead>
            <tbody>
              {                items.length === 0 ? (
                <tr>
                  <td
                    colSpan={(showCheckbox || showUncompleteCheckbox) ? 4 : 3}
                    className="p-8 text-center"
                    style={{ color: "var(--muted-foreground)" }}
                  >
                    {emptyLabel}
                  </td>
                </tr>
              ) : (
                items.map((row) => (
                  <tr
                    key={row.id}
                    className="cursor-pointer border-b transition-colors hover:bg-[var(--muted)]/30"
                    style={{ borderColor: "var(--border)" }}
                    onClick={() => onRowClick(row.id)}
                  >
                    {(showCheckbox || showUncompleteCheckbox) && (
                      <td className="p-2" onClick={(e) => e.stopPropagation()}>
                        <input
                          type="checkbox"
                          className="checkbox checkbox-primary checkbox-sm"
                          checked={!!row.is_complete}
                          onChange={() => {}}
                          onClick={(e) => onComplete(e, row.id, !row.is_complete)}
                        />
                      </td>
                    )}
                    <td className="p-3 font-medium">{notesToPlainText(row.title ?? "") || emptyText}</td>
                    <td className="p-3">{row.assignee ?? emptyText}</td>
                    <td className="p-3">
                      {(isCompleted ? row.completed_at ?? row.due_at : row.due_at)
                        ? formatDateTimeShort(isCompleted ? (row.completed_at ?? row.due_at ?? "") : (row.due_at ?? ""), lang)
                        : emptyText}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </ResizableTable>
        </ResponsiveTable>
      </div>
    </div>
  );
}
