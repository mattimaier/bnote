/**
 * BNote Next Generation - Date/Time picker (Flatpickr + FlyonUI)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useRef } from "react";
import flatpickr, { type Instance } from "flatpickr";
import { German } from "flatpickr/dist/l10n/de";
import { Spanish } from "flatpickr/dist/l10n/es";
import { French } from "flatpickr/dist/l10n/fr";

const LOCALE_MAP: Record<string, object> = {
  de: German,
  en: undefined,
  es: Spanish,
  fr: French,
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
  const opts: Parameters<typeof flatpickr>[1] = {
    allowInput: true,
    monthSelectorType: "static",
    locale: locale ? LOCALE_MAP[locale.split("-")[0]?.toLowerCase() ?? "en"] ?? undefined : undefined,
  };
  switch (mode) {
    case "date":
      return { ...opts, dateFormat: "Y-m-d" };
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
  const fpRef = useRef<Instance | null>(null);

  useEffect(() => {
    const el = inputRef.current;
    if (!el) return;

    const base = getBaseOptions(mode, locale);
    fpRef.current = flatpickr(el, {
      ...base,
      defaultDate: value || undefined,
      onChange: (_dates, dateStr) => {
        if (!dateStr) {
          onChange("");
          return;
        }
        if (mode === "datetime" && appendSeconds && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(dateStr)) {
          onChange(dateStr + ":00");
        } else {
          onChange(dateStr);
        }
      },
    });

    return () => {
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

  const placeholderFallback =
    mode === "date"
      ? "YYYY-MM-DD"
      : mode === "time"
        ? "HH:MM"
        : "YYYY-MM-DD HH:MM";

  return (
    <input
      ref={inputRef}
      type="text"
      data-input
      placeholder={placeholder ?? placeholderFallback}
      className={className}
      disabled={disabled}
      id={id}
      autoComplete="off"
    />
  );
}
