"use client";

import type { ReactNode } from "react";
import { getIcon } from "@/components/icons";
import { getModuleHeadlineConfig } from "@/lib/module-headline-config";
import { SquircleIconBadge } from "@/components/SquircleIconBadge";

export interface AppPageHeaderProps {
  title: ReactNode;
  subtitle?: ReactNode;
  actions?: ReactNode;
  moduleKey?: string;
  iconName?: string;
  iconColor?: string;
}

export function AppPageHeader({ title, subtitle, actions, moduleKey, iconName, iconColor }: AppPageHeaderProps) {
  const moduleConfig = getModuleHeadlineConfig(moduleKey);
  const resolvedIconName = iconName ?? moduleConfig?.icon ?? null;
  const resolvedIconColor = iconColor ?? moduleConfig?.color ?? null;
  const Icon = resolvedIconName ? getIcon(resolvedIconName) : null;
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div className="min-w-0">
        <h1
          className="text-2xl font-bold break-words whitespace-normal leading-tight"
          style={{ color: "var(--foreground)" }}
        >
          <span className="inline-flex items-center gap-3">
            {Icon && <SquircleIconBadge Icon={Icon} color={resolvedIconColor ?? "var(--foreground)"} />}
            <span className="min-w-0 break-words whitespace-normal">{title}</span>
          </span>
        </h1>
        {subtitle != null && (
          <p className="mt-1 text-sm" style={{ color: "var(--muted-foreground)" }}>
            {subtitle}
          </p>
        )}
      </div>
      {actions != null && <div className="shrink-0 flex flex-wrap items-center gap-2 sm:self-start">{actions}</div>}
    </div>
  );
}
