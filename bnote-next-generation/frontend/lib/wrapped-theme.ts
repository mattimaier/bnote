import { type CSSProperties } from "react";
import { getColorForBnoteType } from "@/lib/entity-config";

function normalizeYear(input: number | null | undefined): number {
  const raw = Number(input);
  if (!Number.isFinite(raw)) return new Date().getFullYear();
  const parsed = Math.trunc(raw);
  return Math.max(0, parsed);
}

/**
 * Year-driven palette generation with a long cycle (360 years),
 * avoiding short repeating buckets like `year % 7`.
 */
function getYearPalette(year: number) {
  const rehearsalHue = (year * 137 + 17) % 360;
  const concertHue = (year * 199 + 73) % 360;
  const rehearsalStrength = 62 + ((year * 17) % 17); // 62..78
  const concertStrength = 60 + ((year * 29) % 19); // 60..78
  const rehearsalL = (0.74 + (((year * 31) % 9) / 100)).toFixed(2); // 0.74..0.82
  const concertL = (0.73 + (((year * 37) % 9) / 100)).toFixed(2); // 0.73..0.81
  const rehearsalC = (0.14 + (((year * 41) % 7) / 100)).toFixed(2); // 0.14..0.20
  const concertC = (0.13 + (((year * 43) % 8) / 100)).toFixed(2); // 0.13..0.20

  return {
    rehearsalAnchor: `oklch(${rehearsalL} ${rehearsalC} ${rehearsalHue})`,
    concertAnchor: `oklch(${concertL} ${concertC} ${concertHue})`,
    rehearsalStrength,
    concertStrength,
  };
}

export function getWrappedThemeStyle(year: number | null | undefined): CSSProperties {
  const rehearsalBase = getColorForBnoteType("rehearsal") ?? "#3399FF";
  const concertBase = getColorForBnoteType("concert") ?? "oklch(0.68 0.20 80)";
  const normalizedYear = normalizeYear(year);
  const selected = getYearPalette(normalizedYear);

  return {
    "--wrapped-rehearsal-base": rehearsalBase,
    "--wrapped-concert-base": concertBase,
    "--wrapped-rehearsal": `color-mix(in oklch, var(--wrapped-rehearsal-base) ${selected.rehearsalStrength}%, ${selected.rehearsalAnchor})`,
    "--wrapped-concert": `color-mix(in oklch, var(--wrapped-concert-base) ${selected.concertStrength}%, ${selected.concertAnchor})`,
    "--wrapped-aurora-a": `color-mix(in oklch, ${selected.rehearsalAnchor} 72%, white)`,
    "--wrapped-aurora-b": `color-mix(in oklch, ${selected.concertAnchor} 68%, oklch(0.78 0.08 28))`,
  } as CSSProperties;
}
