/**
 * BNote Next Generation - Debug entity view (mock or placeholder)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { use } from "react";
import { EventDetail } from "@/components/entities/event/EventDetail";
import { getMockEventData } from "@/lib/entities/debug/mock-data";
import { getEntityTypeLabel } from "@/lib/entities/debug/entity-types";
import { getRedirectPath, isEventEntityType } from "@/lib/entities/paths";
import Link from "next/link";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

interface Props {
  params: Promise<{ type: string; id: string }>;
}

function PlaceholderEntityView({ type, id }: { type: string; id: string }) {
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
          <span>{label} (placeholder)</span>
        </div>
        <div className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-6 shadow-sm">
          <h1 className="text-xl font-bold mb-2">{label} — id {id}</h1>
          <p className="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
            Full view/edit is only implemented for rehearsal and concert. This type redirects to the app.
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

export function DebugEntityView({ params }: Props) {
  const { type, id } = use(params);
  if (!isEventEntityType(type)) {
    return <PlaceholderEntityView type={type} id={id} />;
  }
  const mockData = getMockEventData(type, id);
  if (!mockData) {
    return (
      <div className="p-4">
        <p>No mock data for {type}/{id}</p>
        <Link href="/debug" className="text-primary underline">Back to Debug</Link>
      </div>
    );
  }
  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div className="flex items-center gap-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
        <Link href="/debug" className="underline">Debug</Link>
        <span>/</span>
        <span>Entity (mock): {type} / {id}</span>
      </div>
      <EventDetail type={type} id={id} mode="view" initialData={mockData} />
    </div>
  );
}
