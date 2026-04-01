"use client";

import { type CSSProperties, useCallback, useEffect, useMemo, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { wrappedApi, type WrappedYearData } from "@/lib/wrapped-api";
import { getErrorMessage } from "@/lib/error-utils";
import { Spinner } from "@/components/Spinner";
import { WrappedShareModal } from "@/components/dashboard/WrappedShareModal";
import { TablerIconByName } from "@/components/icons";
import { PageContent } from "@/components/PageContent";
import { AppPageHeader } from "@/components/AppPageHeader";
import { getWrappedThemeStyle } from "@/lib/wrapped-theme";

type BadgeLevel = "gold" | "silver" | "bronze";

function yearOptions(now: number): number[] {
  return [now, now - 1, now - 2];
}

function formatMonthLabel(value: string, lang: string): string {
  if (!value || !value.includes("-")) return "—";
  const [year, month] = value.split("-");
  const date = new Date(Number(year), Number(month) - 1, 1);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat(lang || undefined, { month: "long", year: "numeric" }).format(date);
}

function medalCircleTone(level: BadgeLevel): string {
  if (level === "gold") return "bg-gradient-to-br from-[#f8de83]/35 to-[#d4af37]/20 text-[#b38712] dark:text-[#f0d27a] border-[#d4af37]/45";
  if (level === "silver") return "bg-gradient-to-br from-[#e4e8ef]/45 to-[#b7bcc5]/22 text-[#7f8794] dark:text-[#d6dbe3] border-[#b7bcc5]/45";
  return "bg-gradient-to-br from-[#e1b186]/40 to-[#b87333]/22 text-[#9b5b22] dark:text-[#d8a77a] border-[#b87333]/45";
}

function medalIconName(level: BadgeLevel): string {
  if (level === "gold") return "laurel-wreath-1";
  if (level === "silver") return "laurel-wreath-2";
  return "laurel-wreath-3";
}

function badgeValueLabel(
  badge: WrappedYearData["achievements"]["personalBadges"][number],
  t: (key: string, params?: string[]) => string
): string {
  if (badge.unit === "percent") {
    return t("js.wrapped.achievements.value.percent", [badge.value.toFixed(1)]);
  }
  if (badge.unit === "hours") {
    return t("js.wrapped.achievements.value.hours", [badge.value.toFixed(1)]);
  }
  return t("js.wrapped.achievements.value.count", [String(Math.round(badge.value))]);
}

export default function WrappedModulePage() {
  const { t, lang } = useI18n();
  const currentYear = new Date().getFullYear();
  const years = useMemo(() => yearOptions(currentYear), [currentYear]);
  const [year, setYear] = useState<number>(years[0]);
  const [data, setData] = useState<WrappedYearData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [shareOpen, setShareOpen] = useState(false);
  const revealStyle = (delay: number): CSSProperties => ({ "--wrapped-delay": `${delay}ms` } as CSSProperties);
  const themeStyle = useMemo(() => getWrappedThemeStyle(year), [year]);

  const wrappedTitle = data?.profile.bandName ? `${data.profile.bandName} Wrapped` : t("js.wrapped.title");
  const totalResponses = data?.personal.responses.total ?? 0;
  const yesResponses = data?.personal.responses.yes ?? 0;
  const maybeResponses = data?.personal.responses.maybe ?? 0;
  const yesPct = totalResponses > 0 ? Math.round((yesResponses / totalResponses) * 100) : 0;
  const maybePct = totalResponses > 0 ? Math.round((maybeResponses / totalResponses) * 100) : 0;
  const noPct = totalResponses > 0 ? Math.max(0, 100 - yesPct - maybePct) : 0;
  const responseSplitText = t("js.wrapped.card.responseSplit", [String(yesPct), String(maybePct), String(noPct)]);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      await wrappedApi.canAccess();
      const result = await wrappedApi.getYear(year);
      setData(result);
    } catch (err) {
      const status = (err as { status?: number })?.status;
      if (status === 403) {
        setError(t("js.wrapped.accessDenied"));
      } else {
        setError(getErrorMessage(err, t, "js.wrapped.loadError"));
      }
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [year, t]);

  useEffect(() => {
    void loadData();
  }, [loadData]);

  return (
    <PageContent className="px-1 md:px-4">
      <AppPageHeader
        moduleKey="wrapped"
        title={t("js.sidebar.wrapped") !== "js.sidebar.wrapped" ? t("js.sidebar.wrapped") : "Wrapped"}
        subtitle={t("js.wrapped.subtitle") !== "js.wrapped.subtitle" ? t("js.wrapped.subtitle") : "Your personal year in music"}
        actions={(
          <div className="flex items-center gap-2">
            <select
              className="select select-sm select-bordered"
              value={year}
              onChange={(e) => setYear(Number(e.target.value))}
              aria-label={t("js.wrapped.selectYear")}
            >
              {years.map((item) => (
                <option key={item} value={item}>
                  {item}
                </option>
              ))}
            </select>
            <button
              type="button"
              className="btn btn-sm btn-primary"
              onClick={() => setShareOpen(true)}
              disabled={!data}
            >
              {t("js.wrapped.share.button")}
            </button>
          </div>
        )}
      />

      <div className="mx-auto max-w-5xl space-y-3 md:space-y-5" style={themeStyle}>
        <section
          className="relative isolate overflow-hidden rounded-3xl border border-base-300 wrapped-hero-bg wrapped-reveal px-4 py-5 md:px-7 md:py-6 shadow-sm"
          style={revealStyle(0)}
        >
          <div className="pointer-events-none absolute inset-0 wrapped-hero-shimmer" />
          <div className="pointer-events-none absolute -top-24 right-6 h-44 w-44 rounded-full wrapped-spotlight" />
          <div className="pointer-events-none absolute top-8 -left-12 h-32 w-32 rounded-full wrapped-spotlight-warm" />
          <div className="pointer-events-none absolute -bottom-10 right-24 h-28 w-28 rounded-full wrapped-spotlight-warm opacity-70" />
          <div className="relative flex items-center gap-3 min-w-0">
            <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/85 text-primary shadow-sm ring-1 ring-white/60">
              <TablerIconByName name="confetti" className="h-6 w-6" />
            </span>
            <div className="min-w-0">
              <h1 className="wrapped-display text-xl md:text-3xl font-semibold truncate">{wrappedTitle}</h1>
              <p className="text-xs md:text-sm text-base-content/70">{t("js.wrapped.heroYear", [String(year)])}</p>
            </div>
          </div>
        </section>

        {loading ? (
          <div className="flex items-center justify-center min-h-[220px]">
            <Spinner />
          </div>
        ) : null}

        {error ? <div className="alert alert-error">{error}</div> : null}

        {!loading && data ? (
          <>
            <section className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div className="card bg-base-100 border border-base-300 md:col-span-2 wrapped-reveal" style={revealStyle(80)}>
                <div className="card-body p-4 md:p-5">
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-base-content/70">{t("js.wrapped.cards.events")}</p>
                    <TablerIconByName name="calendar-days" className="h-5 w-5 text-primary" />
                  </div>
                  <p className="wrapped-display mt-1 md:mt-2 text-4xl md:text-5xl font-semibold">{data.personal.events.total}</p>
                  <p className="text-xs text-base-content/70">
                    {t("js.wrapped.cards.eventsBreakdown", [
                      String(data.personal.events.rehearsals),
                      String(data.personal.events.concerts),
                    ])}
                  </p>
                </div>
              </div>
              <div className="card bg-base-100 border border-base-300 wrapped-reveal" style={revealStyle(140)}>
                <div className="card-body p-4 md:p-5">
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-base-content/70">{t("js.wrapped.cards.yesRate")}</p>
                    <TablerIconByName name="check-circle" className="h-5 w-5 text-success" />
                  </div>
                  <p className="wrapped-display mt-1 md:mt-2 text-3xl md:text-4xl font-semibold">
                    {data.personal.responses.yesRate.toFixed(1)}%
                  </p>
                  <p className="text-xs text-base-content/70">
                    {t("js.wrapped.cards.responses", [String(data.personal.responses.total)])}
                  </p>
                </div>
              </div>
              <div className="card bg-base-100 border border-base-300 wrapped-reveal" style={revealStyle(200)}>
                <div className="card-body p-4 md:p-5">
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-base-content/70">{t("js.wrapped.cards.eventsBreakdownTitle")}</p>
                    <TablerIconByName name="layout-list" className="h-5 w-5 text-base-content/70" />
                  </div>
                  <div className="mt-3 space-y-2 text-sm">
                    <div className="flex items-center justify-between rounded-full px-3 py-1.5 wrapped-chip-primary">
                      <span className="text-base-content/80">{t("js.sidebar.rehearsals")}</span>
                      <span className="font-semibold text-base-content">{data.personal.events.rehearsals}</span>
                    </div>
                    <div className="flex items-center justify-between rounded-full px-3 py-1.5 wrapped-chip-accent">
                      <span className="text-base-content/80">{t("js.sidebar.concerts")}</span>
                      <span className="font-semibold text-base-content">{data.personal.events.concerts}</span>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <section className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div className="card bg-base-100 border border-base-300 wrapped-reveal" style={revealStyle(260)}>
                <div className="card-body p-4 md:p-5">
                  <div className="flex items-center gap-3">
                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                      <TablerIconByName name="music" className="h-5 w-5" />
                    </span>
                    <h2 className="card-title text-base">{t("js.wrapped.story.vibeTitle")}</h2>
                  </div>
                  <p className="mt-3 border-l-4 border-primary/40 pl-3 text-sm md:text-base text-base-content/80">
                    {t("js.wrapped.story.vibeText", [
                      data.profile.firstName,
                      t(data.personal.funFacts.favoriteType === "concert" ? "js.sidebar.concerts" : "js.sidebar.rehearsals"),
                    ])}
                  </p>
                  <p className="mt-3 rounded-full px-3 py-1.5 text-xs font-semibold wrapped-chip-primary">{responseSplitText}</p>
                </div>
              </div>
              <div className="card bg-base-100 border border-base-300 wrapped-reveal" style={revealStyle(320)}>
                <div className="card-body p-4 md:p-5">
                  <div className="flex items-center gap-3">
                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl wrapped-accent-bg wrapped-accent-text">
                      <TablerIconByName name="calendar" className="h-5 w-5" />
                    </span>
                    <h2 className="card-title text-base">{t("js.wrapped.story.monthTitle")}</h2>
                  </div>
                  <p className="mt-3 border-l-4 wrapped-accent-border pl-3 text-sm md:text-base text-base-content/80">
                    {t("js.wrapped.story.monthText", [formatMonthLabel(data.personal.topMonth || "", lang)])}
                  </p>
                </div>
              </div>
            </section>

            <section className="rounded-3xl border border-base-300 bg-base-200/40 wrapped-reveal" style={revealStyle(360)}>
              <div className="p-4 md:p-5 space-y-3">
                <div className="flex items-center justify-between gap-2">
                  <h2 className="card-title text-base">{t("js.wrapped.achievements.title")}</h2>
                  <span className="text-xs text-base-content/70">{t("js.wrapped.achievements.subtitle")}</span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {data.achievements.personalBadges.map((badge) => (
                    <div key={badge.id} className="rounded-2xl border border-base-300 bg-base-100 p-3">
                      <div className="flex items-center justify-between gap-2">
                        <p className="text-sm font-semibold text-base-content truncate">
                          {t(`js.wrapped.achievements.badge.${badge.id}.title`)}
                        </p>
                        <span className="inline-flex items-center" aria-label={t(`js.wrapped.achievements.level.${badge.level}`)}>
                          <span
                            className={`inline-flex h-10 w-10 items-center justify-center rounded-full border shadow-sm ${medalCircleTone(
                              badge.level
                            )}`}
                          >
                            <TablerIconByName name={medalIconName(badge.level)} className="h-6 w-6" />
                          </span>
                        </span>
                      </div>
                      <p className="mt-2 text-xs text-base-content/70">
                        {t(`js.wrapped.achievements.badge.${badge.id}.desc`)}
                      </p>
                      <p className="mt-3 wrapped-display text-2xl font-semibold">{badgeValueLabel(badge, t)}</p>
                    </div>
                  ))}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div className="rounded-2xl border border-base-300 bg-base-100 p-3">
                    <div className="flex items-center justify-between gap-2">
                      <h3 className="font-semibold text-sm">{t("js.wrapped.achievements.band.topTitle")}</h3>
                      <span className="text-xs text-base-content/70">
                        {t("js.wrapped.achievements.band.minEvents", [String(data.achievements.bandLeaderboard.minEvents)])}
                      </span>
                    </div>
                    <div className="mt-2 space-y-2">
                      {data.achievements.bandLeaderboard.topAttendance.map((row, idx) => (
                        <div key={`${row.firstName}-${row.surname}-${idx}`} className="rounded-xl border border-base-300 px-3 py-2 text-sm">
                          <div className="flex items-center justify-between gap-2">
                            <span className="font-medium truncate">{idx + 1}. {`${row.firstName} ${row.surname}`.trim()}</span>
                            <span className="font-semibold">{row.attendanceRate.toFixed(1)}%</span>
                          </div>
                          <p className="text-xs text-base-content/70 mt-1">
                            {t("js.wrapped.achievements.band.events", [String(row.eventCount), String(row.attendanceCount)])}
                          </p>
                        </div>
                      ))}
                      {data.achievements.bandLeaderboard.topAttendance.length === 0 ? (
                        <p className="text-sm text-base-content/70">{t("js.wrapped.achievements.band.empty")}</p>
                      ) : null}
                    </div>
                  </div>

                  <div className="rounded-2xl border border-base-300 bg-base-100 p-3">
                    <div className="flex items-center justify-between gap-2">
                      <h3 className="font-semibold text-sm">{t("js.wrapped.achievements.band.lowTitle")}</h3>
                      <span className="text-xs text-base-content/70">
                        {t("js.wrapped.achievements.band.minEvents", [String(data.achievements.bandLeaderboard.minEvents)])}
                      </span>
                    </div>
                    <div className="mt-2 space-y-2">
                      {data.achievements.bandLeaderboard.lowestAttendance.map((row, idx) => (
                        <div key={`${row.firstName}-${row.surname}-low-${idx}`} className="rounded-xl border border-base-300 px-3 py-2 text-sm">
                          <div className="flex items-center justify-between gap-2">
                            <span className="font-medium truncate">{idx + 1}. {`${row.firstName} ${row.surname}`.trim()}</span>
                            <span className="font-semibold">{row.attendanceRate.toFixed(1)}%</span>
                          </div>
                          <p className="text-xs text-base-content/70 mt-1">
                            {t("js.wrapped.achievements.band.events", [String(row.eventCount), String(row.attendanceCount)])}
                          </p>
                        </div>
                      ))}
                      {data.achievements.bandLeaderboard.lowestAttendance.length === 0 ? (
                        <p className="text-sm text-base-content/70">{t("js.wrapped.achievements.band.empty")}</p>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>
            </section>

            {data.band && data.band.events.total > 0 ? (
              <section className="rounded-3xl border border-base-300 bg-base-200/40 wrapped-reveal" style={revealStyle(380)}>
                <div className="p-4 md:p-5">
                  <div className="flex items-center justify-between gap-2">
                    <h2 className="card-title text-base">{t("js.wrapped.band.title")}</h2>
                    <span className="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-base-content/70">
                      {t("js.wrapped.band.subtitle", [data.band.yesRate.toFixed(1)])}
                    </span>
                  </div>

                  <div className="mt-3 md:hidden space-y-2">
                    {data.band.topResponders.map((row, idx) => (
                      <div key={`${row.firstName}-${idx}`} className="rounded-xl border border-base-300 bg-base-100 p-3">
                        <div className="flex items-center justify-between gap-2">
                          <span className="font-medium">{idx + 1}. {row.firstName}</span>
                          <span className="badge badge-soft">{row.score}</span>
                        </div>
                      </div>
                    ))}
                  </div>

                  <div className="mt-3 hidden md:block overflow-x-auto rounded-2xl bg-white">
                    <table className="table table-sm">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>{t("js.wrapped.band.firstName")}</th>
                          <th className="text-right">{t("js.wrapped.band.score")}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {data.band.topResponders.map((row, idx) => (
                          <tr key={`${row.firstName}-${idx}`}>
                            <td>{idx + 1}</td>
                            <td>{row.firstName}</td>
                            <td className="text-right">{row.score}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              </section>
            ) : null}
          </>
        ) : null}
      </div>

      <WrappedShareModal open={shareOpen} onClose={() => setShareOpen(false)} data={data} t={t} />
    </PageContent>
  );
}
