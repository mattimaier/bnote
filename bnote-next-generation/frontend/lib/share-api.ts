/**
 * BNote Next Generation - Share API
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { getApiUrl } from "./api";

export interface ShareRoot {
  id: string;
  name: string;
  path: string;
}

export interface ShareItem {
  name: string;
  path: string;
  type: "file" | "folder";
  size: number;
  mimeType: string;
  icon: string;
  canDelete: boolean;
  canRename?: boolean;
  modifiedAt: string;
}

export interface ShareBreadcrumb {
  name: string;
  path: string;
}

export interface SharePermissions {
  canRead: boolean;
  canWrite: boolean;
  canDelete: boolean;
  canCreateFolder: boolean;
}

export interface ShareBrowseResult {
  items: ShareItem[];
  breadcrumbs: ShareBreadcrumb[];
  permissions: SharePermissions;
}

export interface ShareUploadResult {
  uploaded: string[];
  errors: string[];
  success: boolean;
}

async function shareRequest<T>(
  action: string,
  params: Record<string, string> = {},
  data?: Record<string, unknown>
): Promise<T> {
  const apiUrl = getApiUrl();
  const url =
    apiUrl.startsWith("http")
      ? new URL(apiUrl)
      : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
  url.searchParams.set("module", "share");
  url.searchParams.set("action", action);
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

export const shareApi = {
  listRoots: () =>
    shareRequest<{ roots: ShareRoot[] }>("list"),

  browse: (
    path: string,
    sort?: string,
    order?: "asc" | "desc"
  ) => {
    const params: Record<string, string> = { path };
    if (sort) params.sort = sort;
    if (order) params.order = order;
    return shareRequest<ShareBrowseResult>("browse", params);
  },

  getPermissions: (path: string) =>
    shareRequest<SharePermissions>("permissions", { path }),

  upload: async (path: string, files: File | File[]): Promise<ShareUploadResult> => {
    const apiUrl = getApiUrl();
    const uploadUrl =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    uploadUrl.searchParams.set("module", "share");
    uploadUrl.searchParams.set("action", "upload");

    const formData = new FormData();
    formData.set("path", path);
    const fileList = Array.isArray(files) ? files : [files];
    if (fileList.length === 1) {
      formData.set("file", fileList[0]);
    } else {
      fileList.forEach((f) => formData.append("files", f));
    }

    const res = await fetch(uploadUrl.toString(), {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });

    const json = await res.json();
    if (!res.ok || json.success === false) {
      const err = new Error(json.error ?? "Upload failed") as Error & {
        status?: number;
      };
      err.status = res.status;
      throw err;
    }
    return json.data as ShareUploadResult;
  },

  delete: (path: string) =>
    shareRequest<{ success: boolean; message: string }>("delete", {}, { path }),

  createFolder: (path: string, name: string) =>
    shareRequest<{ success: boolean; path: string; message: string }>(
      "createFolder",
      {},
      { path, name }
    ),

  rename: (path: string, newName: string) =>
    shareRequest<{ success: boolean; path: string; message: string }>(
      "rename",
      {},
      { path, newName }
    ),

  getDownloadUrl: (path: string, inline = true): string => {
    const apiUrl = getApiUrl();
    const url =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    url.searchParams.set("module", "share");
    url.searchParams.set("action", "download");
    url.searchParams.set("path", path);
    if (inline) url.searchParams.set("inline", "1");
    return url.toString();
  },

  getDownloadZipUrl: (path: string): string => {
    const apiUrl = getApiUrl();
    const url =
      apiUrl.startsWith("http")
        ? new URL(apiUrl)
        : new URL(apiUrl, typeof window !== "undefined" ? window.location.origin : "http://localhost");
    url.searchParams.set("module", "share");
    url.searchParams.set("action", "downloadZip");
    url.searchParams.set("path", path);
    return url.toString();
  },
};
