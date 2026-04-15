/**
 * BNote Next Generation - Page content wrapper
 * Shared layout for modules and entity pages (wide desktop, less whitespace).
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { PAGE_CONTENT_CLASS } from "@/lib/layout";

export interface PageContentProps {
  children: React.ReactNode;
  className?: string;
}

export function PageContent({ children, className }: PageContentProps) {
  return <div className={[PAGE_CONTENT_CLASS, className].filter(Boolean).join(" ")}>{children}</div>;
}
