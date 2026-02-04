/**
 * BNote Next Generation - Participation Diagram (horizontal bar chart)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";

export interface ParticipationStats {
  yes?: number;
  maybe?: number;
  no?: number;
  pending?: number;
  total?: number;
}

interface ParticipationDiagramProps {
  stats: ParticipationStats | null | undefined;
}

const SEGMENT_COLORS = {
  yes: "var(--success)",
  maybe: "var(--warning)",
  no: "var(--destructive)",
  pending: "color-mix(in oklch, var(--muted) 70%, var(--foreground) 30%)",
};

export function ParticipationDiagram({ stats }: ParticipationDiagramProps) {
  if (!stats) return null;
  const { yes = 0, maybe = 0, no = 0, pending = 0, total = 0 } = stats;
  if (total === 0) {
    return (
      <div className="text-center py-4 text-sm" style={{ color: "var(--muted-foreground)" }}>
        No participants yet
      </div>
    );
  }

  const segments: { key: keyof typeof SEGMENT_COLORS; count: number; pct: number }[] = [];
  if (yes > 0) segments.push({ key: "yes", count: yes, pct: (yes / total) * 100 });
  if (maybe > 0) segments.push({ key: "maybe", count: maybe, pct: (maybe / total) * 100 });
  if (no > 0) segments.push({ key: "no", count: no, pct: (no / total) * 100 });
  if (pending > 0) segments.push({ key: "pending", count: pending, pct: (pending / total) * 100 });

  if (segments.length === 0) return null;

  return (
    <div className="participation-diagram">
      <div
        className="flex items-center gap-0 rounded-lg overflow-hidden shadow-sm"
        style={{ background: "var(--muted)" }}
      >
        {segments.map((seg, i) => {
          const isFirst = i === 0;
          const isLast = i === segments.length - 1;
          const rounded =
            segments.length === 1
              ? "rounded-l-lg rounded-r-lg"
              : isFirst
                ? "rounded-l-lg"
                : isLast
                  ? "rounded-r-lg"
                  : "";
          const minPct = seg.count >= 10 ? 5 : 4;
          const showNumber = segments.length === 1 || seg.pct >= minPct;
          return (
            <div
              key={seg.key}
              className={`h-8 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0 ${rounded}`}
              style={{
                width: `${seg.pct}%`,
                backgroundColor: SEGMENT_COLORS[seg.key],
              }}
              title={`${seg.key}: ${seg.count}`}
            >
              {showNumber ? <span className="whitespace-nowrap leading-none">{seg.count}</span> : null}
            </div>
          );
        })}
      </div>
    </div>
  );
}
