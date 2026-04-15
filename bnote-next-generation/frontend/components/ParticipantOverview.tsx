/**
 * BNote Next Generation - Participant Overview (grouped by instrument/category)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { ParticipationDiagram, type ParticipationStats } from "@/components/ParticipationDiagram";
import { Check, X, HelpCircle, Clock } from "@/components/icons";
import { Avatar } from "@/components/Avatar";

export interface ParticipantItem {
  id: number;
  userId?: number;
  name: string;
  email?: string | null;
  participate: number | null; // 1=yes, 2=maybe, 0=no, null=pending
  reason?: string | null;
}

export interface InstrumentGroup {
  instrument: {
    id: number;
    name: string;
    minimumRequired?: number;
    category?: { id: number; name: string };
    section?: { id?: string | number; name?: string };
  };
  participants: ParticipantItem[];
  stats?: { yes: number; maybe: number; no: number; pending: number };
}

/** When provided, participant names are rendered as links to the entity (contact or user). */
export type GetEntityHref = (entityType: "contact" | "user", id: number) => string | null;

interface ParticipantOverviewProps {
  participantsByInstrument: InstrumentGroup[] | null | undefined;
  /** Optional: when set, participant names link to entity detail when user has view rights */
  getEntityHref?: GetEntityHref | null;
}

function getGroupName(group: InstrumentGroup, mode: "category" | "instrument" | "section"): string {
  if (mode === "section" && group.instrument.section?.name) return group.instrument.section.name;
  if (mode === "category" && group.instrument.category?.name) return group.instrument.category.name;
  return group.instrument.name;
}

function groupByCategory(groups: InstrumentGroup[]): InstrumentGroup[] {
  const byCategory = new Map<string, InstrumentGroup>();
  for (const g of groups) {
    const catName = g.instrument.category?.name ?? "Uncategorized";
    const existing = byCategory.get(catName);
    if (!existing) {
      byCategory.set(catName, {
        instrument: {
          id: 0,
          name: catName,
          minimumRequired: Math.max(0, g.instrument.minimumRequired ?? 0),
          category: g.instrument.category ?? { id: 0, name: catName },
        },
        participants: [...g.participants],
        stats: g.stats ? { ...g.stats } : { yes: 0, maybe: 0, no: 0, pending: 0 },
      });
    } else {
      existing.participants.push(...g.participants);
      existing.instrument.minimumRequired =
        Math.max(0, existing.instrument.minimumRequired ?? 0) + Math.max(0, g.instrument.minimumRequired ?? 0);
      if (g.stats) {
        existing.stats = existing.stats ?? { yes: 0, maybe: 0, no: 0, pending: 0 };
        existing.stats.yes += g.stats.yes ?? 0;
        existing.stats.maybe += g.stats.maybe ?? 0;
        existing.stats.no += g.stats.no ?? 0;
        existing.stats.pending += g.stats.pending ?? 0;
      }
    }
  }
  return Array.from(byCategory.values());
}

function groupBySection(groups: InstrumentGroup[]): InstrumentGroup[] {
  const bySection = new Map<string, InstrumentGroup>();
  for (const g of groups) {
    const sectionName = g.instrument.section?.name ?? "Section";
    const existing = bySection.get(sectionName);
    if (!existing) {
      bySection.set(sectionName, {
        instrument: {
          id: 0,
          name: sectionName,
          section: { id: g.instrument.section?.id ?? sectionName, name: sectionName },
          minimumRequired: Math.max(0, g.instrument.minimumRequired ?? 0),
          category: g.instrument.category,
        },
        participants: [...g.participants],
        stats: g.stats ? { ...g.stats } : { yes: 0, maybe: 0, no: 0, pending: 0 },
      });
    } else {
      existing.participants.push(...g.participants);
      existing.instrument.minimumRequired =
        Math.max(0, existing.instrument.minimumRequired ?? 0) + Math.max(0, g.instrument.minimumRequired ?? 0);
      if (g.stats) {
        existing.stats = existing.stats ?? { yes: 0, maybe: 0, no: 0, pending: 0 };
        existing.stats.yes += g.stats.yes ?? 0;
        existing.stats.maybe += g.stats.maybe ?? 0;
        existing.stats.no += g.stats.no ?? 0;
        existing.stats.pending += g.stats.pending ?? 0;
      }
    }
  }
  return Array.from(bySection.values());
}

function StatusIcon({ participate }: { participate: number | null }) {
  if (participate === null || participate === undefined || participate < 0) {
    return (
      <div className="h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-white border-2 border-base-content/40 bg-base-content/30">
        <Clock className="h-5 w-5" />
      </div>
    );
  }
  if (participate === 1) {
    return (
      <div className="h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-white border-2 border-success bg-success">
        <Check className="h-5 w-5" />
      </div>
    );
  }
  if (participate === 2) {
    return (
      <div className="h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-white border-2 border-warning bg-warning">
        <HelpCircle className="h-5 w-5" />
      </div>
    );
  }
  return (
    <div className="h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-white border-2 border-error bg-error">
      <X className="h-5 w-5" />
    </div>
  );
}

