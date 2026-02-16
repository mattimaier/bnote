/**
 * BNote Next Generation - MultiSelect picker (chips, fullscreen overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useRef, useState } from "react";
import { LayoutList, Trash2 } from "@/components/icons";
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
    <div ref={rootRef} className="relative w-full">
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="btn btn-outline w-full justify-between"
      >
        <span className="flex items-center justify-between gap-2">
          <span>{labelSelect}</span>
          <LayoutList className="h-4 w-4" />
        </span>
      </button>
      {showChips && (
        <div className="mt-4 w-full space-y-2">
          {selected.length === 0 && (
            <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
              {labelNoSelection}
            </span>
          )}
          {selected.map((id) => {
            const opt = options.find((o) => o.id === id);
            if (!opt?.name) return null;
            return (
              <div
                key={id}
                className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
                style={{ borderColor: "var(--border)" }}
              >
                <div className="flex flex-col">
                  <span className="font-medium">{opt.name}</span>
                  {opt.subtitle ? (
                    <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                      {opt.subtitle}
                    </span>
                  ) : null}
                </div>
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
              <label
                key={opt.id}
                className="flex items-center gap-3 px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                style={{ borderColor: "var(--border)" }}
              >
                <input type="checkbox" className="checkbox checkbox-primary checkbox-sm" checked={selectedSet.has(opt.id)} onChange={() => toggle(opt.id)} />
                <span className="flex flex-col">
                  <span>{opt.name ?? "-"}</span>
                  {opt.subtitle ? (
                    <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                      {opt.subtitle}
                    </span>
                  ) : null}
                </span>
              </label>
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
                <label
                  key={opt.id}
                  className="flex items-center gap-3 px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                  style={{ borderColor: "var(--border)" }}
                >
                  <input type="checkbox" className="checkbox checkbox-primary checkbox-sm" checked={selectedSet.has(opt.id)} onChange={() => toggle(opt.id)} />
                  <span className="flex flex-col">
                    <span>{opt.name ?? "-"}</span>
                    {opt.subtitle ? (
                      <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                        {opt.subtitle}
                      </span>
                    ) : null}
                  </span>
                </label>
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
