/**
 * BNote Next Generation - Band Overview Content
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * Extended dashboard with Band Overview (admin tiles) and Your Overview sections.
 */

"use client";

import Link from "next/link";
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  rectSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { getIcon } from "@/components/icons";
import { type Session } from "@/lib/auth";
import { EventCard, type InboxEvent } from "@/components/EventCard";
import { DashboardTile } from "@/components/dashboard/DashboardTile";
import { Spinner } from "@/components/Spinner";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";
import { InstrumentCoverageSettingsModal } from "@/components/dashboard/InstrumentCoverageSettingsModal";
import { formatDateTimeShort, formatDateShortDisplay } from "@/lib/date-time";

const TILE_ORDER_KEY = "band-overview-tile-order";

const DEFAULT_TILE_ORDER = [
  "participation_gaps",
  "pending_accounts",
  "open_votes",
  "missed_deadlines",
  "cancellations",
  "open_tasks",
  "action_needed",
  "pending_invitations",
  "upcoming_events",
  "instrument_coverage",
] as const;

export type AdminOverviewData = {
  participation_gaps?: { count: number; events?: unknown[] };
  missed_deadlines?: { count: number; events?: unknown[] };
  votes_summary?: { open_count: number; open_votes?: unknown[]; votes_closing_soon?: unknown[] };
  tasks_overview?: { open_count: number; overdue_count: number };
  cancellations?: { total_count: number };
  instrument_gaps?: { count: number; events?: unknown[] };
  pending_invitations?: { count: number; total_members: number; events?: unknown[] };
  upcoming_events?: { events?: unknown[] };
  pending_accounts?: {
    count: number;
    users?: Array<{
      userId: number;
      contactId: number;
      login: string;
      name: string;
      surname: string;
      email: string;
    }>;
    default_integration_group?: number;
  };
  action_needed_count?: number;
};

export interface BandOverviewContentProps {
  session: Session | null;
  adminOverview: AdminOverviewData | null;
  dashboardData: { inbox?: unknown[]; news?: string } | null;
  eventsNeedingResponse: unknown[];
  activityFeed: unknown[];
  loading: boolean;
  error: string;
  onReload: () => Promise<void>;
}

function useTileOrder(): [
  string[],
  (value: string[] | ((prev: string[]) => string[])) => void,
] {
  const [order, setOrderState] = useState<string[]>(() => {
    if (typeof window === "undefined") return [...DEFAULT_TILE_ORDER];
    try {
      const stored = localStorage.getItem(TILE_ORDER_KEY);
      if (stored) {
        const parsed = JSON.parse(stored) as string[];
        const valid = DEFAULT_TILE_ORDER.filter((id) => parsed.includes(id));
        const missing = DEFAULT_TILE_ORDER.filter((id) => !parsed.includes(id));
        return [...valid, ...missing];
      }
    } catch {
      // ignore
    }
    return [...DEFAULT_TILE_ORDER];
  });

  const setOrder = useCallback(
    (value: string[] | ((prev: string[]) => string[])) => {
      setOrderState((prev) => {
        const newOrder = typeof value === "function" ? value(prev) : value;
        if (typeof window !== "undefined") {
          try {
            localStorage.setItem(TILE_ORDER_KEY, JSON.stringify(newOrder));
          } catch {
            // ignore
          }
        }
        return newOrder;
      });
    },
    []
  );

  return [order, setOrder];
}

function SortableTile({
  id,
  children,
}: {
  id: string;
  children: (handleProps: React.HTMLAttributes<HTMLElement>) => React.ReactNode;
}) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id });

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
    ...(isDragging && { position: "relative" as const, zIndex: 50 }),
  };

  const handleProps = { ...attributes, ...listeners };

  return (
    <div ref={setNodeRef} style={style} className="min-w-0 w-full overflow-hidden">
      {children(handleProps)}
    </div>
  );
}

