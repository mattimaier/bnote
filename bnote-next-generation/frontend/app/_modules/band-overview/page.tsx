/**
 * BNote Next Generation - Band Overview Page
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * Extended admin dashboard with tile-based Band Overview and Your Overview sections.
 * Route: /band-overview (admin-only in sidebar)
 */

"use client";

import { useCallback } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { useI18n } from "@/contexts/I18nContext";
import type { Session } from "@/lib/auth";
import { getErrorMessage } from "@/lib/error-utils";
import { BandOverviewContent } from "@/components/dashboard/BandOverviewContent";
import type { AdminOverviewData } from "@/components/dashboard/BandOverviewContent";
import { useSessionQuery } from "@/lib/query/hooks/use-session-query";
import { useBandOverviewQuery } from "@/lib/query/hooks/use-dashboard-query";
import { queryKeys } from "@/lib/query/keys";

export default function BandOverviewPage() {
  const { t, ready } = useI18n();
  const queryClient = useQueryClient();
  const { data: session } = useSessionQuery();
  const isAdmin = Boolean(session?.isAdmin);
  const { data, isPending, error } = useBandOverviewQuery(ready && Boolean(session), isAdmin);
  const loadData = useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: queryKeys.dashboard.bandOverview(isAdmin) });
  }, [isAdmin, queryClient]);

  return (
    <BandOverviewContent
      session={(session ?? null) as Session | null}
      adminOverview={session?.isAdmin ? (data?.adminOverview as AdminOverviewData | null) : null}
      dashboardData={data?.dashboardData ?? null}
      eventsNeedingResponse={data?.needResponse?.events ?? []}
      activityFeed={data?.activityFeed?.items ?? []}
      loading={isPending && !data}
      error={error ? getErrorMessage(error, t, "js.dashboard.loadError") : ""}
      onReload={loadData}
    />
  );
}
