/**
 * BNote Next Generation - Address helpers and detection
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { safeString } from "@/lib/string-utils";

export interface AddressParts {
  street?: string;
  zip?: string;
  city?: string;
  state?: string;
  country?: string;
}

const STREET_HINTS =
  /\b(street|st\.?|avenue|ave\.?|road|rd\.?|boulevard|blvd\.?|lane|ln\.?|drive|dr\.?|way|place|pl\.?|square|sq\.?|hwy|highway|strasse|gasse|weg|allee|platz|ring|hof)\b/i;

export function formatAddressParts(addr: AddressParts): string {
  if (!addr || typeof addr !== "object") return "";
  const street = safeString(addr.street);
  const zip = safeString(addr.zip);
  const city = safeString(addr.city);
  const state = safeString(addr.state);
  const country = safeString(addr.country);
  const parts = [street, [zip, city].filter(Boolean).join(" "), state, country].filter(Boolean);
  return parts.join(", ");
}

export function formatAddressPartsMultiline(addr: AddressParts): string {
  if (!addr || typeof addr !== "object") return "";
  const street = safeString(addr.street);
  const line2 = [safeString(addr.zip), safeString(addr.city)].filter(Boolean).join(" ");
  const state = safeString(addr.state);
  const country = safeString(addr.country);
  const parts = [street, line2, state, country].filter(Boolean);
  return parts.join("\n");
}

export function normalizeAddressText(text: string): string {
  return text
    .replace(/\s*\n+\s*/g, ", ")
    .replace(/\s*,\s*/g, ", ")
    .replace(/\s{2,}/g, " ")
    .trim();
}

export function isAddressLike(text: string): boolean {
  const hasLetter = /[A-Za-z]/.test(text);
  const hasDigit = /\d/.test(text);
  const hasZip = /\b\d{4,6}\b/.test(text);
  const hasComma = text.includes(",");
  const hasStreetHint = STREET_HINTS.test(text);
  return hasLetter && (hasStreetHint || (hasDigit && (hasZip || hasComma)));
}

export function getAddressInfo(value: AddressParts | string | null | undefined):
  | { formatted: string; query: string }
  | null {
  if (!value) return null;
  if (typeof value === "string") {
    const raw = value.trim();
    if (!raw) return null;
    const normalized = normalizeAddressText(raw);
    if (!isAddressLike(normalized)) return null;
    return { formatted: normalized, query: normalized };
  }
  const formatted = formatAddressParts(value);
  if (!formatted) return null;
  return { formatted, query: formatted };
}
