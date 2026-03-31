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
import { calendarApi, type CalendarEvent, type CalendarSubscriptionLink } from "@/lib/calendar-api";
import { CalendarView } from "@/components/Calendar/CalendarView";
import { CalendarEventModal } from "@/components/Calendar/CalendarEventModal";
import { PageContent } from "@/components/PageContent";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { ActionButton } from "@/components/ActionButton";
import { AppPageHeader } from "@/components/AppPageHeader";
import { Plus } from "@/components/icons";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { EntityListRow } from "@/components/EntityListRow";
import { Clock } from "@/components/icons";
import { getBadgeClassForBnoteType, getColorForBnoteType, getDotStyle } from "@/lib/entity-config";
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

function getEventComparableDayTs(ev: CalendarEvent): number | null {
  const startRaw = (ev.start ?? "").trim();
  if (!startRaw) return null;
  const btype = ev.extendedProps?.bnoteType ?? "";
  const startDatePart = startRaw.slice(0, 10);

  if (btype === "contact") {
    const parts = startDatePart.split("-");
    if (parts.length === 3) {
      const month = Number(parts[1]);
      const day = Number(parts[2]);
      if (!Number.isNaN(month) && !Number.isNaN(day) && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
        const now = new Date();
        const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        let nextOccurrence = new Date(todayStart.getFullYear(), month - 1, day);
        if (nextOccurrence.getTime() < todayStart.getTime()) {
          nextOccurrence = new Date(todayStart.getFullYear() + 1, month - 1, day);
        }
        return nextOccurrence.getTime();
      }
    }
  }

  const parsed = new Date(startRaw.replace(" ", "T").slice(0, 19));
  if (Number.isNaN(parsed.getTime())) return null;
  parsed.setHours(0, 0, 0, 0);
  return parsed.getTime();
}

