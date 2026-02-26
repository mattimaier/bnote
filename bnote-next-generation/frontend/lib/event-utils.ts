/**
 * BNote Next Generation - Event type and formatting helpers
 * Event type config comes from entity-config (single source of truth).
 *
 * Copyright (C) 2026 BNote Contributors
 */

import {
  getEventTypeConfig as getEventTypeConfigFromEntity,
  type EventDisplayType as EntityEventDisplayType,
} from "./entity-config";
import { formatDateShort, formatTimeShort } from "@/lib/date-time";

export type EventDisplayType = EntityEventDisplayType;

const OTYPE_MAP: Record<string, EventDisplayType> = {
  R: "rehearsal",
  C: "performance",
  A: "meeting",
  T: "meeting",
  V: "vote",
};

export function mapOtypeToEventType(otype: string): EventDisplayType {
  return OTYPE_MAP[otype] ?? "meeting";
}

export function formatEventDate(dateStr: string | undefined, locale: string, fallback = "TBA"): string {
  const formatted = formatDateShort(dateStr, locale);
  return formatted ?? fallback;
}

export function formatEventTime(dateStr: string | undefined, locale: string, fallback = "TBA"): string {
  const formatted = formatTimeShort(dateStr, locale);
  return formatted ?? fallback;
}

export interface EventTypeConfig {
  labelKey: string;
  dotClass: string;
  badgeClass: string;
  icon: string;
  label: string;
}

/** Uses entity-config for icon/color; returns shape expected by EventCard etc. */
export function getEventTypeConfig(
  type: EventDisplayType,
  t: (k: string) => string
): EventTypeConfig {
  const c = getEventTypeConfigFromEntity(type, t);
  return {
    labelKey: c.labelKey,
    dotClass: c.dotClass,
    badgeClass: c.badgeClass,
    icon: c.icon,
    label: c.label,
  };
}
