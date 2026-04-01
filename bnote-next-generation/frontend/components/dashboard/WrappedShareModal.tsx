"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { toBlob } from "html-to-image";
import { Modal } from "@/components/Modal";
import { BNoteLogo } from "@/components/BNoteLogo";
import { TablerIconByName } from "@/components/icons";
import { getApiUrl } from "@/lib/api";
import { type WrappedYearData } from "@/lib/wrapped-api";
import { getWrappedThemeStyle } from "@/lib/wrapped-theme";

interface ShareCardCreateResult {
  url: string;
  shareId: string;
  expiresAt: number;
}

const SHARE_CARD_WIDTH = 420;
const SHARE_CARD_HEIGHT = 560;

function medalIconName(level: "gold" | "silver" | "bronze"): string {
  if (level === "gold") return "laurel-wreath-1";
  if (level === "silver") return "laurel-wreath-2";
  return "laurel-wreath-3";
}

function medalCircleTone(level: "gold" | "silver" | "bronze"): string {
  if (level === "gold") return "bg-[#f8e8b2] text-[#b38712] border-[#d4af37]/55";
  if (level === "silver") return "bg-[#e8ecf2] text-[#7f8794] border-[#b7bcc5]/55";
  return "bg-[#ebc8a8] text-[#9b5b22] border-[#b87333]/55";
}

function formatDeadlineGapForDisplay(
  gapHours: number,
  t: (key: string, params?: string[]) => string,
  lang?: string
): string {
  if (!Number.isFinite(gapHours)) return "—";

  const absHours = Math.abs(gapHours);
  const absDays = absHours / 24;
  const number = new Intl.NumberFormat(lang || undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 1,
  }).format(absHours >= 24 ? absDays : absHours);

  if (gapHours <= 0) {
    return absHours >= 24
      ? t("js.wrapped.metric.deadlineGap.beforeDays", [number])
      : t("js.wrapped.metric.deadlineGap.beforeHours", [number]);
  }

  return absHours >= 24
    ? t("js.wrapped.metric.deadlineGap.afterDays", [number])
    : t("js.wrapped.metric.deadlineGap.afterHours", [number]);
}

function vibeProofValue(
  proof: WrappedYearData["personal"]["vibePersona"]["proof"],
  t: (key: string, params?: string[]) => string,
  lang?: string
): string {
  if (proof.label === "deadline_gap") {
    return formatDeadlineGapForDisplay(proof.value * 24, t, lang);
  }
  if (proof.unit === "percent") {
    return t("js.wrapped.achievements.value.percent", [proof.value.toFixed(1)]);
  }
  return t("js.wrapped.achievements.value.count", [String(Math.round(proof.value))]);
}

async function waitForImagesToBeReady(container: HTMLElement): Promise<void> {
  const images = Array.from(container.querySelectorAll("img"));
  if (images.length === 0) return;

  await Promise.all(
    images.map(
      (img) =>
        new Promise<void>((resolve) => {
          let resolved = false;
          const done = () => {
            if (!resolved) {
              resolved = true;
              resolve();
            }
          };

          if (img.complete && img.naturalWidth > 0) {
            done();
            return;
          }

          const onLoad = () => {
            img.removeEventListener("load", onLoad);
            img.removeEventListener("error", onError);
            done();
          };
          const onError = () => {
            img.removeEventListener("load", onLoad);
            img.removeEventListener("error", onError);
            done();
          };

          img.addEventListener("load", onLoad, { once: true });
          img.addEventListener("error", onError, { once: true });

          const maybeDecode = (img as HTMLImageElement).decode;
          if (typeof maybeDecode === "function") {
            maybeDecode.call(img).then(done).catch(done);
          }
        })
    )
  );
}

