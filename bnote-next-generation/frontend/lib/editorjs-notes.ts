/**
 * BNote Next Generation - EditorJS notes helpers
 * Parse and render notes that may be EditorJS JSON or plain text.
 * Plain text is stored in DB for old app compatibility; see docs/RICH_NOTES.md.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import type { OutputData } from "@editorjs/editorjs";
import edjsHTML from "editorjs-html";

/** Extract plain text from one block for storage in DB (old app compatibility). */
function blockToPlainText(block: { type?: string; data?: Record<string, unknown> }): string {
  const d = block.data;
  if (!d) return "";
  switch (block.type) {
    case "paragraph":
    case "header":
      return typeof d.text === "string" ? d.text : "";
    case "quote":
      return typeof d.text === "string" ? d.text : (typeof d.caption === "string" ? d.caption : "");
    case "list":
      const items = Array.isArray(d.items) ? d.items : [];
      return items.map((item: unknown) => (typeof item === "string" ? item : "")).join("\n");
    case "code":
      return typeof d.code === "string" ? d.code : "";
    default:
      if (typeof d.text === "string") return d.text;
      return "";
  }
}

/** Convert EditorJS JSON (or OutputData) to plain text for the notes column (old app). */
export function editorJsonToPlainText(value: string | OutputData): string {
  if (value == null) return "";
  let data: OutputData;
  if (typeof value === "string") {
    const trimmed = value.trim();
    if (!trimmed || !trimmed.startsWith("{")) return "";
    try {
      data = JSON.parse(trimmed) as OutputData;
    } catch {
      return "";
    }
  } else {
    data = value;
  }
  const blocks = data?.blocks;
  if (!Array.isArray(blocks) || blocks.length === 0) return "";
  const lines = blocks.map((b) => blockToPlainText(b)).filter(Boolean);
  return lines.join("\n\n");
}

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
