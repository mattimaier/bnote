import { type CSSProperties } from "react";
import { getColorForBnoteType } from "@/lib/entity-config";

interface YearPaletteMix {
  rehearsalAnchor: string;
  concertAnchor: string;
  rehearsalStrength: number;
  concertStrength: number;
}

const YEAR_MIXES: YearPaletteMix[] = [
  {
    rehearsalAnchor: "oklch(0.77 0.19 250)",
    concertAnchor: "oklch(0.72 0.20 28)",
    rehearsalStrength: 70,
    concertStrength: 66,
  },
  {
    rehearsalAnchor: "oklch(0.78 0.17 238)",
    concertAnchor: "oklch(0.80 0.16 78)",
    rehearsalStrength: 72,
    concertStrength: 70,
  },
  {
    rehearsalAnchor: "oklch(0.76 0.18 268)",
    concertAnchor: "oklch(0.79 0.15 34)",
    rehearsalStrength: 68,
    concertStrength: 66,
  },
  {
    rehearsalAnchor: "oklch(0.80 0.16 196)",
    concertAnchor: "oklch(0.77 0.15 122)",
    rehearsalStrength: 66,
    concertStrength: 63,
  },
  {
    rehearsalAnchor: "oklch(0.78 0.15 224)",
    concertAnchor: "oklch(0.81 0.16 20)",
    rehearsalStrength: 64,
    concertStrength: 67,
  },
  {
    rehearsalAnchor: "oklch(0.79 0.17 288)",
    concertAnchor: "oklch(0.78 0.15 96)",
    rehearsalStrength: 65,
    concertStrength: 62,
  },
  {
    rehearsalAnchor: "oklch(0.77 0.18 210)",
    concertAnchor: "oklch(0.80 0.15 58)",
    rehearsalStrength: 69,
    concertStrength: 68,
  },
];

export function getWrappedThemeStyle(year: number | null | undefined): CSSProperties {
  const rehearsalBase = getColorForBnoteType("rehearsal") ?? "#3399FF";
  const concertBase = getColorForBnoteType("concert") ?? "oklch(0.68 0.20 80)";
  const idx = Math.abs(Number(year ?? 0)) % YEAR_MIXES.length;
  const selected = YEAR_MIXES[idx];
  const useClassicRedBlue = idx === 0;

  return {
    "--wrapped-rehearsal-base": rehearsalBase,
    "--wrapped-concert-base": concertBase,
    "--wrapped-rehearsal": `color-mix(in oklch, var(--wrapped-rehearsal-base) ${selected.rehearsalStrength}%, ${selected.rehearsalAnchor})`,
    "--wrapped-concert": `color-mix(in oklch, var(--wrapped-concert-base) ${selected.concertStrength}%, ${selected.concertAnchor})`,
    "--wrapped-aurora-a": useClassicRedBlue
      ? "oklch(0.72 0.18 250)"
      : `color-mix(in oklch, ${selected.rehearsalAnchor} 72%, white)`,
    "--wrapped-aurora-b": useClassicRedBlue
      ? "oklch(0.68 0.19 24)"
      : `color-mix(in oklch, ${selected.concertAnchor} 68%, oklch(0.78 0.08 28))`,
  } as CSSProperties;
}
