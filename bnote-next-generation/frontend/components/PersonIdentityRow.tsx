"use client";

import { Avatar } from "@/components/Avatar";

export interface PersonIdentityRowProps {
  name: string;
  email?: string | null;
  subtitle?: string | null;
  avatarSize?: 24 | 32 | 40;
  avatarVariant?: "solid" | "soft" | "birthday";
  compact?: boolean;
  className?: string;
  nameClassName?: string;
}

export function PersonIdentityRow({
  name,
  email,
  subtitle,
  avatarSize = 32,
  avatarVariant = "soft",
  compact = false,
  className = "",
  nameClassName = "",
}: PersonIdentityRowProps) {
  const displayName = name?.trim() || "—";
  const displaySubtitle = subtitle?.trim() || null;

  return (
    <span
      className={`flex items-center min-w-0 ${compact ? "gap-2" : "gap-3"} ${className}`.trim()}
    >
      <Avatar
        email={email}
        name={displayName}
        size={avatarSize}
        variant={avatarVariant}
        className="shrink-0"
      />
      <span className={`flex flex-col min-w-0 ${compact ? "truncate" : ""}`.trim()}>
        <span className={`truncate ${compact ? "" : "font-medium"} ${nameClassName}`.trim()}>
          {displayName}
        </span>
        {displaySubtitle ? (
          <span className="text-xs text-base-content/60 truncate">{displaySubtitle}</span>
        ) : null}
      </span>
    </span>
  );
}
