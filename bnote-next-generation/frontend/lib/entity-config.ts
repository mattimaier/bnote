/**
 * BNote Next Generation - Entity icon/color configuration
 * Single source of truth for entity types (rehearsal, concert, task, location, etc.).
 * Use consistently for badges, bubbles, and icons (entity-config.json).
 *
 * Copyright (C) 2026 BNote Contributors
 */

import entityConfigData from "@/config/entity-config.json";
import mailDesignTokens from "@/mail-design-tokens.json";

/** Canonical entity keys; module routes (e.g. "locations", "users") resolve to these via getEntityConfig. */
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
  | "outfit"
  | "vote";

export type EventDisplayType = "rehearsal" | "performance" | "meeting" | "vote" | "task" | "reservation" | "appointment";

interface EntityEntry {
  color: string;
  icon: string;
}

interface StatusEntry {
  color: string;
}

interface EntityConfigShape {
  features?: { showQuickActions?: boolean };
  entities: Record<string, EntityEntry>;
  statuses?: Record<string, StatusEntry>;
}

const config = entityConfigData as EntityConfigShape;

/** Whether quick actions (dashboard and detail pages) are shown. */
export function isQuickActionsEnabled(): boolean {
  return config?.features?.showQuickActions === true;
}
const entities = config?.entities ?? {};
const statuses = config?.statuses ?? {};

/** Module route or plural form → canonical entity key (same color/icon for module and entity). */
const ROUTE_OR_PLURAL_TO_CANONICAL: Record<string, string> = {
  users: "user",
  contacts: "contact",
  locations: "location",
  tasks: "task",
  outfits: "outfit",
  votes: "vote",
  rehearsals: "rehearsal",
  concerts: "concert",
  songs: "song",
  reservations: "reservation",
};

export function getEntityConfig(entityType: string): EntityEntry | null {
  const key = (entityType?.toLowerCase?.() ?? "").replace(/^\/+|\/+$/g, "");
  return entities[key] ?? entities[ROUTE_OR_PLURAL_TO_CANONICAL[key]] ?? null;
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
  vote: "vote",
  task: "task",
  reservation: "reservation",
  appointment: "appointment",
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
  if (color.includes("#A855F7") || color.includes("#a855f7"))
    return { dotClass: "bg-[#a855f7]", badgeClass: "event-badge event-badge-vote" };
  if (color.includes("#25A65A"))
    return { dotClass: "bg-[#25A65A]", badgeClass: "event-badge event-badge-task" };
  if (color.includes("#F97316") || color.includes("#f97316"))
    return { dotClass: "bg-[#f97316]", badgeClass: "event-badge event-badge-reservation" };
  if (color.includes("#8b6914") || color.includes("oklch(0.58 0.22 20)"))
    return { dotClass: "bg-[#8b6914]", badgeClass: "event-badge event-badge-appointment" };
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
        : eventType === "vote"
          ? "js.sidebar.votes"
          : eventType === "task"
            ? "js.sidebar.tasks"
            : eventType === "reservation"
              ? "js.calendar.reservationLabel"
              : eventType === "appointment"
                ? "js.calendar.appointmentLabel"
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

/**
 * Map bnoteType (from calendar API) to entity color.
 * Uses hex for rehearsal/concert to ensure consistent rendering in color-mix across browsers.
 */
export function getColorForBnoteType(bnoteType: string): string | null {
  const c = getEntityConfig(bnoteType);
  if (c?.color) {
    if (bnoteType === "rehearsal") return "#3399FF";
    if (bnoteType === "concert") return "#E8A84D";
    return c.color;
  }
  if (bnoteType === "phase") return "#3D9970";
  return null;
}

/** Map bnoteType (from calendar API) to event-badge CSS class */
export function getBadgeClassForBnoteType(bnoteType: string): string {
  switch (bnoteType) {
    case "rehearsal":
      return "event-badge";
    case "concert":
      return "event-badge accent";
    case "vote":
      return "event-badge event-badge-vote";
    case "task":
      return "event-badge event-badge-task";
    case "phase":
    case "meeting":
      return "event-badge chart-3";
    case "contact":
      return "event-badge event-badge-birthday";
    case "reservation":
      return "event-badge event-badge-reservation";
    case "appointment":
      return "event-badge event-badge-appointment";
    default:
      return "event-badge chart-3";
  }
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
  equipment: "equipment",
  outfits: "outfit",
  songs: "song",
  votes: "vote",
  reservations: "reservation",
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

export interface EscalationWarningUiConfig {
  severity: "critical" | "soon";
  iconName: string;
  iconClassName: string;
  badgeStyle: EntityPillStyle;
  cardStyle: EntityPillStyle;
  textColor: string;
}

export function getEscalationWarningUiConfig(severityRaw: string): EscalationWarningUiConfig {
  const severity = severityRaw === "critical" ? "critical" : "soon";
  const color =
    severity === "critical"
      ? mailDesignTokens.participationDestructive
      : mailDesignTokens.participationWarning;
  const bg =
    severity === "critical"
      ? mailDesignTokens.participationDestructiveBgMuted
      : mailDesignTokens.participationWarningBgMuted;
  const border =
    severity === "critical"
      ? mailDesignTokens.participationDestructiveBorderMuted
      : mailDesignTokens.participationWarningBorderMuted;
  return {
    severity,
    iconName: "alert-triangle",
    iconClassName: "text-white",
    badgeStyle: {
      borderColor: color,
      backgroundColor: color,
      color: "#ffffff",
    },
    cardStyle: {
      borderColor: border,
      backgroundColor: bg,
      color: severity === "critical" ? "#6f4d54" : "#6b6244",
    },
    textColor: severity === "critical" ? "#6f4d54" : "#6b6244",
  };
}
