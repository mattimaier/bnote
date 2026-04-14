"use client";

import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { concertsApi } from "@/lib/concerts-api";
import { rehearsalsApi } from "@/lib/rehearsals-api";
import { queryKeys } from "@/lib/query/keys";
import { QUERY_STALE_TIMES } from "@/lib/query/stale-times";

export function useRehearsalsListQuery(enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.lists.rehearsals,
    queryFn: ({ signal }) => rehearsalsApi.list(signal),
    enabled,
    staleTime: QUERY_STALE_TIMES.listMs,
    placeholderData: keepPreviousData,
  });
}

export function useConcertsListQuery(enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.lists.concerts,
    queryFn: ({ signal }) => concertsApi.list(signal),
    enabled,
    staleTime: QUERY_STALE_TIMES.listMs,
    placeholderData: keepPreviousData,
  });
}
