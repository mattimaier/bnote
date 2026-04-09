/**
 * BNote Next Generation - Person option row (avatar + name + instrument)
 * Shared component for person/contact selection across the app.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { PersonIdentityRow } from "@/components/PersonIdentityRow";

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
  return (
    <PersonIdentityRow
      name={name}
      email={email}
      subtitle={instrument}
      avatarSize={avatarSize}
      avatarVariant="soft"
      compact={compact}
      className={className}
    />
  );
}
