/**
 * BNote Next Generation - Search context for topbar autocomplete
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from "react";
import { performSearch, type SearchResults } from "@/lib/search";

const AUTOCOMPLETE_LIMIT = 20;
const DEBOUNCE_MS = 250;

interface SearchContextValue {
  query: string;
  setQuery: (q: string) => void;
  results: SearchResults | null;
  loading: boolean;
  overlayOpen: boolean;
  setOverlayOpen: (open: boolean) => void;
}

const SearchContext = createContext<SearchContextValue | null>(null);

export function SearchProvider({ children }: { children: React.ReactNode }) {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchResults | null>(null);
  const [loading, setLoading] = useState(false);
  const [overlayOpen, setOverlayOpen] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const runSearch = useCallback(async (q: string) => {
    const trimmed = q.trim();
    if (trimmed.length < 2) {
      setResults(null);
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const data = await performSearch(trimmed, {}, AUTOCOMPLETE_LIMIT);
      setResults(data);
    } catch {
      setResults(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (query.trim().length < 2) {
      setResults(null);
      setLoading(false);
      return;
    }
    debounceRef.current = setTimeout(() => runSearch(query), DEBOUNCE_MS);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [query, runSearch]);

  const value = useMemo<SearchContextValue>(
    () => ({
      query,
      setQuery,
      results,
      loading,
      overlayOpen,
      setOverlayOpen,
    }),
    [query, results, loading, overlayOpen]
  );

  return <SearchContext.Provider value={value}>{children}</SearchContext.Provider>;
}

export function useSearch() {
  const ctx = useContext(SearchContext);
  if (!ctx) throw new Error("useSearch must be used within SearchProvider");
  return ctx;
}
