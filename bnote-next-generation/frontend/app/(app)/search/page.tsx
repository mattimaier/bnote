/**
 * BNote Next Generation - Search Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useSearchParams, useRouter } from "next/navigation";
import { Suspense, useCallback, useEffect, useMemo, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { performSearch, getSearchYears, type SearchResults, type SearchFilters, type SearchEventItem, type SearchListItem } from "@/lib/search";
import { EventCard, type InboxEvent } from "@/components/EventCard";
import { getIcon } from "@/components/icons";
import {
  getEntityTypeForSearchCategory,
  getColor,
  getIconName,
  getPillStyle,
  getDotStyle,
} from "@/lib/entity-config";
import { prefixPath } from "@/lib/path";
import { Search as SearchIcon, MapPin, User, Users, Music, CheckSquare, FileText } from "lucide-react";

const CATEGORIES: { key: keyof SearchResults; labelKey: string; type: "events" | "list" }[] = [
  { key: "rehearsals", labelKey: "js.search.results.rehearsals", type: "events" },
  { key: "concerts", labelKey: "js.search.results.concerts", type: "events" },
  { key: "users", labelKey: "js.search.results.users", type: "list" },
  { key: "contacts", labelKey: "js.search.results.contacts", type: "list" },
  { key: "tasks", labelKey: "js.search.results.tasks", type: "list" },
  { key: "repertoire", labelKey: "js.search.results.repertoire", type: "list" },
  { key: "locations", labelKey: "js.search.results.locations", type: "list" },
];

function toInboxEvent(item: SearchEventItem, category: "rehearsals" | "concerts"): InboxEvent {
  const otype = item.otype ?? (category === "concerts" ? "C" : "R");
  return {
    otype,
    oid: item.oid ?? item.id ?? 0,
    title: item.title,
    eventBegin: item.eventBegin ?? item.begin,
    dueDate: item.dueDate,
    begin: item.begin,
    location: item.location,
    locationName: item.locationName,
    locationData: item.locationData,
  };
}

function SearchContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const q = searchParams.get("q") ?? "";
  const yearParam = searchParams.get("year");
  const monthParam = searchParams.get("month");
  const typeParam = searchParams.get("type");

  const [results, setResults] = useState<SearchResults | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [years, setYears] = useState<number[]>([]);

  const filters: SearchFilters = useMemo(() => {
    const f: SearchFilters = {};
    if (yearParam) f.date_year = parseInt(yearParam, 10);
    if (monthParam) f.date_month = parseInt(monthParam, 10);
    if (typeParam) f.module_type = typeParam;
    return f;
  }, [yearParam, monthParam, typeParam]);

  const search = useCallback(async () => {
    if (!ready || q.trim().length < 2) {
      setResults(null);
      return;
    }
    setLoading(true);
    setError("");
    try {
      const data = await performSearch(q.trim(), filters, 50);
      setResults(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Search failed");
      setResults(null);
    } finally {
      setLoading(false);
    }
  }, [ready, q, filters]);

  useEffect(() => {
    getSearchYears().then(setYears).catch(() => setYears([]));
  }, []);

  useEffect(() => {
    if (!ready) return;
    if (q.trim().length < 2) {
      setResults(null);
      setLoading(false);
      return;
    }
    search();
  }, [ready, q, search]);

  const buildSearchUrl = useCallback(
    (overrides: { q?: string; year?: string; month?: string; type?: string }) => {
      const params = new URLSearchParams();
      const newQ = overrides.q !== undefined ? overrides.q : q;
      if (newQ) params.set("q", newQ);
      const newYear = overrides.year !== undefined ? overrides.year : yearParam;
      if (newYear) params.set("year", newYear);
      const newMonth = overrides.month !== undefined ? overrides.month : monthParam;
      if (newMonth) params.set("month", newMonth);
      const newType = overrides.type !== undefined ? overrides.type : typeParam;
      if (newType) params.set("type", newType);
      return `/search?${params.toString()}`;
    },
    [q, yearParam, monthParam, typeParam]
  );

  const total = results?.total ?? 0;
  const hasFilters = yearParam || monthParam || typeParam;

  return (
    <div className="mx-auto max-w-4xl space-y-4">
      <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
        {t("js.dashboard.searchPlaceholder")}
      </h1>

      <form action={prefixPath("/search/")} method="get" className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
        <input type="hidden" name="year" value={yearParam ?? ""} />
        <input type="hidden" name="month" value={monthParam ?? ""} />
        <input type="hidden" name="type" value={typeParam ?? ""} />
        <div className="relative flex-1">
          <SearchIcon
            className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--muted-foreground)]/60"
            aria-hidden
          />
          <input
            key={q}
            type="search"
            name="q"
            defaultValue={q}
            placeholder={t("js.dashboard.searchPlaceholder")}
            className="w-full rounded-md border border-[var(--border)] bg-[var(--muted)] py-2 pl-9 pr-4 text-sm outline-none focus:ring-1 focus:ring-[var(--primary)]/30"
            style={{ color: "var(--foreground)" }}
            aria-label={t("js.dashboard.searchPlaceholder")}
          />
        </div>
        <button
          type="submit"
          className="shrink-0 rounded-md px-4 py-2 text-sm font-medium text-white"
          style={{ background: "var(--primary)" }}
        >
          {t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search"}
        </button>
      </form>

      {q.length > 0 && q.length < 2 && (
        <p className="text-sm" style={{ color: "var(--muted-foreground)" }}>
          Type at least 2 characters to search.
        </p>
      )}

      {/* Filters */}
      {q.trim().length >= 2 && (
        <div
          className="flex flex-wrap items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--muted)]/20 px-4 py-3"
          style={{ color: "var(--foreground)" }}
        >
          <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
            {t("js.common.filter")}:
          </span>
          {/* Type filter */}
          {(["rehearsal", "performance"] as const).map((filterType) => {
            const isActive = typeParam === filterType;
            return (
              <Link
                key={filterType}
                href={buildSearchUrl({ type: isActive ? undefined : filterType })}
                className={`rounded-full px-3 py-1.5 text-xs font-medium transition-colors ${
                  isActive ? "ring-2 ring-[var(--primary)]" : ""
                }`}
                style={{
                  background: isActive ? "var(--primary)" : "var(--muted)",
                  color: isActive ? "white" : "var(--muted-foreground)",
                }}
              >
                {filterType === "rehearsal" ? t("js.event.rehearsal") : t("js.event.performance")}
              </Link>
            );
          })}
          {/* Year */}
          <select
            value={yearParam ?? ""}
            onChange={(e) => {
              const v = e.target.value;
              router.push(buildSearchUrl({ year: v || undefined }));
            }}
            className="rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1.5 text-xs"
            style={{ color: "var(--foreground)" }}
          >
            <option value="">{t("js.search.filter.year") || "Year"}</option>
            {years.map((y) => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </select>
          {/* Month */}
          <select
            value={monthParam ?? ""}
            onChange={(e) => {
              const v = e.target.value;
              router.push(buildSearchUrl({ month: v || undefined }));
            }}
            className="rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1.5 text-xs"
            style={{ color: "var(--foreground)" }}
          >
            <option value="">{t("js.search.filter.month") || "Month"}</option>
            {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map((m) => (
              <option key={m} value={m}>
                {new Date(2000, m - 1, 1).toLocaleString(lang === "de" ? "de-DE" : "en-US", { month: "long" })}
              </option>
            ))}
          </select>
          {hasFilters && (
            <Link
              href={buildSearchUrl({ year: undefined, month: undefined, type: undefined })}
              className="text-xs font-medium hover:underline"
              style={{ color: "var(--muted-foreground)" }}
            >
              {t("js.search.filter.clearAll") !== "js.search.filter.clearAll" ? t("js.search.filter.clearAll") : "Clear all"}
            </Link>
          )}
        </div>
      )}

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

      {loading && (
        <div className="flex items-center gap-2 py-8 text-sm" style={{ color: "var(--muted-foreground)" }}>
          <div className="h-4 w-4 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
          {t("js.common.loading")}
        </div>
      )}

      {!loading && results && q.trim().length >= 2 && (
        <>
          {total === 0 ? (
            <p className="py-8 text-sm" style={{ color: "var(--muted-foreground)" }}>
              No results for &quot;{q}&quot;
            </p>
          ) : (
            <div className="space-y-6">
              {CATEGORIES.map((cat) => {
                const items = results[cat.key];
                const list = Array.isArray(items) ? items : [];
                const totalCount = results._totals?.[cat.key] ?? list.length;
                if (list.length === 0 && totalCount === 0) return null;

                return (
                  <div
                    key={cat.key}
                    className="rounded-xl border border-[var(--border)] bg-[var(--card)] shadow-sm overflow-hidden"
                    style={{ color: "var(--card-foreground)" }}
                  >
                    <div className="border-b border-[var(--border)] bg-[var(--muted)]/20 px-4 py-3">
                      <h2 className="text-base font-semibold" style={{ color: "var(--foreground)" }}>
                        {t(cat.labelKey)} <span className="font-normal text-[var(--muted-foreground)]">({totalCount})</span>
                      </h2>
                    </div>
                    <div className="px-4 py-4 space-y-2 md:space-y-3">
                      {cat.type === "events" &&
                        list.map((item: SearchEventItem, idx: number) => {
                          const ev = toInboxEvent(item, cat.key as "rehearsals" | "concerts");
                          return (
                            <EventCard
                              key={`${ev.otype}-${ev.oid}-${idx}`}
                              event={ev}
                              t={t}
                              lang={lang}
                              showParticipation={false}
                              isLast={idx === list.length - 1}
                            />
                          );
                        })}
                      {cat.type === "list" &&
                        (list as SearchListItem[]).map((item, idx) => (
                          <SearchListItemRow key={`${cat.key}-${item.id}-${idx}`} category={cat.key} item={item} t={t} categoryLabel={t(cat.labelKey)} />
                        ))}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </>
      )}
    </div>
  );
}

function SearchListItemRow({
  category,
  item,
  t,
  categoryLabel,
}: {
  category: string;
  item: SearchListItem;
  t: (k: string) => string;
  categoryLabel: string;
}) {
  const title = item.name ?? item.title ?? `#${item.id}`;
  const href =
    category === "users"
      ? `/users?id=${item.id}`
      : category === "contacts"
        ? `/contacts?id=${item.id}`
        : "#";
  const entityType = getEntityTypeForSearchCategory(category);
  const entityColor = getColor(entityType);
  const iconName = getIconName(entityType);
  const Icon = getIcon(iconName);
  const pillStyle = getPillStyle(entityColor);
  const dotStyle = getDotStyle(entityColor);

  return (
    <Link
      href={href}
      className="flex items-start gap-3 rounded-lg border border-[var(--border)] p-3 transition-colors hover:border-[var(--primary)]/30 hover:bg-[var(--muted)]/50"
      style={{ color: "var(--foreground)" }}
    >
      <div
        className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
        style={dotStyle}
      >
        <Icon className="h-4 w-4" />
      </div>
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-1.5">
          <p className="font-semibold text-sm">{title}</p>
          <span
            className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
            style={pillStyle}
          >
            {categoryLabel}
          </span>
        </div>
        {(item.email || item.phone || item.mobile) && (
          <p className="text-xs mt-0.5" style={{ color: "var(--muted-foreground)" }}>
            {[item.email, item.phone, item.mobile].filter(Boolean).join(" · ")}
          </p>
        )}
        {item.instrument && (
          <p className="text-xs mt-0.5" style={{ color: "var(--muted-foreground)" }}>
            {item.instrument}
          </p>
        )}
        {item.city && (
          <p className="text-xs mt-0.5" style={{ color: "var(--muted-foreground)" }}>
            {[item.street, item.city].filter(Boolean).join(", ")}
          </p>
        )}
      </div>
    </Link>
  );
}

export default function SearchPage() {
  return (
    <Suspense
      fallback={
        <div className="flex items-center justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
        </div>
      }
    >
      <SearchContent />
    </Suspense>
  );
}
