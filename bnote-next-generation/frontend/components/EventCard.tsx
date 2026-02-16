/**
 * BNote Next Generation - Event Card (shared for dashboard, search)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import Link from "next/link";
import {
  mapOtypeToEventType,
  formatEventDate,
  formatEventTime,
  getEventTypeConfig,
} from "@/lib/event-utils";
import { getEntityPath } from "@/lib/entities/paths";
import { ParticipationWidget } from "./ParticipationWidget";
import { getIcon } from "@/components/icons";
import { MapPin, Clock } from "@/components/icons";
import { AddressLink } from "@/components/AddressLink";
import { getStatusPillStyle } from "@/lib/entity-config";

export interface InboxEvent {
  otype: string;
  oid: number;
  title?: string;
  eventBegin?: string;
  dueDate?: string;
  begin?: string;
  status?: string;
  location?: { name?: string } | string;
  locationName?: string;
  locationData?: { name?: string };
}

interface EventCardProps {
  event: InboxEvent;
  t: (k: string) => string;
  lang: string;
  showParticipation?: boolean;
  isLast?: boolean;
  onParticipationChange?: () => void;
}

function extractLocation(event: InboxEvent): string {
  const loc = event.location;
  if (typeof loc === "string") return loc;
  return (
    loc?.name ??
    event.locationName ??
    (event.locationData as { name?: string } | undefined)?.name ??
    "TBA"
  );
}

export function EventCard({
  event,
  t,
  lang,
  showParticipation = false,
  isLast = false,
  onParticipationChange,
}: EventCardProps) {
  const eventType = mapOtypeToEventType(event.otype);
  const typeConfig = getEventTypeConfig(eventType, t);
  const tba = t("js.event.tba");
  const dateStr = formatEventDate(event.eventBegin || event.dueDate || event.begin, lang, tba);
  const timeStr = formatEventTime(event.eventBegin || event.dueDate || event.begin, lang, tba);
  const location = extractLocation(event);
  const title = event.title || t("js.event.event");
  const hideTitleWhenDuplicate = title === typeConfig.label;
  const isClickable = event.otype === "R" || event.otype === "C";
  const entityType = event.otype === "C" ? "concert" : "rehearsal";
  const href = isClickable ? getEntityPath(entityType, event.oid) : "#";
  const hasParticipation = showParticipation && isClickable && event.oid && event.otype;
  const status = String(event.status ?? "").toLowerCase();
  const isCancelled = status === "cancelled" || status === "canceled" || status === "abgesagt";
  const DotIcon = getIcon(typeConfig.icon);

  /* Desktop: timeline + card. Mobile: compact list item without timeline */
  const desktopContent = (
    <>
      {!isLast && (
        <div className="absolute left-[15px] top-9 h-[calc(100%-12px)] w-0.5 timeline-connector" />
      )}
      <div
        className={`relative z-10 mt-0.5 h-7 w-7 shrink-0 rounded-full ring-2 ring-[var(--background)] shadow-sm flex items-center justify-center text-white ${typeConfig.dotClass}`}
      >
        <DotIcon className="h-3 w-3" />
      </div>
      <div className="flex-1 rounded-lg border border-border/40 bg-gradient-to-br from-muted/20 to-transparent p-3 transition-all duration-200 hover:shadow-md hover:border-primary/30 group">
        <div className="flex items-start gap-3 mb-2">
          <div className="flex-1 min-w-0">
            <p className="text-base font-bold leading-tight text-foreground mb-1.5">{dateStr}</p>
            <div className="flex items-center gap-2 mb-1.5">
              {!hideTitleWhenDuplicate && (
                <h3 className="text-sm font-semibold text-foreground group-hover:text-primary transition-colors">
                  {title}
                </h3>
              )}
              <span className={`event-badge ${typeConfig.badgeClass}`}>{typeConfig.label}</span>
              {isCancelled && (
                <span
                  className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                  style={getStatusPillStyle("cancelled")}
                >
                  {t("js.event.status.cancelled") !== "js.event.status.cancelled"
                    ? t("js.event.status.cancelled")
                    : "Cancelled"}
                </span>
              )}
            </div>
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground/80">
              <Clock className="h-3 w-3 text-primary/60" />
              <span>{timeStr}</span>
            </div>
          </div>
          {hasParticipation && (
            <div onClick={(e) => { e.preventDefault(); e.stopPropagation(); }} className="shrink-0">
              <ParticipationWidget
                eventId={event.oid}
                eventType={event.otype}
                onStatusChange={onParticipationChange}
                disabled={isCancelled}
              />
            </div>
          )}
        </div>
        <div className="flex items-center text-xs pt-2 border-t border-border/30 text-muted-foreground/70">
          <span className="flex items-center gap-1.5">
            <MapPin className="h-3 w-3 text-primary/50" />
            <AddressLink value={location} t={t} renderRawIfNoAddress />
          </span>
        </div>
      </div>
    </>
  );

  const mobileContent = (
    <>
      <div className="flex items-start gap-2 mb-1.5 px-1">
        <div className="flex-1 min-w-0">
          <p className="text-sm font-bold text-foreground leading-tight mb-1">{dateStr}</p>
          <div className="flex items-center gap-1.5 mb-1 flex-wrap">
            {!hideTitleWhenDuplicate && (
              <h3 className="text-xs font-semibold text-foreground group-hover:text-primary transition-colors">
                {title}
              </h3>
            )}
            <span className={`event-badge ${typeConfig.badgeClass} text-[10px]`}>{typeConfig.label}</span>
            {isCancelled && (
              <span
                className="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium border"
                style={getStatusPillStyle("cancelled")}
              >
                {t("js.event.status.cancelled") !== "js.event.status.cancelled"
                  ? t("js.event.status.cancelled")
                  : "Cancelled"}
              </span>
            )}
          </div>
          <div className="flex items-center gap-1 text-[10px] text-muted-foreground/80">
            <Clock className="h-2.5 w-2.5 text-primary/60" />
            <span>{timeStr}</span>
          </div>
        </div>
        {hasParticipation && (
          <div onClick={(e) => { e.preventDefault(); e.stopPropagation(); }} className="shrink-0">
            <ParticipationWidget
              eventId={event.oid}
              eventType={event.otype}
              onStatusChange={onParticipationChange}
              disabled={isCancelled}
            />
          </div>
        )}
      </div>
      <div className="flex items-center text-[10px] text-muted-foreground/70 pt-1 px-1">
        <span className="flex items-center gap-1">
          <MapPin className="h-2.5 w-2.5 text-primary/50" />
          <AddressLink value={location} t={t} renderRawIfNoAddress />
        </span>
      </div>
    </>
  );

  const content = (
    <>
      <div className="hidden md:flex relative gap-3 w-full">{desktopContent}</div>
      <div
        className={`md:hidden relative w-full px-1 transition-all duration-200 group ${!isLast ? "border-b border-border/30 pb-2 mb-2" : ""}`}
      >
        {mobileContent}
      </div>
    </>
  );

  if (isClickable) {
    return (
      <Link
        href={href}
        className="relative flex flex-col md:flex-row md:gap-3 block no-underline w-full text-foreground hover:text-foreground group"
      >
        {content}
      </Link>
    );
  }

  return (
    <div className="relative flex flex-col md:flex-row md:gap-3 w-full">
      {content}
    </div>
  );
}
