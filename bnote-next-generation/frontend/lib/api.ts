/**
 * BNote Next Generation - API Client
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

function getApiBase(): string {
  if (typeof window !== "undefined") {
    const base = process.env.NEXT_PUBLIC_API_BASE ?? "";
    return base.endsWith("/") ? base.slice(0, -1) : base;
  }
  return process.env.NEXT_PUBLIC_API_BASE ?? "";
}

export function getApiUrl(): string {
  const base = getApiBase();
  if (base) return `${base}/api/index.php`;
  if (typeof window !== "undefined") return `${window.location.origin}/api/index.php`;
  return "/api/index.php";
}

export async function apiRequest<T>(
  module: string,
  action: string,
  data: Record<string, unknown> | null = null,
  params: Record<string, string> = {}
): Promise<T> {
  const apiUrl = getApiUrl();
  const url = apiUrl.startsWith("http") ? new URL(apiUrl) : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
  url.searchParams.set("module", module);
  if (action) url.searchParams.set("action", action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

  const options: RequestInit = {
    method: data ? "POST" : "GET",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
  };
  if (data) {
    options.body = JSON.stringify({ ...data, action });
  }

  const res = await fetch(url.toString(), options);
  let json: { success?: boolean; data?: T; error?: string };
  try {
    json = await res.json();
  } catch {
    json = { success: false, error: "Invalid response" };
  }

  if (!res.ok || json.success === false) {
    const err = new Error(json.error ?? "API request failed") as Error & {
      status?: number;
    };
    err.status = res.status;
    throw err;
  }
  return json.data as T;
}

export const api = {
  get: <T>(module: string, action: string, params?: Record<string, string>) =>
    apiRequest<T>(module, action, null, params ?? {}),
  post: <T>(module: string, action: string, data?: Record<string, unknown>) =>
    apiRequest<T>(module, action, data ?? {}),
};
