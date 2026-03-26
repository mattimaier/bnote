/**
 * BNote Next Generation - BNote logo with blue gradient background
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { getBnoteLogoUrl } from "@/lib/bnote-assets";

export type BNoteLogoSize = "sm" | "lg";

/** Inner padding: "default" | "tight" (less space between container and logo). */
export type BNoteLogoPadding = "default" | "tight";

const sizeClasses: Record<
  BNoteLogoSize,
  Record<BNoteLogoPadding, { container: string; logo: string }>
> = {
  sm: {
    default: { container: "h-9 w-9", logo: "h-9 w-9" },
    tight: { container: "h-9 w-9", logo: "h-9 w-9" },
  },
  lg: {
    default: { container: "h-24 w-24", logo: "h-24 w-24" },
    tight: { container: "h-24 w-24", logo: "h-24 w-24" },
  },
};

export interface BNoteLogoProps {
  size?: BNoteLogoSize;
  /** Inner padding: "tight" = less space between blue box and logo. */
  padding?: BNoteLogoPadding;
  className?: string;
}

export function BNoteLogo({
  size = "sm",
  padding = "default",
  className = "",
}: BNoteLogoProps) {
  const { container, logo } = sizeClasses[size][padding];
  const logoUrl = getBnoteLogoUrl();

  return (
    <div className={`flex items-center justify-center ${container} ${className}`.trim()}>
      <img src={logoUrl} alt="BNote" className={`${logo} object-contain`} />
    </div>
  );
}
