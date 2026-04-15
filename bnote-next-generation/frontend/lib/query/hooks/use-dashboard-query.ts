"use client";

import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import type { DashboardData, EventsNeedingResponse } from "@/components/dashboard/DashboardContent";
import type { AdminOverviewData } from "@/components/dashboard/BandOverviewContent";
import { queryKeys } from "@/lib/query/keys";
import { QUERY_STALE_TIMES } from "@/lib/query/stale-times";

export interface DashboardHomeBundle {
  dashboard: DashboardData;
  needResponse: EventsNeedingResponse;
}

export interface BandOverviewBundle {
  dashboardData: { inbox?: unknown[]; news?: string };
  needResponse: { events?: unknown[] };
  adminOverview: AdminOverviewData | null;
  activityFeed: { items?: unknown[] };
}

export function useDashboardHomeQuery(enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.dashboard.home,
    queryFn: async ({ signal }) => api.get<DashboardHomeBundle>("dashboard", "bundle", undefined, { signal }),
    enabled,
    staleTime: QUERY_STALE_TIMES.dashboardMs,
    placeholderData: keepPreviousData,
  });
}

export function useBandOverviewQuery(enabled: boolean, isAdmin: boolean) {
  return useQuery({
    queryKey: queryKeys.dashboard.bandOverview(isAdmin),
    queryFn: async ({ signal }) => {
      const bundle = await api.get<BandOverviewBundle>("dashboard", "bandOverviewBundle", undefined, { signal });
      return {
        dashboardData: bundle?.dashboardData ?? {},
        needResponse: bundle?.needResponse ?? {},
        adminOverview: isAdmin ? (bundle?.adminOverview ?? null) : null,
        activityFeed: bundle?.activityFeed ?? { items: [] },
      } satisfies BandOverviewBundle;
    },
    enabled,
    staleTime: QUERY_STALE_TIMES.dashboardMs,
    placeholderData: keepPreviousData,
  });
}
