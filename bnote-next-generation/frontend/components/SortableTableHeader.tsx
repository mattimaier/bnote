"use client";

import { ArrowDown, ArrowUp, ArrowUpDown } from "@/components/icons";
import { ResizableTh } from "@/components/ResizableTable";
import type { SortDirection } from "@/lib/table-sort";

function SortIcon({ active, sortDir }: { active: boolean; sortDir: SortDirection }) {
  const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
  return <Icon className="h-4 w-4 opacity-70" />;
}

export interface SortableThProps<TKey extends string> {
  label: string;
  sortKey: TKey;
  currentSortKey: TKey | null;
  sortDir: SortDirection;
  onSort: (key: TKey) => void;
  className?: string;
  buttonClassName?: string;
}

export function SortableTh<TKey extends string>({
  label,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
  className = "text-left p-3 font-semibold",
  buttonClassName = "inline-flex items-center gap-1.5 hover:opacity-80 transition-opacity text-base-content",
}: SortableThProps<TKey>) {
  const active = currentSortKey === sortKey;
  return (
    <th className={className}>
      <button type="button" onClick={() => onSort(sortKey)} className={buttonClassName}>
        {label}
        <SortIcon active={active} sortDir={sortDir} />
      </button>
    </th>
  );
}

export interface ResizableSortableThProps<TKey extends string> extends SortableThProps<TKey> {
  columnId: string;
}

export function ResizableSortableTh<TKey extends string>({
  columnId,
  label,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
  buttonClassName = "inline-flex items-center gap-1.5 transition-opacity hover:opacity-80 text-base-content",
}: ResizableSortableThProps<TKey>) {
  const active = currentSortKey === sortKey;
  return (
    <ResizableTh columnId={columnId}>
      <button type="button" onClick={() => onSort(sortKey)} className={buttonClassName}>
        {label}
        <SortIcon active={active} sortDir={sortDir} />
      </button>
    </ResizableTh>
  );
}
