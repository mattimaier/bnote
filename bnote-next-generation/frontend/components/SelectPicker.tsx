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
import { PersonOptionRow } from "@/components/PersonOptionRow";

/** Option shape: id + optional name (compatible with SimpleOption, InstrumentOption, etc.). */
export interface SelectPickerOption {
  id: number;
  name?: string | null;
  subtitle?: string | null;
  /** For person options: email for Gravatar */
  email?: string | null;
  /** For person options: instrument (always shown as subtitle) */
  instrument?: string | null;
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
  /**
   * If true, sets `data-1p-ignore` so 1Password (and similar) does not treat this control
   * as part of the address/contact sequence (e.g. app-specific pickers between standard fields).
   */
  passwordManagerIgnore?: boolean;
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
  passwordManagerIgnore = false,
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
    <div
      ref={rootRef}
      className="relative w-full min-w-[12rem] max-w-[20rem] space-y-2"
      {...(passwordManagerIgnore ? { "data-1p-ignore": "" as const } : {})}
    >
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="btn btn-outline w-full justify-between"
      >
        <span className="flex items-center justify-between gap-2 w-full min-w-0">
          {selected && (selected.email != null || selected.instrument != null) ? (
            <PersonOptionRow
              name={selected.name ?? resolvedEmptyLabel ?? ""}
              email={selected.email}
              instrument={selected.instrument ?? selected.subtitle}
              avatarSize={24}
              compact
              className="min-w-0 flex-1"
            />
          ) : (
            <span className="flex min-w-0 flex-1 flex-col items-end text-right overflow-hidden">
              <span className="truncate whitespace-nowrap" title={selected?.name ?? resolvedEmptyLabel ?? undefined}>
                {selected?.name ?? resolvedEmptyLabel}
              </span>
              {(selected?.subtitle ?? selected?.instrument) ? (
                <span className="text-xs font-normal truncate whitespace-nowrap text-base-content/60">
                  {selected?.instrument ?? selected?.subtitle}
                </span>
              ) : null}
            </span>
          )}
          <LayoutList className="h-4 w-4 shrink-0" />
        </span>
      </button>
      {open && !useFullscreen && (
        <div className="absolute left-0 top-full z-50 mt-2 w-full rounded-md border border-base-300 bg-base-100 text-base-content p-3 shadow-lg">
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={placeholder}
            className="input input-sm w-full"
          />
          <div className="max-h-80 overflow-y-auto rounded-md border border-base-300">
            {filtered.map((opt) => (
              <button
                key={opt.id}
                type="button"
                onClick={() => {
                  onChange(opt.id);
                  setOpen(false);
                }}
                className="w-full text-left px-3 py-3 text-sm border-b border-base-300 text-base-content hover:bg-base-200/60 active:bg-base-200"
              >
                {(opt.email != null || opt.instrument != null) ? (
                  <PersonOptionRow
                    name={opt.name ?? resolvedEmptyLabel ?? ""}
                    email={opt.email}
                    instrument={opt.instrument ?? opt.subtitle}
                    avatarSize={32}
                  />
                ) : (
                  <span className="flex flex-col">
                    <span>{opt.name ?? resolvedEmptyLabel}</span>
                    {(opt.subtitle ?? opt.instrument) ? (
                      <span className="text-xs text-base-content/60">
                        {opt.instrument ?? opt.subtitle}
                      </span>
                    ) : null}
                  </span>
                )}
              </button>
            ))}
            {filtered.length === 0 && (
              <div className="px-3 py-2 text-sm text-base-content/60">
                {labelNoMatches}
              </div>
            )}
          </div>
        </div>
      )}
      {open && useFullscreen && (
        <div className="fixed inset-0 z-50">
          <div
            className="absolute inset-0 bg-base-content/20"
            onClick={() => setOpen(false)}
          />
          <div className="absolute inset-0 flex h-full w-full flex-col rounded-none border border-base-300 bg-base-100 text-base-content shadow-xl md:left-1/2 md:top-1/2 md:h-[90vh] md:w-[min(98vw,980px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-box">
            <div className="flex items-center justify-between border-b border-base-300 px-4 py-3">
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
            <div className="mx-4 mb-4 mt-3 flex-1 min-h-0 overflow-y-auto rounded-md border border-base-300 md:max-h-[78vh]">
              {filtered.map((opt) => (
                <button
                  key={opt.id}
                  type="button"
                  onClick={() => {
                    onChange(opt.id);
                    setOpen(false);
                  }}
                  className="w-full text-left px-3 py-3 text-sm border-b border-base-300 text-base-content hover:bg-base-200/60 active:bg-base-200"
                >
                  {(opt.email != null || opt.instrument != null) ? (
                    <PersonOptionRow
                      name={opt.name ?? resolvedEmptyLabel ?? ""}
                      email={opt.email}
                      instrument={opt.instrument ?? opt.subtitle}
                      avatarSize={32}
                    />
                  ) : (
                    <span className="flex flex-col">
                      <span>{opt.name ?? emptyLabel}</span>
                      {(opt.subtitle ?? opt.instrument) ? (
                        <span className="text-xs text-base-content/60">
                          {opt.instrument ?? opt.subtitle}
                        </span>
                      ) : null}
                    </span>
                  )}
                </button>
              ))}
              {filtered.length === 0 && (
                <div className="px-3 py-2 text-sm text-base-content/60">
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
