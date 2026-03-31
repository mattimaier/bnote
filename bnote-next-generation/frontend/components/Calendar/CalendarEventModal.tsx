/**
 * BNote Next Generation - Calendar Event Detail Modal
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { Modal } from "@/components/Modal";
import { ConfirmModal } from "@/components/ConfirmModal";
import { Avatar } from "@/components/Avatar";
import { getIcon } from "@/components/icons";
import type { CalendarEvent } from "@/lib/calendar-api";
import { reservationsApi } from "@/lib/reservations-api";
import { appointmentsApi } from "@/lib/appointments-api";
import { notesToPlainText } from "@/lib/editorjs-notes";

interface CalendarEventModalProps {
  event: CalendarEvent | null;
  open: boolean;
  canEditCalendarEntries: boolean;
  onClose: () => void;
  onDeleted?: () => void;
  onEditReservation?: (id: number) => void;
  onEditAppointment?: (id: number) => void;
}

export function CalendarEventModal({
  event,
  open,
  canEditCalendarEntries,
  onClose,
  onDeleted,
  onEditReservation,
  onEditAppointment,
}: CalendarEventModalProps) {
  const { t } = useI18n();
  const [confirmDeleteOpen, setConfirmDeleteOpen] = React.useState(false);
  const [deleting, setDeleting] = React.useState(false);

  const bnoteType = event?.extendedProps?.bnoteType ?? "";
  const isReservation = bnoteType === "reservation";
  const isAppointment = bnoteType === "appointment";
  const isBirthday = bnoteType === "contact";
  const isCalendarNative = isReservation || isAppointment;
  const canEditDelete = canEditCalendarEntries && isCalendarNative;
  const rawId = event?.id?.toString().includes("-") ? event.id.split("-")[1] : event?.id;
  const numId = parseInt(String(rawId ?? "0"), 10);
  const noEditTooltip = "Keine Bearbeitungsrechte";

  const handleDelete = async () => {
    if (!numId) return;
    setDeleting(true);
    try {
      if (isReservation) {
        await reservationsApi.delete(numId);
      } else if (isAppointment) {
        await appointmentsApi.delete(numId);
      }
      onDeleted?.();
      onClose();
    } finally {
      setDeleting(false);
      setConfirmDeleteOpen(false);
    }
  };

  const CakeIcon = getIcon("cake");

  if (!event) return null;

  const link = event.extendedProps?.link ?? "/dashboard";
  const details = event.extendedProps?.details ?? {};
  const avatarEmail = event.extendedProps?.avatarEmail;

  return (
    <>
      <Modal open={open} onClose={onClose} title={notesToPlainText(event.title ?? "")}>
        <div className="space-y-4">
          {isBirthday && (
            <div className="flex items-center gap-3">
              <Avatar
                email={avatarEmail ?? undefined}
                name={notesToPlainText(event.title ?? "")}
                size={40}
                variant="birthday"
              />
              <CakeIcon className="h-5 w-5 text-base-content/70" aria-hidden />
            </div>
          )}

          {Object.keys(details).length > 0 && (
            <dl className="space-y-2 text-sm">
              {Object.entries(details)
                .filter(
                  ([key]) =>
                    !/abst\.?end|end_vote|Abst\.?End/i.test(key)
                )
                .map(([key, value]) => (
                  <div key={key} className="flex gap-2">
                    <dt className="text-base-content/60 shrink-0">{key}:</dt>
                    <dd className="text-base-content">{String(value)}</dd>
                  </div>
                ))}
            </dl>
          )}

          <div className="flex flex-wrap gap-2">
            <Link
              href={link}
              className="btn btn-primary btn-sm"
              onClick={onClose}
            >
              {t("js.common.details") !== "js.common.details"
                ? t("js.common.details")
                : "Details"}
            </Link>
            {isCalendarNative && (
              <>
                <button
                  type="button"
                  className="btn btn-soft btn-sm"
                  onClick={() => {
                    if (!canEditDelete) return;
                    onClose();
                    if (isReservation) onEditReservation?.(numId);
                    else if (isAppointment) onEditAppointment?.(numId);
                  }}
                  disabled={!canEditDelete}
                  title={!canEditDelete ? noEditTooltip : undefined}
                >
                  {t("js.common.edit") !== "js.common.edit" ? t("js.common.edit") : "Edit"}
                </button>
                <button
                  type="button"
                  className="btn btn-soft btn-error btn-sm"
                  onClick={() => setConfirmDeleteOpen(true)}
                  disabled={deleting || !canEditDelete}
                  title={!canEditDelete ? noEditTooltip : undefined}
                >
                  {t("js.common.delete") !== "js.common.delete" ? t("js.common.delete") : "Delete"}
                </button>
              </>
            )}
          </div>
        </div>
      </Modal>

      <ConfirmModal
        open={confirmDeleteOpen}
        onClose={() => setConfirmDeleteOpen(false)}
        title={t("js.common.delete") !== "js.common.delete" ? t("js.common.delete") : "Delete"}
        message={
          t("js.common.deleteConfirm") !== "js.common.deleteConfirm"
            ? t("js.common.deleteConfirm")
            : "Are you sure you want to delete this?"
        }
        confirmLabel={t("js.common.delete") !== "js.common.delete" ? t("js.common.delete") : "Delete"}
        cancelLabel={t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
        onConfirm={handleDelete}
        variant="danger"
      />
    </>
  );
}
