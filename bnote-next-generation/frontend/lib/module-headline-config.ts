import { getEntityConfig } from "@/lib/entity-config";

export interface ModuleHeadlineConfig {
  icon: string;
  color: string;
}

const MODULE_HEADLINE_FALLBACKS: Record<string, ModuleHeadlineConfig> = {
  calendar: { icon: "calendar-days", color: "#0EA5E9" },
  news: { icon: "newspaper", color: "#F59E0B" },
  share: { icon: "share-2", color: "#06B6D4" },
  email: { icon: "mail", color: "#8B5CF6" },
  settings: { icon: "settings", color: "#6B7280" },
  configuration: { icon: "key", color: "#9A3412" },
};

export function getModuleHeadlineConfig(moduleKey?: string | null): ModuleHeadlineConfig | null {
  const key = String(moduleKey ?? "")
    .toLowerCase()
    .replace(/^\/+|\/+$/g, "");
  if (!key) return null;
  if (key === "dashboard") return null;
  const entity = getEntityConfig(key);
  if (entity) {
    return { icon: entity.icon, color: entity.color };
  }
  return MODULE_HEADLINE_FALLBACKS[key] ?? null;
}
