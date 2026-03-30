/**
 * BNote Next Generation - Authentication Helpers
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import { api } from "./api";

export interface SessionUser {
  id?: number;
  name?: string;
  surname?: string;
  email?: string;
  [key: string]: unknown;
}

export interface Session {
  authenticated: boolean;
  user: SessionUser | null;
  isAdmin?: boolean;
}

export function isAdmin(session: Session | null): boolean {
  return Boolean(session?.isAdmin);
}

export async function checkSession(): Promise<Session> {
  try {
    const data = await api.get<Session>("auth", "session");
    return data ?? { authenticated: false, user: null };
  } catch {
    return { authenticated: false, user: null };
  }
}

export async function login(
  username: string,
  password: string
): Promise<SessionUser> {
  const data = await api.post<SessionUser>("auth", "login", {
    username,
    password,
  });
  return data!;
}

export async function logout(): Promise<void> {
  await api.post("auth", "logout", {});
}

/** Set by /reset-password/confirm after success; login page shows one-time banner then clears. */
export const LOGIN_POST_RESET_BANNER_KEY = "bnote_pw_reset_ok";

export interface PasswordResetRequestResult {
  ok: boolean;
  dev_reset_url?: string;
}

export async function requestPasswordReset(
  identifier: string
): Promise<PasswordResetRequestResult> {
  return api.post<PasswordResetRequestResult>("auth", "requestPasswordReset", {
    identifier,
  });
}

export async function completePasswordReset(
  token: string,
  pw1: string,
  pw2: string
): Promise<{ ok: boolean }> {
  return api.post<{ ok: boolean }>("auth", "completePasswordReset", {
    token,
    pw1,
    pw2,
  });
}
