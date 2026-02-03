/**
 * BNote Next Generation - Entity icon/color configuration
 * Single source of truth for entity types (rehearsal, concert, task, location, etc.).
 * Use consistently for badges, bubbles, and icons (entity-config.json).
 *
 * Copyright (C) 2026 BNote Contributors
 */

import entityConfigData from "@/config/entity-config.json";

export type EntityType =
  | "rehearsal"
  | "concert"
  | "contact"
  | "location"
  | "user"
  | "song"
  | "repertoire"
  | "task"
  | "meeting"
  | "appointment"
  | "equipment"
  | "tour"
  | "users"
  | "contacts"
  | "tasks"
  | "locations";

export type EventDisplayType = "rehearsal" | "performance" | "meeting";

interface EntityEntry {
  color: string;
  icon: string;
}

interface StatusEntry {
  color: string;
}

interface EntityConfigShape {
  entities: Record<string, EntityEntry>;
  statuses?: Record<string, StatusEntry>;
}

const config = entityConfigData as EntityConfigShape;
const entities = config?.entities ?? {};
const statuses = config?.statuses ?? {};

export function getEntityConfig(entityType: string): EntityEntry | null {
  const key = entityType?.toLowerCase?.() ?? "";
  return entities[key] ?? null;
}

export function getColor(entityType: string): string | null {
  const c = getEntityConfig(entityType);
  return c?.color ?? null;
}

export function getIconName(entityType: string): string {
  const c = getEntityConfig(entityType);
  return c?.icon ?? "circle";
}

/** Map event type to entity type for config lookup */
const EVENT_TO_ENTITY: Record<EventDisplayType, string> = {
  rehearsal: "rehearsal",
  performance: "concert",
  meeting: "meeting",
};

/**
 * CSS class names for event types – aligned with globals.css (.event-badge, .filter-bubble-*).
 * Primary = rehearsal, accent = concert, chart-3 = meeting.
 */
function colorToEventClasses(color: string | null): { dotClass: string; badgeClass: string } {
  if (!color) return { dotClass: "bg-[var(--chart-3)]", badgeClass: "event-badge chart-3" };
  if (color.includes("#3399FF") || color.toLowerCase().includes("primary"))
    return { dotClass: "bg-[var(--primary)]", badgeClass: "event-badge" };
  if (color.includes("oklch(0.68 0.20 80)") || color.toLowerCase().includes("accent"))
    return { dotClass: "bg-[var(--accent)]", badgeClass: "event-badge accent" };
  if (color.includes("oklch(0.62 0.18 150)") || color.includes("chart-3"))
    return { dotClass: "bg-[var(--chart-3)]", badgeClass: "event-badge chart-3" };
  return { dotClass: "bg-[var(--chart-3)]", badgeClass: "event-badge chart-3" };
}

export interface EventTypeConfigResult {
  icon: string;
  label: string;
  labelKey: string;
  dotClass: string;
  badgeClass: string;
  color: string | null;
}

export function getEventTypeConfig(
  eventType: EventDisplayType,
  t: (k: string) => string
): EventTypeConfigResult {
  const entityKey = EVENT_TO_ENTITY[eventType] ?? "meeting";
  const entity = getEntityConfig(entityKey);
  const labelKey =
    eventType === "rehearsal"
      ? "js.event.rehearsal"
      : eventType === "performance"
        ? "js.event.performance"
        : "js.event.meeting";
  const label = t(labelKey);
  if (!entity) {
    const fallback = colorToEventClasses(null);
    return {
      icon: "users",
      label,
      labelKey,
      ...fallback,
      color: null,
    };
  }
  const { dotClass, badgeClass } = colorToEventClasses(entity.color);
  return {
    icon: entity.icon,
    label,
    labelKey,
    dotClass,
    badgeClass,
    color: entity.color,
  };
}

/** Map search result category key to entity type for config */
const SEARCH_CATEGORY_TO_ENTITY: Record<string, string> = {
  rehearsals: "rehearsal",
  concerts: "concert",
  users: "user",
  contacts: "contact",
  tasks: "task",
  repertoire: "repertoire",
  locations: "location",
};

export function getEntityTypeForSearchCategory(categoryKey: string): string {
  return SEARCH_CATEGORY_TO_ENTITY[categoryKey] ?? categoryKey;
}

function hexToRgb(hex: string): string {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result
    ? `rgb(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)})`
    : hex;
}

/**
 * Inline style for a pill/badge using the entity color (light bg, saturated border).
 * Use for task, location, and other entity pills when not using .event-badge / .filter-bubble classes.
 */
export interface EntityPillStyle {
  borderColor: string;
  backgroundColor: string;
  color: string;
}

export function getPillStyle(color: string | null): EntityPillStyle {
  if (!color)
    return {
      borderColor: "var(--border)",
      backgroundColor: "var(--muted)",
      color: "var(--muted-foreground)",
    };
  const isOklch = color.startsWith("oklch");
  const mix = isOklch ? color : hexToRgb(color);
  return {
    borderColor: `color-mix(in oklab, ${mix} 20%, transparent)`,
    backgroundColor: `color-mix(in oklab, ${mix} 10%, transparent)`,
    color: color,
  };
}

export interface EntityDotStyle {
  background: string;
  color: string;
}

export function getDotStyle(color: string | null): EntityDotStyle {
  if (!color) return { background: "var(--chart-3)", color: "white" };
  return { background: color, color: "white" };
}

export function getStatusPillStyle(status: string): EntityPillStyle {
  const key = status?.toLowerCase?.() ?? "";
  const color = statuses[key]?.color ?? null;
  return getPillStyle(color);
}
