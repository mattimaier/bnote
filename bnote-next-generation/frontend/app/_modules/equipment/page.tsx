/**
 * BNote Next Generation - Equipment List Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useSearchParams, useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { equipmentApi, type Equipment } from "@/lib/equipment-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareNumber, compareString, type SortDirection } from "@/lib/table-sort";
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

type SortKey = "name" | "make" | "model" | "quantity" | "current_value";

export default function EquipmentPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [items, setItems] = useState<Equipment[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<SortKey | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadEquipment = useCallback(async () => {
    setLoading(true);
    try {
      const list = await equipmentApi.list();
      setItems(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(
          t("js.error.equipmentAccessDenied") !== "js.error.equipmentAccessDenied"
            ? t("js.error.equipmentAccessDenied")
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
    loadEquipment();
  }, [ready, loadEquipment]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (items.some((item) => item.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("equipment", id));
    }
  }, [ready, loading, items, searchParams, router]);

  const handleRowClick = (id: number) => {
    router.push(getEntityPath("equipment", id));
  };

  const filtered =
    search.trim()
      ? items.filter(
          (item) =>
            (item.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
            (item.make ?? "").toLowerCase().includes(search.toLowerCase()) ||
            (item.model ?? "").toLowerCase().includes(search.toLowerCase()) ||
            (item.notes ?? "").toLowerCase().includes(search.toLowerCase())
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
            case "make":
              return compareString(a.make ?? "", b.make ?? "", sortDir);
            case "model":
              return compareString(a.model ?? "", b.model ?? "", sortDir);
            case "quantity":
              return compareNumber(
                a.quantity ?? 0,
                b.quantity ?? 0,
                sortDir
              );
            case "current_value": {
              const va = parseFloat(String(a.current_value ?? 0)) || 0;
              const vb = parseFloat(String(b.current_value ?? 0)) || 0;
              return sortDir === "asc" ? va - vb : vb - va;
            }
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
        title={t("js.equipment.title") !== "js.equipment.title" ? t("js.equipment.title") : "Equipment"}
        subtitle={t("js.equipment.subtitle") !== "js.equipment.subtitle" ? t("js.equipment.subtitle") : "Manage inventory and assets"}
        actions={(
          <ActionButton href={getEntityPath("equipment", "new", "edit")}>
            <Plus className="h-4 w-4" />
            {t("js.equipment.addEquipment") !== "js.equipment.addEquipment"
              ? t("js.equipment.addEquipment")
              : "Add Equipment"}
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
        <div className="flex w-full items-center gap-3 rounded-lg bg-base-200 px-3 py-2">
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

      <div
        className="overflow-hidden rounded-xl border border-base-300 bg-base-100 text-base-content"
      >
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Spinner />
          </div>
        ) : (
          <ResponsiveTable<Equipment, SortKey>
            rows={sorted}
            getRowKey={(item) => item.id}
            renderMobileRow={(item) => {
              const entityColor = getColor("equipment");
              const pillStyle = getPillStyle(entityColor);
              const dotStyle = getDotStyle(entityColor);
              const Icon = getIcon("package");
              const sub = [item.make ?? "", item.model ?? ""].filter(Boolean).join(" · ") || emptyText;
              return (
                <EntityListRow
                  icon={
                    <span className="rounded-full flex items-center justify-center w-6 h-6 text-white" style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}>
                      <Icon className="h-3.5 w-3.5" />
                    </span>
                  }
                  primary={item.name ?? emptyText}
                  secondary={sub ? <span>{sub}</span> : undefined}
                  onClick={() => handleRowClick(item.id)}
                />
              );
            }}
            onRowClick={(item) => handleRowClick(item.id)}
            emptyMessage={
              t("js.equipment.noEquipment") !== "js.equipment.noEquipment"
                ? t("js.equipment.noEquipment")
                : "No equipment found"
            }
            sortOptions={[
              { key: "name", label: t("js.equipment.name") !== "js.equipment.name" ? t("js.equipment.name") : "Name" },
              { key: "make", label: t("js.equipment.make") !== "js.equipment.make" ? t("js.equipment.make") : "Make" },
              { key: "model", label: t("js.equipment.model") !== "js.equipment.model" ? t("js.equipment.model") : "Model" },
              { key: "quantity", label: t("js.equipment.quantity") !== "js.equipment.quantity" ? t("js.equipment.quantity") : "Qty" },
              { key: "current_value", label: t("js.equipment.current_value") !== "js.equipment.current_value" ? t("js.equipment.current_value") : "Value" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={handleSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "name", width: 220, minWidth: 180 },
                { id: "make", width: 160, minWidth: 120 },
                { id: "model", width: 160, minWidth: 120 },
                { id: "quantity", width: 100, minWidth: 80 },
                { id: "value", width: 130, minWidth: 100 },
              ]}
            >
              <thead>
                <tr className="border-b border-base-300 bg-base-200/50">
                  <SortableTh
                    label={
                      t("js.equipment.name") !== "js.equipment.name"
                        ? t("js.equipment.name")
                        : "Name"
                    }
                    columnId="name"
                    sortKey="name"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.equipment.make") !== "js.equipment.make"
                        ? t("js.equipment.make")
                        : "Make"
                    }
                    columnId="make"
                    sortKey="make"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.equipment.model") !== "js.equipment.model"
                        ? t("js.equipment.model")
                        : "Model"
                    }
                    columnId="model"
                    sortKey="model"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.equipment.quantity") !== "js.equipment.quantity"
                        ? t("js.equipment.quantity")
                        : "Qty"
                    }
                    columnId="quantity"
                    sortKey="quantity"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <SortableTh
                    label={
                      t("js.equipment.current_value") !== "js.equipment.current_value"
                        ? t("js.equipment.current_value")
                        : "Value"
                    }
                    columnId="value"
                    sortKey="current_value"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                </tr>
              </thead>
              <tbody>
                {sorted.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="p-8 text-center text-base-content/60">
                      {t("js.equipment.noEquipment") !== "js.equipment.noEquipment"
                        ? t("js.equipment.noEquipment")
                        : "No equipment found"}
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
                      <td className="p-3">{item.make ?? emptyText}</td>
                      <td className="p-3">{item.model ?? emptyText}</td>
                      <td className="p-3">{item.quantity ?? emptyText}</td>
                      <td className="p-3">{item.current_value ?? emptyText}</td>
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
