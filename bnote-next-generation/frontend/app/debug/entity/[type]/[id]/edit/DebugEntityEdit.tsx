/**
 * BNote Next Generation - Debug entity edit (mock or placeholder)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { use } from "react";
import { EventDetail } from "@/components/entities/event/EventDetail";
import { getMockEventData } from "@/lib/entities/debug/mock-data";
import { getEntityTypeLabel } from "@/lib/entities/debug/entity-types";
import { isEventEntityType } from "@/lib/entities/paths";
import { getRedirectPath } from "@/lib/entities/paths";
import Link from "next/link";

interface Props {
  params: Promise<{ type: string; id: string }>;
}

function PlaceholderEntityEdit({ type, id }: { type: string; id: string }) {
  const appPath = getRedirectPath(type, id);
  const label = getEntityTypeLabel(type);
  return (
    <div className="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-6">
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
          <Link href="/debug" className="underline">Debug</Link>
          <span>/</span>
          <Link href="/debug/entity" className="underline">Entity</Link>
          <span>/</span>
          <span>{label} edit (placeholder)</span>
        </div>
        <div className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-6 shadow-sm">
          <h1 className="text-xl font-bold mb-2">{label} — edit id {id}</h1>
          <p className="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
            Full edit is only implemented for rehearsal and concert. This type redirects to the app.
          </p>
          <Link
            href={appPath}
            className="inline-flex px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700"
          >
            Open in app →
          </Link>
        </div>
      </div>
    </div>
  );
}

export function DebugEntityEdit({ params }: Props) {
  const { type, id } = use(params);
  if (!isEventEntityType(type)) {
    return <PlaceholderEntityEdit type={type} id={id} />;
  }
  const mockData = id === "new" ? null : getMockEventData(type, id);
  if (id !== "new" && !mockData) {
    return (
      <div className="p-4">
        <p>No mock data for {type}/{id}</p>
        <Link href="/debug" className="text-primary underline">Back to Debug</Link>
      </div>
    );
  }
  return (
    <div className="mx-auto max-w-4xl space-y-4">
      <div className="flex items-center gap-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
        <Link href="/debug" className="underline">Debug</Link>
        <span>/</span>
        <span>Entity edit (mock): {type} / {id}</span>
      </div>
      <EventDetail type={type} id={id} mode="edit" initialData={mockData ?? undefined} />
    </div>
  );
}
