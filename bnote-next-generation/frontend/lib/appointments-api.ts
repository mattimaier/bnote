/**
 * BNote Next Generation - Appointments API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Appointment {
  id: number;
  begin: string;
  end: string;
  name: string;
  location: number | null;
  locationname?: string | null;
  contact: number | null;
  contactname?: string;
  notes: string;
  groups: number[];
}

export interface AppointmentCreate {
  name: string;
  begin: string;
  end: string;
  location?: number | null;
  contact?: number | null;
  notes?: string;
  groups?: number[];
}

export interface AppointmentUpdate {
  name?: string;
  begin?: string;
  end?: string;
  location?: number | null;
  contact?: number | null;
  notes?: string;
  groups?: number[];
}

const APPT_CACHE_KEY = "bnote-appointment-pending";

export function getPendingAppointment(id: number): Appointment | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = sessionStorage.getItem(`${APPT_CACHE_KEY}-${id}`);
    if (!raw) return null;
    const item = JSON.parse(raw) as Appointment;
    sessionStorage.removeItem(`${APPT_CACHE_KEY}-${id}`);
    return item;
  } catch {
    return null;
  }
}

export function setPendingAppointment(item: Appointment): void {
  if (typeof window === "undefined") return;
  try {
    sessionStorage.setItem(`${APPT_CACHE_KEY}-${item.id}`, JSON.stringify(item));
  } catch {
    // ignore
  }
}

export const appointmentsApi = {
  list: () => api.get<Appointment[]>("appointments", "list"),
  get: (id: number) =>
    api.get<Appointment>("appointments", "get", { id: String(id) }),
  create: (data: AppointmentCreate) =>
    api.post<{ id: number; success: boolean; item?: Appointment }>("appointments", "create", data as unknown as Record<string, unknown>),
  update: (id: number, data: AppointmentUpdate) =>
    api.post<{ success: boolean }>("appointments", "update", { id, ...data } as unknown as Record<string, unknown>),
  delete: (id: number) =>
    api.post<{ success: boolean }>("appointments", "delete", { id }),
};
