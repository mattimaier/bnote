/**
 * BNote Next Generation - Votes List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import { useSearchParams, useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { votesApi, type Vote } from "@/lib/votes-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";
import { getStatusPillStyle } from "@/lib/entity-config";
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
import { PageContent } from "@/components/PageContent";

type SortKey = "name" | "end" | "status";

export default function VotesPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [items, setItems] = useState<Vote[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [upcomingSortKey, setUpcomingSortKey] = useState<SortKey | null>(null);
  const [upcomingSortDir, setUpcomingSortDir] = useState<SortDirection>("asc");
  const [pastSortState, setPastSortState] = useState<Record<number, { key: SortKey; dir: SortDirection }>>({});

  const loadVotes = useCallback(async () => {
    setLoading(true);
    try {
      const list = await votesApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(
          t("js.error.votesAccessDenied") !== "js.error.votesAccessDenied"
            ? t("js.error.votesAccessDenied")
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
    loadVotes();
  }, [ready, loadVotes]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (items.some((item) => item.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("vote", id));
    }
  }, [ready, loading, items, searchParams, router]);

  const handleRowClick = (id: number) => {
    router.push(getEntityPath("vote", id));
  };

  const handleUpcomingSort = (key: SortKey) => {
    if (upcomingSortKey === key) {
      setUpcomingSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setUpcomingSortKey(key);
      setUpcomingSortDir("asc");
    }
  };

  const handlePastSort = (year: number, key: SortKey) => {
    setPastSortState((prev) => {
      const current = prev[year] ?? { key: "end" as SortKey, dir: "desc" as SortDirection };
      if (current.key === key) {
        return { ...prev, [year]: { key, dir: current.dir === "asc" ? "desc" : "asc" } };
      }
      return { ...prev, [year]: { key, dir: "asc" } };
    });
  };

  const filteredItems = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) return items;
    return items.filter((item) => {
      const haystack = [item.name, item.end].filter(Boolean).join(" ").toLowerCase();
      return haystack.includes(query);
    });
  }, [items, search]);

  const { upcomingItems, pastItems } = useMemo(() => {
    const now = Date.now();
    const upcoming: Vote[] = [];
    const past: Vote[] = [];
    filteredItems.forEach((item) => {
      const timestamp = item.end ? new Date(item.end).getTime() : NaN;
      if (item.is_finished || (!Number.isNaN(timestamp) && timestamp < now)) {
        past.push(item);
      } else {
        upcoming.push(item);
      }
    });
    return { upcomingItems: upcoming, pastItems: past };
  }, [filteredItems]);

  const pastByYear = useMemo(() => {
    const groups = new Map<number, Vote[]>();
    pastItems.forEach((item) => {
      const year = item.end ? new Date(item.end).getFullYear() : 0;
      const key = Number.isNaN(year) ? 0 : year;
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key)?.push(item);
    });
    const years = Array.from(groups.keys())
      .filter((y) => y > 0)
      .sort((a, b) => b - a);
    return { years, groups };
  }, [pastItems]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <PageContent>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
            {t("js.votes.title") !== "js.votes.title"
              ? t("js.votes.title")
              : "Votes"}
          </h1>
          <p className="mt-1 text-sm text-base-content/60">
            {t("js.votes.subtitle") !== "js.votes.subtitle"
              ? t("js.votes.subtitle")
              : "Polls and surveys"}
          </p>
        </div>
        <ActionButton href={getEntityPath("vote", "new", "edit")}>
          <Plus className="h-4 w-4" />
          {t("js.votes.addVote") !== "js.votes.addVote"
            ? t("js.votes.addVote")
            : "Add Vote"}
        </ActionButton>
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
          onChange={(event) => setSearch(event.target.value)}
          placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
          className="w-full bg-transparent px-0 py-1 text-sm outline-none"
          style={{ color: "var(--foreground)" }}
        />
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-12">
          <Spinner />
        </div>
      ) : (
        <>
          <VotesTable
            title={t("js.votes.active") !== "js.votes.active" ? t("js.votes.active") : "Active"}
            items={upcomingItems}
            loading={loading}
            emptyLabel={t("js.votes.noVotes") !== "js.votes.noVotes" ? t("js.votes.noVotes") : "No votes found"}
            onRowClick={handleRowClick}
            emptyText={emptyText}
            sortKey={upcomingSortKey}
            sortDir={upcomingSortDir}
            onSort={handleUpcomingSort}
            defaultSortKey="end"
            defaultSortDir="asc"
            t={t}
            lang={lang}
          />

          <div>
            <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
              {t("js.common.history") !== "js.common.history" ? t("js.common.history") : "History"}
            </h2>
            {pastByYear.years.length === 0 ? (
              <div className="mt-3 rounded-xl border p-8 text-center text-sm" style={{ color: "var(--muted-foreground)", borderColor: "var(--border)", background: "var(--card)" }}>
                {t("js.votes.noVotes") !== "js.votes.noVotes" ? t("js.votes.noVotes") : "No votes found"}
              </div>
            ) : (
              <div className="mt-3 space-y-6">
                {pastByYear.years.map((year) => {
                  const state = pastSortState[year] ?? { key: "end" as SortKey, dir: "desc" as SortDirection };
                  return (
                    <VotesTable
                      key={year}
                      title={`${year}`}
                      items={pastByYear.groups.get(year) ?? []}
                      loading={loading}
                      emptyLabel={t("js.votes.noVotes") !== "js.votes.noVotes" ? t("js.votes.noVotes") : "No votes found"}
                      onRowClick={handleRowClick}
                      emptyText={emptyText}
                      sortKey={state.key}
                      sortDir={state.dir}
                      onSort={(key) => handlePastSort(year, key)}
                      defaultSortKey="end"
                      defaultSortDir="desc"
                      t={t}
                      lang={lang}
                    />
                  );
                })}
              </div>
            )}
          </div>
        </>
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
  const Icon = active
    ? sortDir === "asc"
      ? ArrowUp
      : ArrowDown
    : ArrowUpDown;
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

function VotesTable({
  title,
  items,
  loading,
  emptyLabel,
  onRowClick,
  emptyText,
  sortKey,
  sortDir,
  onSort,
  defaultSortKey,
  defaultSortDir,
  t,
  lang,
}: {
  title: string;
  items: Vote[];
  loading: boolean;
  emptyLabel: string;
  onRowClick: (id: number) => void;
  emptyText: string;
  sortKey: SortKey | null;
  sortDir: SortDirection;
  onSort: (key: SortKey) => void;
  defaultSortKey: SortKey;
  defaultSortDir: SortDirection;
  t: (k: string) => string;
  lang: string;
}) {
  const sortedItems = useMemo(() => {
    const key = sortKey ?? defaultSortKey;
    const dir = sortDir ?? defaultSortDir;
    return [...items].sort((a, b) => {
      switch (key) {
        case "name":
          return compareString(a.name ?? "", b.name ?? "", dir);
        case "end":
          return compareDate(a.end ?? "", b.end ?? "", dir);
        case "status": {
          const aStatus = a.is_finished ? "finished" : "active";
          const bStatus = b.is_finished ? "finished" : "active";
          return compareString(aStatus, bStatus, dir);
        }
        default:
          return 0;
      }
    });
  }, [items, sortKey, sortDir, defaultSortKey, defaultSortDir]);

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
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Spinner />
          </div>
        ) : (
          <ResponsiveTable<Vote, SortKey>
            rows={sortedItems}
            getRowKey={(row) => row.id}
            renderMobileRow={(row) => {
              const entityColor = getColor("vote");
              const pillStyle = getPillStyle(entityColor);
              const dotStyle = getDotStyle(entityColor);
              const Icon = getIcon("vote");
              const endStr = row.end ? formatDateTimeShort(row.end, lang) : emptyText;
              const statusLabel = row.is_finished
                ? (t("js.votes.finished") !== "js.votes.finished" ? t("js.votes.finished") : "Finished")
                : (t("js.votes.active") !== "js.votes.active" ? t("js.votes.active") : "Active");
              return (
                <EntityListRow
                  icon={<span className="rounded-full flex items-center justify-center w-6 h-6 text-white" style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}><Icon className="h-3.5 w-3.5" /></span>}
                  primary={row.name ?? emptyText}
                  badge={
                    <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(row.is_finished ? "inactive" : "active")}>{statusLabel}</span>
                  }
                  secondary={endStr ? <span>{endStr}</span> : undefined}
                  onClick={() => onRowClick(row.id)}
                />
              );
            }}
            onRowClick={(row) => onRowClick(row.id)}
            emptyMessage={emptyLabel}
            sortOptions={[
              { key: "name", label: t("js.votes.name") !== "js.votes.name" ? t("js.votes.name") : "Name" },
              { key: "end", label: t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "End" },
              { key: "status", label: t("js.votes.active") !== "js.votes.active" ? t("js.votes.active") : "Status" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={onSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "name", width: 260, minWidth: 200 },
                { id: "end", width: 160, minWidth: 120 },
                { id: "status", width: 140, minWidth: 120 },
              ]}
            >
              <thead>
                <tr
                  className="border-b"
                  style={{
                    borderColor: "var(--border)",
                    background: "var(--muted)/30",
                  }}
                >
                  <SortableTh
                    columnId="name"
                    label={t("js.votes.name") !== "js.votes.name" ? t("js.votes.name") : "Name"}
                    sortKey="name"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                  <SortableTh
                    columnId="end"
                    label={t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "End"}
                    sortKey="end"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                  <SortableTh
                    columnId="status"
                    label={t("js.votes.active") !== "js.votes.active" ? t("js.votes.active") : "Status"}
                    sortKey="status"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                </tr>
              </thead>
              <tbody>
                {sortedItems.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="p-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                      {emptyLabel}
                    </td>
                  </tr>
                ) : (
                  sortedItems.map((row) => (
                    <tr
                      key={row.id}
                      className="cursor-pointer border-b transition-colors hover:bg-[var(--muted)]/30"
                      style={{ borderColor: "var(--border)" }}
                      onClick={() => onRowClick(row.id)}
                    >
                      <td className="p-3 font-medium">{row.name ?? emptyText}</td>
                      <td className="p-3">{row.end ? formatDateTimeShort(row.end, lang) : emptyText}</td>
                      <td className="p-3">
                        <span
                          className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                          style={getStatusPillStyle(row.is_finished ? "inactive" : "active")}
                        >
                          {row.is_finished
                            ? t("js.votes.finished") !== "js.votes.finished"
                              ? t("js.votes.finished")
                              : "Finished"
                            : t("js.votes.active") !== "js.votes.active"
                              ? t("js.votes.active")
                              : "Active"}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </ResizableTable>
          </ResponsiveTable>
        )}
      </div>
    </div>
  );
}
