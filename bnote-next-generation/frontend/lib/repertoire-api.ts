/**
 * BNote Next Generation - Repertoire (Songs) API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "./api";

export interface Song {
  id: number;
  title: string;
  composer?: string;
  length?: string;
  bpm?: number | null;
  music_key?: string;
  genre?: string;
  status?: string;
  is_active?: boolean;
}

export interface SongDetail extends Omit<Song, "genre" | "status"> {
  genre?: number | null;
  genrename?: string;
  composer_id?: number | null;
  status?: number | null;
  statusname?: string;
  setting?: string;
  notes?: string;
}

export interface RepertoireMeta {
  genres: { id: number; name: string }[];
  statuses: { id: number; name: string }[];
  composers: { id: number; name: string }[];
}

export const repertoireApi = {
  list: (params?: { genre?: number; status?: number; is_active?: number; title?: string }) =>
    api.get<Song[]>("repertoire", "list", params as Record<string, string> | undefined),
  get: (id: number) => api.get<SongDetail>("repertoire", "get", { id: String(id) }),
  meta: () => api.get<RepertoireMeta>("repertoire", "meta"),
  create: (data: Partial<SongDetail>) =>
    api.post<{ success: boolean; id: number; message: string }>(
      "repertoire",
      "create",
      data as Record<string, unknown>
    ),
  update: (id: number, data: Partial<SongDetail>) =>
    api.post<{ success: boolean; message: string }>("repertoire", "update", { id, ...data } as Record<string, unknown>),
  delete: (id: number) =>
    api.post<{ success: boolean; message: string }>("repertoire", "delete", {
      id,
    }),
};
