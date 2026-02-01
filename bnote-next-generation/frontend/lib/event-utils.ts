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

export type EventDisplayType = EntityEventDisplayType;

const OTYPE_MAP: Record<string, EventDisplayType> = {
  R: "rehearsal",
  C: "performance",
  A: "meeting",
  T: "meeting",
  V: "meeting",
};

export function mapOtypeToEventType(otype: string): EventDisplayType {
  return OTYPE_MAP[otype] ?? "meeting";
}

export function formatEventDate(dateStr: string | undefined, locale: string): string {
  if (!dateStr) return "TBA";
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString(locale === "de" ? "de-DE" : "en-US", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  } catch {
    return "TBA";
  }
}

export function formatEventTime(dateStr: string | undefined, locale: string): string {
  if (!dateStr) return "TBA";
  try {
    const d = new Date(dateStr);
    return d.toLocaleTimeString(locale === "de" ? "de-DE" : "en-US", {
      hour: "numeric",
      minute: "2-digit",
    });
  } catch {
    return "TBA";
  }
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
