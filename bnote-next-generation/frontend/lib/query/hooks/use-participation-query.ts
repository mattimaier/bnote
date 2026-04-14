"use client";

import { keepPreviousData, useQuery, useQueryClient } from "@tanstack/react-query";
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
  return [...dedup.values()];
}

export function useParticipationStatusQuery(
  eventId: number,
  eventType: "R" | "C",
  enabled: boolean,
  initialState?: ParticipationState
) {
  return useQuery({
    queryKey: queryKeys.participation.status(eventType, eventId),
    queryFn: ({ signal }) => participationApi.getStatus(eventId, eventType, signal),
    enabled,
    staleTime: QUERY_STALE_TIMES.participationMs,
    initialData: initialState,
    placeholderData: keepPreviousData,
  });
}

export function usePrefetchParticipationBatch(refs: ParticipationEventRef[], enabled: boolean) {
  const queryClient = useQueryClient();
  const normalized = normalize(refs);
  const eventKeys = normalized.map((x) => `${x.eventType}:${x.eventId}`).sort();
  const batchEnabled = process.env.NEXT_PUBLIC_ENABLE_PARTICIPATION_BATCH !== "0";
  return useQuery({
    queryKey: queryKeys.participation.batch(eventKeys),
    enabled: batchEnabled && enabled && normalized.length > 0,
    staleTime: QUERY_STALE_TIMES.participationMs,
    queryFn: async ({ signal }) => {
      const payload: ParticipationBatchItem[] = normalized.map((x) => ({ event_id: x.eventId, event_type: x.eventType }));
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
