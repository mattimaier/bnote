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

import { getBasePath } from "./path";

function getRuntimeBasePath(): string {
  if (typeof window === "undefined") return "";
  const pathname = window.location.pathname || "";
  const match = pathname.match(/^(.*?\/bnote-next-generation)(?:\/|$)/i);
  return match?.[1] ?? "";
}

/**
 * Logo is pre-rendered at build time to avoid runtime SVG quirks.
 * Path always includes basePath (default /bnote-next-generation).
 */
export function getBnoteLogoUrl(): string {
  const runtimeBasePath = getRuntimeBasePath();
  if (runtimeBasePath) {
    return `${runtimeBasePath}/BNote_Logo_prebuilt.png?v=2`;
  }
  const configBasePath = getBasePath();
  return configBasePath
    ? `${configBasePath}/BNote_Logo_prebuilt.png?v=2`
    : "/BNote_Logo_prebuilt.png?v=2";
}