function getCalendarTypeLabel(ev: CalendarEvent, t: (key: string) => string): string {
  const btype = ev.extendedProps?.bnoteType ?? "";
  const labelKeys: Record<string, string> = {
    rehearsal: "js.event.rehearsal",
    concert: "js.event.performance",
    vote: "js.sidebar.votes",
    task: "js.sidebar.tasks",
    contact: "js.calendar.birthday",
    phase: "js.sidebar.rehearsals",
  };
  const labelKey = labelKeys[btype] ?? null;
  if (labelKey) {
    return t(labelKey) !== labelKey ? t(labelKey) : btype;
  }
  if (btype === "reservation") return "Reservation";
  if (btype === "appointment") return "Appointment";
  return btype;
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
  const [canEditCalendar, setCanEditCalendar] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [modalEvent, setModalEvent] = useState<CalendarEvent | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [selectMenuOpen, setSelectMenuOpen] = useState(false);
  const [selectRange, setSelectRange] = useState<{ start: string; end: string; allDay: boolean } | null>(null);
  const [listSortKey, setListSortKey] = useState<"title" | "start" | "type">("start");
  const [listSortDir, setListSortDir] = useState<SortDirection>("asc");
  const [subscription, setSubscription] = useState<CalendarSubscriptionLink | null>(null);

  const from = useMemo(() => {
    const d = new Date();
    d.setDate(1);
    d.setMonth(d.getMonth() - 6);
    return d.toISOString().slice(0, 10);
  }, []);
  const to = useMemo(() => {
    const d = new Date();
    d.setMonth(d.getMonth() + 12);
    return d.toISOString().slice(0, 10);
  }, []);

  const loadEvents = useCallback(async () => {
    setLoading(true);
    try {
      const [list, caps, sub] = await Promise.all([
        calendarApi.getEvents(from, to),
        calendarApi.getCapabilities(),
        calendarApi.getSubscriptionLink(),
      ]);
      setEvents(list ?? []);
      setCanEditCalendar(Boolean(caps?.canEdit));
      setSubscription(sub);
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
    if (!canEditCalendar) return;
    setSelectRange({ start: startStr, end: endStr, allDay });
    setSelectMenuOpen(true);
  }, [canEditCalendar]);

  const handleAddAppointment = useCallback(() => {
    if (!canEditCalendar) return;
    setSelectMenuOpen(false);
    const params = new URLSearchParams({ type: "appointment", id: "new", edit: "1" });
    if (selectRange?.start) params.set("begin", selectRange.start);
    if (selectRange?.end) params.set("end", selectRange.end);
    router.push(`/entity?${params.toString()}`);
  }, [canEditCalendar, selectRange, router]);

  const handleAddReservation = useCallback(() => {
    if (!canEditCalendar) return;
    setSelectMenuOpen(false);
    const params = new URLSearchParams({ type: "reservation", id: "new", edit: "1" });
    if (selectRange?.start) params.set("begin", selectRange.start);
    if (selectRange?.end) params.set("end", selectRange.end);
    router.push(`/entity?${params.toString()}`);
  }, [canEditCalendar, selectRange, router]);

  const noEditTooltip = "Keine Bearbeitungsrechte";
  const subscriptionMissing = t("js.calendar.subscriptionMissing") !== "js.calendar.subscriptionMissing"
    ? t("js.calendar.subscriptionMissing")
    : "Calendar subscription link unavailable.";

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
      const comparableTs = getEventComparableDayTs(ev);
      return comparableTs !== null && comparableTs >= todayStart;
    });
    if (listSortKey === "start") {
      arr.sort((a, b) => {
        const sa = getEventComparableDayTs(a) ?? Number.MAX_SAFE_INTEGER;
        const sb = getEventComparableDayTs(b) ?? Number.MAX_SAFE_INTEGER;
        return listSortDir === "asc" ? sa - sb : sb - sa;
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
      <AppPageHeader
        title={t("js.sidebar.calendar") !== "js.sidebar.calendar" ? t("js.sidebar.calendar") : "Kalender"}
        actions={(
          <>
            <ActionButton
              onClick={() => router.push(getEntityPath("reservation", "new", "edit"))}
              disabled={!canEditCalendar}
              title={!canEditCalendar ? noEditTooltip : undefined}
            >
              <Plus className="h-4 w-4" />
              {t("js.calendar.addReservation") !== "js.calendar.addReservation"
                ? t("js.calendar.addReservation")
                : "Add Reservation"}
            </ActionButton>
            <ActionButton
              onClick={() => router.push(getEntityPath("appointment", "new", "edit"))}
              disabled={!canEditCalendar}
              title={!canEditCalendar ? noEditTooltip : undefined}
            >
              <Plus className="h-4 w-4" />
              {t("js.calendar.addAppointment") !== "js.calendar.addAppointment"
                ? t("js.calendar.addAppointment")
                : "Add Appointment"}
            </ActionButton>
          </>
        )}
      />

      {error && (
        <div className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error">
          {error}
        </div>
      )}

      <section className="rounded-xl border border-base-300 bg-base-100 p-4">
        <h2 className="text-sm font-semibold">
          {t("js.calendar.subscriptionTitle") !== "js.calendar.subscriptionTitle"
            ? t("js.calendar.subscriptionTitle")
            : "Calendar Subscription"}
        </h2>
        <p className="mt-1 text-xs text-base-content/70">
          {t("js.calendar.subscriptionDescription") !== "js.calendar.subscriptionDescription"
            ? t("js.calendar.subscriptionDescription")
            : "Use this personal link to subscribe in your calendar app. Regenerating invalidates the old link."}
        </p>
        {subscription?.subscriptionUrl ? (
          <div className="mt-3 grid gap-2 md:grid-cols-2">
            <div className="rounded-lg border border-base-300 bg-base-200/40 p-2 text-xs font-mono break-all">
              {subscription.subscriptionUrl}
            </div>
            <div className="flex flex-wrap gap-2">
              <a className="btn btn-soft btn-sm" href={subscription.subscriptionHttpUrl} target="_blank" rel="noopener noreferrer">
                {t("js.calendar.downloadIcs") !== "js.calendar.downloadIcs"
                  ? t("js.calendar.downloadIcs")
                  : "Download ICS"}
              </a>
              <a className="btn btn-primary btn-sm" href={subscription.subscriptionUrl}>
                {t("js.calendar.subscribeWebcal") !== "js.calendar.subscribeWebcal"
                  ? t("js.calendar.subscribeWebcal")
                  : "Subscribe"}
              </a>
            </div>
          </div>
        ) : (
          <p className="mt-2 text-sm text-warning">{subscriptionMissing}</p>
        )}
      </section>

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
            canEdit={canEditCalendar}
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
              {t("js.dashboard.upcomingEvents") !== "js.dashboard.upcomingEvents"
                ? t("js.dashboard.upcomingEvents")
                : "Upcoming entries"}
            </h2>
            <div className="overflow-hidden rounded-xl border border-base-300 bg-base-100">
              <ResponsiveTable<CalendarEvent, "title" | "start" | "type">
                rows={sortedEvents}
                getRowKey={(ev) => ev.id}
                renderMobileRow={(ev) => {
                  const btype = ev.extendedProps?.bnoteType ?? "";
                  const iconName = ev.extendedProps?.icon ?? "calendar";
                  const Icon = getIcon(iconName);
                  const dotColor = getColorForBnoteType(btype);
                  const dotStyle = getDotStyle(dotColor);
                  const typeLabel = getCalendarTypeLabel(ev, t);
                  const dateText = formatEventDateRange(ev, lang);
                  return (
                    <EntityListRow
                      icon={
                        <span
                          className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                          style={dotStyle}
                        >
                          <Icon className="h-3.5 w-3.5" />
                        </span>
                      }
                      primary={notesToPlainText(ev.title ?? "")}
                      badge={<span className={getBadgeClassForBnoteType(btype)}>{typeLabel}</span>}
                      secondary={
                        <span className="flex items-center gap-1">
                          <Clock className="h-3 w-3 opacity-70" />
                          {dateText}
                        </span>
                      }
                      onClick={() => handleEventClick(ev)}
                    />
                  );
                }}
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
                        const typeLabel = getCalendarTypeLabel(ev, t);
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
        canEditCalendarEntries={canEditCalendar}
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
