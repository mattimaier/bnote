import { getEntityConfig } from "@/lib/entity-config";

export interface ModuleHeadlineConfig {
  icon: string;
  color: string;
}

export function getModuleHeadlineConfig(moduleKey?: string | null): ModuleHeadlineConfig | null {
  const key = String(moduleKey ?? "")
    .toLowerCase()
    .replace(/^\/+|\/+$/g, "");
  if (!key) return null;
  if (key === "dashboard") return null;
  const entity = getEntityConfig(key);
  return entity ? { icon: entity.icon, color: entity.color } : null;
}
