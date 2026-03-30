/**
 * Map stable API error codes from auth.register to i18n strings.
 */

export function translateRegisterApiError(
  message: string,
  t: (key: string) => string
): string {
  if (/^register_[a-z_]+$/.test(message)) {
    const key = `js.register.error.${message}`;
    const out = t(key);
    if (out !== key) {
      return out;
    }
  }
  return t("js.register.error.register_failed");
}
