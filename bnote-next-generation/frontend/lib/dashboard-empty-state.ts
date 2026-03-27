/**
 * Dashboard empty-state helper for response section.
 * Keeps variant selection logic out of UI components.
 */

export interface DashboardEmptyStateOptions {
  variantCount: number;
  t: (key: string, params?: string[]) => string;
}

export function pickDashboardEmptyResponseVariantKey(variantCount: number): string {
  const count = Math.max(1, variantCount);
  const idx = Math.floor(Math.random() * count) + 1;
  return `js.dashboard.emptyResponse.fun.${idx}`;
}

export function resolveDashboardEmptyResponseMessage(
  key: string,
  t: (key: string, params?: string[]) => string
): string {
  const translated = t(key);
  if (translated && translated !== key) return translated;

  const fallback = t("js.dashboard.noEventsNeedingResponse");
  if (fallback && fallback !== "js.dashboard.noEventsNeedingResponse") return fallback;
  return "No events need your response at this time.";
}

export function getDashboardEmptyResponseMessage({
  variantCount,
  t,
}: DashboardEmptyStateOptions): string {
  return resolveDashboardEmptyResponseMessage(
    pickDashboardEmptyResponseVariantKey(variantCount),
    t
  );
}
