/**
 * Map auth password-reset API errors to i18n.
 */

export function translatePasswordResetApiError(
  message: string,
  t: (key: string) => string
): string {
  if (message === "password_reset_rate_limited") {
    return t("js.resetPassword.rateLimited");
  }
  if (message === "password_reset_invalid") {
    return t("js.resetPassword.invalidToken");
  }
  if (message === "register_validation") {
    return t("js.resetPassword.validationError");
  }
  return t("js.resetPassword.validationError");
}
