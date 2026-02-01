/**
 * BNote Next Generation - Entity Detail Page (rehearsal / concert)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Suspense, useCallback, useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";
import { ParticipationWidget } from "@/components/ParticipationWidget";
import { ParticipationDiagram, type ParticipationStats } from "@/components/ParticipationDiagram";
import { ParticipantOverview, type InstrumentGroup } from "@/components/ParticipantOverview";
import { getIcon } from "@/components/icons";
import { getEventTypeConfig } from "@/lib/event-utils";
import { ChevronLeft, MapPin } from "lucide-react";

interface LocationObj {
  id?: number;
  name?: string;
  address?: { street?: string; city?: string; zip?: string; state?: string; country?: string };
}

interface ConductorObj {
  id?: number;
  name?: string;
}

interface SongObj {
  id?: number;
  title?: string;
  notes?: string | null;
}

interface ProgramObj {
  id?: number;
  name?: string;
  notes?: string | null;
}

interface OutfitObj {
  id?: number;
  name?: string;
}

interface EquipmentObj {
  id?: number;
  name?: string;
}

interface GroupObj {
  id?: number;
  name?: string;
}

interface AccommodationObj {
  id?: number;
  name?: string;
  address?: Record<string, unknown>;
}

interface ContactObj {
  id?: number;
  name?: string;
  phone?: string;
  mobile?: string;
  email?: string;
}

function formatDate(begin: string | undefined, lang: string): string {
  if (!begin) return "TBA";
  try {
    const d = new Date(begin);
    return d.toLocaleDateString(lang === "de" ? "de-DE" : "en-US", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  } catch {
    return "TBA";
  }
}

function formatTime(begin: string | undefined): string {
  if (!begin) return "TBA";
  try {
    const d = new Date(begin);
    return d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
  } catch {
    return "TBA";
  }
}

function formatDateTime(str: string | undefined, lang: string): string {
  if (!str) return "TBA";
  try {
    const d = new Date(str);
    return d.toLocaleString(lang === "de" ? "de-DE" : "en-US", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  } catch {
    return "TBA";
  }
}

function formatAddress(addr: LocationObj["address"]): string {
  if (!addr) return "";
  const parts = [addr.street, [addr.zip, addr.city].filter(Boolean).join(" "), addr.state, addr.country].filter(
    Boolean
  );
  return parts.join(", ");
}

function EntityDetailContent() {
  const searchParams = useSearchParams();
  const { t, ready, lang } = useI18n();
  const type = searchParams.get("type") || "";
  const id = searchParams.get("id") || "";
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const module = type === "rehearsal" ? "rehearsals" : "concerts";
  const numId = id ? parseInt(String(id), 10) : NaN;
  const eventType = type === "rehearsal" ? "R" : "C";

  const loadData = useCallback(async () => {
    if (!ready || !type || !id || isNaN(numId)) return;
    try {
      const result = await api.get<Record<string, unknown>>(module, "", { id: String(numId) });
      setData(result);
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load");
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [ready, type, id, numId, module]);

  useEffect(() => {
    if (!ready || !type || !id || isNaN(numId)) {
      setLoading(false);
      return;
    }
    loadData();
  }, [ready, type, id, numId, loadData]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="mx-auto max-w-4xl space-y-4">
        <Link
          href="/dashboard"
          className="inline-flex items-center gap-1 text-sm font-medium"
          style={{ color: "var(--primary)" }}
        >
          <ChevronLeft className="h-4 w-4" />
          {t("js.dashboard.backToDashboard")}
        </Link>
        <div
          className="rounded-lg border px-4 py-3"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {error || "Not found"}
        </div>
      </div>
    );
  }

  const loc = data.location as LocationObj | undefined;
  const conductor = data.conductor as ConductorObj | undefined;
  const locationName = loc?.name;
  const title =
    (data.title as string) ??
    locationName ??
    conductor?.name ??
    (type === "concert" ? t("js.event.performance") : t("js.event.rehearsal"));
  const begin = (data.begin ?? data.date ?? data.event_begin) as string | undefined;
  const end = data.end as string | undefined;
  const dateStr = formatDate(begin, lang);
  const timeStr = formatTime(begin);
  const endTimeStr = end ? formatTime(end) : null;
  const status = (data.status as string) ?? "planned";
  const approveUntil = data.approve_until as string | undefined;
  const notes = data.notes as string | undefined;
  const meetingtime = data.meetingtime as string | undefined;
  const songsToPractice = (data.songsToPractice ?? data.songs_to_practice) as SongObj[] | undefined;
  const participationStats = data.participationStats as ParticipationStats | undefined;
  const participantsByInstrument = (data.participantsByInstrument ?? data.participants_by_instrument) as
    | InstrumentGroup[]
    | undefined;

  // Concert metadata
  const groups = (data.groups ?? []) as GroupObj[];
  const program = data.program as ProgramObj | undefined;
  const outfit = data.outfit as OutfitObj | undefined;
  const equipment = (data.equipment ?? []) as EquipmentObj[];
  const accommodation = data.accommodation as AccommodationObj | undefined;
  const payment = data.payment as number | null | undefined;
  const conditions = data.conditions as string | undefined;
  const contact = data.contact as ContactObj | undefined;

  const eventTypeConfig = getEventTypeConfig(type === "concert" ? "performance" : type, t);
  const EventIcon = getIcon(eventTypeConfig.icon);

  const statusLabel =
    status === "confirmed"
      ? t("js.event.status.confirmed")
      : status === "cancelled"
        ? t("js.event.status.cancelled")
        : t("js.event.status.planned");

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <Link
        href="/dashboard"
        className="inline-flex items-center gap-1 text-sm font-medium"
        style={{ color: "var(--primary)" }}
      >
        <ChevronLeft className="h-4 w-4" />
        {t("js.dashboard.backToDashboard")}
      </Link>

      {/* Header + participation widget */}
      <div
        className="rounded-xl border p-6 shadow-sm"
        style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
      >
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <div
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white"
                style={{
                  background: type === "rehearsal" ? "var(--primary)" : "var(--accent)",
                }}
              >
                <EventIcon className="h-5 w-5" />
              </div>
              <h1 className="text-2xl font-bold truncate" style={{ color: "var(--foreground)" }}>
                {title}
              </h1>
            </div>
            <p className="mt-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
              {dateStr} · {timeStr}
              {endTimeStr ? ` - ${endTimeStr}` : ""}
            </p>
            {locationName && (
              <p className="mt-1 flex items-center gap-1.5 text-sm" style={{ color: "var(--muted-foreground)" }}>
                <MapPin className="h-4 w-4 shrink-0" />
                {locationName}
                {loc?.address && formatAddress(loc.address) && (
                  <span className="opacity-80"> · {formatAddress(loc.address)}</span>
                )}
              </p>
            )}
          </div>
          {(type === "rehearsal" || type === "concert") && (
            <div className="shrink-0">
              <ParticipationWidget eventId={numId} eventType={eventType} onStatusChange={loadData} />
            </div>
          )}
        </div>
      </div>

      {/* Basic info */}
      <div
        className="rounded-xl border p-6 shadow-sm"
        style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
      >
        <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
          {t("js.event.detail.additionalInfo") !== "js.event.detail.additionalInfo"
            ? t("js.event.detail.additionalInfo")
            : "Details"}
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.date")}:
            </span>
            <span className="ml-2 text-sm">{dateStr}</span>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.time")}:
            </span>
            <span className="ml-2 text-sm">
              {timeStr}
              {endTimeStr ? ` - ${endTimeStr}` : ""}
            </span>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.status")}:
            </span>
            <span className="ml-2 text-sm">{statusLabel}</span>
          </div>
          {approveUntil && (
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.deadline") !== "js.event.detail.deadline" ? t("js.event.detail.deadline") : "Reply by"}:
              </span>
              <span className="ml-2 text-sm">{formatDateTime(approveUntil, lang)}</span>
            </div>
          )}
          {type === "rehearsal" && conductor?.name && (
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.conductor")}:
              </span>
              <span className="ml-2 text-sm">{conductor.name}</span>
            </div>
          )}
          {type === "concert" && meetingtime && (
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.meetingTime") !== "js.event.detail.meetingTime"
                  ? t("js.event.detail.meetingTime")
                  : "Meeting time"}:
              </span>
              <span className="ml-2 text-sm">{formatDateTime(meetingtime, lang)}</span>
            </div>
          )}
          <div className="md:col-span-2">
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.location")}:
            </span>
            <div className="mt-1 flex items-center gap-2 flex-wrap text-sm">
              {locationName ?? "TBA"}
              {loc?.address && formatAddress(loc.address) && (
                <span style={{ color: "var(--muted-foreground)" }}> · {formatAddress(loc.address)}</span>
              )}
            </div>
          </div>
          {type === "rehearsal" && Array.isArray(songsToPractice) && songsToPractice.length > 0 && (
            <div className="md:col-span-2">
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.songsToPractice")}:
              </span>
              <ul className="list-disc list-inside space-y-1 mt-1 text-sm">
                {songsToPractice.map((song, i) => (
                  <li key={song.id ?? i}>
                    {song.title}
                    {song.notes?.trim() ? (
                      <span className="text-xs ml-1" style={{ color: "var(--muted-foreground)" }}>
                        ({song.notes})
                      </span>
                    ) : null}
                  </li>
                ))}
              </ul>
            </div>
          )}
          {type === "concert" && notes?.trim() && (
            <div className="md:col-span-2">
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.notes")}:
              </span>
              <div className="mt-1 text-sm whitespace-pre-wrap">{notes}</div>
            </div>
          )}
        </div>
      </div>

      {/* Participation overview (diagram) */}
      {participationStats && (participationStats.total ?? 0) > 0 && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
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
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
            {t("js.event.detail.participants") !== "js.event.detail.participants"
              ? t("js.event.detail.participants")
              : "Participants"}
          </h2>
          <ParticipantOverview participantsByInstrument={participantsByInstrument} />
        </div>
      )}

      {/* Concert metadata */}
      {type === "concert" && (groups.length > 0 || program?.name || outfit?.name || equipment.length > 0 || accommodation?.name || (payment != null && payment !== undefined) || conditions || contact) && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
            {t("js.event.metadata.organisation") !== "js.event.metadata.organisation"
              ? t("js.event.metadata.organisation")
              : "Organisation"}
          </h2>
          <div className="space-y-4">
            {groups.length > 0 && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.besetzung") !== "js.event.metadata.besetzung"
                    ? t("js.event.metadata.besetzung")
                    : "Groups"}:
                </span>
                <span className="ml-2 text-sm">{groups.map((g) => g.name).filter(Boolean).join(", ")}</span>
              </div>
            )}
            {program?.name && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.programm") !== "js.event.metadata.programm"
                    ? t("js.event.metadata.programm")
                    : "Program"}:
                </span>
                <span className="ml-2 text-sm">{program.name}</span>
              </div>
            )}
            {outfit?.name && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.outfit") !== "js.event.metadata.outfit"
                    ? t("js.event.metadata.outfit")
                    : "Outfit"}:
                </span>
                <span className="ml-2 text-sm">{outfit.name}</span>
              </div>
            )}
            {equipment.length > 0 && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.equipment") !== "js.event.metadata.equipment"
                    ? t("js.event.metadata.equipment")
                    : "Equipment"}:
                </span>
                <span className="ml-2 text-sm">{equipment.map((e) => e.name).filter(Boolean).join(", ")}</span>
              </div>
            )}
          </div>
          <h3 className="text-sm font-semibold mt-6 mb-3" style={{ color: "var(--foreground)" }}>
            {t("js.event.metadata.details") !== "js.event.metadata.details"
              ? t("js.event.metadata.details")
              : "Details"}
          </h3>
          <div className="space-y-2">
            {accommodation?.name && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.unterkunft") !== "js.event.metadata.unterkunft"
                    ? t("js.event.metadata.unterkunft")
                    : "Accommodation"}:
                </span>
                <span className="ml-2 text-sm">{accommodation.name}</span>
              </div>
            )}
            {payment != null && payment !== undefined && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.gage") !== "js.event.metadata.gage" ? t("js.event.metadata.gage") : "Payment"}:
                </span>
                <span className="ml-2 text-sm">
                  {new Intl.NumberFormat(lang === "de" ? "de-DE" : "en-US", {
                    style: "currency",
                    currency: "EUR",
                  }).format(payment)}
                </span>
              </div>
            )}
            {conditions?.trim() && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.konditionen") !== "js.event.metadata.konditionen"
                    ? t("js.event.metadata.konditionen")
                    : "Conditions"}:
                </span>
                <span className="ml-2 text-sm">{conditions}</span>
              </div>
            )}
            {meetingtime && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.treffpunkt") !== "js.event.metadata.treffpunkt"
                    ? t("js.event.metadata.treffpunkt")
                    : "Meeting point"}:
                </span>
                <span className="ml-2 text-sm">{formatDateTime(meetingtime, lang)}</span>
              </div>
            )}
            {contact && (contact.name || contact.phone || contact.mobile || contact.email) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.kontakt") !== "js.event.metadata.kontakt"
                    ? t("js.event.metadata.kontakt")
                    : "Contact"}:
                </span>
                <span className="ml-2 text-sm">
                  {[contact.name, contact.phone, contact.mobile, contact.email].filter(Boolean).join(" | ")}
                </span>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Notes (rehearsal) */}
      {type === "rehearsal" && notes?.trim() && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-2" style={{ color: "var(--foreground)" }}>
            {t("js.event.detail.notes")}
          </h2>
          <p className="text-sm whitespace-pre-wrap" style={{ color: "var(--muted-foreground)" }}>
            {notes}
          </p>
        </div>
      )}
    </div>
  );
}

export default function EntityDetailPage() {
  return (
    <Suspense
      fallback={
        <div className="flex items-center justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
        </div>
      }
    >
      <EntityDetailContent />
    </Suspense>
  );
}
