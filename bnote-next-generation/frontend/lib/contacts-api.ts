/**
 * BNote Next Generation - Contacts API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Contact {
  id: number;
  name: string;
  surname: string;
  nickname?: string;
  company?: string;
  email?: string;
  phone?: string;
  mobile?: string;
  business?: string;
  web?: string;
  instrumentname?: string;
  instrument?: number;
  birthday?: string | null;
  status?: string;
  street?: string;
  city?: string;
  zip?: string;
  notes?: string;
  address?: number;
}

export interface ContactDetail extends Contact {
  groups?: number[];
  share_address?: boolean;
  share_phones?: boolean;
  share_birthday?: boolean;
  share_email?: boolean;
  is_conductor?: boolean;
}

export interface ContactGroup {
  id: number;
  name: string;
  is_active?: boolean;
}

export const contactsApi = {
  list: (group?: string | null) =>
    api.get<Contact[]>("contacts", "list", group && group !== "all" ? { group } : {}),
  get: (id: number) => api.get<ContactDetail>("contacts", "get", { id: String(id) }),
  create: (data: Partial<ContactDetail> & { name?: string; surname?: string; nickname?: string; groups?: number[] }) =>
    api.post<{ success: boolean; id: number; message: string }>("contacts", "create", data as Record<string, unknown>),
  update: (id: number, data: Partial<ContactDetail> & { groups?: number[] }) =>
    api.post<{ success: boolean; message: string }>("contacts", "update", { id, ...data } as Record<string, unknown>),
  delete: (id: number) => api.post<{ success: boolean; message: string }>("contacts", "delete", { id }),
  getGroups: () => api.get<ContactGroup[]>("contacts", "getGroups"),
};
