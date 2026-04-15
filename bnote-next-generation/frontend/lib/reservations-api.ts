/**
 * BNote Next Generation - Reservations API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Reservation {
  id: number;
  begin: string;
  end: string;
  name: string;
  location: number | null;
  locationname?: string | null;
  contact: number | null;
  contactname?: string;
  contactFirstName?: string | null;
  contactSurname?: string | null;
  contactEmail?: string | null;
  notes: string;
}

export interface ReservationCreate {
  name: string;
  begin: string;
  end: string;
  location?: number | null;
  contact?: number | null;
  notes?: string;
}

export interface ReservationUpdate {
  name?: string;
  begin?: string;
  end?: string;
  location?: number | null;
  contact?: number | null;
  notes?: string;
}

export const reservationsApi = {
  list: () => api.get<Reservation[]>("reservations", "list"),
  get: (id: number) => api.get<Reservation>("reservations", "get", { id: String(id) }),
  create: (data: ReservationCreate) =>
    api.post<{ id: number; success: boolean }>("reservations", "create", data as unknown as Record<string, unknown>),
  update: (id: number, data: ReservationUpdate) =>
    api.post<{ success: boolean }>("reservations", "update", { id, ...data } as unknown as Record<string, unknown>),
  delete: (id: number) => api.post<{ success: boolean }>("reservations", "delete", { id }),
};
