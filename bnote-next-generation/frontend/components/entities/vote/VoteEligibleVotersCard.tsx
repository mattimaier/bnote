/**
 * BNote Next Generation - Eligible voters card (Stimmberechtigte)
 * Shows who can vote, who has voted, who hasn't — reuses ParticipantOverview UI
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { ParticipantOverview, type InstrumentGroup } from "@/components/ParticipantOverview";
import { DetailCard } from "@/components/DetailCard";
import { Spinner } from "@/components/Spinner";
import { votesApi } from "@/lib/votes-api";
import { getEntityPath } from "@/lib/entities/paths";

interface VoteEligibleVotersCardProps {
  voteId: number;
}

export function VoteEligibleVotersCard({ voteId }: VoteEligibleVotersCardProps) {
  const { t } = useI18n();
  const [participantsByInstrument, setParticipantsByInstrument] = useState<InstrumentGroup[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    votesApi
      .getVoters(voteId)
      .then((data) => {
        setParticipantsByInstrument(
          data.map((g) => ({
            instrument: g.instrument,
            participants: g.participants.map((p) => ({
              id: p.id,
              userId: p.userId,
              name: p.name,
              email: p.email ?? null,
              participate: p.participate,
              reason: p.reason ?? null,
            })),
            stats: g.stats,
          }))
        );
      })
      .catch((err) =>
        setError(
          err instanceof Error
            ? err.message
            : t("js.votes.votersLoadFailed") !== "js.votes.votersLoadFailed"
              ? t("js.votes.votersLoadFailed")
              : "Failed to load"
        )
      )
      .finally(() => setLoading(false));
  }, [voteId]);

  const title =
    t("js.votes.voters") !== "js.votes.voters" ? t("js.votes.voters") : "Eligible voters";

  if (loading) {
    return (
      <DetailCard>
        <h2 className="text-base font-semibold text-base-content/60 mb-3">{title}</h2>
        <div className="flex justify-center py-8">
          <Spinner />
        </div>
      </DetailCard>
    );
  }

  if (error) {
    return (
      <DetailCard>
        <h2 className="text-base font-semibold text-base-content/60 mb-3">{title}</h2>
        <p className="text-sm text-error">{error}</p>
      </DetailCard>
    );
  }

  return (
    <DetailCard>
      <h2 className="text-base font-semibold text-base-content/60 mb-3">{title}</h2>
      <ParticipantOverview
        participantsByInstrument={participantsByInstrument}
        getEntityHref={(entityType, entityId) =>
          entityType === "contact" ? getEntityPath("contact", entityId) : null
        }
      />
    </DetailCard>
  );
}
