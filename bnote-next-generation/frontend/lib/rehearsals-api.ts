/**
 * BNote Next Generation - Rehearsals API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface ParticipationStats {
  yes?: number;
  maybe?: number;
  no?: number;
  pending?: number;
  total?: number;
}

export interface RehearsalListItem {
  id: number;
  begin: string;
  end: string;
  approve_until?: string;
  location_name: string;
  notes?: string;
  status?: string;
  conductor?: number | null;
  participationStats?: ParticipationStats;
}

export const rehearsalsApi = {
  list: () => api.get<RehearsalListItem[]>("rehearsals", "list"),
};
