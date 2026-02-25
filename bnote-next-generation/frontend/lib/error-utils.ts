/**
 * BNote Next Generation - Centralized error message handling
 *
 * Copyright (C) 2026 BNote Contributors
 */

export type TranslateFn = (key: string) => string;

/**
 * Returns a user-facing error message from an unknown error and optional i18n.
 * Uses fallbackKey (e.g. "js.common.failedToLoad") when error has no message.
 */
export function getErrorMessage(
  err: unknown,
  t: TranslateFn,
  fallbackKey: string
): string {
  if (err instanceof Error && err.message?.trim()) {
    return err.message.trim();
  }
  const fallback = t(fallbackKey);
  return fallback !== fallbackKey ? fallback : "An error occurred";
}
