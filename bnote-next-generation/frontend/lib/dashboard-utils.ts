/**
 * BNote Next Generation - Dashboard helpers
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { useMemo } from "react";
import edjsHTML from "editorjs-html";

/**
 * Normalize company/band name from API response.
 * Handles string, array, or object formats (e.g., from SimpleXMLElement JSON encoding).
 */
export function normalizeCompany(val: unknown): string {
  if (val == null) return "";
  if (typeof val === "string") return val.trim();
  if (Array.isArray(val)) return normalizeCompany(val[0]);
  if (typeof val === "object") {
    const obj = val as Record<string, unknown>;
    const v = obj["0"] ?? obj["name"] ?? obj["value"] ?? Object.values(obj)[0];
    if (v == null) return "";
    if (typeof v === "string") return v.trim();
    return normalizeCompany(v);
  }
  return "";
}

/** Render news content: EditorJS JSON → HTML via editorjs-html; legacy → HTML (newlines to br). */
export function useNewsHtml(news: string | undefined): string {
  return useMemo(() => {
    if (!news || typeof news !== "string") return "";
    const trimmed = news.trim();
    if (!trimmed) return "";
    if (trimmed.startsWith("{")) {
      try {
        const parsed = JSON.parse(trimmed) as { blocks?: unknown[] };
        if (Array.isArray(parsed?.blocks)) {
          const parser = edjsHTML();
          const html = parser.parse(parsed as import("@editorjs/editorjs").OutputData);
          return typeof html === "string" ? html : "";
        }
      } catch {
        // fall through to legacy
      }
    }
    return trimmed.replace(/\n/g, "<br />\n");
  }, [news]);
}

export const MAX_SHOW_DEFAULT = 5;
