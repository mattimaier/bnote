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

export interface ContactGroupListItem extends ContactGroup {
  memberCount?: number;
}

export interface ContactsAccessProfile {
  canManageContacts: boolean;
  membersOnlyAccess: boolean;
  membersGroupId: number;
}

export interface IntegrationMemberRow {
  id: number;
  name: string;
  surname: string;
  nickname?: string;
  email?: string;
  instrumentname?: string;
  label: string;
}

export interface IntegrationEventRow {
  id: number;
  begin?: string;
  label?: string;
  name?: string;
  /** Concert title (rehearsals omit). */
  title?: string;
  location_name?: string;
  notes?: string;
  status?: string;
}

export interface IntegrationBundle {
  members: IntegrationMemberRow[];
  rehearsals: IntegrationEventRow[];
  phases: IntegrationEventRow[];
  concerts: IntegrationEventRow[];
  votes: IntegrationEventRow[];
}

export interface IntegrateResult {
  success: boolean;
  message?: string;
  created?: number;
  summaryMailsSent?: number;
  summaryMailsAttempted?: number;
  summaryMailsReason?: string;
  removed?: number;
  affected?: {
    rehearsals?: number;
    rehearsalphases?: number;
    concerts?: number;
    votes?: number;
  };
  errors?: string[];
}

export const contactsApi = {
  list: (group?: string | null) => api.get<Contact[]>("contacts", "list", group && group !== "all" ? { group } : {}),
  get: (id: number) => api.get<ContactDetail>("contacts", "get", { id: String(id) }),
  create: (data: Partial<ContactDetail> & { name?: string; surname?: string; nickname?: string; groups?: number[] }) =>
    api.post<{ success: boolean; id: number; message: string }>("contacts", "create", data as Record<string, unknown>),
  update: (id: number, data: Partial<ContactDetail> & { groups?: number[] }) =>
    api.post<{ success: boolean; message: string }>("contacts", "update", { id, ...data } as Record<string, unknown>),
  delete: (id: number) => api.post<{ success: boolean; message: string }>("contacts", "delete", { id }),
  getGroups: () => api.get<ContactGroup[]>("contacts", "getGroups"),
  listGroups: () => api.get<ContactGroupListItem[]>("contacts", "listGroups"),
  createGroup: (name: string, isActive = true) =>
    api.post<{ success: boolean; id: number; message: string }>("contacts", "createGroup", {
      name,
      is_active: isActive,
    }),
  updateGroup: (id: number, data: { name?: string; is_active?: boolean }) =>
    api.post<{ success: boolean; message: string }>("contacts", "updateGroup", { id, ...data }),
  deleteGroup: (id: number) => api.post<{ success: boolean; message: string }>("contacts", "deleteGroup", { id }),
  getGroupMembers: (id: number) =>
    api.get<Array<{ name: string; instrument?: string; notes?: string }>>("contacts", "getGroupMembers", {
      id: String(id),
    }),
  getAccessProfile: () => api.get<ContactsAccessProfile>("contacts", "getAccessProfile"),
  getIntegrationBundle: (groupId?: string | null) =>
    api.get<IntegrationBundle>(
      "contacts",
      "getIntegrationBundle",
      groupId != null && groupId !== "" ? { group: String(groupId) } : {}
    ),
  getRemovalBundle: (contactId: number) =>
    api.get<IntegrationBundle>("contacts", "getRemovalBundle", { contact: String(contactId) }),
  integrate: (body: {
    group?: string | null;
    members: number[];
    rehearsals: number[];
    /** Matches PHP `contacts::integrate` (`rehearsalphases`). */
    rehearsalphases: number[];
    concerts: number[];
    votes: number[];
  }) => api.post<IntegrateResult>("contacts", "integrate", { ...body }),
  bulkRemove: (body: {
    members: number[];
    rehearsals: number[];
    rehearsalphases: number[];
    concerts: number[];
    votes: number[];
  }) => api.post<IntegrateResult>("contacts", "bulkRemove", { ...body }),
};
