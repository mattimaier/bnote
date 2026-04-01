/**
 * BNote Next Generation - Rehearsals List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { rehearsalsApi, type RehearsalListItem } from "@/lib/rehearsals-api";
import { getEntityPath } from "@/lib/entities/paths";
import { type SortDirection } from "@/lib/table-sort";
import { CalendarDays, Plus } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { AppPageHeader } from "@/components/AppPageHeader";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PageContent } from "@/components/PageContent";
import { RehearsalsTable } from "@/components/rehearsals/RehearsalsTable";

export default function RehearsalsPage() {
  const router = useRouter();
  const { t, ready, formatDateTime, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [items, setItems] = useState<RehearsalListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [upcomingSortKey, setUpcomingSortKey] = useState<
    "begin" | "status" | "location" | "notes" | null
  >("begin");
  const [upcomingSortDir, setUpcomingSortDir] = useState<SortDirection>("asc");
  const [pastSortState, setPastSortState] = useState<
    Record<number, { key: "begin" | "status" | "location" | "notes" | null; dir: SortDirection }>
  >({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const list = await rehearsalsApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    if (!ready) return;
    load();
  }, [ready, load]);

  const handleUpcomingSort = (key: "begin" | "status" | "location" | "notes") => {
    if (upcomingSortKey === key) {
      setUpcomingSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setUpcomingSortKey(key);
      setUpcomingSortDir("asc");
    }
  };

  const handlePastSort = (
    year: number,
    key: "begin" | "status" | "location" | "notes"
  ) => {
    setPastSortState((prev) => {
      const current = prev[year] ?? { key: "begin", dir: "desc" as SortDirection };
      if (current.key === key) {
        return { ...prev, [year]: { key, dir: current.dir === "asc" ? "desc" : "asc" } };
      }
      return { ...prev, [year]: { key, dir: "asc" } };
    });
  };

  const filteredItems = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) return items;
    return items.filter((item) => {
      const haystack = [
        item.location_name,
        item.status,
        item.notes,
        item.begin,
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
      return haystack.includes(query);
    });
  }, [items, search]);

  const { upcomingItems, pastItems } = useMemo(() => {
    const now = Date.now();
    const upcoming: RehearsalListItem[] = [];
    const past: RehearsalListItem[] = [];
    filteredItems.forEach((item) => {
      const dateValue = item.end || item.begin;
      const timestamp = dateValue ? new Date(dateValue).getTime() : NaN;
      if (!Number.isNaN(timestamp) && timestamp < now) {
        past.push(item);
      } else {
        upcoming.push(item);
      }
    });
    return { upcomingItems: upcoming, pastItems: past };
  }, [filteredItems]);

  const pastByYear = useMemo(() => {
    const groups = new Map<number, RehearsalListItem[]>();
    pastItems.forEach((item) => {
      const dateValue = item.begin || item.end;
      const year = dateValue ? new Date(dateValue).getFullYear() : 0;
      const key = Number.isNaN(year) ? 0 : year;
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key)?.push(item);
    });
    const years = Array.from(groups.keys())
      .filter((y) => y > 0)
      .sort((a, b) => b - a);
    return { years, groups };
  }, [pastItems]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <PageContent className="px-1 md:px-4">
      <AppPageHeader
        moduleKey="rehearsal"
        title={t("js.sidebar.rehearsals") !== "js.sidebar.rehearsals" ? t("js.sidebar.rehearsals") : "Rehearsals"}
        actions={(
          <>
            <ActionButton variant="outline" href="/rehearsals/series">
              <CalendarDays className="h-4 w-4" />
              {t("js.rehearsals.series.openButton") !== "js.rehearsals.series.openButton"
                ? t("js.rehearsals.series.openButton")
                : "Open series"}
            </ActionButton>
            <ActionButton href={getEntityPath("rehearsal", "new", "edit")}>
              <Plus className="h-4 w-4" />
              {t("js.rehearsals.addRehearsal") !== "js.rehearsals.addRehearsal"
                ? t("js.rehearsals.addRehearsal")
                : "Add Rehearsal"}
            </ActionButton>
          </>
        )}
      />

      {error && (
        <div
          className="rounded-lg border px-4 py-3 text-sm"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {error}
        </div>
      )}
      <div className="flex items-center gap-3 rounded-lg px-3 py-2" style={{ background: "var(--muted)" }}>
        <input
          type="search"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
          className="w-full bg-transparent px-0 py-1 text-sm outline-none"
          style={{ color: "var(--foreground)" }}
        />
      </div>

      <RehearsalsTable
        title={t("js.rehearsals.upcoming") !== "js.rehearsals.upcoming"
          ? t("js.rehearsals.upcoming")
          : "Upcoming rehearsals"}
        items={upcomingItems}
        loading={loading}
        emptyLabel={t("js.rehearsals.noRehearsals") !== "js.rehearsals.noRehearsals"
          ? t("js.rehearsals.noRehearsals")
          : "No rehearsals"}
        formatDateTime={formatDateTime}
        onRowClick={(id) => router.push(getEntityPath("rehearsal", id))}
        emptyText={emptyText}
        sortKey={upcomingSortKey}
        sortDir={upcomingSortDir}
        onSort={handleUpcomingSort}
        defaultSortKey="begin"
        defaultSortDir="asc"
        t={t}
        lang={lang}
      />

      <div>
        <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
          {t("js.common.history") !== "js.common.history" ? t("js.common.history") : "History"}
        </h2>
        {pastByYear.years.length === 0 ? (
          <div className="mt-3 rounded-xl border p-8 text-center text-sm" style={{ color: "var(--muted-foreground)", borderColor: "var(--border)", background: "var(--card)" }}>
            {t("js.rehearsals.noHistory") !== "js.rehearsals.noHistory"
              ? t("js.rehearsals.noHistory")
              : "No past rehearsals"}
          </div>
        ) : (
          <div className="mt-3 space-y-6">
            {pastByYear.years.map((year) => {
              const state = pastSortState[year] ?? { key: "begin" as const, dir: "desc" as SortDirection };
              return (
                <RehearsalsTable
                  key={year}
                  title={`${year}`}
                  items={pastByYear.groups.get(year) ?? []}
                  loading={loading}
                  emptyLabel={t("js.rehearsals.noHistory") !== "js.rehearsals.noHistory"
                    ? t("js.rehearsals.noHistory")
                    : "No past rehearsals"}
                  formatDateTime={formatDateTime}
                  onRowClick={(id) => router.push(getEntityPath("rehearsal", id))}
                  emptyText={emptyText}
                  sortKey={state.key}
                  sortDir={state.dir}
                  onSort={(key) => handlePastSort(year, key)}
                  defaultSortKey="begin"
                  defaultSortDir="desc"
                  t={t}
                  lang={lang}
                />
              );
            })}
          </div>
        )}
      </div>
    </PageContent>
  );
}
