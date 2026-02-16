/**
 * BNote Next Generation - Toast Container
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import { useToast, type ToastType } from "@/contexts/ToastContext";
import { getIcon } from "@/components/icons";
import { getIconName } from "@/lib/entity-config";
import { Info, CheckCircle, AlertCircle, X } from "@/components/icons";

const typeStyles: Record<ToastType, string> = {
  default: "bg-[var(--primary)] text-white border-[var(--primary)] shadow-[0_4px_14px_rgba(51,153,255,0.35)]",
  success: "bg-[var(--primary)] text-white border-[var(--primary)] shadow-[0_4px_14px_rgba(51,153,255,0.35)]",
  error: "bg-[var(--destructive)] text-white border-[var(--destructive)] shadow-[0_4px_14px_rgba(229,43,60,0.35)]",
};

function ToastIcon({ type, entityType }: { type: ToastType; entityType?: string }) {
  if (entityType) {
    const IconComponent = getIcon(getIconName(entityType));
    return <IconComponent className="h-6 w-6 shrink-0 opacity-95" aria-hidden />;
  }
  switch (type) {
    case "success":
      return <CheckCircle className="h-6 w-6 shrink-0 opacity-95" aria-hidden />;
    case "error":
      return <AlertCircle className="h-6 w-6 shrink-0 opacity-95" aria-hidden />;
    default:
      return <Info className="h-6 w-6 shrink-0 opacity-95" aria-hidden />;
  }
}

export function ToastContainer() {
  const { toasts, dismiss } = useToast();

  if (toasts.length === 0) return null;

  return (
    <div
      className="fixed top-4 left-1/2 z-[100] flex w-full max-w-[520px] -translate-x-1/2 flex-col gap-3 px-4"
      aria-live="polite"
    >
      {toasts.map((toast) => (
        <div
          key={toast.id}
          className={`group pointer-events-auto relative flex w-full items-center gap-4 overflow-hidden rounded-xl border p-5 pr-12 transition-all ${typeStyles[toast.type]}`}
        >
          <ToastIcon type={toast.type} entityType={toast.entityType} />
          <p className="min-w-0 flex-1 text-base font-medium leading-snug">{toast.message}</p>
          <button
            type="button"
            onClick={() => dismiss(toast.id)}
            className="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 opacity-90 transition-opacity hover:opacity-100 focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-white/50"
            aria-label="Dismiss"
          >
            <X className="h-5 w-5" />
          </button>
        </div>
      ))}
    </div>
  );
}
