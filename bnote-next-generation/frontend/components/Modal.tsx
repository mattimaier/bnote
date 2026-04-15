/**
 * BNote Next Generation - Modal (React-controlled overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import { X } from "@/components/icons";
import { useI18n } from "@/contexts/I18nContext";

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  bodyClassName?: string;
  dialogClassName?: string;
}

export function Modal({ open, onClose, title, children, bodyClassName = "", dialogClassName = "" }: ModalProps) {
  const { t } = useI18n();
  const closeLabel = t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close";

  useEffect(() => {
    if (!open) return;
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", handleEscape);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "";
    };
  }, [open, onClose]);

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) onClose();
  };

  if (!open) return null;

  const modalContent = (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      <div className="absolute inset-0 bg-base-content/20" aria-hidden="true" onClick={handleBackdropClick} />
      <div
        className={`modal-dialog modal-dialog-sm modal-middle relative z-10 w-full max-w-md ${dialogClassName}`.trim()}
      >
        <div className="modal-content rounded-box border border-base-300 bg-base-100 shadow-xl">
          <div className="modal-header flex items-center justify-between p-4">
            <h3 id="modal-title" className="modal-title text-lg font-semibold text-base-content">
              {title}
            </h3>
            <button type="button" onClick={onClose} className="btn btn-soft btn-square btn-sm" aria-label={closeLabel}>
              <X className="h-5 w-5" />
            </button>
          </div>
          <div className={`modal-body max-h-[70vh] overflow-y-auto p-4 pt-0 ${bodyClassName}`.trim()}>{children}</div>
        </div>
      </div>
    </div>
  );

  return typeof document !== "undefined" ? createPortal(modalContent, document.body) : null;
}
