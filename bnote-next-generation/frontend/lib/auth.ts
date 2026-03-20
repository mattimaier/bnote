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
