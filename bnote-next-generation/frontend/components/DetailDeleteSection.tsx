/**
 * BNote Next Generation - Delete section for entity edit views
 * Renders at the bottom of edit form; only visible if user has delete rights.
 * Triggers confirm/cancel modal before delete.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useState } from "react";
import { Trash2 } from "lucide-react";
import { useI18n } from "@/contexts/I18nContext";
import { ConfirmModal } from "@/components/ConfirmModal";
import { DetailCard } from "@/components/DetailCard";

export interface DetailDeleteSectionProps {
  canDelete: boolean;
  onDelete: () => void | Promise<void>;
  /** Optional entity name for confirmation message */
  entityTitle?: string;
}

export function DetailDeleteSection({ canDelete, onDelete, entityTitle }: DetailDeleteSectionProps) {
  const { t } = useI18n();
  const [confirmOpen, setConfirmOpen] = useState(false);

  if (!canDelete) return null;

  const title = t("js.common.confirmDeleteTitle") !== "js.common.confirmDeleteTitle" ? t("js.common.confirmDeleteTitle") : "Delete?";
  const message = entityTitle
    ? (t("js.common.confirmDeleteMessageNamed") !== "js.common.confirmDeleteMessageNamed"
      ? t("js.common.confirmDeleteMessageNamed").replace("%s", entityTitle)
      : `Delete "${entityTitle}"? This cannot be undone.`)
    : (t("js.common.confirmDeleteMessage") !== "js.common.confirmDeleteMessage" ? t("js.common.confirmDeleteMessage") : "This cannot be undone.");
  const confirmLabel = t("js.common.delete") !== "js.common.delete" ? t("js.common.delete") : "Delete";
  const cancelLabel = t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel";

  const handleConfirm = async () => {
    await onDelete();
  };

  return (
    <>
      <DetailCard>
        <div className="flex items-center justify-between gap-4">
          <p className="text-sm" style={{ color: "var(--muted-foreground)" }}>
            {t("js.common.deleteSectionHint") !== "js.common.deleteSectionHint"
              ? t("js.common.deleteSectionHint")
              : "Permanently remove this item."}
          </p>
          <button
            type="button"
            onClick={() => setConfirmOpen(true)}
            className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium border"
            style={{ borderColor: "var(--destructive)", color: "var(--destructive)" }}
          >
            <Trash2 className="h-4 w-4" />
            {confirmLabel}
          </button>
        </div>
      </DetailCard>
      <ConfirmModal
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title={title}
        message={message}
        confirmLabel={confirmLabel}
        cancelLabel={cancelLabel}
        onConfirm={handleConfirm}
        variant="danger"
      />
    </>
  );
}
