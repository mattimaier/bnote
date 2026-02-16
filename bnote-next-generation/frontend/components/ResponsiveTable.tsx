/**
 * BNote Next Generation - ResponsiveTable
 * Table on md+, list with separator lines and title/subtitle on mobile.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React from "react";
import { ArrowUp, ArrowDown } from "@/components/icons";

export type ResponsiveTableSubtitle = React.ReactNode | string | (React.ReactNode | string)[];

export interface ResponsiveTableSortOption<TKey extends string = string> {
  key: TKey;
  label: string;
}

export interface ResponsiveTableProps<TRow, TSortKey extends string = string> {
  /** Rows to show (already sorted if needed for list) */
  rows: TRow[];
  /** Unique key per row */
  getRowKey: (row: TRow) => string | number;
  /** Title line (bold, primary). Omit if using renderMobileRow. */
  getMobileTitle?: (row: TRow) => React.ReactNode;
  /** Subtitle: one or more lines/entries (muted). Omit if using renderMobileRow. */
  getMobileSubtitle?: (row: TRow) => ResponsiveTableSubtitle;
  /** Custom row renderer for mobile (e.g. EntityListRow). When set, getMobileTitle/Subtitle are ignored. Row must handle onClick. */
  renderMobileRow?: (row: TRow) => React.ReactNode;
  /** Called when a list/row is clicked */
  onRowClick: (row: TRow) => void;
  /** Message when rows are empty (both table and list) */
  emptyMessage: React.ReactNode;
  /** Sort options for mobile dropdown */
  sortOptions?: ResponsiveTableSortOption<TSortKey>[];
  sortKey?: TSortKey | null;
  sortDir?: "asc" | "desc";
  onSort?: (key: TSortKey) => void;
  /** Table content (desktop): thead + tbody */
  children: React.ReactNode;
}

function normalizeSubtitle(sub: ResponsiveTableSubtitle): React.ReactNode {
  if (Array.isArray(sub)) {
    return (
      <>
        {sub.map((line, i) => (
          <span key={i}>
            {line}
            {i < sub.length - 1 && <br />}
          </span>
        ))}
      </>
    );
  }
  if (typeof sub === "string" && sub.includes("\n")) {
    return sub.split("\n").map((line, i) => (
      <span key={i}>
        {line}
        {i < sub.split("\n").length - 1 && <br />}
      </span>
    ));
  }
  return sub;
}

export function ResponsiveTable<TRow, TSortKey extends string = string>({
  rows,
  getRowKey,
  getMobileTitle,
  getMobileSubtitle,
  renderMobileRow,
  onRowClick,
  emptyMessage,
  sortOptions = [],
  sortKey = null,
  sortDir = "asc",
  onSort,
  children,
}: ResponsiveTableProps<TRow, TSortKey>) {
  const useCustomRow = typeof renderMobileRow === "function";

  return (
    <>
      <div className="hidden md:block overflow-x-auto">{children}</div>
      <div className="md:hidden flex flex-col px-2 pb-3">
      {sortOptions.length > 0 && onSort && (
        <div className="flex items-center justify-end gap-2 py-2">
          <select
            value={sortKey ?? sortOptions[0]?.key ?? ""}
            onChange={(e) => onSort(e.target.value as TSortKey)}
            className="input input-sm"
          >
            {sortOptions.map((opt) => (
              <option key={opt.key} value={opt.key}>
                {opt.label}
              </option>
            ))}
          </select>
          {sortKey != null && onSort && (
            <button
              type="button"
              onClick={() => onSort(sortKey)}
              className="p-1.5 rounded-field transition-opacity hover:opacity-80 text-base-content"
              title={sortDir === "asc" ? "Ascending" : "Descending"}
              aria-label={sortDir === "asc" ? "Sort ascending" : "Sort descending"}
            >
              {sortDir === "asc" ? (
                <ArrowUp className="h-4 w-4" />
              ) : (
                <ArrowDown className="h-4 w-4" />
              )}
            </button>
          )}
        </div>
      )}
      {rows.length === 0 ? (
        <div className="py-8 text-center text-sm text-base-content/60">
          {emptyMessage}
        </div>
      ) : (
        <ul className="list-none p-0 m-0 space-y-0.5">
          {rows.map((row, index) => (
            <li
              key={getRowKey(row)}
              className={index < rows.length - 1 ? "border-b border-base-300" : ""}
            >
              {useCustomRow ? (
                renderMobileRow(row)
              ) : (
                <button
                  type="button"
                  className="w-full text-left cursor-pointer py-3 transition-colors hover:bg-base-200/50"
                  onClick={() => onRowClick(row)}
                >
                  <div className="font-semibold text-sm text-base-content">
                    {getMobileTitle!(row)}
                  </div>
                  <div className="text-sm mt-0.5 text-base-content/60">
                    {normalizeSubtitle(getMobileSubtitle!(row))}
                  </div>
                </button>
              )}
            </li>
          ))}
        </ul>
      )}
      </div>
    </>
  );
}
