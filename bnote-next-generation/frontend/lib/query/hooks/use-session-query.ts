"use client";

import { useQuery } from "@tanstack/react-query";
import { checkSession } from "@/lib/auth";
import { queryKeys } from "@/lib/query/keys";
import { QUERY_STALE_TIMES } from "@/lib/query/stale-times";

export function useSessionQuery() {
  return useQuery({
    queryKey: queryKeys.auth.session,
    queryFn: ({ signal }) => checkSession(signal),
    staleTime: QUERY_STALE_TIMES.authSessionMs,
  });
}
