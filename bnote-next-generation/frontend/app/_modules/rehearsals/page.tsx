/**
 * BNote Next Generation - Rehearsals List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { rehearsalsApi, type RehearsalListItem } from "@/lib/rehearsals-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";
import { getStatusPillStyle } from "@/lib/entity-config";
import { NotesContent } from "@/components/NotesContent";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { formatEventDate, formatEventTime } from "@/lib/event-utils";
import { getEventTypeConfig } from "@/lib/entity-config";
import { ParticipationDiagram } from "@/components/ParticipationDiagram";
import { ArrowDown, ArrowUp, ArrowUpDown, Clock, MapPin } from "@/components/icons";

export default function RehearsalsPage() {
  const router = useRouter();
  const { t, ready, formatDateTime, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [items, setItems] = useState<RehearsalListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [upcomingSortKey, setUpcomingSortKey] = useState<
    "begin" | "status" | "location" | "notes" | null
  >("begin");
  const [upcomingSortDir, setUpcomingSortDir] = useState<SortDirection>("asc");
  const [pastSortState, setPastSortState] = useState<
    Record<number, { key: "begin" | "status" | "location" | "notes" | null; dir: SortDirection }>
  >({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const list = await rehearsalsApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load rehearsals");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!ready) return;
    load();
  }, [ready, load]);

  const handleUpcomingSort = (key: "begin" | "status" | "location" | "notes") => {
    if (upcomingSortKey === key) {
      setUpcomingSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setUpcomingSortKey(key);
      setUpcomingSortDir("asc");
    }
  };

  const handlePastSort = (
    year: number,
    key: "begin" | "status" | "location" | "notes"
  ) => {
    setPastSortState((prev) => {
      const current = prev[year] ?? { key: "begin", dir: "desc" as SortDirection };
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
      const haystack = [
        item.location_name,
        item.status,
        item.notes,
        item.begin,
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
      return haystack.includes(query);
    });
  }, [items, search]);

  const { upcomingItems, pastItems } = useMemo(() => {
    const now = Date.now();
    const upcoming: RehearsalListItem[] = [];
    const past: RehearsalListItem[] = [];
    filteredItems.forEach((item) => {
      const dateValue = item.end || item.begin;
      const timestamp = dateValue ? new Date(dateValue).getTime() : NaN;
      if (!Number.isNaN(timestamp) && timestamp < now) {
        past.push(item);
      } else {
        upcoming.push(item);
      }
    });
    return { upcomingItems: upcoming, pastItems: past };
  }, [filteredItems]);

  const pastByYear = useMemo(() => {
    const groups = new Map<number, RehearsalListItem[]>();
    pastItems.forEach((item) => {
      const dateValue = item.begin || item.end;
      const year = dateValue ? new Date(dateValue).getFullYear() : 0;
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
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-5xl space-y-4">
      <div>
        <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
          {t("js.sidebar.rehearsals") !== "js.sidebar.rehearsals"
            ? t("js.sidebar.rehearsals")
            : "Rehearsals"}
        </h1>
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

      <EventsTable
        title={t("js.rehearsals.upcoming") !== "js.rehearsals.upcoming"
          ? t("js.rehearsals.upcoming")
          : "Upcoming rehearsals"}
        items={upcomingItems}
        loading={loading}
        emptyLabel={t("js.rehearsals.noRehearsals") !== "js.rehearsals.noRehearsals"
          ? t("js.rehearsals.noRehearsals")
          : "No rehearsals"}
        formatDateTime={formatDateTime}
        onRowClick={(id) => router.push(getEntityPath("rehearsal", id))}
        emptyText={emptyText}
        sortKey={upcomingSortKey}
        sortDir={upcomingSortDir}
        onSort={handleUpcomingSort}
        defaultSortKey="begin"
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
            {t("js.rehearsals.noHistory") !== "js.rehearsals.noHistory"
              ? t("js.rehearsals.noHistory")
              : "No past rehearsals"}
          </div>
        ) : (
          <div className="mt-3 space-y-6">
            {pastByYear.years.map((year) => {
              const state = pastSortState[year] ?? { key: "begin" as const, dir: "desc" as SortDirection };
              return (
                <EventsTable
                  key={year}
                  title={`${year}`}
                  items={pastByYear.groups.get(year) ?? []}
                  loading={loading}
                  emptyLabel={t("js.rehearsals.noHistory") !== "js.rehearsals.noHistory"
                    ? t("js.rehearsals.noHistory")
                    : "No past rehearsals"}
                  formatDateTime={formatDateTime}
                  onRowClick={(id) => router.push(getEntityPath("rehearsal", id))}
                  emptyText={emptyText}
                  sortKey={state.key}
                  sortDir={state.dir}
                  onSort={(key) => handlePastSort(year, key)}
                  defaultSortKey="begin"
                  defaultSortDir="desc"
                  t={t}
                  lang={lang}
                />
              );
            })}
          </div>
        )}
      </div>
    </div>
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
  sortKey: "begin" | "status" | "location" | "notes";
  currentSortKey: "begin" | "status" | "location" | "notes" | null;
  sortDir: SortDirection;
  onSort: (key: "begin" | "status" | "location" | "notes") => void;
}) {
  const active = currentSortKey === sortKey;
  const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
  return (
    <ResizableTh columnId={columnId} className="text-left p-3 font-semibold">
      <button
        type="button"
        onClick={() => onSort(sortKey)}
        className="inline-flex items-center gap-1.5 hover:opacity-80 transition-opacity"
        style={{ color: "var(--foreground)" }}
      >
        {label}
        <Icon className="h-4 w-4 opacity-70" />
      </button>
    </ResizableTh>
  );
}

function EventsTable({
  title,
  items,
  loading,
  emptyLabel,
  formatDateTime,
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
  items: RehearsalListItem[];
  loading: boolean;
  emptyLabel: string;
  formatDateTime: (date: Date) => string;
  onRowClick: (id: number) => void;
  emptyText: string;
  sortKey: "begin" | "status" | "location" | "notes" | null;
  sortDir: SortDirection;
  onSort: (key: "begin" | "status" | "location" | "notes") => void;
  defaultSortKey: "begin" | "status" | "location" | "notes";
  defaultSortDir: SortDirection;
  t: (k: string) => string;
  lang: string;
}) {
  const statusLabelFor = (value?: string) => {
    if (!value) return emptyText;
    if (value === "confirmed") return t("js.event.status.confirmed");
    if (value === "cancelled") return t("js.event.status.cancelled");
    if (value === "hidden") return t("js.event.status.hidden");
    if (value === "planned") return t("js.event.status.planned");
    return value;
  };
  const sortedItems = useMemo(() => {
    const key = sortKey ?? defaultSortKey;
    const dir = sortDir ?? defaultSortDir;
    return [...items].sort((a, b) => {
      switch (key) {
        case "begin":
          return compareDate(a.begin, b.begin, dir);
        case "status":
          return compareString(a.status ?? "", b.status ?? "", dir);
        case "location":
          return compareString(a.location_name ?? "", b.location_name ?? "", dir);
        case "notes":
          return compareString(a.notes ?? "", b.notes ?? "", dir);
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
            <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
          </div>
        ) : (
          <ResponsiveTable<RehearsalListItem, "begin" | "status" | "location" | "notes">
            rows={sortedItems}
            getRowKey={(row) => row.id}
            renderMobileRow={(row) => {
              const typeConfig = getEventTypeConfig("rehearsal", t);
              const Icon = getIcon(typeConfig.icon);
              const tba = t("js.event.tba");
              const dateStr = formatEventDate(row.begin, lang, tba);
              const timeStr = formatEventTime(row.begin, lang, tba);
              const loc = row.location_name || emptyText;
              return (
                <EntityListRow
                  icon={
                    <span className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}>
                      <Icon className="h-3 w-3" />
                    </span>
                  }
                  primary={<span className="font-bold leading-tight" style={{ color: "var(--primary)" }}>{dateStr}</span>}
                  badge={row.status ? (
                    <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(row.status)}>
                      {statusLabelFor(row.status)}
                    </span>
                  ) : undefined}
                  secondary={
                    <>
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3 opacity-70" />
                        {timeStr}
                      </span>
                      {loc && loc !== emptyText && (
                        <span className="flex items-center gap-1">
                          <MapPin className="h-3 w-3 opacity-70" />
                          {loc}
                        </span>
                      )}
                    </>
                  }
                  onClick={() => onRowClick(row.id)}
                />
              );
            }}
            onRowClick={(row) => onRowClick(row.id)}
            emptyMessage={emptyLabel}
            sortOptions={[
              { key: "begin", label: t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin" },
              { key: "status", label: t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status" },
              { key: "location", label: t("js.event.location") !== "js.event.location" ? t("js.event.location") : "Location" },
              { key: "notes", label: t("js.common.notes") !== "js.common.notes" ? t("js.common.notes") : "Notes" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={onSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "begin", width: 180, minWidth: 150 },
                { id: "status", width: 140, minWidth: 120 },
                { id: "location", width: 220, minWidth: 160 },
                { id: "attendance", width: 220, minWidth: 180 },
                { id: "notes", width: 320, minWidth: 220 },
              ]}
            >
              <thead>
                <tr
                  className="border-b"
                  style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}
                >
                  <SortableTh
                    columnId="begin"
                    label={t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin"}
                    sortKey="begin"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                  <SortableTh
                    columnId="status"
                    label={t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status"}
                    sortKey="status"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                  <SortableTh
                    columnId="location"
                    label={t("js.event.location") !== "js.event.location" ? t("js.event.location") : "Location"}
                    sortKey="location"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                  <ResizableTh columnId="attendance">
                    {t("js.event.attendance") !== "js.event.attendance" ? t("js.event.attendance") : "Attendance"}
                  </ResizableTh>
                  <SortableTh
                    columnId="notes"
                    label={t("js.common.notes") !== "js.common.notes" ? t("js.common.notes") : "Notes"}
                    sortKey="notes"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
                </tr>
              </thead>
              <tbody>
                {sortedItems.length === 0 ? (
                  <tr>
                    <td
                      colSpan={5}
                      className="p-8 text-center"
                      style={{ color: "var(--muted-foreground)" }}
                    >
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
                      <td className="p-3">
                        {row.begin ? formatDateTime(new Date(row.begin)) : emptyText}
                      </td>
                      <td className="p-3">
                        {row.status ? (
                          <span
                            className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                            style={getStatusPillStyle(row.status)}
                          >
                            {statusLabelFor(row.status)}
                          </span>
                        ) : (
                          emptyText
                        )}
                      </td>
                      <td className="p-3">{row.location_name || emptyText}</td>
                      <td className="p-3">
                        {row.participationStats && (row.participationStats.total ?? 0) > 0 ? (
                          <div className="min-w-[160px]">
                            <ParticipationDiagram stats={row.participationStats} />
                          </div>
                        ) : (
                          emptyText
                        )}
                      </td>
                      <td className="p-3">
                        {row.notes ? (
                          <NotesContent value={row.notes} className="max-w-[200px] whitespace-pre-wrap break-words" />
                        ) : (
                          emptyText
                        )}
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
