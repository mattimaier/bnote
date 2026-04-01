"use client";

import type { ReactNode } from "react";
import { getIcon } from "@/components/icons";
import { getModuleHeadlineConfig } from "@/lib/module-headline-config";

export interface AppPageHeaderProps {
  title: ReactNode;
  subtitle?: ReactNode;
  actions?: ReactNode;
  moduleKey?: string;
  iconName?: string;
  iconColor?: string;
}

export function AppPageHeader({
  title,
  subtitle,
  actions,
  moduleKey,
  iconName,
  iconColor,
}: AppPageHeaderProps) {
  const moduleConfig = getModuleHeadlineConfig(moduleKey);
  const resolvedIconName = iconName ?? moduleConfig?.icon ?? null;
  const resolvedIconColor = iconColor ?? moduleConfig?.color ?? null;
  const Icon = resolvedIconName ? getIcon(resolvedIconName) : null;
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div className="min-w-0">
        <h1
          className="text-2xl font-bold break-words whitespace-normal leading-tight"
          style={{ color: "var(--foreground)" }}
        >
          <span className="inline-flex items-center gap-3">
            {Icon && (
              <span
                className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border"
                style={{
                  color: resolvedIconColor ?? "var(--foreground)",
                  borderColor: `color-mix(in oklch, ${resolvedIconColor ?? "var(--foreground)"} 30%, transparent)`,
                  background: `color-mix(in oklch, ${resolvedIconColor ?? "var(--foreground)"} 14%, transparent)`,
                }}
              >
                <Icon className="h-5 w-5" />
              </span>
            )}
            <span className="min-w-0 break-words whitespace-normal">{title}</span>
          </span>
        </h1>
        {subtitle != null && (
          <p className="mt-1 text-sm" style={{ color: "var(--muted-foreground)" }}>
            {subtitle}
          </p>
        )}
      </div>
      {actions != null && <div className="shrink-0 flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  );
}
