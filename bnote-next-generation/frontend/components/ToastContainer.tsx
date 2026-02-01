/**
 * BNote Next Generation - Toast Container
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import { useToast, type ToastType } from "@/contexts/ToastContext";

const typeStyles: Record<ToastType, string> = {
  default: "bg-[var(--background)] text-[var(--foreground)] border-[var(--border)]",
  success: "bg-[var(--accent)] text-[var(--accent-foreground)] border-[var(--accent)]",
  error: "bg-[var(--destructive)] text-[var(--destructive-foreground)] border-[var(--destructive)]",
};

export function ToastContainer() {
  const { toasts, dismiss } = useToast();

  if (toasts.length === 0) return null;

  return (
    <div
      className="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-[420px]"
      aria-live="polite"
    >
      {toasts.map((toast) => (
        <div
          key={toast.id}
          className={`group pointer-events-auto flex w-full items-center justify-between gap-4 overflow-hidden rounded-md border p-4 pr-10 shadow-lg transition-all ${typeStyles[toast.type]}`}
        >
          <p className="text-sm font-semibold flex-1">{toast.message}</p>
          <button
            type="button"
            onClick={() => dismiss(toast.id)}
            className="absolute right-2 top-2 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 focus:opacity-100"
            aria-label="Dismiss"
          >
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      ))}
    </div>
  );
}
