/**
 * BNote Next Generation - Shared card container for detail/view/edit sections
 * Use for all entity and profile detail content so view and edit modes share the same optics (white/card box).
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

export interface DetailCardProps {
  children: React.ReactNode;
  /** Optional extra className (e.g. space-y-6 for inner spacing) */
  className?: string;
}

/** Card box for detail page sections: rounded border, card background on desktop; flat (no box) on mobile. */
export function DetailCard({ children, className = "" }: DetailCardProps) {
  return (
    <div
      className={`rounded-none border-0 shadow-none bg-transparent p-3 md:rounded-xl md:border md:shadow-sm md:p-6 md:bg-[var(--card)] ${className}`.trim()}
      style={{
        borderColor: "var(--border)",
        color: "var(--card-foreground)",
      }}
    >
      {children}
    </div>
  );
}
