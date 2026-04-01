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
  const funnyText = t("js.wrapped.card.funLine", [
    data?.profile.firstName ?? "",
    t(data?.personal.funFacts.favoriteType === "concert" ? "js.sidebar.concerts" : "js.sidebar.rehearsals"),
  ]);

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
              className="w-[520px] max-w-[92vw] rounded-3xl bg-white p-0 text-[#111827] shadow-xl"
              style={{ aspectRatio: "4 / 5" }}
            >
              <div className="rounded-t-3xl bg-[linear-gradient(135deg,#bfdfff_0%,#d9ecff_38%,#edf6ff_62%,#d2e8ff_100%)] px-6 py-5">
                <div className="flex items-center justify-between">
                  <div className="min-w-0">
                    <p className="text-[10px] uppercase tracking-wide text-[#4b5563]">{data?.year}</p>
                    <p className="truncate text-base font-semibold">{data?.profile.bandName}</p>
                  </div>
                  <BNoteLogo size="sm" />
                </div>
                <div className="mt-6">
                  <h3 className="text-3xl font-bold">{t("js.wrapped.card.headline", [data?.profile.firstName ?? ""])}</h3>
                  <p className="mt-1 text-sm text-[#4b5563]">{t("js.wrapped.card.subline", [String(data?.year ?? "")])}</p>
                </div>
              </div>

              <div className="px-6 py-5">
                <div className="grid grid-cols-2 gap-3 text-center">
                  <div className="rounded-xl border border-[#cfe6ff] bg-[#eaf4ff] px-3 py-3">
                    <div className="text-xs text-[#355070]">{t("js.wrapped.card.events")}</div>
                    <div className="text-2xl font-bold text-[#0b2e4f]">{totalEvents}</div>
                  </div>
                  <div className="rounded-xl border border-[#c1dcfb] bg-[#dff0ff] px-3 py-3">
                    <div className="text-xs text-[#355070]">{t("js.wrapped.card.yesRate")}</div>
                    <div className="text-2xl font-bold text-[#0b2e4f]">{yesRate.toFixed(1)}%</div>
                  </div>
                </div>

                <div className="mt-4 rounded-xl border border-[#cfe6ff] bg-[#f4f9ff] px-4 py-3 text-sm">
                  <div className="flex items-center justify-between">
                    <span className="text-[#355070]">{t("js.sidebar.rehearsals")}</span>
                    <span className="font-semibold text-[#0b2e4f]">{rehearsals}</span>
                  </div>
                  <div className="mt-1 flex items-center justify-between">
                    <span className="text-[#355070]">{t("js.sidebar.concerts")}</span>
                    <span className="font-semibold text-[#0b2e4f]">{concerts}</span>
                  </div>
                </div>

                <div className="mt-4 rounded-xl border border-[#8fc7ff] bg-[#dcedff] px-4 py-3 text-sm text-[#0b2e4f]">
                  <p className="font-medium">{funnyText}</p>
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

