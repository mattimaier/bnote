/**
 * BNote Next Generation - Inline vote widget for dashboard event cards
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { ParticipationTrafficLight } from "@/components/entities/event/ParticipationTrafficLight";
import { formatDateShortDisplay } from "@/lib/date-time";
import { votesApi } from "@/lib/votes-api";

interface VoteOption {
  id: number;
  name?: string;
  odate?: string | null;
}

interface DashboardVoteWidgetProps {
  voteId: number;
  options: VoteOption[];
  userChoices: Record<number, string>;
  isDate: boolean;
  isMulti: boolean;
  lang: string;
  onVoteChange?: () => void;
  disabled?: boolean;
}

export function DashboardVoteWidget({
  voteId,
  options,
  userChoices,
  isDate,
  isMulti,
  lang,
  onVoteChange,
  disabled = false,
}: DashboardVoteWidgetProps) {
  const { t } = useI18n();
  const { showToast } = useToast();
  const [submitting, setSubmitting] = useState(false);
  const [choices, setChoices] = useState<Record<number, string>>(() => {
    const init: Record<number, string> = {};
    options.forEach((o) => {
      init[o.id] = userChoices[o.id] ?? "no";
    });
    return init;
  });
  const [singleChoice, setSingleChoice] = useState<number | null>(() => {
    if (isMulti) return null;
    const entry = Object.entries(userChoices).find(([, v]) => v === "yes");
    return entry ? parseInt(entry[0], 10) : null;
  });

  useEffect(() => {
    const init: Record<number, string> = {};
    options.forEach((o) => {
      init[o.id] = userChoices[o.id] ?? "no";
    });
    setChoices(init);
    if (!isMulti) {
      const entry = Object.entries(userChoices).find(([, v]) => v === "yes");
      setSingleChoice(entry ? parseInt(entry[0], 10) : null);
    }
  }, [options, userChoices, isMulti]);

  const optionLabel = (opt: VoteOption) =>
    opt.odate ? formatDateShortDisplay(opt.odate, lang) : (opt.name ?? "");

  const submit = async (data: { choices?: Record<number, string>; uservote?: number | null }) => {
    if (disabled || submitting) return;
    setSubmitting(true);
    try {
      await votesApi.submit(voteId, data);
      showToast(
        t("js.votes.submitted") !== "js.votes.submitted" ? t("js.votes.submitted") : "Vote submitted",
        "success"
      );
      onVoteChange?.();
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
  };

  if (options.length === 0) return null;

  if (isMulti) {
    return (
      <div className="flex flex-col gap-1.5 mt-1" onClick={(e) => e.preventDefault()}>
        {options.map((opt) => (
          <div
            key={opt.id}
            className="flex items-center justify-between gap-2 text-xs"
          >
            <span className="truncate text-base-content/90 min-w-0">
              {optionLabel(opt)}
            </span>
            <ParticipationTrafficLight
              value={(choices[opt.id] || "pending") as "yes" | "maybe" | "no" | "pending"}
              onChange={async (next) => {
                const val = next === "pending" ? "no" : next;
                const nextChoices = { ...choices, [opt.id]: val };
                setChoices(nextChoices);
                const fullChoices: Record<number, string> = {};
                options.forEach((o) => {
                  fullChoices[o.id] = nextChoices[o.id] ?? "no";
                });
                await submit({ choices: fullChoices });
              }}
              allowMaybe={isDate}
              disabled={submitting || disabled}
            />
          </div>
        ))}
      </div>
    );
  }

  return (
    <div
      className="flex flex-col gap-1.5 mt-1"
      onClick={(e) => e.preventDefault()}
      role="radiogroup"
    >
      <label className="label-text flex cursor-pointer items-center gap-2 text-xs">
        <input
          type="radio"
          name={`vote-dash-${voteId}`}
          className="radio radio-primary radio-sm"
          checked={singleChoice === null}
          disabled={submitting || disabled}
          onChange={async () => {
            if (singleChoice === null) return;
            setSingleChoice(null);
            await submit({ uservote: null });
          }}
        />
        <span className="truncate text-base-content/90">
          {t("js.votes.noVote") !== "js.votes.noVote" ? t("js.votes.noVote") : "No selection"}
        </span>
      </label>
      {options.map((opt) => (
        <label
          key={opt.id}
          className="label-text flex cursor-pointer items-center gap-2 text-xs"
        >
          <input
            type="radio"
            name={`vote-dash-${voteId}`}
            className="radio radio-primary radio-sm"
            checked={singleChoice === opt.id}
            disabled={submitting || disabled}
            onChange={async () => {
              if (singleChoice === opt.id) return;
              setSingleChoice(opt.id);
              await submit({ uservote: opt.id });
            }}
          />
          <span className="truncate text-base-content/90 min-w-0">{optionLabel(opt)}</span>
        </label>
      ))}
    </div>
  );
}
