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

import { getBasePath } from "./path";

export interface ApiRequestOptions {
  signal?: AbortSignal;
}

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
  const basePath = getBasePath();
  const prefix = basePath ? `${basePath}` : "";
  if (typeof window !== "undefined") return `${window.location.origin}${prefix}/api/index.php`;
  return `${prefix}/api/index.php`;
}

/** Directory URL for PHP under `/api` (no `index.php`), e.g. `https://host/bnote-next-generation/api`. */
export function getApiPhpDirectoryUrl(): string {
  const apiUrl = getApiUrl();
  if (apiUrl.includes("/index.php")) {
    const trimmed = apiUrl.replace(/\/index\.php$/, "");
    return trimmed.endsWith("/") ? trimmed.slice(0, -1) : trimmed;
  }
  const basePath = getBasePath();
  if (typeof window !== "undefined") {
    const p = basePath ? `${basePath}/api` : "/api";
    return `${window.location.origin}${p}`;
  }
  return basePath ? `${basePath}/api` : "/api";
}

/** Loopback mail debug scripts live under `api/debug/`. */
export function getApiDebugScriptUrl(file: string): string {
  const name = file.replace(/^\/+/, "");
  return `${getApiPhpDirectoryUrl()}/debug/${name}`;
}

export async function apiRequest<T>(
  module: string,
  action: string,
  data: Record<string, unknown> | null = null,
  params: Record<string, string> = {},
  requestOptions: ApiRequestOptions = {}
): Promise<T> {
  const apiUrl = getApiUrl();
  const url = apiUrl.startsWith("http")
    ? new URL(apiUrl)
    : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
  url.searchParams.set("module", module);
  if (action) url.searchParams.set("action", action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

  const options: RequestInit = {
    method: data ? "POST" : "GET",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    signal: requestOptions.signal,
  };
  if (data) {
    options.body = JSON.stringify({ ...data, action });
  }

  const res = await fetch(url.toString(), options);
  let json: { success?: boolean; data?: T; error?: string };
  try {
    json = await res.json();
  } catch {
    json = { success: false, error: "js.common.invalidResponse" };
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
  get: <T>(module: string, action: string, params?: Record<string, string>, requestOptions?: ApiRequestOptions) =>
    apiRequest<T>(module, action, null, params ?? {}, requestOptions),
  post: <T>(module: string, action: string, data?: Record<string, unknown>, requestOptions?: ApiRequestOptions) =>
    apiRequest<T>(module, action, data ?? {}, {}, requestOptions),
};
