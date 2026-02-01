/**
 * BNote Next Generation - Path helpers for basePath
 * Use these whenever building paths manually so subfolder deployment works.
 *
 * Copyright (C) 2026 BNote Contributors
 */

/** Base path when app is in a subfolder (e.g. /bnote-next-generation). Default /bnote-next-generation; set NEXT_PUBLIC_BASE_PATH="" for root. */
export function getBasePath(): string {
  const p = process.env.NEXT_PUBLIC_BASE_PATH ?? "/bnote-next-generation";
  return p.endsWith("/") ? p.slice(0, -1) : p;
}

/** Prefix a path with basePath so it works in subfolder deployment. Use for form action, redirect params, or any manual path. */
export function prefixPath(path: string): string {
  const base = getBasePath();
  if (!base) return path;
  const p = path.startsWith("/") ? path : `/${path}`;
  return `${base}${p}`;
}
