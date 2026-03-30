/**
 * BNote Next Generation - Shared shell for entity-style detail pages (contact, user, preferences, …)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import type { ReactNode } from "react";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { DetailCard } from "@/components/DetailCard";
import { DetailPageHeader } from "@/components/DetailPageHeader";

export interface EntityDetailViewLayoutProps {
  title: ReactNode;
  subtitle?: ReactNode;
  right?: ReactNode;
  /** Passed to DetailCard (default matches contact detail). */
  cardClassName?: string;
  children: ReactNode;
}

/** Page column + header + card — same structure as {@link ContactDetail}. */
export function EntityDetailViewLayout({
  title,
  subtitle,
  right,
  cardClassName = "space-y-6",
  children,
}: EntityDetailViewLayoutProps) {
  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader title={title} subtitle={subtitle} right={right} />
      <DetailCard className={cardClassName}>{children}</DetailCard>
    </div>
  );
}
