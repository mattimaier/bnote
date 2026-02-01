/**
 * BNote Next Generation - BNote asset URLs (logo, etc.)
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Base URL for BNote folder (sibling to bnote-next-generation).
 * Used for logo: BNote/style/images/BNote_Logo_white_transparent.svg
 */
export function getBnoteBaseUrl(): string {
  if (typeof window === "undefined") return "";
  const apiBase = process.env.NEXT_PUBLIC_API_BASE ?? "";
  if (apiBase) {
    const u = apiBase.replace(/\/bnote-next-generation\/?$/i, "");
    return u ? `${u}/BNote/` : "";
  }
  const pathname = window.location.pathname;
  const idx = pathname.toLowerCase().indexOf("/bnote-next-generation");
  if (idx !== -1) {
    const before = pathname.slice(0, idx) || "/";
    const origin = window.location.origin;
    return `${origin}${before.endsWith("/") ? before : before + "/"}BNote/`;
  }
  return "";
}

/**
 * Logo is bundled in the Next.js app at public/BNote_Logo_white_transparent.svg.
 * Use that path so it works in dev and static export without depending on BNote folder.
 */
export function getBnoteLogoUrl(): string {
  return "/BNote_Logo_white_transparent.svg";
}
