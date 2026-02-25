/**
 * BNote Next Generation - Outfits List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { outfitsApi, type Outfit } from "@/lib/outfits-api";
import { getEntityPath } from "@/lib/entities/paths";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { compareNumber, compareString, type SortDirection } from "@/lib/table-sort";
import { MarkdownText } from "@/components/MarkdownText";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { getColor, getPillStyle, getDotStyle } from "@/lib/entity-config";
import { Plus, ArrowUp, ArrowDown, ArrowUpDown } from "@/components/icons";

type SortKey = "name";

export default function OutfitsPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [items, setItems] = useState<Outfit[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<SortKey | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadOutfits = useCallback(async () => {
    setLoading(true);
    try {
      const list = await outfitsApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(
          t("js.error.outfitsAccessDenied") !== "js.error.outfitsAccessDenied"
            ? t("js.error.outfitsAccessDenied")
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
    loadOutfits();
  }, [ready, loadOutfits]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (items.some((item) => item.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("outfit", id));
    }
  }, [ready, loading, items, searchParams, router]);

  const handleRowClick = (id: number) => {
    router.push(getEntityPath("outfit", id));
  };

  const filtered =
    search.trim()
      ? items.filter(
          (item) =>
            (item.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
            (item.description ?? "").toLowerCase().includes(search.toLowerCase())
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
            case "name":
              return compareString(a.name ?? "", b.name ?? "", sortDir);
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
    <div className="mx-auto max-w-5xl space-y-4">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-base-content">
            {t("js.outfits.title") !== "js.outfits.title"
              ? t("js.outfits.title")
              : "Outfits"}
          </h1>
          <p className="mt-1 text-sm text-base-content/60">
            {t("js.outfits.subtitle") !== "js.outfits.subtitle"
              ? t("js.outfits.subtitle")
              : "Manage costumes and uniforms"}
          </p>
        </div>
        <Link
          href={getEntityPath("outfit", "new", "edit")}
          className="inline-flex shrink-0 items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white"
        >
          <Plus className="h-4 w-4" />
          {t("js.outfits.addOutfit") !== "js.outfits.addOutfit"
            ? t("js.outfits.addOutfit")
            : "Add Outfit"}
        </Link>
      </div>

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
          <ResponsiveTable<Outfit, SortKey>
            rows={sorted}
            getRowKey={(item) => item.id}
            renderMobileRow={(item) => {
              const entityColor = getColor("outfit");
              const pillStyle = getPillStyle(entityColor);
              const dotStyle = getDotStyle(entityColor);
              const Icon = getIcon("shirt");
              const sub = (item.description ?? "").trim().slice(0, 120) || emptyText;
              return (
                <EntityListRow
                  icon={<span className="rounded-full flex items-center justify-center w-6 h-6 text-white" style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}><Icon className="h-3.5 w-3.5" /></span>}
                  primary={item.name ?? emptyText}
                  secondary={sub ? <span>{sub}</span> : undefined}
                  onClick={() => handleRowClick(item.id)}
                />
              );
            }}
            onRowClick={(item) => handleRowClick(item.id)}
            emptyMessage={
              t("js.outfits.noOutfits") !== "js.outfits.noOutfits"
                ? t("js.outfits.noOutfits")
                : "No outfits found"
            }
            sortOptions={[
              { key: "name", label: t("js.outfits.name") !== "js.outfits.name" ? t("js.outfits.name") : "Name" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={handleSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "name", width: 240, minWidth: 180 },
                { id: "description", width: 360, minWidth: 240 },
              ]}
            >
              <thead>
                <tr className="border-b border-base-300 bg-base-200/50">
                  <SortableTh
                    label={
                      t("js.outfits.name") !== "js.outfits.name"
                        ? t("js.outfits.name")
                        : "Name"
                    }
                    columnId="name"
                    sortKey="name"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <ResizableTh columnId="description">
                    {t("js.outfits.description") !== "js.outfits.description"
                      ? t("js.outfits.description")
                      : "Description"}
                  </ResizableTh>
                </tr>
              </thead>
              <tbody>
                {sorted.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="p-8 text-center text-base-content/60">
                      {t("js.outfits.noOutfits") !== "js.outfits.noOutfits"
                        ? t("js.outfits.noOutfits")
                        : "No outfits found"}
                    </td>
                  </tr>
                ) : (
                  sorted.map((item) => (
                    <tr
                      key={item.id}
                      className="cursor-pointer border-b border-base-300 transition-colors hover:bg-base-200/50"
                      onClick={() => handleRowClick(item.id)}
                    >
                      <td className="p-3 font-medium">{item.name ?? emptyText}</td>
                      <td className="p-3">
                        {item.description ? (
                          <MarkdownText value={item.description} className="whitespace-pre-wrap break-words" />
                        ) : (
                          emptyText
                        )}
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
