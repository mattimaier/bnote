"use client";

import { keepPreviousData, useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo } from "react";
import { participationApi, type ParticipationBatchItem, type ParticipationState } from "@/lib/participation";
import { queryKeys } from "@/lib/query/keys";
import { QUERY_STALE_TIMES } from "@/lib/query/stale-times";

export interface ParticipationEventRef {
  eventId: number;
  eventType: "R" | "C";
}

function normalize(refs: ParticipationEventRef[]): ParticipationEventRef[] {
  const dedup = new Map<string, ParticipationEventRef>();
  for (const ref of refs) {
    if (!ref?.eventId || (ref.eventType !== "R" && ref.eventType !== "C")) continue;
    dedup.set(`${ref.eventType}:${ref.eventId}`, ref);
  }
  return [...dedup.values()].sort((a, b) => {
    const typeOrder = a.eventType.localeCompare(b.eventType);
    if (typeOrder !== 0) return typeOrder;
    return a.eventId - b.eventId;
  });
}

export interface ParticipationStatusQueryOptions {
  cacheFirst?: boolean;
}

export function useParticipationStatusQuery(
  eventId: number,
  eventType: "R" | "C",
  enabled: boolean,
  initialState?: ParticipationState,
  options: ParticipationStatusQueryOptions = {}
) {
  const queryClient = useQueryClient();
  const queryKey = queryKeys.participation.status(eventType, eventId);
  const staleTime = QUERY_STALE_TIMES.participationMs;
  const useCacheFirst = options.cacheFirst === true;
  return useQuery({
    queryKey,
    queryFn: ({ signal }) => {
      if (useCacheFirst) {
        const state = queryClient.getQueryState<ParticipationState>(queryKey);
        const cached = state?.data;
        const age = state?.dataUpdatedAt ? Date.now() - state.dataUpdatedAt : Number.POSITIVE_INFINITY;
        if (cached && age <= staleTime) {
          return cached;
        }
      }
      return participationApi.getStatus(eventId, eventType, signal);
    },
    enabled,
    staleTime,
    initialData: initialState,
    refetchOnMount: useCacheFirst ? false : true,
    placeholderData: keepPreviousData,
  });
}

export function usePrefetchParticipationBatch(refs: ParticipationEventRef[], enabled: boolean) {
  const queryClient = useQueryClient();
  const normalized = useMemo(() => normalize(refs), [refs]);
  const eventKeys = normalized.map((x) => `${x.eventType}:${x.eventId}`).sort();
  const batchEnabled = process.env.NEXT_PUBLIC_ENABLE_PARTICIPATION_BATCH !== "0";
  return useQuery({
    queryKey: queryKeys.participation.batch(eventKeys),
    enabled: batchEnabled && enabled && normalized.length > 0,
    staleTime: QUERY_STALE_TIMES.participationMs,
    queryFn: async ({ signal }) => {
      const payload: ParticipationBatchItem[] = normalized.map((x) => ({
        event_id: x.eventId,
        event_type: x.eventType,
      }));
      const items = await participationApi.batchGet(payload, signal);
      for (const [key, value] of Object.entries(items)) {
        const [type, idRaw] = key.split(":");
        const id = Number(idRaw);
        if ((type === "R" || type === "C") && Number.isFinite(id) && id > 0) {
          queryClient.setQueryData<ParticipationState>(queryKeys.participation.status(type, id), value);
        }
      }
      return items;
    },
  });
}
