"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { toBlob } from "html-to-image";
import { Modal } from "@/components/Modal";
import { BNoteLogo } from "@/components/BNoteLogo";
import { getApiUrl } from "@/lib/api";
import { type WrappedYearData } from "@/lib/wrapped-api";

interface ShareCardCreateResult {
  url: string;
  shareId: string;
  expiresAt: number;
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
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [shareUrl, setShareUrl] = useState("");
  const [copied, setCopied] = useState(false);

  const fileName = useMemo(() => {
    const firstName = data?.profile.firstName || "member";
    const bandName = data?.profile.bandName || "bnote";
    const year = String(data?.year ?? "");
    return `${year} Wrapped - ${firstName} - ${bandName}.png`;
  }, [data?.profile.bandName, data?.profile.firstName, data?.year]);

  useEffect(() => {
    if (!open) {
      setError("");
      setShareUrl("");
      setCopied(false);
    }
  }, [open]);

  const createShareBlob = async (): Promise<Blob> => {
    if (!previewRef.current) throw new Error("No preview");
    const blob = await toBlob(previewRef.current, {
      cacheBust: true,
      pixelRatio: 3,
      backgroundColor: "#ffffff",
      imagePlaceholder:
        "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==",
    });
    if (!blob) throw new Error(t("js.wrapped.share.error"));
    return blob;
  };

  const upload = async (blob: Blob): Promise<ShareCardCreateResult> => {
    const formData = new FormData();
    formData.set("file", new File([blob], fileName, { type: "image/png" }));
    formData.set("name", fileName);

    const apiUrl = getApiUrl();
    const endpoint =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    endpoint.searchParams.set("module", "share");
    endpoint.searchParams.set("action", "uploadShareCard");

    const res = await fetch(endpoint.toString(), {
      method: "POST",
      body: formData,
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });
    const json = (await res.json()) as { success?: boolean; data?: ShareCardCreateResult; error?: string };
    if (!res.ok || json.success === false || !json.data?.url) {
      throw new Error(json.error || t("js.wrapped.share.error"));
    }
    return json.data;
  };

  const handleCreateLink = async () => {
    if (!data) return;
    setBusy(true);
    setError("");
    setCopied(false);
    try {
      const blob = await createShareBlob();
      const result = await upload(blob);
      setShareUrl(result.url);
    } catch (err) {
      setError(err instanceof Error ? err.message : t("js.wrapped.share.error"));
    } finally {
      setBusy(false);
    }
  };

  const handleCopy = async () => {
    if (!shareUrl) return;
    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
    } catch {
      setError(t("js.wrapped.share.copyError"));
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
  const noResponses = data?.personal.responses.no ?? 0;
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
      bodyClassName="!max-h-[85vh]"
    >
      <div className="space-y-4">
        <div className="flex justify-center">
          <div className="rounded-xl border border-base-300 p-2 bg-base-200/40">
            <div
              ref={previewRef}
              className="relative w-[520px] max-w-[92vw] overflow-hidden rounded-3xl bg-white p-0 text-[#0f172a] shadow-2xl"
              style={{ aspectRatio: "4 / 5" }}
            >
              <div className="absolute inset-0 wrapped-card-bg" />
              <div className="absolute -top-20 -right-20 h-48 w-48 rounded-full wrapped-spotlight" />
              <div className="absolute top-28 -left-16 h-40 w-40 rounded-full wrapped-spotlight-warm" />
              <div className="relative flex h-full flex-col px-7 py-6">
                <div className="flex items-center justify-between">
                  <div className="min-w-0">
                    <p className="text-[11px] uppercase tracking-[0.24em] text-primary">{data?.year}</p>
                    <p className="truncate text-base font-semibold text-[#0f172a]">{data?.profile.bandName}</p>
                  </div>
                  <div className="rounded-full bg-white/70 px-2.5 py-2 shadow-sm">
                    <BNoteLogo size="sm" />
                  </div>
                </div>

                <div className="mt-6">
                  <h3 className="wrapped-display text-4xl font-bold leading-[1.05]">
                    {t("js.wrapped.card.headline", [data?.profile.firstName ?? ""])}
                  </h3>
                  <p className="mt-1 text-sm text-[#334155]">
                    {t("js.wrapped.card.subline", [String(data?.year ?? "")])}
                  </p>
                </div>

                <div className="mt-6 grid grid-cols-[1.25fr_0.75fr] gap-4">
                  <div className="rounded-2xl border border-white/80 bg-white/80 px-4 py-4 shadow-sm">
                    <div className="text-xs uppercase tracking-[0.2em] text-primary">
                      {t("js.wrapped.card.events")}
                    </div>
                    <div className="wrapped-display mt-2 text-4xl font-bold text-[#0f172a]">{totalEvents}</div>
                  </div>
                  <div className="rounded-2xl border border-primary/30 bg-primary/10 px-4 py-4 shadow-sm">
                    <div className="text-xs uppercase tracking-[0.2em] text-primary">
                      {t("js.wrapped.card.yesRate")}
                    </div>
                    <div className="wrapped-display mt-2 text-3xl font-bold text-[#0f172a]">
                      {yesRate.toFixed(1)}%
                    </div>
                  </div>
                </div>

                <div className="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                  <span className="rounded-full px-3 py-1 wrapped-chip-primary">
                    {t("js.sidebar.rehearsals")} · {rehearsals}
                  </span>
                  <span className="rounded-full px-3 py-1 wrapped-chip-accent">
                    {t("js.sidebar.concerts")} · {concerts}
                  </span>
                </div>

                <div className="mt-4 rounded-2xl border border-primary/25 bg-white/75 px-4 py-3 text-sm text-[#0f172a] shadow-sm">
                  <p className="font-medium">{funnyText}</p>
                  <p className="mt-2 text-xs text-[#334155]">{responseSplitText}</p>
                </div>

                <div className="mt-auto flex items-center justify-between pt-5 text-xs text-[#334155]">
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

        {error ? <div className="alert alert-error text-sm">{error}</div> : null}

        <div className="space-y-2">
          <button type="button" className="btn btn-primary w-full" onClick={() => void handleCreateLink()} disabled={busy}>
            {busy ? t("js.wrapped.share.creating") : t("js.wrapped.share.create")}
          </button>
          {shareUrl ? (
            <>
              <input type="text" className="input input-bordered w-full text-sm" value={shareUrl} readOnly />
              <div className="grid grid-cols-2 gap-2">
                <button type="button" className="btn btn-soft" onClick={() => void handleCopy()}>
                  {copied ? t("js.wrapped.share.copied") : t("js.wrapped.share.copy")}
                </button>
                <a className="btn btn-soft btn-primary" href={shareUrl} target="_blank" rel="noopener noreferrer">
                  {t("js.wrapped.share.open")}
                </a>
              </div>
            </>
          ) : null}
        </div>
      </div>
    </Modal>
  );
}
