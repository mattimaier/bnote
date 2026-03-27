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

export interface RehearsalSeriesSummary {
  id: number;
  name: string;
  firstSession: string;
  lastSession: string;
  rehearsalCount: number;
}

export interface CreateRehearsalSeriesPayload {
  name: string;
  cycle: 1 | 2;
  firstSession: string;
  lastSession: string;
  defaultTime: string;
  duration: number;
  status: string;
  location: number;
  conductor: number;
  notes: string;
  groupIds: number[];
  contacts: number[];
}

export interface CreateRehearsalSeriesResult {
  seriesId: number;
  createdCount: number;
}

export interface DeleteRehearsalSeriesResult {
  deletedCount: number;
}

export interface RehearsalSeriesDetail {
  id: number;
  name: string;
  cycle: 1 | 2;
  firstSession: string;
  lastSession: string;
  defaultTime: string;
  duration: number;
  status: string;
  location: number;
  conductor: number;
  notes: string;
  groupIds: number[];
  contacts?: number[];
  rehearsals: RehearsalListItem[];
}

export interface UpdateRehearsalSeriesPayload extends CreateRehearsalSeriesPayload {
  id: number;
}

export interface UpdateRehearsalSeriesResult {
  seriesId: number;
  updated: boolean;
  updatedRehearsals?: number;
  createdRehearsals?: number;
  removedRehearsals?: number;
  totalRehearsals?: number;
}

export const rehearsalsApi = {
  list: () => api.get<RehearsalListItem[]>("rehearsals", "list"),
  meta: () => api.get<Record<string, unknown>>("rehearsals", "meta"),
  listSeries: () => api.get<RehearsalSeriesSummary[]>("rehearsals", "list_series"),
  getSeries: (id: number) => api.get<RehearsalSeriesDetail>("rehearsals", "get_series", { id }),
  listBySeries: (seriesId: number) => api.get<RehearsalListItem[]>("rehearsals", "list_by_series", { seriesId }),
  createSeries: (payload: CreateRehearsalSeriesPayload) =>
    api.post<CreateRehearsalSeriesResult>("rehearsals", "create_series", payload as unknown as Record<string, unknown>),
  updateSeries: (payload: UpdateRehearsalSeriesPayload) =>
    api.post<UpdateRehearsalSeriesResult>(
      "rehearsals",
      "update_series",
      payload as unknown as Record<string, unknown>
    ),
  deleteSeries: (seriesId: number) =>
    api.post<DeleteRehearsalSeriesResult>("rehearsals", "delete_series", { seriesId }),
};
