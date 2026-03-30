/**
 * Normalized pathname helpers for sidebar / drawer active states.
 * usePathname() omits basePath; paths use trailingSlash (e.g. /dashboard/).
 */

/** First URL segment, lowercased — e.g. /dashboard/ → "dashboard", /contacts/5 → "contacts". */
export function getSidebarModuleKey(pathname: string | null | undefined): string {
  const seg = pathname?.split("/").filter(Boolean) ?? [];
  const first = seg[0];
  return (first ?? "dashboard").toLowerCase();
}

/** In-app /imprint/ or public /legal/imprint/. */
export function isImprintNavActive(pathname: string | null | undefined): boolean {
  const seg = pathname?.split("/").filter(Boolean) ?? [];
  if (seg.length === 1 && seg[0]?.toLowerCase() === "imprint") return true;
  if (seg[0]?.toLowerCase() === "legal" && seg[1]?.toLowerCase() === "imprint") return true;
  return false;
}

/** In-app /privacy/ or public /legal/privacy/. */
export function isPrivacyNavActive(pathname: string | null | undefined): boolean {
  const seg = pathname?.split("/").filter(Boolean) ?? [];
  if (seg.length === 1 && seg[0]?.toLowerCase() === "privacy") return true;
  if (seg[0]?.toLowerCase() === "legal" && seg[1]?.toLowerCase() === "privacy") return true;
  return false;
}
