/**
 * BNote Next Generation - Kontaktdaten (My Contact Data) API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface MyContactDetail {
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
  notes?: string;
  instrument?: number;
  instrumentname?: string;
  birthday?: string | null;
  street?: string;
  city?: string;
  zip?: string;
  share_address?: boolean;
  share_phones?: boolean;
  share_birthday?: boolean;
  share_email?: boolean;
  /** User preference: receive notification emails. */
  email_notification?: boolean;
}

export interface UserPreferences {
  email_notification: boolean;
}

export interface InstrumentOption {
  id: number;
  name: string;
}

export const kontaktdatenApi = {
  getMine: () => api.get<MyContactDetail | null>("kontaktdaten", "getMine"),
  updateMine: (data: Partial<MyContactDetail>) =>
    api.post<{ success: boolean; message: string }>("kontaktdaten", "updateMine", data as Record<string, unknown>),
  getUserPreferences: () => api.get<UserPreferences>("kontaktdaten", "getUserPreferences"),
  updateUserPreferences: (data: UserPreferences) =>
    api.post<{ success: boolean; message: string; email_notification?: boolean }>(
      "kontaktdaten",
      "updateUserPreferences",
      data as unknown as Record<string, unknown>
    ),
  getInstruments: () => api.get<InstrumentOption[]>("kontaktdaten", "getInstruments"),
};
