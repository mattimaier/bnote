/**
 * BNote Next Generation - Participation Modal (React-controlled overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useId, useRef, useState } from "react";

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
  const id = useId().replace(/:/g, "-") || "participation-1";
  const modalId = `bn-participation-${id}`;

  useEffect(() => {
    if (open) {
      setReason("");
      setTimeout(() => textareaRef.current?.focus(), 100);
    }
  }, [open, status]);

  useEffect(() => {
    if (!open) return;
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape") onCancel();
    };
    document.addEventListener("keydown", handleEscape);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "";
    };
  }, [open, onCancel]);

  const handleConfirm = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();
    e.stopPropagation();
    onConfirm(reason.trim());
  };

  const handleBackdropClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.target === e.currentTarget) onCancel();
  };

  if (!open) return null;

  return (
    <div
      id={modalId}
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby={`${modalId}-title`}
    >
      <div
        className="absolute inset-0 bg-base-content/20"
        aria-hidden="true"
        onClick={handleBackdropClick}
      />
      <div
        className="relative z-10 w-full max-w-md rounded-box border border-base-300 bg-base-100 p-4 shadow-xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-4">
          <h3 id={`${modalId}-title`} className="text-lg font-semibold text-base-content">
            {reasonForLabel} {statusLabel}
          </h3>
        </div>
        <div className="mb-4">
          <textarea
            ref={textareaRef}
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder={reasonPlaceholder}
            rows={4}
            className="textarea textarea-md w-full"
          />
        </div>
        <div className="flex justify-end gap-2">
          <button
            type="button"
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onCancel();
            }}
            className="btn btn-outline btn-sm"
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={handleConfirm}
            className={status === "maybe" ? "btn btn-warning btn-sm" : "btn btn-error btn-sm"}
          >
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}
