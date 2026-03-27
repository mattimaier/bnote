/**
 * BNote Next Generation - Event Detail (rehearsal / concert)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { ParticipationWidget } from "@/components/ParticipationWidget";
import { ParticipationDiagram, type ParticipationStats } from "@/components/ParticipationDiagram";
import { ParticipantOverview, type InstrumentGroup } from "@/components/ParticipantOverview";
import { getIcon } from "@/components/icons";
import { getEventTypeConfig, type EventDisplayType } from "@/lib/event-utils";
import { safeString } from "@/lib/string-utils";
import { AddressLink } from "@/components/AddressLink";
import { MarkdownText } from "@/components/MarkdownText";
import { NotesContent } from "@/components/NotesContent";
import { NotesEditor } from "@/components/NotesEditor";
import { getAddressInfo } from "@/lib/address-utils";
import { formatDateShort, formatDateTimeShort, formatTimeShort } from "@/lib/date-time";
import { getStatusPillStyle, isQuickActionsEnabled } from "@/lib/entity-config";
import { getEntityPath } from "@/lib/entities/paths";
import { canViewEntityType } from "@/lib/entities/permissions";
import { useModules } from "@/lib/use-modules";
import type {
  AccommodationObj,
  ConcertMeta,
  ContactObj,
  EditableParticipant,
  EditableSong,
  EquipmentObj,
  EventContact,
  EventDetailForm,
  GroupObj,
  LocationObj,
  ConductorObj,
  OutfitObj,
  ProgramObj,
  RehearsalMeta,
  SimpleOption,
  SongObj,
} from "@/lib/entities/event/types";
import { MultiSelect } from "@/components/entities/event/MultiSelect";
import { ParticipantEditor } from "@/components/entities/event/ParticipantEditor";
import { SelectPicker } from "@/components/SelectPicker";
import { SelectedItemsList } from "@/components/entities/event/SelectedItemsList";
import { StatusPicker } from "@/components/entities/event/StatusPicker";
import { getEventViewActions } from "@/lib/entities/event/actions";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { DetailEditButton } from "@/components/DetailPageHeader";
import { LayoutList, Trash2 } from "@/components/icons";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { DETAIL_SECTION_CLASS } from "@/components/DetailSection";
import { DatePicker } from "@/components/DatePicker";
import { EntityLink } from "@/components/EntityLink";
import { useEventDetailData } from "@/lib/entities/event/useEventDetailData";
import { EventParticipationShareModal } from "@/components/entities/event/EventParticipationShareModal";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { normalizeCompany } from "@/lib/dashboard-utils";
import {
  addMinutesToInputDateTime,
  deriveEventContacts,
  getGroupContacts,
  syncEndDate,
} from "@/lib/entities/event/rehearsal-prefill";
import { concertsApi } from "@/lib/concerts-api";
import { rehearsalsApi } from "@/lib/rehearsals-api";

export interface EventDetailProps {
  type?: string;
  id?: string;
  mode?: "view" | "edit";
  /** When provided (e.g. debug), used as data and no API load is performed. */
  initialData?: Record<string, unknown>;
  /** Optional content to render inside the root container after the main content (e.g. comments). */
  renderAfterContent?: React.ReactNode;
}

