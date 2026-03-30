/**
 * BNote Next Generation - Shared card container for detail/view/edit sections
 * Use for all entity and profile detail content so view and edit modes share the same optics (white/card box).
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

/**
 * Nested panel inside {@link DetailCard} (e.g. visibility, notification prefs).
 * Same surface as the contact detail “Visibility” block: flat on small screens, bordered inset on md+.
 */
export const DETAIL_CARD_SUBSECTION_CLASS =
  "rounded-none border-0 p-4 md:rounded-box md:border md:border-base-300 md:p-4 bg-base-100 md:bg-transparent";

export interface DetailCardProps {
  children: React.ReactNode;
  /** Optional extra className (e.g. space-y-6 for inner spacing) */
  className?: string;
}

/** Card box for detail page sections: rounded border, card background on desktop; flat (no box) on mobile. */
export function DetailCard({ children, className = "" }: DetailCardProps) {
  return (
    <div
      className={`rounded-none border-0 shadow-none bg-transparent p-3 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 md:bg-base-100 text-base-content ${className}`.trim()}
    >
      {children}
    </div>
  );
}
