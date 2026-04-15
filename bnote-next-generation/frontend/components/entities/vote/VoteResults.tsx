/**
 * BNote Next Generation - Vote results display (progress bars, Modal)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { Modal } from "@/components/Modal";
import { ParticipationDiagram, type ParticipationStats } from "@/components/ParticipationDiagram";
import { PersonOptionRow } from "@/components/PersonOptionRow";
import { formatDateShortDisplay } from "@/lib/date-time";
import type { VoteOption } from "@/lib/votes-api";

/** Parse comma-separated voters string into display names (e.g. "John (Piano), Jane" → ["John (Piano)", "Jane"]) */
function parseVoters(voters: string): string[] {
  if (!voters?.trim()) return [];
  return voters
    .split(/,\s*/)
    .map((s) => s.trim())
    .filter(Boolean);
}

function parseVoterDisplay(raw: string): { name: string; instrument?: string } {
  const trimmed = raw.trim();
  if (!trimmed) return { name: "—" };

  const match = trimmed.match(/^(.*?)\s*\(([^)]+)\)\s*$/);
  if (!match) return { name: trimmed };

  const parsedName = match[1]?.trim() || trimmed;
  const parsedInstrument = match[2]?.trim() || undefined;
  return { name: parsedName, instrument: parsedInstrument };
}

/** Result row from API (standard format) */
interface ResultRow {
  id?: number;
  option?: string;
  votes?: number | string;
  voters?: string;
  Option?: string;
}

/** Parsed result item for display */
interface ParsedOption {
  optionId: number;
  label: string;
  votes: number;
  voters: string;
  choice?: "yes" | "no" | "maybe"; // for date-multi-maybe
}

interface VoteResultsProps {
  result: unknown;
  options: VoteOption[];
  isDate: boolean;
  isMulti: boolean;
  lang: string;
}

function parseResult(
  result: unknown,
  options: VoteOption[],
  isDate: boolean,
  isMulti: boolean,
  lang: string
): { items: ParsedOption[]; totalVotes: number; isDateMultiMaybe: boolean } {
  const optionLabel = (opt: VoteOption) => (opt.odate ? formatDateShortDisplay(opt.odate, lang) : (opt.name ?? ""));

  if (!result || !Array.isArray(result)) {
    return { items: [], totalVotes: 0, isDateMultiMaybe: false };
  }

  const rows = result as ResultRow[];
  if (rows.length === 0) {
    return { items: [], totalVotes: 0, isDateMultiMaybe: false };
  }

  // Detect date-multi-maybe: has rows with votes like "4 Ja", "2 Nein"
  const firstRow = rows[1] ?? rows[0];
  const votesVal = firstRow?.votes;
  const isDateMultiMaybe =
    isDate &&
    isMulti &&
    typeof votesVal === "string" &&
    (votesVal.includes("Ja") ||
      votesVal.includes("Yes") ||
      votesVal.includes("Oui") ||
      votesVal.includes("Nein") ||
      votesVal.includes("No") ||
      votesVal.includes("Vllt") ||
      votesVal.includes("Maybe"));

  if (isDateMultiMaybe) {
    // Group by option id: 3 rows per option (yes, no, maybe) - order is yes, no, maybe per option
    const byOption = new Map<number, { yes: ParsedOption; no: ParsedOption; maybe: ParsedOption }>();
    const optOrder: number[] = [];
    for (let i = 1; i < rows.length; i++) {
      const r = rows[i];
      const optId = Number(r.id ?? 0);
      const votesStr = String(r.votes ?? "0");
      const num = parseInt(votesStr.replace(/\D/g, ""), 10) || 0;
      const v = votesStr.toLowerCase();
      let choice: "yes" | "no" | "maybe" = "yes";
      if (v.includes("nein") || v.includes("no") || v.includes("non")) choice = "no";
      else if (v.includes("vllt") || v.includes("maybe") || v.includes("peut")) choice = "maybe";

      const opt = options.find((o) => o.id === optId);
      const label = opt ? optionLabel(opt) : String(r.option ?? r.Option ?? "");
      const parsed: ParsedOption = {
        optionId: optId,
        label,
        votes: num,
        voters: r.voters ?? "",
        choice,
      };

      if (!byOption.has(optId)) {
        optOrder.push(optId);
        byOption.set(optId, {
          yes: { ...parsed, choice: "yes", votes: 0 },
          no: { ...parsed, choice: "no", votes: 0 },
          maybe: { ...parsed, choice: "maybe", votes: 0 },
        });
      }
      const entry = byOption.get(optId)!;
      entry[choice] = parsed;
    }
    const items: ParsedOption[] = [];
    let totalVotes = 0;
    (optOrder.length > 0 ? optOrder : options.map((o) => o.id)).forEach((optId) => {
      const e = byOption.get(optId);
      if (!e) return;
      totalVotes += e.yes.votes + e.no.votes + e.maybe.votes;
      items.push(e.yes);
      items.push(e.no);
      items.push(e.maybe);
    });
    return { items, totalVotes, isDateMultiMaybe: true };
  }

  // Standard format: one row per option (only options with votes appear)
  const resultMap = new Map<number, { votes: number; voters: string }>();
  let totalVotes = 0;
  for (let i = 1; i < rows.length; i++) {
    const r = rows[i];
    const optId = Number(r.id ?? 0);
    const votes = typeof r.votes === "number" ? r.votes : parseInt(String(r.votes ?? 0).replace(/\D/g, ""), 10) || 0;
    resultMap.set(optId, { votes, voters: r.voters ?? "" });
    totalVotes += votes;
  }

  const items: ParsedOption[] = options.map((opt) => {
    const entry = resultMap.get(opt.id) ?? { votes: 0, voters: "" };
    return {
      optionId: opt.id,
      label: optionLabel(opt),
      votes: entry.votes,
      voters: entry.voters,
    };
  });

  return { items, totalVotes, isDateMultiMaybe: false };
}

