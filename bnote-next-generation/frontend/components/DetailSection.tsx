/**
 * BNote Next Generation - Shared detail page section card
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

/** Reusable card/section class for entity detail pages (mobile: flat, desktop: rounded card). */
export const DETAIL_SECTION_CLASS =
  "rounded-lg border border-base-300/70 bg-base-100/80 px-3 py-3 shadow-sm md:rounded-xl md:border md:border-base-300 md:shadow-sm md:p-6 md:bg-base-100 text-base-content";

export interface DetailSectionProps {
  children: React.ReactNode;
  className?: string;
}

export function DetailSection({ children, className = "" }: DetailSectionProps) {
  return (
    <div className={`${DETAIL_SECTION_CLASS} ${className}`.trim()}>
      {children}
    </div>
  );
}
