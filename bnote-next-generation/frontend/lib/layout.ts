/**
 * BNote Next Generation - Shared layout constants
 * Single source of truth for page content width and spacing.
 *
 * Copyright (C) 2026 BNote Contributors
 */

/** Base layout: wide max-width, reduced whitespace (no vertical gap). */
export const PAGE_CONTENT_BASE_CLASS =
  "mx-auto w-full max-w-7xl px-2 py-3 md:px-4 md:py-3";

/** Shared class for module and entity page content: base + vertical gap. */
export const PAGE_CONTENT_CLASS = `${PAGE_CONTENT_BASE_CLASS} space-y-4`;
