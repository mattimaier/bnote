/**
 * BNote Next Generation - Search autocomplete overlay
 * Styled like the search results page: categories with headers, event/task/location rows with icons and pills.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useRef } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useSearch } from "@/contexts/SearchContext";
import { getIcon } from "@/components/icons";
import { formatEventDate, formatEventTime, getEventTypeConfig } from "@/lib/event-utils";
import {
  getEntityTypeForSearchCategory,
  getColor,
  getIconName,
  getPillStyle,
  getDotStyle,
} from "@/lib/entity-config";
import { Clock, MapPin, Calendar, User, Loader2 } from "lucide-react";
import type { SearchResults, SearchEventItem, SearchListItem } from "@/lib/search";

const CATEGORIES: { key: keyof SearchResults; labelKey: string; type: "events" | "list" }[] = [
  { key: "rehearsals", labelKey: "js.search.results.rehearsals", type: "events" },
  { key: "concerts", labelKey: "js.search.results.concerts", type: "events" },
  { key: "users", labelKey: "js.search.results.users", type: "list" },
  { key: "contacts", labelKey: "js.search.results.contacts", type: "list" },
  { key: "tasks", labelKey: "js.search.results.tasks", type: "list" },
  { key: "repertoire", labelKey: "js.search.results.repertoire", type: "list" },
  { key: "locations", labelKey: "js.search.results.locations", type: "list" },
];

const MAX_ITEMS_PER_CATEGORY = 4;

interface SearchAutocompleteOverlayProps {
  anchorRef: React.RefObject<HTMLDivElement | null>;
  onSelect: () => void;
  isDesktop?: boolean;
}

function formatLocation(item: SearchEventItem): string {
  const loc = item.location;
  if (typeof loc === "string") return loc;
  if (loc && typeof loc === "object" && "name" in loc) return (loc as { name?: string }).name ?? "—";
  return item.locationName ?? (item.locationData as { name?: string } | undefined)?.name ?? "—";
}

export function SearchAutocompleteOverlay({ anchorRef, onSelect, isDesktop = true }: SearchAutocompleteOverlayProps) {
  const { t, lang } = useI18n();
  const router = useRouter();
  const { query, results, loading, setOverlayOpen, setQuery } = useSearch();
  const overlayRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      const anchor = anchorRef.current;
      const overlay = overlayRef.current;
      const target = e.target as Node;
      if (anchor?.contains(target) || overlay?.contains(target)) return;
      setOverlayOpen(false);
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [anchorRef, setOverlayOpen]);

  const trimmed = query.trim();
  const showOverlay = trimmed.length >= 2;

  if (!showOverlay) return null;

  const mobileBackdrop = !isDesktop && (
    <div
      className="fixed inset-0 z-[99] bg-black/20 md:bg-black/40 backdrop-blur-sm md:hidden"
      aria-hidden
      onClick={() => setOverlayOpen(false)}
    />
  );

  const handleLinkClick = (href: string) => {
    setOverlayOpen(false);
    setQuery("");
    onSelect();
    router.push(href);
  };

  const linkProps = (href: string) => ({
    href,
    onClick: (e: React.MouseEvent) => {
      e.preventDefault();
      handleLinkClick(href);
    },
  });

  return (
    <>
      {mobileBackdrop}
      <div
        id="search-autocomplete"
        role="listbox"
        ref={overlayRef}
        className={
          isDesktop
            ? "absolute left-0 right-0 top-full z-[100] overflow-hidden rounded-b-lg border-x border-b shadow-2xl max-h-[min(70vh,420px)] overflow-y-auto"
            : "fixed top-16 left-0 right-0 bottom-0 z-[100] border-t overflow-y-auto md:hidden"
        }
        style={{
          background: "var(--card)",
          borderColor: "color-mix(in oklch, var(--border) 60%, transparent)",
          color: "var(--card-foreground)",
        }}
      >
        {/* Blue separator line (like results page) */}
        <div className="h-0.5 w-full shrink-0" style={{ background: "var(--primary)" }} />

        {loading ? (
          <div className="py-3">
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin" style={{ color: "var(--primary)" }} />
            </div>
            <Link
              {...linkProps(`/search?q=${encodeURIComponent(trimmed)}`)}
              className="block mx-4 mt-3 pt-3 border-t text-center text-sm font-semibold"
              style={{ borderColor: "var(--border)", color: "var(--primary)" }}
            >
              {t("js.search.showResults") !== "js.search.showResults" ? t("js.search.showResults") : "Show all results"}
            </Link>
          </div>
        ) : (
          <div className="py-3">
            {results && (
            <>
            {CATEGORIES.map((cat) => {
              const arr = results[cat.key];
              const list = Array.isArray(arr) ? arr.slice(0, MAX_ITEMS_PER_CATEGORY) : [];
              const totalCount = Array.isArray(results[cat.key]) ? (results[cat.key] as unknown[]).length : 0;
              if (list.length === 0 && totalCount === 0) return null;

              const sectionLabel = t(cat.labelKey);
              return (
                <div key={cat.key} className="mb-4 last:mb-0">
                  <h3
                    className="px-4 py-1.5 text-xs font-semibold"
                    style={{ color: "var(--muted-foreground)" }}
                  >
                    {sectionLabel} ({totalCount})
                  </h3>
                  <div className="space-y-0.5">
                    {cat.type === "events" &&
                      (list as SearchEventItem[]).map((item) => {
                        const oid = item.oid ?? item.id;
                        const entityType = cat.key === "concerts" ? "concert" : "rehearsal";
                        const href = oid != null ? `/entity?type=${entityType}&id=${oid}` : "#";
                        const eventType = cat.key === "concerts" ? "performance" : "rehearsal";
                        const typeConfig = getEventTypeConfig(eventType, t);
                        const Icon = getIcon(typeConfig.icon);
                        const dateStr = formatEventDate(item.eventBegin ?? item.begin ?? item.dueDate, lang);
                        const timeStr = formatEventTime(item.eventBegin ?? item.begin ?? item.dueDate, lang);
                        const location = formatLocation(item);
                        const title = item.title ?? (cat.key === "concerts" ? t("js.event.performance") : t("js.event.rehearsal"));

                        return (
                          <Link
                            key={`${cat.key}-${oid}`}
                            {...linkProps(href)}
                            className="flex gap-3 px-4 py-2.5 transition-colors hover:bg-[var(--muted)]/60 text-left"
                            style={{ color: "var(--foreground)" }}
                          >
                            <div
                              className={`mt-0.5 h-6 w-6 shrink-0 rounded-full flex items-center justify-center text-white ${typeConfig.dotClass}`}
                            >
                              <Icon className="h-3 w-3" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <p className="text-sm font-bold leading-tight" style={{ color: "var(--primary)" }}>
                                {dateStr}
                              </p>
                              <div className="flex flex-wrap items-center gap-1.5 mt-0.5">
                                <span className="text-sm">{title}</span>
                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium event-badge ${typeConfig.badgeClass}`}>
                                  {typeConfig.label}
                                </span>
                              </div>
                              <div className="flex flex-wrap items-center gap-3 mt-1 text-xs" style={{ color: "var(--muted-foreground)" }}>
                                <span className="flex items-center gap-1">
                                  <Clock className="h-3 w-3 opacity-70" />
                                  {timeStr}
                                </span>
                                {location && location !== "—" && (
                                  <span className="flex items-center gap-1">
                                    <MapPin className="h-3 w-3 opacity-70" />
                                    {location}
                                  </span>
                                )}
                              </div>
                            </div>
                          </Link>
                        );
                      })}
                    {cat.type === "list" &&
                      (list as SearchListItem[]).map((item) => {
                        const title = item.name ?? item.title ?? `#${item.id}`;
                        const href =
                          cat.key === "users"
                            ? `/users?id=${item.id}`
                            : cat.key === "contacts"
                              ? "/contacts"
                              : cat.key === "tasks"
                                ? "#"
                                : "#";
                        const entityType = getEntityTypeForSearchCategory(cat.key);
                        const entityColor = getColor(entityType);
                        const iconName = getIconName(entityType);
                        const Icon = getIcon(iconName);
                        const pillStyle = getPillStyle(entityColor);
                        const dotStyle = getDotStyle(entityColor);

                        if (cat.key === "tasks") {
                          return (
                            <Link
                              key={`${cat.key}-${item.id}`}
                              {...linkProps(href)}
                              className="flex gap-3 px-4 py-2.5 transition-colors hover:bg-[var(--muted)]/60 text-left"
                              style={{ color: "var(--foreground)" }}
                            >
                              <div
                                className="mt-0.5 h-6 w-6 shrink-0 rounded flex items-center justify-center"
                                style={dotStyle}
                              >
                                <Icon className="h-3.5 w-3.5" />
                              </div>
                              <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-1.5">
                                  <span className="text-sm font-medium">{title}</span>
                                  <span
                                    className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                                    style={pillStyle}
                                  >
                                    {sectionLabel}
                                  </span>
                                </div>
                                <div className="flex flex-wrap items-center gap-3 mt-1 text-xs" style={{ color: "var(--muted-foreground)" }}>
                                  {item.dueAt && (
                                    <span className="flex items-center gap-1">
                                      <Calendar className="h-3 w-3 opacity-70" />
                                      Due: {item.dueAt}
                                    </span>
                                  )}
                                  {item.assignee && (
                                    <span className="flex items-center gap-1">
                                      <User className="h-3 w-3 opacity-70" />
                                      Assigned to: {item.assignee}
                                    </span>
                                  )}
                                </div>
                              </div>
                            </Link>
                          );
                        }

                        if (cat.key === "locations") {
                          const address = [item.street, item.city].filter(Boolean).join(", ") || "—";
                          return (
                            <Link
                              key={`${cat.key}-${item.id}`}
                              {...linkProps(href)}
                              className="flex gap-3 px-4 py-2.5 transition-colors hover:bg-[var(--muted)]/60 text-left"
                              style={{ color: "var(--foreground)" }}
                            >
                              <div
                                className="mt-0.5 h-6 w-6 shrink-0 rounded-full flex items-center justify-center"
                                style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}
                              >
                                <Icon className="h-3.5 w-3.5" />
                              </div>
                              <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-1.5">
                                  <span className="text-sm font-medium">{title}</span>
                                  <span
                                    className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                                    style={pillStyle}
                                  >
                                    {sectionLabel}
                                  </span>
                                </div>
                                {address !== "—" && (
                                  <p className="flex items-center gap-1 mt-1 text-xs" style={{ color: "var(--muted-foreground)" }}>
                                    <MapPin className="h-3 w-3 opacity-70 shrink-0" />
                                    {address}
                                  </p>
                                )}
                              </div>
                            </Link>
                          );
                        }

                        return (
                          <Link
                            key={`${cat.key}-${item.id}`}
                            {...linkProps(href)}
                            className="flex gap-3 px-4 py-2.5 transition-colors hover:bg-[var(--muted)]/60 text-left"
                            style={{ color: "var(--foreground)" }}
                          >
                            <div
                              className="mt-0.5 h-6 w-6 shrink-0 rounded-full flex items-center justify-center"
                              style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}
                            >
                              <Icon className="h-3.5 w-3.5" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <span className="text-sm font-medium">{title}</span>
                              {(item.email || item.phone || item.instrument) && (
                                <p className="text-xs mt-0.5 truncate" style={{ color: "var(--muted-foreground)" }}>
                                  {[item.email, item.phone, item.instrument].filter(Boolean).join(" · ")}
                                </p>
                              )}
                            </div>
                          </Link>
                        );
                      })}
                  </div>
                </div>
              );
            })}
            </>
            )}
            <Link
              {...linkProps(`/search?q=${encodeURIComponent(trimmed)}`)}
              className="block mx-4 mt-3 pt-3 border-t text-center text-sm font-semibold"
              style={{ borderColor: "var(--border)", color: "var(--primary)" }}
            >
              {t("js.search.showResults") !== "js.search.showResults" ? t("js.search.showResults") : "Show all results"}
            </Link>
            {!results && !loading && (
              <div className="py-4 px-4 text-center text-sm" style={{ color: "var(--muted-foreground)" }}>
                {t("js.search.noResults") !== "js.search.noResults" ? t("js.search.noResults") : "No results"}
              </div>
            )}
          </div>
        )}
      </div>
    </>
  );
}
