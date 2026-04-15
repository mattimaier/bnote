"use client";

import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import type { ModuleEntry } from "@/lib/entities/permissions";
import { queryKeys } from "@/lib/query/keys";
import { QUERY_STALE_TIMES } from "@/lib/query/stale-times";

interface RawModule {
  id: number;
  name: string;
  route?: string;
  icon?: string;
  i18n?: string;
}

function normalizeModules(input: RawModule[]): ModuleEntry[] {
  return input.map((m) => ({
    id: m.id,
    name: m.name ?? "",
    route: m.route,
    icon: m.icon,
    i18n: m.i18n,
  }));
}

export function useModulesQuery() {
  return useQuery({
    queryKey: queryKeys.auth.modules,
    queryFn: async ({ signal }) => {
      const res = await api.get<RawModule[] | { modules: RawModule[] }>("auth", "getModules", undefined, { signal });
      const list = Array.isArray(res) ? res : ((res as { modules: RawModule[] }).modules ?? []);
      return normalizeModules(list);
    },
    staleTime: QUERY_STALE_TIMES.authModulesMs,
  });
}
