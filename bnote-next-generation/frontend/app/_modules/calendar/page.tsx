/**
 * BNote Next Generation - Calendar Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { calendarApi, type CalendarEvent } from "@/lib/calendar-api";
import { CalendarView } from "@/components/Calendar/CalendarView";
import { CalendarEventModal } from "@/components/Calendar/CalendarEventModal";
import { PageContent } from "@/components/PageContent";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { ActionButton } from "@/components/ActionButton";
import { Plus } from "@/components/icons";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getColor, getBadgeClassForBnoteType } from "@/lib/entity-config";
import { getIcon } from "@/components/icons";
import { getEntityPath } from "@/lib/entities/paths";
import { formatDateTimeShort } from "@/lib/date-time";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";
import type { SortDirection } from "@/lib/table-sort";
import { compareString } from "@/lib/table-sort";
import { notesToPlainText } from "@/lib/editorjs-notes";

function formatEventDateRange(ev: CalendarEvent, lang: string): string {
  const start = ev.start ?? "";
  if (!start) return "";
  return formatDateTimeShort(start.slice(0, 19).replace("T", " "), lang) ?? "";
}

export default function CalendarPage() {
  const { t, ready, lang } = useI18n();
  const router = useRouter();
  const searchParams = useSearchParams();
  const { setEditingBar } = useEditingBar();

  useEffect(() => {
    setEditingBar(null);
  }, [setEditingBar]);
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [modalEvent, setModalEvent] = useState<CalendarEvent | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [selectMenuOpen, setSelectMenuOpen] = useState(false);
  const [selectRange, setSelectRange] = useState<{ start: string; end: string; allDay: boolean } | null>(null);
  const [listSortKey, setListSortKey] = useState<"title" | "start" | "type">("start");
  const [listSortDir, setListSortDir] = useState<SortDirection>("asc");

  const from = useMemo(() => {
    const d = new Date();
    d.setDate(1);
    d.setMonth(d.getMonth() - 6);
    return d.toISOString().slice(0, 10);
  }, []);
  const to = useMemo(() => {
    const d = new Date();
    d.setMonth(d.getMonth() + 2);
    return d.toISOString().slice(0, 10);
  }, []);

  const loadEvents = useCallback(async () => {
    setLoading(true);
    try {
      const list = await calendarApi.getEvents(from, to);
      setEvents(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [from, to, t]);

  useEffect(() => {
    if (!ready) return;
    loadEvents();
  }, [ready, loadEvents]);

  const reservationId = searchParams.get("reservation");
  const appointmentId = searchParams.get("appointment");
  useEffect(() => {
    if (!ready) return;
    const rid = reservationId ? parseInt(reservationId, 10) : 0;
    const aid = appointmentId ? parseInt(appointmentId, 10) : 0;
    if (rid && !Number.isNaN(rid)) {
      router.replace(getEntityPath("reservation", rid));
      return;
    }
    if (aid && !Number.isNaN(aid)) {
      router.replace(getEntityPath("appointment", aid));
      return;
    }
  }, [ready, reservationId, appointmentId, router]);

  const handleEventClick = useCallback(
    (ev: CalendarEvent) => {
      const link = ev.extendedProps?.link;
      if (link) {
        router.push(link);
      } else {
        setModalEvent(ev);
        setModalOpen(true);
      }
    },
    [router]
  );

  const handleSelectRange = useCallback((startStr: string, endStr: string, allDay: boolean) => {
    setSelectRange({ start: startStr, end: endStr, allDay });
    setSelectMenuOpen(true);
  }, []);

  const handleAddAppointment = useCallback(() => {
    setSelectMenuOpen(false);
    const params = new URLSearchParams({ type: "appointment", id: "new", edit: "1" });
    if (selectRange?.start) params.set("begin", selectRange.start);
    if (selectRange?.end) params.set("end", selectRange.end);
    router.push(`/entity?${params.toString()}`);
  }, [selectRange, router]);

  const handleAddReservation = useCallback(() => {
    setSelectMenuOpen(false);
    const params = new URLSearchParams({ type: "reservation", id: "new", edit: "1" });
    if (selectRange?.start) params.set("begin", selectRange.start);
    if (selectRange?.end) params.set("end", selectRange.end);
    router.push(`/entity?${params.toString()}`);
  }, [selectRange, router]);

  const handleListSort = useCallback((key: string) => {
    const k = key as "title" | "start" | "type";
    setListSortKey((prev) => {
      if (prev === k) {
        setListSortDir((d) => (d === "asc" ? "desc" : "asc"));
        return prev;
      }
      setListSortDir("asc");
      return k;
    });
  }, []);

  const sortedEvents = useMemo(() => {
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    const arr = events.filter((ev) => {
      if (ev.extendedProps?.bnoteType === "contact") {
        const start = (ev.start ?? "").trim();
        if (start.length < 10 || start.startsWith("0000-00-00")) return false;
      }
      const startTs = ev.start ? new Date(ev.start.replace(" ", "T").slice(0, 19)).getTime() : 0;
      const evDateStart = new Date(startTs);
      evDateStart.setHours(0, 0, 0, 0);
      return evDateStart.getTime() >= todayStart;
    });
    if (listSortKey === "start") {
      arr.sort((a, b) => {
        const sa = a.start ?? "";
        const sb = b.start ?? "";
        return listSortDir === "asc" ? sa.localeCompare(sb) : sb.localeCompare(sa);
      });
    } else if (listSortKey === "title") {
      arr.sort((a, b) => compareString(notesToPlainText(a.title ?? ""), notesToPlainText(b.title ?? ""), listSortDir));
    } else if (listSortKey === "type") {
      arr.sort((a, b) =>
        compareString(
          a.extendedProps?.bnoteType ?? "",
          b.extendedProps?.bnoteType ?? "",
          listSortDir
        )
      );
    }
    return arr;
  }, [events, listSortKey, listSortDir]);

  if (!ready) {
    return (
      <div className={PAGE_CONTENT_BASE_CLASS}>
        <div className="flex justify-center py-12">
          <Spinner />
        </div>
      </div>
    );
  }

  return (
    <PageContent>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-base-content">
            {t("js.sidebar.calendar") !== "js.sidebar.calendar"
              ? t("js.sidebar.calendar")
              : "Kalender"}
          </h1>
        </div>
        <div className="flex gap-2">
          <ActionButton onClick={() => router.push(getEntityPath("reservation", "new", "edit"))}>
            <Plus className="h-4 w-4" />
            {t("js.calendar.addReservation") !== "js.calendar.addReservation"
              ? t("js.calendar.addReservation")
              : "Add Reservation"}
          </ActionButton>
          <ActionButton onClick={() => router.push(getEntityPath("appointment", "new", "edit"))}>
            <Plus className="h-4 w-4" />
            {t("js.calendar.addAppointment") !== "js.calendar.addAppointment"
              ? t("js.calendar.addAppointment")
              : "Add Appointment"}
          </ActionButton>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error">
          {error}
        </div>
      )}

      {loading ? (
        <div className="flex justify-center py-12">
          <Spinner />
        </div>
      ) : (
        <>
          <CalendarView
            events={events}
            onEventClick={handleEventClick}
            onSelectRange={handleSelectRange}
          />

          {selectMenuOpen && selectRange && (
            <div
              className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
              onClick={() => setSelectMenuOpen(false)}
            >
              <div
                className="rounded-box bg-base-100 p-4 shadow-lg"
                onClick={(e) => e.stopPropagation()}
              >
                <p className="mb-3 text-sm font-medium">
                  {t("js.calendar.addEvent") !== "js.calendar.addEvent"
                    ? t("js.calendar.addEvent")
                    : "Add event"}
                </p>
                <div className="flex gap-2">
                  <button
                    type="button"
                    className="btn btn-primary btn-sm"
                    onClick={handleAddAppointment}
                  >
                    {t("js.calendar.addAppointment") !== "js.calendar.addAppointment"
                      ? t("js.calendar.addAppointment")
                      : "Add Appointment"}
                  </button>
                  <button
                    type="button"
                    className="btn btn-soft btn-sm"
                    onClick={handleAddReservation}
                  >
                    {t("js.calendar.addReservation") !== "js.calendar.addReservation"
                      ? t("js.calendar.addReservation")
                      : "Add Reservation"}
                  </button>
                  <button
                    type="button"
                    className="btn btn-soft btn-sm"
                    onClick={() => setSelectMenuOpen(false)}
                  >
                    {t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
                  </button>
                </div>
              </div>
            </div>
          )}

          <section className="mt-6">
            <h2 className="mb-3 text-lg font-semibold">
              {t("js.calendar.entriesList") !== "js.calendar.entriesList"
                ? t("js.calendar.entriesList")
                : "All entries"}
            </h2>
            <div className="overflow-hidden rounded-xl border border-base-300 bg-base-100">
              <ResponsiveTable<CalendarEvent, "title" | "start" | "type">
                rows={sortedEvents}
                getRowKey={(ev) => ev.id}
                getMobileTitle={(ev) => notesToPlainText(ev.title ?? "")}
                getMobileSubtitle={(ev) => formatEventDateRange(ev, lang)}
                onRowClick={(ev) => handleEventClick(ev)}
                emptyMessage={
                  t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "No entries"
                }
                sortKey={listSortKey}
                sortDir={listSortDir}
                onSort={handleListSort}
                sortOptions={[
                  { key: "start", label: t("js.calendar.date") !== "js.calendar.date" ? t("js.calendar.date") : "Date" },
                  { key: "title", label: t("js.calendar.title") !== "js.calendar.title" ? t("js.calendar.title") : "Title" },
                  { key: "type", label: t("js.calendar.type") !== "js.calendar.type" ? t("js.calendar.type") : "Type" },
                ]}
              >
                <ResizableTable
                  className="w-full text-sm"
                  columns={[
                    { id: "type", width: 120, minWidth: 80 },
                    { id: "title", width: 260, minWidth: 160 },
                    { id: "start", width: 200, minWidth: 140 },
                  ]}
                >
                  <thead>
                    <tr className="border-b border-base-300 bg-base-200/50">
                      <ResizableTh columnId="type">
                        {t("js.calendar.type") !== "js.calendar.type" ? t("js.calendar.type") : "Type"}
                      </ResizableTh>
                      <ResizableTh columnId="title">
                        {t("js.calendar.title") !== "js.calendar.title" ? t("js.calendar.title") : "Title"}
                      </ResizableTh>
                      <ResizableTh columnId="start">
                        {t("js.calendar.date") !== "js.calendar.date" ? t("js.calendar.date") : "Date"}
                      </ResizableTh>
                    </tr>
                  </thead>
                  <tbody>
                    {sortedEvents.length === 0 ? (
                      <tr>
                        <td colSpan={3} className="p-8 text-center text-base-content/60">
                          {t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "No entries"}
                        </td>
                      </tr>
                    ) : (
                      sortedEvents.map((ev) => {
                        const btype = ev.extendedProps?.bnoteType ?? "";
                        const iconName = ev.extendedProps?.icon ?? "calendar";
                        const Icon = getIcon(iconName);
                        const badgeClass = getBadgeClassForBnoteType(btype);
                        const labelKeys: Record<string, string> = {
                          rehearsal: "js.event.rehearsal",
                          concert: "js.event.performance",
                          vote: "js.sidebar.votes",
                          task: "js.sidebar.tasks",
                          contact: "js.calendar.birthday",
                          phase: "js.sidebar.rehearsals",
                        };
                        const labelKey = labelKeys[btype] ?? null;
                        const typeLabel = labelKey
                          ? (t(labelKey) !== labelKey ? t(labelKey) : btype)
                          : btype === "reservation"
                            ? "Reservation"
                            : btype === "appointment"
                              ? "Appointment"
                              : btype;
                        return (
                          <tr
                            key={ev.id}
                            className="cursor-pointer border-b border-base-300 transition-colors hover:bg-base-200/50"
                            onClick={() => handleEventClick(ev)}
                          >
                            <td className="p-3">
                              <span className={`inline-flex items-center gap-1.5 ${badgeClass}`}>
                                <Icon className="h-3.5 w-3.5 shrink-0" />
                                <span>{typeLabel}</span>
                              </span>
                            </td>
                            <td className="p-3 font-medium">{notesToPlainText(ev.title ?? "")}</td>
                            <td className="p-3 text-base-content/70">
                              {formatEventDateRange(ev, lang)}
                            </td>
                          </tr>
                        );
                      })
                    )}
                  </tbody>
                </ResizableTable>
              </ResponsiveTable>
            </div>
          </section>
        </>
      )}

      <CalendarEventModal
        event={modalEvent}
        open={modalOpen}
        onClose={() => {
          setModalOpen(false);
          setModalEvent(null);
        }}
        onDeleted={loadEvents}
        onEditReservation={(id) => {
          setModalOpen(false);
          router.push(getEntityPath("reservation", id, "edit"));
        }}
        onEditAppointment={(id) => {
          setModalOpen(false);
          router.push(getEntityPath("appointment", id, "edit"));
        }}
      />
    </PageContent>
  );
}
