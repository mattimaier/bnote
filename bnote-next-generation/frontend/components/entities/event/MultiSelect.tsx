/**
 * BNote Next Generation - MultiSelect picker (chips, fullscreen overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useRef, useState } from "react";
import { CHECKBOX_ROW_INPUT_CLASS, CHECKBOX_ROW_LABEL_CLASS } from "@/components/CheckboxRow";
import { LayoutList, Trash2 } from "@/components/icons";
import { PersonOptionRow } from "@/components/PersonOptionRow";
import type { SimpleOption } from "@/lib/entities/event/types";

export interface MultiSelectProps {
  options: SimpleOption[];
  selected: number[];
  onChange: (next: number[]) => void;
  placeholder?: string;
  showChips?: boolean;
  labelSelect?: string;
  labelSelectedCount?: (count: number) => string;
  labelNoSelection?: string;
  labelNoMatches?: string;
  labelClose?: string;
  labelRemove?: string;
}

export function MultiSelect({
  options,
  selected,
  onChange,
  placeholder = "Search…",
  showChips = true,
  labelSelect = "Select…",
  labelNoSelection = "No selection",
  labelNoMatches = "No matches",
  labelClose = "Close",
  labelRemove = "Remove",
}: MultiSelectProps) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const rootRef = useRef<HTMLDivElement | null>(null);
  const selectedSet = new Set(selected);
  const filtered = options.filter((opt) => (opt.name ?? "").toLowerCase().includes(query.toLowerCase()));
  const useFullscreen = options.length > 5;

  const toggle = (id: number) => {
    if (selectedSet.has(id)) {
      onChange(selected.filter((v) => v !== id));
    } else {
      onChange([...selected, id]);
    }
  };

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
    <div ref={rootRef} className="relative w-full min-w-[12rem] max-w-[20rem]">
      <button type="button" onClick={() => setOpen((prev) => !prev)} className="btn btn-outline w-full justify-between">
        <span className="flex items-center justify-between gap-2 w-full min-w-0">
          <span className="text-right flex-1 truncate whitespace-nowrap min-w-0" title={labelSelect}>
            {labelSelect}
          </span>
          <LayoutList className="h-4 w-4" />
        </span>
      </button>
      {showChips && (
        <div className="mt-4 w-full space-y-2">
          {selected.length === 0 && <span className="text-xs text-base-content/60">{labelNoSelection}</span>}
          {selected.map((id) => {
            const opt = options.find((o) => o.id === id);
            if (!opt?.name) return null;
            return (
              <div
                key={id}
                className="flex items-center justify-between gap-3 rounded-field border border-base-300 px-3 py-2 text-sm"
              >
                {opt.email != null || opt.instrument != null ? (
                  <PersonOptionRow
                    name={opt.name}
                    email={opt.email}
                    instrument={opt.instrument ?? opt.subtitle}
                    avatarSize={24}
                    compact
                  />
                ) : (
                  <div className="flex flex-col">
                    <span className="font-medium">{opt.name}</span>
                    {(opt.subtitle ?? opt.instrument) ? (
                      <span className="text-xs text-base-content/60">{opt.instrument ?? opt.subtitle}</span>
                    ) : null}
                  </div>
                )}
                <button
                  type="button"
                  onClick={() => toggle(id)}
                  className="btn btn-soft btn-square btn-sm"
                  aria-label={labelRemove}
                >
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            );
          })}
        </div>
      )}
      {open && !useFullscreen && (
        <div className="absolute left-0 top-full z-50 mt-2 w-full rounded-box border border-base-300 bg-base-100 text-base-content p-3 shadow-lg">
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={placeholder}
            className="input input-sm w-full"
          />
          <div className="max-h-80 overflow-y-auto rounded-field border border-base-300">
            {filtered.map((opt) => (
              <label key={opt.id} className={CHECKBOX_ROW_LABEL_CLASS}>
                <input
                  type="checkbox"
                  className={CHECKBOX_ROW_INPUT_CLASS}
                  checked={selectedSet.has(opt.id)}
                  onChange={() => toggle(opt.id)}
                />
                {opt.email != null || opt.instrument != null ? (
                  <PersonOptionRow
                    name={opt.name ?? "-"}
                    email={opt.email}
                    instrument={opt.instrument ?? opt.subtitle}
                    avatarSize={32}
                  />
                ) : (
                  <span className="flex flex-col">
                    <span>{opt.name ?? "-"}</span>
                    {(opt.subtitle ?? opt.instrument) ? (
                      <span className="text-xs text-base-content/60">{opt.instrument ?? opt.subtitle}</span>
                    ) : null}
                  </span>
                )}
              </label>
            ))}
            {filtered.length === 0 && <div className="px-3 py-2 text-sm text-base-content/60">{labelNoMatches}</div>}
          </div>
        </div>
      )}
      {open && useFullscreen && (
        <div className="fixed inset-0 z-50">
          <div className="absolute inset-0 bg-base-content/20" onClick={() => setOpen(false)} />
          <div className="absolute inset-0 flex h-full w-full flex-col rounded-none border border-base-300 shadow-xl md:left-1/2 md:top-1/2 md:h-[90vh] md:w-[min(98vw,980px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-box bg-base-100 text-base-content">
            <div className="flex items-center justify-between border-b border-base-300 px-4 py-3">
              <div className="text-sm font-semibold">{labelSelect}</div>
              <button type="button" onClick={() => setOpen(false)} className="btn btn-outline btn-sm">
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
            <div className="mx-4 mb-4 mt-3 flex-1 min-h-0 overflow-y-auto rounded-field border border-base-300 md:max-h-[78vh]">
              {filtered.map((opt) => (
                <label key={opt.id} className={CHECKBOX_ROW_LABEL_CLASS}>
                  <input
                    type="checkbox"
                    className={CHECKBOX_ROW_INPUT_CLASS}
                    checked={selectedSet.has(opt.id)}
                    onChange={() => toggle(opt.id)}
                  />
                  {opt.email != null || opt.instrument != null ? (
                    <PersonOptionRow
                      name={opt.name ?? "-"}
                      email={opt.email}
                      instrument={opt.instrument ?? opt.subtitle}
                      avatarSize={32}
                    />
                  ) : (
                    <span className="flex flex-col">
                      <span>{opt.name ?? "-"}</span>
                      {(opt.subtitle ?? opt.instrument) ? (
                        <span className="text-xs text-base-content/60">{opt.instrument ?? opt.subtitle}</span>
                      ) : null}
                    </span>
                  )}
                </label>
              ))}
              {filtered.length === 0 && <div className="px-3 py-2 text-sm text-base-content/60">{labelNoMatches}</div>}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
