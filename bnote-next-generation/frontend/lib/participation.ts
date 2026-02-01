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
}

export const participationApi = {
  getStatus: (eventId: string | number, eventType: string) =>
    api.get<ParticipationState>("participation", "get", {
      event_id: String(eventId),
      event_type: eventType,
    }),

  saveStatus: (
    eventId: string | number,
    eventType: string,
    status: ParticipationStatus,
    reason?: string
  ) =>
    api.post<unknown>("participation", "save", {
      event_id: String(eventId),
      event_type: eventType,
      status,
      reason: reason ?? "",
    }),
};
