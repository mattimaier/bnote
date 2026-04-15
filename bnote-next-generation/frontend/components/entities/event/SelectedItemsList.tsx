/**
 * BNote Next Generation - Read-only list of selected items with remove
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { RemoveOptionButton } from "@/components/RemoveOptionButton";
import type { SimpleOption } from "@/lib/entities/event/types";

export interface SelectedItemsListProps {
  options: SimpleOption[];
  selected: number[];
  onRemove: (id: number) => void;
  removable?: boolean;
  labelRemove?: string;
  emptyLabel?: string;
  className?: string;
}

export function SelectedItemsList({
  options,
  selected,
  onRemove,
  removable = true,
  labelRemove = "Remove",
  emptyLabel = "No selection",
  className = "",
}: SelectedItemsListProps) {
  if (selected.length === 0) {
    return (
      <div className={className}>
        <span className="text-sm text-base-content/60">{emptyLabel}</span>
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
            className="flex items-center justify-between gap-3 rounded-md border border-base-300 px-3 py-2 text-sm"
          >
            <div className="flex flex-col">
              <span className="font-medium">{opt.name}</span>
              {opt.subtitle ? <span className="text-xs text-base-content/60">{opt.subtitle}</span> : null}
            </div>
            {removable ? <RemoveOptionButton onClick={() => onRemove(id)} ariaLabel={labelRemove} /> : null}
          </div>
        );
      })}
    </div>
  );
}
