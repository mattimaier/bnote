/**
 * BNote Next Generation - Entity types for debug views (from plan §3)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { isEventEntityType } from "@/lib/entities/paths";

/** All entity types that have debug views (event types get full mock; others get placeholder). */
export const ALL_DEBUG_ENTITY_TYPES = [
  "rehearsal",
  "concert",
  "contact",
  "location",
  "user",
  "song",
  "repertoire",
  "task",
  "meeting",
  "appointment",
  "equipment",
  "tour",
] as const;

export type DebugEntityType = (typeof ALL_DEBUG_ENTITY_TYPES)[number];

export function isDebugEntityType(type: string): type is DebugEntityType {
  return ALL_DEBUG_ENTITY_TYPES.includes(type as DebugEntityType);
}

export function hasFullMockView(type: string): boolean {
  return isEventEntityType(type);
}

export function getEntityTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    rehearsal: "Rehearsal",
    concert: "Concert",
    contact: "Contact",
    location: "Location",
    user: "User",
    song: "Song",
    repertoire: "Repertoire",
    task: "Task",
    meeting: "Meeting",
    appointment: "Appointment",
    equipment: "Equipment",
    tour: "Tour",
  };
  return labels[type] ?? type;
}
