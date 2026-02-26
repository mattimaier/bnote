/**
 * BNote Next Generation - Participation traffic light (yes/maybe/no)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import type { EditableParticipant } from "@/lib/entities/event/types";

const PARTICIPATION_BTN_BASE =
  "participation-btn w-9 h-9 md:w-10 md:h-10 rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-md";

export type ParticipationValue = EditableParticipant["participate"];

export function ParticipationTrafficLight({
  value,
  onChange,
  allowMaybe = true,
  allowDeselect = true,
  disabled = false,
}: {
  value: ParticipationValue;
  onChange: (next: ParticipationValue) => void;
  allowMaybe?: boolean;
  allowDeselect?: boolean;
  disabled?: boolean;
}) {
  const btnClass = (status: "yes" | "maybe" | "no", active: boolean) => {
    const roleClass =
      status === "yes"
        ? "participation-btn-yes"
        : status === "maybe"
          ? "participation-btn-maybe"
          : "participation-btn-no";
    const activeClass = active
      ? status === "yes"
        ? "participation-active-yes"
        : status === "maybe"
          ? "participation-active-maybe"
          : "participation-active-no"
      : "";
    return `${PARTICIPATION_BTN_BASE} ${roleClass} ${activeClass}`.trim();
  };

  const handleClick = (next: ParticipationValue) => {
    if (disabled) return;
    if (allowDeselect && next === value && value !== "pending") {
      onChange("pending");
      return;
    }
    onChange(next);
  };

  const isPending = value === "pending";

  return (
    <div className={`flex items-center gap-2 ${isPending ? "participation-pending" : ""}`}>
      <button type="button" className={btnClass("yes", value === "yes")} onClick={() => handleClick("yes")}>
        <svg className="h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
        </svg>
      </button>
      {allowMaybe && (
        <button type="button" className={btnClass("maybe", value === "maybe")} onClick={() => handleClick("maybe")}>
          <svg className="h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </button>
      )}
      <button type="button" className={btnClass("no", value === "no")} onClick={() => handleClick("no")}>
        <svg className="h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  );
}
