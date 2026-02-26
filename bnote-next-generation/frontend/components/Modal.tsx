/**
 * BNote Next Generation - Modal (FlyonUI/Preline overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useId, useRef } from "react";
import { X } from "@/components/icons";
import { useI18n } from "@/contexts/I18nContext";

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
}

declare global {
  interface Window {
    HSOverlay?: {
      open: (el: string | HTMLElement) => void;
      close: (el: string | HTMLElement) => void;
    };
  }
}

export function Modal({ open, onClose, title, children }: ModalProps) {
  const { t } = useI18n();
  const id = useId().replace(/:/g, "-") || "modal-1";
  const closeLabel = t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close";
  const modalId = `bn-modal-${id}`;
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!ref.current || typeof window === "undefined") return;
    const el = ref.current;

    const handleClose = () => {
      onClose();
    };

    el.addEventListener("close.overlay", handleClose);
    return () => el.removeEventListener("close.overlay", handleClose);
  }, [onClose]);

  useEffect(() => {
    if (typeof window === "undefined" || !window.HSOverlay || !ref.current) return;
    if (open) {
      window.HSOverlay.open(ref.current);
    } else {
      window.HSOverlay.close(ref.current);
    }
  }, [open]);

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
              {title}
            </h3>
            <button
              type="button"
              onClick={onClose}
              className="btn btn-soft btn-square btn-sm"
              aria-label={closeLabel}
            >
              <X className="h-5 w-5" />
            </button>
          </div>
          <div className="modal-body max-h-[70vh] overflow-y-auto">{children}</div>
        </div>
      </div>
    </div>
  );
}
