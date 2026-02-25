/**
 * BNote Next Generation - Share File List
 * Sortable table/list of files and folders
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { formatDateTimeShort } from "@/lib/date-time";
import { getIcon } from "@/components/icons";
import type { ShareItem, SharePermissions } from "@/lib/share-api";
import { ArrowUp, ArrowDown, ArrowUpDown, Trash2 } from "@/components/icons";

export type ShareSortKey = "name" | "size" | "modifiedAt";
export type SortDirection = "asc" | "desc";

export interface ShareFileListProps {
  items: ShareItem[];
  permissions: SharePermissions;
  currentPath: string;
  sortKey: ShareSortKey | null;
  sortDir: SortDirection;
  onSort: (key: ShareSortKey) => void;
  onNavigate: (path: string) => void;
  onDelete: (item: ShareItem) => void;
  onDownload: (item: ShareItem) => void;
  onDownloadZip?: (path: string) => void;
}

function formatSize(bytes: number): string {
  if (bytes === 0) return "—";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

export function ShareFileList({
  items,
  permissions,
  sortKey,
  sortDir,
  onSort,
  onNavigate,
  onDelete,
  onDownload,
  onDownloadZip,
}: ShareFileListProps) {
  const { t, lang } = useI18n();
  const handleSort = useCallback(
    (key: ShareSortKey) => {
      onSort(key);
    },
    [onSort]
  );

  const SortTh = ({
    label,
    columnKey,
  }: {
    label: string;
    columnKey: ShareSortKey;
  }) => {
    const active = sortKey === columnKey;
    const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
    return (
      <th
        className="px-4 py-3 text-left text-sm font-medium cursor-pointer select-none hover:opacity-80 text-base-content"
        onClick={() => handleSort(columnKey)}
      >
        <span className="inline-flex items-center gap-1.5">
          {label}
          <Icon className="h-4 w-4 opacity-70" />
        </span>
      </th>
    );
  };

  return (
    <div className="overflow-x-auto rounded-box border border-base-300 bg-base-100 text-base-content">
      <table className="w-full min-w-[32rem] text-sm">
        <thead>
          <tr className="border-b border-base-300 bg-base-200/50">
            <SortTh label={t("js.share.name")} columnKey="name" />
            <SortTh label={t("js.share.size")} columnKey="size" />
            <SortTh label={t("js.share.modified")} columnKey="modifiedAt" />
            <th className="px-4 py-3 w-12" />
          </tr>
        </thead>
        <tbody>
          {items.length === 0 ? (
            <tr>
              <td colSpan={4} className="px-4 py-8 text-center text-base-content/60">
                {t("js.share.noFilesOrFolders")}
              </td>
            </tr>
          ) : (
            items.map((item) => {
              const Icon = getIcon(item.icon);
              const isFolder = item.type === "folder";
              return (
                <tr
                  key={item.path}
                  className="border-b border-base-300 transition-colors hover:bg-base-200/50 cursor-pointer"
                  onClick={() => {
                    if (isFolder) {
                      onNavigate(item.path);
                    } else {
                      onDownload(item);
                    }
                  }}
                >
                  <td className="px-4 py-3">
                    <span className="inline-flex items-center gap-2">
                      <Icon className="h-5 w-5 shrink-0 text-base-content/60" />
                      <span className="font-medium">{item.name}</span>
                    </span>
                  </td>
                  <td className="px-4 py-3 text-base-content/60">
                    {isFolder ? "—" : formatSize(item.size)}
                  </td>
                  <td className="px-4 py-3 text-base-content/60 whitespace-nowrap" title={item.modifiedAt}>
                    {formatDateTimeShort(item.modifiedAt, lang) ?? "—"}
                  </td>
                  <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                    {item.canDelete && permissions.canDelete ? (
                      <button
                        type="button"
                        onClick={() => onDelete(item)}
                        className="p-1.5 rounded hover:bg-error/20 text-base-content/60 hover:text-error"
                        aria-label={t("js.share.deleteAria")}
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    ) : null}
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
}