export function EventDetail({
  type: typeProp,
  id: idProp,
  mode: modeProp,
  initialData: initialDataProp,
  renderAfterContent,
}: EventDetailProps = {}) {
  const searchParams = useSearchParams();
  const router = useRouter();
  const modules = useModules();
  const { t, ready, lang } = useI18n();
  const { showToast } = useToast();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const type = typeProp ?? searchParams.get("type") ?? "";
  const id = idProp ?? searchParams.get("id") ?? "";
  const { data, meta, loading, error, setError, reload: loadData, loadMeta } = useEventDetailData(
    type,
    id,
    initialDataProp ?? null,
    ready,
    t
  );
  const [isEditing, setIsEditing] = useState(false);
  const [shareModalOpen, setShareModalOpen] = useState(false);
  const [configuredBandName, setConfiguredBandName] = useState("");
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState("");
  const [form, setForm] = useState<EventDetailForm | null>(null);

  const toInputDateTime = (value?: string) => {
    if (!value) return "";
    const normalized = value.replace(" ", "T");
    return normalized.length >= 16 ? normalized.slice(0, 16) : normalized;
  };

  const fromInputDateTime = (value: string) => {
    if (!value) return "";
    const normalized = value.replace(" ", "T");
    return normalized.length === 16 ? `${normalized}:00` : normalized;
  };


  const mapParticipationToStatus = (value: number | null | undefined): EditableParticipant["participate"] => {
    if (value === 1) return "yes";
    if (value === 2) return "maybe";
    if (value === 0) return "no";
    return "pending";
  };

  const mapStatusToParticipation = (value: EditableParticipant["participate"]) => {
    if (value === "yes") return 1;
    if (value === "maybe") return 2;
    if (value === "no") return 0;
    return null;
  };

  const arraysEqual = (a: number[], b: number[]) => {
    if (a.length !== b.length) return false;
    const aSorted = [...a].sort((x, y) => x - y);
    const bSorted = [...b].sort((x, y) => x - y);
    return aSorted.every((val, idx) => val === bSorted[idx]);
  };

  const module = type === "rehearsal" ? "rehearsals" : "concerts";
  const isNew = id === "new";
  const numId = id && !isNew ? parseInt(String(id), 10) : NaN;
  const eventType = type === "rehearsal" ? "R" : "C";
  const canEdit = isNew || Boolean((data as { canEdit?: boolean } | null)?.canEdit);
  const canEditParticipation = isNew || Boolean((data as { canEditParticipation?: boolean } | null)?.canEditParticipation);

  const rehearsalMeta = type === "rehearsal" ? (meta as RehearsalMeta | null) : null;
  const concertMeta = type === "concert" ? (meta as ConcertMeta | null) : null;
  const shouldEdit = modeProp === "edit" || searchParams.get("edit") === "1";

  useEffect(() => {
    if (!ready || loading || error || !data) return;
    if (!shouldEdit || !canEdit) return;
    if (!isEditing) {
      setForm(buildForm());
      setIsEditing(true);
      if (!meta) {
        loadMeta();
      }
    }
  }, [ready, loading, error, shouldEdit, canEdit, data, isEditing, meta, loadMeta]);

  useEffect(() => {
    if (shouldEdit || !isEditing) return;
    setIsEditing(false);
    setForm(null);
    setSaveError("");
  }, [shouldEdit, isEditing]);

  useEffect(() => {
    if (!isEditing || !form || form.manualContactsInitialized || !meta) return;
    const members =
      type === "concert" ? (meta as ConcertMeta | null)?.groupMembers : (meta as RehearsalMeta | null)?.groupMembers;
    const groupContacts = getGroupContacts(form.groups, members);
    const nextManual = form.eventContacts.filter((id) => !groupContacts.has(id));
    setForm({ ...form, manualContacts: nextManual, manualContactsInitialized: true });
  }, [isEditing, form?.manualContactsInitialized, form?.groups, form?.eventContacts, form, meta]);

  useEffect(() => {
    if (!isEditing || !form) return;
    const members =
      type === "concert" ? (meta as ConcertMeta | null)?.groupMembers : (meta as RehearsalMeta | null)?.groupMembers;
    const { eventContacts, validExcludedContacts } = deriveEventContacts(
      form.groups,
      form.manualContacts,
      form.excludedContacts,
      members
    );
    if (!arraysEqual(eventContacts, form.eventContacts) || !arraysEqual(validExcludedContacts, form.excludedContacts)) {
      setForm({ ...form, eventContacts, excludedContacts: validExcludedContacts });
    }
  }, [isEditing, form?.groups, form?.manualContacts, form?.excludedContacts, form, meta, type, setForm]);

  useEffect(() => {
    if (!isEditing || !form) return;
    const contactOptions = (type === "concert" ? concertMeta?.contacts : rehearsalMeta?.contacts) ?? [];
    const currentByContact = new Map(form.participants.map((p) => [p.contactId, p]));
    const nextParticipants: EditableParticipant[] = [];

    form.eventContacts.forEach((contactId) => {
      const existing = currentByContact.get(contactId);
      if (existing) {
        nextParticipants.push(existing);
        return;
      }
      const fallback = contactOptions.find((c) => c.id === contactId);
      nextParticipants.push({
        userId: 0,
        contactId,
        name: fallback?.name ?? emptyText,
        instrument: emptyText,
        participate: "pending",
      });
    });

    if (
      nextParticipants.length !== form.participants.length ||
      nextParticipants.some((p, idx) => form.participants[idx]?.contactId !== p.contactId)
    ) {
      setForm({ ...form, participants: nextParticipants });
    }
  }, [isEditing, form?.eventContacts, form?.participants, form, setForm, type, concertMeta, rehearsalMeta]);

  const cancelEditRef = useRef(() => {});
  const saveEditRef = useRef(async () => {});
  const { setEditingBar, clearEditingBar } = useEditingBar();
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    if (!isEditing) {
      barTokenRef.current = null;
      setEditingBar(null);
      return;
    }
    const token = setEditingBar({
      isNew: !!isNew,
      saving,
      submitFormId: "",
      onCancel: () => cancelEditRef.current?.(),
      onSave: () => saveEditRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isEditing, saving, setEditingBar, clearEditingBar, isNew]);
  const appName = t("js.common.appName") !== "js.common.appName" ? t("js.common.appName") : "BNote";
  useEffect(() => {
    if (!ready) return;
    let cancelled = false;
    api
      .get<{ company?: unknown }>("auth", "getPublicConfig")
      .then((config) => {
        if (cancelled) return;
        const normalized = normalizeCompany(config?.company);
        setConfiguredBandName(normalized || appName);
      })
      .catch(() => {
        if (!cancelled) setConfiguredBandName(appName);
      });
    return () => {
      cancelled = true;
    };
  }, [ready, appName]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="w-full max-w-none px-0 py-0 md:max-w-7xl md:mx-auto md:px-4 md:py-3">
        <div className="rounded-lg border border-error bg-error/15 text-error px-4 py-3">
          {error || (t("js.common.notFound") !== "js.common.notFound" ? t("js.common.notFound") : "Not found")}
        </div>
      </div>
    );
  }

  const loc = data.location as LocationObj | undefined;
  const conductor = data.conductor as ConductorObj | undefined;
  const locationName = safeString(loc?.name);
  const locationAddressInfo = loc?.address ? getAddressInfo(loc.address) : null;
  const hasLocationName = Boolean(locationName);
  const hasLocationAddress = Boolean(locationAddressInfo);
  const conductorName = safeString(conductor?.name);
  const title =
    safeString(data.title) ||
    locationName ||
    conductorName ||
    (type === "concert" ? t("js.event.performance") : t("js.event.rehearsal"));
  const begin = (data.begin ?? data.date ?? data.event_begin) as string | undefined;
  const end = data.end as string | undefined;
  const currentBegin = isEditing && form ? fromInputDateTime(form.begin) : begin;
  const currentEnd = isEditing && form ? fromInputDateTime(form.end) : end;
  const currentLocationId = isEditing && form ? form.locationId : (loc?.id ?? 0);
  const currentLocationName = isEditing && form
    ? (((type === "concert" ? concertMeta?.locations : rehearsalMeta?.locations) ?? []).find(
        (option) => option.id === currentLocationId
      )?.name ?? "")
    : locationName;
  const tba = t("js.event.tba");
  const dateStr = formatDateShort(currentBegin, lang) ?? tba;
  const timeStr = formatTimeShort(currentBegin, lang) ?? tba;
  const endTimeStr = currentEnd ? formatTimeShort(currentEnd, lang) ?? tba : null;
  const status = (data.status as string) ?? "planned";
  const approveUntil = data.approve_until as string | undefined;
  const notes = data.notes as string | undefined;
  const organizer = data.organizer as string | undefined;
  const meetingtime = data.meetingtime as string | undefined;
  const songsToPractice = (data.songsToPractice ?? data.songs_to_practice) as SongObj[] | undefined;
  const participationStats = data.participationStats as ParticipationStats | undefined;
  const participantsByInstrument = (data.participantsByInstrument ?? data.participants_by_instrument) as
    | InstrumentGroup[]
    | undefined;
  const eventContacts = (data.eventContacts ?? []) as EventContact[];

  // Concert metadata
  const groups = (data.groups ?? []) as GroupObj[];
  const program = data.program as ProgramObj | undefined;
  const outfit = data.outfit as OutfitObj | undefined;
  const equipment = (data.equipment ?? []) as EquipmentObj[];
  const accommodation = data.accommodation as AccommodationObj | undefined;
  const payment = data.payment as number | null | undefined;
  const conditions = data.conditions as string | undefined;
  const contact = data.contact as (ContactObj & { firstname?: string; surname?: string; lastname?: string }) | undefined;
  const contactNameParts = [
    safeString(contact?.firstname),
    safeString(contact?.surname ?? contact?.lastname),
  ].filter(Boolean);
  const contactName = contactNameParts.length > 0 ? contactNameParts.join(" ") : safeString(contact?.name);

  const displayType: EventDisplayType = type === "concert" ? "performance" : (type as EventDisplayType);
  const eventTypeConfig = getEventTypeConfig(displayType, t);
  const EventIcon = getIcon(eventTypeConfig.icon);
  const ShareIcon = getIcon("share");

  const statusLabel =
    status === "confirmed"
      ? t("js.event.status.confirmed")
      : status === "cancelled"
        ? t("js.event.status.cancelled")
        : status === "hidden"
          ? t("js.event.status.hidden")
          : t("js.event.status.planned");
  const statusLabelFor = (value: string) =>
    value === "confirmed"
      ? t("js.event.status.confirmed")
      : value === "cancelled"
        ? t("js.event.status.cancelled")
        : value === "hidden"
          ? t("js.event.status.hidden")
          : t("js.event.status.planned");
  const selectedCountLabel = (count: number) =>
    (t("js.common.selectedCount") !== "js.common.selectedCount" ? t("js.common.selectedCount") : "{count} selected").replace(
      "{count}",
      String(count)
    );

  const safeDate = (value?: string) => {
    if (!value) return null;
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
  };
  const now = new Date();
  const eventEnd = safeDate(end) ?? safeDate(begin);
  const deadlineDate = safeDate(approveUntil);
  const isPastEvent = eventEnd ? eventEnd.getTime() < now.getTime() : false;
  const isPastDeadline = deadlineDate ? deadlineDate.getTime() < now.getTime() : false;
  const participationDisabled = isPastEvent || isPastDeadline;
  const shareBandName = configuredBandName || appName;

  function buildForm() {
    const participantRows: EditableParticipant[] =
      participantsByInstrument?.flatMap((group) =>
        group.participants
          .filter((p) => typeof p.userId === "number")
          .map((p) => ({
            userId: p.userId ?? 0,
            contactId: p.id,
            name: p.name ?? "",
            instrument: group.instrument.name ?? "",
            participate: mapParticipationToStatus(p.participate),
          }))
      ) ?? [];

    const initialContacts = Array.from(
      new Set(
        eventContacts.length > 0 ? eventContacts.map((c) => c.id) : participantRows.map((p) => p.contactId)
      )
    );

    return {
      title: safeString(data?.title) || "",
      begin: toInputDateTime(begin),
      end: toInputDateTime(end),
      approveUntil: toInputDateTime(approveUntil),
      meetingtime: toInputDateTime(meetingtime),
      status,
      notes: notes ?? "",
      organizer: organizer ?? "",
      payment: payment != null ? String(payment) : "",
      conditions: conditions ?? "",
      locationId: loc?.id ?? 0,
      conductorId: conductor?.id ?? 0,
      contactId: contact?.id ?? 0,
      programId: program?.id ?? 0,
      outfitId: outfit?.id ?? 0,
      accommodationId: accommodation?.id ?? 0,
      groups: groups.map((g) => g.id ?? 0).filter((g) => g > 0),
      equipment: equipment.map((e) => e.id ?? 0).filter((e) => e > 0),
      eventContacts: initialContacts,
      manualContacts: initialContacts,
      excludedContacts: [],
      manualContactsInitialized: false,
      songs:
        songsToPractice
          ?.filter((song) => typeof song.id === "number" && song.id > 0)
          .map((song) => ({
            id: song.id ?? 0,
            title: safeString(song.title) || "",
            notes: song.notes ?? "",
          })) ?? [],
      participants: participantRows,
    };
  }

  const startEdit = () => {
    setSaveError("");
    setForm(buildForm());
    setIsEditing(true);
    if (!meta) {
      loadMeta();
    }
    if (typeProp !== undefined && idProp !== undefined) {
      router.push(getEntityPath(typeProp, idProp, "edit"));
    } else {
      const params = new URLSearchParams(searchParams.toString());
      params.set("edit", "1");
      router.push(`/entity?${params.toString()}`);
    }
  };

  const cancelEdit = () => {
    setIsEditing(false);
    setForm(null);
    setSaveError("");
    if (isNew) {
      router.replace(type === "rehearsal" ? "/rehearsals" : "/concerts");
      return;
    }
    if (typeProp !== undefined && idProp !== undefined) {
      router.replace(getEntityPath(typeProp, idProp, "view"));
    } else {
      const params = new URLSearchParams(searchParams.toString());
      params.delete("edit");
      router.replace(`/entity?${params.toString()}`);
    }
  };

  const saveEdit = async () => {
    if (!form) return;
    setSaving(true);
    setSaveError("");
    try {
      const baseFields =
        type === "concert"
          ? {
              title: form.title.trim(),
              begin: fromInputDateTime(form.begin),
              end: fromInputDateTime(form.end),
              meetingtime: fromInputDateTime(form.meetingtime),
              approve_until: fromInputDateTime(form.approveUntil),
              status: form.status,
              notes: form.notes,
              organizer: form.organizer,
              payment: form.payment,
              conditions: form.conditions,
              location: form.locationId,
              contact: form.contactId,
              program: form.programId,
              outfit: form.outfitId,
              accommodation: form.accommodationId,
            }
          : {
              begin: fromInputDateTime(form.begin),
              end: fromInputDateTime(form.end),
              approve_until: fromInputDateTime(form.approveUntil),
              status: form.status,
              notes: form.notes,
              location: form.locationId,
              conductor: form.conductorId,
            };

      if (isNew) {
        const payload = {
          fields: baseFields,
          groups: form.groups,
          equipment: type === "concert" ? form.equipment : undefined,
          contacts: form.eventContacts,
          songs: type === "rehearsal" ? form.songs.map((song) => ({ id: song.id, notes: song.notes })) : undefined,
          participants: canEditParticipation
            ? form.participants.map((participant) => ({
                userId: participant.userId,
                participate: mapStatusToParticipation(participant.participate),
              }))
            : undefined,
        };
        const result = await api.post<{ id?: number }>(module, "create", payload);
        setIsEditing(false);
        setForm(null);
        const newId = result?.id;
        if (typeProp !== undefined && newId != null && newId > 0) {
          router.replace(getEntityPath(typeProp, String(newId), "view"));
        } else {
          router.replace("/dashboard");
        }
      } else {
        await api.post(module, "update", {
          id: numId,
          fields: baseFields,
          groups: form.groups,
          equipment: type === "concert" ? form.equipment : undefined,
          contacts: form.eventContacts,
          songs: type === "rehearsal" ? form.songs.map((song) => ({ id: song.id, notes: song.notes })) : undefined,
          participants: canEditParticipation
            ? form.participants.map((participant) => ({
                userId: participant.userId,
                participate: mapStatusToParticipation(participant.participate),
              }))
            : undefined,
        });
        setIsEditing(false);
        setForm(null);
        if (typeProp !== undefined && idProp !== undefined) {
          router.replace(getEntityPath(typeProp, idProp, "view"));
        } else {
          const params = new URLSearchParams(searchParams.toString());
          params.delete("edit");
          router.replace(`/entity?${params.toString()}`);
        }
        await loadData();
      }
    } catch (err) {
      setSaveError(getErrorMessage(err, t, "js.common.saveFailed"));
    } finally {
      setSaving(false);
    }
  };

  cancelEditRef.current = cancelEdit;
  saveEditRef.current = saveEdit;

  const deleteCurrentEvent = async () => {
    if (isNew || Number.isNaN(numId) || numId <= 0) return;
    try {
      if (type === "rehearsal") {
        await rehearsalsApi.delete(numId);
      } else {
        await concertsApi.delete(numId);
      }
      showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
      router.push(type === "rehearsal" ? "/rehearsals" : "/concerts");
    } catch (err) {
      setSaveError(getErrorMessage(err, t, "js.common.deleteFailed"));
      throw err;
    }
  };

  const updateSongSelection = (selectedIds: number[]) => {
    if (!form) return;
    const available = (type === "rehearsal" ? (meta as RehearsalMeta | null)?.songs : []) ?? [];
    const existing = new Map(form.songs.map((song) => [song.id, song]));
    const next = selectedIds.map((id) => {
      const current = existing.get(id);
      const metaSong = available.find((song) => song.id === id);
      return {
        id,
        title: current?.title ?? metaSong?.title ?? "",
        notes: current?.notes ?? "",
      };
    });
    setForm({ ...form, songs: next });
  };

  return (
    <div className="w-full max-w-none px-0 py-0 space-y-3 md:max-w-7xl md:mx-auto md:space-y-6 md:px-4 md:py-3">
      {/* Header + participation widget */}
      <div
        className={DETAIL_SECTION_CLASS}
      >
        <div className="flex flex-col gap-3 md:gap-4">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <div
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white ${type === "rehearsal" ? "bg-primary" : "bg-accent"}`}
              >
                <EventIcon className="h-5 w-5" />
              </div>
              <div className="flex flex-wrap items-center gap-2 min-w-0">
                {isEditing && form && type === "concert" ? (
                  <input
                    type="text"
                    value={form.title}
                    onChange={(event) => setForm({ ...form, title: event.target.value })}
                    className="text-2xl font-bold rounded-md border border-base-300 bg-base-100 text-base-content px-2 py-1 min-w-0 flex-1"
                  />
                ) : (
                  <h1 className="text-2xl font-bold text-base-content break-words whitespace-normal leading-tight">
                    {title}
                  </h1>
                )}
                <span className={`event-badge ${eventTypeConfig.badgeClass}`}>{eventTypeConfig.label}</span>
              </div>
            </div>
            <div className="mt-2 space-y-1">
              <p className="text-sm text-base-content/60">
                {dateStr} · {timeStr}
                {endTimeStr ? ` - ${endTimeStr}` : ""}
              </p>
              {currentLocationName && (
                <p className="text-sm text-base-content/60">
                  <EntityLink entityType="location" id={currentLocationId} name={currentLocationName} modules={modules} />
                </p>
              )}
            </div>
            {isPastEvent && (
              <p className="mt-2 text-xs font-medium text-error">
                {t("js.event.detail.pastEvent")}
              </p>
            )}
            {!isPastEvent && isPastDeadline && (
              <p className="mt-2 text-xs font-medium text-base-content/60">
                {t("js.event.detail.participationClosed")}
              </p>
            )}
            {/* Top section only shows name + date/time (address in details card) */}
          </div>
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center md:justify-end">
            {canEdit && !isEditing && <DetailEditButton onClick={startEdit} />}
            {!isEditing && participationStats && (participationStats.total ?? 0) > 0 && (
              <button
                type="button"
                className="btn btn-soft btn-primary"
                onClick={() => setShareModalOpen(true)}
              >
                <ShareIcon className="h-4 w-4" />
                <span>
                  {t("js.event.share.button") !== "js.event.share.button"
                    ? t("js.event.share.button")
                    : "Share overview"}
                </span>
              </button>
            )}
            {!isEditing && (type === "rehearsal" || type === "concert") && (
              <div className="sm:ml-auto">
                <ParticipationWidget
                  eventId={numId}
                  eventType={eventType}
                  onStatusChange={loadData}
                  disabled={participationDisabled}
                />
              </div>
            )}
          </div>
        </div>
      </div>

      {saveError && (
        <div className="rounded-lg border border-error bg-error/15 text-error px-4 py-3 text-sm">
          {saveError}
        </div>
      )}

      {/* Event actions (view mode only; config: features.showQuickActions) */}
      {!isEditing && isQuickActionsEnabled() && (type === "rehearsal" || type === "concert") && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <h2 className="text-sm font-semibold mb-2 md:mb-3 text-base-content/60">
            {t("js.event.actions.title") !== "js.event.actions.title" ? t("js.event.actions.title") : "Actions"}
          </h2>
          <div className="grid grid-cols-2 lg:grid-cols-3 gap-3">
            {getEventViewActions(type as "rehearsal" | "concert").map((action) => {
              const Icon = getIcon(action.icon);
              const cardClass = `group flex flex-col items-center gap-3 p-4 rounded-lg border border-base-300 transition-colors ${action.comingSoon ? "opacity-90 cursor-not-allowed" : "hover:border-primary/30 hover:shadow-md"} ${action.colorClass ?? ""}`;
              const content = (
                <>
                  <div className="p-2 rounded-lg bg-current/10 group-hover:bg-current/15 transition-colors relative">
                    <Icon className="h-5 w-5" />
                    {action.comingSoon && (
                      <span
                        className="absolute -top-1 -right-1 text-[10px] px-1.5 py-0.5 rounded font-medium bg-base-200 text-base-content/60"
                      >
                        {t("js.event.actions.comingSoon") !== "js.event.actions.comingSoon" ? t("js.event.actions.comingSoon") : "Coming soon"}
                      </span>
                    )}
                  </div>
                  <div className="text-center">
                    <p className="font-semibold text-sm leading-tight">
                      {t(action.titleKey) !== action.titleKey ? t(action.titleKey) : action.id}
                    </p>
                    <p className="text-xs mt-1 opacity-80 font-normal">
                      {t(action.descKey) !== action.descKey ? t(action.descKey) : ""}
                    </p>
                  </div>
                </>
              );
              return action.comingSoon ? (
                <div key={action.id} className={cardClass}>
                  {content}
                </div>
              ) : (
                <Link key={action.id} href={action.href} className={cardClass}>
                  {content}
                </Link>
              );
            })}
          </div>
        </div>
      )}

      {/* Basic info */}
      <div
        className={DETAIL_SECTION_CLASS}
      >
        <h2 className="text-lg font-semibold mb-3 md:mb-4 text-base-content">
          {t("js.event.detail.additionalInfo") !== "js.event.detail.additionalInfo"
            ? t("js.event.detail.additionalInfo")
            : "Details"}
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {t("js.event.detail.start") !== "js.event.detail.start" ? t("js.event.detail.start") : "Start"}:
            </span>
            {isEditing && form ? (
              <DatePicker
                value={toInputDateTime(form.begin).replace("T", " ")}
                onChange={(val) => {
                  const nextBegin = val ? val.replace(" ", "T") : "";
                    const isFirstBeginSet = !form.begin && !!nextBegin;
                    const rehearsalDuration =
                      type === "rehearsal" ? Number(rehearsalMeta?.defaultDurationMinutes ?? 0) : 0;
                    const shouldAutofillRehearsalEnd =
                      type === "rehearsal" &&
                      isFirstBeginSet &&
                      !form.end &&
                      Number.isFinite(rehearsalDuration) &&
                      rehearsalDuration > 0;
                    const nextEnd = shouldAutofillRehearsalEnd
                      ? addMinutesToInputDateTime(nextBegin, rehearsalDuration) || syncEndDate(nextBegin, form.end)
                      : syncEndDate(nextBegin, form.end);
                    const shouldAutofillConcertTimes = type === "concert" && isFirstBeginSet;
                    const shouldAutofillRehearsalDeadline = type === "rehearsal" && isFirstBeginSet;
                    setForm({
                      ...form,
                      begin: nextBegin,
                      end: nextEnd,
                      approveUntil:
                        (shouldAutofillConcertTimes || shouldAutofillRehearsalDeadline) && !form.approveUntil
                          ? nextBegin
                          : form.approveUntil,
                      meetingtime:
                        shouldAutofillConcertTimes && !form.meetingtime ? nextBegin : form.meetingtime,
                    });
                }}
                mode="datetime"
                locale={lang}
                className="ml-2 input input-sm text-base-content"
              />
            ) : (
              <span className="ml-2 text-sm">{formatDateTimeShort(begin, lang) ?? tba}</span>
            )}
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {t("js.event.detail.end") !== "js.event.detail.end" ? t("js.event.detail.end") : "End"}:
            </span>
            {isEditing && form ? (
              <DatePicker
                value={toInputDateTime(form.end).replace("T", " ")}
                onChange={(val) => setForm({ ...form, end: val ? val.replace(" ", "T") : "" })}
                mode="datetime"
                locale={lang}
                className="ml-2 input input-sm text-base-content"
              />
            ) : (
              <span className="ml-2 text-sm">{formatDateTimeShort(end, lang) ?? tba}</span>
            )}
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {t("js.event.detail.status")}:
            </span>
            {isEditing && form ? (
              <span className="ml-2 inline-flex items-center gap-2 align-middle">
                <StatusPicker
                  options={(type === "concert" ? concertMeta?.statusOptions : rehearsalMeta?.statusOptions) ?? [
                    "planned",
                    "confirmed",
                    "cancelled",
                    "hidden",
                  ]}
                  value={form.status}
                  onChange={(next) => setForm({ ...form, status: next })}
                  labelFor={statusLabelFor}
                />
              </span>
            ) : (
              <span
                className="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium border align-middle"
                style={getStatusPillStyle(status)}
              >
                {statusLabel}
              </span>
            )}
          </div>
          {(approveUntil || isEditing) && (
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.event.detail.deadline") !== "js.event.detail.deadline" ? t("js.event.detail.deadline") : "Reply by"}:
              </span>
              {isEditing && form ? (
                <DatePicker
                  value={toInputDateTime(form.approveUntil).replace("T", " ")}
                  onChange={(val) => setForm({ ...form, approveUntil: val ? val.replace(" ", "T") : "" })}
                  mode="datetime"
                  locale={lang}
                  className="ml-2 input input-sm text-base-content"
                />
              ) : (
                <span className="ml-2 text-sm">{formatDateTimeShort(approveUntil, lang) ?? tba}</span>
              )}
            </div>
          )}
          {type === "rehearsal" && (conductor?.name || isEditing) && (
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.event.detail.conductor")}:
              </span>
            {isEditing && form ? (
              <SelectPicker
                options={[{ id: 0, name: "-" }, ...(rehearsalMeta?.conductors ?? [])]}
                value={form.conductorId}
                onChange={(next) => setForm({ ...form, conductorId: next })}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel="-"
                labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
              />
            ) : (
                <span className="ml-2 text-sm">
                  <EntityLink entityType="contact" id={conductor?.id} name={conductorName} modules={modules} />
                </span>
              )}
            </div>
          )}
          {type === "concert" && (meetingtime || isEditing) && (
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.event.detail.meetingTime") !== "js.event.detail.meetingTime"
                  ? t("js.event.detail.meetingTime")
                  : "Meeting time"}:
              </span>
              {isEditing && form ? (
                <DatePicker
                  value={toInputDateTime(form.meetingtime).replace("T", " ")}
                  onChange={(val) => setForm({ ...form, meetingtime: val ? val.replace(" ", "T") : "" })}
                  mode="datetime"
                  locale={lang}
                  className="ml-2 input input-sm text-base-content"
                />
              ) : (
                <span className="ml-2 text-sm">{formatDateTimeShort(meetingtime, lang) ?? tba}</span>
              )}
            </div>
          )}
          <div className="md:col-span-2">
            <span className="text-xs font-medium text-base-content/60">
              {t("js.event.detail.location")}:
            </span>
            {isEditing && form ? (
              <SelectPicker
                options={[
                  { id: 0, name: "-" },
                  ...(((type === "concert" ? concertMeta?.locations : rehearsalMeta?.locations) ?? []) as SimpleOption[]),
                ]}
                value={form.locationId}
                onChange={(next) => setForm({ ...form, locationId: next })}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel="-"
                labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
              />
            ) : (
              <div className="mt-1 flex items-center gap-2 flex-wrap text-sm">
                {hasLocationAddress ? (
                  <AddressLink name={locationName} value={loc?.address} t={t} />
                ) : hasLocationName ? (
                  canViewEntityType("location", modules) && (loc?.id ?? 0) > 0 ? (
                    <Link href={getEntityPath("location", loc!.id!)} className="text-inherit no-underline">
                      <AddressLink value={locationName} t={t} renderRawIfNoAddress />
                    </Link>
                  ) : (
                    <AddressLink value={locationName} t={t} renderRawIfNoAddress />
                  )
                ) : (
                  "TBA"
                )}
              </div>
            )}
          </div>
          {type === "concert" && (notes?.trim() || isEditing) && (
            <div className="md:col-span-2">
              <span className="text-xs font-medium text-base-content/60">
                {t("js.event.detail.notes")}:
              </span>
              {isEditing && form ? (
                <div className="mt-1">
                  <NotesEditor
                    value={form.notes}
                    onChange={(next) => setForm({ ...form, notes: next })}
                    placeholder={t("js.event.detail.notes")}
                    id="event-concert-notes-editor"
                  />
                </div>
              ) : (
                <NotesContent value={notes ?? ""} className="mt-1 text-sm" />
              )}
            </div>
          )}
        </div>
      </div>

      {type === "rehearsal" && (isEditing || groups.length > 0) && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold text-base-content">
              {t("js.event.metadata.besetzung") !== "js.event.metadata.besetzung"
                ? t("js.event.metadata.besetzung")
                : "Groups"}
            </h2>
            {isEditing && form ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={rehearsalMeta?.groups ?? []}
                  selected={form.groups}
                  onChange={(next) => setForm({ ...form, groups: next })}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={selectedCountLabel}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
              </div>
            ) : null}
          </div>
          {isEditing && form ? (
            <SelectedItemsList
              options={rehearsalMeta?.groups ?? []}
              selected={form.groups}
              onRemove={(id) => setForm({ ...form, groups: form.groups.filter((gid) => gid !== id) })}
              labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
              className="mt-4"
            />
          ) : (
            <div className="mt-4 text-sm">
              {groups.map((group) => safeString(group.name)).filter(Boolean).join(", ") || emptyText}
            </div>
          )}
        </div>
      )}

      {/* Participation overview (diagram) */}
      {!isEditing && participationStats && (participationStats.total ?? 0) > 0 && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <h2 className="text-lg font-semibold mb-3 md:mb-4 text-base-content">
            {t("js.event.detail.participationOverview") !== "js.event.detail.participationOverview"
              ? t("js.event.detail.participationOverview")
              : "Participation overview"}
          </h2>
          <ParticipationDiagram stats={participationStats} />
        </div>
      )}

      {/* Participants by instrument */}
      {participantsByInstrument && participantsByInstrument.length > 0 && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold text-base-content">
              {t("js.event.detail.participants") !== "js.event.detail.participants"
                ? t("js.event.detail.participants")
                : "Participants"}
            </h2>
            {isEditing && form && canEditParticipation ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={(type === "concert" ? concertMeta?.contacts : rehearsalMeta?.contacts) ?? []}
                  selected={form.eventContacts}
                  onChange={(next) => {
                    if (!form) return;
                    const groupContacts = getGroupContacts(
                      form.groups,
                      type === "concert" ? concertMeta?.groupMembers : rehearsalMeta?.groupMembers
                    );
                    const nextManual = next.filter((id) => !groupContacts.has(id));
                    const nextExcluded = form.excludedContacts.filter((id) => next.includes(id));
                    setForm({
                      ...form,
                      manualContacts: nextManual,
                      excludedContacts: nextExcluded,
                      manualContactsInitialized: true,
                    });
                  }}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={selectedCountLabel}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
              </div>
            ) : null}
          </div>
          {isEditing && form && canEditParticipation ? (
            <div className="mt-4">
              <ParticipantEditor
              participants={form.participants}
              onRemoveContact={(contactId) => {
                if (!form) return;
                const groupContacts = getGroupContacts(
                  form.groups,
                  type === "concert" ? concertMeta?.groupMembers : rehearsalMeta?.groupMembers
                );
                if (groupContacts.has(contactId)) {
                  if (!form.excludedContacts.includes(contactId)) {
                    setForm({
                      ...form,
                      excludedContacts: [...form.excludedContacts, contactId],
                      manualContacts: form.manualContacts.filter((id) => id !== contactId),
                      manualContactsInitialized: true,
                    });
                  }
                } else {
                  setForm({
                    ...form,
                    manualContacts: form.manualContacts.filter((id) => id !== contactId),
                    manualContactsInitialized: true,
                  });
                }
              }}
              onChange={(next) => setForm({ ...form, participants: next })}
              t={t}
              />
            </div>
          ) : (
            <ParticipantOverview
              participantsByInstrument={participantsByInstrument}
              getEntityHref={
                modules
                  ? (entityType, entityId) =>
                      canViewEntityType(entityType, modules) ? getEntityPath(entityType, entityId) : null
                  : null
              }
            />
          )}
        </div>
      )}

      {/* Concert metadata */}
      {type === "concert" &&
        (isEditing ||
          groups.length > 0 ||
          program?.name ||
          outfit?.name ||
          equipment.length > 0 ||
          accommodation?.name ||
          (payment != null && payment !== undefined) ||
          conditions ||
          contact) && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <h2 className="text-lg font-semibold mb-3 md:mb-4 text-base-content">
            {t("js.event.metadata.organisation") !== "js.event.metadata.organisation"
              ? t("js.event.metadata.organisation")
              : "Organisation"}
          </h2>
          <div className="space-y-3 md:space-y-4">
            {(isEditing || groups.length > 0) && (
              <div>
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                  <span className="text-xs font-medium text-base-content/60">
                    {t("js.event.metadata.besetzung") !== "js.event.metadata.besetzung"
                      ? t("js.event.metadata.besetzung")
                      : "Groups"}
                    :
                  </span>
                  {isEditing && form ? (
                    <div className="w-[min(100%,260px)]">
                      <MultiSelect
                        options={concertMeta?.groups ?? []}
                        selected={form.groups}
                        onChange={(next) => setForm({ ...form, groups: next })}
                        placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                        showChips={false}
                        labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                        labelSelectedCount={selectedCountLabel}
                        labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                        labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                        labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                        labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                      />
                    </div>
                  ) : null}
                </div>
                {!isEditing && (
                  <div className="mt-4 text-sm">
                    {groups.map((g) => safeString(g.name)).filter(Boolean).join(", ") || emptyText}
                  </div>
                )}
                {isEditing && form ? (
                  <SelectedItemsList
                    options={concertMeta?.groups ?? []}
                    selected={form.groups}
                    onRemove={(id) => setForm({ ...form, groups: form.groups.filter((gid) => gid !== id) })}
                    labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                    emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                    className="mt-4"
                  />
                ) : null}
              </div>
            )}
            {(isEditing || safeString(program?.name)) && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.programm") !== "js.event.metadata.programm"
                    ? t("js.event.metadata.programm")
                    : "Program"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={[{ id: 0, name: "-" }, ...(concertMeta?.programs ?? [])]}
                    value={form.programId}
                    onChange={(next) => setForm({ ...form, programId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">{safeString(program?.name)}</span>
                )}
              </div>
            )}
            {(isEditing || safeString(outfit?.name)) && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.outfit") !== "js.event.metadata.outfit"
                    ? t("js.event.metadata.outfit")
                    : "Outfit"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={[{ id: 0, name: "-" }, ...(concertMeta?.outfits ?? [])]}
                    value={form.outfitId}
                    onChange={(next) => setForm({ ...form, outfitId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">{safeString(outfit?.name)}</span>
                )}
              </div>
            )}
            {(isEditing || equipment.length > 0) && (
              <div>
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                  <span className="text-xs font-medium text-base-content/60">
                    {t("js.event.metadata.equipment") !== "js.event.metadata.equipment"
                      ? t("js.event.metadata.equipment")
                      : "Equipment"}
                    :
                  </span>
                  {isEditing && form ? (
                    <div className="w-[min(100%,260px)]">
                      <MultiSelect
                        options={concertMeta?.equipment ?? []}
                        selected={form.equipment}
                        onChange={(next) => setForm({ ...form, equipment: next })}
                        placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                        showChips={false}
                        labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                        labelSelectedCount={selectedCountLabel}
                        labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                        labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                        labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                        labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                      />
                    </div>
                  ) : null}
                </div>
                {!isEditing && (
                  <div className="mt-4 text-sm">
                    {equipment.map((e) => safeString(e.name)).filter(Boolean).join(", ") || emptyText}
                  </div>
                )}
                {isEditing && form ? (
                  <SelectedItemsList
                    options={concertMeta?.equipment ?? []}
                    selected={form.equipment}
                    onRemove={(id) => setForm({ ...form, equipment: form.equipment.filter((eid) => eid !== id) })}
                    labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                    emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                    className="mt-4"
                  />
                ) : null}
              </div>
            )}
          </div>
          <h3 className="text-sm font-semibold mt-4 mb-3 md:mt-6 md:mb-3 text-base-content">
            {t("js.event.metadata.details") !== "js.event.metadata.details"
              ? t("js.event.metadata.details")
              : "Details"}
          </h3>
          <div className="space-y-3">
            {(isEditing || safeString(accommodation?.name)) && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.unterkunft") !== "js.event.metadata.unterkunft"
                    ? t("js.event.metadata.unterkunft")
                    : "Accommodation"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={[{ id: 0, name: "-" }, ...(concertMeta?.locations ?? [])]}
                    value={form.accommodationId}
                    onChange={(next) => setForm({ ...form, accommodationId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">{safeString(accommodation?.name)}</span>
                )}
              </div>
            )}
            {(isEditing || (payment != null && payment !== undefined)) && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.gage") !== "js.event.metadata.gage" ? t("js.event.metadata.gage") : "Payment"}:
                </span>
                {isEditing && form ? (
                  <input
                    type="number"
                    value={form.payment}
                    onChange={(event) => setForm({ ...form, payment: event.target.value })}
                    className="ml-2 rounded-md border border-base-300 bg-base-100 text-base-content px-2 py-1 text-sm"
                  />
                ) : (
                  <span className="ml-2 text-sm">
                    {new Intl.NumberFormat(lang === "de" ? "de-DE" : "en-US", {
                      style: "currency",
                      currency: "EUR",
                    }).format(payment ?? 0)}
                  </span>
                )}
              </div>
            )}
            {(isEditing || conditions?.trim()) && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.konditionen") !== "js.event.metadata.konditionen"
                    ? t("js.event.metadata.konditionen")
                    : "Conditions"}:
                </span>
                {isEditing && form ? (
                  <textarea
                    value={form.conditions}
                    onChange={(event) => setForm({ ...form, conditions: event.target.value })}
                    className="mt-1 w-full rounded-md border border-base-300 bg-base-100 text-base-content px-3 py-2 text-sm"
                    rows={3}
                  />
                ) : (
                  <MarkdownText value={conditions ?? ""} className="mt-1 text-sm" />
                )}
              </div>
            )}
            {(isEditing ||
              (contact && (safeString(contact.name) || safeString(contact.phone) || safeString(contact.mobile) || safeString(contact.email)))) && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.kontakt") !== "js.event.metadata.kontakt"
                    ? t("js.event.metadata.kontakt")
                    : "Contact"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={concertMeta?.contacts ?? []}
                    value={form.contactId}
                    onChange={(next) => setForm({ ...form, contactId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">
                    {contact?.id && canViewEntityType("contact", modules) ? (
                      <Link href={getEntityPath("contact", contact.id)} className="text-inherit no-underline">
                        {contactName || "—"}
                      </Link>
                    ) : (
                      contactName || "—"
                    )}
                  </span>
                )}
              </div>
            )}
            {isEditing && form && (
              <div>
                <span className="text-xs font-medium text-base-content/60">
                  {t("js.event.metadata.organizer") !== "js.event.metadata.organizer" ? t("js.event.metadata.organizer") : "Organizer"}:
                </span>
                <input
                  type="text"
                  value={form.organizer}
                  onChange={(event) => setForm({ ...form, organizer: event.target.value })}
                  className="ml-2 rounded-md border border-base-300 bg-base-100 text-base-content px-2 py-1 text-sm"
                />
              </div>
            )}
          </div>
        </div>
      )}

      {/* Notes (rehearsal) */}
      {type === "rehearsal" && (notes?.trim() || isEditing) && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <h2 className="text-lg font-semibold mb-2 text-base-content">
            {t("js.event.detail.notes")}
          </h2>
          <div className="text-base-content/60">
            {isEditing && form ? (
              <NotesEditor
                value={form.notes}
                onChange={(next) => setForm({ ...form, notes: next })}
                placeholder={t("js.event.detail.notes")}
                id="event-rehearsal-notes-editor"
              />
            ) : (
              <NotesContent value={notes ?? ""} className="text-sm" />
            )}
          </div>
        </div>
      )}

      {type === "rehearsal" && (isEditing || (Array.isArray(songsToPractice) && songsToPractice.length > 0)) && (
        <div
          className={DETAIL_SECTION_CLASS}
        >
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold text-base-content">
              {t("js.event.detail.songsToPractice")}
            </h2>
            {isEditing && form ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={(rehearsalMeta?.songs ?? []).map((song) => ({ id: song.id, name: song.title }))}
                  selected={form.songs.map((song) => song.id)}
                  onChange={(next) => updateSongSelection(next)}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelSelectedCount={selectedCountLabel}
                labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              />
              </div>
            ) : null}
          </div>
          {isEditing && form ? (
            <div className="mt-3 space-y-2 md:mt-6">
              {form.songs.map((song) => (
                <div key={song.id} className="rounded-md border border-base-300 px-3 py-2">
                  <div className="flex items-start justify-between gap-2">
                    <div className="text-sm font-medium">{song.title}</div>
                    <button
                      type="button"
                      onClick={() => setForm({ ...form, songs: form.songs.filter((entry) => entry.id !== song.id) })}
                      className="inline-flex items-center justify-center rounded-md border border-base-300 text-base-content px-2 py-2 text-sm"
                      aria-label={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                  <div className="mt-2">
                    <NotesEditor
                      value={song.notes}
                      onChange={(next) =>
                        setForm({
                          ...form,
                          songs: form.songs.map((entry) =>
                            entry.id === song.id ? { ...entry, notes: next } : entry
                          ),
                        })
                      }
                      placeholder={t("js.event.detail.notes")}
                      minHeight="80px"
                      id={`event-song-notes-editor-${song.id}`}
                    />
                  </div>
                </div>
              ))}
              {form.songs.length === 0 && (
                <div className="text-sm text-base-content/60">
                  {t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                </div>
              )}
            </div>
          ) : (
            <ul className="list-disc list-inside space-y-1 text-sm">
              {songsToPractice?.map((song, i) => (
                <li key={song.id ?? i}>
                  {song.title}
                  {song.notes?.trim() ? (
                    <div className="mt-1 text-xs text-base-content/60">
                      <NotesContent value={song.notes} maxLines={3} />
                    </div>
                  ) : null}
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
      {isEditing && !isNew && canEdit && (
        <DetailDeleteSection
          canDelete
          entityTitle={title || undefined}
          onDelete={deleteCurrentEvent}
        />
      )}
      {renderAfterContent}
      {participationStats && participantsByInstrument && participantsByInstrument.length > 0 && (
        <EventParticipationShareModal
          open={shareModalOpen}
          onClose={() => setShareModalOpen(false)}
          title={title}
          bandName={shareBandName}
          eventBadgeLabel={eventTypeConfig.label}
          eventBadgeClassName={eventTypeConfig.badgeClass}
          fileDateIso={typeof begin === "string" && begin.length >= 10 ? begin.slice(0, 10) : new Date().toISOString().slice(0, 10)}
          fileEventType={eventTypeConfig.label}
          fileLocation={locationName || "Location"}
          locale={lang}
          dateText={dateStr}
          timeText={`${timeStr}${endTimeStr ? ` - ${endTimeStr}` : ""}`}
          locationText={type === "rehearsal" ? undefined : locationName}
          stats={participationStats}
          participantsByInstrument={participantsByInstrument}
          t={t}
        />
      )}
    </div>
  );
}
