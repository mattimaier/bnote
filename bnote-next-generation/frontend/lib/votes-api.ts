/**
 * BNote Next Generation - Votes API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface VoteOption {
  id: number;
  name?: string;
  odate?: string | null;
}

export interface Vote {
  id: number;
  name: string;
  end: string;
  is_date: boolean;
  is_multi: boolean;
  is_finished?: boolean;
}

export interface VoteDetail extends Vote {
  author?: number | null;
  is_author: boolean;
  is_active: boolean;
  options: VoteOption[];
  result?: unknown;
}

export const votesApi = {
  list: (params?: { active?: string }) =>
    api.get<Vote[]>("votes", "list", params ? { active: params.active ?? "" } : {}),
  get: (id: number) =>
    api.get<VoteDetail>("votes", "get", { id: String(id) }),
  create: (data: { name: string; end: string; is_date: boolean; is_multi: boolean; groups?: number[] }) =>
    api.post<{ success: boolean; id: number; message: string }>(
      "votes",
      "create",
      data as Record<string, unknown>
    ),
  update: (id: number, data: { name?: string; end?: string }) =>
    api.post<{ success: boolean; message: string }>(
      "votes",
      "update",
      { id, ...data } as Record<string, unknown>
    ),
  delete: (id: number) =>
    api.post<{ success: boolean; message: string }>("votes", "delete", {
      id,
    }),
  addOption: (voteId: number, data: { name?: string; odate?: string }) =>
    api.post<{ success: boolean; id: number; message: string }>(
      "votes",
      "addOption",
      { vote_id: voteId, ...data } as Record<string, unknown>
    ),
  removeOption: (optionId: number) =>
    api.post<{ success: boolean; message: string }>("votes", "removeOption", {
      option_id: optionId,
    }),
  finish: (id: number) =>
    api.post<{ success: boolean; message: string }>("votes", "finish", {
      id,
    }),
  submit: (
    voteId: number,
    data: { choices?: Record<number, string>; uservote?: number }
  ) =>
    api.post<{ success: boolean; message: string }>("votes", "submit", {
      vote_id: voteId,
      ...data,
    } as Record<string, unknown>),
};
