"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { PAGE_CONTENT_BASE_CLASS } from "@/lib/layout";
import { useI18n } from "@/contexts/I18nContext";
import { wrappedApi, type WrappedYearData } from "@/lib/wrapped-api";
import { getErrorMessage } from "@/lib/error-utils";
import { Spinner } from "@/components/Spinner";
import { WrappedShareModal } from "@/components/dashboard/WrappedShareModal";
import { TablerIconByName } from "@/components/icons";

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

export default function WrappedModulePage() {
  const { t, lang } = useI18n();
  const currentYear = new Date().getFullYear();
  const years = useMemo(() => yearOptions(currentYear), [currentYear]);
  const [year, setYear] = useState<number>(years[0]);
  const [data, setData] = useState<WrappedYearData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [shareOpen, setShareOpen] = useState(false);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const result = await wrappedApi.getYear(year);
      setData(result);
    } catch (err) {
      setError(getErrorMessage(err, t, "js.wrapped.loadError"));
    } finally {
      setLoading(false);
    }
  }, [year, t]);

  useEffect(() => {
    void loadData();
  }, [loadData]);

  return (
    <main className={PAGE_CONTENT_BASE_CLASS}>
      <div className="mx-auto max-w-5xl space-y-4 md:space-y-6">
        <section className="rounded-box border border-base-300 bg-gradient-to-r from-primary/10 via-base-100 to-accent/10 px-4 py-4 md:px-5 md:py-5">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-3 min-w-0">
              <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-primary shadow-sm">
                <TablerIconByName name="chart-bar" className="h-5 w-5" />
              </span>
              <div className="min-w-0">
                <h1 className="text-lg md:text-xl font-semibold truncate">{t("js.wrapped.title")}</h1>
                <p className="text-sm text-base-content/70">{t("js.wrapped.subtitle")}</p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <select
                className="select select-sm select-bordered"
                value={year}
                onChange={(e) => setYear(Number(e.target.value))}
              >
                {years.map((item) => (
                  <option key={item} value={item}>
                    {item}
                  </option>
                ))}
              </select>
              <button type="button" className="btn btn-sm btn-primary" onClick={() => setShareOpen(true)} disabled={!data}>
                {t("js.wrapped.share.button")}
              </button>
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
              <div className="card bg-base-100 border border-base-300">
                <div className="card-body p-4">
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-base-content/70">{t("js.wrapped.cards.events")}</p>
                    <TablerIconByName name="calendar-days" className="h-4 w-4 text-primary" />
                  </div>
                  <p className="text-3xl font-semibold">{data.personal.events.total}</p>
                  <p className="text-xs text-base-content/70">
                    {t("js.wrapped.cards.eventsBreakdown", [
                      String(data.personal.events.rehearsals),
                      String(data.personal.events.concerts),
                    ])}
                  </p>
                </div>
              </div>
              <div className="card bg-base-100 border border-base-300">
                <div className="card-body p-4">
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-base-content/70">{t("js.wrapped.cards.yesRate")}</p>
                    <TablerIconByName name="check-circle" className="h-4 w-4 text-success" />
                  </div>
                  <p className="text-3xl font-semibold">{data.personal.responses.yesRate.toFixed(1)}%</p>
                  <p className="text-xs text-base-content/70">
                    {t("js.wrapped.cards.responses", [String(data.personal.responses.total)])}
                  </p>
                </div>
              </div>
              <div className="card bg-base-100 border border-base-300">
                <div className="card-body p-4">
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-base-content/70">{t("js.wrapped.cards.eventsBreakdownTitle")}</p>
                    <TablerIconByName name="layout-list" className="h-4 w-4 text-base-content/70" />
                  </div>
                  <div className="mt-2 space-y-1 text-sm">
                    <div className="flex items-center justify-between">
                      <span className="text-base-content/70">{t("js.sidebar.rehearsals")}</span>
                      <span className="font-semibold">{data.personal.events.rehearsals}</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-base-content/70">{t("js.sidebar.concerts")}</span>
                      <span className="font-semibold">{data.personal.events.concerts}</span>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <section className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div className="card bg-base-100 border border-base-300">
                <div className="card-body p-4">
                  <h2 className="card-title text-base">{t("js.wrapped.story.vibeTitle")}</h2>
                  <p>
                    {t("js.wrapped.story.vibeText", [
                      data.profile.firstName,
                      t(data.personal.funFacts.favoriteType === "concert" ? "js.sidebar.concerts" : "js.sidebar.rehearsals"),
                    ])}
                  </p>
                </div>
              </div>
              <div className="card bg-base-100 border border-base-300">
                <div className="card-body p-4">
                  <h2 className="card-title text-base">{t("js.wrapped.story.monthTitle")}</h2>
                  <p>
                    {t("js.wrapped.story.monthText", [formatMonthLabel(data.personal.topMonth || "", lang)])}
                  </p>
                </div>
              </div>
            </section>

            {data.band && data.band.events.total > 0 ? (
              <section className="card bg-base-100 border border-base-300">
                <div className="card-body p-4">
                  <h2 className="card-title text-base">{t("js.wrapped.band.title")}</h2>
                  <p className="text-sm text-base-content/70">
                    {t("js.wrapped.band.subtitle", [data.band.yesRate.toFixed(1)])}
                  </p>
                  <div className="mt-3 overflow-x-auto">
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
    </main>
  );
}

