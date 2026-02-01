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

/**
 * Logo is bundled in the Next.js app at public/BNote_Logo_white_transparent.svg.
 * Path always includes basePath (default /bnote-next-generation).
 */
export function getBnoteLogoUrl(): string {
  const basePath = getBasePath();
  return basePath ? `${basePath}/BNote_Logo_white_transparent.svg` : "/BNote_Logo_white_transparent.svg";
}
