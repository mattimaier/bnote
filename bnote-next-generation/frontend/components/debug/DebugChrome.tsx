/**
 * Shared chrome for /debug/* routes — matches Developer hub (theme tokens, cards).
 *
 * Copyright (C) 2026 BNote Contributors
 */

import Link from "next/link";
import type { ReactNode } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";

export type DebugCrumb = { label: string; href?: string };

export function DebugBreadcrumb({ items }: { items: DebugCrumb[] }) {
  return (
    <nav className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-base-content/65" aria-label="Breadcrumb">
      {items.map((item, i) => (
        <span key={`${item.label}-${i}`} className="flex items-center gap-2">
          {i > 0 ? <span className="text-base-content/35 select-none">/</span> : null}
          {item.href ? (
            <Link
              href={item.href}
              className="link link-hover text-base-content/80 hover:text-primary truncate font-medium"
            >
              {item.label}
            </Link>
          ) : (
            <span className="truncate font-medium text-base-content">{item.label}</span>
          )}
        </span>
      ))}
    </nav>
  );
}

export function DebugSection({
  title,
  description,
  children,
  className,
}: {
  title: string;
  description?: ReactNode;
  children: ReactNode;
  className?: string;
}) {
  return (
    <section
      className={["rounded-box border border-base-300 bg-base-100/90 p-4 md:p-5 space-y-4 shadow-sm", className ?? ""]
        .filter(Boolean)
        .join(" ")}
    >
      <header className="space-y-1">
        <h2 className="text-base font-semibold tracking-tight text-base-content">{title}</h2>
        {description ? <p className="text-sm text-base-content/70 leading-relaxed">{description}</p> : null}
      </header>
      {children}
    </section>
  );
}

/** Standard wrapper: breadcrumbs + optional header + stacked sections. */
export function DebugPageShell({
  breadcrumb,
  title,
  subtitle,
  headerActions,
  children,
}: {
  breadcrumb: DebugCrumb[];
  title: string;
  subtitle?: string;
  headerActions?: ReactNode;
  children: ReactNode;
}) {
  return (
    <PageContent className="space-y-6">
      <div className="space-y-3">
        <DebugBreadcrumb items={breadcrumb} />
        <AppPageHeader title={title} subtitle={subtitle} actions={headerActions} />
      </div>
      {children}
    </PageContent>
  );
}
