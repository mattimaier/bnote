"use client";

import type { ComponentType, CSSProperties } from "react";

export interface SquircleIconBadgeProps {
  Icon: ComponentType<{ className?: string; style?: CSSProperties }>;
  color: string;
  size?: "sm" | "md" | "lg";
  iconClassName?: string;
  className?: string;
}

export function SquircleIconBadge({ Icon, color, size = "lg", iconClassName, className = "" }: SquircleIconBadgeProps) {
  const sizeClass = size === "sm" ? "h-7 w-7 border" : size === "md" ? "h-8 w-8 border-2" : "h-10 w-10 border-2";
  const resolvedIconClassName =
    iconClassName ?? (size === "sm" ? "h-3.5 w-3.5" : size === "md" ? "h-4 w-4" : "h-5 w-5");
  const badgeStyle = {
    color,
    background: `color-mix(in oklch, ${color} 17%, transparent)`,
    borderColor: `color-mix(in oklch, ${color} 42%, transparent)`,
    borderRadius: "30%",
    // Superellipse where supported; border-radius fallback elsewhere.
    cornerShape: "superellipse(100%)",
  } as CSSProperties;

  return (
    <span
      className={`inline-flex shrink-0 items-center justify-center leading-none ${sizeClass} ${className}`.trim()}
      style={badgeStyle}
    >
      <Icon className={resolvedIconClassName} />
    </span>
  );
}
