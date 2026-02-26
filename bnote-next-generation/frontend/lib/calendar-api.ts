/**
 * BNote Next Generation - Calendar API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface CalendarEventExtendedProps {
  bnoteType: string;
  link: string;
  color: string;
  icon: string;
  details: Record<string, string>;
  avatarEmail?: string | null;
}

export interface CalendarEvent {
  id: string;
  title: string;
  start: string;
  end: string;
  allDay?: boolean;
  extendedProps?: CalendarEventExtendedProps;
}

export const calendarApi = {
  getEvents: (from: string, to: string) =>
    api.get<CalendarEvent[]>("calendar", "getEvents", { from, to }),
};
