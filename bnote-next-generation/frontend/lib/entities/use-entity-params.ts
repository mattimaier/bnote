/**
 * BNote Next Generation - Entity params from URL (query-based routing)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useSearchParams } from "next/navigation";

/** Entity type, id and edit flag from ?type=...&id=...&edit=1 */
export function useEntityParams(): {
  type: string;
  id: string;
  edit: boolean;
} {
  const searchParams = useSearchParams();
  return {
    type: searchParams.get("type") ?? "",
    id: searchParams.get("id") ?? "",
    edit: searchParams.get("edit") === "1",
  };
}
