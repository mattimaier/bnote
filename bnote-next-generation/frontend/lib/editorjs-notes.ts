/**
 * BNote Next Generation - EditorJS notes helpers
 * Parse and render notes that may be EditorJS JSON or plain text.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import type { OutputData } from "@editorjs/editorjs";
import edjsHTML from "editorjs-html";
import { sanitizeUntrustedHtml } from "@/lib/html-sanitizer";

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

/** True if value is EditorJS JSON with no blocks (empty document). Normalize to "" so it is never stored or shown. */
export function isEmptyEditorJson(value: string): boolean {
  if (!value || typeof value !== "string") return false;
  const trimmed = value.trim();
  if (!trimmed || !trimmed.startsWith("{")) return false;
  try {
    const parsed = JSON.parse(trimmed) as { blocks?: unknown[] };
    return Array.isArray(parsed?.blocks) && parsed.blocks.length === 0;
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
      if (Array.isArray(parsed?.blocks)) {
        if (parsed.blocks.length > 0) return parsed;
        return undefined;
      }
    } catch {
      // fall through to plain text
    }
  }
  return { blocks: [{ type: "paragraph", data: { text: trimmed } }] };
}

/**
 * Extract plain text from notes (EditorJS JSON or plain string).
 * Use for titles, previews, list cells where raw JSON must not be shown.
 */
export function notesToPlainText(value: string): string {
  if (!value || typeof value !== "string") return "";
  const trimmed = value.trim();
  if (!trimmed) return "";
  if (!trimmed.startsWith("{")) return trimmed;
  try {
    const parsed = JSON.parse(trimmed) as { blocks?: Array<{ type?: string; data?: Record<string, unknown> }> };
    const blocks = parsed?.blocks;
    if (!Array.isArray(blocks) || blocks.length === 0) return "";
    const parts: string[] = [];
    for (const block of blocks) {
      const d = block?.data;
      if (!d || typeof d !== "object") continue;
      if (block.type === "paragraph" || block.type === "header") {
        const t = d.text;
        if (typeof t === "string" && t.trim()) parts.push(stripHtml(t));
      } else if (block.type === "list") {
        const items = d.items;
        if (Array.isArray(items)) {
          for (const it of items) {
            if (typeof it === "string" && it.trim()) parts.push(stripHtml(it));
          }
        }
      } else if (block.type === "quote") {
        const t = d.text;
        if (typeof t === "string" && t.trim()) parts.push(stripHtml(t));
      } else if (block.type === "code") {
        const c = d.code;
        if (typeof c === "string" && c.trim()) parts.push(c);
      }
    }
    return parts.join(" ").trim();
  } catch {
    return trimmed;
  }
}

function stripHtml(html: string): string {
  if (typeof document === "undefined") {
    return html
      .replace(/<[^>]*>/g, "")
      .replace(/&nbsp;/g, " ")
      .replace(/&amp;/g, "&")
      .replace(/&lt;/g, "<")
      .replace(/&gt;/g, ">")
      .replace(/&quot;/g, '"');
  }
  const div = document.createElement("div");
  div.innerHTML = html;
  return (div.textContent ?? div.innerText ?? "").trim();
}

/** Convert notes string to HTML if EditorJS JSON; otherwise return empty string (caller uses MarkdownText for legacy). */
export function notesEditorJsonToHtml(value: string): string {
  if (!isEditorJson(value)) return "";
  try {
    const parsed = JSON.parse(value.trim()) as OutputData;
    const parser = edjsHTML();
    const html = parser.parse(parsed);
    return typeof html === "string" ? sanitizeUntrustedHtml(html) : "";
  } catch {
    return "";
  }
}
