/**
 * BNote Next Generation - Dashboard Tile Component
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * Reusable tile card for admin dashboard with header, optional badge, body, and footer link.
 * Uses FlyonUI stat/card patterns and badges for attention states.
 */

"use client";

import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { getIcon } from "@/components/icons";

export type BadgeVariant = "default" | "warning" | "error" | "success" | "info";

export interface DashboardTileProps {
  /** i18n key for title */
  titleKey: string;
  /** Optional count to show in badge */
  count?: number;
  /** Badge variant based on urgency */
  badgeVariant?: BadgeVariant;
  /** Link href for "View" action */
  href?: string;
  /** i18n key for link text (default: View) */
  linkKey?: string;
  /** Optional icon name from getIcon */
  icon?: string;
  /** Optional icon color class (e.g. text-warning) */
  iconClass?: string;
  children?: React.ReactNode;
  /** Optional stat value (large number) */
  statValue?: string | number;
  /** Optional description text below stat */
  statDesc?: string;
  /** Draggable handle - pass attributes from useSortable */
  dragHandleProps?: React.HTMLAttributes<HTMLElement>;
  /** Additional className for the tile wrapper */
  className?: string;
  /** Optional extra content in header (e.g. settings button) */
  headerExtra?: React.ReactNode;
}

const badgeClasses: Record<BadgeVariant, string> = {
  default: "badge badge-soft",
  warning: "badge badge-warning",
  error: "badge badge-error",
  success: "badge badge-success",
  info: "badge badge-soft badge-info",
};

export function DashboardTile({
  titleKey,
  count,
  badgeVariant = "default",
  href,
  linkKey = "js.common.view",
  icon,
  iconClass = "text-base-content/60",
  children,
  statValue,
  statDesc,
  dragHandleProps,
  className = "",
  headerExtra,
}: DashboardTileProps) {
  const { t } = useI18n();

  const title = t(titleKey);
  const linkText = t(linkKey);
  const showBadge = count !== undefined && count !== null;
  const badgeClass = badgeClasses[badgeVariant];

  return (
    <div
      className={`stats stats-vertical shadow stats-border bg-base-100 rounded-box overflow-hidden min-h-[160px] w-full max-w-full ${className}`}
    >
      <div className="stat py-4 px-4 md:py-5 md:px-5">
        <div className="flex items-start justify-between gap-2">
          <div className="flex items-center gap-2 flex-1 min-w-0">
            {icon && (() => {
              const IconComponent = getIcon(icon);
              return (
                <div
                  className={`stat-figure shrink-0 ${iconClass} hidden sm:block`}
                  aria-hidden
                >
                  <IconComponent className="h-6 w-6" />
                </div>
              );
            })()}
            <div className="stat-title text-sm md:text-base truncate">
              {title}
              {showBadge && (
                <span className={`badge badge-sm ml-2 ${badgeClass}`}>
                  {count}
                </span>
              )}
            </div>
          </div>
          {headerExtra}
          {dragHandleProps && (() => {
            const GripIcon = getIcon("grip-vertical");
            return (
              <button
                type="button"
                className="btn btn-soft btn-xs btn-square shrink-0 cursor-grab active:cursor-grabbing"
                aria-label={t("js.common.dragToReorder")}
                {...dragHandleProps}
              >
                <GripIcon className="h-4 w-4" />
              </button>
            );
          })()}
        </div>
        {statValue !== undefined && statValue !== null && (
          <div className="stat-value text-2xl md:text-3xl mt-1">
            {statValue}
          </div>
        )}
        {statDesc && (
          <div className="stat-desc text-xs hidden md:block">{statDesc}</div>
        )}
        {children && <div className="mt-3 text-sm space-y-1.5 max-h-36 overflow-y-auto overscroll-contain">{children}</div>}
        {href && (
          <div className="stat-actions mt-2">
            <Link href={href} className="btn btn-sm btn-soft btn-primary">
              {linkText} →
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}