export function BandOverviewContent({
  session,
  adminOverview,
  dashboardData,
  eventsNeedingResponse,
  activityFeed,
  loading,
  error,
  onReload,
}: BandOverviewContentProps) {
  const { t, lang } = useI18n();
  const [tileOrder, setTileOrder] = useTileOrder();
  const [instrumentSettingsOpen, setInstrumentSettingsOpen] = useState(false);
  const [participationRefreshToken, setParticipationRefreshToken] = useState(0);

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  );

  const handleDragEnd = useCallback(
    (event: DragEndEvent) => {
      const { active, over } = event;
      if (!over || active.id === over.id) return;
      setTileOrder((prev) => {
        const idx = prev.indexOf(String(active.id));
        const overIdx = prev.indexOf(String(over.id));
        if (idx === -1 || overIdx === -1) return prev;
        return arrayMove(prev, idx, overIdx);
      });
    },
    [setTileOrder]
  );

  const inboxEvents = useMemo(() => {
    const raw = dashboardData?.inbox ?? [];
    return raw as InboxEvent[];
  }, [dashboardData?.inbox]);

  const needResponseEvents = useMemo(
    () => (eventsNeedingResponse as InboxEvent[]).filter(Boolean),
    [eventsNeedingResponse]
  );

  const upcomingEvents = useMemo(() => {
    const evts = adminOverview?.upcoming_events?.events ?? dashboardData?.inbox ?? [];
    return (evts as InboxEvent[]).slice(0, 7);
  }, [adminOverview?.upcoming_events?.events, dashboardData?.inbox]);
  const handleEventStateChange = useCallback(() => {
    setParticipationRefreshToken((prev) => prev + 1);
    void onReload();
  }, [onReload]);

  if (loading && !dashboardData) {
    return (
      <main className={PAGE_CONTENT_BASE_CLASS}>
        <div className="flex items-center justify-center min-h-[200px]">
          <Spinner />
        </div>
      </main>
    );
  }

  return (
    <main className={PAGE_CONTENT_BASE_CLASS}>
      <div className="space-y-6 md:space-y-8 max-w-6xl mx-auto">
        {error && (
          <div className="alert alert-error" role="alert">
            <span>{error}</span>
            <button type="button" className="btn btn-sm btn-soft" onClick={onReload}>
              {t("js.common.loadMore")}
            </button>
          </div>
        )}

        {session?.isAdmin && adminOverview && (
          <section>
            <div className="flex items-center gap-2 mb-3 md:mb-4">
              <h2 className="text-xl font-semibold">{t("js.bandOverview.sectionTitle")}</h2>
              <span className="badge badge-soft badge-info">{t("js.bandOverview.admin")}</span>
            </div>
            <DndContext
              sensors={sensors}
              collisionDetection={closestCenter}
              onDragEnd={handleDragEnd}
            >
              <SortableContext items={tileOrder} strategy={rectSortingStrategy}>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-5 items-start">
                  {tileOrder.map((tileId) => (
                    <SortableTile key={tileId} id={tileId}>
                      {(handleProps) =>
                        renderAdminTile(
                          tileId,
                          adminOverview,
                          t,
                          lang,
                          handleProps,
                          () => setInstrumentSettingsOpen(true)
                        )
                      }
                    </SortableTile>
                  ))}
                </div>
              </SortableContext>
            </DndContext>
          </section>
        )}

        <section>
          <h2 className="text-xl font-semibold mb-3 md:mb-4">{t("js.bandOverview.yourOverview")}</h2>
          <div className="space-y-4 md:space-y-6">
            {Array.isArray(activityFeed) && activityFeed.length > 0 && (
              <div>
                <h3 className="text-sm font-medium mb-2">{t("js.bandOverview.recentActivity")}</h3>
                <ul className="space-y-2">
                  {activityFeed.slice(0, 10).map((item: unknown, i: number) => {
                    const it = item as { activity_type?: string; author_name?: string; message?: string; entity_title?: string; created_at?: string; title?: string };
                    return (
                      <li key={i} className="text-sm flex items-start gap-2">
                        {it.activity_type === "task_created" ? (
                          <>
                            <span className="badge badge-soft badge-warning badge-sm">Task</span>
                            <span>{t("js.bandOverview.newTask", [it.title ?? ""])}</span>
                          </>
                        ) : (
                          <>
                            <span className="badge badge-soft badge-info badge-sm">Comment</span>
                            <span>{it.author_name}: {String(it.message ?? "").slice(0, 60)}…</span>
                          </>
                        )}
                      </li>
                    );
                  })}
                </ul>
              </div>
            )}

            <div>
              <h3 className="text-sm font-medium mb-2">{t("js.dashboard.responseNeeded")}</h3>
              {needResponseEvents.length === 0 ? (
                <p className="text-sm text-base-content/70">{t("js.common.empty") || "—"}</p>
              ) : (
                <div className="space-y-2 md:space-y-3">
                  {needResponseEvents.slice(0, 10).map((ev) => (
                    <EventCard
                      key={`${ev.otype}-${ev.oid}`}
                      event={ev}
                      t={t}
                      lang={lang}
                      showParticipation={true}
                      onParticipationChange={handleEventStateChange}
                      onTaskComplete={handleEventStateChange}
                      participationRefreshToken={participationRefreshToken}
                    />
                  ))}
                </div>
              )}
            </div>

            <div>
              <h3 className="text-sm font-medium mb-2">{t("js.dashboard.upcomingEvents")}</h3>
              {upcomingEvents.length === 0 ? (
                <p className="text-sm text-base-content/70">{t("js.common.empty") || "—"}</p>
              ) : (
                <div className="space-y-2 md:space-y-3">
                  {upcomingEvents.slice(0, 7).map((ev) => (
                    <EventCard
                      key={`${ev.otype}-${ev.oid}`}
                      event={ev}
                      t={t}
                      lang={lang}
                      onParticipationChange={handleEventStateChange}
                      onTaskComplete={handleEventStateChange}
                      participationRefreshToken={participationRefreshToken}
                    />
                  ))}
                </div>
              )}
            </div>
          </div>
        </section>
      </div>
      <InstrumentCoverageSettingsModal
        open={instrumentSettingsOpen}
        onClose={() => setInstrumentSettingsOpen(false)}
        onSaved={onReload}
      />
    </main>
  );
}

