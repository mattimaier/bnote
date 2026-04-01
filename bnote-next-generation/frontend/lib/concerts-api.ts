/**
 * BNote Next Generation - Concerts API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";
import type { EscalationWarning } from "@/lib/entities/event/types";

export interface ParticipationStats {
  yes?: number;
  maybe?: number;
  no?: number;
  pending?: number;
  total?: number;
}

export interface ConcertListItem {
  id: number;
  title: string;
  begin: string;
  end: string;
  approve_until?: string;
  location_name: string;
  notes?: string;
  status?: string;
  participationStats?: ParticipationStats;
  escalationWarning?: EscalationWarning | null;
}

export const concertsApi = {
  list: () => api.get<ConcertListItem[]>("concerts", "list"),
  delete: (id: number) => api.post<{ success: boolean }>("concerts", "delete", { id }),
};
