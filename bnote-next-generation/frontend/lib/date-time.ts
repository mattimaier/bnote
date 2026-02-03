/**
 * BNote Next Generation - Date/time formatting helpers
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { format, isValid, parseISO } from "date-fns";
import { de, enUS, es, fr } from "date-fns/locale";

const LOCALE_MAP: Record<string, Locale> = {
  de,
  en: enUS,
  es,
  fr,
};

function resolveLocale(lang: string | undefined): Locale {
  if (!lang) return enUS;
  const key = lang.split("-")[0]?.toLowerCase();
  return (key && LOCALE_MAP[key]) || enUS;
}

function toDate(value: string | Date | null | undefined): Date | null {
  if (!value) return null;
  if (value instanceof Date) return isValid(value) ? value : null;
  const raw = new Date(value);
  if (isValid(raw)) return raw;
  const parsed = parseISO(value);
  return isValid(parsed) ? parsed : null;
}

export function formatDateValue(
  value: string | Date | null | undefined,
  lang: string,
  pattern: string
): string | null {
  const date = toDate(value);
  if (!date) return null;
  return format(date, pattern, { locale: resolveLocale(lang) });
}

export function formatDateShort(value: string | Date | null | undefined, lang: string): string | null {
  return formatDateValue(value, lang, "P");
}

export function formatTimeShort(value: string | Date | null | undefined, lang: string): string | null {
  return formatDateValue(value, lang, "p");
}

export function formatDateTimeShort(value: string | Date | null | undefined, lang: string): string | null {
  return formatDateValue(value, lang, "Pp");
}

export function formatMonthName(monthIndex: number, lang: string): string {
  const safeMonth = Number.isFinite(monthIndex) ? monthIndex : 1;
  const date = new Date(2000, Math.max(1, Math.min(12, safeMonth)) - 1, 1);
  return format(date, "LLLL", { locale: resolveLocale(lang) });
}