function ParticipantRow({
  participant,
  getEntityHref,
}: {
  participant: ParticipantItem;
  getEntityHref?: GetEntityHref | null;
}) {
  const entityType: "contact" | "user" = "contact";
  const entityId = participant.id;
  const href = getEntityHref?.(entityType, entityId) ?? null;

  const nameNode = href ? (
    <Link href={href} className="text-sm font-medium text-inherit no-underline">
      {participant.name}
    </Link>
  ) : (
    <span className="text-sm font-medium">{participant.name}</span>
  );

  return (
    <div className="flex items-start gap-3 px-3 py-3 text-base-content md:rounded-md md:border md:border-base-300/60 md:bg-base-100 md:px-2 md:py-2 md:hover:bg-base-200/70 md:transition-colors">
      <Avatar email={participant.email} name={participant.name} size={32} variant="soft" className="shrink-0" />
      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between gap-2">
          {nameNode}
          <StatusIcon participate={participant.participate} />
        </div>
        {participant.reason?.trim() && <p className="mt-1 text-xs italic text-base-content/70">{participant.reason}</p>}
      </div>
    </div>
  );
}

export function ParticipantOverview({ participantsByInstrument, getEntityHref }: ParticipantOverviewProps) {
  const { t } = useI18n();
  const [groupMode, setGroupMode] = useState<"category" | "instrument" | "section">("category");
  const hasSectionData = useMemo(
    () => Boolean(participantsByInstrument?.some((group) => (group.instrument.section?.name ?? "").trim() !== "")),
    [participantsByInstrument]
  );

  useEffect(() => {
    if (!hasSectionData && groupMode === "section") {
      setGroupMode("category");
    }
  }, [groupMode, hasSectionData]);

  const groups = useMemo(() => {
    if (!participantsByInstrument?.length) return [];
    if (groupMode === "section") return groupBySection(participantsByInstrument);
    if (groupMode === "category") return groupByCategory(participantsByInstrument);
    return participantsByInstrument;
  }, [participantsByInstrument, groupMode]);

  const hasParticipants = groups.some((g) => g.participants?.length > 0);

  if (!hasParticipants) {
    return (
      <div className="text-center py-8 text-sm text-base-content/70">
        {t("js.participants.noParticipantsYet") !== "js.participants.noParticipantsYet"
          ? t("js.participants.noParticipantsYet")
          : "No participants yet"}
      </div>
    );
  }

  return (
    <div className="participant-overview space-y-4">
      <div className="flex items-center gap-3 flex-wrap">
        <span className="text-sm font-medium text-base-content/80">
          {t("js.participants.groupBy") !== "js.participants.groupBy" ? t("js.participants.groupBy") : "Group by"}
        </span>
        <button
          type="button"
          onClick={() => setGroupMode("category")}
          className={`filter-bubble ${groupMode === "category" ? "filter-bubble-rehearsal selected" : "filter-bubble-rehearsal"}`}
        >
          {t("js.participants.category") !== "js.participants.category" ? t("js.participants.category") : "Category"}
        </button>
        <button
          type="button"
          onClick={() => setGroupMode("instrument")}
          className={`filter-bubble ${groupMode === "instrument" ? "filter-bubble-performance selected" : "filter-bubble-performance"}`}
        >
          {t("js.participants.instrument") !== "js.participants.instrument"
            ? t("js.participants.instrument")
            : "Instrument"}
        </button>
        {hasSectionData ? (
          <button
            type="button"
            onClick={() => setGroupMode("section")}
            className={`filter-bubble ${groupMode === "section" ? "filter-bubble-performance selected" : "filter-bubble-performance"}`}
          >
            {t("js.event.share.sectionFallback") !== "js.event.share.sectionFallback"
              ? t("js.event.share.sectionFallback")
              : "Section"}
          </button>
        ) : null}
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {groups.map((group, idx) => {
          const name = getGroupName(group, groupMode);
          const stats: ParticipationStats = group.stats
            ? {
                yes: group.stats.yes,
                maybe: group.stats.maybe,
                no: group.stats.no,
                pending: group.stats.pending,
                total:
                  (group.stats.yes ?? 0) +
                  (group.stats.maybe ?? 0) +
                  (group.stats.no ?? 0) +
                  (group.stats.pending ?? 0),
              }
            : { total: 0 };
          return (
            <div
              key={`${name}-${idx}`}
              className="overflow-hidden pb-4 text-base-content md:rounded-lg md:border-2 md:border-base-300 md:bg-base-100 md:p-4 md:shadow-sm"
            >
              <div className="mb-2 px-1 pt-1 md:px-0 md:pt-0 flex items-center justify-between gap-2">
                <h3 className="text-base font-semibold text-base-content truncate" title={name}>
                  {name}
                </h3>
                {(() => {
                  const minimumRequired = Math.max(0, group.instrument.minimumRequired ?? 0);
                  if (minimumRequired < 1) return null;
                  const attending = (group.stats?.yes ?? 0) + (group.stats?.maybe ?? 0);
                  const meetsMinimum = attending >= minimumRequired;
                  return (
                    <span
                      className={`badge badge-sm ${meetsMinimum ? "badge-success" : "badge-warning"} font-mono font-semibold tracking-wide text-white`}
                      title="Attending (yes+maybe) / minimum required"
                    >
                      {attending}/{minimumRequired}
                    </span>
                  );
                })()}
              </div>
              {(stats.total ?? 0) > 0 && (
                <div className="mb-3 px-1 md:px-0">
                  <ParticipationDiagram stats={stats} />
                </div>
              )}
              <div className="divide-y divide-base-300/60 bg-transparent md:space-y-2 md:divide-y-0 md:border-0 md:bg-transparent">
                {group.participants?.map((p) => (
                  <ParticipantRow key={`${p.id}-${p.userId ?? p.name}`} participant={p} getEntityHref={getEntityHref} />
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
