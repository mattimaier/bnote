/**
 * BNote Next Generation - Participant list editor with traffic light
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useState } from "react";
import { Trash2 } from "@/components/icons";
import type { EditableParticipant } from "@/lib/entities/event/types";
import { ParticipationTrafficLight } from "./ParticipationTrafficLight";

export interface ParticipantEditorProps {
  participants: EditableParticipant[];
  onRemoveContact: (contactId: number) => void;
  onChange: (next: EditableParticipant[]) => void;
  t: (key: string) => string;
}

export function ParticipantEditor({ participants, onRemoveContact, onChange, t }: ParticipantEditorProps) {
  const [query, setQuery] = useState("");
  const filtered = participants.filter((p) => {
    const haystack = `${p.name} ${p.instrument}`.toLowerCase();
    return haystack.includes(query.toLowerCase());
  });

  return (
    <div className="space-y-4">
      <input
        type="search"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
        className="input input-sm w-full text-base-content"
      />
      <div className="space-y-3 text-sm">
        {filtered.map((participant) => (
          <div
            key={`${participant.userId}-${participant.contactId}`}
            className="flex flex-col gap-3 rounded-field border border-base-300 px-3 py-2 md:flex-row md:items-center md:justify-between"
          >
            <div>
              <div className="font-medium">{participant.name}</div>
              <div className="text-xs text-base-content/60">{participant.instrument}</div>
            </div>
            <div className="flex items-center gap-3">
              <ParticipationTrafficLight
                value={participant.participate}
                onChange={(next) =>
                  onChange(
                    participants.map((entry) =>
                      entry.userId === participant.userId && entry.contactId === participant.contactId
                        ? { ...entry, participate: next }
                        : entry
                    )
                  )
                }
              />
              <button
                type="button"
                onClick={() => onRemoveContact(participant.contactId)}
                className="inline-flex items-center justify-center rounded-field border border-base-300 px-2 py-2 text-sm text-base-content"
                aria-label={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          </div>
        ))}
        {participants.length === 0 && (
          <div className="text-sm text-base-content/60">
            {t("js.event.detail.noParticipants") !== "js.event.detail.noParticipants"
              ? t("js.event.detail.noParticipants")
              : "No participants available."}
          </div>
        )}
        {participants.length > 0 && filtered.length === 0 && (
          <div className="text-sm text-base-content/60">
            {t("js.common.noResults") !== "js.common.noResults" ? t("js.common.noResults") : "No results"}
          </div>
        )}
      </div>
    </div>
  );
}
