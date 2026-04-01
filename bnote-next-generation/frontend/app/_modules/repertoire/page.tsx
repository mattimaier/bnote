/**
 * BNote Next Generation - Repertoire (Songs) List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { repertoireApi, type Song } from "@/lib/repertoire-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareNumber, compareString, type SortDirection } from "@/lib/table-sort";
import { getStatusPillStyle } from "@/lib/entity-config";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { getColor, getPillStyle, getDotStyle } from "@/lib/entity-config";
import { Plus, ArrowUp, ArrowDown, ArrowUpDown } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { AppPageHeader } from "@/components/AppPageHeader";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

type SortKey = "title" | "composer" | "genre" | "status";
const toStatusKey = (value: string) => value.trim().toLowerCase().replace(/\s+/g, "-");

export default function RepertoirePage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [items, setItems] = useState<Song[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<SortKey | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadSongs = useCallback(async () => {
    setLoading(true);
    try {
      const list = await repertoireApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(
          t("js.error.repertoireAccessDenied") !== "js.error.repertoireAccessDenied"
            ? t("js.error.repertoireAccessDenied")
            : "Access denied",
          "error"
        );
      }
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    loadSongs();
  }, [ready, loadSongs]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (items.some((item) => item.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("song", id));
    }
  }, [ready, loading, items, searchParams, router]);

  const handleRowClick = (id: number) => {
    router.push(getEntityPath("song", id));
  };

  const filtered =
    search.trim()
      ? items.filter(
          (item) =>
            (item.title ?? "").toLowerCase().includes(search.toLowerCase()) ||
            (item.composer ?? "").toLowerCase().includes(search.toLowerCase()) ||
            (item.genre ?? "").toLowerCase().includes(search.toLowerCase())
        )
      : items;

  const handleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setSortKey(key);
      setSortDir("asc");
    }
  };

  const sorted =
    sortKey == null
      ? filtered
      : [...filtered].sort((a, b) => {
          switch (sortKey) {
            case "title":
              return compareString(a.title ?? "", b.title ?? "", sortDir);
            case "composer":
              return compareString(a.composer ?? "", b.composer ?? "", sortDir);
            case "genre":
              return compareString(a.genre ?? "", b.genre ?? "", sortDir);
            case "status":
              return compareString(a.status ?? "", b.status ?? "", sortDir);
            default:
              return 0;
          }
        });

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <AppPageHeader
        moduleKey="repertoire"
        title={t("js.repertoire.title") !== "js.repertoire.title" ? t("js.repertoire.title") : "Repertoire"}
        subtitle={t("js.repertoire.subtitle") !== "js.repertoire.subtitle" ? t("js.repertoire.subtitle") : "Manage songs and repertoire"}
        actions={(
          <ActionButton href={getEntityPath("song", "new", "edit")}>
            <Plus className="h-4 w-4" />
            {t("js.repertoire.addSong") !== "js.repertoire.addSong"
              ? t("js.repertoire.addSong")
              : "Add Song"}
          </ActionButton>
        )}
      />

      {error && (
        <div
          className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error"
        >
          {error}
        </div>
      )}

      <div className="flex items-center gap-2">
        <div className="flex w-full items-center gap-3 rounded-lg px-3 py-2 bg-base-200">
          <input
            type="search"
            placeholder={
              t("js.common.search") !== "js.common.search"
                ? t("js.common.search")
                : "Search…"
            }
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full bg-transparent px-0 py-1 text-sm outline-none text-base-content"
          />
        </div>
      </div>

      <div className="overflow-hidden rounded-box border border-base-300 bg-base-100 text-base-content">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Spinner />
          </div>
        ) : (
          <ResponsiveTable<Song, SortKey>
            rows={sorted}
            getRowKey={(item) => item.id}
            renderMobileRow={(item) => {
              const entityColor = getColor("song");
              const pillStyle = getPillStyle(entityColor);
              const dotStyle = getDotStyle(entityColor);
              const Icon = getIcon("music");
              return (
                <EntityListRow
                  icon={<span className="rounded-full flex items-center justify-center w-6 h-6 text-white" style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}><Icon className="h-3.5 w-3.5" /></span>}
                  primary={item.title ?? emptyText}
                  secondary={item.composer ? <span>{item.composer}</span> : undefined}
                  onClick={() => handleRowClick(item.id)}
                />
              );
            }}
            onRowClick={(item) => handleRowClick(item.id)}
            emptyMessage={
              t("js.repertoire.noSongs") !== "js.repertoire.noSongs"
                ? t("js.repertoire.noSongs")
                : "No songs found"
            }
            sortOptions={[
              { key: "title", label: t("js.repertoire.songTitle") !== "js.repertoire.songTitle" ? t("js.repertoire.songTitle") : "Title" },
              { key: "composer", label: t("js.repertoire.composer") !== "js.repertoire.composer" ? t("js.repertoire.composer") : "Composer" },
              { key: "genre", label: t("js.repertoire.genre") !== "js.repertoire.genre" ? t("js.repertoire.genre") : "Genre" },
              { key: "status", label: t("js.repertoire.status") !== "js.repertoire.status" ? t("js.repertoire.status") : "Status" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={handleSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "title", width: 260, minWidth: 200 },
                { id: "composer", width: 200, minWidth: 160 },
                { id: "genre", width: 160, minWidth: 120 },
                { id: "status", width: 160, minWidth: 120 },
                { id: "length", width: 110, minWidth: 90 },
                { id: "active", width: 120, minWidth: 100 },
              ]}
            >
              <thead>
                <tr className="border-b border-base-300 bg-base-200/60">
                  <SortableTh
                    label={
                      t("js.repertoire.songTitle") !== "js.repertoire.songTitle"
                        ? t("js.repertoire.songTitle")
                        : "Title"
                    }
                    columnId="title"
                    sortKey="title"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.repertoire.composer") !== "js.repertoire.composer"
                        ? t("js.repertoire.composer")
                        : "Composer"
                    }
                    columnId="composer"
                    sortKey="composer"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.repertoire.genre") !== "js.repertoire.genre"
                        ? t("js.repertoire.genre")
                        : "Genre"
                    }
                    columnId="genre"
                    sortKey="genre"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.repertoire.status") !== "js.repertoire.status"
                        ? t("js.repertoire.status")
                        : "Status"
                    }
                    columnId="status"
                    sortKey="status"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <ResizableTh columnId="length">
                    {t("js.repertoire.length") !== "js.repertoire.length"
                      ? t("js.repertoire.length")
                      : "Length"}
                  </ResizableTh>
                  <ResizableTh columnId="active">
                    {t("js.repertoire.isActive") !== "js.repertoire.isActive"
                      ? t("js.repertoire.isActive")
                      : "Active"}
                  </ResizableTh>
                </tr>
              </thead>
              <tbody>
                {sorted.length === 0 ? (
                  <tr>
                    <td
                      colSpan={7}
                      className="p-8 text-center text-base-content/60"
                    >
                      {t("js.repertoire.noSongs") !== "js.repertoire.noSongs"
                        ? t("js.repertoire.noSongs")
                        : "No songs found"}
                    </td>
                  </tr>
                ) : (
                  sorted.map((item) => (
                    <tr
                      key={item.id}
                      className="cursor-pointer border-b border-base-300 transition-colors hover:bg-base-200/70"
                      onClick={() => handleRowClick(item.id)}
                    >
                      <td className="p-3 font-medium">{item.title ?? emptyText}</td>
                      <td className="p-3">{item.composer ?? emptyText}</td>
                      <td className="p-3">{item.genre ?? emptyText}</td>
                      <td className="p-3">
                        {item.status ? (
                          <span
                            className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                            style={getStatusPillStyle(toStatusKey(item.status))}
                          >
                            {item.status}
                          </span>
                        ) : (
                          emptyText
                        )}
                      </td>
                      <td className="p-3">{item.length ?? emptyText}</td>
                      <td className="p-3">
                        <span
                          className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                          style={getStatusPillStyle(item.is_active ? "active" : "inactive")}
                        >
                          {item.is_active
                            ? t("js.common.active") !== "js.common.active"
                              ? t("js.common.active")
                              : "Active"
                            : t("js.common.inactive") !== "js.common.inactive"
                              ? t("js.common.inactive")
                              : "Inactive"}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </ResizableTable>
          </ResponsiveTable>
        )}
      </div>
    </div>
  );
}

function SortableTh({
  label,
  columnId,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
}: {
  label: string;
  columnId: string;
  sortKey: SortKey;
  currentSortKey: SortKey | null;
  sortDir: SortDirection;
  onSort: (k: SortKey) => void;
}) {
  const active = currentSortKey === sortKey;
  const Icon = active
    ? sortDir === "asc"
      ? ArrowUp
      : ArrowDown
    : ArrowUpDown;
  return (
    <ResizableTh columnId={columnId}>
      <button
        type="button"
        onClick={() => onSort(sortKey)}
        className="inline-flex items-center gap-1.5 transition-opacity hover:opacity-80 text-base-content"
      >
        {label}
        <Icon className="h-4 w-4 opacity-70" />
      </button>
    </ResizableTh>
  );
}
