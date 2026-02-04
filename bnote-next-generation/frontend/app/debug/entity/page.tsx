/**
 * BNote Next Generation - Debug entity index (all types)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import Link from "next/link";
import { ALL_DEBUG_ENTITY_TYPES, getEntityTypeLabel, hasFullMockView } from "@/lib/entities/debug/entity-types";

export default function DebugEntityIndexPage() {
  return (
    <div className="min-h-screen bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-6">
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
          <Link href="/debug" className="underline">Debug</Link>
          <span>/</span>
          <span>Entity</span>
        </div>
        <h1 className="text-xl font-bold">Entity debug views</h1>
        <p className="text-sm text-zinc-600 dark:text-zinc-400">
          Rehearsal and concert have full mock view/edit. Other types show a placeholder and link to the app.
        </p>
        <div className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-4 shadow-sm space-y-3">
          {ALL_DEBUG_ENTITY_TYPES.map((type) => {
            const label = getEntityTypeLabel(type);
            const fullMock = hasFullMockView(type);
            return (
              <div
                key={type}
                className="flex items-center justify-between gap-3 py-2 border-b border-zinc-200 dark:border-zinc-600 last:border-0"
              >
                <span className="font-medium">{label}</span>
                <div className="flex gap-2">
                  <Link
                    href={`/debug/entity/${type}/1`}
                    className="px-3 py-1.5 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
                  >
                    View
                  </Link>
                  <Link
                    href={`/debug/entity/${type}/1/edit`}
                    className="px-3 py-1.5 rounded-md text-sm bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600"
                  >
                    Edit
                  </Link>
                  {fullMock && (
                    <Link
                      href={`/debug/entity/${type}/new/edit`}
                      className="px-3 py-1.5 rounded-md text-sm bg-blue-600 text-white hover:bg-blue-700"
                    >
                      Create
                    </Link>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
