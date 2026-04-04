"use client";

import { useEffect, useMemo, useState } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { useI18n } from "@/contexts/I18nContext";
import { formatDateTimeShort } from "@/lib/date-time";
import { changelogApi, type ChangelogResponse } from "@/lib/changelog-api";

export default function ChangelogPage() {
  const { t, lang } = useI18n();
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);
  const changeTypeLabel = (type: "added" | "fixed" | "changed" | "removed") => {
    if (type === "added") return label("js.changelog.type.added", "Added");
    if (type === "fixed") return label("js.changelog.type.fixed", "Fixed");
    if (type === "removed") return label("js.changelog.type.removed", "Removed");
    return label("js.changelog.type.changed", "Changed");
  };
  const [changelog, setChangelog] = useState<ChangelogResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError("");
      try {
        const data = await changelogApi.get();
        if (cancelled) return;
        setChangelog(data);
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

  const entries = useMemo(() => changelog?.entries ?? [], [changelog]);

  return (
    <PageContent className="space-y-6">
      <AppPageHeader
        moduleKey="changelog"
        iconName="confetti"
        iconColor="#0EA5E9"
        title={label("js.changelog.pageTitle", "What's New in BNote")}
        subtitle={label("js.changelog.pageSubtitle", "Product updates and fixes in BNote (not band news posts).")}
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

      {!loading && changelog?.releaseId ? (
        <div className="text-xs text-base-content/60">
          {label("js.changelog.releaseId", "Release")}: {changelog.releaseId}
        </div>
      ) : null}

      {!loading ? (
        <section className="rounded-box border border-base-300 bg-base-100 p-4">
          {entries.length > 0 ? (
            <ul className="space-y-4">
              {entries.map((entry) => (
                <li key={`${entry.bugId}-${entry.shortCommit}`} className="text-sm">
                  <div className="text-base-content">- {changeTypeLabel(entry.changeType)}: {entry.title}</div>
                  <div className="mt-1 pl-4 text-xs text-base-content/65">
                    {entry.bugId}
                    {entry.date ? ` · ${formatDateTimeShort(entry.date, lang) ?? entry.date}` : ""}
                    {entry.shortCommit ? ` · ${entry.shortCommit}` : ""}
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-base-content/70">
              {label("js.changelog.empty", "No changelog entries available yet.")}
            </p>
          )}
        </section>
      ) : null}
    </PageContent>
  );
}
