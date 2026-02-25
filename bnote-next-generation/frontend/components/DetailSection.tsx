/**
 * BNote Next Generation - Shared detail page section card
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

/** Reusable card/section class for entity detail pages (mobile: flat, desktop: rounded card). */
export const DETAIL_SECTION_CLASS =
  "rounded-none border-0 shadow-none px-0 py-1 md:rounded-xl md:border md:border-base-300 md:shadow-sm md:p-6 bg-transparent md:bg-base-100 text-base-content";

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
