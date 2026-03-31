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

export interface CalendarCapabilities {
  canView: boolean;
  canEdit: boolean;
}

export interface CalendarSubscriptionLink {
  token: string;
  downloadUrl: string;
  subscriptionUrl: string; // webcal://
  subscriptionHttpUrl: string; // http(s)://
}

export const calendarApi = {
  getEvents: (from: string, to: string) =>
    api.get<CalendarEvent[]>("calendar", "getEvents", { from, to }),
  getCapabilities: () => api.get<CalendarCapabilities>("calendar", "getCapabilities"),
  getSubscriptionLink: async (): Promise<CalendarSubscriptionLink> => {
    const res = await api.post<{
      token: string;
      downloadUrl?: string;
      subscriptionUrlHttp?: string;
      subscriptionUrlWebcal?: string;
    }>("auth", "getCalendarSubscriptionLink");
    return normalizeSubscriptionResult(res);
  },
  regenerateSubscriptionLink: async (): Promise<CalendarSubscriptionLink> => {
    const res = await api.post<{
      token: string;
      downloadUrl?: string;
      subscriptionUrlHttp?: string;
      subscriptionUrlWebcal?: string;
    }>("auth", "regenerateCalendarSubscriptionLink");
    return normalizeSubscriptionResult(res);
  },
};

function normalizeSubscriptionResult(res: {
  token?: string;
  downloadUrl?: string;
  subscriptionUrlHttp?: string;
  subscriptionUrlWebcal?: string;
}): CalendarSubscriptionLink {
  const token = String(res?.token ?? "").trim();
  const downloadUrl = String(res?.downloadUrl ?? "").trim();
  const subscriptionHttpUrl = String(res?.subscriptionUrlHttp ?? "").trim();
  const subscriptionUrl = String(res?.subscriptionUrlWebcal ?? "").trim();
  if (downloadUrl !== "" && subscriptionHttpUrl !== "" && subscriptionUrl !== "") {
    return {
      token,
      downloadUrl,
      subscriptionUrl,
      subscriptionHttpUrl,
    };
  }
  // Fallback for older backend responses.
  return buildSubscriptionUrls(token);
}

function buildSubscriptionUrls(token: string): CalendarSubscriptionLink {
  const safeToken = String(token ?? "").trim();
  const base = (process.env.NEXT_PUBLIC_API_BASE ?? "").replace(/\/api\/index\.php$/i, "").replace(/\/$/, "");
  const phpDir = base !== "" ? `${base}/api` : "/api";
  const downloadUrl = `${phpDir}/calendar.ics.php?token=${encodeURIComponent(safeToken)}&download=1`;
  const subscribeHttpUrl = `${phpDir}/calendar.ics.php?token=${encodeURIComponent(safeToken)}`;
  const subscriptionUrl = toWebcalUrl(subscribeHttpUrl);
  return {
    token: safeToken,
    downloadUrl,
    subscriptionUrl,
    subscriptionHttpUrl: subscribeHttpUrl,
  };
}

function toWebcalUrl(url: string): string {
  if (url.startsWith("https://")) {
    return "webcal://" + url.slice("https://".length);
  }
  if (url.startsWith("http://")) {
    return "webcal://" + url.slice("http://".length);
  }
  return url;
}
