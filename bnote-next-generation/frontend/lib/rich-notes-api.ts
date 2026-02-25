/**
 * BNote Next Generation - Rich notes API client
 * GET/POST EditorJS JSON by entity. Plain text stays in entity notes for old app.
 * See docs/RICH_NOTES.md.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { api } from "@/lib/api";

export type RichNotesEntityType =
  | "rehearsal"
  | "concert"
  | "contact"
  | "song"
  | "equipment"
  | "location"
  | "program"
  | "rehearsal_song"
  | "news"
  | "concert_conditions";

/** Get rich notes content for an entity. Returns null if 404 (use plain notes). */
export async function getRichNotes(
  entityType: RichNotesEntityType,
  entityId: string
): Promise<string | null> {
  try {
    const data = await api.get<{ content: string }>("richnotes", "", {
      entity_type: entityType,
      entity_id: entityId,
    });
    return data?.content ?? null;
  } catch (err) {
    const e = err as { status?: number };
    if (e.status === 404) return null;
    throw err;
  }
}

/** Save rich notes content for an entity. */
export async function saveRichNotes(
  entityType: RichNotesEntityType,
  entityId: string,
  content: string
): Promise<void> {
  await api.post("richnotes", "", {
    entity_type: entityType,
    entity_id: entityId,
    content,
  });
}
