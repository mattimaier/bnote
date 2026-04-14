/**
 * BNote Next Generation - Event detail data loading hook
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { api } from "@/lib/api";
import { getErrorMessage } from "@/lib/error-utils";
import type { ConcertMeta, RehearsalMeta } from "@/lib/entities/event/types";
import { queryKeys } from "@/lib/query/keys";
import { QUERY_STALE_TIMES } from "@/lib/query/stale-times";

export type TranslateFn = (key: string) => string;

export interface UseEventDetailDataResult {
  data: Record<string, unknown> | null;
  meta: RehearsalMeta | ConcertMeta | null;
  loading: boolean;
  isInitialLoading: boolean;
  isFetching: boolean;
  isResolvedNotFound: boolean;
  error: string;
  setError: (s: string) => void;
  reload: () => Promise<void>;
  loadMeta: () => Promise<void>;
}

export function useEventDetailData(
  type: string | undefined,
  id: string | undefined,
  initialData: Record<string, unknown> | undefined | null,
  ready: boolean,
  t: TranslateFn
): UseEventDetailDataResult {
  const [manualError, setManualError] = useState("");

  const moduleKey = type === "rehearsal" ? "rehearsals" : "concerts";
  const isNew = id === "new";
  const numId = id && !isNew ? parseInt(String(id), 10) : NaN;

  const detailEnabled = ready && !initialData && !isNew && Boolean(type) && Boolean(id) && !isNaN(numId);
  const detailQuery = useQuery({
    queryKey: queryKeys.entities.eventDetail(moduleKey, numId),
    queryFn: ({ signal }) => api.get<Record<string, unknown>>(moduleKey, "", { id: String(numId) }, { signal }),
    enabled: detailEnabled,
    staleTime: QUERY_STALE_TIMES.entityDetailMs,
    placeholderData: keepPreviousData,
  });

  const canEdit = isNew || Boolean((detailQuery.data as { canEdit?: boolean } | null)?.canEdit);
  const metaQuery = useQuery({
    queryKey: queryKeys.entities.eventMeta(moduleKey),
    queryFn: ({ signal }) => api.get<RehearsalMeta | ConcertMeta>(moduleKey, "meta", undefined, { signal }),
    enabled: false,
    staleTime: QUERY_STALE_TIMES.entityDetailMs,
  });

  const data =
    initialData ?? (isNew ? ({ canEdit: true, canEditParticipation: true } as Record<string, unknown>) : detailQuery.data ?? null);
  const queryError = detailQuery.error ? getErrorMessage(detailQuery.error, t, "js.common.failedToLoad") : "";
  const error = manualError || queryError;
  const isInitialLoading = detailEnabled ? detailQuery.isPending && !detailQuery.data : !ready && !initialData;
  const isFetching = detailQuery.isFetching;
  const isResolvedNotFound =
    !isInitialLoading &&
    !data &&
    Boolean(
      (detailQuery.error as { status?: number } | null)?.status === 404
    );

  return {
    data,
    meta: (metaQuery.data as RehearsalMeta | ConcertMeta | null) ?? null,
    loading: isInitialLoading,
    isInitialLoading,
    isFetching,
    isResolvedNotFound,
    error,
    setError: setManualError,
    reload: async () => {
      await detailQuery.refetch();
    },
    loadMeta: async () => {
      if (!ready || !canEdit) return;
      await metaQuery.refetch();
    },
  };
}
