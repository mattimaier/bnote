/**
 * BNote Next Generation - useModules hook for permission checks
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import type { ModuleEntry } from "@/lib/entities/permissions";

interface RawModule {
  id: number;
  name: string;
  route?: string;
  icon?: string;
  i18n?: string;
}

/**
 * Fetches auth getModules for use with canViewEntityType.
 * Returns null until loaded (or on error).
 */
export function useModules(): ModuleEntry[] | null {
  const [modules, setModules] = useState<ModuleEntry[] | null>(null);

  useEffect(() => {
    api
      .get<RawModule[] | { modules: RawModule[] }>("auth", "getModules")
      .then((res) => {
        const list = Array.isArray(res) ? res : (res as { modules: RawModule[] }).modules ?? [];
        setModules(
          list.map((m) => ({
            id: m.id,
            name: m.name ?? "",
            route: m.route,
            icon: m.icon,
            i18n: m.i18n,
          }))
        );
      })
      .catch(() => setModules(null));
  }, []);

  return modules;
}
