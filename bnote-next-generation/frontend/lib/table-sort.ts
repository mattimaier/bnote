/**
 * Table sort utilities: comparators and helpers for sortable tables.
 * Date sorting parses ISO/date strings and compares by timestamp; null/undefined sort to end.
 */

export type SortDirection = "asc" | "desc";

/** Compare two numbers; null/undefined treated as NaN and sorted to end (after all numbers). */
export function compareNumber(a: number | null | undefined, b: number | null | undefined, dir: SortDirection): number {
  const na = a == null || Number.isNaN(Number(a)) ? NaN : Number(a);
  const nb = b == null || Number.isNaN(Number(b)) ? NaN : Number(b);
  const aMissing = Number.isNaN(na);
  const bMissing = Number.isNaN(nb);
  if (aMissing && bMissing) return 0;
  if (aMissing) return dir === "asc" ? 1 : -1;
  if (bMissing) return dir === "asc" ? -1 : 1;
  const cmp = na - nb;
  return dir === "asc" ? cmp : -cmp;
}

/** Compare two strings with localeCompare; null/undefined treated as "". */
export function compareString(a: string | null | undefined, b: string | null | undefined, dir: SortDirection): number {
  const sa = (a ?? "").toString().trim();
  const sb = (b ?? "").toString().trim();
  const cmp = sa.localeCompare(sb, undefined, { sensitivity: "base" });
  return dir === "asc" ? cmp : -cmp;
}

/**
 * Compare two date-like values (ISO string or Date). Sorts by timestamp.
 * null/undefined/invalid dates sort to the end in asc, to the start in desc (so "no date" appears last when ascending).
 */
export function compareDate(
  a: string | Date | null | undefined,
  b: string | Date | null | undefined,
  dir: SortDirection
): number {
  const parse = (v: string | Date | null | undefined): number | null => {
    if (v == null) return null;
    if (v instanceof Date) return v.getTime();
    const d = new Date(v as string);
    return Number.isNaN(d.getTime()) ? null : d.getTime();
  };
  const ta = parse(a);
  const tb = parse(b);
  const aMissing = ta == null;
  const bMissing = tb == null;
  if (aMissing && bMissing) return 0;
  if (aMissing) return dir === "asc" ? 1 : -1;
  if (bMissing) return dir === "asc" ? -1 : 1;
  const cmp = ta - tb;
  return dir === "asc" ? cmp : -cmp;
}
