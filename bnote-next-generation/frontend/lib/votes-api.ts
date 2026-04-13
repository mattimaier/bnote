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

export interface VoteAssignableVoter {
  id: number;
  name: string;
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
  can_edit?: boolean;
  is_active: boolean;
  options: VoteOption[];
  result?: unknown;
  /** Current user's choices: optionId -> "yes"|"no"|"maybe" */
  user_choices?: Record<number, string>;
}

export const votesApi = {
  list: (params?: { active?: string }) =>
    api.get<Vote[]>("votes", "list", params ? { active: params.active ?? "" } : {}),
  get: (id: number) =>
    api.get<VoteDetail>("votes", "get", { id: String(id) }),
  getVoters: (id: number) =>
    api.get<
      Array<{
        instrument: { id: number; name: string; category: { id: number; name: string } };
        participants: Array<{
          id: number;
          userId?: number;
          name: string;
          email?: string | null;
          participate: number | null;
          reason?: string | null;
        }>;
        stats: { yes: number; maybe: number; no: number; pending: number };
      }>
    >("votes", "getVoters", { id: String(id) }),
  create: (data: { name: string; end: string; is_date: boolean; is_multi: boolean; groups?: number[] }) =>
    api.post<{ success: boolean; id: number; message: string }>(
      "votes",
      "create",
      data as Record<string, unknown>
    ),
  update: (id: number, data: { name?: string; end?: string; is_finished?: boolean }) =>
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
    data: { choices?: Record<number, string>; uservote?: number | null }
  ) =>
    api.post<{ success: boolean; message: string }>("votes", "submit", {
      vote_id: voteId,
      ...data,
    } as Record<string, unknown>),
  getAssignableVoters: (id: number) =>
    api.get<VoteAssignableVoter[]>("votes", "getAssignableVoters", { id: String(id) }),
  getAssignedVoters: (id: number) =>
    api.get<VoteAssignableVoter[]>("votes", "getAssignedVoters", { id: String(id) }),
  addVoters: (voteId: number, userIds: number[]) =>
    api.post<{ success: boolean; message: string; added: number }>(
      "votes",
      "addVoters",
      { id: voteId, user_ids: userIds } as Record<string, unknown>
    ),
  removeVoters: (voteId: number, userIds: number[]) =>
    api.post<{ success: boolean; message: string; removed: number }>(
      "votes",
      "removeVoters",
      { id: voteId, user_ids: userIds } as Record<string, unknown>
    ),
  setVoters: (voteId: number, userIds: number[]) =>
    api.post<{ success: boolean; message: string; added: number; removed: number }>(
      "votes",
      "setVoters",
      { id: voteId, user_ids: userIds } as Record<string, unknown>
    ),
};