export function WrappedShareModal({
  open,
  onClose,
  data,
  lang,
  t,
}: {
  open: boolean;
  onClose: () => void;
  data: WrappedYearData | null;
  lang?: string;
  t: (key: string, params?: string[]) => string;
}) {
  const previewRef = useRef<HTMLDivElement | null>(null);
  const previewViewportRef = useRef<HTMLDivElement | null>(null);
  const captureRef = useRef<HTMLDivElement | null>(null);
  const preparedShareIdRef = useRef("");
  const didClickShareRef = useRef(false);

  const [busy, setBusy] = useState(false);
  const [preparing, setPreparing] = useState(false);
  const [preparedShareUrl, setPreparedShareUrl] = useState("");
  const [preparedShareId, setPreparedShareId] = useState("");
  const [error, setError] = useState("");
  const [previewScale, setPreviewScale] = useState(1);

  const fileName = useMemo(() => {
    const firstName = data?.profile.firstName || "member";
    const bandName = data?.profile.bandName || "bnote";
    const year = String(data?.year ?? "");
    return `${year} Wrapped - ${firstName} - ${bandName}.png`;
  }, [data?.profile.bandName, data?.profile.firstName, data?.year]);
  const themeStyle = useMemo(() => getWrappedThemeStyle(data?.year), [data?.year]);

  const createShareBlob = useCallback(async (): Promise<Blob> => {
    const captureNode = captureRef.current ?? previewRef.current;
    if (!captureNode) throw new Error("No preview");
    await waitForImagesToBeReady(captureNode);
    const blob = await toBlob(captureNode, {
      cacheBust: true,
      pixelRatio: 3,
      backgroundColor: "#ffffff",
      imagePlaceholder:
        "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==",
    });
    if (!blob) throw new Error(t("js.wrapped.share.error"));
    return blob;
  }, [t]);

  const createShareUrl = useCallback(async (blob: Blob): Promise<ShareCardCreateResult> => {
    const formData = new FormData();
    const file = new File([blob], fileName, { type: "image/png" });
    formData.set("file", file);
    formData.set("name", fileName);

    const apiUrl = getApiUrl();
    const endpoint =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    endpoint.searchParams.set("module", "share");
    endpoint.searchParams.set("action", "uploadShareCard");

    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 20000);
    let res: Response;
    try {
      res = await fetch(endpoint.toString(), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: { Accept: "application/json" },
        signal: controller.signal,
      });
    } catch (err) {
      if (err instanceof DOMException && err.name === "AbortError") {
        throw new Error(t("js.wrapped.share.timeoutError"));
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
      throw new Error(t("js.wrapped.share.nonJsonError"));
    }

    if (!res.ok || json.success === false || !json.data?.url || !json.data?.shareId) {
      throw new Error(json.error || t("js.wrapped.share.error"));
    }

    return json.data;
  }, [fileName, t]);

  const deleteShareCard = useCallback(async (shareId: string): Promise<void> => {
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
  }, []);

  const prepareShareUrl = useCallback(async (): Promise<ShareCardCreateResult> => {
    const blob = await createShareBlob();
    return createShareUrl(blob);
  }, [createShareBlob, createShareUrl]);

  useEffect(() => {
    if (!open) {
      if (preparedShareIdRef.current && !didClickShareRef.current) {
        void deleteShareCard(preparedShareIdRef.current);
      }
      setPreparing(false);
      setPreparedShareUrl("");
      setPreparedShareId("");
      preparedShareIdRef.current = "";
      didClickShareRef.current = false;
      setError("");
      return;
    }

    let cancelled = false;
    setPreparing(true);
    setPreparedShareUrl("");
    setPreparedShareId("");
    preparedShareIdRef.current = "";
    didClickShareRef.current = false;
    setError("");

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
          setError(err instanceof Error && err.message ? err.message : t("js.wrapped.share.error"));
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
  }, [open, deleteShareCard, prepareShareUrl, t]);

  useEffect(() => {
    if (!open) return;

    const updatePreviewScale = () => {
      const viewport = previewViewportRef.current;
      if (!viewport) return;
      const availableWidth = Math.max(220, viewport.clientWidth - 12);
      const availableHeight = Math.max(240, viewport.clientHeight - 12);
      const rawScale = Math.min(
        1,
        availableWidth / SHARE_CARD_WIDTH,
        availableHeight / SHARE_CARD_HEIGHT
      );
      const scale = Math.min(1, rawScale * 0.97);
      setPreviewScale(scale);
    };

    updatePreviewScale();

    let observer: ResizeObserver | null = null;
    if (typeof ResizeObserver !== "undefined" && previewViewportRef.current) {
      observer = new ResizeObserver(() => updatePreviewScale());
      observer.observe(previewViewportRef.current);
    }

    window.addEventListener("resize", updatePreviewScale);
    window.addEventListener("orientationchange", updatePreviewScale);
    return () => {
      observer?.disconnect();
      window.removeEventListener("resize", updatePreviewScale);
      window.removeEventListener("orientationchange", updatePreviewScale);
    };
  }, [open]);

  const handleShare = async () => {
    if (!data) return;
    setBusy(true);
    setError("");

    try {
      didClickShareRef.current = true;
      const prepared = preparedShareUrl
        ? { url: preparedShareUrl, shareId: preparedShareId }
        : await prepareShareUrl();

      if (!preparedShareUrl) {
        setPreparedShareUrl(prepared.url);
        setPreparedShareId(prepared.shareId);
        preparedShareIdRef.current = prepared.shareId;
      }

      if (typeof window !== "undefined") {
        window.open(prepared.url, "_blank", "noopener,noreferrer");
      }
    } catch (err) {
      setError(err instanceof Error && err.message ? err.message : t("js.wrapped.share.error"));
    } finally {
      setBusy(false);
    }
  };

  const modalTitle = t("js.wrapped.share.title");
  const vibePersona = data?.personal.vibePersona ?? null;
  const vibePersonaId = vibePersona?.id ?? "reliable_anchor";
  const vibeVariant = vibePersona ? Math.max(0, Math.min(3, vibePersona.variant || 0)) : 0;
  const vibeTitleKey = `js.wrapped.vibe.persona.${vibePersonaId}.title`;
  const vibeHypeKey = `js.wrapped.vibe.persona.${vibePersonaId}.hype.${vibeVariant}`;
  const vibeHypeFallbackKey = `js.wrapped.vibe.persona.${vibePersonaId}.hype.0`;
  const vibeHeadline = t(vibeTitleKey);
  const vibeHypeRaw = t(vibeHypeKey, [data?.profile.firstName ?? ""]);
  const vibeHypeFallback = t(vibeHypeFallbackKey, [data?.profile.firstName ?? ""]);
  const vibeHype =
    vibeHypeRaw !== vibeHypeKey
      ? vibeHypeRaw
      : vibeHypeFallback !== vibeHypeFallbackKey
        ? vibeHypeFallback
        : t("js.wrapped.vibe.hypeFallback", [data?.profile.firstName ?? ""]);
  const vibeProofLabel = t(`js.wrapped.vibe.proof.${vibePersona?.proof.label ?? "events"}`);
  const vibeProofText = vibePersona ? vibeProofValue(vibePersona.proof, t, lang) : "—";
  const vibeProofLine = t("js.wrapped.vibe.proofLine", [vibeProofLabel, vibeProofText]);
  const showLowerIsBetterHint = vibePersona?.proof.direction === "lower_better";
  const medals = data?.achievements.personalBadges ?? [];
  const renderShareCardContent = () => (
    <>
      <div className="absolute inset-0 wrapped-card-bg" />
      <div className="absolute -top-20 -right-20 h-44 w-44 rounded-full wrapped-spotlight" />
      <div className="absolute top-20 -left-14 h-32 w-32 rounded-full wrapped-spotlight-warm" />
      <div className="relative flex h-full flex-col px-5 py-5">
        <div className="flex items-center justify-between">
          <div className="min-w-0">
            <p className="text-[10px] uppercase tracking-[0.24em] text-primary">{data?.year}</p>
            <p className="truncate text-sm font-semibold text-[#0f172a]">{data?.profile.bandName}</p>
          </div>
          <BNoteLogo size="sm" />
        </div>

        <div className="mt-5">
          <h3 className="wrapped-display text-3xl font-bold leading-[1.05]">
            {t("js.wrapped.card.headline", [data?.profile.firstName ?? ""])}
          </h3>
          <p className="mt-1 text-xs text-[#334155]">
            {t("js.wrapped.card.subline", [String(data?.year ?? "")])}
          </p>
        </div>

        <div className="mt-4 rounded-2xl border border-primary/25 bg-white/80 px-3 py-3 text-xs text-[#0f172a] shadow-sm">
          <p className="text-[10px] uppercase tracking-[0.2em] text-primary">{t("js.wrapped.story.vibeTitle")}</p>
          <p className="wrapped-display mt-1 text-2xl font-bold text-[#0f172a]">{vibeHeadline}</p>
          <p className="mt-1.5 font-medium leading-snug [display:-webkit-box] [-webkit-line-clamp:2] [-webkit-box-orient:vertical] overflow-hidden">
            {vibeHype}
          </p>
          <p className="mt-1.5 text-[11px] text-[#334155] leading-snug [display:-webkit-box] [-webkit-line-clamp:2] [-webkit-box-orient:vertical] overflow-hidden">
            {vibeProofLine}
          </p>
          {showLowerIsBetterHint ? (
            <p className="mt-1 text-[10px] text-[#475569] leading-snug [display:-webkit-box] [-webkit-line-clamp:1] [-webkit-box-orient:vertical] overflow-hidden">
              {t("js.wrapped.metric.deadlineGap.hint")}
            </p>
          ) : null}
        </div>

        {medals.length > 0 ? (
          <div className="mt-3 rounded-2xl border border-primary/20 bg-white/75 px-3 py-2.5 shadow-sm">
            <p className="text-[10px] uppercase tracking-[0.2em] text-primary">
              {t("js.wrapped.achievements.title")}
            </p>
            <div className="mt-2 grid grid-cols-2 gap-2">
              {medals.slice(0, 4).map((badge) => (
                <div
                  key={badge.id}
                  className="flex min-w-0 items-start gap-2 rounded-xl border border-primary/20 bg-white/90 px-2 py-1.5 min-h-12"
                >
                  <span
                    className={`inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border ${medalCircleTone(
                      badge.level
                    )}`}
                  >
                    <TablerIconByName name={medalIconName(badge.level)} className="h-4 w-4" />
                  </span>
                  <span className="min-w-0 text-[10px] font-semibold leading-tight text-[#0f172a] whitespace-normal break-words [overflow-wrap:anywhere] [display:-webkit-box] [-webkit-line-clamp:2] [-webkit-box-orient:vertical] overflow-hidden">
                    {t(`js.wrapped.achievements.badge.${badge.id}.title`)}
                  </span>
                </div>
              ))}
            </div>
          </div>
        ) : null}

        <div className="mt-auto flex items-center justify-between pt-4 text-xs text-[#334155]">
          <span className="truncate">#{data?.profile.bandName}</span>
          <div className="flex items-center gap-2">
            <span className="uppercase tracking-[0.2em]">BNote</span>
            <BNoteLogo size="sm" className="scale-75" />
          </div>
        </div>
      </div>
    </>
  );

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={modalTitle}
      dialogClassName="!max-w-3xl"
      bodyClassName="max-h-[90dvh] p-0 pt-0"
    >
      <div className="flex max-h-[90dvh] flex-col">
        <div className="fixed left-[-10000px] top-0 pointer-events-none" aria-hidden="true">
          <div className="rounded-xl border border-base-300 bg-base-200/30 p-2" data-theme="bnotelight" style={{ colorScheme: "light" }}>
            <div
              ref={captureRef}
              className="relative mx-auto w-[420px] overflow-hidden rounded-3xl bg-white p-0 text-[#0f172a]"
              style={{ aspectRatio: "3 / 4", ...themeStyle }}
            >
              {renderShareCardContent()}
            </div>
          </div>
        </div>

        <div className="overflow-y-auto p-4">
          <div ref={previewViewportRef} className="max-h-[62dvh] overflow-auto rounded-xl [touch-action:pan-x_pan-y]">
            <div className="flex justify-center">
            <div className="inline-block rounded-xl border border-base-300 bg-base-200/30 p-2" data-theme="bnotelight" style={{ colorScheme: "light" }}>
              <div
                className="relative"
                style={{
                  width: `${Math.ceil(SHARE_CARD_WIDTH * previewScale)}px`,
                  height: `${Math.ceil(SHARE_CARD_HEIGHT * previewScale)}px`,
                }}
              >
                <div
                  ref={previewRef}
                  className="absolute left-0 top-0 overflow-hidden rounded-3xl bg-white p-0 text-[#0f172a]"
                  style={{
                    width: `${SHARE_CARD_WIDTH}px`,
                    height: `${SHARE_CARD_HEIGHT}px`,
                    transform: `scale(${previewScale})`,
                    transformOrigin: "top left",
                    ...themeStyle,
                  }}
                >
                  {renderShareCardContent()}
                </div>
              </div>
            </div>
            </div>
          </div>

          {error ? <div className="mt-4 rounded-md border border-error bg-error/10 p-2 text-sm text-error">{error}</div> : null}
        </div>

        <div className="sticky bottom-0 border-t border-base-300 bg-base-100 p-4">
          <button type="button" onClick={() => void handleShare()} disabled={busy || preparing} className="btn btn-primary w-full">
            {preparing ? t("js.wrapped.share.preparing") : busy ? t("js.wrapped.share.sharing") : t("js.wrapped.share.now")}
          </button>
        </div>
      </div>
    </Modal>
  );
}
