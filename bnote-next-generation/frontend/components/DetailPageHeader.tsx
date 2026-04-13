/**
 * BNote Next Generation - Detail page header
 * Shared layout: title (left), optional actions (right), baseline aligned.
 * Use for all entity/profile detail views so the Edit button is always right, baseline aligned.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { Pencil } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { useI18n } from "@/contexts/I18nContext";

export interface DetailPageHeaderProps {
  /** Page title (e.g. entity name or "Meine Kontaktdaten") */
  title: React.ReactNode;
  /** Optional subtitle below title */
  subtitle?: React.ReactNode;
  /** Content on the right (e.g. Edit button). Baseline-aligned with title. */
  right?: React.ReactNode;
}

export function DetailPageHeader({ title, subtitle, right }: DetailPageHeaderProps) {
  return (
    <div className="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
      <div className="min-w-0">
        <h1 className="text-2xl font-bold text-base-content break-words whitespace-normal leading-tight">
          {title}
        </h1>
        {subtitle != null && (
          <p className="mt-1 text-sm text-base-content/60">
            {subtitle}
          </p>
        )}
      </div>
      {right != null && (
        <div className="shrink-0 flex items-baseline">
          {right}
        </div>
      )}
    </div>
  );
}

/** Standard Edit button for detail views (right, primary style, Pencil icon). */
export function DetailEditButton({
  onClick,
  label,
}: {
  onClick: () => void;
  label?: string;
}) {
  const { t } = useI18n();
  const text = label ?? (t("js.common.edit") !== "js.common.edit" ? t("js.common.edit") : "Bearbeiten");
  return (
    <ActionButton onClick={onClick} data-bnote-hotkey-action="edit">
      <Pencil className="h-4 w-4" />
      {text}
    </ActionButton>
  );
}
