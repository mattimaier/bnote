/**
 * BNote Next Generation - FullCalendar View
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import dynamic from "next/dynamic";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import allLocales from "@fullcalendar/core/locales-all";
import { useI18n } from "@/contexts/I18nContext";
import { Avatar } from "@/components/Avatar";
import { getIcon } from "@/components/icons";
import { getPillStyle, getColorForBnoteType } from "@/lib/entity-config";
import type { CalendarEvent } from "@/lib/calendar-api";
import { notesToPlainText } from "@/lib/editorjs-notes";

const FullCalendar = dynamic(
  () => import("@fullcalendar/react").then((mod) => mod.default),
  { ssr: false }
);

const PLUGINS = [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin];

interface CalendarViewProps {
  events: CalendarEvent[];
  onEventClick: (event: CalendarEvent) => void;
  onSelectRange: (start: string, end: string, allDay: boolean) => void;
  canEdit: boolean;
}

export function CalendarView({
  events,
  onEventClick,
  onSelectRange,
  canEdit,
}: CalendarViewProps) {
  const { t, lang } = useI18n();
  const [isMobile, setIsMobile] = useState(false);
  const fcLocale = (lang ?? "en").split("-")[0]?.toLowerCase() || "en";

  useEffect(() => {
    const media = window.matchMedia("(max-width: 767px)");
    const update = () => setIsMobile(media.matches);
    update();
    media.addEventListener("change", update);
    return () => media.removeEventListener("change", update);
  }, []);

  const eventMap = useMemo(() => {
    const map = new Map<string, CalendarEvent>();
    for (const ev of events) map.set(ev.id, ev);
    return map;
  }, [events]);

  const fcEvents = useMemo(
    () =>
      events.map((ev) => {
        const bnoteType = ev.extendedProps?.bnoteType ?? "";
        const isBirthday = bnoteType === "contact";
        const color = getColorForBnoteType(bnoteType) ?? ev.extendedProps?.color ?? null;
        const pillStyle = getPillStyle(color);
        return {
          id: ev.id,
          title: notesToPlainText(ev.title ?? ""),
          start: ev.start,
          end: ev.end,
          allDay: isBirthday ? true : (ev.allDay ?? false),
          backgroundColor: pillStyle.backgroundColor,
          borderColor: pillStyle.borderColor,
          textColor: pillStyle.color,
          extendedProps: {
            ...(ev.extendedProps ?? {}),
            _bg: pillStyle.backgroundColor,
            _border: pillStyle.borderColor,
            _text: pillStyle.color,
          },
        };
      }),
    [events]
  );

  const handleEventClick = useCallback(
    (info: { event: { id: string; extendedProps: unknown }; jsEvent: { preventDefault: () => void } }) => {
      info.jsEvent.preventDefault();
      const ev = eventMap.get(info.event.id);
      if (ev) onEventClick(ev);
    },
    [eventMap, onEventClick]
  );

  const handleSelect = useCallback(
    (info: { startStr: string; endStr: string; allDay: boolean; view?: { calendar?: { unselect?: () => void } } }) => {
      onSelectRange(info.startStr, info.endStr, info.allDay);
      try {
        info.view?.calendar?.unselect?.();
      } catch {
        /* ignore */
      }
    },
    [onSelectRange]
  );

  const eventContent = useCallback(
    (arg: {
      event: {
        extendedProps?: {
          bnoteType?: string;
          avatarEmail?: string | null;
          icon?: string;
          _bg?: string;
          _border?: string;
          _text?: string;
        };
        title: string;
      };
      createElement: (type: string, props?: object, ...children: unknown[]) => React.ReactElement;
    }) => {
      const ext = arg.event.extendedProps ?? {};
      const isBirthday = ext.bnoteType === "contact";
      const bg = ext._bg ?? "var(--muted)";
      const border = ext._border ?? "var(--border)";
      const text = ext._text ?? "var(--muted-foreground)";
      const eventStyle = {
        backgroundColor: bg,
        borderColor: border,
        color: text,
      };

      if (isBirthday && !isMobile) {
        const CakeIcon = getIcon("cake");
        return (
          <div
            className="fc-event-main-frame flex items-start gap-1.5 overflow-visible rounded px-1 py-0.5 border"
            style={eventStyle}
          >
            <Avatar
              email={ext.avatarEmail}
              name={arg.event.title}
              size={24}
              variant="birthday"
              className="shrink-0"
            />
            <CakeIcon className="h-4 w-4 shrink-0 opacity-80 mt-0.5" aria-hidden />
            <span className="text-xs break-words leading-tight flex-1 min-w-0">{arg.event.title}</span>
          </div>
        );
      }

      const EventIcon = getIcon(ext.icon ?? "calendar");
      return (
        <div
          className={`fc-event-main-frame flex rounded border ${isMobile ? "items-center gap-1 px-1 py-0.5" : "items-start gap-1.5 px-1 py-0.5"} overflow-visible`}
          style={eventStyle}
        >
          <EventIcon className={`shrink-0 opacity-80 ${isMobile ? "h-3 w-3 mt-0" : "h-4 w-4 mt-0.5"}`} aria-hidden />
          <span className={`text-xs leading-tight flex-1 min-w-0 ${isMobile ? "truncate whitespace-nowrap" : "break-words"}`}>
            {arg.event.title}
          </span>
        </div>
      );
    },
    [isMobile]
  );

  const initialView = isMobile ? "listWeek" : "dayGridMonth";
  const headerToolbar = isMobile
    ? { left: "prev,next", center: "title", right: "today" }
    : { left: "prev,next today title", right: "dayGridMonth,timeGridWeek,timeGridDay" };

  return (
    <div className="calendar-shell rounded-box border border-base-300 bg-base-100 p-2 md:p-4">
      <FullCalendar
        key={isMobile ? "mobile" : "desktop"}
        plugins={PLUGINS}
        locale={fcLocale}
        locales={allLocales}
        initialView={initialView}
        headerToolbar={headerToolbar}
        buttonText={{
          month: t("js.calendar.month") !== "js.calendar.month" ? t("js.calendar.month") : "Month",
          week: t("js.calendar.week") !== "js.calendar.week" ? t("js.calendar.week") : "Week",
          day: t("js.calendar.day") !== "js.calendar.day" ? t("js.calendar.day") : "Day",
          list: t("js.calendar.week") !== "js.calendar.week" ? t("js.calendar.week") : "Week",
          today: t("js.calendar.today") !== "js.calendar.today" ? t("js.calendar.today") : "Today",
        }}
        events={fcEvents}
        editable={false}
        selectable={canEdit}
        selectMirror={canEdit}
        eventClick={handleEventClick}
        select={canEdit ? handleSelect : undefined}
        eventContent={eventContent}
        dayMaxEvents={isMobile ? 1 : 3}
        slotMinTime="06:00:00"
        slotMaxTime="24:00:00"
        height={isMobile ? "auto" : 900}
        eventTimeFormat={{
          hour: "2-digit",
          minute: "2-digit",
          hour12: false,
        }}
      />
    </div>
  );
}
