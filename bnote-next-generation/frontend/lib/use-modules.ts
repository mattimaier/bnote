/**
 * BNote Next Generation - useModules hook for permission checks
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import type { ModuleEntry } from "@/lib/entities/permissions";
import { useModulesQuery } from "@/lib/query/hooks/use-modules-query";

/**
 * Fetches auth getModules for use with canViewEntityType.
 * Returns null until loaded (or on error).
 */
export function useModules(): ModuleEntry[] | null {
  const { data } = useModulesQuery();
  return data ?? null;
}
