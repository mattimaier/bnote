/**
 * BNote Next Generation - Dashboard Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { useI18n } from "@/contexts/I18nContext";
import { getErrorMessage } from "@/lib/error-utils";
import DashboardContent from "@/components/dashboard/DashboardContent";
import { useSessionQuery } from "@/lib/query/hooks/use-session-query";
import { useDashboardHomeQuery } from "@/lib/query/hooks/use-dashboard-query";
import { queryKeys } from "@/lib/query/keys";

export default function DashboardPage() {
  const { t, ready } = useI18n();
  const queryClient = useQueryClient();
  const { data: session } = useSessionQuery();
  const { data, isPending, error } = useDashboardHomeQuery(ready);

  const loadDashboard = useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: queryKeys.dashboard.home });
  }, [queryClient]);

  return (
    <DashboardContent
      session={session ?? null}
      dashboard={data?.dashboard ?? null}
      needResponse={{
        events: data?.needResponse?.events ?? [],
        config: data?.needResponse?.config ?? {},
        counts: {
          rehearsal: data?.needResponse?.counts?.rehearsal ?? 0,
          performance: data?.needResponse?.counts?.performance ?? 0,
          meeting: data?.needResponse?.counts?.meeting ?? 0,
          vote: data?.needResponse?.counts?.vote ?? 0,
        },
      }}
      loading={isPending && !data}
      error={error ? getErrorMessage(error, t, "js.dashboard.loadError") : ""}
      onReload={loadDashboard}
      filterHiddenEvents={false}
    />
  );
}
