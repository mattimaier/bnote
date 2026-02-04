/**
 * BNote Next Generation - Resizable table columns (desktop only)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";

export interface ResizableTableColumn {
  id: string;
  width?: number;
  minWidth?: number;
  maxWidth?: number;
  resizable?: boolean;
}

interface ResizableTableContextValue {
  columns: ResizableTableColumn[];
  widths: Record<string, number | undefined>;
  isDesktop: boolean;
  startResize: (columnId: string, event: React.PointerEvent) => void;
}

const ResizableTableContext = createContext<ResizableTableContextValue | null>(null);

function useResizableTableContext() {
  const ctx = useContext(ResizableTableContext);
  if (!ctx) {
    throw new Error("ResizableTable components must be used within <ResizableTable>.");
  }
  return ctx;
}

export function ResizableTable({
  columns,
  className,
  children,
}: {
  columns: ResizableTableColumn[];
  className?: string;
  children: React.ReactNode;
}) {
  const [isDesktop, setIsDesktop] = useState(false);
  const [widths, setWidths] = useState<Record<string, number | undefined>>(() => {
    const initial: Record<string, number | undefined> = {};
    columns.forEach((col) => {
      if (col.width != null) initial[col.id] = col.width;
    });
    return initial;
  });

  useEffect(() => {
    if (typeof window === "undefined") return;
    const mq = window.matchMedia("(hover: hover) and (pointer: fine)");
    const update = () => setIsDesktop(mq.matches);
    update();
    if (mq.addEventListener) {
      mq.addEventListener("change", update);
      return () => mq.removeEventListener("change", update);
    }
    mq.addListener(update);
    return () => mq.removeListener(update);
  }, []);

  const startResize = useCallback((columnId: string, event: React.PointerEvent) => {
    if (!isDesktop) return;
    event.preventDefault();
    event.stopPropagation();

    const column = columns.find((c) => c.id === columnId);
    const handle = event.currentTarget as HTMLElement;
    const th = handle.closest("th");
    const startWidth = th?.getBoundingClientRect().width ?? column?.width ?? 160;
    const startX = event.clientX;
    const minWidth = column?.minWidth ?? 80;
    const maxWidth = column?.maxWidth ?? 800;

    const onMove = (moveEvent: PointerEvent) => {
      const delta = moveEvent.clientX - startX;
      const next = Math.min(maxWidth, Math.max(minWidth, startWidth + delta));
      setWidths((prev) => ({ ...prev, [columnId]: next }));
    };

    const onUp = () => {
      document.body.style.cursor = "";
      document.body.style.userSelect = "";
      window.removeEventListener("pointermove", onMove);
      window.removeEventListener("pointerup", onUp);
    };

    document.body.style.cursor = "col-resize";
    document.body.style.userSelect = "none";
    window.addEventListener("pointermove", onMove);
    window.addEventListener("pointerup", onUp);
  }, [columns, isDesktop]);

  const ctxValue = useMemo(
    () => ({ columns, widths, isDesktop, startResize }),
    [columns, widths, isDesktop, startResize]
  );

  return (
    <ResizableTableContext.Provider value={ctxValue}>
      <table className={className} style={{ tableLayout: "fixed", width: "100%" }}>
        <colgroup>
          {columns.map((col) => {
            const width = widths[col.id] ?? col.width;
            return (
              <col
                key={col.id}
                style={{
                  width: width != null ? `${width}px` : undefined,
                  minWidth: col.minWidth != null ? `${col.minWidth}px` : undefined,
                  maxWidth: col.maxWidth != null ? `${col.maxWidth}px` : undefined,
                }}
              />
            );
          })}
        </colgroup>
        {children}
      </table>
    </ResizableTableContext.Provider>
  );
}

export function ResizableTh({
  columnId,
  className = "p-3 text-left font-semibold",
  children,
}: {
  columnId: string;
  className?: string;
  children?: React.ReactNode;
}) {
  const { columns, isDesktop, startResize } = useResizableTableContext();
  const column = columns.find((c) => c.id === columnId);
  const resizable = column?.resizable !== false;

  return (
    <th className={`relative ${className}`.trim()}>
      <div className="flex items-center gap-1.5">
        <div className="min-w-0 flex-1">{children}</div>
      </div>
      {isDesktop && resizable && (
        <div
          role="separator"
          aria-orientation="vertical"
          onPointerDown={(event) => startResize(columnId, event)}
          className="absolute right-0 top-0 h-full w-3 cursor-col-resize"
          style={{ touchAction: "none" }}
        />
      )}
    </th>
  );
}
