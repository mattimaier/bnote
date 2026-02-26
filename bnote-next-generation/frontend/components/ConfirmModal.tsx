/**
 * BNote Next Generation - Confirm modal (FlyonUI/Preline overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useId, useRef } from "react";

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

declare global {
  interface Window {
    HSOverlay?: {
      open: (el: string | HTMLElement) => void;
      close: (el: string | HTMLElement) => void;
    };
  }
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
  const id = useId().replace(/:/g, "-") || "confirm-1";
  const modalId = `bn-confirm-${id}`;
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!ref.current || typeof window === "undefined") return;
    const el = ref.current;
    const handleClose = () => onClose();
    el.addEventListener("close.overlay", handleClose);
    return () => el.removeEventListener("close.overlay", handleClose);
  }, [onClose]);

  useEffect(() => {
    if (typeof window === "undefined" || !window.HSOverlay || !ref.current) return;
    try {
      if (open) {
        window.HSOverlay.open(ref.current);
      } else {
        window.HSOverlay.close(ref.current);
      }
    } catch {
      /* HSOverlay can throw if $hsOverlayCollection is undefined or overlay not registered */
    }
  }, [open]);

  const handleConfirm = async () => {
    await onConfirm();
    onClose();
  };

  return (
    <div
      id={modalId}
      ref={ref}
      className="overlay modal overlay-open:opacity-100 overlay-open:duration-300 hidden"
      role="dialog"
      tabIndex={-1}
      aria-modal="true"
      aria-labelledby={`${modalId}-title`}
    >
      <div className="modal-dialog modal-dialog-sm modal-middle">
        <div className="modal-content">
          <div className="modal-header">
            <h3 id={`${modalId}-title`} className="modal-title">
              {title}
            </h3>
          </div>
          <div className="modal-body">
            <p className="text-sm text-base-content/70">{message}</p>
          </div>
          <div className="modal-footer">
            <button type="button" onClick={onClose} className="btn btn-outline btn-sm">
              {cancelLabel}
            </button>
            <button
              type="button"
              onClick={handleConfirm}
              className={variant === "danger" ? "btn btn-error btn-sm text-white" : "btn btn-primary btn-sm"}
            >
              {confirmLabel}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
