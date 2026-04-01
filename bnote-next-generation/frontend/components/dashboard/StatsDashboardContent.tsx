"use client";

import { useMemo } from "react";
import { useRouter } from "next/navigation";
import {
  Bar,
  BarChart,
  Area,
  AreaChart,
  CartesianGrid,
  Cell,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";
import { Spinner } from "@/components/Spinner";
import { useI18n } from "@/contexts/I18nContext";
import { type StatsDashboardData } from "@/lib/stats-api";
import { getEntityConfig, getEscalationWarningUiConfig, getEventTypeConfig, getStatusPillStyle } from "@/lib/entity-config";
import { EntityListRow } from "@/components/EntityListRow";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { ParticipationDiagram, PARTICIPATION_SEGMENT_COLORS } from "@/components/ParticipationDiagram";
import { TablerIconByName } from "@/components/icons";
import { getIcon } from "@/components/icons";
import { formatDateShortDisplay, formatDateTimeShort } from "@/lib/date-time";
import { Clock, MapPin } from "@/components/icons";
import { getEntityPath } from "@/lib/entities/paths";

const CHART_COLORS = {
  rehearsal: "var(--primary)",
  concert: "var(--accent)",
  trend: "var(--chart-3)",
  completion: "var(--chart-4)",
  yes: PARTICIPATION_SEGMENT_COLORS.yes,
  maybe: PARTICIPATION_SEGMENT_COLORS.maybe,
  no: PARTICIPATION_SEGMENT_COLORS.no,
  pending: PARTICIPATION_SEGMENT_COLORS.pending,
};


function StatCard({
  iconName,
  iconColor,
  title,
  value,
  hint,
}: {
  iconName: string;
  iconColor: string;
  title: string;
  value: string;
  hint?: string;
}) {
  return (
    <div className="stats bg-base-100 md:stats-border shadow-none md:shadow rounded-none md:rounded-box overflow-hidden">
      <div className="stat px-4 py-3">
        <div className="flex items-center justify-between gap-2">
          <div className="stat-title text-sm">{title}</div>
          <span className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-base-200" style={{ color: iconColor }}>
            <TablerIconByName name={iconName} className="h-4 w-4" />
          </span>
        </div>
        <div className="stat-value text-2xl md:text-3xl">{value}</div>
        {hint ? <div className="stat-desc">{hint}</div> : null}
      </div>
    </div>
  );
}

export function StatsDashboardContent({
  data,
  loading,
  error,
  scope,
  year,
  onScopeChange,
  onYearChange,
  onReload,
}: {
  data: StatsDashboardData | null;
  loading: boolean;
  error: string;
  scope: "year" | "all";
  year: number;
  onScopeChange: (value: "year" | "all") => void;
  onYearChange: (value: number) => void;
  onReload: () => Promise<void>;
}) {
  const { t, lang } = useI18n();
  const router = useRouter();
  const statsEntity = getEntityConfig("stats");
  const statsAccent = statsEntity?.color ?? "var(--primary)";
  const availableYears = data?.meta.availableYears ?? [];

  const eventsSeries = useMemo(() => {
    const all = data?.eventsByMonth ?? [];
    return all;
  }, [data?.eventsByMonth]);

  const participationSeries = useMemo(() => {
    const all = data?.participationTrend ?? [];
    return all;
  }, [data?.participationTrend]);

  const responseSeries = useMemo(() => {
    const all = data?.responseCompletionTrend ?? [];
    return all;
  }, [data?.responseCompletionTrend]);

  const membersPerGroup = data?.membersPerGroup ?? [];
  const topRehearsal = data?.topParticipants.rehearsals ?? [];
  const topVotes = data?.topParticipants.votes ?? [];
  const criticalEvents = data?.criticalEventsList ?? [];
  const responseBehavior = data?.responseBehavior;
  const responseMixTrend = responseBehavior?.mixTrend ?? [];
  const responseFunnel = responseBehavior?.funnel ?? { invited: 0, responded: 0, confirmed: 0 };
  const leadTime = responseBehavior?.leadTimeHours;
  const lateResponses = responseBehavior?.lateResponses;
  const noResponses = responseBehavior?.noResponses;
  const participationStability = data?.participationStability;
  const activeMembersTrend = data?.activeMembersTrend?.series ?? [];
  const responseConsistency = data?.responseConsistency?.buckets ?? [];
  const taskLatency = data?.taskCompletionLatency;
  const voteParticipationTrend = data?.voteParticipationTrend?.series ?? [];
  const reminderEffectiveness = data?.reminderEffectiveness;
  const instrumentCoverage = data?.instrumentCoverageRisk?.byInstrument ?? [];
  const userRankings = data?.userRankings;

  const leadTimeValue = leadTime?.sampleSize ? `${leadTime.medianHours.toFixed(1)}h` : "—";
  const leadTimeHint = leadTime?.sampleSize
    ? t("js.stats.cards.leadTimeHint", [`${leadTime.p90Hours.toFixed(1)}h`, String(leadTime.sampleSize)])
    : t("js.stats.cards.leadTimeEmpty");
  const lateRateValue = lateResponses ? `${lateResponses.rate.toFixed(1)}%` : "0.0%";
  const lateRateHint = lateResponses
    ? t("js.stats.cards.lateResponsesHint", [String(lateResponses.late), String(lateResponses.total)])
    : t("js.common.empty");
  const noResponseValue = noResponses ? `${noResponses.rate.toFixed(1)}%` : "0.0%";
  const noResponseHint = noResponses
    ? t("js.stats.cards.noResponsesHint", [String(noResponses.pending), String(noResponses.invited)])
    : t("js.common.empty");
  const stabilityValue = participationStability ? `${participationStability.stdDev.toFixed(1)}%` : "—";
  const stabilityHint = participationStability
    ? t("js.stats.cards.participationStabilityHint", [String(participationStability.variance.toFixed(2))])
    : t("js.common.empty");
  const activeMemberRate = data?.activeMembersTrend?.overallRate ?? 0;
  const activeMemberHint = data?.activeMembersTrend
    ? t("js.stats.cards.activeMembersHint", [String(data.activeMembersTrend.active), String(data.activeMembersTrend.total)])
    : t("js.common.empty");
  const taskLatencyValue = taskLatency?.available ? `${taskLatency.overallMedian.toFixed(1)}h` : "—";
  const taskLatencyHint = taskLatency?.available
    ? t("js.stats.cards.taskLatencyHint")
    : t("js.stats.cards.taskLatencyEmpty");
  const voteParticipationValue = data?.voteParticipationTrend ? `${data.voteParticipationTrend.overallRate.toFixed(1)}%` : "—";
  const voteParticipationHint = t("js.stats.cards.voteParticipationHint");
  const reminderUpliftValue = reminderEffectiveness ? `${reminderEffectiveness.upliftRate.toFixed(1)}%` : "—";
  const reminderUpliftHint = reminderEffectiveness
    ? t("js.stats.cards.reminderEffectivenessHint", [String(reminderEffectiveness.afterCount), String(reminderEffectiveness.beforeCount)])
    : t("js.common.empty");

  const formatRankingValue = (metricKey: string, value: number) => {
    if (metricKey.toLowerCase().includes("rate")) {
      return `${value.toFixed(1)}%`;
    }
    if (metricKey.toLowerCase().includes("responses") || metricKey.toLowerCase().includes("noresponses")) {
      return String(Math.round(value));
    }
    if (metricKey.toLowerCase().includes("lead") || metricKey.toLowerCase().includes("response")) {
      return `${value.toFixed(1)}h`;
    }
    return String(value);
  };

  const rankingMetricLabel = (key: string) => {
    const map: Record<string, string> = {
      fastestResponses: "js.stats.rankings.fastestResponses",
      highestResponseRate: "js.stats.rankings.highestResponseRate",
      highestYesRate: "js.stats.rankings.highestYesRate",
      mostResponses: "js.stats.rankings.mostResponses",
      slowestResponses: "js.stats.rankings.slowestResponses",
      highestNoResponseRate: "js.stats.rankings.highestNoResponseRate",
      highestLateRate: "js.stats.rankings.highestLateRate",
      mostNoResponses: "js.stats.rankings.mostNoResponses",
    };
    return t(map[key] ?? key);
  };

  const rankingKeysPositive = ["fastestResponses", "highestResponseRate", "highestYesRate", "mostResponses"];
  const rankingKeysNegative = ["slowestResponses", "highestNoResponseRate", "highestLateRate", "mostNoResponses"];

  const statusLabelFor = (value?: string) => {
    const key = (value ?? "").toLowerCase();
    if (key === "confirmed") return t("js.event.status.confirmed");
    if (key === "cancelled" || key === "canceled") return t("js.event.status.cancelled");
    if (key === "hidden") return t("js.event.status.hidden");
    return t("js.event.status.planned");
  };

  const handleCriticalEventClick = (event: { type: "rehearsal" | "concert"; id: number }) => {
    router.push(getEntityPath(event.type, event.id));
  };

  if (loading && !data) {
    return (
      <main className={PAGE_CONTENT_BASE_CLASS}>
        <div className="flex items-center justify-center min-h-[240px]">
          <Spinner />
        </div>
      </main>
    );
  }

  return (
    <main className={PAGE_CONTENT_BASE_CLASS}>
      <div className="mx-auto max-w-7xl space-y-4 md:space-y-6">
        {error ? (
          <div className="alert alert-error" role="alert">
            <span>{error}</span>
            <button type="button" className="btn btn-sm btn-soft" onClick={() => void onReload()}>
              {t("js.common.retry")}
            </button>
          </div>
        ) : null}

        <section className="bg-base-100 rounded-box border border-base-300 px-4 py-4 md:px-5 md:py-5">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex min-w-0 items-center gap-3">
              <span
                className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-base-200"
                style={statsEntity?.color ? { color: statsEntity.color } : undefined}
              >
                <TablerIconByName name={statsEntity?.icon ?? "chart-bar"} className="h-5 w-5" />
              </span>
              <div className="min-w-0">
                <h1 className="text-lg md:text-xl font-semibold truncate">{t("js.stats.title")}</h1>
                <p className="text-sm text-base-content/70">{t("js.stats.subtitle")}</p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <select
                className="select select-sm select-bordered"
                value={scope}
                onChange={(e) => onScopeChange((e.target.value as "year" | "all") || "year")}
              >
                <option value="year">{t("js.stats.filters.year")}</option>
                <option value="all">{t("js.stats.filters.allTime")}</option>
              </select>
              <select
                className="select select-sm select-bordered"
                value={year}
                onChange={(e) => onYearChange(Number(e.target.value))}
                disabled={scope === "all"}
              >
                {availableYears.map((item) => (
                  <option key={item} value={item}>
                    {item}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </section>

        <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <StatCard
            iconName="alert-triangle"
            iconColor="var(--color-error)"
            title={t("js.stats.cards.criticalEvents")}
            value={String(data?.overview.criticalEvents ?? 0)}
            hint={t("js.stats.cards.criticalEventsHint")}
          />
          <StatCard
            iconName="mail"
            iconColor="var(--color-warning)"
            title={t("js.stats.cards.pendingResponses")}
            value={String(data?.overview.pendingResponses ?? 0)}
            hint={t("js.stats.cards.pendingResponsesHint")}
          />
          <StatCard
            iconName="music"
            iconColor={CHART_COLORS.rehearsal}
            title={t("js.stats.cards.rehearsalRate")}
            value={`${(data?.overview.rehearsalParticipationRate ?? 0).toFixed(1)}%`}
            hint={t("js.stats.cards.rehearsalRateHint")}
          />
          <StatCard
            iconName="mic-vocal"
            iconColor={CHART_COLORS.concert}
            title={t("js.stats.cards.concertRate")}
            value={`${(data?.overview.concertParticipationRate ?? 0).toFixed(1)}%`}
            hint={t("js.stats.cards.concertRateHint")}
          />
          <StatCard
            iconName="check-circle"
            iconColor="var(--color-success)"
            title={t("js.stats.cards.participationRate")}
            value={`${(data?.overview.participationRate ?? 0).toFixed(1)}%`}
            hint={t("js.stats.cards.participationRateHint")}
          />
          <StatCard
            iconName="message-square"
            iconColor={CHART_COLORS.completion}
            title={t("js.stats.cards.responseCompletion")}
            value={`${(data?.overview.responseCompletionRate ?? 0).toFixed(1)}%`}
            hint={t("js.stats.cards.responseCompletionHint", [
              String(data?.overview.responsesTotal ?? 0),
              String(data?.overview.invitationsTotal ?? 0),
            ])}
          />
          <StatCard
            iconName="clock"
            iconColor={CHART_COLORS.rehearsal}
            title={t("js.stats.cards.leadTime")}
            value={leadTimeValue}
            hint={leadTimeHint}
          />
          <StatCard
            iconName="alert-circle"
            iconColor={CHART_COLORS.completion}
            title={t("js.stats.cards.lateResponses")}
            value={lateRateValue}
            hint={lateRateHint}
          />
          <StatCard
            iconName="user-x"
            iconColor={CHART_COLORS.trend}
            title={t("js.stats.cards.noResponses")}
            value={noResponseValue}
            hint={noResponseHint}
          />
        </section>

        <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <StatCard
            iconName="chart-bar"
            iconColor={CHART_COLORS.trend}
            title={t("js.stats.cards.participationStability")}
            value={stabilityValue}
            hint={stabilityHint}
          />
          <StatCard
            iconName="users"
            iconColor={CHART_COLORS.rehearsal}
            title={t("js.stats.cards.activeMembers")}
            value={`${activeMemberRate.toFixed(1)}%`}
            hint={activeMemberHint}
          />
          <StatCard
            iconName="check-square"
            iconColor={CHART_COLORS.completion}
            title={t("js.stats.cards.taskLatency")}
            value={taskLatencyValue}
            hint={taskLatencyHint}
          />
          <StatCard
            iconName="vote"
            iconColor={CHART_COLORS.concert}
            title={t("js.stats.cards.voteParticipation")}
            value={voteParticipationValue}
            hint={voteParticipationHint}
          />
          <StatCard
            iconName="bell"
            iconColor={CHART_COLORS.completion}
            title={t("js.stats.cards.reminderEffectiveness")}
            value={reminderUpliftValue}
            hint={reminderUpliftHint}
          />
        </section>

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.eventsByMonth")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={eventsSeries}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Legend />
                    <Line type="monotone" dataKey="rehearsals" stroke={CHART_COLORS.rehearsal} strokeWidth={2} name={t("js.sidebar.rehearsals")} />
                    <Line type="monotone" dataKey="concerts" stroke={CHART_COLORS.concert} strokeWidth={2} name={t("js.sidebar.concerts")} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.membersPerGroup")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={membersPerGroup}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="name" interval={0} angle={-20} textAnchor="end" height={60} />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="count" radius={[6, 6, 0, 0]} name={t("js.stats.labels.members")}>
                      {membersPerGroup.map((entry) => (
                        <Cell key={`member-group-${entry.name}`} fill={entry.name.toLowerCase().includes("vorstand") ? CHART_COLORS.concert : CHART_COLORS.trend} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.participationTrend")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={participationSeries}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis domain={[0, 100]} />
                    <Tooltip />
                    <Line type="monotone" dataKey="rate" stroke={CHART_COLORS.trend} strokeWidth={2} name={t("js.stats.labels.participationRate")} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.responseCompletionTrend")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={responseSeries}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis domain={[0, 100]} />
                    <Tooltip />
                    <Line
                      type="monotone"
                      dataKey="rate"
                      stroke={CHART_COLORS.completion}
                      strokeWidth={2}
                      name={t("js.stats.labels.responseCompletionRate")}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </section>

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.responseMixTrend")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={responseMixTrend}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Legend />
                    <Area type="monotone" dataKey="yes" stackId="a" stroke={CHART_COLORS.yes} fill={CHART_COLORS.yes} name={t("js.stats.labels.yes")} />
                    <Area type="monotone" dataKey="maybe" stackId="a" stroke={CHART_COLORS.maybe} fill={CHART_COLORS.maybe} name={t("js.stats.labels.maybe")} />
                    <Area type="monotone" dataKey="no" stackId="a" stroke={CHART_COLORS.no} fill={CHART_COLORS.no} name={t("js.stats.labels.no")} />
                    <Area type="monotone" dataKey="pending" stackId="a" stroke={CHART_COLORS.pending} fill={CHART_COLORS.pending} name={t("js.stats.labels.pendingResponses")} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.responseFunnel")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={[
                      { stage: t("js.stats.labels.invited"), value: responseFunnel.invited, color: CHART_COLORS.rehearsal },
                      { stage: t("js.stats.labels.responded"), value: responseFunnel.responded, color: CHART_COLORS.concert },
                      { stage: t("js.stats.labels.confirmed"), value: responseFunnel.confirmed, color: CHART_COLORS.rehearsal },
                    ]}
                    layout="vertical"
                  >
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis type="number" allowDecimals={false} />
                    <YAxis dataKey="stage" type="category" width={120} />
                    <Tooltip />
                    <Bar dataKey="value" radius={[0, 6, 6, 0]} name={t("js.stats.labels.count")}>
                      {[
                        { color: CHART_COLORS.rehearsal },
                        { color: CHART_COLORS.concert },
                        { color: CHART_COLORS.rehearsal },
                      ].map((entry, index) => (
                        <Cell key={`funnel-cell-${index}`} fill={entry.color} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </section>

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.responseLeadTime")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={[
                      { label: "P50", value: leadTime?.medianHours ?? 0 },
                      { label: "P90", value: leadTime?.p90Hours ?? 0 },
                      { label: t("js.stats.labels.average"), value: leadTime?.avgHours ?? 0 },
                    ]}
                  >
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="label" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="value" radius={[6, 6, 0, 0]} fill={CHART_COLORS.rehearsal} name={t("js.stats.labels.hours")} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.activeMembers")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={activeMembersTrend}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis domain={[0, 100]} />
                    <Tooltip />
                    <Line type="monotone" dataKey="rate" stroke={CHART_COLORS.trend} strokeWidth={2} name={t("js.stats.labels.activeRate")} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </section>

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.responseConsistency")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={responseConsistency}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="label" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="count" radius={[6, 6, 0, 0]} fill={CHART_COLORS.completion} name={t("js.stats.labels.members")} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.taskLatency")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={taskLatency?.series ?? []}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Line type="monotone" dataKey="medianHours" stroke={CHART_COLORS.rehearsal} strokeWidth={2} name={t("js.stats.labels.hours")} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </section>

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.voteParticipation")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={voteParticipationTrend}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="month" />
                    <YAxis domain={[0, 100]} />
                    <Tooltip />
                    <Line type="monotone" dataKey="rate" stroke={CHART_COLORS.concert} strokeWidth={2} name={t("js.stats.labels.participationRate")} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.reminderEffectiveness")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={[
                      { label: t("js.stats.labels.before"), value: reminderEffectiveness?.beforeCount ?? 0 },
                      { label: t("js.stats.labels.after"), value: reminderEffectiveness?.afterCount ?? 0 },
                    ]}
                  >
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="label" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="value" radius={[6, 6, 0, 0]} fill={CHART_COLORS.trend} name={t("js.stats.labels.responses")} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </section>

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.charts.instrumentCoverage")}</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={instrumentCoverage}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-base-300)" />
                    <XAxis dataKey="name" interval={0} angle={-20} textAnchor="end" height={60} />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="shortfalls" radius={[6, 6, 0, 0]} fill={CHART_COLORS.completion} name={t("js.stats.labels.shortfalls")} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </section>

        {userRankings ? (
          <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
            <div className="card bg-base-100 border border-base-300">
              <div className="card-body p-4 space-y-3">
                <h2 className="card-title text-base">{t("js.stats.rankings.positiveTitle")}</h2>
                {rankingKeysPositive.map((key) => {
                  const entries = userRankings.positive[key] ?? [];
                  return (
                    <div key={`pos-${key}`} className="space-y-2">
                      <h3 className="text-sm font-semibold text-base-content/80">{rankingMetricLabel(key)}</h3>
                      <div className="overflow-x-auto">
                        <table className="table table-sm">
                          <thead>
                            <tr>
                              <th>{t("js.stats.rankings.name")}</th>
                              <th>{t("js.stats.rankings.instrument")}</th>
                              <th className="text-right">{t("js.stats.rankings.value")}</th>
                            </tr>
                          </thead>
                          <tbody>
                            {entries.map((entry) => (
                              <tr key={`pos-${key}-${entry.userId}`}>
                                <td>{[entry.name, entry.surname].filter(Boolean).join(" ") || "—"}</td>
                                <td>{entry.instrument || "—"}</td>
                                <td className="text-right font-medium">{formatRankingValue(key, entry.value)}</td>
                              </tr>
                            ))}
                            {entries.length === 0 ? (
                              <tr>
                                <td colSpan={3} className="text-base-content/60">
                                  {t("js.common.empty") || "—"}
                                </td>
                              </tr>
                            ) : null}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="card bg-base-100 border border-base-300">
              <div className="card-body p-4 space-y-3">
                <h2 className="card-title text-base">{t("js.stats.rankings.negativeTitle")}</h2>
                {rankingKeysNegative.map((key) => {
                  const entries = userRankings.negative[key] ?? [];
                  return (
                    <div key={`neg-${key}`} className="space-y-2">
                      <h3 className="text-sm font-semibold text-base-content/80">{rankingMetricLabel(key)}</h3>
                      <div className="overflow-x-auto">
                        <table className="table table-sm">
                          <thead>
                            <tr>
                              <th>{t("js.stats.rankings.name")}</th>
                              <th>{t("js.stats.rankings.instrument")}</th>
                              <th className="text-right">{t("js.stats.rankings.value")}</th>
                            </tr>
                          </thead>
                          <tbody>
                            {entries.map((entry) => (
                              <tr key={`neg-${key}-${entry.userId}`}>
                                <td>{[entry.name, entry.surname].filter(Boolean).join(" ") || "—"}</td>
                                <td>{entry.instrument || "—"}</td>
                                <td className="text-right font-medium">{formatRankingValue(key, entry.value)}</td>
                              </tr>
                            ))}
                            {entries.length === 0 ? (
                              <tr>
                                <td colSpan={3} className="text-base-content/60">
                                  {t("js.common.empty") || "—"}
                                </td>
                              </tr>
                            ) : null}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </section>
        ) : null}

        <section className="grid grid-cols-1 xl:grid-cols-2 gap-3 md:gap-4">
          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.top.rehearsalParticipants")}</h2>
              <div className="overflow-x-auto">
                <table className="table table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>{t("js.stats.labels.name")}</th>
                      <th>{t("js.stats.labels.instrument")}</th>
                      <th className="text-right">{t("js.stats.labels.score")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {topRehearsal.map((row) => (
                      <tr key={`reh-${row.rank}-${row.firstName}`}>
                        <td>{row.rank}</td>
                        <td>{row.firstName}</td>
                        <td>{row.instrument || "—"}</td>
                        <td className="text-right">{row.score}</td>
                      </tr>
                    ))}
                    {topRehearsal.length === 0 ? (
                      <tr>
                        <td colSpan={4} className="text-base-content/60">
                          {t("js.common.empty") || "—"}
                        </td>
                      </tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div className="card bg-base-100 border border-base-300">
            <div className="card-body p-4">
              <h2 className="card-title text-base">{t("js.stats.top.voteParticipants")}</h2>
              <div className="overflow-x-auto">
                <table className="table table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>{t("js.stats.labels.name")}</th>
                      <th>{t("js.stats.labels.instrument")}</th>
                      <th className="text-right">{t("js.stats.labels.score")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {topVotes.map((row) => (
                      <tr key={`vote-${row.rank}-${row.firstName}`}>
                        <td>{row.rank}</td>
                        <td>{row.firstName}</td>
                        <td>{row.instrument || "—"}</td>
                        <td className="text-right">{row.score}</td>
                      </tr>
                    ))}
                    {topVotes.length === 0 ? (
                      <tr>
                        <td colSpan={4} className="text-base-content/60">
                          {t("js.common.empty") || "—"}
                        </td>
                      </tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <section className="card bg-base-100 border border-base-300">
          <div className="card-body p-4">
            <h2 className="card-title text-base">{t("js.stats.criticalEvents.title")}</h2>
            <ResponsiveTable
              rows={criticalEvents}
              getRowKey={(event) => `${event.type}-${event.id}`}
              renderMobileRow={(event) => {
                const eventType = event.type === "concert" ? "performance" : "rehearsal";
                const typeConfig = getEventTypeConfig(eventType, t);
                const Icon = getIcon(typeConfig.icon);
                const warningUi = getEscalationWarningUiConfig(event.severity);
                const WarningIcon = getIcon(warningUi.iconName);
                const begin = formatDateTimeShort(event.begin, lang) ?? formatDateShortDisplay(event.begin, lang);
                const deadline = formatDateTimeShort(event.approveUntil, lang) ?? formatDateShortDisplay(event.approveUntil, lang);
                const title = event.type === "concert" ? event.title : "";
                const location = event.locationName?.trim() || "—";
                const href = getEntityPath(event.type, event.id);
                return (
                  <EntityListRow
                    href={href}
                    icon={
                      <span className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}>
                        <Icon className="h-3 w-3" />
                      </span>
                    }
                    primary={begin}
                    badge={
                      <>
                        <span
                          className="inline-flex items-center justify-center rounded-full border px-2 py-0.5"
                          style={warningUi.badgeStyle}
                        >
                          <WarningIcon className={`h-3.5 w-3.5 ${warningUi.iconClassName}`} />
                        </span>
                        {event.status ? (
                          <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(event.status)}>
                            {statusLabelFor(event.status)}
                          </span>
                        ) : null}
                      </>
                    }
                    secondary={
                      <>
                        <span className="flex items-center gap-1">
                          <Clock className="h-3 w-3 opacity-70" />
                          {deadline}
                        </span>
                        {title ? <span className="text-sm">{title}</span> : null}
                        <span className="flex items-start gap-1">
                          <MapPin className="mt-0.5 h-3 w-3 opacity-70 shrink-0" />
                          <span className="min-w-0 break-words whitespace-normal leading-snug">{location}</span>
                        </span>
                        {event.invitedUsers > 0 ? (
                          <span className="w-full">
                            <ParticipationDiagram
                              stats={{
                                yes: event.repliedUsers,
                                pending: event.pendingUsers,
                                total: event.invitedUsers,
                              }}
                            />
                          </span>
                        ) : null}
                      </>
                    }
                  />
                );
              }}
              onRowClick={(event) => handleCriticalEventClick(event)}
              emptyMessage={t("js.stats.criticalEvents.empty")}
            >
              <ResizableTable
                className="w-full text-sm"
                columns={[
                  { id: "begin", width: 220, minWidth: 180 },
                  { id: "status", width: 140, minWidth: 120 },
                  { id: "warning", width: 90, minWidth: 80 },
                  { id: "location", width: 220, minWidth: 160 },
                  { id: "pending", width: 220, minWidth: 180 },
                ]}
              >
                <thead>
                  <tr className="border-b" style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}>
                    <ResizableTh columnId="begin" className="text-left p-3 font-semibold">
                      {t("js.stats.labels.begin")}
                    </ResizableTh>
                    <ResizableTh columnId="status" className="text-left p-3 font-semibold">
                      {t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status"}
                    </ResizableTh>
                    <ResizableTh columnId="warning" className="text-left p-3 font-semibold">
                      {t("mail.escalation.alertBadge") !== "mail.escalation.alertBadge" ? t("mail.escalation.alertBadge") : "Alert"}
                    </ResizableTh>
                    <ResizableTh columnId="location" className="text-left p-3 font-semibold">
                      {t("js.event.location") !== "js.event.location" ? t("js.event.location") : "Location"}
                    </ResizableTh>
                    <ResizableTh columnId="pending" className="text-left p-3 font-semibold">
                      {t("js.stats.labels.pending")}
                    </ResizableTh>
                  </tr>
                </thead>
                <tbody>
                  {criticalEvents.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="p-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                        {t("js.stats.criticalEvents.empty")}
                      </td>
                    </tr>
                  ) : (
                    criticalEvents.map((event) => {
                      const eventType = event.type === "concert" ? "performance" : "rehearsal";
                      const typeConfig = getEventTypeConfig(eventType, t);
                      const Icon = getIcon(typeConfig.icon);
                      const warningUi = getEscalationWarningUiConfig(event.severity);
                      const WarningIcon = getIcon(warningUi.iconName);
                      const begin = formatDateTimeShort(event.begin, lang) ?? formatDateShortDisplay(event.begin, lang);
                      const title = event.type === "concert" ? event.title : "";
                      const location = event.locationName?.trim() || "—";
                      return (
                        <tr
                          key={`${event.type}-${event.id}`}
                          className="cursor-pointer border-b transition-colors hover:bg-[var(--muted)]/30"
                          style={{ borderColor: "var(--border)" }}
                          onClick={() => handleCriticalEventClick(event)}
                        >
                          <td className="p-3">
                            <div className="flex items-center gap-2">
                              <div className={`h-7 w-7 shrink-0 rounded-full flex items-center justify-center text-white ring-2 ring-base-100 shadow-sm ${typeConfig.dotClass}`}>
                                <Icon className="h-3.5 w-3.5" />
                              </div>
                              <div className="min-w-0">
                                <div className="truncate">{begin}</div>
                                {title ? <div className="text-xs text-base-content/60 truncate">{title}</div> : null}
                              </div>
                            </div>
                          </td>
                          <td className="p-3">
                            {event.status ? (
                              <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(event.status)}>
                                {statusLabelFor(event.status)}
                              </span>
                            ) : (
                              "—"
                            )}
                          </td>
                          <td className="p-3">
                            <span
                              className="inline-flex items-center justify-center rounded-full border px-2 py-0.5"
                              style={warningUi.badgeStyle}
                              title={event.severity === "critical" ? t("js.stats.cards.criticalEvents") : t("js.stats.cards.pendingResponses")}
                            >
                              <WarningIcon className={`h-5 w-5 ${warningUi.iconClassName}`} />
                            </span>
                          </td>
                          <td className="p-3">{location}</td>
                          <td className="p-3">
                            {event.invitedUsers > 0 ? (
                              <div className="min-w-[160px]">
                                <ParticipationDiagram
                                  stats={{
                                    yes: event.repliedUsers,
                                    pending: event.pendingUsers,
                                    total: event.invitedUsers,
                                  }}
                                />
                              </div>
                            ) : (
                              "—"
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
        </section>
      </div>
    </main>
  );
}
