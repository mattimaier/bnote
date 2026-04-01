/**
 * BNote Next Generation - Human-readable duration formatting helpers.
 */

export function formatHoursForDisplay(
  hours: number,
  t: (key: string, params?: string[]) => string,
  options?: { locale?: string }
): string {
  if (!Number.isFinite(hours)) return "—";

  const locale = options?.locale;
  const absHours = Math.abs(hours);
  const sign = hours < 0 ? "-" : "";
  const dayKey = "js.common.unit.dayShort";
  const hourKey = "js.common.unit.hourShort";
  const dayUnit = t(dayKey) === dayKey ? "d" : t(dayKey);
  const hourUnit = t(hourKey) === hourKey ? "h" : t(hourKey);
  const formatNumber = (value: number, maxFractionDigits: number) =>
    new Intl.NumberFormat(locale || undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: maxFractionDigits,
    }).format(value);

  if (absHours >= 24) {
    const days = absHours / 24;
    return `${sign}${formatNumber(days, 1)} ${dayUnit}`;
  }

  return `${sign}${formatNumber(absHours, 1)} ${hourUnit}`;
}
