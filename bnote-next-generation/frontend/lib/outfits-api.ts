/**
 * BNote Next Generation - Outfits API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Outfit {
  id: number;
  name: string;
  description?: string;
}

export interface OutfitDetail extends Outfit {}

export const outfitsApi = {
  list: () => api.get<Outfit[]>("outfits", "list"),
  get: (id: number) => api.get<OutfitDetail>("outfits", "get", { id: String(id) }),
  create: (data: Partial<OutfitDetail>) =>
    api.post<{ success: boolean; id: number; message: string }>("outfits", "create", data as Record<string, unknown>),
  update: (id: number, data: Partial<OutfitDetail>) =>
    api.post<{ success: boolean; message: string }>("outfits", "update", { id, ...data } as Record<string, unknown>),
  delete: (id: number) =>
    api.post<{ success: boolean; message: string }>("outfits", "delete", {
      id,
    }),
};
