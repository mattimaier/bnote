"use client";

import { useMemo, useRef, useState } from "react";
import { toBlob } from "html-to-image";
import { Modal } from "@/components/Modal";
import type { ParticipationStats } from "@/components/ParticipationDiagram";
import type { InstrumentGroup } from "@/components/ParticipantOverview";
import { getBnoteLogoUrl } from "@/lib/bnote-assets";

interface EventParticipationShareModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  bandName: string;
  eventBadgeLabel: string;
  eventBadgeClassName: string;
  fileDateIso: string;
  fileEventType: string;
  fileLocation: string;
  locale: string;
  dateText: string;
  timeText: string;
  locationText?: string;
  stats: ParticipationStats;
  participantsByInstrument: InstrumentGroup[];
  t: (key: string) => string;
}

interface SharePerson {
  id: number;
  name: string;
  participate: number | null;
}

interface SectionStats {
  name: string;
  yes: number;
  maybe: number;
  no: number;
  pending: number;
  total: number;
}

function getLabel(t: (key: string) => string, key: string, fallback: string): string {
  const value = t(key);
  return value !== key ? value : fallback;
}

function uniqueParticipants(groups: InstrumentGroup[]): SharePerson[] {
  const byId = new Map<number, SharePerson>();
  groups.forEach((group) => {
    group.participants.forEach((participant) => {
      if (!byId.has(participant.id)) {
        byId.set(participant.id, {
          id: participant.id,
          name: participant.name,
          participate: participant.participate,
        });
      }
    });
  });
  return Array.from(byId.values());
}

function buildSectionStats(groups: InstrumentGroup[], sectionFallback: string): SectionStats[] {
  return groups
    .map((group) => {
      const yes = group.participants.filter((p) => p.participate === 1).length;
      const maybe = group.participants.filter((p) => p.participate === 2).length;
      const no = group.participants.filter((p) => p.participate === 0).length;
      const pending = group.participants.filter((p) => p.participate == null).length;
      const total = yes + maybe + no + pending;
      return {
        name: group.instrument?.name || sectionFallback,
        yes,
        maybe,
        no,
        pending,
        total,
      };
    })
    .filter((section) => section.total > 0)
    .sort((a, b) => b.total - a.total || a.name.localeCompare(b.name));
}

