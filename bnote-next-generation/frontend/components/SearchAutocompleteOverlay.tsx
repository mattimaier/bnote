/**
 * BNote Next Generation - Search autocomplete overlay
 * Styled like the search results page: categories with headers, event/task/location rows with icons and pills.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useSearch } from "@/contexts/SearchContext";
import { getIcon } from "@/components/icons";
import { EntityListRow } from "@/components/EntityListRow";
import { Avatar } from "@/components/Avatar";
import { AddressLink } from "@/components/AddressLink";
import { formatEventDate, formatEventTime, getEventTypeConfig } from "@/lib/event-utils";
import { getEntityTypeForSearchCategory, getColor, getIconName, getPillStyle, getDotStyle } from "@/lib/entity-config";
import { getEntityPath } from "@/lib/entities/paths";
import { Clock, MapPin, Calendar, User, Loader2 } from "@/components/icons";
import type { SearchResults, SearchEventItem, SearchListItem } from "@/lib/search";
import { notesToPlainText } from "@/lib/editorjs-notes";

const CATEGORIES: { key: keyof SearchResults; labelKey: string; type: "events" | "list" }[] = [
  { key: "rehearsals", labelKey: "js.search.results.rehearsals", type: "events" },
  { key: "concerts", labelKey: "js.search.results.concerts", type: "events" },
  { key: "users", labelKey: "js.search.results.users", type: "list" },
  { key: "contacts", labelKey: "js.search.results.contacts", type: "list" },
  { key: "tasks", labelKey: "js.search.results.tasks", type: "list" },
  { key: "repertoire", labelKey: "js.search.results.repertoire", type: "list" },
  { key: "locations", labelKey: "js.search.results.locations", type: "list" },
  { key: "equipment", labelKey: "js.search.results.equipment", type: "list" },
  { key: "outfits", labelKey: "js.search.results.outfits", type: "list" },
  { key: "songs", labelKey: "js.search.results.songs", type: "list" },
  { key: "votes", labelKey: "js.search.results.votes", type: "list" },
];

const MAX_ITEMS_PER_CATEGORY = 4;

interface SearchAutocompleteOverlayProps {
  anchorRef: React.RefObject<HTMLDivElement | null>;
  onSelect: () => void;
  isDesktop?: boolean;
}

function formatLocation(item: SearchEventItem, emptyText: string): string {
  const loc = item.location;
  if (typeof loc === "string") return loc;
  if (loc && typeof loc === "object" && "name" in loc) return (loc as { name?: string }).name ?? emptyText;
  return item.locationName ?? (item.locationData as { name?: string } | undefined)?.name ?? emptyText;
}

export function SearchAutocompleteOverlay({ anchorRef, onSelect, isDesktop = true }: SearchAutocompleteOverlayProps) {
  const { t, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
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
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  if (!showOverlay) return null;

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

  const overlayContent = (
    <>
      {!isDesktop && (
        <div
          className="fixed top-16 left-0 right-0 bottom-0 z-[9998] bg-black/25"
          aria-hidden
          onClick={() => setOverlayOpen(false)}
        />
      )}
      <div
        id="search-autocomplete"
        role="listbox"
        ref={overlayRef}
        className={
          isDesktop
            ? "dropdown-menu absolute left-0 right-0 top-full z-[100] overflow-hidden max-h-[min(70vh,420px)] overflow-y-auto opacity-100 rounded-box border border-base-300 bg-base-100 shadow-xl"
            : "fixed top-16 left-0 right-0 bottom-0 z-[9999] overflow-y-auto md:hidden bg-base-100 border-t border-base-300 shadow-2xl"
        }
      >
        {/* Blue separator line (like results page) */}
        <div className="h-0.5 w-full shrink-0 bg-primary" />

        {loading ? (
          <div className="py-3">
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin text-primary" />
            </div>
            <Link
              {...linkProps(`/search?q=${encodeURIComponent(trimmed)}`)}
              className="block mx-4 mt-3 pt-3 border-t border-base-300 text-center text-sm font-semibold text-primary"
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
                      <h3 className="px-4 py-1.5 text-xs font-semibold text-base-content/60">
                        {sectionLabel} ({totalCount})
                      </h3>
                      <div className="space-y-0.5">
                        {cat.type === "events" &&
                          (list as SearchEventItem[]).map((item) => {
                            const oid = item.oid ?? item.id;
                            const entityType = cat.key === "concerts" ? "concert" : "rehearsal";
                            const href = oid != null ? getEntityPath(entityType, oid) : "#";
                            const eventType = cat.key === "concerts" ? "performance" : "rehearsal";
                            const typeConfig = getEventTypeConfig(eventType, t);
                            const Icon = getIcon(typeConfig.icon);
                            const tba = t("js.event.tba");
                            const dateStr = formatEventDate(item.eventBegin ?? item.begin ?? item.dueDate, lang, tba);
                            const timeStr = formatEventTime(item.eventBegin ?? item.begin ?? item.dueDate, lang, tba);
                            const location = formatLocation(item, emptyText);
                            const title =
                              notesToPlainText(item.title ?? "") ||
                              (cat.key === "concerts" ? t("js.event.performance") : t("js.event.rehearsal"));

                            return (
                              <div key={`${cat.key}-${oid}`}>
                                <EntityListRow
                                  icon={
                                    <span
                                      className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}
                                    >
                                      <Icon className="h-3 w-3" />
                                    </span>
                                  }
                                  primary={<span className="font-bold leading-tight text-primary">{dateStr}</span>}
                                  badge={<span className="text-sm">{title}</span>}
                                  secondary={
                                    <>
                                      <span className="flex items-center gap-1">
                                        <Clock className="h-3 w-3 opacity-70" />
                                        {timeStr}
                                      </span>
                                      {location && location !== emptyText && (
                                        <span className="flex items-start gap-1">
                                          <MapPin className="mt-0.5 h-3 w-3 opacity-70 shrink-0" />
                                          <AddressLink
                                            value={location}
                                            t={t}
                                            renderRawIfNoAddress
                                            interactive={false}
                                          />
                                        </span>
                                      )}
                                    </>
                                  }
                                  href={href}
                                  onClick={(e) => {
                                    e.preventDefault();
                                    handleLinkClick(href);
                                  }}
                                />
                              </div>
                            );
                          })}
                        {cat.type === "list" &&
                          (list as SearchListItem[]).map((item) => {
                            const title = item.name ?? (notesToPlainText(item.title ?? "") || `#${item.id}`);
                            const href =
                              cat.key === "users"
                                ? getEntityPath("user", item.id)
                                : cat.key === "contacts"
                                  ? getEntityPath("contact", item.id)
                                  : cat.key === "locations"
                                    ? getEntityPath("location", item.id)
                                    : cat.key === "equipment"
                                      ? getEntityPath("equipment", item.id)
                                      : cat.key === "outfits"
                                        ? getEntityPath("outfit", item.id)
                                        : cat.key === "songs"
                                          ? getEntityPath("song", item.id)
                                          : cat.key === "votes"
                                            ? getEntityPath("vote", item.id)
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
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={
                                      <span
                                        className="rounded flex items-center justify-center w-6 h-6"
                                        style={dotStyle}
                                      >
                                        <Icon className="h-3.5 w-3.5" />
                                      </span>
                                    }
                                    primary={title}
                                    secondary={
                                      item.dueAt || item.assignee ? (
                                        <>
                                          {item.dueAt && (
                                            <span className="flex items-center gap-1">
                                              <Calendar className="h-3 w-3 opacity-70" />
                                              Due: {item.dueAt}
                                            </span>
                                          )}
                                          {(item.assigneeName ?? item.assignee) && (
                                            <span className="flex items-center gap-1">
                                              <User className="h-3 w-3 opacity-70" />
                                              Assigned to: {item.assigneeName ?? item.assignee}
                                            </span>
                                          )}
                                        </>
                                      ) : undefined
                                    }
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }

                            if (cat.key === "locations") {
                              const address = [item.street, item.city].filter(Boolean).join(", ");
                              return (
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={
                                      <span
                                        className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                                        style={{
                                          ...dotStyle,
                                          background: pillStyle.backgroundColor,
                                          color: pillStyle.color,
                                        }}
                                      >
                                        <Icon className="h-3.5 w-3.5" />
                                      </span>
                                    }
                                    primary={title}
                                    secondary={
                                      address ? (
                                        <span className="flex items-start gap-1">
                                          <MapPin className="mt-0.5 h-3 w-3 opacity-70 shrink-0" />
                                          <AddressLink value={address} t={t} renderRawIfNoAddress interactive={false} />
                                        </span>
                                      ) : undefined
                                    }
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }

                            if (cat.key === "equipment") {
                              return (
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={
                                      <span
                                        className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                                        style={{
                                          ...dotStyle,
                                          background: pillStyle.backgroundColor,
                                          color: pillStyle.color,
                                        }}
                                      >
                                        <Icon className="h-3.5 w-3.5" />
                                      </span>
                                    }
                                    primary={title}
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }

                            if (cat.key === "outfits") {
                              return (
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={
                                      <span
                                        className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                                        style={{
                                          ...dotStyle,
                                          background: pillStyle.backgroundColor,
                                          color: pillStyle.color,
                                        }}
                                      >
                                        <Icon className="h-3.5 w-3.5" />
                                      </span>
                                    }
                                    primary={title}
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }

                            if (cat.key === "songs") {
                              return (
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={
                                      <span
                                        className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                                        style={{
                                          ...dotStyle,
                                          background: pillStyle.backgroundColor,
                                          color: pillStyle.color,
                                        }}
                                      >
                                        <Icon className="h-3.5 w-3.5" />
                                      </span>
                                    }
                                    primary={title}
                                    secondary={item.composer ? <span>{item.composer}</span> : undefined}
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }

                            if (cat.key === "votes") {
                              return (
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={
                                      <span
                                        className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                                        style={{
                                          ...dotStyle,
                                          background: pillStyle.backgroundColor,
                                          color: pillStyle.color,
                                        }}
                                      >
                                        <Icon className="h-3.5 w-3.5" />
                                      </span>
                                    }
                                    primary={title}
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }

                            if (cat.key === "users" || cat.key === "contacts") {
                              return (
                                <div key={`${cat.key}-${item.id}`}>
                                  <EntityListRow
                                    icon={<Avatar email={item.email} name={title} size={24} variant="soft" />}
                                    primary={title}
                                    secondary={item.instrument ? <span>{item.instrument}</span> : undefined}
                                    href={href}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleLinkClick(href);
                                    }}
                                  />
                                </div>
                              );
                            }
                            return (
                              <div key={`${cat.key}-${item.id}`}>
                                <EntityListRow
                                  icon={
                                    <span
                                      className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                                      style={{
                                        ...dotStyle,
                                        background: pillStyle.backgroundColor,
                                        color: pillStyle.color,
                                      }}
                                    >
                                      <Icon className="h-3.5 w-3.5" />
                                    </span>
                                  }
                                  primary={title}
                                  secondary={
                                    item.email || item.phone || item.instrument ? (
                                      <span className="truncate">
                                        {[item.email, item.phone, item.instrument].filter(Boolean).join(" · ")}
                                      </span>
                                    ) : undefined
                                  }
                                  href={href}
                                  onClick={(e) => {
                                    e.preventDefault();
                                    handleLinkClick(href);
                                  }}
                                />
                              </div>
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
              className="block mx-4 mt-3 pt-3 border-t border-base-300 text-center text-sm font-semibold text-primary"
            >
              {t("js.search.showResults") !== "js.search.showResults" ? t("js.search.showResults") : "Show all results"}
            </Link>
            {!results && !loading && (
              <div className="py-4 px-4 text-center text-sm text-base-content/60">
                {t("js.search.noResults") !== "js.search.noResults" ? t("js.search.noResults") : "No results"}
              </div>
            )}
          </div>
        )}
      </div>
    </>
  );

  if (!isDesktop && mounted && typeof document !== "undefined") {
    return createPortal(overlayContent, document.body);
  }
  return overlayContent;
}
