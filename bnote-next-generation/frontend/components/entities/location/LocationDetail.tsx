/**
 * BNote Next Generation - Location detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useMemo, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import {
  locationsApi,
  type LocationDetail as LocationDetailType,
  type LocationEventItem,
} from "@/lib/locations-api";
import { getEntityPath } from "@/lib/entities/paths";
import { getStatusPillStyle, getEventTypeConfig } from "@/lib/entity-config";
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";
import { AddressLink } from "@/components/AddressLink";
import { MarkdownText } from "@/components/MarkdownText";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { formatEventDate, formatEventTime } from "@/lib/event-utils";
import { ArrowDown, ArrowUp, ArrowUpDown, Clock } from "lucide-react";

export function LocationDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, formatDateTime, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const statusLabelFor = (value?: string) => {
    if (!value) return emptyText;
    if (value === "confirmed") return t("js.event.status.confirmed");
    if (value === "cancelled") return t("js.event.status.cancelled");
    if (value === "hidden") return t("js.event.status.hidden");
    if (value === "planned") return t("js.event.status.planned");
    return value;
  };
  const [location, setLocation] = useState<LocationDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [events, setEvents] = useState<LocationEventItem[]>([]);
  const [eventsLoading, setEventsLoading] = useState(false);
  const [sortStateByYear, setSortStateByYear] = useState<
    Record<number, { key: "title" | "begin" | "status"; dir: SortDirection }>
  >({});

  const eventsByYear = useMemo(() => {
    const groups = new Map<number, LocationEventItem[]>();
    events.forEach((item) => {
      const year = item.begin ? new Date(item.begin).getFullYear() : 0;
      const key = Number.isNaN(year) ? 0 : year;
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key)!.push(item);
    });
    const years = Array.from(groups.keys()).filter((y) => y > 0).sort((a, b) => b - a);
    return { years, groups };
  }, [events]);

  const handleSort = (year: number, key: "title" | "begin" | "status") => {
    setSortStateByYear((prev) => {
      const current = prev[year] ?? { key: "begin", dir: "desc" as SortDirection };
      if (current.key === key) {
        return { ...prev, [year]: { key, dir: current.dir === "asc" ? "desc" : "asc" } };
      }
      return { ...prev, [year]: { key, dir: key === "begin" ? "desc" : "asc" } };
    });
  };

  useEffect(() => {
    if (!id || id === "new" || !ready) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    locationsApi
      .get(numId)
      .then(setLocation)
      .catch((err) => setError(err instanceof Error ? err.message : "Failed to load"))
      .finally(() => setLoading(false));
  }, [id, ready]);

  useEffect(() => {
    if (!location?.id || !ready) return;
    setEventsLoading(true);
    locationsApi
      .getEvents(location.id)
      .then(setEvents)
      .catch(() => setEvents([]))
      .finally(() => setEventsLoading(false));
  }, [location?.id, ready]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (id === "new") {
    router.replace(getEntityPath("location", "new", "edit"));
    return null;
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (error || !location) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-[var(--destructive)]">{error || "Location not found."}</p>
      </div>
    );
  }

  const hasAddress =
    (location.street ?? "").trim() ||
    (location.city ?? "").trim() ||
    (location.zip ?? "").trim() ||
    (location.country ?? "").trim();
  const notes = String(location.notes ?? "").trim();
  const emptyLabel = emptyText;
  const addressValue = hasAddress
    ? {
        street: location.street ?? "",
        city: location.city ?? "",
        zip: location.zip ?? "",
        state: location.state ?? "",
        country: location.country ?? "",
      }
    : null;

  return (
    <div className="mx-auto max-w-2xl space-y-4 p-4 md:space-y-6 md:p-6">
      <DetailPageHeader
        title={location.name || emptyText}
        right={<DetailEditButton onClick={() => router.push(getEntityPath("location", location.id, "edit"))} />}
      />

      <DetailCard className="space-y-4">
        <div>
          <h2 className="text-sm font-semibold" style={{ color: "var(--muted-foreground)" }}>
            {t("js.locations.notes") !== "js.locations.notes" ? t("js.locations.notes") : "Notes"}
          </h2>
          <div className="mt-1 prose prose-sm max-w-none dark:prose-invert">
            {notes.length > 0 ? <MarkdownText value={notes} /> : <p>{emptyLabel}</p>}
          </div>
        </div>

        {addressValue && (
          <div>
            <h2 className="text-sm font-semibold" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.location") !== "js.event.detail.location"
                ? t("js.event.detail.location")
                : "Address"}
            </h2>
            <div className="mt-1">
              <AddressLink value={addressValue} t={t} renderRawIfNoAddress />
            </div>
          </div>
        )}
      </DetailCard>

      {events.length > 0 && (
        <div>
          <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
            {t("js.locations.eventsAtLocation")}
          </h2>
          {eventsLoading ? (
            <div className="mt-3 flex items-center justify-center rounded-none border-0 py-12 md:rounded-xl md:border md:py-12 bg-[var(--background)] md:bg-[var(--card)]" style={{ borderColor: "var(--border)" }}>
              <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
            </div>
          ) : (
            <div className="mt-3 space-y-6">
              {eventsByYear.years.map((year) => (
                <LocationEventsTable
                  key={year}
                  year={year}
                  items={eventsByYear.groups.get(year) ?? []}
                  sortState={sortStateByYear[year] ?? { key: "begin", dir: "desc" }}
                  onSort={(key) => handleSort(year, key)}
                  formatDateTime={formatDateTime}
                  statusLabelFor={statusLabelFor}
                  emptyText={emptyText}
                  t={t}
                  lang={lang}
                  onRowClick={(type, id) => router.push(getEntityPath(type, id))}
                />
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

type EventSortKey = "title" | "begin" | "status";

function SortableTh({
  label,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
  columnId,
}: {
  label: string;
  columnId: string;
  sortKey: EventSortKey;
  currentSortKey: EventSortKey | null;
  sortDir: SortDirection;
  onSort: (key: EventSortKey) => void;
}) {
  const active = currentSortKey === sortKey;
  const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
  return (
    <ResizableTh columnId={columnId} className="p-3 text-left font-semibold">
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

function getEventDisplayTitle(row: LocationEventItem, t: (k: string) => string): string {
  if (row.type === "concert" && (row.title ?? "").trim()) return row.title!.trim();
  return row.type === "concert"
    ? t("js.event.performance")
    : t("js.event.rehearsal");
}

function LocationEventsTable({
  year,
  items,
  sortState,
  onSort,
  formatDateTime,
  statusLabelFor,
  emptyText,
  t,
  lang,
  onRowClick,
}: {
  year: number;
  items: LocationEventItem[];
  sortState: { key: EventSortKey; dir: SortDirection };
  onSort: (key: EventSortKey) => void;
  formatDateTime: (date: Date) => string;
  statusLabelFor: (value?: string) => string;
  emptyText: string;
  t: (k: string) => string;
  lang: string;
  onRowClick: (type: "rehearsal" | "concert", id: number) => void;
}) {
  const sortedItems = useMemo(() => {
    const { key, dir } = sortState;
    return [...items].sort((a, b) => {
      switch (key) {
        case "title":
          return compareString(
            getEventDisplayTitle(a, t),
            getEventDisplayTitle(b, t),
            dir
          );
        case "begin":
          return compareDate(a.begin, b.begin, dir);
        case "status":
          return compareString(a.status ?? "", b.status ?? "", dir);
        default:
          return 0;
      }
    });
  }, [items, sortState, t]);

  const titleLabel = t("js.event.title") !== "js.event.title" ? t("js.event.title") : "Title";
  const beginLabel = t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin";
  const statusLabel = t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status";

  return (
    <div>
      <h3 className="text-base font-semibold" style={{ color: "var(--foreground)" }}>
        {year}
      </h3>
      <div
        className="mt-2 overflow-hidden rounded-none border-0 bg-[var(--background)] md:rounded-xl md:border md:bg-[var(--card)]"
        style={{
          borderColor: "var(--border)",
          color: "var(--card-foreground)",
        }}
      >
        <ResponsiveTable<LocationEventItem, EventSortKey>
          rows={sortedItems}
          getRowKey={(row) => `${row.type}-${row.id}`}
          renderMobileRow={(row) => {
            const eventType = row.type === "concert" ? "performance" : "rehearsal";
            const typeConfig = getEventTypeConfig(eventType, t);
            const Icon = getIcon(typeConfig.icon);
            const tba = t("js.event.tba");
            const dateStr = formatEventDate(row.begin, lang, tba);
            const timeStr = formatEventTime(row.begin, lang, tba);
            const hasCustomTitle = row.type === "concert" && (row.title ?? "").trim();
            const primaryText = hasCustomTitle ? (row.title ?? "").trim() : dateStr;
            return (
              <EntityListRow
                icon={
                  <span className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}>
                    <Icon className="h-3 w-3" />
                  </span>
                }
                primary={hasCustomTitle ? primaryText : <span className="font-bold leading-tight" style={{ color: "var(--primary)" }}>{primaryText}</span>}
                badge={
                  <>
                    {hasCustomTitle && <span className="text-sm">{dateStr}</span>}
                    {row.status && (
                      <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(row.status)}>
                        {statusLabelFor(row.status)}
                      </span>
                    )}
                  </>
                }
                secondary={
                  hasCustomTitle ? (
                    <span className="flex items-center gap-1">
                      <Clock className="h-3 w-3 opacity-70" />
                      {timeStr}
                    </span>
                  ) : undefined
                }
                onClick={() => onRowClick(row.type, row.id)}
              />
            );
          }}
          onRowClick={(row) => onRowClick(row.type, row.id)}
          emptyMessage={t("js.locations.noEventsAtLocation")}
          sortOptions={[
            { key: "title", label: titleLabel },
            { key: "begin", label: beginLabel },
            { key: "status", label: statusLabel },
          ]}
          sortKey={sortState.key}
          sortDir={sortState.dir}
          onSort={onSort}
        >
          <ResizableTable
            className="w-full text-sm"
            columns={[
              { id: "title", width: 280, minWidth: 200 },
              { id: "begin", width: 180, minWidth: 150 },
              { id: "status", width: 140, minWidth: 120 },
            ]}
          >
            <thead>
              <tr
                className="border-b"
                style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}
              >
                <SortableTh
                  columnId="title"
                  label={titleLabel}
                  sortKey="title"
                  currentSortKey={sortState.key}
                  sortDir={sortState.dir}
                  onSort={onSort}
                />
                <SortableTh
                  columnId="begin"
                  label={beginLabel}
                  sortKey="begin"
                  currentSortKey={sortState.key}
                  sortDir={sortState.dir}
                  onSort={onSort}
                />
                <SortableTh
                  columnId="status"
                  label={statusLabel}
                  sortKey="status"
                  currentSortKey={sortState.key}
                  sortDir={sortState.dir}
                  onSort={onSort}
                />
              </tr>
            </thead>
            <tbody>
              {sortedItems.length === 0 ? (
                <tr>
                  <td
                    colSpan={3}
                    className="p-8 text-center"
                    style={{ color: "var(--muted-foreground)" }}
                  >
                    {t("js.locations.noEventsAtLocation")}
                  </td>
                </tr>
              ) : (
                sortedItems.map((row) => {
                  const eventType = row.type === "concert" ? "performance" : "rehearsal";
                  const typeConfig = getEventTypeConfig(eventType, t);
                  const Icon = getIcon(typeConfig.icon);
                  const displayTitle = getEventDisplayTitle(row, t);
                  return (
                    <tr
                      key={`${row.type}-${row.id}`}
                      className="cursor-pointer border-b transition-colors hover:bg-[var(--muted)]/30"
                      style={{ borderColor: "var(--border)" }}
                      onClick={() => onRowClick(row.type, row.id)}
                    >
                      <td className="p-3">
                        <div className="flex items-center gap-2">
                          <div
                            className={`h-7 w-7 shrink-0 rounded-full flex items-center justify-center text-white ring-2 ring-[var(--background)] shadow-sm ${typeConfig.dotClass}`}
                          >
                            <Icon className="h-3.5 w-3.5" />
                          </div>
                          <span className="truncate">{displayTitle || emptyText}</span>
                        </div>
                      </td>
                      <td className="p-3">
                        {row.begin ? formatDateTime(new Date(row.begin)) : emptyText}
                      </td>
                      <td className="p-3">
                        {row.status ? (
                          <span
                            className="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium"
                            style={getStatusPillStyle(row.status)}
                          >
                            {statusLabelFor(row.status)}
                          </span>
                        ) : (
                          emptyText
                        )}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </ResizableTable>
        </ResponsiveTable>
      </div>
    </div>
  );
}
