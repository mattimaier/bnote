/**
 * BNote Next Generation - Users API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface User {
  id: number;
  login: string;
  firstName?: string;
  lastName?: string;
  name?: string;
  email?: string | null;
  isActive: boolean;
  lastlogin?: string | null;
}

export interface UserDetail {
  id: number;
  login: string;
  contact: number;
  isActive: boolean;
  lastlogin?: string | null;
  contactName?: string;
  contactFirstName?: string;
  contactSurname?: string;
  contactEmail?: string | null;
}

export interface ContactOption {
  id: number;
  label: string;
  name?: string;
  surname?: string;
  instrument?: string;
}

export interface PrivilegeModule {
  id: number;
  name: string;
  hasAccess: boolean;
}

export interface PrivilegesResponse {
  userId: number;
  privileges: number[];
  modules: PrivilegeModule[];
}

export const usersApi = {
  list: () => api.get<User[]>("users", "list"),
  get: (id: number) => api.get<UserDetail>("users", "get", { id: String(id) }),
  create: (data: { login: string; password: string; contact: number | string; isActive?: boolean }) =>
    api.post<{ success: boolean; id: number; message: string }>("users", "create", {
      ...data,
      contact: String(data.contact),
      isActive: data.isActive !== false,
    }),
  update: (id: number, data: { password?: string; contact?: number | string; isActive?: boolean }) =>
    api.post<{ success: boolean; message: string }>("users", "update", { id, ...data, contact: data.contact != null ? String(data.contact) : undefined }),
  delete: (id: number) => api.post<{ success: boolean; message: string }>("users", "delete", { id }),
  activate: (id: number) => api.post<{ success: boolean; message: string }>("users", "activate", { id }),
  getPrivileges: (id: number) => api.get<PrivilegesResponse>("users", "getPrivileges", { id: String(id) }),
  updatePrivileges: (id: number, privileges: number[]) =>
    api.post<{ success: boolean; message: string }>("users", "updatePrivileges", { id, privileges }),
  getContacts: () => api.get<ContactOption[]>("users", "getContacts"),
};
