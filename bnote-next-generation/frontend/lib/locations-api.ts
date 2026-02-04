/**
 * BNote Next Generation - Locations API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Location {
  id: number;
  name: string;
  notes?: string;
  address?: number | null;
  location_type?: number | null;
  street?: string;
  city?: string;
  zip?: string;
  state?: string;
  country?: string;
}

export interface LocationDetail extends Location {}

export interface ParticipationStats {
  yes?: number;
  maybe?: number;
  no?: number;
  pending?: number;
  total?: number;
}

export interface LocationEventItem {
  type: "rehearsal" | "concert";
  id: number;
  begin: string;
  end: string;
  title: string;
  status?: string;
  notes?: string;
  participationStats?: ParticipationStats;
}

export const locationsApi = {
  list: () => api.get<Location[]>("locations", "list"),
  get: (id: number) =>
    api.get<LocationDetail>("locations", "get", { id: String(id) }),
  getEvents: (id: number) =>
    api.get<LocationEventItem[]>("locations", "events", { id: String(id) }),
  create: (data: Partial<LocationDetail>) =>
    api.post<{ success: boolean; id: number; message: string }>(
      "locations",
      "create",
      data as Record<string, unknown>
    ),
  update: (id: number, data: Partial<LocationDetail>) =>
    api.post<{ success: boolean; message: string }>(
      "locations",
      "update",
      { id, ...data } as Record<string, unknown>
    ),
  delete: (id: number) =>
    api.post<{ success: boolean; message: string }>("locations", "delete", {
      id,
    }),
};
