/**
 * Dashboard empty-state helper for response section.
 * Keeps variant selection logic out of UI components.
 */

export interface DashboardEmptyStateOptions {
  userName?: string;
  variantCount: number;
  t: (key: string, params?: string[]) => string;
}

function hashString(value: string): number {
  let hash = 0;
  for (let i = 0; i < value.length; i++) {
    hash = (hash * 31 + value.charCodeAt(i)) >>> 0;
  }
  return hash;
}

export function getDashboardEmptyResponseMessage({
  userName,
  variantCount,
  t,
}: DashboardEmptyStateOptions): string {
  const count = Math.max(1, variantCount);
  const now = new Date();
  const dayToken = `${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()}`;
  const userToken = userName?.trim() || "user";
  const seed = hashString(`${dayToken}:${userToken}`);
  const idx = (seed % count) + 1;
  const key = `js.dashboard.emptyResponse.fun.${idx}`;
  const translated = t(key);
  if (translated && translated !== key) return translated;

  const fallback = t("js.dashboard.noEventsNeedingResponse");
  if (fallback && fallback !== "js.dashboard.noEventsNeedingResponse") return fallback;
  return "No events need your response at this time.";
}
