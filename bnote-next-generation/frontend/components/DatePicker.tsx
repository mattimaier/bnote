/**
 * BNote Next Generation - Date/Time picker (Flatpickr + FlyonUI)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useRef } from "react";
import flatpickr from "flatpickr";
import { German } from "flatpickr/dist/l10n/de";
import { Spanish } from "flatpickr/dist/l10n/es";
import { French } from "flatpickr/dist/l10n/fr";

const LOCALE_MAP: Record<string, object | undefined> = {
  de: German,
  en: undefined,
  es: Spanish,
  fr: French,
};

/** Locale-specific display format (matches date-fns P / BNote conventions) */
const ALT_FORMAT_BY_LOCALE: Record<string, string> = {
  de: "d.m.Y",
  en: "n/j/Y",
  es: "d/m/Y",
  fr: "d/m/Y",
};

/** Locale-specific placeholders for date/time inputs */
const PLACEHOLDER_BY_LOCALE: Record<string, { date: string; time: string; datetime: string }> = {
  de: { date: "TT.MM.JJJJ", time: "HH:MM", datetime: "TT.MM.JJJJ HH:MM" },
  en: { date: "MM/DD/YYYY", time: "HH:MM", datetime: "MM/DD/YYYY HH:MM" },
  es: { date: "DD/MM/YYYY", time: "HH:MM", datetime: "DD/MM/YYYY HH:MM" },
  fr: { date: "JJ/MM/AAAA", time: "HH:MM", datetime: "JJ/MM/AAAA HH:MM" },
};

export type DatePickerMode = "date" | "time" | "datetime";

export interface DatePickerProps {
  value: string;
  onChange: (value: string) => void;
  mode: DatePickerMode;
  placeholder?: string;
  className?: string;
  disabled?: boolean;
  id?: string;
  locale?: string;
  /** For datetime mode, if true outputs YYYY-MM-DD HH:mm:00 instead of YYYY-MM-DD HH:mm */
  appendSeconds?: boolean;
}

function getBaseOptions(mode: DatePickerMode, locale?: string) {
  const langKey = locale?.split("-")[0]?.toLowerCase() ?? "en";
  const fpLocale = LOCALE_MAP[langKey] ?? undefined;
  const altFmt = ALT_FORMAT_BY_LOCALE[langKey] ?? "Y-m-d";

  const opts: Parameters<typeof flatpickr>[1] = {
    allowInput: true,
    monthSelectorType: "static",
    locale: fpLocale,
  };

  switch (mode) {
    case "date":
      return { ...opts, dateFormat: "Y-m-d", altInput: true, altFormat: altFmt };
    case "time":
      return {
        ...opts,
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
      };
    case "datetime":
      return {
        ...opts,
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: `${altFmt} H:i`,
      };
  }
}

export function DatePicker({
  value,
  onChange,
  mode,
  placeholder,
  className,
  disabled,
  id,
  locale,
  appendSeconds = false,
}: DatePickerProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const fpRef = useRef<flatpickr.Instance | null>(null);
  const onChangeRef = useRef(onChange);

  useEffect(() => {
    onChangeRef.current = onChange;
  }, [onChange]);

  useEffect(() => {
    const el = inputRef.current;
    if (!el) return;

    const base = getBaseOptions(mode, locale);
    fpRef.current = flatpickr(el, {
      ...base,
      ...(base.altInput && { altInputClass: className ?? "input max-w-sm" }),
      defaultDate: value || undefined,
      closeOnSelect: true,
      onChange: (_dates, dateStr, instance) => {
        if (!dateStr) {
          onChangeRef.current("");
          return;
        }
        if (mode === "datetime" && appendSeconds && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(dateStr)) {
          onChangeRef.current(dateStr + ":00");
        } else {
          onChangeRef.current(dateStr);
        }
        if (mode === "date" || mode === "time") {
          instance.close();
        } else if (mode === "datetime" && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/.test(dateStr)) {
          instance.close();
        }
      },
    });

    if (base.altInput) {
      el.style.position = "absolute";
      el.style.width = "1px";
      el.style.height = "1px";
      el.style.padding = "0";
      el.style.margin = "-1px";
      el.style.overflow = "hidden";
      el.style.clip = "rect(0,0,0,0)";
      el.style.whiteSpace = "nowrap";
      el.style.borderWidth = "0";
    }

    return () => {
      el.style.cssText = "";
      fpRef.current?.destroy();
      fpRef.current = null;
    };
  }, [mode, locale]); // eslint-disable-line react-hooks/exhaustive-deps -- init only on mode/locale

  // Sync value when it changes externally
  useEffect(() => {
    const fp = fpRef.current;
    if (!fp) return;
    if (value) {
      fp.setDate(value, false);
    } else {
      fp.clear();
    }
  }, [value]);

  useEffect(() => {
    const fp = fpRef.current;
    if (!fp?.input) return;
    fp.input.disabled = !!disabled;
  }, [disabled]);

  const langKey = locale?.split("-")[0]?.toLowerCase() ?? "en";
  const localePlaceholders = PLACEHOLDER_BY_LOCALE[langKey] ?? PLACEHOLDER_BY_LOCALE.en;
  const placeholderFallback =
    mode === "date"
      ? localePlaceholders.date
      : mode === "time"
        ? localePlaceholders.time
        : localePlaceholders.datetime;

  return (
    <input
      ref={inputRef}
      type="text"
      className={className ?? "input max-w-sm"}
      placeholder={placeholder ?? placeholderFallback}
      disabled={disabled}
      id={id}
      autoComplete="off"
    />
  );
}
