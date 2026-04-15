/**
 * BNote Next Generation - Debug entity index (all types)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import Link from "next/link";
import { ALL_DEBUG_ENTITY_TYPES, getEntityTypeLabel, hasFullMockView } from "@/lib/entities/debug/entity-types";
import { DebugPageShell, DebugSection } from "@/components/debug/DebugChrome";

export default function DebugEntityIndexPage() {
  return (
    <DebugPageShell
      breadcrumb={[
        { label: "Developer", href: "/developer/" },
        { label: "Debug", href: "/debug/" },
        { label: "Entity" },
      ]}
      title="Entity debug views"
      subtitle="Rehearsal and concert have full mock view/edit. Other types show a placeholder and link to the app."
      headerActions={
        <Link href="/debug/" className="btn btn-soft btn-sm" prefetch={false}>
          ← Debug home
        </Link>
      }
    >
      <DebugSection title="All types" description="Jump to mock view, edit, or create (where implemented).">
        <ul className="divide-y divide-base-200 rounded-box border border-base-200 overflow-hidden bg-base-100">
          {ALL_DEBUG_ENTITY_TYPES.map((type) => {
            const label = getEntityTypeLabel(type);
            const fullMock = hasFullMockView(type);
            return (
              <li
                key={type}
                className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between transition-colors hover:bg-base-200/40"
              >
                <span className="font-medium text-base-content">{label}</span>
                <div className="flex flex-wrap gap-2">
                  <Link href={`/debug/entity/${type}/1/`} className="btn btn-soft btn-xs sm:btn-sm" prefetch={false}>
                    View
                  </Link>
                  <Link
                    href={`/debug/entity/${type}/1/edit/`}
                    className="btn btn-soft btn-xs sm:btn-sm"
                    prefetch={false}
                  >
                    Edit
                  </Link>
                  {fullMock ? (
                    <Link
                      href={`/debug/entity/${type}/new/edit/`}
                      className="btn btn-soft btn-xs sm:btn-sm btn-primary"
                      prefetch={false}
                    >
                      Create
                    </Link>
                  ) : null}
                </div>
              </li>
            );
          })}
        </ul>
      </DebugSection>
    </DebugPageShell>
  );
}
