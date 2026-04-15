/**
 * BNote Next Generation - Entity path helpers
 * Use for all entity view/edit links so path-based routes stay consistent.
 *
 * Copyright (C) 2026 BNote Contributors
 */

export type EventEntityType = "rehearsal" | "concert";

const EVENT_TYPES: EventEntityType[] = ["rehearsal", "concert"];

export function isEventEntityType(type: string): type is EventEntityType {
  return EVENT_TYPES.includes(type as EventEntityType);
}

/**
 * Build path for entity view or edit (query-based; scales with static export).
 * Do not add basePath (Next.js does that).
 */
export function getEntityPath(type: string, id: string | number, mode?: "view" | "edit"): string {
  const idStr = String(id);
  const params = new URLSearchParams({ type, id: idStr });
  if (mode === "edit") params.set("edit", "1");
  return `/entity?${params.toString()}`;
}

/**
 * Redirect targets for non-event entity types (contact, user, location, etc.).
 * Used when user opens /entity/contact/5 etc. so "open entity" still works.
 */
export interface EntityRedirectTarget {
  pathname: string;
  query?: Record<string, string>;
}

const NON_EVENT_REDIRECT: Record<string, EntityRedirectTarget> = {
  contact: { pathname: "/entity", query: { type: "contact", id: "__id__" } },
  contacts: { pathname: "/entity", query: { type: "contact", id: "__id__" } },
  user: { pathname: "/entity", query: { type: "user", id: "__id__" } },
  users: { pathname: "/entity", query: { type: "user", id: "__id__" } },
  location: { pathname: "/locations" },
  locations: { pathname: "/locations" },
  task: { pathname: "/tasks", query: { id: "__id__" } },
  tasks: { pathname: "/tasks", query: { id: "__id__" } },
  group_task: { pathname: "/tasks" },
  repertoire: { pathname: "/repertoire", query: { id: "__id__" } },
  song: { pathname: "/repertoire", query: { id: "__id__" } },
  meeting: { pathname: "/dashboard" },
  appointment: { pathname: "/entity", query: { type: "appointment", id: "__id__" } },
  appointments: { pathname: "/entity", query: { type: "appointment", id: "__id__" } },
  reservation: { pathname: "/entity", query: { type: "reservation", id: "__id__" } },
  reservations: { pathname: "/entity", query: { type: "reservation", id: "__id__" } },
  equipment: { pathname: "/equipment", query: { id: "__id__" } },
  outfit: { pathname: "/outfits", query: { id: "__id__" } },
  outfits: { pathname: "/outfits", query: { id: "__id__" } },
  vote: { pathname: "/votes", query: { id: "__id__" } },
  votes: { pathname: "/votes", query: { id: "__id__" } },
  tour: { pathname: "/dashboard" },
};

/**
 * Get redirect target for a non-event entity type. Replace __id__ in pathname and query values with actual id.
 */
export function getRedirectForEntityType(type: string, id: string | number): EntityRedirectTarget | null {
  const key = type?.toLowerCase?.() ?? "";
  const target = NON_EVENT_REDIRECT[key];
  if (!target) return null;
  const idStr = String(id);
  const pathname = target.pathname.includes("__id__") ? target.pathname.replace("__id__", idStr) : target.pathname;
  const query = target.query
    ? Object.fromEntries(Object.entries(target.query).map(([k, v]) => [k, v === "__id__" ? idStr : v]))
    : undefined;
  return { pathname, query };
}

/** Build full path string for redirect (pathname + query). */
export function getRedirectPath(type: string, id: string | number): string {
  const target = getRedirectForEntityType(type, id);
  if (!target) return "/dashboard";
  const qs = target.query ? "?" + new URLSearchParams(target.query).toString() : "";
  return target.pathname + qs;
}
