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
import { formatDateShortDisplay, formatDateTimeShort } from "@/lib/date-time";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailCard } from "@/components/DetailCard";
import { ParticipationTrafficLight } from "@/components/entities/event/ParticipationTrafficLight";
import { StatusPicker } from "@/components/entities/event/StatusPicker";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { getStatusPillStyle } from "@/lib/entity-config";
import { getErrorMessage } from "@/lib/error-utils";
import { Spinner } from "@/components/Spinner";
import { VoteResults } from "./VoteResults";
import { VoteEligibleVotersCard } from "./VoteEligibleVotersCard";

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
        setError(getErrorMessage(err, t, "js.common.failedToLoad"))
      )
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadVote();
  }, [id, ready]);

  useEffect(() => {
    if (!item?.user_choices) return;
    const uc = item.user_choices;
    if (item.is_multi) {
      const next: Record<number, string> = {};
      for (const [k, v] of Object.entries(uc)) {
        const kid = parseInt(k, 10);
        if (!Number.isNaN(kid)) next[kid] = v;
      }
      setChoices(next);
    } else {
      const selected = Object.entries(uc).find(([, v]) => v === "yes");
      setSingleChoice(selected ? parseInt(selected[0], 10) : null);
    }
  }, [item?.id, item?.is_multi, item?.user_choices]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (id === "new") {
    return null;
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">
          {error ||
            (t("js.votes.notFound") !== "js.votes.notFound" ? t("js.votes.notFound") : "Vote not found.")}
        </p>
      </div>
    );
  }

  const optionLabel = (opt: { name?: string; odate?: string | null }) =>
    opt.odate ? formatDateShortDisplay(opt.odate, lang) : (opt.name ?? emptyText);
  const canEditVote = Boolean(item.can_edit ?? item.is_author);

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader
        title={item.name || emptyText}
        right={
          canEditVote ? (
            <DetailEditButton onClick={() => router.push(getEntityPath("vote", item.id, "edit"))} />
          ) : undefined
        }
      />

      <DetailCard>
        <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm">
          <div>
            <span className="text-base-content/60">
              {t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "End"}:
            </span>{" "}
            {item.end ? (formatDateTimeShort(item.end, lang) ?? item.end) : emptyText}
          </div>
          <div>
            <span className="text-base-content/60">
              {t("js.event.detail.status") !== "js.event.detail.status" ? t("js.event.detail.status") : "Status"}:
            </span>{" "}
            {canEditVote ? (
              <span className="ml-0.5 inline-flex align-middle">
                <StatusPicker
                  options={["active", "finished"]}
                  value={item.is_finished ? "finished" : "active"}
                  onChange={async (next) => {
                    setSubmitting(true);
                    try {
                      await votesApi.update(item.id, { is_finished: next === "finished" });
                      showToast(
                        next === "finished"
                          ? (t("js.votes.finishDone") !== "js.votes.finishDone" ? t("js.votes.finishDone") : "Vote finished")
                          : (t("js.votes.updated") !== "js.votes.updated" ? t("js.votes.updated") : "Vote updated"),
                        "success"
                      );
                      loadVote();
                    } catch (err) {
                      showToast(
                        err instanceof Error
                          ? err.message
                          : t("js.votes.submitFailed") !== "js.votes.submitFailed"
                            ? t("js.votes.submitFailed")
                            : "Submit failed",
                        "error"
                      );
                    } finally {
                      setSubmitting(false);
                    }
                  }}
                  labelFor={(v) =>
                    v === "finished"
                      ? (t("js.votes.finished") !== "js.votes.finished" ? t("js.votes.finished") : "Finished")
                      : (t("js.common.active") !== "js.common.active" ? t("js.common.active") : "Active")
                  }
                />
              </span>
            ) : (
              <span
                className="ml-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium border align-middle"
                style={getStatusPillStyle(item.is_finished ? "inactive" : "active")}
              >
                {item.is_finished
                  ? (t("js.votes.finished") !== "js.votes.finished" ? t("js.votes.finished") : "Finished")
                  : t("js.common.active") !== "js.common.active"
                    ? t("js.common.active")
                    : "Active"}
              </span>
            )}
          </div>
        </div>

        {item.is_active && item.options.length > 0 && (
          <div className="mt-6 space-y-4">
            <h2 className="text-sm font-semibold text-base-content/60">
              {t("js.votes.castVote") !== "js.votes.castVote" ? t("js.votes.castVote") : "Cast your vote"}
            </h2>
            {item.is_multi ? (
              <div className="space-y-3">
                {item.options.map((opt) => (
                  <div
                    key={opt.id}
                    className="flex flex-wrap items-center justify-between gap-3 rounded-field border border-base-300 px-3 py-2"
                  >
                    <span className="font-medium">{optionLabel(opt)}</span>
                    <ParticipationTrafficLight
                      value={(choices[opt.id] || "pending") as "yes" | "maybe" | "no" | "pending"}
                      onChange={async (next) => {
                        const val = next === "pending" ? "no" : next;
                        const nextChoices = { ...choices, [opt.id]: val };
                        setChoices(nextChoices);
                        setSubmitting(true);
                        try {
                          const fullChoices: Record<number, string> = {};
                          item.options.forEach((o) => {
                            fullChoices[o.id] = nextChoices[o.id] ?? "no";
                          });
                          await votesApi.submit(item.id, { choices: fullChoices });
                          showToast(
                            t("js.votes.submitted") !== "js.votes.submitted"
                              ? t("js.votes.submitted")
                              : "Vote submitted",
                            "success"
                          );
                          loadVote();
                        } catch (err) {
                          showToast(
                            err instanceof Error
                              ? err.message
                              : t("js.votes.submitFailed") !== "js.votes.submitFailed"
                                ? t("js.votes.submitFailed")
                                : "Submit failed",
                            "error"
                          );
                        } finally {
                          setSubmitting(false);
                        }
                      }}
                      allowMaybe={item.is_date}
                      disabled={submitting}
                    />
                  </div>
                ))}
              </div>
            ) : (
              <div
                className="flex flex-col gap-2 rounded-field border border-base-300 p-3"
                role="radiogroup"
                aria-label={
                  t("js.votes.castVote") !== "js.votes.castVote"
                    ? t("js.votes.castVote")
                    : "Cast your vote"
                }
              >
                {item.options.map((opt) => (
                  <label
                    key={opt.id}
                    className="label-text flex cursor-pointer items-center gap-2"
                  >
                    <input
                      type="radio"
                      name={`vote-${item.id}`}
                      className="radio radio-primary"
                      checked={singleChoice === opt.id}
                      disabled={submitting}
                      onChange={async () => {
                        if (singleChoice === opt.id) return;
                        setSubmitting(true);
                        try {
                          setSingleChoice(opt.id);
                          await votesApi.submit(item.id, { uservote: opt.id });
                          showToast(
                            t("js.votes.submitted") !== "js.votes.submitted"
                              ? t("js.votes.submitted")
                              : "Vote submitted",
                            "success"
                          );
                          loadVote();
                        } catch (err) {
                          showToast(
                            err instanceof Error
                              ? err.message
                              : t("js.votes.submitFailed") !== "js.votes.submitFailed"
                                ? t("js.votes.submitFailed")
                                : "Submit failed",
                            "error"
                          );
                        } finally {
                          setSubmitting(false);
                        }
                      }}
                    />
                    <span className="text-base font-medium">{optionLabel(opt)}</span>
                  </label>
                ))}
              </div>
            )}
          </div>
        )}

        {item.result != null && Array.isArray(item.result) ? (
          <VoteResults
            result={item.result}
            options={item.options}
            isDate={item.is_date}
            isMulti={item.is_multi}
            lang={lang}
          />
        ) : null}
      </DetailCard>
      <VoteEligibleVotersCard voteId={item.id} />
      {renderAfterContent}
    </div>
  );
}
