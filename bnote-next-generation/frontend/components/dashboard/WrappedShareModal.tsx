"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { toBlob } from "html-to-image";
import { Modal } from "@/components/Modal";
import { BNoteLogo } from "@/components/BNoteLogo";
import { getApiUrl } from "@/lib/api";
import { type WrappedYearData } from "@/lib/wrapped-api";
import { getWrappedThemeStyle } from "@/lib/wrapped-theme";

interface ShareCardCreateResult {
  url: string;
  shareId: string;
  expiresAt: number;
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
  t,
}: {
  open: boolean;
  onClose: () => void;
  data: WrappedYearData | null;
  t: (key: string, params?: string[]) => string;
}) {
  const previewRef = useRef<HTMLDivElement | null>(null);
  const preparedShareIdRef = useRef("");
  const didClickShareRef = useRef(false);

  const [busy, setBusy] = useState(false);
  const [preparing, setPreparing] = useState(false);
  const [preparedShareUrl, setPreparedShareUrl] = useState("");
  const [preparedShareId, setPreparedShareId] = useState("");
  const [error, setError] = useState("");

  const fileName = useMemo(() => {
    const firstName = data?.profile.firstName || "member";
    const bandName = data?.profile.bandName || "bnote";
    const year = String(data?.year ?? "");
    return `${year} Wrapped - ${firstName} - ${bandName}.png`;
  }, [data?.profile.bandName, data?.profile.firstName, data?.year]);
  const themeStyle = useMemo(() => getWrappedThemeStyle(data?.year), [data?.year]);

  const createShareBlob = useCallback(async (): Promise<Blob> => {
    if (!previewRef.current) throw new Error("No preview");
    await waitForImagesToBeReady(previewRef.current);
    const blob = await toBlob(previewRef.current, {
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
  const yesRate = data?.personal.responses.yesRate ?? 0;
  const totalEvents = data?.personal.events.total ?? 0;
  const rehearsals = data?.personal.events.rehearsals ?? 0;
  const concerts = data?.personal.events.concerts ?? 0;
  const totalResponses = data?.personal.responses.total ?? 0;
  const yesResponses = data?.personal.responses.yes ?? 0;
  const maybeResponses = data?.personal.responses.maybe ?? 0;
  const yesPct = totalResponses > 0 ? Math.round((yesResponses / totalResponses) * 100) : 0;
  const maybePct = totalResponses > 0 ? Math.round((maybeResponses / totalResponses) * 100) : 0;
  const noPct = totalResponses > 0 ? Math.max(0, 100 - yesPct - maybePct) : 0;
  const funnyText = t("js.wrapped.card.funLine", [
    data?.profile.firstName ?? "",
    t(data?.personal.funFacts.favoriteType === "concert" ? "js.sidebar.concerts" : "js.sidebar.rehearsals"),
  ]);
  const responseSplitText = t("js.wrapped.card.responseSplit", [String(yesPct), String(maybePct), String(noPct)]);

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={modalTitle}
      dialogClassName="!max-w-3xl"
      bodyClassName="max-h-[90dvh] p-0 pt-0"
    >
      <div className="flex max-h-[90dvh] flex-col">
        <div className="overflow-y-auto p-4">
          <div className="flex justify-center">
            <div className="inline-block rounded-xl border border-base-300 bg-base-200/30 p-2" data-theme="bnotelight" style={{ colorScheme: "light" }}>
              <div
                ref={previewRef}
                className="relative mx-auto w-[320px] md:w-[420px] max-w-[88vw] overflow-hidden rounded-3xl bg-white p-0 text-[#0f172a] shadow-2xl"
                style={{ aspectRatio: "4 / 5", ...themeStyle }}
              >
                <div className="absolute inset-0 wrapped-card-bg" />
                <div className="absolute -top-20 -right-20 h-44 w-44 rounded-full wrapped-spotlight" />
                <div className="absolute top-20 -left-14 h-32 w-32 rounded-full wrapped-spotlight-warm" />
                <div className="relative flex h-full flex-col px-5 py-5 md:px-6 md:py-6">
                  <div className="flex items-center justify-between">
                    <div className="min-w-0">
                      <p className="text-[10px] uppercase tracking-[0.24em] text-primary">{data?.year}</p>
                      <p className="truncate text-sm md:text-base font-semibold text-[#0f172a]">{data?.profile.bandName}</p>
                    </div>
                    <BNoteLogo size="sm" />
                  </div>

                  <div className="mt-5">
                    <h3 className="wrapped-display text-3xl md:text-4xl font-bold leading-[1.05]">
                      {t("js.wrapped.card.headline", [data?.profile.firstName ?? ""])}
                    </h3>
                    <p className="mt-1 text-xs md:text-sm text-[#334155]">
                      {t("js.wrapped.card.subline", [String(data?.year ?? "")])}
                    </p>
                  </div>

                  <div className="mt-5 grid grid-cols-[1.25fr_0.75fr] gap-3">
                    <div className="rounded-2xl border border-white/80 bg-white/80 px-3 py-3 shadow-sm">
                      <div className="text-[10px] md:text-xs uppercase tracking-[0.2em] text-primary">
                        {t("js.wrapped.card.events")}
                      </div>
                      <div className="wrapped-display mt-1 text-3xl md:text-4xl font-bold text-[#0f172a]">{totalEvents}</div>
                    </div>
                    <div className="rounded-2xl border border-primary/30 bg-primary/10 px-3 py-3 shadow-sm">
                      <div className="text-[10px] md:text-xs uppercase tracking-[0.2em] text-primary">
                        {t("js.wrapped.card.yesRate")}
                      </div>
                      <div className="wrapped-display mt-1 text-2xl md:text-3xl font-bold text-[#0f172a]">
                        {yesRate.toFixed(1)}%
                      </div>
                    </div>
                  </div>

                  <div className="mt-3 flex flex-wrap gap-2 text-[11px] md:text-xs font-semibold">
                    <span className="rounded-full px-3 py-1 wrapped-chip-primary">
                      {t("js.sidebar.rehearsals")} · {rehearsals}
                    </span>
                    <span className="rounded-full px-3 py-1 wrapped-chip-accent">
                      {t("js.sidebar.concerts")} · {concerts}
                    </span>
                  </div>

                  <div className="mt-3 rounded-2xl border border-primary/25 bg-white/75 px-3 py-3 text-xs md:text-sm text-[#0f172a] shadow-sm">
                    <p className="font-medium">{funnyText}</p>
                    <p className="mt-2 text-[11px] md:text-xs text-[#334155]">{responseSplitText}</p>
                  </div>

                  <div className="mt-auto flex items-center justify-between pt-4 text-[10px] md:text-xs text-[#334155]">
                    <span className="truncate">#{data?.profile.bandName}</span>
                    <div className="flex items-center gap-2">
                      <span className="uppercase tracking-[0.2em]">BNote</span>
                      <BNoteLogo size="sm" className="scale-75" />
                    </div>
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
