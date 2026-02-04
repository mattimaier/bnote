/**
 * BNote Next Generation - Equipment API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Equipment {
  id: number;
  name: string;
  make?: string;
  model?: string;
  quantity?: number | null;
  purchase_price?: string | null;
  current_value?: string | null;
  notes?: string;
}

export interface EquipmentDetail extends Equipment {}

export const equipmentApi = {
  list: () => api.get<Equipment[]>("equipment", "list"),
  get: (id: number) =>
    api.get<EquipmentDetail>("equipment", "get", { id: String(id) }),
  create: (data: Partial<EquipmentDetail>) =>
    api.post<{ success: boolean; id: number; message: string }>(
      "equipment",
      "create",
      data as Record<string, unknown>
    ),
  update: (id: number, data: Partial<EquipmentDetail>) =>
    api.post<{ success: boolean; message: string }>(
      "equipment",
      "update",
      { id, ...data } as Record<string, unknown>
    ),
  delete: (id: number) =>
    api.post<{ success: boolean; message: string }>("equipment", "delete", {
      id,
    }),
};
