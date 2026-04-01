"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { ActionButton } from "@/components/ActionButton";
import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { Spinner } from "@/components/Spinner";
import { EntityListRow } from "@/components/EntityListRow";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { useI18n } from "@/contexts/I18nContext";
import { getErrorMessage } from "@/lib/error-utils";
import { rehearsalsApi, type RehearsalSeriesSummary } from "@/lib/rehearsals-api";
import { CalendarDays, Plus } from "@/components/icons";

export default function RehearsalSeriesListPage() {
  const router = useRouter();
  const { t, ready, formatDate } = useI18n();
  const [items, setItems] = useState<RehearsalSeriesSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await rehearsalsApi.listSeries());
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    if (!ready) return;
    void load();
  }, [ready, load]);

  const { activeItems, pastItems } = useMemo(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const active: RehearsalSeriesSummary[] = [];
    const past: RehearsalSeriesSummary[] = [];
    for (const item of items) {
      const last = item.lastSession ? new Date(item.lastSession) : null;
      if (!last || Number.isNaN(last.getTime()) || last >= today) active.push(item);
      else past.push(item);
    }
    active.sort((a, b) => (a.firstSession ?? "").localeCompare(b.firstSession ?? ""));
    past.sort((a, b) => (b.lastSession ?? "").localeCompare(a.lastSession ?? ""));
    return { activeItems: active, pastItems: past };
  }, [items]);

  if (!ready) {
    return <div className="flex items-center justify-center py-12"><Spinner /></div>;
  }

  return (
    <PageContent className="px-1 md:px-4">
      <AppPageHeader
        title={t("js.rehearsals.series.listTitle") !== "js.rehearsals.series.listTitle" ? t("js.rehearsals.series.listTitle") : "Rehearsal series"}
        actions={(
          <ActionButton href="/rehearsals/series/detail?new=1&edit=1">
            <Plus className="h-4 w-4" />
            {t("js.rehearsals.series.createButton") !== "js.rehearsals.series.createButton" ? t("js.rehearsals.series.createButton") : "Create series"}
          </ActionButton>
        )}
      />

      {error && <div className="rounded-lg border border-error bg-error/15 text-error px-4 py-3 text-sm">{error}</div>}

      {loading ? (
        <div className="overflow-hidden rounded-xl border" style={{ borderColor: "var(--border)", background: "var(--card)" }}>
          <div className="flex items-center justify-center py-12"><Spinner /></div>
        </div>
      ) : (
        <div className="space-y-6">
          <SeriesTableSection
            title={t("js.rehearsals.series.activeTitle") !== "js.rehearsals.series.activeTitle"
              ? t("js.rehearsals.series.activeTitle")
              : "Active series"}
            items={activeItems}
            onOpen={(seriesId) => router.push(`/rehearsals/series/detail?seriesId=${seriesId}`)}
            t={t}
            formatDate={formatDate}
          />
          <SeriesTableSection
            title={t("js.common.history") !== "js.common.history" ? t("js.common.history") : "History"}
            items={pastItems}
            onOpen={(seriesId) => router.push(`/rehearsals/series/detail?seriesId=${seriesId}`)}
            t={t}
            formatDate={formatDate}
          />
        </div>
      )}
    </PageContent>
  );
}

function SeriesTableSection({
  title,
  items,
  onOpen,
  t,
  formatDate,
}: {
  title: string;
  items: RehearsalSeriesSummary[];
  onOpen: (id: number) => void;
  t: (key: string) => string;
  formatDate: (date: Date) => string;
}) {
  const localizeDate = (value?: string) => {
    if (!value) return "-";
    const normalized = value.includes("T") ? value.split("T")[0] : value.split(" ")[0];
    const [year, month, day] = normalized.split("-").map((part) => Number(part));
    if (!year || !month || !day) return value;
    return formatDate(new Date(year, month - 1, day));
  };

  return (
    <div>
      <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>{title}</h2>
      <div className="mt-3 overflow-hidden rounded-xl border" style={{ borderColor: "var(--border)", background: "var(--card)" }}>
        <ResponsiveTable<RehearsalSeriesSummary, never>
          rows={items}
          getRowKey={(row) => row.id}
          onRowClick={(row) => onOpen(row.id)}
          renderMobileRow={(row) => (
            <EntityListRow
              icon={<CalendarDays className="h-4 w-4" />}
              primary={<span className="font-semibold">{row.name || `#${row.id}`}</span>}
              secondary={<span>{localizeDate(row.firstSession)} - {localizeDate(row.lastSession)} ({row.rehearsalCount})</span>}
              onClick={() => onOpen(row.id)}
            />
          )}
          emptyMessage={t("js.common.noData") !== "js.common.noData" ? t("js.common.noData") : "No data"}
          sortOptions={[]}
          sortKey={null}
          sortDir="asc"
          onSort={() => undefined}
        >
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b" style={{ borderColor: "var(--border)" }}>
                <th className="p-3 text-left">{t("js.common.name") !== "js.common.name" ? t("js.common.name") : "Name"}</th>
                <th className="p-3 text-left">{t("js.rehearsals.series.firstSession")}</th>
                <th className="p-3 text-left">{t("js.rehearsals.series.lastSession")}</th>
                <th className="p-3 text-left">{t("js.rehearsals.series.sessions") !== "js.rehearsals.series.sessions" ? t("js.rehearsals.series.sessions") : "Sessions"}</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr><td className="p-8 text-center" colSpan={4}>{t("js.common.noData") !== "js.common.noData" ? t("js.common.noData") : "No data"}</td></tr>
              ) : items.map((row) => (
                <tr key={row.id} className="cursor-pointer border-b border-base-300 hover:bg-base-200/70" onClick={() => onOpen(row.id)}>
                  <td className="p-3">{row.name || `#${row.id}`}</td>
                  <td className="p-3">{localizeDate(row.firstSession)}</td>
                  <td className="p-3">{localizeDate(row.lastSession)}</td>
                  <td className="p-3">{row.rehearsalCount}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </ResponsiveTable>
      </div>
    </div>
  );
}
