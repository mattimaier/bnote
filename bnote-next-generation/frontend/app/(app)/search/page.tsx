/**
 * BNote Next Generation - Search Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { Suspense } from "react";
import { Spinner } from "@/components/Spinner";
import SearchPageContent from "@/components/search/SearchPageContent";

export default function SearchPage() {
  return (
    <Suspense
      fallback={
        <div className="flex items-center justify-center py-12">
          <Spinner />
        </div>
      }
    >
      <SearchPageContent />
    </Suspense>
  );
}