function toFilePart(input: string): string {
  const value = (input || "").trim();
  // Keep whitespace for readability; only remove invalid filename characters.
  const safe = value.replace(/[<>:"/\\|?*\u0000-\u001F]/g, "").trim();
  return safe || "unknown";
}

export function EventParticipationShareModal({
  open,
  onClose,
  title,
  bandName,
  eventBadgeLabel,
  eventBadgeClassName,
  fileDateIso,
  fileEventType,
  fileLocation,
  locale,
  dateText,
  timeText,
  locationText,
  stats,
  participantsByInstrument,
  t,
}: EventParticipationShareModalProps) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const previewRef = useRef<HTMLDivElement | null>(null);

  const shareNowLabel = getLabel(t, "js.event.share.now", "Share");
  const modalTitle = getLabel(t, "js.event.share.modalTitle", "Share Participation");
  const sectionFallbackLabel = getLabel(t, "js.event.share.sectionFallback", "Section");
  const logoUrl = getBnoteLogoUrl();
  const generatedAt = new Intl.DateTimeFormat(locale || undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date());

  const people = useMemo(() => uniqueParticipants(participantsByInstrument), [participantsByInstrument]);
  const sectionStats = useMemo(
    () => buildSectionStats(participantsByInstrument, sectionFallbackLabel),
    [participantsByInstrument, sectionFallbackLabel]
  );

  const yes = stats.yes ?? 0;
  const maybe = stats.maybe ?? 0;
  const no = stats.no ?? 0;
  const pending = stats.pending ?? 0;
  const total = stats.total ?? yes + maybe + no + pending;
  const safeTotal = total > 0 ? total : 1;

  const yesPct = (yes / safeTotal) * 100;
  const maybePct = (maybe / safeTotal) * 100;
  const noPct = (no / safeTotal) * 100;
  const pendingPct = (pending / safeTotal) * 100;

  const createShareBlob = async (): Promise<Blob> => {
    if (!previewRef.current) throw new Error("No preview");
    const blob = await toBlob(previewRef.current, {
      cacheBust: true,
      pixelRatio: 3,
      backgroundColor: "#ffffff",
      imagePlaceholder:
        "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==",
    });
    if (!blob) throw new Error("Export failed");
    return blob;
  };

  const fileName = `${toFilePart(fileDateIso)} - ${toFilePart(fileEventType)} - ${toFilePart(fileLocation)}.png`;

  const downloadBlob = (blob: Blob) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = fileName;
    link.rel = "noopener";
    document.body.appendChild(link);
    link.click();
    link.remove();
    // Revoke with delay to avoid Safari/WebKit race where immediate revoke cancels download.
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    // Extra fallback for browsers that ignore the download attribute on blob URLs.
    window.setTimeout(() => {
      try {
        window.open(url, "_blank", "noopener,noreferrer");
      } catch {
        // no-op
      }
    }, 50);
  };

  const handleShare = async () => {
    setBusy(true);
    setError("");
    try {
      const blob = await createShareBlob();
      const file = new File([blob], fileName, { type: "image/png" });
      const nav = navigator as Navigator & {
        canShare?: (data: ShareData) => boolean;
      };
      if (nav.share && (!nav.canShare || nav.canShare({ files: [file] }))) {
        try {
          await nav.share({
            files: [file],
            title,
            text: `${title} - ${dateText} ${timeText}`,
          });
          return;
        } catch (err) {
          if (err instanceof DOMException && err.name === "AbortError") {
            // If share sheet was dismissed/canceled, still provide the file via download fallback.
            downloadBlob(blob);
            return;
          }
          downloadBlob(blob);
          return;
        }
      } else {
        downloadBlob(blob);
      }
    } catch {
      setError(getLabel(t, "js.event.share.shareError", "Failed to share image."));
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={modalTitle}
      bodyClassName="max-h-[90dvh]"
    >
      <div className="space-y-4">
        <div className="flex justify-center">
          <div className="inline-block rounded-xl border border-base-300 bg-transparent p-2">
            <div
              ref={previewRef}
              className="mx-auto w-[320px] rounded-2xl bg-white p-4 text-[#111827]"
              style={{ aspectRatio: "5 / 4" }}
            >
              <div className="flex items-center justify-between">
                <span className={`event-badge ${eventBadgeClassName}`}>
                  {eventBadgeLabel}
                </span>
                <div className="rounded-box flex h-9 w-9 items-center justify-center bg-gradient-to-br from-primary/30 to-primary/10 ring-1 ring-primary/20">
                  <img
                    src={logoUrl}
                    alt="BNote"
                    className="h-5 w-5"
                    style={{
                      filter:
                        "brightness(0) saturate(100%) invert(58%) sepia(95%) saturate(2878%) hue-rotate(195deg) brightness(102%) contrast(101%)",
                    }}
                  />
                </div>
              </div>
              <div className="mt-3">
                <h3 className="line-clamp-2 text-lg font-bold">{title}</h3>
                <p className="mt-1 text-xs text-[#4b5563]">
                  {dateText} · {timeText}
                </p>
                {locationText ? <p className="line-clamp-1 text-xs text-[#4b5563]">{locationText}</p> : null}
              </div>

              <div className="mt-4">
                <div className="h-6 overflow-hidden rounded-md bg-[#e5e7eb]">
                  <div className="flex h-full w-full">
                    {yes > 0 ? (
                      <div
                        style={{ width: `${yesPct}%` }}
                        className="flex items-center justify-center bg-[#16a34a] text-[10px] font-semibold text-white"
                      >
                        {yes}
                      </div>
                    ) : null}
                    {maybe > 0 ? (
                      <div
                        style={{ width: `${maybePct}%` }}
                        className="flex items-center justify-center bg-[#f59e0b] text-[10px] font-semibold text-white"
                      >
                        {maybe}
                      </div>
                    ) : null}
                    {no > 0 ? (
                      <div
                        style={{ width: `${noPct}%` }}
                        className="flex items-center justify-center bg-[#dc2626] text-[10px] font-semibold text-white"
                      >
                        {no}
                      </div>
                    ) : null}
                    {pending > 0 ? (
                      <div
                        style={{ width: `${pendingPct}%` }}
                        className="flex items-center justify-center bg-[#9ca3af] text-[10px] font-semibold text-white"
                      >
                        {pending}
                      </div>
                    ) : null}
                  </div>
                </div>
              </div>

              <div className="mt-4">
                <div className="grid grid-cols-2 gap-x-3 gap-y-1.5 text-[10px] leading-snug">
                  {sectionStats.map((section) => (
                    <div key={section.name} className="py-1 min-w-0">
                      <div className="mb-1 truncate text-xs font-semibold text-[#111827]">{section.name}</div>
                      <div className="h-4 overflow-hidden rounded bg-[#e5e7eb]">
                        <div className="flex h-full w-full">
                          {section.yes > 0 ? (
                            <div
                              className="flex items-center justify-center bg-[#16a34a] text-[9px] font-semibold text-white"
                              style={{ width: `${(section.yes / section.total) * 100}%` }}
                            >
                              {section.yes}
                            </div>
                          ) : null}
                          {section.maybe > 0 ? (
                            <div
                              className="flex items-center justify-center bg-[#f59e0b] text-[9px] font-semibold text-white"
                              style={{ width: `${(section.maybe / section.total) * 100}%` }}
                            >
                              {section.maybe}
                            </div>
                          ) : null}
                          {section.no > 0 ? (
                            <div
                              className="flex items-center justify-center bg-[#dc2626] text-[9px] font-semibold text-white"
                              style={{ width: `${(section.no / section.total) * 100}%` }}
                            >
                              {section.no}
                            </div>
                          ) : null}
                          {section.pending > 0 ? (
                            <div
                              className="flex items-center justify-center bg-[#9ca3af] text-[9px] font-semibold text-white"
                              style={{ width: `${(section.pending / section.total) * 100}%` }}
                            >
                              {section.pending}
                            </div>
                          ) : null}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="mt-4 flex items-center justify-between border-t border-[#e5e7eb] pt-3 text-[10px] text-[#6b7280]">
                <span className="truncate">{bandName}</span>
                <span>{generatedAt}</span>
              </div>
            </div>
          </div>
        </div>

        {error ? <div className="rounded-md border border-error bg-error/10 p-2 text-sm text-error">{error}</div> : null}

        <div className="flex flex-col gap-2 sm:flex-row sm:justify-end">
          <button type="button" onClick={handleShare} disabled={busy} className="btn btn-primary">
            {busy ? `${shareNowLabel}...` : shareNowLabel}
          </button>
        </div>
      </div>
    </Modal>
  );
}
