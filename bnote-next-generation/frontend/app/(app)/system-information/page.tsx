"use client";

import { useEffect, useState } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { useI18n } from "@/contexts/I18nContext";
import { formatDateTimeShort } from "@/lib/date-time";
import {
  systemInformationApi,
  type SystemInformationOverview,
} from "@/lib/system-information-api";

export default function SystemInformationPage() {
  const { t, lang } = useI18n();
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);
  const [overview, setOverview] = useState<SystemInformationOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError("");
      try {
        const overviewData = await systemInformationApi.getOverview();
        if (cancelled) return;
        setOverview(overviewData);
      } catch (err) {
        if (cancelled) return;
        setError(
          err instanceof Error
            ? err.message
            : (t("js.common.loadFailed") !== "js.common.loadFailed"
                ? t("js.common.loadFailed")
                : "Failed to load data.")
        );
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [t]);

  return (
    <PageContent className="space-y-6">
      <AppPageHeader
        moduleKey="system-information"
        title={label("js.systemInformation.title", "System Information")}
        subtitle={label(
          "js.systemInformation.subtitle",
          "Legacy core data and Next Generation build metadata."
        )}
      />

      {error ? (
        <div className="rounded-box border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
          {error}
        </div>
      ) : null}

      {loading ? (
        <div className="rounded-box border border-base-300 bg-base-100 px-4 py-3 text-sm text-base-content/70">
          {label("js.common.loading", "Loading...")}
        </div>
      ) : null}

      {!loading && overview ? (
        <section className="rounded-box border border-base-300 bg-base-100 p-4">
          <h2 className="text-base font-semibold text-base-content">
            {label("js.systemInformation.section.system", "System")}
          </h2>
          <dl className="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <InfoRow label={label("js.systemInformation.company", "Company")} value={overview.company || "—"} />
            <InfoRow
              label={label("js.systemInformation.legacyVersion", "BNote legacy version")}
              value={overview.bnote_version_legacy || "unknown"}
            />
            <InfoRow label={label("js.systemInformation.language", "Language")} value={overview.lang || "—"} />
            <InfoRow label={label("js.systemInformation.country", "Country")} value={overview.country || "—"} />
            <InfoRow
              label={label("js.systemInformation.demoMode", "Demo mode")}
              value={overview.demo_mode ? label("js.common.yes", "Yes") : label("js.common.no", "No")}
            />
            <InfoRow label={label("js.systemInformation.systemUrl", "System URL")} value={overview.system_url || "—"} />
            <InfoRow label={label("js.systemInformation.modulesCount", "Modules")} value={String(overview.modules_count ?? 0)} />
          </dl>
        </section>
      ) : null}

      {!loading && overview ? (
        <section className="rounded-box border border-base-300 bg-base-100 p-4">
          <h2 className="text-base font-semibold text-base-content">
            {label("js.systemInformation.section.build", "Next Generation Build")}
          </h2>
          <dl className="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <InfoRow label={label("js.systemInformation.version", "Version")} value={overview.nextgen.version || "unknown"} />
            <InfoRow label={label("js.systemInformation.buildId", "Build ID")} value={overview.nextgen.buildId || "unknown"} />
            <InfoRow label={label("js.systemInformation.commit", "Commit")} value={overview.nextgen.commit || "unknown"} />
            <InfoRow
              label={label("js.systemInformation.buildTime", "Build time")}
              value={formatDateTimeShort(overview.nextgen.buildTime, lang) ?? (overview.nextgen.buildTime || "unknown")}
            />
          </dl>
        </section>
      ) : null}

    </PageContent>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <dt className="text-xs uppercase tracking-wide text-base-content/60">{label}</dt>
      <dd className="mt-0.5 text-sm text-base-content break-all">{value}</dd>
    </div>
  );
}
