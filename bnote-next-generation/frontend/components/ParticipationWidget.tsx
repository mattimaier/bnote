/**
 * BNote Next Generation - Participation Widget (React)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useCallback, useEffect, useState } from "react";
import { participationApi, type ParticipationState, type ParticipationStatus } from "@/lib/participation";
import { useToast } from "@/contexts/ToastContext";
import { ParticipationModal, type ParticipationModalStatus } from "./ParticipationModal";
import { useI18n } from "@/contexts/I18nContext";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { queryKeys } from "@/lib/query/keys";
import { useParticipationStatusQuery } from "@/lib/query/hooks/use-participation-query";

interface ParticipationWidgetProps {
  eventId: number;
  eventType: string;
  onStatusChange?: () => void;
  disabled?: boolean;
  initialState?: ParticipationState;
  queryMode?: "default" | "cache-first";
}

export function ParticipationWidget({
  eventId,
  eventType,
  onStatusChange,
  disabled = false,
  initialState,
  queryMode = "default",
}: ParticipationWidgetProps) {
  const { t } = useI18n();
  const { showToast } = useToast();
  const queryClient = useQueryClient();
  const type = eventType === "R" || eventType === "C" ? eventType : null;
  const { data, isPending } = useParticipationStatusQuery(eventId, type ?? "R", Boolean(type), initialState, {
    cacheFirst: queryMode === "cache-first",
  });
  const [status, setStatus] = useState<ParticipationStatus>(data?.status ?? "undecided");
  const [allowMaybe, setAllowMaybe] = useState(Boolean(data?.allow_maybe));
  const [isLocked, setIsLocked] = useState(Boolean(data?.is_locked));
  const [saving, setSaving] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalStatus, setModalStatus] = useState<ParticipationModalStatus | null>(null);

  useEffect(() => {
    if (!data) return;
    setStatus((data.status as ParticipationStatus) ?? "undecided");
    setAllowMaybe(data.allow_maybe ?? false);
    setIsLocked(data.is_locked ?? false);
  }, [data]);

  const saveMutation = useMutation({
    mutationFn: async (payload: { newStatus: ParticipationStatus; reason: string }) =>
      participationApi.saveStatus(eventId, type ?? eventType, payload.newStatus, payload.reason),
    onSuccess: (_res, vars) => {
      if (!type) return;
      queryClient.setQueryData(queryKeys.participation.status(type, eventId), (prev: unknown) => {
        const previous = (prev ?? {}) as {
          status?: ParticipationStatus;
          allow_maybe?: boolean;
          is_locked?: boolean;
          reason?: string | null;
        };
        return {
          ...previous,
          status: vars.newStatus,
          reason: vars.reason || null,
        };
      });
      void queryClient.invalidateQueries({ queryKey: ["participation", "batch"] });
      void queryClient.invalidateQueries({ queryKey: ["dashboard"] });
      void queryClient.invalidateQueries({ queryKey: ["list"] });
    },
  });

  const updateStatus = useCallback(
    async (newStatus: ParticipationStatus, reason = "") => {
      if (saving || saveMutation.isPending) return;
      setSaving(true);
      try {
        await saveMutation.mutateAsync({ newStatus, reason });
        setStatus(newStatus);
        const messages: Record<string, string> = {
          yes: t("js.participation.confirmed"),
          maybe: t("js.participation.maybeSaved"),
          no: t("js.participation.notAttending"),
          undecided: t("js.participation.cleared"),
        };
        showToast(messages[newStatus] ?? t("js.participation.statusUpdated"), "success");
        onStatusChange?.();
      } catch {
        showToast(t("js.error.participationUpdateFailed"), "error");
      } finally {
        setSaving(false);
      }
    },
    [onStatusChange, saveMutation, saving, showToast, t]
  );

  const handleClick = useCallback(
    (event: React.MouseEvent<HTMLButtonElement>, clicked: ParticipationStatus) => {
      event.preventDefault();
      event.stopPropagation();
      const loading = isPending && !data;
      if (isLocked || loading || disabled) return;
      if (clicked === status && status !== "undecided") {
        updateStatus("undecided", "");
        return;
      }
      if (clicked === "yes") {
        updateStatus("yes", "");
      } else {
        setModalStatus(clicked as ParticipationModalStatus);
        setModalOpen(true);
      }
    },
    [data, disabled, isLocked, isPending, status, updateStatus]
  );

  const handleModalConfirm = useCallback(
    (reason: string) => {
      if (modalStatus) updateStatus(modalStatus, reason);
      setModalOpen(false);
      setModalStatus(null);
    },
    [modalStatus, updateStatus]
  );

  const handleModalCancel = useCallback(() => {
    setModalOpen(false);
    setModalStatus(null);
  }, []);

  const loading = isPending && !data;
  if (loading) {
    return (
      <div className="flex flex-col gap-2 items-end">
        <p className="text-xs font-medium opacity-50 text-base-content/60">{t("js.event.participation")}</p>
        <div className="h-10 w-10 rounded-full border-2 border-base-300 animate-pulse" />
      </div>
    );
  }

  const showYes = status === "undecided" || status === "yes";
  const showMaybe = (status === "undecided" || status === "maybe") && allowMaybe;
  const showNo = status === "undecided" || status === "no";

  const btn = (s: ParticipationStatus, active: boolean) => {
    const base =
      "participation-btn w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed";
    const roleClass =
      s === "yes" ? "participation-btn-yes" : s === "maybe" ? "participation-btn-maybe" : "participation-btn-no";
    const activeClass = active
      ? s === "yes"
        ? "participation-active-yes"
        : s === "maybe"
          ? "participation-active-maybe"
          : "participation-active-no"
      : "";
    return `${base} ${roleClass} ${activeClass}`.trim();
  };

  return (
    <>
      <div className="flex flex-col gap-2 items-end shrink-0">
        <p className="text-xs font-medium text-base-content/60">{t("js.event.participation")}</p>
        <div className="flex items-center gap-3 justify-end">
          {showYes && (
            <button
              type="button"
              disabled={isLocked || saving || disabled}
              className={btn("yes", status === "yes")}
              onClick={(event) => handleClick(event, "yes")}
              aria-label={t("js.participation.participate")}
            >
              <svg className="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </button>
          )}
          {showMaybe && (
            <button
              type="button"
              disabled={isLocked || saving || disabled}
              className={btn("maybe", status === "maybe")}
              onClick={(event) => handleClick(event, "maybe")}
              aria-label={t("js.participation.maybe")}
            >
              <svg className="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </button>
          )}
          {showNo && (
            <button
              type="button"
              disabled={isLocked || saving || disabled}
              className={btn("no", status === "no")}
              onClick={(event) => handleClick(event, "no")}
              aria-label={t("js.participation.doNotParticipate")}
            >
              <svg className="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          )}
        </div>
      </div>

      <ParticipationModal
        open={modalOpen}
        status={modalStatus}
        statusLabel={modalStatus ? t(`js.participation.${modalStatus}`) : ""}
        reasonPlaceholder={
          modalStatus === "no" ? t("js.participation.reasonSuggested") : t("js.participation.reasonOptional")
        }
        onConfirm={handleModalConfirm}
        onCancel={handleModalCancel}
        confirmLabel={t("js.common.confirm")}
        cancelLabel={t("js.common.cancel")}
        reasonForLabel={t("js.participation.reasonFor")}
      />
    </>
  );
}