function VotersTable({ voters, noVotesText }: { voters: string; noVotesText: string }) {
  const names = parseVoters(voters);
  if (names.length === 0) {
    return <p className="text-sm text-base-content/60">{noVotesText}</p>;
  }
  return (
    <div className="rounded-field border border-base-300 divide-y divide-base-300 overflow-hidden">
      {names.map((entry, i) => {
        const { name, instrument } = parseVoterDisplay(entry);
        return (
          <div key={`${entry}-${i}`} className="px-3 py-2">
            <PersonOptionRow name={name} instrument={instrument} avatarSize={24} compact />
          </div>
        );
      })}
    </div>
  );
}

function VoteResultRow({
  item,
  percent,
  votesSuffix,
  noVotesText,
}: {
  item: ParsedOption;
  percent: number;
  votesSuffix: string;
  noVotesText: string;
}) {
  const [modalOpen, setModalOpen] = useState(false);

  return (
    <div className="space-y-1">
      <div className="flex items-center justify-between gap-2">
        <span className="text-sm font-medium text-base-content">{item.label}</span>
        <button type="button" onClick={() => setModalOpen(true)} className="text-sm text-primary hover:underline">
          {item.votes} {votesSuffix} ({percent}%)
        </button>
      </div>
      <div className="progress h-2 w-full">
        <div className="progress-bar progress-primary" style={{ width: `${percent}%` }} />
      </div>
      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={`${item.label} — ${item.votes} ${votesSuffix}`}
      >
        <VotersTable voters={item.voters} noVotesText={noVotesText} />
      </Modal>
    </div>
  );
}

