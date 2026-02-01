/**
 * BNote Next Generation - App Topbar
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { logout } from "@/lib/auth";
import { useSearch } from "@/contexts/SearchContext";
import { SearchAutocompleteOverlay } from "@/components/SearchAutocompleteOverlay";
import { useEffect, useRef, useState } from "react";
import { checkSession } from "@/lib/auth";
import { Search, Menu, X } from "lucide-react";

function useMediaQuery(query: string): boolean {
  const [matches, setMatches] = useState(false);
  useEffect(() => {
    const m = window.matchMedia(query);
    setMatches(m.matches);
    const handler = (e: MediaQueryListEvent) => setMatches(e.matches);
    m.addEventListener("change", handler);
    return () => m.removeEventListener("change", handler);
  }, [query]);
  return matches;
}

interface AppTopbarProps {
  onOpenMobileNav?: () => void;
}

export function AppTopbar({ onOpenMobileNav }: AppTopbarProps) {
  const router = useRouter();
  const { t } = useI18n();
  const { query, setQuery, setOverlayOpen } = useSearch();
  const [user, setUser] = useState<{ name?: string; surname?: string } | null>(null);
  const searchAnchorRef = useRef<HTMLDivElement>(null);
  const isDesktop = useMediaQuery("(min-width: 768px)");

  useEffect(() => {
    checkSession().then((s) => {
      if (s.user) setUser(s.user);
    });
  }, []);

  const initials =
    user?.name || user?.surname
      ? [user.name?.charAt(0) ?? "", user.surname?.charAt(0) ?? ""].join("").toUpperCase() || "U"
      : "U";
  const fullName = [user?.name, user?.surname].filter(Boolean).join(" ") || t("js.common.user");

  async function handleLogout() {
    await logout();
    router.replace("/login");
  }

  return (
    <header
      className="sticky top-0 z-50 w-full border-b flex h-16 items-center gap-3 px-4 lg:px-6 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
      style={{ borderColor: "color-mix(in oklch, var(--border) 40%, transparent)" }}
    >
      {/* Hamburger: visible only on mobile */}
      {onOpenMobileNav && (
        <button
          type="button"
          onClick={onOpenMobileNav}
          className="md:hidden h-9 w-9 shrink-0 rounded-md flex items-center justify-center transition-colors hover:bg-[var(--muted)]/60"
          style={{ color: "var(--muted-foreground)" }}
          aria-label={t("js.common.menu") !== "js.common.menu" ? t("js.common.menu") : "Menu"}
        >
          <Menu className="h-5 w-5" />
        </button>
      )}
      {/* Search: full width */}
      <div ref={searchAnchorRef} className="relative flex-1 min-w-0">
          <form action="/search" method="get" role="search" className="relative flex items-center w-full">
            <Search
              className="absolute left-3 h-4 w-4 -translate-y-1/2 text-[var(--muted-foreground)]/60 z-10 top-1/2 pointer-events-none"
              aria-hidden
            />
            <input
              type="search"
              name="q"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onFocus={() => setOverlayOpen(true)}
              placeholder={t("js.dashboard.searchPlaceholder")}
              className="pl-9 pr-9 h-9 w-full rounded-md text-sm border outline-none focus:ring-1 focus:ring-[var(--primary)]/30 bg-muted/40 border-border/40 text-foreground placeholder:text-muted-foreground/60 focus:bg-muted/60"
              aria-label={t("js.dashboard.searchPlaceholder")}
              aria-autocomplete="list"
              aria-controls={query.trim().length >= 2 ? "search-autocomplete" : undefined}
              id="topbar-search"
            />
            {query.length > 0 && (
              <button
                type="button"
                onClick={() => setQuery("")}
                className="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 rounded-md flex items-center justify-center z-10 text-muted-foreground hover:text-foreground transition-colors"
                aria-label={t("js.search.clear") !== "js.search.clear" ? t("js.search.clear") : "Clear search"}
              >
                <X className="h-4 w-4" />
              </button>
            )}
          </form>
          {query.trim().length >= 2 && (
            <SearchAutocompleteOverlay anchorRef={searchAnchorRef} onSelect={() => {}} isDesktop={isDesktop} />
          )}
      </div>
      {/* Theme + user: shrink-0 so they don't overlap */}
      <div className="flex shrink-0 items-center gap-3">
        <ThemeToggle inline />
        <div className="h-6 w-px hidden sm:block opacity-30" style={{ background: "var(--border)" }} />
        <button
          type="button"
          onClick={handleLogout}
          className="flex items-center gap-3 pl-3 pr-2 py-1 rounded-lg hover:bg-muted/50 transition-colors user-info-btn"
        >
          <div
            className="h-8 w-8 rounded-full border-2 flex items-center justify-center shrink-0 text-xs font-semibold user-info-initials"
            style={{
              borderColor: "color-mix(in oklch, var(--primary) 20%, transparent)",
              background: "color-mix(in oklch, var(--primary) 10%, transparent)",
              color: "var(--primary)",
            }}
          >
            {initials}
          </div>
          <span className="hidden sm:block text-xs font-semibold text-left" style={{ color: "var(--foreground)" }}>
            {fullName}
          </span>
        </button>
      </div>
    </header>
  );
}
