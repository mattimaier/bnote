/**
 * BNote Next Generation - Debug entity view route (mock data)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { Suspense } from "react";
import { ALL_DEBUG_ENTITY_TYPES } from "@/lib/entities/debug/entity-types";
import { DebugEntityView } from "./DebugEntityView";
import { Spinner } from "@/components/Spinner";

interface PageProps {
  params: Promise<{ type: string; id: string }>;
}

export async function generateStaticParams() {
  return ALL_DEBUG_ENTITY_TYPES.flatMap((type) => [
    { type, id: "1" },
    { type, id: "2" },
  ]);
}

export default function DebugEntityPage(props: PageProps) {
  return (
    <Suspense
      fallback={
        <div className="mx-auto flex min-h-[40vh] w-full max-w-7xl items-center justify-center px-4 py-12">
          <div className="flex flex-col items-center gap-3 text-base-content/60">
            <Spinner />
            <span className="text-sm">Loading mock entity…</span>
          </div>
        </div>
      }
    >
      <DebugEntityView params={props.params} />
    </Suspense>
  );
}
