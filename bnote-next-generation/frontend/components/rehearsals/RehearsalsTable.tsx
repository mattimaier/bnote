"use client";

import { useMemo } from "react";
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";
import { getEscalationWarningUiConfig, getStatusPillStyle } from "@/lib/entity-config";
import { NotesContent } from "@/components/NotesContent";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { formatEventDate, formatEventTime } from "@/lib/event-utils";
import { getEventTypeConfig } from "@/lib/entity-config";
import { ParticipationDiagram } from "@/components/ParticipationDiagram";
import { ArrowDown, ArrowUp, ArrowUpDown, Clock, MapPin } from "@/components/icons";
import { Spinner } from "@/components/Spinner";
import type { RehearsalListItem } from "@/lib/rehearsals-api";

type RehearsalSortKey = "begin" | "status" | "location" | "notes";

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
  sortKey: RehearsalSortKey;
  currentSortKey: RehearsalSortKey | null;
  sortDir: SortDirection;
  onSort: (key: RehearsalSortKey) => void;
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

export function RehearsalsTable({
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
  sortKey: RehearsalSortKey | null;
  sortDir: SortDirection;
  onSort: (key: RehearsalSortKey) => void;
  defaultSortKey: RehearsalSortKey;
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
            <Spinner />
          </div>
        ) : (
          <ResponsiveTable<RehearsalListItem, RehearsalSortKey>
            rows={sortedItems}
            getRowKey={(row) => row.id}
            renderMobileRow={(row) => {
              const typeConfig = getEventTypeConfig("rehearsal", t);
              const Icon = getIcon(typeConfig.icon);
              const tba = t("js.event.tba");
              const dateStr = formatEventDate(row.begin, lang, tba);
              const timeStr = formatEventTime(row.begin, lang, tba);
              const loc = row.location_name || emptyText;
              const warning = row.escalationWarning;
              const warningUi = warning ? getEscalationWarningUiConfig(warning.severity) : null;
              const WarningIcon = warningUi ? getIcon(warningUi.iconName) : null;
              return (
                <EntityListRow
                  icon={
                    <span
                      className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}
                    >
                      <Icon className="h-3 w-3" />
                    </span>
                  }
                  primary={
                    <span className="font-bold leading-tight" style={{ color: "var(--primary)" }}>
                      {dateStr}
                    </span>
                  }
                  badge={
                    <>
                      {WarningIcon && (
                        <span
                          className="inline-flex items-center justify-center rounded-full border px-2 py-0.5"
                          style={warningUi?.badgeStyle}
                          title={warning?.reasons?.join(" • ")}
                        >
                          <WarningIcon className={`h-3.5 w-3.5 ${warningUi?.iconClassName ?? ""}`} />
                        </span>
                      )}
                      {row.status ? (
                        <span
                          className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                          style={getStatusPillStyle(row.status)}
                        >
                          {statusLabelFor(row.status)}
                        </span>
                      ) : undefined}
                    </>
                  }
                  secondary={
                    <span className="flex w-full flex-col gap-0.5">
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3 opacity-70" />
                        {timeStr}
                      </span>
                      {loc && loc !== emptyText && (
                        <span className="flex items-start gap-1">
                          <MapPin className="mt-0.5 h-3 w-3 opacity-70 shrink-0" />
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
              { key: "begin", label: t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin" },
              { key: "status", label: t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status" },
              {
                key: "location",
                label: t("js.event.location") !== "js.event.location" ? t("js.event.location") : "Location",
              },
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
                { id: "warning", width: 90, minWidth: 80 },
                { id: "location", width: 220, minWidth: 160 },
                { id: "attendance", width: 220, minWidth: 180 },
                { id: "notes", width: 320, minWidth: 220 },
              ]}
            >
              <thead>
                <tr className="border-b border-base-300 bg-base-200/60">
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
                  <ResizableTh columnId="warning">
                    {t("mail.escalation.alertBadge") !== "mail.escalation.alertBadge"
                      ? t("mail.escalation.alertBadge")
                      : "Alert"}
                  </ResizableTh>
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
                    <td colSpan={6} className="p-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                      {emptyLabel}
                    </td>
                  </tr>
                ) : (
                  sortedItems.map((row) => (
                    <tr
                      key={row.id}
                      className="cursor-pointer border-b border-base-300 transition-colors hover:bg-base-200/70"
                      onClick={() => onRowClick(row.id)}
                    >
                      <td className="p-3">{row.begin ? formatDateTime(new Date(row.begin)) : emptyText}</td>
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
                      <td className="p-3">
                        {row.escalationWarning
                          ? (() => {
                              const warningUi = getEscalationWarningUiConfig(row.escalationWarning.severity);
                              const WarningIcon = getIcon(warningUi.iconName);
                              return (
                                <span
                                  className="inline-flex items-center justify-center rounded-full border px-2 py-0.5"
                                  style={warningUi.badgeStyle}
                                  title={row.escalationWarning.reasons?.join(" • ")}
                                >
                                  <WarningIcon className={`h-5 w-5 ${warningUi.iconClassName}`} />
                                </span>
                              );
                            })()
                          : emptyText}
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
                          <NotesContent
                            value={row.notes}
                            className="max-w-[200px] whitespace-pre-wrap break-words"
                            maxLines={3}
                          />
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