type TileEvent = { id: number; otype: string; title?: string; begin?: string; approve_until?: string; participation_stats?: { pending?: number }; gaps?: { instrument_name?: string; current?: number; minimum?: number }[]; pending_users?: unknown[] };
type VoteEntry = { id: number; name?: string; end?: string };
type UpcomingEvent = { title?: string; eventBegin?: string; otype?: string };

function renderAdminTile(
  tileId: string,
  data: AdminOverviewData,
  t: (key: string, params?: string[]) => string,
  lang: string,
  dragHandleProps?: React.HTMLAttributes<HTMLElement>,
  onOpenInstrumentSettings?: () => void
): React.ReactNode {
  const participationGaps = data.participation_gaps;
  const missedDeadlines = data.missed_deadlines;
  const votesSummary = data.votes_summary;
  const tasksOverview = data.tasks_overview;
  const cancellations = data.cancellations;
  const instrumentGaps = data.instrument_gaps;
  const pendingInvitations = data.pending_invitations;
  const upcomingEvents = data.upcoming_events;
  const actionNeeded = data.action_needed_count ?? 0;

  switch (tileId) {
    case "participation_gaps": {
      const events = (participationGaps?.events ?? []) as TileEvent[];
      return (
        <DashboardTile
          titleKey="js.bandOverview.participationGaps"
          badgeVariant="warning"
          href="/rehearsals"
          icon="users"
          iconClass="text-warning"
          statDesc={t("js.bandOverview.eventsWithPending")}
          dragHandleProps={dragHandleProps}
        >
          {events.slice(0, 5).map((ev) => {
            const pending = ev.participation_stats?.pending ?? 0;
            return (
              <div key={`${ev.otype}-${ev.id}`} className="flex items-center justify-between gap-2 text-xs">
                <span className="truncate min-w-0">{ev.title ?? ""}</span>
                <span className="badge badge-sm badge-warning shrink-0">{pending} pending</span>
              </div>
            );
          })}
        </DashboardTile>
      );
    }
    case "pending_accounts": {
      const pa = data.pending_accounts;
      const cnt = pa?.count ?? 0;
      const group = pa?.default_integration_group ?? 2;
      const preview = pa?.users ?? [];
      return (
        <DashboardTile
          titleKey="js.dashboard.pendingAccounts"
          badgeVariant={cnt > 0 ? "warning" : "success"}
          href={`/contacts/integration/?group=${group}`}
          icon="user-plus"
          iconClass={cnt > 0 ? "text-warning" : "text-success"}
          statValue={cnt}
          statDesc={t("js.dashboard.pendingAccountsHint")}
          dragHandleProps={dragHandleProps}
        >
          {preview.slice(0, 5).map((u) => (
            <div key={u.userId} className="flex items-center justify-between gap-2 text-xs">
              <span className="truncate min-w-0">
                {[u.name, u.surname].filter(Boolean).join(" ") || u.login || u.email}
              </span>
              <span className="text-base-content/60 shrink-0 truncate max-w-[40%]">{u.email}</span>
            </div>
          ))}
        </DashboardTile>
      );
    }
    case "open_votes": {
      const openVotes = (votesSummary?.open_votes ?? []) as VoteEntry[];
      const closingSoon = (votesSummary?.votes_closing_soon ?? []) as VoteEntry[];
      const closingIds = new Set(closingSoon.map((v) => v.id));
      return (
        <DashboardTile
          titleKey="js.bandOverview.openVotes"
          badgeVariant={(votesSummary?.votes_closing_soon?.length ?? 0) > 0 ? "warning" : "default"}
          href="/votes"
          icon="vote"
          statDesc={
            (votesSummary?.votes_closing_soon?.length ?? 0) > 0
              ? t("js.bandOverview.closingSoon", [
                  String(votesSummary?.votes_closing_soon?.length ?? 0),
                ])
              : undefined
          }
          dragHandleProps={dragHandleProps}
        >
          {openVotes.slice(0, 5).map((v) => {
            const dateStr = formatDateTimeShort(v.end ?? "", lang) ?? "—";
            const soon = closingIds.has(v.id);
            return (
              <div key={v.id} className="flex items-center justify-between gap-2 text-xs">
                <span className={`truncate min-w-0 ${soon ? "font-medium" : ""}`}>{v.name ?? ""}</span>
                <span className={`shrink-0 ${soon ? "badge badge-sm badge-warning" : "text-base-content/60"}`}>{dateStr}</span>
              </div>
            );
          })}
        </DashboardTile>
      );
    }
    case "missed_deadlines": {
      const missedEvents = (missedDeadlines?.events ?? []) as TileEvent[];
      return (
        <DashboardTile
          titleKey="js.bandOverview.missedDeadlines"
          badgeVariant="error"
          href="/rehearsals"
          icon="clock"
          iconClass="text-error"
          dragHandleProps={dragHandleProps}
        >
          {missedEvents.slice(0, 5).map((ev) => (
            <div key={`${ev.otype}-${ev.id}`} className="truncate text-xs">{ev.title ?? ""}</div>
          ))}
        </DashboardTile>
      );
    }
    case "cancellations": {
      const cancelCount = cancellations?.total_count ?? 0;
      return (
        <DashboardTile
          titleKey="js.bandOverview.cancellations"
          href="/rehearsals"
          icon="users"
          statValue={cancelCount}
          statDesc={t("js.bandOverview.membersCancelled")}
          dragHandleProps={dragHandleProps}
        />
      );
    }
    case "open_tasks":
      return (
        <DashboardTile
          titleKey="js.bandOverview.openTasks"
          statValue={tasksOverview?.open_count ?? 0}
          badgeVariant={(tasksOverview?.overdue_count ?? 0) > 0 ? "error" : "warning"}
          href="/tasks"
          icon="check-square"
          statDesc={
            (tasksOverview?.overdue_count ?? 0) > 0
              ? t("js.bandOverview.overdue", [String(tasksOverview?.overdue_count ?? 0)])
              : undefined
          }
          dragHandleProps={dragHandleProps}
        />
      );
    case "action_needed":
      return (
        <DashboardTile
          titleKey="js.bandOverview.actionNeeded"
          badgeVariant={actionNeeded > 0 ? "error" : "success"}
          icon="alert-triangle"
          iconClass={actionNeeded > 0 ? "text-error" : "text-success"}
          statValue={actionNeeded}
          dragHandleProps={dragHandleProps}
        />
      );
    case "pending_invitations": {
      const invEvents = (pendingInvitations?.events ?? []) as TileEvent[];
      return (
        <DashboardTile
          titleKey="js.bandOverview.pendingInvitations"
          href="/rehearsals"
          icon="users"
          dragHandleProps={dragHandleProps}
        >
          {invEvents.slice(0, 4).map((ev) => {
            const n = Array.isArray(ev.pending_users) ? ev.pending_users.length : 0;
            return (
              <div key={`${ev.otype}-${ev.id}`} className="flex items-center justify-between gap-2 text-xs">
                <span className="truncate min-w-0">{ev.title ?? ""}</span>
                <span className="badge badge-sm badge-soft shrink-0">{n}</span>
              </div>
            );
          })}
        </DashboardTile>
      );
    }
    case "upcoming_events": {
      const upcoming = (upcomingEvents?.events ?? []) as UpcomingEvent[];
      return (
        <DashboardTile
          titleKey="js.bandOverview.upcomingEvents"
          href="/calendar"
          icon="calendar"
          dragHandleProps={dragHandleProps}
        >
          {upcoming.slice(0, 5).map((ev, i) => {
            const dateStr = formatDateShortDisplay(ev.eventBegin ?? "", lang);
            return (
              <div key={i} className="flex items-center gap-2 text-xs">
                <span className="text-base-content/60 shrink-0 w-12">{dateStr}</span>
                <span className="truncate min-w-0">{ev.title ?? ""}</span>
              </div>
            );
          })}
        </DashboardTile>
      );
    }
    case "instrument_coverage": {
      const SettingsIcon = getIcon("settings");
      const gapEvents = (instrumentGaps?.events ?? []) as TileEvent[];
      return (
        <DashboardTile
          titleKey="js.bandOverview.instrumentCoverage"
          badgeVariant={(instrumentGaps?.count ?? 0) > 0 ? "warning" : "success"}
          href="/rehearsals"
          icon="music"
          dragHandleProps={dragHandleProps}
          headerExtra={
            onOpenInstrumentSettings ? (
              <button
                type="button"
                className="btn btn-soft btn-xs btn-square shrink-0"
                aria-label={t("js.bandOverview.instrumentCoverageSettings")}
                onPointerDown={(e) => e.stopPropagation()}
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  onOpenInstrumentSettings();
                }}
              >
                <SettingsIcon className="h-4 w-4" />
              </button>
            ) : undefined
          }
        >
          {gapEvents.slice(0, 4).map((ev) => {
            const gapStr = (ev.gaps ?? [])
              .slice(0, 2)
              .map((g) => `${g.instrument_name ?? ""} ${g.current ?? 0}/${g.minimum ?? 0}`)
              .join(", ");
            return (
              <div key={`${ev.otype}-${ev.id}`} className="text-xs">
                <span className="truncate block">{ev.title ?? ""}</span>
                {gapStr && <span className="text-base-content/60">{gapStr}</span>}
              </div>
            );
          })}
        </DashboardTile>
      );
    }
    default:
      return null;
  }
}
