/**
 * BNote Next Generation - Event Card (shared for dashboard, search)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useState } from "react";
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
import { DashboardVoteWidget } from "@/components/dashboard/DashboardVoteWidget";
import { tasksApi } from "@/lib/tasks-api";
import { notesToPlainText } from "@/lib/editorjs-notes";

export interface VoteOption {
  id: number;
  name?: string;
  odate?: string | null;
}

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
  vote_options?: VoteOption[];
  vote_user_choices?: Record<number, string>;
  vote_is_date?: boolean;
  vote_is_multi?: boolean;
  assignee?: string | null;
  assigneeFullName?: string | null;
  is_complete?: number;
}

interface EventCardProps {
  event: InboxEvent;
  t: (k: string) => string;
  lang: string;
  showParticipation?: boolean;
  isLast?: boolean;
  onParticipationChange?: () => void;
  onTaskComplete?: () => void;
}

function extractLocation(event: InboxEvent): string | null {
  if (event.otype === "V" || event.otype === "T") return null;
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
  onTaskComplete,
}: EventCardProps) {
  const [taskCompleting, setTaskCompleting] = useState(false);
  const eventType = mapOtypeToEventType(event.otype);
  const typeConfig = getEventTypeConfig(eventType, t);
  const tba = t("js.event.tba");
  const dateStr = formatEventDate(event.eventBegin || event.dueDate || event.begin, lang, tba);
  const timeStr = formatEventTime(event.eventBegin || event.dueDate || event.begin, lang, tba);
  const location = extractLocation(event);
  const isVote = event.otype === "V";
  const isTask = event.otype === "T";
  const title = notesToPlainText(event.title ?? "") || t("js.event.event");
  const hideTitleWhenDuplicate = title === typeConfig.label;
  const isClickable = event.otype === "R" || event.otype === "C" || event.otype === "V" || event.otype === "T" || event.otype === "RS" || event.otype === "AP";
  const entityType =
    event.otype === "C"
      ? "concert"
      : event.otype === "V"
        ? "vote"
        : event.otype === "T"
          ? "task"
          : event.otype === "RS"
            ? "reservation"
            : event.otype === "AP"
              ? "appointment"
              : "rehearsal";
  const href = isClickable ? getEntityPath(entityType, event.oid) : "#";
  const hasParticipation =
    showParticipation && (event.otype === "R" || event.otype === "C") && event.oid && event.otype;
  const hasTaskCheckbox =
    showParticipation && isTask && event.oid && (event.is_complete ?? 0) === 0;
  const TASK_COMPLETE_DELAY_MS = 2000;
  const handleTaskCheck = async (e: React.ChangeEvent<HTMLInputElement>) => {
    e.stopPropagation();
    if (taskCompleting) return;
    if (!e.target.checked) return;
    setTaskCompleting(true);
    try {
      await tasksApi.complete(event.oid, true);
      setTimeout(() => onTaskComplete?.(), TASK_COMPLETE_DELAY_MS);
    } catch {
      setTaskCompleting(false);
    }
  };
  const hasVoteWidget =
    showParticipation &&
    isVote &&
    event.oid &&
    (event as InboxEvent).vote_options &&
    (event as InboxEvent).vote_options!.length > 0;
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
        className={`relative z-10 mt-0.5 h-7 w-7 shrink-0 rounded-full ring-2 ring-base-100 shadow-sm flex items-center justify-center text-white ${typeConfig.dotClass}`}
      >
        <DotIcon className="h-3 w-3" />
      </div>
      <div className="flex-1 rounded-box border border-base-300/60 bg-gradient-to-br from-base-200/50 to-transparent p-3 transition-all duration-200 hover:shadow-md hover:border-primary/30 group">
        <div className="flex items-start gap-3 mb-2">
          <div className="flex-1 min-w-0">
            <p className="text-base font-bold leading-tight text-base-content mb-1.5">{dateStr}</p>
            <div className="flex items-center gap-2 mb-1.5">
              {!hideTitleWhenDuplicate && (
                <h3 className="text-sm font-semibold text-base-content group-hover:text-primary transition-colors">
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
            {!isVote && (
              <div className="flex items-center gap-1.5 text-xs text-base-content/80">
                <Clock className="h-3 w-3 text-primary/60" />
                <span>{timeStr}</span>
              </div>
            )}
          </div>
          {(hasParticipation || hasVoteWidget || hasTaskCheckbox) && (
            <div onClick={(e) => e.stopPropagation()} className="shrink-0">
              {hasTaskCheckbox ? (
                <label className="flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    className="checkbox checkbox-primary dashboard-task-checkbox"
                    checked={taskCompleting}
                    onChange={handleTaskCheck}
                    disabled={taskCompleting}
                  />
                </label>
              ) : hasVoteWidget ? (
                <DashboardVoteWidget
                  voteId={event.oid}
                  options={event.vote_options!}
                  userChoices={event.vote_user_choices ?? {}}
                  isDate={event.vote_is_date ?? false}
                  isMulti={event.vote_is_multi ?? false}
                  lang={lang}
                  onVoteChange={onParticipationChange}
                />
              ) : hasParticipation ? (
                <ParticipationWidget
                eventId={event.oid}
                eventType={event.otype}
                onStatusChange={onParticipationChange}
                disabled={isCancelled}
              />
              ) : null}
            </div>
          )}
        </div>
        {(location !== null || isVote) && !hasVoteWidget && (
          <div className="flex items-center text-xs pt-2 border-t border-base-300/50 text-base-content/70">
            {isVote ? (
              <span className="flex items-center gap-1.5">
                <Clock className="h-3 w-3 text-primary/50" />
                {t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "Ends"}: {dateStr}
              </span>
            ) : (
              <span className="flex items-center gap-1.5">
                <MapPin className="h-3 w-3 text-primary/50" />
                <AddressLink value={location!} t={t} renderRawIfNoAddress />
              </span>
            )}
          </div>
        )}
      </div>
    </>
  );

  const mobileContent = (
    <>
      <div className="flex items-start gap-2 mb-2">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-1.5 mb-1 flex-wrap">
            <p className="text-sm font-bold text-base-content leading-tight">{dateStr}</p>
            <span className={`event-badge ${typeConfig.badgeClass} text-xs`}>{typeConfig.label}</span>
          </div>
          <div className="flex items-start gap-1.5 mb-1 flex-wrap">
            {!hideTitleWhenDuplicate && (
              <h3 className="text-sm font-semibold text-base-content group-hover:text-primary transition-colors min-w-0 break-words whitespace-normal leading-snug">
                {title}
              </h3>
            )}
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
          {!isVote && (
            <div className="space-y-0.5 text-sm text-base-content/80">
              <div className="flex items-center gap-1.5">
                <Clock className="h-3.5 w-3.5 text-primary/60" />
                <span>{timeStr}</span>
              </div>
              {location !== null && (
                <div className="flex items-center gap-1.5 text-base-content/70">
                  <MapPin className="h-3.5 w-3.5 text-primary/50" />
                  <span className="min-w-0 break-words whitespace-normal leading-snug">
                    <AddressLink value={location} t={t} renderRawIfNoAddress interactive={false} />
                  </span>
                </div>
              )}
            </div>
          )}
        </div>
        {(hasParticipation || hasVoteWidget || hasTaskCheckbox) && (
          <div onClick={(e) => e.stopPropagation()} className="shrink-0">
            {hasTaskCheckbox ? (
              <label className="flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  className="checkbox checkbox-primary dashboard-task-checkbox"
                  checked={taskCompleting}
                  onChange={handleTaskCheck}
                  disabled={taskCompleting}
                />
              </label>
            ) : hasVoteWidget ? (
              <DashboardVoteWidget
                voteId={event.oid}
                options={event.vote_options!}
                userChoices={event.vote_user_choices ?? {}}
                isDate={event.vote_is_date ?? false}
                isMulti={event.vote_is_multi ?? false}
                lang={lang}
                onVoteChange={onParticipationChange}
              />
            ) : (
              <ParticipationWidget
                eventId={event.oid}
                eventType={event.otype}
                onStatusChange={onParticipationChange}
                disabled={isCancelled}
              />
            )}
          </div>
        )}
      </div>
      {isVote && !hasVoteWidget && (
        <div className="flex items-center text-sm text-base-content/70 pt-1">
          <span className="flex items-center gap-1">
            <Clock className="h-3.5 w-3.5 text-primary/50" />
            {t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "Ends"}: {dateStr}
          </span>
        </div>
      )}
    </>
  );

  const content = (
    <>
      <div className="hidden md:flex relative gap-3 w-full">{desktopContent}</div>
      <div
        className={`md:hidden relative w-full transition-all duration-200 group ${!isLast ? "border-b border-base-300/50 pb-3 mb-3" : ""}`}
      >
        {mobileContent}
      </div>
    </>
  );

  if (isClickable) {
    return (
      <Link
        href={href}
        className="relative flex flex-col md:flex-row md:gap-3 block no-underline w-full text-base-content hover:text-base-content group"
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
