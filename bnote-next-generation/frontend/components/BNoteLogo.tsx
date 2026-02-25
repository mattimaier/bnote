/**
 * BNote Next Generation - BNote logo with blue gradient background
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { getBnoteLogoUrl } from "@/lib/bnote-assets";

const LOGO_FILTER =
  "brightness(0) saturate(100%) invert(58%) sepia(95%) saturate(2878%) hue-rotate(195deg) brightness(102%) contrast(101%)";

export type BNoteLogoSize = "sm" | "lg";

/** Inner padding: "default" | "tight" (less space between container and logo). */
export type BNoteLogoPadding = "default" | "tight";

const sizeClasses: Record<
  BNoteLogoSize,
  Record<BNoteLogoPadding, { container: string; logo: string; fallback: string }>
> = {
  sm: {
    default: { container: "h-9 w-9", logo: "h-5 w-5", fallback: "text-xs" },
    tight: { container: "h-9 w-9", logo: "h-6 w-6", fallback: "text-xs" },
  },
  lg: {
    default: { container: "h-24 w-24", logo: "h-14 w-14", fallback: "text-2xl" },
    tight: { container: "h-24 w-24", logo: "h-20 w-20", fallback: "text-2xl" },
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
  const [logoUrl, setLogoUrl] = useState("");
  const { container, logo, fallback } = sizeClasses[size][padding];

  useEffect(() => {
    setLogoUrl(getBnoteLogoUrl());
  }, []);

  return (
    <div
      className={`rounded-box flex items-center justify-center ring-1 ring-primary/20 bg-gradient-to-br from-primary/30 to-primary/10 ${container} ${className}`.trim()}
    >
      {logoUrl ? (
        <img
          src={logoUrl}
          alt="BNote"
          className={logo}
          style={{ filter: LOGO_FILTER }}
        />
      ) : (
        <span className={`text-primary font-bold ${fallback}`}>B</span>
      )}
    </div>
  );
}
