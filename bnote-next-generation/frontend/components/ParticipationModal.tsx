/**
 * BNote Next Generation - Participation Modal (FlyonUI/Preline overlay)
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

declare global {
  interface Window {
    HSOverlay?: {
      open: (el: string | HTMLElement) => void;
      close: (el: string | HTMLElement) => void;
    };
  }
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
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (open) {
      setReason("");
      setTimeout(() => textareaRef.current?.focus(), 100);
    }
  }, [open, status]);

  useEffect(() => {
    if (!ref.current || typeof window === "undefined") return;
    const el = ref.current;
    const handleClose = () => onCancel();
    el.addEventListener("close.overlay", handleClose);
    return () => el.removeEventListener("close.overlay", handleClose);
  }, [onCancel]);

  useEffect(() => {
    if (typeof window === "undefined" || !window.HSOverlay || !ref.current) return;
    if (open) {
      window.HSOverlay.open(ref.current);
    } else {
      window.HSOverlay.close(ref.current);
    }
  }, [open]);

  const handleConfirm = () => {
    onConfirm(reason.trim());
  };

  return (
    <div
      id={modalId}
      ref={ref}
      className="overlay modal hidden"
      role="dialog"
      aria-modal="true"
      aria-labelledby={`${modalId}-title`}
    >
      <div className="modal-dialog modal-dialog-sm modal-middle">
        <div className="modal-content">
          <div className="modal-header">
            <h3 id={`${modalId}-title`} className="modal-title">
              {reasonForLabel} {statusLabel}
            </h3>
          </div>
          <div className="modal-body">
            <textarea
              ref={textareaRef}
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder={reasonPlaceholder}
              rows={4}
              className="textarea textarea-md w-full"
            />
          </div>
          <div className="modal-footer">
            <button type="button" onClick={onCancel} className="btn btn-outline btn-sm">
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
    </div>
  );
}
