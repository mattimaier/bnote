/**
 * Syntax-only email validation (does not verify that the mailbox exists).
 * Uses the `email-validator` package (lightweight, RFC-oriented checks).
 */
import { validate } from "email-validator";

export function isValidEmailSyntax(value: string): boolean {
  const v = value.trim();
  if (!v) return false;
  return validate(v);
}
