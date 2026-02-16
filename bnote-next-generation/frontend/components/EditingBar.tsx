/**
 * BNote Next Generation - Sticky editing mode bar
 * Same look as rehearsal/concert edit: "Editing" label + Save + Cancel
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useI18n } from "@/contexts/I18nContext";
import { Save, X } from "@/components/icons";

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
    <div className="sticky top-0 z-30 rounded-box border border-base-300 bg-primary/10 px-4 py-3 shadow-sm text-base-content">
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
              className="btn btn-primary btn-sm gap-2"
            >
              <Save className="h-4 w-4" />
              {saveLabel}
            </button>
          ) : (
            <button
              type="button"
              onClick={onSave}
              disabled={saving}
              className="btn btn-primary btn-sm gap-2"
            >
              <Save className="h-4 w-4" />
              {saveLabel}
            </button>
          )}
          <button
            type="button"
            onClick={onCancel}
            disabled={saving}
            className="btn btn-outline btn-error btn-sm gap-2"
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
