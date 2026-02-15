/**
 * BNote Next Generation - Share File List
 * Sortable table/list of files and folders
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback } from "react";
import { getIcon } from "@/components/icons";
import type { ShareItem, SharePermissions } from "@/lib/share-api";
import { ArrowUp, ArrowDown, ArrowUpDown, Trash2 } from "lucide-react";

export type ShareSortKey = "name" | "size" | "type" | "modifiedAt";
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

function formatDate(iso: string): string {
  try {
    const d = new Date(iso);
    return d.toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return iso;
  }
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
        className="px-4 py-3 text-left text-sm font-medium cursor-pointer select-none hover:opacity-80"
        style={{ color: "var(--foreground)" }}
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
    <div
      className="overflow-hidden rounded-xl border"
      style={{
        borderColor: "var(--border)",
        background: "var(--card)",
        color: "var(--card-foreground)",
      }}
    >
      <table className="w-full text-sm">
        <thead>
          <tr
            className="border-b"
            style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}
          >
            <SortTh label="Name" columnKey="name" />
            <SortTh label="Size" columnKey="size" />
            <SortTh label="Type" columnKey="type" />
            <SortTh label="Modified" columnKey="modifiedAt" />
            <th className="px-4 py-3 w-12" />
          </tr>
        </thead>
        <tbody>
          {items.length === 0 ? (
            <tr>
              <td
                colSpan={5}
                className="px-4 py-8 text-center"
                style={{ color: "var(--muted-foreground)" }}
              >
                No files or folders
              </td>
            </tr>
          ) : (
            items.map((item) => {
              const Icon = getIcon(item.icon);
              const isFolder = item.type === "folder";
              return (
                <tr
                  key={item.path}
                  className="border-b transition-colors hover:bg-[var(--muted)]/30 cursor-pointer"
                  style={{ borderColor: "var(--border)" }}
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
                      <Icon
                        className="h-5 w-5 shrink-0"
                        style={{ color: "var(--muted-foreground)" }}
                      />
                      <span className="font-medium">{item.name}</span>
                    </span>
                  </td>
                  <td className="px-4 py-3" style={{ color: "var(--muted-foreground)" }}>
                    {isFolder ? "—" : formatSize(item.size)}
                  </td>
                  <td className="px-4 py-3" style={{ color: "var(--muted-foreground)" }}>
                    {isFolder ? "Folder" : item.mimeType || "—"}
                  </td>
                  <td className="px-4 py-3" style={{ color: "var(--muted-foreground)" }}>
                    {formatDate(item.modifiedAt)}
                  </td>
                  <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                    {item.canDelete && permissions.canDelete ? (
                      <button
                        type="button"
                        onClick={() => onDelete(item)}
                        className="p-1.5 rounded hover:bg-[var(--destructive)]/20 text-[var(--muted-foreground)] hover:text-[var(--destructive)]"
                        aria-label="Delete"
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
