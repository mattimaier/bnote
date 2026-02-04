/**
 * BNote Next Generation - Entity view permissions
 * Rehearsals/concerts: read is not gated by module; other entities use getModules.
 *
 * Copyright (C) 2026 BNote Contributors
 */

/** Backend module name per entity type (for permission check). */
const ENTITY_TYPE_TO_MODULE: Record<string, string> = {
  contact: "Kontakte",
  contacts: "Kontakte",
  user: "User",
  users: "User",
  location: "Locations",
  locations: "Locations",
  task: "Aufgaben",
  tasks: "Aufgaben",
  repertoire: "Repertoire",
  song: "Repertoire",
  equipment: "Equipment",
  outfit: "Outfits",
  outfits: "Outfits",
  vote: "Abstimmung",
  votes: "Abstimmung",
};

/** Entity types that are not gated by module for view (read is allowed without module). */
const VIEW_ALWAYS_ALLOWED = new Set(["rehearsal", "concert"]);

export interface ModuleEntry {
  id: number;
  name: string;
  route?: string;
  icon?: string;
  i18n?: string;
}

/**
 * Whether the user can view the given entity type (for cross-entity links).
 * Rehearsal/concert: always true. Others: true iff user has that module (from getModules).
 */
export function canViewEntityType(
  entityType: string,
  modules: ModuleEntry[] | null
): boolean {
  const key = entityType?.toLowerCase?.() ?? "";
  if (VIEW_ALWAYS_ALLOWED.has(key)) return true;
  const moduleName = ENTITY_TYPE_TO_MODULE[key];
  if (!moduleName) return false;
  if (!modules || modules.length === 0) return false;
  return modules.some((m) => (m.name ?? "") === moduleName);
}
