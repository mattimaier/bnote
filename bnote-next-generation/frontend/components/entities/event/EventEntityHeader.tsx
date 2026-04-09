"use client";

import type { ReactNode } from "react";
import { getIcon } from "@/components/icons";

interface EventEntityHeaderProps {
  title: ReactNode;
  iconName: string;
  iconBgClassName: string;
  badgeLabel?: string;
  badgeClassName: string;
  dateTimeLine?: ReactNode;
  locationLine?: ReactNode;
}

export function EventEntityHeader({
  title,
  iconName,
  iconBgClassName,
  badgeLabel,
  badgeClassName,
  dateTimeLine,
  locationLine,
}: EventEntityHeaderProps) {
  const EventIcon = getIcon(iconName);

  return (
    <div className="flex-1 min-w-0">
      <div className="flex items-center gap-2">
        <div
          className={`module-header-icon-shape flex h-10 w-10 shrink-0 items-center justify-center text-white ${iconBgClassName}`}
        >
          <EventIcon className="h-5 w-5" />
        </div>
        <div className="flex flex-wrap items-center gap-2 min-w-0">
          <h1 className="text-2xl font-bold text-base-content break-words whitespace-normal leading-tight">
            {title}
          </h1>
          {badgeLabel ? <span className={`event-badge ${badgeClassName}`}>{badgeLabel}</span> : null}
        </div>
      </div>
      <div className="mt-2 space-y-1">
        {dateTimeLine ? <p className="text-sm text-base-content/60">{dateTimeLine}</p> : null}
        {locationLine && (
          <p className="text-sm text-base-content/60">{locationLine}</p>
        )}
      </div>
    </div>
  );
}
