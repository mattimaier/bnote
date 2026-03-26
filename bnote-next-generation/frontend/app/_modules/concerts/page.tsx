/**
 * BNote Next Generation - Concerts List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { concertsApi, type ConcertListItem } from "@/lib/concerts-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";
import { getStatusPillStyle } from "@/lib/entity-config";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { formatEventDate, formatEventTime } from "@/lib/event-utils";
import { getEventTypeConfig } from "@/lib/entity-config";
import { ParticipationDiagram } from "@/components/ParticipationDiagram";
import { ArrowDown, ArrowUp, ArrowUpDown, Clock, MapPin } from "@/components/icons";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { notesToPlainText } from "@/lib/editorjs-notes";
import { PageContent } from "@/components/PageContent";

export default function ConcertsPage() {
  const router = useRouter();
  const { t, ready, formatDateTime, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [items, setItems] = useState<ConcertListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [upcomingSortKey, setUpcomingSortKey] = useState<
    "title" | "begin" | "status" | "location" | null
  >("begin");
  const [upcomingSortDir, setUpcomingSortDir] = useState<SortDirection>("asc");
  const [pastSortState, setPastSortState] = useState<
    Record<number, { key: "title" | "begin" | "status" | "location" | null; dir: SortDirection }>
  >({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const list = await concertsApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!ready) return;
    load();
  }, [ready, load]);

  const handleUpcomingSort = (key: "title" | "begin" | "status" | "location") => {
    if (upcomingSortKey === key) {
      setUpcomingSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setUpcomingSortKey(key);
      setUpcomingSortDir("asc");
    }
  };

  const handlePastSort = (
    year: number,
    key: "title" | "begin" | "status" | "location"
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
        item.title,
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
    const upcoming: ConcertListItem[] = [];
    const past: ConcertListItem[] = [];
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
    const groups = new Map<number, ConcertListItem[]>();
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
        <Spinner />
      </div>
    );
  }

  return (
    <PageContent>
      <div>
        <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
          {t("js.sidebar.concerts") !== "js.sidebar.concerts"
            ? t("js.sidebar.concerts")
            : "Concerts"}
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
        title={t("js.concerts.upcoming") !== "js.concerts.upcoming" ? t("js.concerts.upcoming") : "Upcoming concerts"}
        items={upcomingItems}
        loading={loading}
        emptyLabel={t("js.concerts.noConcerts") !== "js.concerts.noConcerts" ? t("js.concerts.noConcerts") : "No concerts"}
        formatDateTime={formatDateTime}
        onRowClick={(id) => router.push(getEntityPath("concert", id))}
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
            {t("js.concerts.noHistory") !== "js.concerts.noHistory" ? t("js.concerts.noHistory") : "No past concerts"}
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
                  emptyLabel={t("js.concerts.noHistory") !== "js.concerts.noHistory" ? t("js.concerts.noHistory") : "No past concerts"}
                  formatDateTime={formatDateTime}
                  onRowClick={(id) => router.push(getEntityPath("concert", id))}
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
  sortKey: "title" | "begin" | "status" | "location";
  currentSortKey: "title" | "begin" | "status" | "location" | null;
  sortDir: SortDirection;
  onSort: (key: "title" | "begin" | "status" | "location") => void;
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
  items: ConcertListItem[];
  loading: boolean;
  emptyLabel: string;
  formatDateTime: (date: Date) => string;
  onRowClick: (id: number) => void;
  emptyText: string;
  sortKey: "title" | "begin" | "status" | "location" | null;
  sortDir: SortDirection;
  onSort: (key: "title" | "begin" | "status" | "location") => void;
  defaultSortKey: "title" | "begin" | "status" | "location";
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
        case "title":
          return compareString(a.title ?? "", b.title ?? "", dir);
        case "begin":
          return compareDate(a.begin, b.begin, dir);
        case "status":
          return compareString(a.status ?? "", b.status ?? "", dir);
        case "location":
          return compareString(a.location_name ?? "", b.location_name ?? "", dir);
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
          <ResponsiveTable<ConcertListItem, "title" | "begin" | "status" | "location">
            rows={sortedItems}
            getRowKey={(row) => row.id}
            renderMobileRow={(row) => {
              const typeConfig = getEventTypeConfig("performance", t);
              const Icon = getIcon(typeConfig.icon);
              const tba = t("js.event.tba");
              const dateStr = formatEventDate(row.begin, lang, tba);
              const timeStr = formatEventTime(row.begin, lang, tba);
              const loc = row.location_name || emptyText;
              const title = notesToPlainText(row.title ?? "").trim() || typeConfig.label;
              return (
                <EntityListRow
                  icon={
                    <span className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}>
                      <Icon className="h-3 w-3" />
                    </span>
                  }
                  primary={<span className="font-bold leading-tight" style={{ color: "var(--primary)" }}>{dateStr}</span>}
                  badge={
                    <>
                      <span className="text-sm min-w-0 break-words whitespace-normal leading-snug">{title}</span>
                      {row.status && (
                        <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(row.status)}>
                          {statusLabelFor(row.status)}
                        </span>
                      )}
                    </>
                  }
                  secondary={
                    <span className="flex w-full flex-col gap-0.5">
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3 opacity-70" />
                        {timeStr}
                      </span>
                      {loc && loc !== emptyText && (
                        <span className="flex items-center gap-1">
                          <MapPin className="h-3 w-3 opacity-70" />
                          <span className="min-w-0 break-words whitespace-normal leading-snug">{loc}</span>
                        </span>
                      )}
                    </span>
                  }
                  onClick={() => onRowClick(row.id)}
                />
              );
            }}
            onRowClick={(row) => onRowClick(row.id)}
            emptyMessage={emptyLabel}
            sortOptions={[
              { key: "title", label: t("js.event.title") !== "js.event.title" ? t("js.event.title") : "Title" },
              { key: "begin", label: t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin" },
              { key: "status", label: t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status" },
              { key: "location", label: t("js.event.location") !== "js.event.location" ? t("js.event.location") : "Location" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={onSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "title", width: 240, minWidth: 200 },
                { id: "begin", width: 180, minWidth: 150 },
                { id: "status", width: 140, minWidth: 120 },
                { id: "location", width: 220, minWidth: 160 },
                { id: "attendance", width: 220, minWidth: 180 },
              ]}
            >
              <thead>
                <tr
                  className="border-b"
                  style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}
                >
                  <SortableTh
                    columnId="title"
                    label={t("js.event.title") !== "js.event.title" ? t("js.event.title") : "Title"}
                    sortKey="title"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={onSort}
                  />
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
                      <td className="p-3 font-medium">{notesToPlainText(row.title ?? "") || emptyText}</td>
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
