/**
 * BNote Next Generation - Person option row (avatar + name + instrument)
 * Shared component for person/contact selection across the app.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { Avatar } from "@/components/Avatar";

export interface PersonOptionRowProps {
  /** Display name */
  name: string;
  /** Email for Gravatar (optional) */
  email?: string | null;
  /** Instrument - always shown as subtitle for persons */
  instrument?: string | null;
  /** Size of avatar */
  avatarSize?: 24 | 32 | 40;
  /** Compact layout (single line) */
  compact?: boolean;
  className?: string;
}

export function PersonOptionRow({
  name,
  email,
  instrument,
  avatarSize = 32,
  compact = false,
  className = "",
}: PersonOptionRowProps) {
  const displayName = name?.trim() || "—";
  const displayInstrument = instrument?.trim() || null;

  if (compact) {
    return (
      <span className={`flex items-center gap-2 min-w-0 ${className}`.trim()}>
        <Avatar
          email={email}
          name={displayName}
          size={avatarSize}
          variant="soft"
          className="shrink-0"
        />
        <span className="min-w-0 flex flex-col truncate">
          <span className="truncate">{displayName}</span>
          {displayInstrument && (
            <span className="text-xs text-base-content/60 truncate">
              {displayInstrument}
            </span>
          )}
        </span>
      </span>
    );
  }

  return (
    <span className={`flex items-center gap-3 min-w-0 ${className}`.trim()}>
      <Avatar
        email={email}
        name={displayName}
        size={avatarSize}
        variant="soft"
        className="shrink-0"
      />
      <span className="flex flex-col min-w-0">
        <span className="truncate font-medium">{displayName}</span>
        {displayInstrument ? (
          <span className="text-xs text-base-content/60 truncate">
            {displayInstrument}
          </span>
        ) : null}
      </span>
    </span>
  );
}
