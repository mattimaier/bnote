/**
 * Selectable list row: primary checkbox + content (users, events, etc.).
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import type { ReactNode } from "react";

/** Label wrapper for each checkbox row in scrollable lists */
export const CHECKBOX_ROW_LABEL_CLASS =
  "flex items-center gap-3 px-3 py-3 text-sm border-b border-base-300 hover:bg-base-200/60 active:bg-base-200 cursor-pointer";

/** “Select all” / header row — same interaction, slightly denser */
export const CHECKBOX_ROW_SELECT_ALL_LABEL_CLASS =
  "flex items-center gap-3 px-3 py-2.5 text-xs font-medium border-b border-base-300 hover:bg-base-200/60 active:bg-base-200 cursor-pointer";

export const CHECKBOX_ROW_INPUT_CLASS = "checkbox checkbox-primary checkbox-sm shrink-0";

export function CheckboxRow({
  checked,
  onToggle,
  children,
}: {
  checked: boolean;
  /** Called when the user toggles the checkbox (controlled). */
  onToggle: () => void;
  children: ReactNode;
}) {
  return (
    <label className={CHECKBOX_ROW_LABEL_CLASS}>
      <input
        type="checkbox"
        className={CHECKBOX_ROW_INPUT_CLASS}
        checked={checked}
        onChange={() => onToggle()}
      />
      {children}
    </label>
  );
}

export function CheckboxSelectAllRow({
  checked,
  onChange,
  label,
}: {
  checked: boolean;
  onChange: (checked: boolean) => void;
  label: string;
}) {
  return (
    <label className={CHECKBOX_ROW_SELECT_ALL_LABEL_CLASS}>
      <input
        type="checkbox"
        className={CHECKBOX_ROW_INPUT_CLASS}
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
      />
      <span>{label}</span>
    </label>
  );
}
