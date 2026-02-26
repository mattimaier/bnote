/**
 * BNote Next Generation - Sticky editing mode bar
 * Same look as rehearsal/concert edit: "Editing" label + Save + Cancel
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useI18n } from "@/contexts/I18nContext";
import { Save, X } from "@/components/icons";
import { ActionButton, actionButtonClassNames } from "@/components/ActionButton";

export interface EditingBarProps {
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
    <div className="z-30 w-full rounded-b-box border border-base-300 border-t-0 bg-[color-mix(in_oklch,var(--primary)_10%,white)] px-4 py-3 shadow-sm text-base-content">
      <div className="mx-auto flex max-w-[1200px] flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
              className={actionButtonClassNames("primary")}
            >
              <Save className="h-4 w-4" />
              {saveLabel}
            </button>
          ) : (
            <ActionButton
              type="button"
              onClick={onSave}
              disabled={saving}
            >
              <Save className="h-4 w-4" />
              {saveLabel}
            </ActionButton>
          )}
          <ActionButton
            variant="outline-error"
            onClick={onCancel}
            disabled={saving}
          >
            <X className="h-4 w-4" />
            {t("js.common.cancel") !== "js.common.cancel"
              ? t("js.common.cancel")
              : "Cancel"}
          </ActionButton>
        </div>
      </div>
    </div>
  );
}
