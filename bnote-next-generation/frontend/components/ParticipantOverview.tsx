/**
 * BNote Next Generation - Participant Overview (grouped by instrument/category)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useMemo, useState } from "react";
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
  instrument: { id: number; name: string; category?: { id: number; name: string } };
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

function getGroupName(group: InstrumentGroup, mode: "category" | "instrument"): string {
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
          category: g.instrument.category ?? { id: 0, name: catName },
        },
        participants: [...g.participants],
        stats: g.stats
          ? { ...g.stats }
          : { yes: 0, maybe: 0, no: 0, pending: 0 },
      });
    } else {
      existing.participants.push(...g.participants);
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
    <div className="flex items-start gap-3 py-2 px-2 rounded-md border border-base-300/60 bg-base-100 hover:bg-base-200/70 transition-colors text-base-content">
      <Avatar
        email={participant.email}
        name={participant.name}
        size={32}
        variant="soft"
        className="shrink-0"
      />
      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between gap-2">
          {nameNode}
          <StatusIcon participate={participant.participate} />
        </div>
        {participant.reason?.trim() && (
          <p className="text-xs mt-1 italic text-base-content/70">
            {participant.reason}
          </p>
        )}
      </div>
    </div>
  );
}

export function ParticipantOverview({ participantsByInstrument, getEntityHref }: ParticipantOverviewProps) {
  const { t } = useI18n();
  const [groupMode, setGroupMode] = useState<"category" | "instrument">("category");

  const groups = useMemo(() => {
    if (!participantsByInstrument?.length) return [];
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
          {t("js.participants.instrument") !== "js.participants.instrument" ? t("js.participants.instrument") : "Instrument"}
        </button>
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
              className="rounded-lg border-2 border-base-300 bg-white p-4 shadow-sm text-base-content"
            >
              <h3 className="text-base font-semibold text-center mb-2 text-base-content">
                {name}
              </h3>
              {(stats.total ?? 0) > 0 && (
                <div className="mb-3">
                  <ParticipationDiagram stats={stats} />
                </div>
              )}
              <div className="space-y-2">
                {group.participants?.map((p) => (
                  <ParticipantRow
                    key={`${p.id}-${p.userId ?? p.name}`}
                    participant={p}
                    getEntityHref={getEntityHref}
                  />
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
