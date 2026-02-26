/**
 * BNote Next Generation - Shared remove/delete button for list options
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { Trash2 } from "@/components/icons";

export interface RemoveOptionButtonProps {
  onClick: () => void;
  ariaLabel?: string;
}

export function RemoveOptionButton({
  onClick,
  ariaLabel = "Remove",
}: RemoveOptionButtonProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="inline-flex items-center justify-center rounded-field border border-base-300 text-base-content px-2 py-2 text-sm hover:bg-base-200/50 active:bg-base-200/70"
      aria-label={ariaLabel}
    >
      <Trash2 className="h-4 w-4" />
    </button>
  );
}
