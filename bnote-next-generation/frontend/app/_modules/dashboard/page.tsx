/**
 * BNote Next Generation - Dashboard Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";
import { checkSession, type Session } from "@/lib/auth";
import { mapOtypeToEventType } from "@/lib/event-utils";
import { EventCard, type InboxEvent } from "@/components/EventCard";
import { getIcon } from "@/components/icons";
import { isQuickActionsEnabled } from "@/lib/entity-config";

interface DashboardData {
  inbox: InboxEvent[];
  company?: string | Record<string, string> | string[];
  counts?: { rehearsal: number; performance: number; meeting: number };
  config?: { max_show?: number };
}

/**
 * Normalize company/band name from API response.
 * Handles string, array, or object formats (e.g., from SimpleXMLElement JSON encoding).
 * Recurses into nested objects so we never render [object Object].
 */
function normalizeCompany(val: unknown): string {
  if (val == null) return "";
  if (typeof val === "string") return val.trim();
  if (Array.isArray(val)) return normalizeCompany(val[0]);
  if (typeof val === "object") {
    const obj = val as Record<string, unknown>;
    const v = obj["0"] ?? obj["name"] ?? obj["value"] ?? Object.values(obj)[0];
    if (v == null) return "";
    if (typeof v === "string") return v.trim();
    return normalizeCompany(v);
  }
  return "";
}

interface EventsNeedingResponse {
  events: InboxEvent[];
  config?: { max_show?: number };
  counts?: { rehearsal: number; performance: number; meeting: number };
}

function isHiddenEvent(event: InboxEvent): boolean {
  const status = (event as { status?: string }).status;
  const raw = String(status ?? "").toLowerCase();
  return raw === "hidden" || raw === "versteckt";
}

type SectionId = "events-needing-response" | "events-timeline";

const MAX_SHOW_DEFAULT = 5;

