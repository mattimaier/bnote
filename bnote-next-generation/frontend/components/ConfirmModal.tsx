/**
 * BNote Next Generation - Confirm modal (React-controlled overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { ActionButton } from "@/components/ActionButton";

export interface ConfirmModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  message: React.ReactNode;
  confirmLabel: string;
  cancelLabel?: string;
  onConfirm: () => void | Promise<void>;
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
  const [confirming, setConfirming] = useState(false);

  useEffect(() => {
    if (!open) return;
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape" && !confirming) onClose();
    };
    document.addEventListener("keydown", handleEscape);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "";
    };
  }, [open, onClose, confirming]);

  useEffect(() => {
    if (!open) setConfirming(false);
  }, [open]);

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget && !confirming) onClose();
  };

  const handleConfirm = async () => {
    if (confirming) return;
    setConfirming(true);
    try {
      await onConfirm();
      onClose();
    } finally {
      setConfirming(false);
    }
  };

  if (!open) return null;

  const modalContent = (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="bn-confirm-title"
    >
      <div
        className="absolute inset-0 bg-base-content/20"
        aria-hidden="true"
        onClick={handleBackdropClick}
      />
      <div className="modal-dialog modal-dialog-sm modal-middle relative z-10 w-full max-w-md">
        <div className="modal-content rounded-box border border-base-300 bg-base-100 shadow-xl">
          <div className="modal-header p-4">
            <h3 id="bn-confirm-title" className="modal-title text-lg font-semibold text-base-content">
              {title}
            </h3>
          </div>
          <div className="modal-body p-4 pt-0">
            <p className="text-sm text-base-content/70">{message}</p>
          </div>
          <div className="modal-footer flex gap-2 p-4 pt-0">
            <ActionButton variant="outline" onClick={onClose} disabled={confirming}>
              {cancelLabel}
            </ActionButton>
            <ActionButton
              variant={variant === "danger" ? "danger" : "primary"}
              onClick={handleConfirm}
              disabled={confirming}
            >
              {confirmLabel}
            </ActionButton>
          </div>
        </div>
      </div>
    </div>
  );

  return typeof document !== "undefined" ? createPortal(modalContent, document.body) : null;
}
