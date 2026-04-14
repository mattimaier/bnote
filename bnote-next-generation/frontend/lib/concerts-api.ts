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

export interface EscalationRiskActionResult {
  status: string;
  warning?: EscalationWarning | null;
}

export const concertsApi = {
  list: (signal?: AbortSignal) => api.get<ConcertListItem[]>("concerts", "list", undefined, { signal }),
  acceptEscalationRisk: (id: number) =>
    api.post<EscalationRiskActionResult>("concerts", "acceptEscalationRisk", { id }),
  resetEscalationRisk: (id: number) =>
    api.post<EscalationRiskActionResult>("concerts", "resetEscalationRisk", { id }),
  delete: (id: number) => api.post<{ success: boolean }>("concerts", "delete", { id }),
};
