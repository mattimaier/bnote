/**
 * BNote Next Generation - String helpers for API values
 * Ensures we never render [object Object] when API returns nested objects (e.g. SimpleXMLElement).
 *
 * Copyright (C) 2026 BNote Contributors
 */

/**
 * Coerce a value to a display string. Handles string, array, or object (recurses into nested objects).
 */
export function safeString(val: unknown): string {
  if (val == null) return "";
  if (typeof val === "string") return val.trim();
  if (Array.isArray(val)) return safeString(val[0]);
  if (typeof val === "object") {
    const obj = val as Record<string, unknown>;
    const v = obj["0"] ?? obj["name"] ?? obj["value"] ?? Object.values(obj)[0];
    if (v == null) return "";
    if (typeof v === "string") return v.trim();
    return safeString(v);
  }
  return String(val);
}
