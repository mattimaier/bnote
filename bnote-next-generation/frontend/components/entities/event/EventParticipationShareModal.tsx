"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { toBlob } from "html-to-image";
import { Modal } from "@/components/Modal";
import type { ParticipationStats } from "@/components/ParticipationDiagram";
import type { InstrumentGroup } from "@/components/ParticipantOverview";
import { getBnoteLogoUrl } from "@/lib/bnote-assets";
import { getApiUrl } from "@/lib/api";

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

interface ShareCardCreateResult {
  url: string;
  shareId: string;
  expiresAt: number;
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
  const [preparing, setPreparing] = useState(false);
  const [preparedShareUrl, setPreparedShareUrl] = useState("");
  const [preparedShareId, setPreparedShareId] = useState("");
  const [didClickShare, setDidClickShare] = useState(false);
  const [error, setError] = useState("");
  const previewRef = useRef<HTMLDivElement | null>(null);
  const preparedShareIdRef = useRef("");
  const didClickShareRef = useRef(false);

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

  const createShareUrl = async (blob: Blob): Promise<ShareCardCreateResult> => {
    const formData = new FormData();
    const file = new File([blob], fileName, { type: "image/png" });
    formData.set("file", file);
    formData.set("name", fileName);
    const apiUrl = getApiUrl();
    const shareCardEndpoint =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    shareCardEndpoint.searchParams.set("module", "share");
    shareCardEndpoint.searchParams.set("action", "uploadShareCard");
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 20000);
    let res: Response;
    try {
      res = await fetch(shareCardEndpoint.toString(), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
        },
        signal: controller.signal,
      });
    } catch (err) {
      if (err instanceof DOMException && err.name === "AbortError") {
        throw new Error("Share upload timed out");
      }
      throw err;
    } finally {
      window.clearTimeout(timeoutId);
    }
    const raw = await res.text();
    let json: { success?: boolean; data?: ShareCardCreateResult; error?: string } | null = null;
    try {
      json = JSON.parse(raw) as { success?: boolean; data?: ShareCardCreateResult; error?: string };
    } catch {
      throw new Error("Share endpoint returned non-JSON response");
    }
    if (!res.ok || json.success === false || !json.data?.url || !json.data?.shareId) {
      throw new Error(json.error ?? "Failed to create share URL");
    }
    return json.data;
  };

  const deleteShareCard = async (shareId: string): Promise<void> => {
    if (!shareId) return;
    const apiUrl = getApiUrl();
    const endpoint =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    endpoint.searchParams.set("module", "share");
    endpoint.searchParams.set("action", "deleteShareCard");
    const body = new URLSearchParams();
    body.set("shareId", shareId);
    await fetch(endpoint.toString(), {
      method: "POST",
      body,
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });
  };

  const prepareShareUrl = async (): Promise<ShareCardCreateResult> => {
    const blob = await createShareBlob();
    return createShareUrl(blob);
  };

  useEffect(() => {
    if (!open) {
      if (preparedShareIdRef.current && !didClickShareRef.current) {
        void deleteShareCard(preparedShareIdRef.current);
      }
      setPreparing(false);
      setPreparedShareUrl("");
      setPreparedShareId("");
      setDidClickShare(false);
      preparedShareIdRef.current = "";
      didClickShareRef.current = false;
      return;
    }

    let cancelled = false;
    setError("");
    setPreparing(true);
    setPreparedShareUrl("");
    setPreparedShareId("");
    setDidClickShare(false);
    preparedShareIdRef.current = "";
    didClickShareRef.current = false;

    // Wait one frame so the preview card is painted before html-to-image runs.
    const timer = window.setTimeout(async () => {
      try {
        const prepared = await prepareShareUrl();
        if (!cancelled) {
          setPreparedShareUrl(prepared.url);
          setPreparedShareId(prepared.shareId);
          preparedShareIdRef.current = prepared.shareId;
        }
      } catch (err) {
        if (!cancelled) {
          const fallback = getLabel(t, "js.event.share.shareError", "Failed to share image.");
          setError(err instanceof Error && err.message ? err.message : fallback);
        }
      } finally {
        if (!cancelled) {
          setPreparing(false);
        }
      }
    }, 0);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [open, fileName, t]);

  const handleShare = async () => {
    setBusy(true);
    setError("");
    try {
      setDidClickShare(true);
      didClickShareRef.current = true;
      const prepared = preparedShareUrl ? { url: preparedShareUrl, shareId: preparedShareId } : await prepareShareUrl();
      const shareUrl = prepared.url;
      if (!preparedShareUrl) {
        setPreparedShareUrl(prepared.url);
        setPreparedShareId(prepared.shareId);
        preparedShareIdRef.current = prepared.shareId;
      }
      if (typeof window !== "undefined") {
        window.open(shareUrl, "_blank", "noopener,noreferrer");
      }
    } catch (err) {
      const fallback = getLabel(t, "js.event.share.shareError", "Failed to share image.");
      const message = err instanceof Error && err.message ? err.message : fallback;
      setError(message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={modalTitle}
      bodyClassName="max-h-[90dvh] p-0 pt-0"
    >
      <div className="flex max-h-[90dvh] flex-col">
        <div className="overflow-y-auto p-4">
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

          {error ? <div className="mt-4 rounded-md border border-error bg-error/10 p-2 text-sm text-error">{error}</div> : null}
        </div>

        <div className="sticky bottom-0 border-t border-base-300 bg-base-100 p-4">
          <button type="button" onClick={handleShare} disabled={busy || preparing} className="btn btn-primary w-full">
            {preparing ? "Preparing..." : busy ? `${shareNowLabel}...` : shareNowLabel}
          </button>
        </div>
      </div>
    </Modal>
  );
}
