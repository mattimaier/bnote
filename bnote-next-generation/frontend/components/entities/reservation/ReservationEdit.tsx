/**
 * BNote Next Generation - Reservation create/edit (full-page, same pattern as other entities)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { reservationsApi, type Reservation } from "@/lib/reservations-api";
import { locationsApi } from "@/lib/locations-api";
import { tasksApi } from "@/lib/tasks-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DatePicker } from "@/components/DatePicker";
import { SelectPicker } from "@/components/SelectPicker";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { NotesEditor } from "@/components/NotesEditor";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import type { SelectPickerOption } from "@/components/SelectPicker";

function toDatetimeLocal(s: string): string {
  if (!s) return "";
  const d = s.replace("T", " ").slice(0, 19);
  return d.length >= 16 ? d : s.slice(0, 16).replace("T", " ");
}

export function ReservationEdit() {
  const { id } = useEntityParams();
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const { showToast } = useToast();
  const isNew = id === "new";

  const [name, setName] = useState("");
  const [begin, setBegin] = useState("");
  const [end, setEnd] = useState("");
  const [locationId, setLocationId] = useState(0);
  const [contactId, setContactId] = useState(0);
  const [notes, setNotes] = useState("");
  const [locations, setLocations] = useState<SelectPickerOption[]>([]);
  const [contacts, setContacts] = useState<SelectPickerOption[]>([]);
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const loadData = useCallback(() => {
    if (!isNew && id) {
      const numId = parseInt(id, 10);
      if (!Number.isNaN(numId)) {
        setLoading(true);
        reservationsApi
          .get(numId)
          .then((r: Reservation) => {
            setName(r.name ?? "");
            setBegin(r.begin ? toDatetimeLocal(r.begin) : "");
            setEnd(r.end ? toDatetimeLocal(r.end) : "");
            setLocationId(r.location ?? 0);
            setContactId(r.contact ?? 0);
            setNotes(r.notes ?? "");
          })
          .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
          .finally(() => setLoading(false));
      }
    } else if (isNew) {
      const qBegin = searchParams.get("begin");
      const qEnd = searchParams.get("end");
      if (qBegin) setBegin(toDatetimeLocal(qBegin));
      if (qEnd) setEnd(toDatetimeLocal(qEnd));
      setLoading(false);
    }
  }, [id, isNew, searchParams, t]);

  useEffect(() => {
    if (!ready) return;
    loadData();
  }, [ready, loadData]);

  useEffect(() => {
    if (!ready) return;
    locationsApi
      .list()
      .then((list) => {
        const opts = (list ?? []).map((l) => ({ id: l.id, name: l.name }));
        setLocations([
          { id: 0, name: t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "—" },
          ...opts,
        ]);
      })
      .catch(() => setLocations([{ id: 0, name: "—" }]));
    tasksApi
      .getContacts()
      .then((list) => {
        const opts = (list ?? []).map((c) => ({
          id: c.id,
          name: c.name ?? "",
          email: c.email ?? null,
          instrument: c.instrument ?? null,
        }));
        setContacts([
          { id: 0, name: t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "—" },
          ...opts,
        ]);
      })
      .catch(() => setContacts([{ id: 0, name: "—" }]));
  }, [ready, t]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) {
      const msg =
        t("js.calendar.nameRequired") !== "js.calendar.nameRequired"
          ? t("js.calendar.nameRequired")
          : "Name is required";
      setError(msg);
      showToast(msg, "error");
      return;
    }
    const toApiDatetime = (s: string) => {
      if (!s) return "";
      const x = s.trim().replace(" ", "T").slice(0, 19);
      if (x.length < 16) return "";
      if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(x)) return x.replace("T", " ") + ":00";
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(x)) return x + ":00";
      return x.replace("T", " ").slice(0, 19);
    };
    const beginVal = toApiDatetime(begin);
    const endVal = end ? toApiDatetime(end) : beginVal;
    if (!beginVal) {
      const msg =
        t("js.calendar.beginRequired") !== "js.calendar.beginRequired"
          ? t("js.calendar.beginRequired")
          : "Start time is required";
      setError(msg);
      showToast(msg, "error");
      return;
    }
    setSaving(true);
    setError("");
    try {
      const payload = {
        name: name.trim(),
        begin: beginVal,
        end: endVal || beginVal,
        location: locationId > 0 ? locationId : null,
        contact: contactId > 0 ? contactId : null,
        notes: isEmptyEditorJson(notes) || !notes.trim() ? undefined : notes.trim(),
      };
      if (isNew) {
        const res = await reservationsApi.create(payload);
        showToast(
          t("js.calendar.reservationCreated") !== "js.calendar.reservationCreated"
            ? t("js.calendar.reservationCreated")
            : "Reservation created",
          "success"
        );
        router.replace(getEntityPath("reservation", res.id));
      } else {
        await reservationsApi.update(parseInt(id!, 10), payload);
        showToast(t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved", "success");
        router.replace(getEntityPath("reservation", id!, "view"));
      }
    } catch (err) {
      const msg = getErrorMessage(err, t, "js.common.saveFailed");
      setError(msg);
      showToast(msg, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!id || id === "new") return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) return;
    try {
      await reservationsApi.delete(numId);
      showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
      router.push("/calendar");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.deleteFailed"), "error");
    }
  };

  const handleCancel = useCallback(() => {
    if (isNew) router.push("/calendar");
    else router.push(getEntityPath("reservation", id!, "view"));
  }, [isNew, id, router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "reservation-edit-form",
      onCancel: () => onCancelRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isNew, saving, setEditingBar, clearEditingBar]);

  if (!ready) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <form id="reservation-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">{error}</div>
        )}
        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.calendar.name") !== "js.calendar.name" ? t("js.calendar.name") : "Name"} *
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.calendar.begin") !== "js.calendar.begin" ? t("js.calendar.begin") : "Start"} *
              </label>
              <DatePicker
                value={begin.slice(0, 16)}
                onChange={(v) => setBegin(v)}
                mode="datetime"
                locale={lang}
                appendSeconds
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.calendar.end") !== "js.calendar.end" ? t("js.calendar.end") : "End"}
              </label>
              <DatePicker
                value={end.slice(0, 16)}
                onChange={(v) => setEnd(v)}
                mode="datetime"
                locale={lang}
                appendSeconds
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.calendar.location") !== "js.calendar.location" ? t("js.calendar.location") : "Location"}
              </label>
              <SelectPicker
                options={locations}
                value={locationId}
                onChange={setLocationId}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel={t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : ""}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.calendar.contact") !== "js.calendar.contact" ? t("js.calendar.contact") : "Contact"}
              </label>
              <SelectPicker
                options={contacts}
                value={contactId}
                onChange={setContactId}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel={t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : ""}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.common.notes") !== "js.common.notes" ? t("js.common.notes") : "Notes"}
              </label>
              <NotesEditor
                value={notes}
                onChange={setNotes}
                placeholder={t("js.common.notes") !== "js.common.notes" ? t("js.common.notes") : "Notes"}
                id="reservation-notes-editor"
              />
            </div>
          </div>
        </div>
        {!isNew && <DetailDeleteSection canDelete entityTitle={name || undefined} onDelete={handleDelete} />}
      </form>
    </div>
  );
}
