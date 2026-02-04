/**
 * BNote Next Generation - Confirm modal (e.g. delete confirmation)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect } from "react";

export interface ConfirmModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  message: React.ReactNode;
  confirmLabel: string;
  cancelLabel?: string;
  onConfirm: () => void;
  variant?: "danger" | "default";
}

export function ConfirmModal({
  open,
  onClose,
  title,
  message,
  confirmLabel,
  cancelLabel = "Cancel",
  onConfirm,
  variant = "danger",
}: ConfirmModalProps) {
  useEffect(() => {
    if (!open) return;
    const handle = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", handle);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handle);
      document.body.style.overflow = "";
    };
  }, [open, onClose]);

  if (!open) return null;

  const handleConfirm = async () => {
    await onConfirm();
    onClose();
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
      onClick={(e) => e.target === e.currentTarget && onClose()}
    >
      <div
        className="w-full max-w-md rounded-lg border border-[var(--border)] bg-[var(--card)] shadow-xl p-4"
        style={{ color: "var(--card-foreground)" }}
        onClick={(e) => e.stopPropagation()}
      >
        <h3 className="text-lg font-semibold mb-2">{title}</h3>
        <p className="text-sm mb-6" style={{ color: "var(--muted-foreground)" }}>
          {message}
        </p>
        <div className="flex justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-md border text-sm font-medium"
            style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={handleConfirm}
            className="px-4 py-2 rounded-md text-sm font-medium text-white"
            style={{
              background: variant === "danger" ? "var(--destructive)" : "var(--primary)",
              color: variant === "danger" ? "var(--destructive-foreground)" : undefined,
            }}
          >
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}
