/**
 * BNote Next Generation - Shared single-select picker
 * Used for all entities (events, profile, contacts, etc.) in edit mode.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { LayoutList } from "@/components/icons";

/** Option shape: id + optional name (compatible with SimpleOption, InstrumentOption, etc.). */
export interface SelectPickerOption {
  id: number;
  name?: string | null;
  subtitle?: string | null;
}

export interface SelectPickerProps {
  options: SelectPickerOption[];
  value: number;
  onChange: (next: number) => void;
  placeholder?: string;
  emptyLabel?: string;
  labelSelect?: string;
  labelNoMatches?: string;
  labelClose?: string;
}

export function SelectPicker({
  options,
  value,
  onChange,
  placeholder = "Search…",
  emptyLabel,
  labelSelect = "Select…",
  labelNoMatches = "No matches",
  labelClose = "Close",
}: SelectPickerProps) {
  const { t } = useI18n();
  const resolvedEmptyLabel = emptyLabel ?? (t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "");
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const rootRef = useRef<HTMLDivElement | null>(null);
  const filtered = options.filter((opt) => (opt.name ?? "").toLowerCase().includes(query.toLowerCase()));
  const selected = options.find((opt) => opt.id === value);
  const useFullscreen = options.length > 5;

  useEffect(() => {
    if (!open) return;
    const handle = (event: MouseEvent) => {
      if (!rootRef.current) return;
      if (!rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, [open]);

  return (
    <div ref={rootRef} className="relative space-y-2">
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="btn btn-outline w-full justify-between"
      >
        <span className="flex items-center justify-between gap-2">
          <span className="flex flex-col text-left">
            <span>{selected?.name ?? resolvedEmptyLabel}</span>
            {selected?.subtitle ? (
              <span className="text-xs font-normal" style={{ color: "var(--muted-foreground)" }}>
                {selected.subtitle}
              </span>
            ) : null}
          </span>
          <LayoutList className="h-4 w-4" />
        </span>
      </button>
      {open && !useFullscreen && (
        <div
          className="absolute left-0 top-full z-50 mt-2 w-full rounded-md border p-3 shadow-lg"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={placeholder}
            className="input input-sm w-full"
          />
          <div className="max-h-80 overflow-y-auto rounded-md border" style={{ borderColor: "var(--border)" }}>
            {filtered.map((opt) => (
              <button
                key={opt.id}
                type="button"
                onClick={() => {
                  onChange(opt.id);
                  setOpen(false);
                }}
                className="w-full text-left px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              >
                <span className="flex flex-col">
                  <span>{opt.name ?? resolvedEmptyLabel}</span>
                  {opt.subtitle ? (
                    <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                      {opt.subtitle}
                    </span>
                  ) : null}
                </span>
              </button>
            ))}
            {filtered.length === 0 && (
              <div className="px-3 py-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
                {labelNoMatches}
              </div>
            )}
          </div>
        </div>
      )}
      {open && useFullscreen && (
        <div className="fixed inset-0 z-50">
          <div
            className="absolute inset-0"
            style={{ background: "color-mix(in oklch, var(--foreground) 20%, transparent)" }}
            onClick={() => setOpen(false)}
          />
          <div
            className="absolute inset-0 flex h-full w-full flex-col rounded-none border shadow-xl md:left-1/2 md:top-1/2 md:h-[90vh] md:w-[min(98vw,980px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-lg"
            style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
          >
            <div className="flex items-center justify-between border-b px-4 py-3" style={{ borderColor: "var(--border)" }}>
              <div className="text-sm font-semibold">{labelSelect}</div>
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="btn btn-outline btn-sm"
              >
                {labelClose}
              </button>
            </div>
            <input
              type="search"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder={placeholder}
              className="input input-sm w-full mx-4 mt-4"
            />
            <div
              className="mx-4 mb-4 mt-3 flex-1 min-h-0 overflow-y-auto rounded-md border md:max-h-[78vh]"
              style={{ borderColor: "var(--border)" }}
            >
              {filtered.map((opt) => (
                <button
                  key={opt.id}
                  type="button"
                  onClick={() => {
                    onChange(opt.id);
                    setOpen(false);
                  }}
                  className="w-full text-left px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                  style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
                >
                  <span className="flex flex-col">
                    <span>{opt.name ?? emptyLabel}</span>
                    {opt.subtitle ? (
                      <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                        {opt.subtitle}
                      </span>
                    ) : null}
                  </span>
                </button>
              ))}
              {filtered.length === 0 && (
                <div className="px-3 py-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
                  {labelNoMatches}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
