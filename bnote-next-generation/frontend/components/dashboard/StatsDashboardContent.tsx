"use client";

import { useMemo } from "react";
import {
  Bar,
  BarChart,
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
import { getEntityConfig } from "@/lib/entity-config";
import { TablerIconByName } from "@/components/icons";
import { formatDateShortDisplay } from "@/lib/date-time";

const CHART_COLORS = {
  rehearsal: "var(--primary)",
  concert: "var(--accent)",
  trend: "var(--chart-3)",
  completion: "var(--chart-4)",
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
  const statsEntity = getEntityConfig("stats");
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
            <div className="overflow-x-auto">
              <table className="table table-sm">
                <thead>
                  <tr>
                    <th>{t("js.stats.labels.type")}</th>
                    <th>{t("js.stats.labels.event")}</th>
                    <th>{t("js.stats.labels.begin")}</th>
                    <th>{t("js.stats.labels.deadline")}</th>
                    <th className="text-right">{t("js.stats.labels.pending")}</th>
                  </tr>
                </thead>
                <tbody>
                  {criticalEvents.map((event) => (
                    <tr key={`${event.type}-${event.id}`}>
                      <td>
                        <span className={`badge badge-sm ${event.severity === "critical" ? "badge-error" : "badge-warning"}`}>
                          {event.type === "rehearsal" ? t("js.sidebar.rehearsals") : t("js.sidebar.concerts")}
                        </span>
                      </td>
                      <td>{event.title}</td>
                      <td>{formatDateShortDisplay(event.begin, lang)}</td>
                      <td>{formatDateShortDisplay(event.approveUntil, lang)}</td>
                      <td className="text-right font-medium">{event.pendingUsers}</td>
                    </tr>
                  ))}
                  {criticalEvents.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="text-base-content/60">
                        {t("js.stats.criticalEvents.empty")}
                      </td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </main>
  );
}

