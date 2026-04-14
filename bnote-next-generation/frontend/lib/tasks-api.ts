/**
 * BNote Next Generation - Tasks API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Task {
  id: number;
  title: string;
  description?: string;
  created_at?: string | null;
  due_at?: string | null;
  is_complete: boolean;
  completed_at?: string | null;
  assigned_to?: number | null;
  assignee?: string | null;
  assigneeName?: string | null;
  assigneeEmail?: string | null;
  creator?: string | null;
  tourIds?: number[];
}

export interface TaskCreate {
  title: string;
  description?: string;
  due_at?: string | null;
  assigned_to?: number | null;
  tour_id?: number | null;
}

export interface TaskUpdate {
  title?: string;
  description?: string;
  due_at?: string | null;
  assigned_to?: number | null;
}

export const tasksApi = {
  list: (params?: { open?: boolean; tour_id?: number }) =>
    api.get<Task[]>("tasks", "list", {
      open: params?.open !== false ? "1" : "0",
      ...(params?.tour_id != null ? { tour_id: String(params.tour_id) } : {}),
    }),
  get: (id: number, signal?: AbortSignal) =>
    api.get<Task>("tasks", "get", { id: String(id) }, { signal }),
  create: (data: TaskCreate) =>
    api.post<{ id: number; success: boolean }>("tasks", "create", data as unknown as Record<string, unknown>),
  update: (id: number, data: TaskUpdate) =>
    api.post<{ success: boolean }>("tasks", "update", { id, ...data } as unknown as Record<string, unknown>),
  delete: (id: number) =>
    api.post<{ success: boolean }>("tasks", "delete", { id }),
  complete: (id: number, complete: boolean) =>
    api.post<{ success: boolean; is_complete: boolean }>("tasks", "complete", {
      id,
      complete,
    }),
  createGroupTasks: (data: {
    groupIds: number[];
    title: string;
    description?: string;
    due_at?: string | null;
  }) =>
    api.post<{ success: boolean; created: number }>("tasks", "createGroupTasks", data as unknown as Record<string, unknown>),
  getContacts: () =>
    api.get<Array<{ id: number; name: string; email?: string | null; instrument?: string | null }>>("tasks", "getContacts"),
  getGroups: () =>
    api.get<Array<{ id: number; name: string }>>("tasks", "getGroups"),
  getTours: () =>
    api.get<Array<{ id: number; name: string }>>("tasks", "getTours"),
};