export default function DashboardPage() {
  const { t, ready, lang } = useI18n();
  const [session, setSession] = useState<Session | null>(null);
  const [dashboard, setDashboard] = useState<DashboardData | null>(null);
  const [eventsNeedingResponse, setEventsNeedingResponse] = useState<InboxEvent[]>([]);
  const [needResponseConfig, setNeedResponseConfig] = useState<{ max_show?: number }>({});
  const [needResponseCounts, setNeedResponseCounts] = useState<Record<string, number>>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [filters, setFilters] = useState<Record<SectionId, Set<string>>>({
    "events-needing-response": new Set(),
    "events-timeline": new Set(),
  });
  const [displayedCount, setDisplayedCount] = useState<Record<SectionId, number>>({
    "events-needing-response": MAX_SHOW_DEFAULT,
    "events-timeline": MAX_SHOW_DEFAULT,
  });
  const [greeting, setGreeting] = useState("Hello");

  useEffect(() => {
    checkSession().then((s) => setSession(s));
  }, []);

  const loadDashboard = useCallback(async () => {
    try {
      const [dash, needResp] = await Promise.all([
        api.get<DashboardData>("dashboard", "dashboard"),
        api.get<EventsNeedingResponse>("dashboard", "eventsNeedingResponse"),
      ]);
      setDashboard(dash);
      setEventsNeedingResponse(needResp?.events ?? []);
      setNeedResponseConfig(needResp?.config ?? {});
      setNeedResponseCounts(needResp?.counts ?? { rehearsal: 0, performance: 0, meeting: 0 });
      const maxNeed = needResp?.config?.max_show ?? MAX_SHOW_DEFAULT;
      const maxTimeline = dash?.config?.max_show ?? MAX_SHOW_DEFAULT;
      setDisplayedCount({
        "events-needing-response": Math.min(maxNeed, (needResp?.events ?? []).length),
        "events-timeline": maxTimeline,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load dashboard");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!ready) return;
    loadDashboard();
  }, [ready, loadDashboard]);

  useEffect(() => {
    if (!ready) return;
    const welcomeText = t("banner_Logout.welcome");
    if (welcomeText && welcomeText !== "banner_Logout.welcome") {
      setGreeting(welcomeText);
    } else {
      const hour = new Date().getHours();
      const morning = t("js.common.greeting.morning");
      const afternoon = t("js.common.greeting.afternoon");
      const evening = t("js.common.greeting.evening");
      if (hour < 12) setGreeting(morning !== "js.common.greeting.morning" ? morning : "Good morning");
      else if (hour < 18) setGreeting(afternoon !== "js.common.greeting.afternoon" ? afternoon : "Good afternoon");
      else setGreeting(evening !== "js.common.greeting.evening" ? evening : "Good evening");
    }
  }, [ready, t]);

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
      const visible = events.filter((e) => !isHiddenEvent(e));
      if (!filterSet || filterSet.size === 0) return visible;
      return visible.filter((e) => filterSet.has(mapOtypeToEventType(e.otype)));
    },
    [filters]
  );

  const countByType = useCallback((events: InboxEvent[]) => {
    const c = { rehearsal: 0, performance: 0, meeting: 0 };
    events.forEach((e) => {
      const type = mapOtypeToEventType(e.otype);
      if (type in c) (c as Record<string, number>)[type]++;
    });
    return c;
  }, []);

  const needResponseFiltered = useMemo(
    () => applyFilters("events-needing-response", eventsNeedingResponse),
    [eventsNeedingResponse, applyFilters, filters["events-needing-response"]]
  );
  const timelineEvents = useMemo(() => {
    const inbox = (dashboard?.inbox ?? []).filter((item) => !isHiddenEvent(item));
    return inbox
      .filter((item) => item.eventBegin || item.dueDate)
      .sort((a, b) => {
        const dateA = new Date(a.eventBegin || a.dueDate!).getTime();
        const dateB = new Date(b.eventBegin || b.dueDate!).getTime();
        return dateA - dateB;
      });
  }, [dashboard?.inbox]);
  const timelineFiltered = useMemo(
    () => applyFilters("events-timeline", timelineEvents),
    [timelineEvents, applyFilters, filters["events-timeline"]]
  );

  const maxNeed = needResponseConfig?.max_show ?? MAX_SHOW_DEFAULT;
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

  const defaultCounts = { rehearsal: 0, performance: 0, meeting: 0 };
  const filterCountsNeed: { rehearsal: number; performance: number; meeting: number } =
    filters["events-needing-response"]?.size > 0
      ? countByType(needResponseFiltered)
      : { ...defaultCounts, ...needResponseCounts };
  const filterCountsTimeline: { rehearsal: number; performance: number; meeting: number } =
    filters["events-timeline"]?.size > 0
      ? countByType(timelineFiltered)
      : { ...defaultCounts, ...(dashboard?.counts ?? {}) };

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
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

  const firstName = session?.user?.name || t("js.common.user");
  const companyName = String(normalizeCompany(dashboard?.company) || t("js.common.appName")).trim();
  const subtitle = t("js.dashboard.subtitle", [companyName]);

  const quickActions = [
    {
      titleKey: "js.dashboard.quickAction.viewCalendar",
      descKey: "js.dashboard.quickAction.viewCalendarDesc",
      icon: "calendar-days",
      colorClass: "bg-primary/10 text-primary hover:bg-primary/20",
      href: "#",
    },
    {
      titleKey: "js.dashboard.quickAction.contactBand",
      descKey: "js.dashboard.quickAction.contactBandDesc",
      icon: "message-square",
      colorClass: "bg-accent/10 text-accent hover:bg-accent/20",
      href: "#",
    },
    {
      titleKey: "js.dashboard.quickAction.bandDirectory",
      descKey: "js.dashboard.quickAction.bandDirectoryDesc",
      icon: "users",
      colorClass: "bg-chart-3/10 text-chart-3 hover:bg-chart-3/20",
      href: "/contacts",
    },
    {
      titleKey: "js.dashboard.quickAction.myProfile",
      descKey: "js.dashboard.quickAction.myProfileDesc",
      icon: "music",
      colorClass: "bg-chart-4/10 text-chart-4 hover:bg-chart-4/20",
      href: "#",
    },
  ];

  const FilterBubbles = ({
    sectionId,
    counts,
  }: {
    sectionId: SectionId;
    counts: { rehearsal: number; performance: number; meeting: number };
  }) => (
    <div className="flex items-center gap-3 flex-wrap">
      <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
        {t("js.common.filter")}:
      </span>
      {(["rehearsal", "performance"] as const).map((filterType) => {
        const selected = filters[sectionId]?.has(filterType);
        const bubbleClass =
          filterType === "rehearsal"
            ? "filter-bubble filter-bubble-rehearsal"
            : "filter-bubble filter-bubble-performance";
        return (
          <button
            key={filterType}
            type="button"
            onClick={() => toggleFilter(sectionId, filterType)}
            className={`${bubbleClass} ${selected ? "selected" : ""}`}
          >
            {t(filterType === "rehearsal" ? "js.event.rehearsal" : "js.event.performance")}{" "}
            <span className="opacity-70 ml-1">({counts[filterType] ?? 0})</span>
          </button>
        );
      })}
      <button
        type="button"
        onClick={() => clearFilters(sectionId)}
        className="text-xs px-2 py-1 rounded-md transition-colors hover:bg-[var(--muted)]"
        style={{ color: "var(--muted-foreground)" }}
      >
        {t("js.common.clear")}
      </button>
    </div>
  );

  return (
    <div className="mx-auto max-w-4xl">
      <div className="mb-6 pb-4 border-b border-border/30">
        <h1 className="text-3xl font-bold tracking-tight text-foreground">{greeting}, {firstName}</h1>
        <p className="text-sm font-medium mt-1 text-muted-foreground">{subtitle}</p>
      </div>

      <div className="space-y-4">
        {/* Quick actions (config: features.showQuickActions) */}
        {isQuickActionsEnabled() && (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 rounded-xl border border-border/40 p-4 shadow-sm bg-card text-card-foreground">
            {quickActions.map((action) => {
              const Icon = getIcon(action.icon);
              return (
                <Link
                  key={action.titleKey}
                  href={action.href}
                  className={`group flex flex-col items-center gap-3 p-4 rounded-lg border border-border/40 transition-all duration-200 hover:border-primary/30 hover:shadow-md hover:bg-primary/5 ${action.colorClass}`}
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

        {/* Events needing response: md:border-border/40, section border-border/30, px-1 md:px-4 lg:px-5 */}
        <div className="flex flex-col gap-4 py-2 md:py-4 md:rounded-xl md:border md:border-border/40 md:shadow-sm md:hover:shadow-md transition-shadow bg-card text-card-foreground md:bg-card">
          <div className="px-1 md:px-4 lg:px-5 pb-2 md:border-b md:border-border/30">
            <h2 className="text-sm md:text-base font-semibold text-foreground">{t("js.dashboard.responseNeeded")}</h2>
          </div>
          <div className="px-1 md:px-4 lg:px-5 pb-2 md:pb-3">
            <FilterBubbles sectionId="events-needing-response" counts={filterCountsNeed} />
          </div>
          <div className="relative space-y-2 md:space-y-3 px-1 md:px-4 lg:px-5">
            {showNeed.length === 0 ? (
              <p className="text-sm py-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                {t("js.dashboard.noEventsNeedingResponse") !== "js.dashboard.noEventsNeedingResponse"
                  ? t("js.dashboard.noEventsNeedingResponse")
                  : "No events need your response at this time."}
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
                  onParticipationChange={loadDashboard}
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

        {/* Upcoming events timeline */}
        <div className="flex flex-col gap-4 py-2 md:py-4 md:rounded-xl md:border md:border-border/40 md:shadow-sm md:hover:shadow-md transition-shadow bg-card text-card-foreground md:bg-card">
          <div className="px-1 md:px-4 lg:px-5 pb-2 md:border-b md:border-border/30">
            <h2 className="text-sm md:text-base font-semibold text-foreground">{t("js.dashboard.upcomingEvents")}</h2>
          </div>
          <div className="px-1 md:px-4 lg:px-5 pb-2 md:pb-3">
            <FilterBubbles sectionId="events-timeline" counts={filterCountsTimeline} />
          </div>
          <div className="relative space-y-2 md:space-y-3 px-1 md:px-4 lg:px-5">
            {showTimeline.length === 0 ? (
              <p className="text-sm py-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                No upcoming events scheduled.
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
                  onParticipationChange={loadDashboard}
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
