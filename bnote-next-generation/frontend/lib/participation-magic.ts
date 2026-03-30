/**
 * Public participation via magic link from event invite email (no session).
 */

import { api } from "./api";
import { getEntityPath } from "./entities/paths";

export type ApplyParticipationResult = {
  ok: boolean;
  status?: string;
  eventType?: string;
  eventId?: number;
  expiresAt?: string;
};

export type ParticipationTokenInfoResult = {
  ok: boolean;
  expiresAt?: string;
  reusable?: boolean;
};

/** Public: token still valid + expiry (ISO 8601), without changing participation. */
export async function getParticipationTokenInfo(token: string): Promise<ParticipationTokenInfoResult> {
  const normalized = token.trim().toLowerCase();
  return api.post<ParticipationTokenInfoResult>("auth", "getParticipationTokenInfo", { token: normalized });
}

export async function applyParticipationToken(
  token: string,
  status: "yes" | "maybe" | "no" | "undecided",
  reason?: string
): Promise<ApplyParticipationResult> {
  const normalized = token.trim().toLowerCase();
  const body: Record<string, unknown> = {
    token: normalized,
    status,
  };
  if (reason !== undefined && reason !== "") {
    body.reason = reason;
  }
  return api.post<ApplyParticipationResult>("auth", "applyParticipationToken", body);
}

export function parseParticipationChoice(raw: string | null): "yes" | "maybe" | "no" | null {
  const v = (raw ?? "").trim().toLowerCase();
  if (v === "yes" || v === "maybe" || v === "no") return v;
  return null;
}

/** App path to open rehearsal/concert entity (no basePath — Next.js Link adds it). */
export function participationEntityHref(eventType: string | undefined, eventId: number | undefined): string | null {
  if (eventId === undefined || eventId < 1 || !eventType) return null;
  const t = eventType.trim().toUpperCase();
  let type: string;
  if (t === "R") type = "rehearsal";
  else if (t === "C") type = "concert";
  else return null;
  return getEntityPath(type, eventId);
}
