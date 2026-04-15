"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { DetailSection } from "@/components/DetailSection";
import { Spinner } from "@/components/Spinner";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { getApiPhpDirectoryUrl } from "@/lib/api";
import { configurationApi } from "@/lib/configuration-api";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

interface PublicConcertItem {
  id: number;
  title: string;
  begin: string;
  end: string;
  locationName: string;
  status: string;
}

interface PublicConcertsPayload {
  generatedAt: string;
  items: PublicConcertItem[];
}

export default function PublicConcertsPreviewPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [payload, setPayload] = useState<PublicConcertsPayload | null>(null);
  const [feedUrl, setFeedUrl] = useState(() => `${getApiPhpDirectoryUrl()}/public-concerts.json.php`);

  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);
  const feedRangeExampleUrl = useMemo(
    () => `${feedUrl}${feedUrl.includes("?") ? "&" : "?"}from=2026-01-01&to=2026-12-31`,
    [feedUrl]
  );

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const cfg = await configurationApi.getConfig();
      const configuredUrl = String(
        cfg?.derived?.publicConcertsFeedTokenizedUrl ?? cfg?.derived?.publicConcertsFeedUrl ?? ""
      ).trim();
      const effectiveUrl = configuredUrl !== "" ? configuredUrl : `${getApiPhpDirectoryUrl()}/public-concerts.json.php`;
      setFeedUrl(effectiveUrl);

      const res = await fetch(effectiveUrl);
      const json = await res.json();
      if (!res.ok || json?.success === false) {
        const msg = String(json?.error ?? "public_concerts_feed_unavailable");
        throw new Error(msg);
      }
      setPayload((json?.data ?? null) as PublicConcertsPayload | null);
    } catch (err) {
      setPayload(null);
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    if (!ready) return;
    void load();
  }, [ready, load]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={`${PAGE_CONTENT_CLASS} space-y-6`}>
      <AppPageHeader
        moduleKey="configuration"
        title={label("js.configuration.publicConcerts.previewTitle", "Public concerts preview")}
        actions={
          <div className="flex flex-wrap gap-2">
            <button type="button" className="btn btn-soft" onClick={() => void load()}>
              {label("js.common.refresh", "Refresh")}
            </button>
            <button
              type="button"
              className="btn btn-soft"
              onClick={async () => {
                try {
                  await navigator.clipboard.writeText(feedUrl);
                  showToast(
                    label("js.configuration.publicConcerts.copySuccess", "Public concerts feed URL copied."),
                    "success"
                  );
                } catch {
                  showToast(label("js.configuration.publicConcerts.copyFailed", "Copy failed"), "error");
                }
              }}
            >
              {label("js.configuration.publicConcerts.copyUrl", "Copy URL")}
            </button>
          </div>
        }
      />

      <DetailSection className="space-y-3">
        <p className="text-sm text-base-content/70">
          {label(
            "js.configuration.publicConcerts.previewHelp",
            "This preview fetches the public JSON endpoint exactly as external consumers would."
          )}
        </p>
        <label className="form-control">
          <span className="label-text text-xs font-medium text-base-content/70">
            {label("js.configuration.publicConcerts.feedUrl", "Public concerts feed URL")}
          </span>
          <input type="text" className="input input-bordered font-mono text-xs" value={feedUrl} readOnly />
        </label>
        <div className="rounded-box bg-base-200/40 p-3">
          <p className="text-xs font-semibold text-base-content">
            {label("js.configuration.publicConcerts.paramsTitle", "Optional URL parameters")}
          </p>
          <ul className="mt-2 list-disc space-y-1 pl-5 text-xs text-base-content/70">
            <li>
              <code>from=YYYY-MM-DD</code>{" "}
              {label(
                "js.configuration.publicConcerts.paramsFromHelp",
                "Include concerts that end on or after this day."
              )}
            </li>
            <li>
              <code>to=YYYY-MM-DD</code>{" "}
              {label(
                "js.configuration.publicConcerts.paramsToHelp",
                "Include concerts that start on or before this day."
              )}
            </li>
            <li>
              {label(
                "js.configuration.publicConcerts.paramsRangeHelp",
                "Using both returns concerts overlapping the date range."
              )}
            </li>
          </ul>
          <p className="mt-2 text-xs text-base-content/70">
            {label("js.configuration.publicConcerts.paramsExample", "Example:")} <code>{feedRangeExampleUrl}</code>
          </p>
        </div>
        {error ? (
          <div className="rounded-lg border border-error bg-error/15 text-error px-4 py-3 text-sm">{error}</div>
        ) : null}
      </DetailSection>

      <DetailSection className="space-y-3">
        <div className="flex items-center justify-between gap-2">
          <h2 className="text-lg font-semibold text-base-content">
            {label("js.configuration.publicConcerts.previewData", "Feed data")}
          </h2>
          <span className="text-xs text-base-content/60">
            {payload?.generatedAt
              ? `${label("js.configuration.publicConcerts.generatedAt", "Generated at")}: ${payload.generatedAt}`
              : ""}
          </span>
        </div>
        <pre className="max-h-[60vh] overflow-auto rounded-box border border-base-300 bg-base-200/50 p-3 text-xs font-mono whitespace-pre-wrap">
          {JSON.stringify(payload ?? { generatedAt: null, items: [] }, null, 2)}
        </pre>
      </DetailSection>
    </div>
  );
}
