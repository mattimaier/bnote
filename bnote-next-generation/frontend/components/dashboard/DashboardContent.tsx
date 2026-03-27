/**
 * BNote Next Generation - Dashboard content (shared UI)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { type Session } from "@/lib/auth";
import { mapOtypeToEventType } from "@/lib/event-utils";
import { EventCard, type InboxEvent } from "@/components/EventCard";
import { DashboardGreeting } from "@/components/dashboard/DashboardGreeting";
import { getIcon } from "@/components/icons";
import { isQuickActionsEnabled } from "@/lib/entity-config";
import { useModules } from "@/lib/use-modules";
import { useNewsHtml, MAX_SHOW_DEFAULT } from "@/lib/dashboard-utils";
import { getDashboardEmptyResponseMessage } from "@/lib/dashboard-empty-state";
import { Spinner } from "@/components/Spinner";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";

export interface DashboardData {
  inbox: InboxEvent[];
  news?: string;
  company?: string | Record<string, string> | string[];
  counts?: { rehearsal: number; performance: number; meeting: number; vote?: number; task?: number; reservation?: number; appointment?: number };
  config?: { max_show?: number };
}

export interface EventsNeedingResponse {
  events: InboxEvent[];
  config?: { max_show?: number };
  counts?: { rehearsal: number; performance: number; meeting: number; vote?: number; task?: number; reservation?: number; appointment?: number };
}

export type SectionId = "events-needing-response" | "events-timeline";

function isHidden(event: InboxEvent): boolean {
  const status = (event as { status?: string }).status;
  const raw = String(status ?? "").toLowerCase();
  return raw === "hidden" || raw === "versteckt";
}

export interface DashboardContentProps {
  session: Session | null;
  dashboard: DashboardData | null;
  needResponse: {
    events: InboxEvent[];
    config?: { max_show?: number };
    counts?: { rehearsal: number; performance: number; meeting: number; vote?: number };
  };
  loading: boolean;
  error: string;
  onReload: () => Promise<void>;
  filterHiddenEvents?: boolean;
}

export default function DashboardContent({
  session,
  dashboard,
  needResponse,
  loading,
  error,
  onReload,
  filterHiddenEvents = false,
}: DashboardContentProps) {
  const { t, ready, lang } = useI18n();
  const modules = useModules();

  const [filters, setFilters] = useState<Record<SectionId, Set<string>>>({
    "events-needing-response": new Set(),
    "events-timeline": new Set(),
  });
  const [displayedCount, setDisplayedCount] = useState<Record<SectionId, number>>({
    "events-needing-response": MAX_SHOW_DEFAULT,
    "events-timeline": MAX_SHOW_DEFAULT,
  });

  useEffect(() => {
    const maxNeed = needResponse?.config?.max_show ?? MAX_SHOW_DEFAULT;
    const maxTimeline = dashboard?.config?.max_show ?? MAX_SHOW_DEFAULT;
    setDisplayedCount({
      "events-needing-response": Math.min(maxNeed, (needResponse?.events ?? []).length),
      "events-timeline": maxTimeline,
    });
  }, [dashboard, needResponse]);

  const toggleFilter = useCallback((sectionId: SectionId, filterType: string) => {
    setFilters((prev) => {
      const next = new Set(prev[sectionId]);
      if (next.has(filterType)) next.delete(filterType);
      else next.add(filterType);
      return { ...prev, [sectionId]: next };
    });
  }, []);

  const clearFilters = useCallback((sectionId: SectionId) => {
    setFilters((prev) => ({ ...prev, [sectionId]: new Set() }));
  }, []);

  const applyFilters = useCallback(
    (sectionId: SectionId, events: InboxEvent[]) => {
      const filterSet = filters[sectionId];
      if (!filterSet || filterSet.size === 0) return events;
      return events.filter((e) => filterSet.has(mapOtypeToEventType(e.otype)));
    },
    [filters]
  );

  const countByType = useCallback((events: InboxEvent[]) => {
    const c = { rehearsal: 0, performance: 0, meeting: 0, vote: 0, task: 0, reservation: 0, appointment: 0 };
    events.forEach((e) => {
      const type = mapOtypeToEventType(e.otype);
      if (type in c) (c as Record<string, number>)[type]++;
    });
    return c;
  }, []);

  const timelineEvents = useMemo(() => {
    const inbox = dashboard?.inbox ?? [];
    const filtered = filterHiddenEvents ? inbox.filter((e) => !isHidden(e)) : inbox;
    return filtered
      .filter((item) => item.eventBegin || item.dueDate)
      .sort((a, b) => {
        const dateA = new Date(a.eventBegin || a.dueDate!).getTime();
        const dateB = new Date(b.eventBegin || b.dueDate!).getTime();
        return dateA - dateB;
      });
  }, [dashboard?.inbox, filterHiddenEvents]);

  const needResponseFiltered = useMemo(
    () => applyFilters("events-needing-response", needResponse?.events ?? []),
    [needResponse?.events, applyFilters, filters["events-needing-response"]]
  );
  const timelineFiltered = useMemo(
    () => applyFilters("events-timeline", timelineEvents),
    [timelineEvents, applyFilters, filters["events-timeline"]]
  );
  const allEvents = useMemo(() => {
    const source = [...(dashboard?.inbox ?? []), ...(needResponse?.events ?? [])];
    const dedup = new Map<string, InboxEvent>();
    source.forEach((event) => {
      const key = `${event.otype}-${event.oid}`;
      if (!dedup.has(key)) dedup.set(key, event);
    });
    return [...dedup.values()];
  }, [dashboard?.inbox, needResponse?.events]);

  const maxNeed = needResponse?.config?.max_show ?? MAX_SHOW_DEFAULT;
  const maxTimeline = dashboard?.config?.max_show ?? MAX_SHOW_DEFAULT;
  const displayedNeed = Math.min(displayedCount["events-needing-response"], needResponseFiltered.length);
  const displayedTimeline = Math.min(displayedCount["events-timeline"], timelineFiltered.length);
  const showNeed = needResponseFiltered.slice(0, displayedNeed);
  const showTimeline = timelineFiltered.slice(0, displayedTimeline);
  const hasMoreNeed = needResponseFiltered.length > displayedNeed;
  const hasMoreTimeline = timelineFiltered.length > displayedTimeline;

  const loadMore = useCallback((sectionId: SectionId) => {
    const max = sectionId === "events-needing-response" ? maxNeed : maxTimeline;
    setDisplayedCount((prev) => ({
      ...prev,
      [sectionId]: prev[sectionId] + max,
    }));
  }, [maxNeed, maxTimeline]);

  const canEditNews = Boolean(
    modules?.some((m) => (m.route ?? "").replace(/^\//, "").toLowerCase() === "news" || m.name === "Nachrichten")
  );
  const newsContent = dashboard?.news;
  const hasNews = Boolean(newsContent && String(newsContent).trim());
  const newsHtml = useNewsHtml(hasNews ? String(newsContent) : undefined);

  const defaultCounts = { rehearsal: 0, performance: 0, meeting: 0, vote: 0, task: 0, reservation: 0, appointment: 0 };
  const needResponseCounts = needResponse?.counts ?? defaultCounts;
  const timelineCounts = dashboard?.counts ?? defaultCounts;
  const filterCountsNeed: { rehearsal: number; performance: number; meeting: number; vote: number; task: number; reservation: number; appointment: number } =
    filters["events-needing-response"]?.size > 0
      ? countByType(needResponseFiltered)
      : { ...defaultCounts, ...needResponseCounts };
  const filterCountsTimeline: { rehearsal: number; performance: number; meeting: number; vote: number; task: number; reservation: number; appointment: number } =
    filters["events-timeline"]?.size > 0
      ? countByType(timelineFiltered)
      : { ...defaultCounts, ...timelineCounts };

  const FilterBubbles = useCallback(
    ({
      sectionId,
      counts,
      unfilteredCounts,
    }: {
      sectionId: SectionId;
      counts: { rehearsal: number; performance: number; meeting: number; vote: number; task: number; reservation: number; appointment: number };
      unfilteredCounts: { rehearsal: number; performance: number; meeting: number; vote: number; task: number; reservation: number; appointment: number };
    }) => {
      const entityTypesWithItems = (["rehearsal", "performance", "vote", "task", "reservation", "appointment"] as const).filter(
        (k) => (unfilteredCounts[k] ?? 0) > 0
      );
      if (entityTypesWithItems.length < 2) return null;
      return (
      <div className="flex flex-wrap items-center gap-x-2 gap-y-2 md:gap-3">
        <span className="text-xs font-medium tracking-wide uppercase" style={{ color: "var(--muted-foreground)" }}>
          {t("js.common.filter")}
        </span>
        {entityTypesWithItems.map((filterType) => {
          const count = counts[filterType] ?? 0;
          const selected = filters[sectionId]?.has(filterType);
          const bubbleClass =
            filterType === "rehearsal"
              ? "filter-bubble filter-bubble-rehearsal"
              : filterType === "performance"
                ? "filter-bubble filter-bubble-performance"
                : filterType === "vote"
                  ? "filter-bubble filter-bubble-vote"
                  : filterType === "task"
                    ? "filter-bubble filter-bubble-task"
                    : filterType === "reservation"
                      ? "filter-bubble filter-bubble-reservation"
                      : filterType === "appointment"
                        ? "filter-bubble filter-bubble-appointment"
                        : "filter-bubble filter-bubble-task";
          const labelKey =
            filterType === "rehearsal"
              ? "js.event.rehearsal"
              : filterType === "performance"
                ? "js.event.performance"
                : filterType === "vote"
                  ? "js.sidebar.votes"
                  : filterType === "task"
                    ? "js.sidebar.tasks"
                    : filterType === "reservation"
                      ? "js.calendar.reservationLabel"
                      : filterType === "appointment"
                        ? "js.calendar.appointmentLabel"
                        : "js.sidebar.tasks";
          return (
            <button
              key={filterType}
              type="button"
              onClick={() => toggleFilter(sectionId, filterType)}
              className={`${bubbleClass} ${selected ? "selected" : ""}`}
            >
              {t(labelKey)}{" "}
              <span className="opacity-70 ml-1">({count})</span>
            </button>
          );
        })}
        <button
          type="button"
          onClick={() => clearFilters(sectionId)}
          className="text-xs px-2 py-1 rounded-md transition-colors hover:bg-[var(--muted)] whitespace-nowrap ml-auto sm:ml-0"
          style={{ color: "var(--muted-foreground)" }}
        >
          {t("js.common.clear")}
        </button>
      </div>
    );
    },
    [t, filters, toggleFilter, clearFilters]
  );
  const emptyNeedResponseMessage = useMemo(
    () =>
      getDashboardEmptyResponseMessage({
        userName: session?.user?.name,
        variantCount: 10,
        t,
      }),
    [session?.user?.name, t]
  );

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error) {
    return (
      <div
        className="rounded-lg border px-4 py-3"
        style={{
          borderColor: "var(--destructive)",
          background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
          color: "var(--destructive-foreground)",
        }}
      >
        {error}
      </div>
    );
  }

  const getWeekBounds = (): { start: Date; end: Date } => {
    const now = new Date();
    const start = new Date(now);
    const day = (now.getDay() + 6) % 7; // Monday=0 ... Sunday=6
    start.setDate(now.getDate() - day);
    start.setHours(0, 0, 0, 0);
    const end = new Date(start);
    end.setDate(start.getDate() + 7);
    return { start, end };
  };
  const getEventDate = (event: InboxEvent): Date | null => {
    const raw = event.eventBegin || event.dueDate || event.begin;
    if (!raw) return null;
    const date = new Date(raw);
    return Number.isNaN(date.getTime()) ? null : date;
  };
  const isInCurrentWeek = (event: InboxEvent): boolean => {
    const date = getEventDate(event);
    if (!date) return false;
    const { start, end } = getWeekBounds();
    return date >= start && date < end;
  };

  const pendingResponses = (needResponse?.events ?? []).length;
  const weekEvents = timelineEvents.filter(isInCurrentWeek);
  const weekUpcomingEvents = weekEvents.length;
  const weekRehearsals = weekEvents.filter((e) => mapOtypeToEventType(e.otype) === "rehearsal").length;
  const weekConcerts = weekEvents.filter((e) => mapOtypeToEventType(e.otype) === "performance").length;

  const isSameLocalDay = (dateValue?: string): boolean => {
    if (!dateValue) return false;
    const d = new Date(dateValue);
    if (Number.isNaN(d.getTime())) return false;
    const now = new Date();
    return (
      d.getFullYear() === now.getFullYear() &&
      d.getMonth() === now.getMonth() &&
      d.getDate() === now.getDate()
    );
  };

  const todayDueTasks = allEvents.filter((event) => event.otype === "T" && isSameLocalDay(event.dueDate || event.eventBegin || event.begin)).length;
  const todayRehearsals = allEvents.filter((event) => event.otype === "R" && isSameLocalDay(event.eventBegin || event.begin || event.dueDate)).length;
  const todayConcerts = allEvents.filter((event) => event.otype === "C" && isSameLocalDay(event.eventBegin || event.begin || event.dueDate)).length;

  const quickActions = [
    { titleKey: "js.dashboard.quickAction.viewCalendar", descKey: "js.dashboard.quickAction.viewCalendarDesc", icon: "calendar-days", colorClass: "bg-primary/10 text-primary hover:bg-primary/20", href: "/calendar" },
    { titleKey: "js.dashboard.quickAction.contactBand", descKey: "js.dashboard.quickAction.contactBandDesc", icon: "message-square", colorClass: "bg-accent/10 text-accent hover:bg-accent/20", href: "#" },
    { titleKey: "js.dashboard.quickAction.bandDirectory", descKey: "js.dashboard.quickAction.bandDirectoryDesc", icon: "users", colorClass: "bg-chart-3/10 text-chart-3 hover:bg-chart-3/20", href: "/contacts" },
    { titleKey: "js.dashboard.quickAction.myProfile", descKey: "js.dashboard.quickAction.myProfileDesc", icon: "music", colorClass: "bg-chart-4/10 text-chart-4 hover:bg-chart-4/20", href: "#" },
  ];

  return (
    <div className={PAGE_CONTENT_BASE_CLASS}>
      <div className="mb-5 pb-4 border-b border-border/30 md:mb-6 md:pb-4">
        <DashboardGreeting
          session={session}
          dashboard={dashboard}
          pendingResponses={pendingResponses}
          upcomingEvents={weekUpcomingEvents}
          upcomingRehearsals={weekRehearsals}
          upcomingConcerts={weekConcerts}
          todayDueTasks={todayDueTasks}
          todayRehearsals={todayRehearsals}
          todayConcerts={todayConcerts}
        />
      </div>

      <div className="space-y-5 md:space-y-4">
        {hasNews && newsHtml && (
          <div
            className="overflow-hidden border-0 rounded-none shadow-none md:card md:card-border md:shadow-sm md:rounded-xl"
            style={{
              borderColor: "color-mix(in oklch, var(--primary) 25%, transparent)",
              backgroundColor: "color-mix(in oklch, var(--primary) 6%, transparent)",
              color: "var(--color-base-content)",
            }}
          >
            <div className="px-3 py-2.5 border-b border-border/20 md:card-header md:px-4 md:py-3 md:border-border/30 flex flex-row items-center justify-between gap-3">
              <h2 className="card-title text-base font-semibold m-0">
                {t("js.sidebar.news") !== "js.sidebar.news" ? t("js.sidebar.news") : "News"}
              </h2>
              {canEditNews && (
                <Link href="/news/" className="btn btn-soft btn-sm btn-primary text-sm shrink-0">
                  {t("js.news.edit") !== "js.news.edit" ? t("js.news.edit") : "Edit"}
                </Link>
              )}
            </div>
            <div
              className="px-3 py-2.5 md:card-body md:px-4 md:py-3 text-base-content/90 rich-text-content"
              dangerouslySetInnerHTML={{ __html: newsHtml }}
            />
          </div>
        )}

        {isQuickActionsEnabled() && (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-3 rounded-none border-0 p-0 shadow-none bg-transparent text-card-foreground md:rounded-xl md:border md:border-border/40 md:p-4 md:shadow-sm md:bg-card">
            {quickActions.map((action) => {
              const Icon = getIcon(action.icon);
              return (
                <Link
                  key={action.titleKey}
                  href={action.href}
                    className={`group flex flex-col items-center gap-2 p-3 md:gap-3 md:p-4 rounded-lg border border-border/20 md:border-border/40 transition-all duration-200 hover:border-primary/30 md:hover:shadow-md hover:bg-primary/5 ${action.colorClass}`}
                >
                  <div className="p-2 rounded-lg bg-current/10 group-hover:bg-current/15 transition-colors">
                    <Icon className="h-5 w-5" />
                  </div>
                  <div className="text-center">
                    <p className="font-semibold text-sm leading-tight">{t(action.titleKey)}</p>
                    <p className="text-xs mt-1 text-muted-foreground/70 font-normal">{t(action.descKey)}</p>
                  </div>
                </Link>
              );
            })}
          </div>
        )}

        <div className="flex flex-col gap-4 md:gap-4 py-3 md:py-4 md:rounded-xl md:border md:border-border/40 md:shadow-sm md:hover:shadow-md transition-shadow bg-transparent md:bg-card text-card-foreground">
          <div className="px-0 md:px-4 lg:px-5 pb-2.5 md:pb-2 md:border-b md:border-border/30">
            <h2 className="text-base md:text-base font-semibold text-foreground">{t("js.dashboard.responseNeeded")}</h2>
          </div>
          <div className="px-0 md:px-4 lg:px-5 pb-2.5 md:pb-3">
            <FilterBubbles
              sectionId="events-needing-response"
              counts={filterCountsNeed}
              unfilteredCounts={{ ...defaultCounts, ...needResponseCounts }}
            />
          </div>
          <div className="relative space-y-3 md:space-y-3 px-0 md:px-4 lg:px-5">
            {showNeed.length === 0 ? (
              <p className="text-sm py-10 text-center" style={{ color: "var(--muted-foreground)" }}>
                {emptyNeedResponseMessage}
              </p>
            ) : (
              showNeed.map((ev, idx) => (
                <EventCard
                  key={`${ev.otype}-${ev.oid}`}
                  event={ev}
                  t={t}
                  lang={lang}
                  showParticipation
                  isLast={idx === showNeed.length - 1 && !hasMoreNeed}
                  onParticipationChange={onReload}
                  onTaskComplete={onReload}
                />
              ))
            )}
            {hasMoreNeed && (
              <div className="flex justify-center mt-6">
                <button
                  type="button"
                  onClick={() => loadMore("events-needing-response")}
                  className="load-more-btn px-6 py-3 text-sm font-semibold rounded-lg border-2 border-primary transition-all shadow-sm hover:shadow-md hover:border-primary/50"
                  style={{
                    color: "var(--primary)",
                    background: "color-mix(in oklch, var(--primary) 10%, transparent)",
                  }}
                >
                  {t("js.common.loadMore")}
                </button>
              </div>
            )}
          </div>
        </div>

        <div className="flex flex-col gap-4 md:gap-4 py-3 md:py-4 md:rounded-xl md:border md:border-border/40 md:shadow-sm md:hover:shadow-md transition-shadow bg-transparent md:bg-card text-card-foreground">
          <div className="px-0 md:px-4 lg:px-5 pb-2.5 md:pb-2 md:border-b md:border-border/30">
            <h2 className="text-base md:text-base font-semibold text-foreground">{t("js.dashboard.upcomingEvents")}</h2>
          </div>
          <div className="px-0 md:px-4 lg:px-5 pb-2.5 md:pb-3">
            <FilterBubbles
              sectionId="events-timeline"
              counts={filterCountsTimeline}
              unfilteredCounts={{ ...defaultCounts, ...timelineCounts }}
            />
          </div>
          <div className="relative space-y-3 md:space-y-3 px-0 md:px-4 lg:px-5">
            {showTimeline.length === 0 ? (
              <p className="text-sm py-10 text-center" style={{ color: "var(--muted-foreground)" }}>
                {t("js.dashboard.noUpcomingEvents") !== "js.dashboard.noUpcomingEvents" ? t("js.dashboard.noUpcomingEvents") : "No upcoming events scheduled."}
              </p>
            ) : (
              showTimeline.map((ev, idx) => (
                <EventCard
                  key={`${ev.otype}-${ev.oid}`}
                  event={ev}
                  t={t}
                  lang={lang}
                  showParticipation
                  isLast={idx === showTimeline.length - 1 && !hasMoreTimeline}
                  onParticipationChange={onReload}
                  onTaskComplete={onReload}
                />
              ))
            )}
            {hasMoreTimeline && (
              <div className="flex justify-center mt-6">
                <button
                  type="button"
                  onClick={() => loadMore("events-timeline")}
                  className="load-more-btn px-6 py-3 text-sm font-semibold rounded-lg border-2 border-primary transition-all shadow-sm hover:shadow-md hover:border-primary/50"
                  style={{
                    color: "var(--primary)",
                    background: "color-mix(in oklch, var(--primary) 10%, transparent)",
                  }}
                >
                  {t("js.common.loadMore")}
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
