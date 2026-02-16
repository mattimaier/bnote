/**
 * BNote Next Generation - Status pill picker (popover)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useRef, useState } from "react";
import { getStatusPillStyle } from "@/lib/entity-config";

export interface StatusPickerProps {
  options: string[];
  value: string;
  onChange: (next: string) => void;
  labelFor?: (value: string) => string;
}

export function StatusPicker({ options, value, onChange, labelFor }: StatusPickerProps) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);
  const displayLabel = labelFor ? labelFor(value) : value;

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
        className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
        style={getStatusPillStyle(value)}
      >
        {displayLabel}
      </button>
      {open && (
        <div
          className="absolute left-0 top-full z-50 mt-2 min-w-[160px] rounded-md border border-base-300 bg-base-100 text-base-content p-2 shadow-lg"
        >
          <div className="flex flex-col gap-2">
            {options.map((opt) => (
              <button
                key={opt}
                type="button"
                onClick={() => {
                  onChange(opt);
                  setOpen(false);
                }}
                className="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-medium border"
                style={getStatusPillStyle(opt)}
              >
                {labelFor ? labelFor(opt) : opt}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
