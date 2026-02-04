/**
 * BNote Next Generation - Sticky editing mode bar
 * Same look as rehearsal/concert edit: "Editing" label + Save + Cancel
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useI18n } from "@/contexts/I18nContext";
import { Save, X } from "lucide-react";

interface EditingBarProps {
  isNew?: boolean;
  saving?: boolean;
  onCancel: () => void;
  /** If true, render a submit button that submits the form with id="entity-edit-form". Otherwise call onSave. */
  submitFormId?: string;
  onSave?: () => void;
}

export function EditingBar({
  isNew,
  saving,
  onCancel,
  submitFormId = "entity-edit-form",
  onSave,
}: EditingBarProps) {
  const { t } = useI18n();
  const saveLabel = isNew
    ? (t("js.common.create") !== "js.common.create" ? t("js.common.create") : "Create")
    : (t("js.common.save") !== "js.common.save" ? t("js.common.save") : "Save");

  return (
    <div
      className="sticky top-0 z-30 rounded-xl border px-4 py-3 shadow-sm"
      style={{
        borderColor: "var(--border)",
        background: "color-mix(in oklch, var(--primary) 12%, var(--card))",
        color: "var(--card-foreground)",
      }}
    >
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="text-sm font-medium">
          {t("js.common.editing") !== "js.common.editing"
            ? t("js.common.editing")
            : "Editing"}
        </div>
        <div className="flex items-center gap-2">
          {submitFormId ? (
            <button
              type="submit"
              form={submitFormId}
              disabled={saving}
              className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white"
              style={{ background: "var(--primary)" }}
            >
              <Save className="h-4 w-4" />
              {saveLabel}
            </button>
          ) : (
            <button
              type="button"
              onClick={onSave}
              disabled={saving}
              className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white"
              style={{ background: "var(--primary)" }}
            >
              <Save className="h-4 w-4" />
              {saveLabel}
            </button>
          )}
          <button
            type="button"
            onClick={onCancel}
            disabled={saving}
            className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium"
            style={{
              borderColor: "color-mix(in oklch, var(--destructive) 60%, var(--border))",
              color: "var(--destructive)",
            }}
          >
            <X className="h-4 w-4" />
            {t("js.common.cancel") !== "js.common.cancel"
              ? t("js.common.cancel")
              : "Cancel"}
          </button>
        </div>
      </div>
    </div>
  );
}
