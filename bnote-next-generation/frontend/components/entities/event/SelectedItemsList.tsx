/**
 * BNote Next Generation - Read-only list of selected items with remove
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { Trash2 } from "@/components/icons";
import type { SimpleOption } from "@/lib/entities/event/types";

export interface SelectedItemsListProps {
  options: SimpleOption[];
  selected: number[];
  onRemove: (id: number) => void;
  labelRemove?: string;
  emptyLabel?: string;
  className?: string;
}

export function SelectedItemsList({
  options,
  selected,
  onRemove,
  labelRemove = "Remove",
  emptyLabel = "No selection",
  className = "",
}: SelectedItemsListProps) {
  if (selected.length === 0) {
    return (
      <div className={className}>
        <span className="text-sm" style={{ color: "var(--muted-foreground)" }}>
          {emptyLabel}
        </span>
      </div>
    );
  }

  return (
    <div className={`space-y-2 ${className}`.trim()}>
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
              onClick={() => onRemove(id)}
              className="inline-flex items-center justify-center rounded-md border px-2 py-2 text-sm hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
              style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              aria-label={labelRemove}
            >
              <Trash2 className="h-4 w-4" />
            </button>
          </div>
        );
      })}
    </div>
  );
}
