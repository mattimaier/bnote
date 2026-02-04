/**
 * BNote Next Generation - EntityListRow
 * Shared row layout for search overlay and mobile table lists: icon, primary line, optional badge, secondary.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import Link from "next/link";

export interface EntityListRowProps {
  /** Left: colored circle with icon inside (full block, e.g. div with dotClass + getIcon) */
  icon: React.ReactNode;
  /** Main line (bold or primary color) */
  primary: React.ReactNode;
  /** Optional pill/badge next to primary */
  badge?: React.ReactNode;
  /** Optional secondary line(s), muted (e.g. Clock + time, MapPin + location) */
  secondary?: React.ReactNode;
  /** If set, render as Link with this href */
  href?: string;
  /** If set (and no href), render as button with onClick */
  onClick?: (e: React.MouseEvent) => void;
  /** Additional class for the row container */
  className?: string;
}

const rowClass =
  "flex gap-3 px-2 py-2.5 md:px-4 transition-colors hover:bg-[var(--muted)]/60 active:bg-[var(--muted)]/80 text-left w-full items-start no-underline border-0 bg-transparent cursor-pointer font-inherit select-none";
const rowStyle: React.CSSProperties = {
  color: "var(--foreground)",
  WebkitTapHighlightColor: "transparent",
} as React.CSSProperties;

export function EntityListRow({
  icon,
  primary,
  badge,
  secondary,
  href,
  onClick,
  className = "",
}: EntityListRowProps) {
  const content = (
    <>
      <div className="mt-0.5 h-6 w-6 shrink-0 flex items-center justify-center rounded-full overflow-hidden">
        {icon}
      </div>
      <div className="min-w-0 flex-1 space-y-1">
        <div className="flex flex-wrap items-center gap-x-1.5 gap-y-1 min-w-0">
          <span className="text-sm font-medium min-w-0 truncate">{primary}</span>
          {badge != null && badge !== false && (
            <span className="flex-shrink-0 flex flex-wrap items-center gap-1.5">
              {badge}
            </span>
          )}
        </div>
        {secondary != null && secondary !== false && (
          <div
            className="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs leading-snug"
            style={{ color: "var(--muted-foreground)" }}
          >
            {secondary}
          </div>
        )}
      </div>
    </>
  );

  const combinedClass = `${rowClass} ${className}`.trim();

  if (href != null && href !== "") {
    return (
      <Link href={href} className={combinedClass} style={rowStyle} onClick={onClick}>
        {content}
      </Link>
    );
  }

  if (onClick) {
    return (
      <button
        type="button"
        className={combinedClass}
        style={rowStyle}
        onClick={onClick}
      >
        {content}
      </button>
    );
  }

  return (
    <div className={combinedClass} style={rowStyle}>
      {content}
    </div>
  );
}
