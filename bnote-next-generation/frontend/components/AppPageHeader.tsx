"use client";

import type { ReactNode } from "react";

export interface AppPageHeaderProps {
  title: ReactNode;
  subtitle?: ReactNode;
  actions?: ReactNode;
}

export function AppPageHeader({ title, subtitle, actions }: AppPageHeaderProps) {
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div className="min-w-0">
        <h1
          className="text-2xl font-bold break-words whitespace-normal leading-tight"
          style={{ color: "var(--foreground)" }}
        >
          {title}
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
