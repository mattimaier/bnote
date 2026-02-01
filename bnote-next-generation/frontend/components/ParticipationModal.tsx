/**
 * BNote Next Generation - Participation Modal (reason for maybe/no)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useRef, useState } from "react";

export type ParticipationModalStatus = "maybe" | "no";

interface ParticipationModalProps {
  open: boolean;
  status: ParticipationModalStatus | null;
  statusLabel: string;
  reasonPlaceholder: string;
  onConfirm: (reason: string) => void;
  onCancel: () => void;
  confirmLabel: string;
  cancelLabel: string;
  reasonForLabel: string;
}

export function ParticipationModal({
  open,
  status,
  statusLabel,
  reasonPlaceholder,
  onConfirm,
  onCancel,
  confirmLabel,
  cancelLabel,
  reasonForLabel,
}: ParticipationModalProps) {
  const [reason, setReason] = useState("");
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  useEffect(() => {
    if (open) {
      setReason("");
      setTimeout(() => textareaRef.current?.focus(), 100);
    }
  }, [open, status]);

  if (!open) return null;

  const handleConfirm = () => {
    onConfirm(reason.trim());
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      onClick={(e) => e.target === e.currentTarget && onCancel()}
    >
      <div
        className="mx-4 w-full max-w-md rounded-lg border border-[var(--border)] bg-[var(--card)] p-6 shadow-xl"
        style={{ color: "var(--card-foreground)" }}
      >
        <h3 className="mb-4 text-xl font-semibold">
          {reasonForLabel} {statusLabel}
        </h3>
        <textarea
          ref={textareaRef}
          value={reason}
          onChange={(e) => setReason(e.target.value)}
          placeholder={reasonPlaceholder}
          rows={4}
          className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--primary)]/30"
          style={{ color: "var(--foreground)" }}
        />
        <div className="mt-4 flex justify-end gap-3">
          <button
            type="button"
            onClick={onCancel}
            className="rounded-md border border-[var(--border)] px-4 py-2 transition-colors hover:bg-[var(--muted)]"
            style={{ color: "var(--foreground)" }}
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={handleConfirm}
            className="rounded-md px-4 py-2 text-white transition-opacity hover:opacity-90"
            style={{
              background: status === "maybe" ? "var(--warning, #eab308)" : "var(--destructive)",
            }}
          >
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}
