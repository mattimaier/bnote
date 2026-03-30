/**
 * BNote Next Generation - Debug entity edit route (mock data)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { Suspense } from "react";
import { ALL_DEBUG_ENTITY_TYPES, hasFullMockView } from "@/lib/entities/debug/entity-types";
import { DebugEntityEdit } from "./DebugEntityEdit";
import { Spinner } from "@/components/Spinner";

interface PageProps {
  params: Promise<{ type: string; id: string }>;
}

export async function generateStaticParams() {
  const params: { type: string; id: string }[] = [];
  for (const type of ALL_DEBUG_ENTITY_TYPES) {
    params.push({ type, id: "1" });
    params.push({ type, id: "2" });
    if (hasFullMockView(type)) {
      params.push({ type, id: "new" });
    }
  }
  return params;
}

export default function DebugEntityEditPage(props: PageProps) {
  return (
    <Suspense
      fallback={
        <div className="mx-auto flex min-h-[40vh] w-full max-w-7xl items-center justify-center px-4 py-12">
          <div className="flex flex-col items-center gap-3 text-base-content/60">
            <Spinner />
            <span className="text-sm">Loading mock editor…</span>
          </div>
        </div>
      }
    >
      <DebugEntityEdit params={props.params} />
    </Suspense>
  );
}
