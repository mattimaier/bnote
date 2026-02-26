/**
 * BNote Next Generation - Share Page
 * File sharing with rights-based access (Dropbox-style)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import {
  shareApi,
  type ShareRoot,
  type ShareItem,
  type ShareBrowseResult,
} from "@/lib/share-api";
import { ShareFileList } from "@/components/share/ShareFileList";
import { ShareUploadZone } from "@/components/share/ShareUploadZone";
import { getIcon } from "@/components/icons";
import { ChevronRight, FolderPlus, Download } from "@/components/icons";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import type { ShareSortKey, SortDirection } from "@/components/share/ShareFileList";

export default function SharePage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [roots, setRoots] = useState<ShareRoot[]>([]);
  const [browseResult, setBrowseResult] = useState<ShareBrowseResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [sortKey, setSortKey] = useState<ShareSortKey | null>("name");
  const [sortDir, setSortDir] = useState<SortDirection>("asc");
  const [deleteModal, setDeleteModal] = useState<ShareItem | null>(null);
  const [createFolderModal, setCreateFolderModal] = useState(false);
  const [newFolderName, setNewFolderName] = useState("");

  const pathFromUrl = searchParams.get("path");
  const effectivePath = pathFromUrl ?? "";

  const loadRoots = useCallback(async () => {
    try {
      const res = await shareApi.listRoots();
      setRoots(res.roots ?? []);
    } catch (err) {
      setError(getErrorMessage(err, t, "js.share.failedToLoad"));
    }
  }, [t]);

  const loadBrowse = useCallback(
    async (path: string) => {
      setLoading(true);
      setError("");
      try {
        const res = await shareApi.browse(
          path,
          sortKey ?? undefined,
          sortDir
        );
        setBrowseResult(res);
      } catch (err) {
        setError(getErrorMessage(err, t, "js.share.failedToLoad"));
        setBrowseResult(null);
      } finally {
        setLoading(false);
      }
    },
    [sortKey, sortDir, t]
  );

  useEffect(() => {
    if (!ready) return;
    loadRoots();
  }, [ready, loadRoots]);

  useEffect(() => {
    if (!ready) return;
    loadBrowse(effectivePath);
  }, [ready, effectivePath, loadBrowse]);

  const handleNavigate = useCallback(
    (path: string) => {
      const query = path ? `?path=${encodeURIComponent(path)}` : "";
      router.push(`/share${query}`);
    },
    [router]
  );

  const handleSort = useCallback((key: ShareSortKey) => {
    setSortKey((prev) => {
      if (prev === key) {
        setSortDir((d) => (d === "asc" ? "desc" : "asc"));
      } else {
        setSortDir("asc");
      }
      return key;
    });
  }, []);

  const handleUpload = useCallback(
    async (files: File[]) => {
      const res = await shareApi.upload(effectivePath, files);
      if (res.uploaded.length > 0) {
        showToast(
          res.uploaded.length === 1
            ? t("js.share.uploadedSingle").replace("%s", res.uploaded[0])
            : t("js.share.uploadedMultiple").replace("%s", String(res.uploaded.length)),
          "success"
        );
        loadBrowse(effectivePath);
      }
      if (res.errors.length > 0) {
        showToast(res.errors.join("; "), "error");
      }
    },
    [effectivePath, showToast, loadBrowse, t]
  );

  const handleDelete = useCallback(
    async (item: ShareItem) => {
      try {
        await shareApi.delete(item.path);
        showToast(t("js.common.deleted"), "success");
        setDeleteModal(null);
        loadBrowse(effectivePath);
      } catch (err) {
        showToast(err instanceof Error ? err.message : t("js.share.deleteFailed"), "error");
      }
    },
    [effectivePath, showToast, loadBrowse, t]
  );

  const handleDownload = useCallback((item: ShareItem) => {
    if (item.type === "folder") return;
    const url = shareApi.getDownloadUrl(item.path);
    window.open(url, "_blank");
  }, []);

  const handleDownloadZip = useCallback(() => {
    const url = shareApi.getDownloadZipUrl(effectivePath);
    window.open(url, "_blank");
  }, [effectivePath]);

  const handleCreateFolder = useCallback(
    async () => {
      const name = newFolderName.trim();
      if (!name) return;
      try {
        await shareApi.createFolder(effectivePath, name);
        showToast(t("js.share.folderCreated"), "success");
        setCreateFolderModal(false);
        setNewFolderName("");
        loadBrowse(effectivePath);
      } catch (err) {
        showToast(err instanceof Error ? err.message : t("js.share.createFolderFailed"), "error");
      }
    },
    [effectivePath, newFolderName, showToast, loadBrowse, t]
  );

  const title = t("js.share.title") !== "js.share.title" ? t("js.share.title") : "Share";
  const subtitle = t("js.share.subtitle") !== "js.share.subtitle" ? t("js.share.subtitle") : "Browse and manage shared files";

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div>
        <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
          {title}
        </h1>
        <p className="mt-1 text-sm" style={{ color: "var(--muted-foreground)" }}>
          {subtitle}
        </p>
      </div>

      {error && (
        <div
          className="rounded-lg border px-4 py-3 text-sm"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {error}
        </div>
      )}

      <div className="flex flex-col gap-4 lg:flex-row">
        {/* Sidebar - roots */}
        <aside
          className="w-full lg:w-56 shrink-0 rounded-lg border p-3"
          style={{
            borderColor: "var(--border)",
            background: "var(--card)",
            color: "var(--card-foreground)",
          }}
        >
          <nav className="space-y-0.5">
            {roots.map((root) => {
              const isActive = effectivePath === root.path;
              const Icon = getIcon("folder-open");
              return (
                <button
                  key={root.id}
                  type="button"
                  onClick={() => handleNavigate(root.path)}
                  className={`w-full flex items-center gap-2 px-3 py-2 rounded-lg text-left text-sm transition-colors ${
                    isActive ? "bg-[var(--primary)]/12 font-medium" : "hover:bg-[var(--muted)]/50"
                  }`}
                  style={{
                    color: isActive ? "var(--primary)" : "var(--foreground)",
                  }}
                >
                  <Icon className="h-4 w-4 shrink-0" />
                  <span className="truncate">
                    {root.id.startsWith("group_") && root.name.startsWith("Group ")
                      ? t("js.share.root.groupFallback").replace("%s", root.id.replace("group_", ""))
                      : t(`js.share.root.${root.id}`) !== `js.share.root.${root.id}`
                        ? t(`js.share.root.${root.id}`)
                        : root.name}
                  </span>
                </button>
              );
            })}
          </nav>
        </aside>

        {/* Main content */}
        <div className="flex-1 min-w-0 space-y-4">
          {/* Breadcrumb */}
          {browseResult && browseResult.breadcrumbs.length > 0 && (
            <div className="flex items-center gap-1 text-sm flex-wrap">
              {browseResult.breadcrumbs.map((crumb, i) => (
                <span key={crumb.path} className="flex items-center gap-1">
                  {i > 0 && <ChevronRight className="h-4 w-4 opacity-50" />}
                  <button
                    type="button"
                    onClick={() => handleNavigate(crumb.path)}
                    className="hover:underline"
                    style={{ color: "var(--foreground)" }}
                  >
                    {crumb.path === "" ? t("js.share.breadcrumbRoot") : crumb.name}
                  </button>
                </span>
              ))}
            </div>
          )}

          {/* Actions */}
          {browseResult?.permissions && (
            <div className="flex flex-wrap gap-2">
              {browseResult.permissions.canCreateFolder && (
                <button
                  type="button"
                  onClick={() => setCreateFolderModal(true)}
                  className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium border"
                  style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
                >
                  <FolderPlus className="h-4 w-4" />
                  {t("js.share.newFolder") !== "js.share.newFolder" ? t("js.share.newFolder") : "New folder"}
                </button>
              )}
              {browseResult.permissions.canRead && effectivePath && (
                <button
                  type="button"
                  onClick={handleDownloadZip}
                  className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium border"
                  style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
                >
                  <Download className="h-4 w-4" />
                  {t("js.share.downloadZip") !== "js.share.downloadZip" ? t("js.share.downloadZip") : "Download as ZIP"}
                </button>
              )}
            </div>
          )}

          {/* Upload zone */}
          {browseResult?.permissions?.canWrite && (
            <ShareUploadZone
              onUpload={handleUpload}
              disabled={loading}
            />
          )}

          {/* File list */}
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <Spinner />
            </div>
          ) : browseResult ? (
            <ShareFileList
              items={browseResult.items}
              permissions={browseResult.permissions}
              currentPath={effectivePath}
              sortKey={sortKey}
              sortDir={sortDir}
              onSort={handleSort}
              onNavigate={handleNavigate}
              onDelete={(item) => setDeleteModal(item)}
              onDownload={handleDownload}
              onDownloadZip={effectivePath ? handleDownloadZip : undefined}
            />
          ) : null}
        </div>
      </div>

      {/* Delete confirmation */}
      {deleteModal && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          onClick={() => setDeleteModal(null)}
        >
          <div
            className="w-full max-w-md rounded-lg border bg-[var(--card)] p-4 shadow-xl"
            style={{ borderColor: "var(--border)", color: "var(--card-foreground)" }}
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-lg font-semibold mb-3">
              {t("js.common.confirmDeleteTitle") !== "js.common.confirmDeleteTitle" ? t("js.common.confirmDeleteTitle") : "Delete?"}
            </h3>
            <p className="text-sm mb-4" style={{ color: "var(--muted-foreground)" }}>
              {t("js.common.confirmDeleteMessageNamed") !== "js.common.confirmDeleteMessageNamed"
                ? t("js.common.confirmDeleteMessageNamed").replace("%s", deleteModal.name)
                : `Delete "${deleteModal.name}"? This cannot be undone.`}
            </p>
            <div className="flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setDeleteModal(null)}
                className="px-4 py-2 rounded-lg border text-sm font-medium"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              >
                {t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
              </button>
              <button
                type="button"
                onClick={() => handleDelete(deleteModal)}
                className="px-4 py-2 rounded-lg text-sm font-medium text-white"
                style={{ background: "var(--destructive)", color: "var(--destructive-foreground)" }}
              >
                {t("js.common.delete") !== "js.common.delete" ? t("js.common.delete") : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Create folder modal */}
      {createFolderModal && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
          onClick={() => setCreateFolderModal(false)}
        >
          <div
            className="w-full max-w-md rounded-lg border bg-[var(--card)] p-4 shadow-xl"
            style={{ borderColor: "var(--border)", color: "var(--card-foreground)" }}
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-lg font-semibold mb-3">
              {t("js.share.newFolder") !== "js.share.newFolder" ? t("js.share.newFolder") : "New folder"}
            </h3>
            <input
              type="text"
              value={newFolderName}
              onChange={(e) => setNewFolderName(e.target.value)}
              placeholder={t("js.share.folderName")}
              className="w-full rounded-lg border px-3 py-2 text-sm mb-4"
              style={{
                borderColor: "var(--border)",
                background: "var(--background)",
                color: "var(--foreground)",
              }}
              autoFocus
              onKeyDown={(e) => {
                if (e.key === "Enter") handleCreateFolder();
                if (e.key === "Escape") setCreateFolderModal(false);
              }}
            />
            <div className="flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setCreateFolderModal(false)}
                className="px-4 py-2 rounded-lg border text-sm font-medium"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              >
                {t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
              </button>
              <button
                type="button"
                onClick={handleCreateFolder}
                disabled={!newFolderName.trim()}
                className="px-4 py-2 rounded-lg text-sm font-medium text-white disabled:opacity-50"
                style={{ background: "var(--primary)" }}
              >
                {t("js.share.create") !== "js.share.create" ? t("js.share.create") : "Create"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
