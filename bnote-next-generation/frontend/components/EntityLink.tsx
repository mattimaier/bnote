/**
 * BNote Next Generation - Entity link (when user has view permission)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { getEntityPath } from "@/lib/entities/paths";
import { canViewEntityType } from "@/lib/entities/permissions";
import type { ModuleEntry } from "@/lib/entities/permissions";

export interface EntityLinkProps {
  entityType: string;
  id: number | undefined;
  name: string;
  modules: ModuleEntry[] | null;
  children?: React.ReactNode;
  emptyLabel?: string;
}

/** Renders name as link to entity when user has view permission, else plain text. */
export function EntityLink({
  entityType,
  id,
  name,
  modules,
  children,
  emptyLabel = "",
}: EntityLinkProps) {
  const text = (children ?? name) || emptyLabel;
  if (!id || !canViewEntityType(entityType, modules)) return <span>{text}</span>;
  return (
    <Link href={getEntityPath(entityType, id)} className="text-inherit no-underline">
      {text}
    </Link>
  );
}
