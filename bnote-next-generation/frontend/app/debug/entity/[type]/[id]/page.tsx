/**
 * BNote Next Generation - Debug entity view route (mock data)
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { Suspense } from "react";
import { ALL_DEBUG_ENTITY_TYPES } from "@/lib/entities/debug/entity-types";
import { DebugEntityView } from "./DebugEntityView";

interface PageProps {
  params: Promise<{ type: string; id: string }>;
}

export async function generateStaticParams() {
  return ALL_DEBUG_ENTITY_TYPES.flatMap((type) => [{ type, id: "1" }, { type, id: "2" }]);
}

export default function DebugEntityPage(props: PageProps) {
  return (
    <Suspense fallback={<div className="flex items-center justify-center py-12"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>}>
      <DebugEntityView params={props.params} />
    </Suspense>
  );
}
