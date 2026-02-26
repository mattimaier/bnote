/**
 * BNote Next Generation - Appointment detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { appointmentsApi, getPendingAppointment, type Appointment } from "@/lib/appointments-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { getEventTypeConfig } from "@/lib/entity-config";
import { getIcon } from "@/components/icons";
import { NotesContent } from "@/components/NotesContent";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { formatDateTimeShort } from "@/lib/date-time";
import { getErrorMessage } from "@/lib/error-utils";
import { Spinner } from "@/components/Spinner";

export function AppointmentDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [item, setItem] = useState<Appointment | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!id || id === "new" || !ready) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    const cached = getPendingAppointment(numId);
    if (cached) {
      setItem(cached);
      setLoading(false);
      return;
    }
    setLoading(true);
    appointmentsApi
      .get(numId)
      .then(setItem)
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
      .finally(() => setLoading(false));
  }, [id, ready, t]);

  if (!ready) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (id === "new") return null;

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">
          {error || (t("js.common.notFound") !== "js.common.notFound" ? t("js.common.notFound") : "Not found.")}
        </p>
      </div>
    );
  }

  const typeConfig = getEventTypeConfig("appointment", t);
  const AppointmentIcon = getIcon(typeConfig.icon);

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader
        title={item.name || emptyText}
        subtitle={
          <span className={`inline-flex items-center gap-1.5 event-badge ${typeConfig.badgeClass} w-fit`}>
            <AppointmentIcon className="h-3.5 w-3.5 shrink-0" />
            <span>{typeConfig.label}</span>
          </span>
        }
        right={<DetailEditButton onClick={() => router.push(getEntityPath("appointment", item.id, "edit"))} />}
      />

      <DetailCard>
        <div className="space-y-4">
          <div>
            <span className="text-base-content/60 text-sm">{t("js.calendar.begin") !== "js.calendar.begin" ? t("js.calendar.begin") : "Start"}:</span>{" "}
            {item.begin ? formatDateTimeShort(item.begin, lang) : emptyText}
          </div>
          <div>
            <span className="text-base-content/60 text-sm">{t("js.calendar.end") !== "js.calendar.end" ? t("js.calendar.end") : "End"}:</span>{" "}
            {item.end ? formatDateTimeShort(item.end, lang) : emptyText}
          </div>
          {item.locationname && (
            <div>
              <span className="text-base-content/60 text-sm">{t("js.calendar.location") !== "js.calendar.location" ? t("js.calendar.location") : "Location"}:</span>{" "}
              {item.locationname}
            </div>
          )}
          {item.contactname && (
            <div>
              <span className="text-base-content/60 text-sm">{t("js.calendar.contact") !== "js.calendar.contact" ? t("js.calendar.contact") : "Contact"}:</span>{" "}
              {item.contactname}
            </div>
          )}
          {item.notes?.trim() && !isEmptyEditorJson(item.notes) && (
            <div>
              <span className="text-base-content/60 text-sm block mb-1">{t("js.common.notes") !== "js.common.notes" ? t("js.common.notes") : "Notes"}:</span>
              <NotesContent value={item.notes} className="text-sm" />
            </div>
          )}
        </div>
      </DetailCard>
    </div>
  );
}
