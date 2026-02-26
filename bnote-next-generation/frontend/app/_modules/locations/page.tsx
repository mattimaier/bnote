/**
 * BNote Next Generation - Locations Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useSearchParams, useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { locationsApi, type Location } from "@/lib/locations-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareNumber, compareString, type SortDirection } from "@/lib/table-sort";
import { NotesContent } from "@/components/NotesContent";
import { ResizableTable, ResizableTh } from "@/components/ResizableTable";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { getIcon } from "@/components/icons";
import { AddressLink } from "@/components/AddressLink";
import { getColor, getPillStyle, getDotStyle } from "@/lib/entity-config";
import { Plus, ArrowUp, ArrowDown, ArrowUpDown, MapPin } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PageContent } from "@/components/PageContent";

type SortKey = "name" | "city" | "zip";

export default function LocationsPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [locations, setLocations] = useState<Location[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<SortKey | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadLocations = useCallback(async () => {
    setLoading(true);
    try {
      const list = await locationsApi.list();
      setLocations(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(
          t("js.error.locationsAccessDenied") !== "js.error.locationsAccessDenied"
            ? t("js.error.locationsAccessDenied")
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
    loadLocations();
  }, [ready, loadLocations]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (locations.some((loc) => loc.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("location", id));
    }
  }, [ready, loading, locations, searchParams, router]);

  const handleRowClick = (id: number) => {
    router.push(getEntityPath("location", id));
  };

  const filteredLocations = search.trim()
    ? locations.filter(
        (loc) =>
          (loc.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (loc.notes ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (loc.city ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (loc.street ?? "").toLowerCase().includes(search.toLowerCase())
      )
    : locations;

  const handleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setSortKey(key);
      setSortDir("asc");
    }
  };

  const sortedLocations =
    sortKey == null
      ? filteredLocations
      : [...filteredLocations].sort((a, b) => {
          switch (sortKey) {
            case "name":
              return compareString(a.name ?? "", b.name ?? "", sortDir);
            case "city":
              return compareString(a.city ?? "", b.city ?? "", sortDir);
            case "zip":
              return compareString(a.zip ?? "", b.zip ?? "", sortDir);
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
    <PageContent>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-base-content">
            {t("js.locations.title") !== "js.locations.title" ? t("js.locations.title") : "Locations"}
          </h1>
          <p className="mt-1 text-sm text-base-content/60">
            {t("js.locations.subtitle") !== "js.locations.subtitle"
              ? t("js.locations.subtitle")
              : "Manage venues and rehearsal rooms"}
          </p>
        </div>
        <ActionButton href={getEntityPath("location", "new", "edit")}>
          <Plus className="h-4 w-4" />
          {t("js.locations.addLocation") !== "js.locations.addLocation"
            ? t("js.locations.addLocation")
            : "Add Location"}
        </ActionButton>
      </div>

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
              t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"
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
          <ResponsiveTable<Location, SortKey>
            rows={sortedLocations}
            getRowKey={(loc) => loc.id}
            renderMobileRow={(loc) => {
              const entityColor = getColor("location");
              const pillStyle = getPillStyle(entityColor);
              const dotStyle = getDotStyle(entityColor);
              const Icon = getIcon("map-pin");
              const address = [loc.street, loc.city].filter(Boolean).join(", ");
              return (
                <EntityListRow
                  icon={
                    <span
                      className="rounded-full flex items-center justify-center w-6 h-6 text-white"
                      style={{ ...dotStyle, background: pillStyle.backgroundColor, color: pillStyle.color }}
                    >
                      <Icon className="h-3.5 w-3.5" />
                    </span>
                  }
                  primary={loc.name ?? emptyText}
                  secondary={
                    address ? (
                      <span className="flex items-center gap-1">
                        <MapPin className="h-3 w-3 opacity-70 shrink-0" />
                        <AddressLink value={address} t={t} renderRawIfNoAddress interactive={false} />
                      </span>
                    ) : undefined
                  }
                  onClick={() => handleRowClick(loc.id)}
                />
              );
            }}
            onRowClick={(loc) => handleRowClick(loc.id)}
            emptyMessage={
              t("js.locations.noLocations") !== "js.locations.noLocations"
                ? t("js.locations.noLocations")
                : "No locations found"
            }
            sortOptions={[
              { key: "name", label: t("js.locations.name") !== "js.locations.name" ? t("js.locations.name") : "Name" },
              { key: "city", label: t("js.locations.city") !== "js.locations.city" ? t("js.locations.city") : "City" },
              { key: "zip", label: t("js.locations.zip") !== "js.locations.zip" ? t("js.locations.zip") : "Zip" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={handleSort}
          >
            <ResizableTable
              className="w-full text-sm"
              columns={[
                { id: "name", width: 240, minWidth: 180 },
                { id: "notes", width: 320, minWidth: 220 },
                { id: "city", width: 180, minWidth: 140 },
              ]}
            >
              <thead>
                <tr
                  className="border-b border-base-300 bg-base-200/50"
                >
                  <SortableTh
                    label={t("js.locations.name") !== "js.locations.name" ? t("js.locations.name") : "Name"}
                    columnId="name"
                    sortKey="name"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                  <ResizableTh columnId="notes">
                    {t("js.locations.notes") !== "js.locations.notes" ? t("js.locations.notes") : "Notes"}
                  </ResizableTh>
                  <SortableTh
                    label={t("js.locations.city") !== "js.locations.city" ? t("js.locations.city") : "City"}
                    columnId="city"
                    sortKey="city"
                    currentSortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                  />
                </tr>
              </thead>
              <tbody>
                {sortedLocations.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="p-8 text-center text-base-content/60">
                      {t("js.locations.noLocations") !== "js.locations.noLocations"
                        ? t("js.locations.noLocations")
                        : "No locations found"}
                    </td>
                  </tr>
                ) : (
                  sortedLocations.map((loc) => (
                    <tr
                      key={loc.id}
                      className="cursor-pointer border-b border-base-300 transition-colors hover:bg-base-200/50"
                      onClick={() => handleRowClick(loc.id)}
                    >
                      <td className="p-3 font-medium">{loc.name ?? emptyText}</td>
                      <td className="p-3">
                        {loc.notes ? (
                          <NotesContent value={loc.notes} className="max-w-[200px] whitespace-pre-wrap break-words" maxLines={3} />
                        ) : (
                          emptyText
                        )}
                      </td>
                      <td className="p-3">{loc.city ?? emptyText}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </ResizableTable>
          </ResponsiveTable>
        )}
      </div>
    </PageContent>
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
  const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
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
