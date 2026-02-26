/**
 * BNote Next Generation - Shared loading spinner
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useI18n } from "@/contexts/I18nContext";

export type SpinnerVariant = "primary" | "muted";

const variantClasses: Record<SpinnerVariant, string> = {
  primary:
    "border-2 border-primary border-t-transparent",
  muted:
    "border-2 border-zinc-400 border-t-transparent dark:border-zinc-500",
};

export interface SpinnerProps {
  /** Visual style. Default: primary */
  variant?: SpinnerVariant;
  /** Optional size: default 32px (h-8 w-8). Use "sm" for smaller. */
  size?: "default" | "sm";
  className?: string;
}

const sizeClasses = {
  default: "h-8 w-8",
  sm: "h-5 w-5",
};

export function Spinner({
  variant = "primary",
  size = "default",
  className = "",
}: SpinnerProps) {
  const { t } = useI18n();
  const loadingLabel = t("js.common.loading") !== "js.common.loading" ? t("js.common.loading") : "Loading";
  const borderClass =
    variant === "primary"
      ? variantClasses.primary
      : variantClasses.muted;
  return (
    <div
      className={`animate-spin rounded-full ${sizeClasses[size]} ${borderClass} ${className}`.trim()}
      role="status"
      aria-label={loadingLabel}
    />
  );
}
