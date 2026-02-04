/**
 * BNote Next Generation - Debug entity index by type
 *
 * Copyright (C) 2026 BNote Contributors
 */

import Link from "next/link";
import { ALL_DEBUG_ENTITY_TYPES, getEntityTypeLabel, hasFullMockView, isDebugEntityType } from "@/lib/entities/debug/entity-types";

interface PageProps {
  params: Promise<{ type: string }>;
}

const MOCK_IDS = ["1", "2"];

export async function generateStaticParams() {
  return ALL_DEBUG_ENTITY_TYPES.map((type) => ({ type }));
}

export default async function DebugEntityTypePage(props: PageProps) {
  const { type } = await props.params;
  if (!isDebugEntityType(type)) {
    return (
      <div className="p-6 max-w-2xl mx-auto">
        <p className="text-zinc-600 dark:text-zinc-400">Unknown entity type: {type}</p>
        <Link href="/debug/entity" className="mt-4 inline-block text-blue-600 dark:text-blue-400 underline">All entity types</Link>
      </div>
    );
  }

  const label = getEntityTypeLabel(type);
  const fullMock = hasFullMockView(type);
  return (
    <div className="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-6">
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
          <Link href="/debug" className="underline">Debug</Link>
          <span>/</span>
          <Link href="/debug/entity" className="underline">Entity</Link>
          <span>/</span>
          <span>{label}</span>
        </div>
        <h1 className="text-xl font-bold">{label}</h1>
        <p className="text-sm text-zinc-600 dark:text-zinc-400">
          {fullMock
            ? "Open view or edit with mock data (no API). IDs are for display only; same mock payload is used."
            : "Placeholder view/edit; opens in app."}
        </p>
        <div className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-4 shadow-sm space-y-4">
          <h2 className="text-sm font-semibold text-zinc-500 dark:text-zinc-400">View</h2>
          <ul className="flex flex-wrap gap-2">
            {MOCK_IDS.map((id) => (
              <li key={id}>
                <Link
                  href={`/debug/entity/${type}/${id}`}
                  className="px-3 py-2 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
                >
                  View id={id}
                </Link>
              </li>
            ))}
          </ul>
          <h2 className="text-sm font-semibold text-zinc-500 dark:text-zinc-400 pt-2">Edit</h2>
          <ul className="flex flex-wrap gap-2">
            {MOCK_IDS.map((id) => (
              <li key={id}>
                <Link
                  href={`/debug/entity/${type}/${id}/edit`}
                  className="px-3 py-2 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
                >
                  Edit id={id}
                </Link>
              </li>
            ))}
            {fullMock && (
              <li>
                <Link
                  href={`/debug/entity/${type}/new/edit`}
                  className="px-3 py-2 rounded-md text-sm bg-blue-600 text-white hover:bg-blue-700"
                >
                  Create (new)
                </Link>
              </li>
            )}
          </ul>
        </div>
      </div>
    </div>
  );
}
