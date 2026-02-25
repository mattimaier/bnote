/**
 * BNote Next Generation - EditorJS notes helpers
 * Parse and render notes that may be EditorJS JSON or plain text.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import type { OutputData } from "@editorjs/editorjs";
import edjsHTML from "editorjs-html";

/** Check if a string looks like EditorJS JSON (has blocks array). */
export function isEditorJson(value: string): boolean {
  if (!value || typeof value !== "string") return false;
  const trimmed = value.trim();
  if (!trimmed || !trimmed.startsWith("{")) return false;
  try {
    const parsed = JSON.parse(trimmed) as { blocks?: unknown[] };
    return Array.isArray(parsed?.blocks);
  } catch {
    return false;
  }
}

/** Parse notes string to EditorJS initial data. Returns undefined for empty; single paragraph for plain text. */
export function parseNotesInitialData(value: string): { blocks?: unknown[] } | undefined {
  if (!value || typeof value !== "string") return undefined;
  const trimmed = value.trim();
  if (!trimmed) return undefined;
  if (trimmed.startsWith("{")) {
    try {
      const parsed = JSON.parse(trimmed) as { blocks?: unknown[] };
      if (Array.isArray(parsed?.blocks) && parsed.blocks.length > 0) return parsed;
    } catch {
      // fall through to plain text
    }
  }
  return { blocks: [{ type: "paragraph", data: { text: trimmed } }] };
}

/** Convert notes string to HTML if EditorJS JSON; otherwise return empty string (caller uses MarkdownText for legacy). */
export function notesEditorJsonToHtml(value: string): string {
  if (!isEditorJson(value)) return "";
  try {
    const parsed = JSON.parse(value.trim()) as OutputData;
    const parser = edjsHTML();
    const html = parser.parse(parsed);
    return typeof html === "string" ? html : "";
  } catch {
    return "";
  }
}