export function VoteResults({ result, options, isDate, isMulti, lang }: VoteResultsProps) {
  const { t } = useI18n();
  const [modalOpen, setModalOpen] = useState(false);
  const { items, totalVotes, isDateMultiMaybe } = parseResult(result, options, isDate, isMulti, lang);

  const resultsLabel = t("js.votes.results") !== "js.votes.results" ? t("js.votes.results") : "Results";
  const viewAllLabel = t("js.votes.voters") !== "js.votes.voters" ? t("js.votes.voters") : "View votes";
  const voteLabel = t("js.votes.vote") !== "js.votes.vote" ? t("js.votes.vote") : "vote";
  const votesLabel = t("js.votes.votes") !== "js.votes.votes" ? t("js.votes.votes") : "votes";
  const votesSuffix = totalVotes === 1 ? voteLabel : votesLabel;

  if (items.length === 0 && totalVotes === 0) {
    return (
      <div className="mt-4">
        <h2 className="text-sm font-semibold text-base-content/60">{resultsLabel}</h2>
        <p className="mt-2 text-sm text-base-content/60">
          {totalVotes === 0
            ? "0 " + votesSuffix
            : t("js.votes.noVotes") !== "js.votes.noVotes"
              ? t("js.votes.noVotes")
              : "No votes"}
        </p>
      </div>
    );
  }

  if (isDateMultiMaybe) {
    const yesLabel = t("js.votes.yes") !== "js.votes.yes" ? t("js.votes.yes") : "Yes";
    const noLabel = t("js.votes.no") !== "js.votes.no" ? t("js.votes.no") : "No";
    const maybeLabel = t("js.votes.maybe") !== "js.votes.maybe" ? t("js.votes.maybe") : "Maybe";

    const optionRows = new Map<number, { label: string; yes: ParsedOption; no: ParsedOption; maybe: ParsedOption }>();
    items.forEach((item) => {
      if (!optionRows.has(item.optionId)) {
        optionRows.set(item.optionId, {
          label: item.label,
          yes: item,
          no: item,
          maybe: item,
        });
      }
      const row = optionRows.get(item.optionId)!;
      if (item.choice === "yes") row.yes = item;
      else if (item.choice === "no") row.no = item;
      else row.maybe = item;
    });

    return (
      <div className="mt-4">
        <div className="flex items-center justify-between gap-2">
          <h2 className="text-sm font-semibold text-base-content/60">{resultsLabel}</h2>
          <button type="button" onClick={() => setModalOpen(true)} className="btn btn-soft btn-sm btn-primary text-xs">
            {viewAllLabel}
          </button>
        </div>
        <div className="mt-2 space-y-3">
          {Array.from(optionRows.entries()).map(([optId, row]) => {
            const stats: ParticipationStats = {
              yes: row.yes.votes,
              no: row.no.votes,
              maybe: row.maybe.votes,
              total: row.yes.votes + row.no.votes + row.maybe.votes,
            };
            return (
              <div key={optId} className="space-y-1">
                <span className="text-sm font-medium text-base-content">{row.label}</span>
                {(stats.total ?? 0) > 0 ? (
                  <ParticipationDiagram stats={stats} />
                ) : (
                  <div className="flex h-8 items-center rounded-lg border border-base-300 bg-base-200 px-3 text-xs text-base-content/60">
                    {t("js.votes.noVotes") !== "js.votes.noVotes" ? t("js.votes.noVotes") : "No votes"}
                  </div>
                )}
              </div>
            );
          })}
        </div>
        <p className="mt-2 text-xs text-base-content/60">
          Total: {totalVotes} {votesSuffix}
        </p>
        <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={resultsLabel}>
          <div className="space-y-4">
            {Array.from(optionRows.entries()).map(([optId, row]) => (
              <div key={optId} className="border-b border-base-300 pb-4 last:border-0">
                <p className="mb-2 font-medium text-base-content">{row.label}</p>
                {(() => {
                  const sections = [
                    row.yes.voters ? { key: "yes", label: yesLabel, voters: row.yes.voters } : null,
                    row.no.voters ? { key: "no", label: noLabel, voters: row.no.voters } : null,
                    row.maybe.voters ? { key: "maybe", label: maybeLabel, voters: row.maybe.voters } : null,
                  ].filter((section): section is { key: string; label: string; voters: string } => Boolean(section));

                  const gridClass =
                    sections.length <= 1
                      ? "grid gap-3 grid-cols-1"
                      : sections.length === 2
                        ? "grid gap-3 grid-cols-1 sm:grid-cols-2"
                        : "grid gap-3 grid-cols-1 sm:grid-cols-3";

                  return (
                    <div className={gridClass}>
                      {sections.map((section) => (
                        <div key={section.key}>
                          <p className="mb-1 text-xs font-medium text-base-content/60">{section.label}</p>
                          <VotersTable voters={section.voters} noVotesText="" />
                        </div>
                      ))}
                    </div>
                  );
                })()}
              </div>
            ))}
          </div>
        </Modal>
      </div>
    );
  }

  // Standard: progress bars per option
  const percent = (v: number) => (totalVotes > 0 ? Math.round((v / totalVotes) * 100) : 0);

  return (
    <div className="mt-4">
      <div className="flex items-center justify-between gap-2">
        <h2 className="text-sm font-semibold text-base-content/60">{resultsLabel}</h2>
        <button type="button" onClick={() => setModalOpen(true)} className="btn btn-soft btn-sm btn-primary text-xs">
          {viewAllLabel}
        </button>
      </div>
      <div className="mt-2 space-y-3">
        {items.map((item) => (
          <VoteResultRow
            key={item.optionId}
            item={item}
            percent={percent(item.votes)}
            votesSuffix={votesSuffix}
            noVotesText={t("js.votes.noVotes") !== "js.votes.noVotes" ? t("js.votes.noVotes") : "No votes"}
          />
        ))}
      </div>
      <p className="mt-2 text-xs text-base-content/60">
        Total: {totalVotes} {votesSuffix}
      </p>
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={resultsLabel}>
        <div className="space-y-4">
          {items.map((item) => (
            <div key={item.optionId} className="border-b border-base-300 pb-4 last:border-0">
              <p className="mb-2 font-medium text-base-content">
                {item.label} — {item.votes} {votesSuffix} ({percent(item.votes)}%)
              </p>
              <VotersTable
                voters={item.voters}
                noVotesText={t("js.votes.noVotes") !== "js.votes.noVotes" ? t("js.votes.noVotes") : "No votes"}
              />
            </div>
          ))}
        </div>
      </Modal>
    </div>
  );
}
