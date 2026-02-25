/**
 * BNote Next Generation - Avatar (FlyonUI + Gravatar, initials fallback)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useState } from "react";
import gravatarUrl from "gravatar-url";

export type AvatarVariant = "solid" | "soft";

const SIZE_CLASSES = {
  24: "size-6",
  32: "size-8",
  40: "size-10",
} as const;

const PLACEHOLDER_SOLID = "bg-primary text-primary-content";
const PLACEHOLDER_SOFT = "bg-primary/10 text-primary";

function getInitials(name: string): string {
  if (!name?.trim()) return "?";
  const parts = name.trim().split(/\s+/);
  const first = parts[0]?.[0] ?? "";
  const last = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? "") : "";
  return (first + last).toUpperCase().slice(0, 2) || "?";
}

export interface AvatarProps {
  /** Email for Gravatar; when present and image loads, Gravatar is shown */
  email?: string | null;
  /** Display name for initials fallback */
  name: string;
  /** Pixel size (24, 32, or 40); maps to FlyonUI size-6/8/10 */
  size?: 24 | 32 | 40;
  /** Placeholder style when showing initials */
  variant?: AvatarVariant;
  className?: string;
}

export function Avatar({
  email,
  name,
  size = 32,
  variant = "solid",
  className = "",
}: AvatarProps) {
  const [usePlaceholder, setUsePlaceholder] = useState(false);
  const initials = getInitials(name);
  const sizeClass = SIZE_CLASSES[size];
  const placeholderClasses =
    variant === "soft" ? PLACEHOLDER_SOFT : PLACEHOLDER_SOLID;
  const showImage = email?.trim() && !usePlaceholder;
  const gravatarSrc = email?.trim()
    ? gravatarUrl(email.trim(), { size: size * 2, default: "404" })
    : "";

  if (showImage && gravatarSrc) {
    return (
      <div className={`avatar ${className}`.trim()}>
        <div className={`${sizeClass} rounded-full overflow-hidden`}>
          {/* 404 is expected when this email has no Gravatar; onError then shows initials */}
          <img
            src={gravatarSrc}
            alt=""
            width={size}
            height={size}
            className="size-full object-cover"
            onError={() => setUsePlaceholder(true)}
          />
        </div>
      </div>
    );
  }

  return (
    <div className={`avatar avatar-placeholder ${className}`.trim()}>
      <div
        className={`${sizeClass} rounded-full flex items-center justify-center text-xs font-semibold uppercase ${placeholderClasses}`}
      >
        {initials}
      </div>
    </div>
  );
}
