/**
 * BNote Next Generation - Vote detail view and cast-vote form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { votesApi, type VoteDetail as VoteDetailType } from "@/lib/votes-api";
import { formatDateShortDisplay } from "@/lib/date-time";
import { getEntityPath } from "@/lib/entities/paths";
import { CheckCircle } from "@/components/icons";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { getStatusPillStyle } from "@/lib/entity-config";

export interface VoteDetailProps {
  /** Optional content to render inside the root container after the main content (e.g. comments). */
  renderAfterContent?: React.ReactNode;
}

export function VoteDetail({ renderAfterContent }: VoteDetailProps = {}) {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const [item, setItem] = useState<VoteDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [choices, setChoices] = useState<Record<number, string>>({});
  const [singleChoice, setSingleChoice] = useState<number | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const loadVote = () => {
    if (!id || id === "new" || !ready) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    votesApi
      .get(numId)
      .then(setItem)
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Failed to load")
      )
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadVote();
  }, [id, ready]);

  const handleSubmitVote = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!item) return;
    setSubmitting(true);
    try {
      if (item.is_multi) {
        await votesApi.submit(item.id, { choices });
      } else {
        if (singleChoice == null) {
          showToast("Please select an option", "error");
          setSubmitting(false);
          return;
        }
        await votesApi.submit(item.id, { uservote: singleChoice });
      }
      showToast(
        t("js.votes.submitted") !== "js.votes.submitted"
          ? t("js.votes.submitted")
          : "Vote submitted",
        "success"
      );
      loadVote();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Submit failed", "error");
    } finally {
      setSubmitting(false);
    }
  };

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (id === "new") {
    return null;
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-error">
          {error || "Vote not found."}
        </p>
      </div>
    );
  }

  const optionLabel = (opt: { name?: string; odate?: string | null }) =>
    opt.odate ? formatDateShortDisplay(opt.odate, lang) : (opt.name ?? emptyText);

  return (
    <div className="mx-auto max-w-2xl space-y-4 p-4 md:space-y-6 md:p-6">
      <DetailPageHeader
        title={item.name || emptyText}
        right={
          item.is_author ? (
            <DetailEditButton onClick={() => router.push(getEntityPath("vote", item.id, "edit"))} />
          ) : undefined
        }
      />

      <DetailCard>
        <p className="text-sm text-base-content/60">
          {t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "End"}: {item.end ?? emptyText}
          {item.is_finished && (
            <span
              className="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
              style={getStatusPillStyle("inactive")}
            >
              {t("js.votes.finished") !== "js.votes.finished" ? t("js.votes.finished") : "Finished"}
            </span>
          )}
          {!item.is_finished && item.is_active && (
            <span
              className="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
              style={getStatusPillStyle("active")}
            >
              {t("js.common.active") !== "js.common.active" ? t("js.common.active") : "Active"}
            </span>
          )}
        </p>

        {item.options.length > 0 && (
          <div className="mt-4">
            <h2 className="text-sm font-semibold text-base-content/60">
              {t("js.votes.options") !== "js.votes.options" ? t("js.votes.options") : "Options"}
            </h2>
            <ul className="mt-2 list-inside list-disc space-y-1">
              {item.options.map((opt) => (
                <li key={opt.id}>{optionLabel(opt)}</li>
              ))}
            </ul>
          </div>
        )}

        {item.is_active && item.options.length > 0 && (
          <form onSubmit={handleSubmitVote} className="mt-6 space-y-4">
            <h2 className="text-sm font-semibold text-base-content/60">
              {t("js.votes.castVote") !== "js.votes.castVote" ? t("js.votes.castVote") : "Cast your vote"}
            </h2>
            {item.is_multi ? (
              <div className="space-y-2">
                {item.options.map((opt) => (
                  <div key={opt.id} className="flex flex-wrap items-center gap-2">
                    <span className="w-48">{optionLabel(opt)}</span>
                    <div className="select select-sm w-32">
                      <select
                        value={choices[opt.id] ?? ""}
                        onChange={(e) =>
                          setChoices((c) => ({ ...c, [opt.id]: e.target.value }))
                        }
                      >
                      <option value="">{emptyText}</option>
                      <option value="yes">
                        {t("js.votes.yes") !== "js.votes.yes" ? t("js.votes.yes") : "Yes"}
                      </option>
                      <option value="no">
                        {t("js.votes.no") !== "js.votes.no" ? t("js.votes.no") : "No"}
                      </option>
                      <option value="maybe">
                        {t("js.votes.maybe") !== "js.votes.maybe" ? t("js.votes.maybe") : "Maybe"}
                      </option>
                    </select>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="space-y-2">
                {item.options.map((opt) => (
                  <label key={opt.id} className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      name="voteOption"
                      value={opt.id}
                      checked={singleChoice === opt.id}
                      onChange={() => setSingleChoice(opt.id)}
                      className="radio radio-primary"
                    />
                    <span>{optionLabel(opt)}</span>
                  </label>
                ))}
              </div>
            )}
            <button
              type="submit"
              disabled={submitting}
              className="btn btn-primary btn-sm gap-2"
            >
              <CheckCircle className="h-4 w-4" />
              {t("js.votes.submit") !== "js.votes.submit" ? t("js.votes.submit") : "Submit"}
            </button>
          </form>
        )}

        {item.is_finished && item.result && Array.isArray(item.result) ? (
          <div className="mt-4">
            <h2 className="text-sm font-semibold text-base-content/60">
              {t("js.votes.results") !== "js.votes.results" ? t("js.votes.results") : "Results"}
            </h2>
            <pre className="mt-2 overflow-auto rounded-field border border-base-300 p-2 text-xs text-base-content">
              {JSON.stringify(item.result, null, 2)}
            </pre>
          </div>
        ) : null}
      </DetailCard>
      {renderAfterContent}
    </div>
  );
}
