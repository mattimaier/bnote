/**
 * BNote Next Generation - Participation API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export type ParticipationStatus = "yes" | "maybe" | "no" | "undecided";

export interface ParticipationState {
  status: ParticipationStatus;
  allow_maybe: boolean;
  is_locked: boolean;
  reason?: string | null;
  deadline?: string | null;
}

export interface ParticipationBatchItem {
  event_id: number;
  event_type: "R" | "C";
}

export const participationApi = {
  getStatus: (eventId: string | number, eventType: string, signal?: AbortSignal) =>
    api.get<ParticipationState>(
      "participation",
      "get",
      {
        event_id: String(eventId),
        event_type: eventType,
      },
      { signal }
    ),

  batchGet: async (events: ParticipationBatchItem[], signal?: AbortSignal) => {
    const res = await api.post<{ items: Record<string, ParticipationState> }>(
      "participation",
      "batchGet",
      { events },
      { signal }
    );
    return res?.items ?? {};
  },

  saveStatus: (
    eventId: string | number,
    eventType: string,
    status: ParticipationStatus,
    reason?: string,
    signal?: AbortSignal
  ) =>
    api.post<unknown>(
      "participation",
      "save",
      {
        event_id: String(eventId),
        event_type: eventType,
        status,
        reason: reason ?? "",
      },
      { signal }
    ),
};
