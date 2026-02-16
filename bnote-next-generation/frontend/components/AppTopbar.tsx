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

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { logout } from "@/lib/auth";
import { prefixPath } from "@/lib/path";
import { useSearch } from "@/contexts/SearchContext";
import { SearchAutocompleteOverlay } from "@/components/SearchAutocompleteOverlay";
import { useEffect, useRef, useState } from "react";
import { checkSession } from "@/lib/auth";
import { Search, Menu, X, LogOut, User } from "@/components/icons";

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
  const [menuOpen, setMenuOpen] = useState(false);
  const searchAnchorRef = useRef<HTMLDivElement>(null);
  const userMenuRef = useRef<HTMLDivElement>(null);
  const isDesktop = useMediaQuery("(min-width: 768px)");

  useEffect(() => {
    checkSession().then((s) => {
      if (s.user) setUser(s.user);
    });
  }, []);

  useEffect(() => {
    if (!menuOpen) return;
    function handleClickOutside(e: MouseEvent) {
      if (userMenuRef.current && !userMenuRef.current.contains(e.target as Node)) {
        setMenuOpen(false);
      }
    }
    document.addEventListener("click", handleClickOutside);
    return () => document.removeEventListener("click", handleClickOutside);
  }, [menuOpen]);

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
      className="sticky top-0 z-50 w-full border-b flex h-16 items-center gap-3 px-3 md:px-4 lg:px-6 bg-[var(--background)] md:bg-background/95 md:backdrop-blur"
      style={{ borderColor: "color-mix(in oklch, var(--border) 40%, transparent)" }}
    >
      {/* Hamburger: visible only on mobile */}
      {onOpenMobileNav && (
        <button
          type="button"
          onClick={onOpenMobileNav}
          className="btn btn-soft btn-square btn-sm md:hidden"
          aria-label={t("js.common.menu") !== "js.common.menu" ? t("js.common.menu") : "Menu"}
        >
          <Menu className="h-5 w-5" />
        </button>
      )}
      {/* Search: full width */}
      <div ref={searchAnchorRef} className="relative flex-1 min-w-0">
          <form action={prefixPath("/search/")} method="get" role="search" className="relative flex items-center w-full">
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
              className="input input-sm w-full pl-9 pr-9 md:pr-56"
              aria-label={t("js.dashboard.searchPlaceholder")}
              aria-autocomplete="list"
              aria-controls={query.trim().length >= 2 ? "search-autocomplete" : undefined}
              id="topbar-search"
            />
            {query.length > 0 && (
              <button
                type="button"
                onClick={() => setQuery("")}
                className="btn btn-soft btn-square btn-xs absolute right-2 md:right-40 top-1/2 -translate-y-1/2 z-10"
                aria-label={t("js.search.clear") !== "js.search.clear" ? t("js.search.clear") : "Clear search"}
              >
                <X className="h-4 w-4" />
              </button>
            )}
            {query.length > 0 && (
              <button
                type="submit"
                className="btn btn-primary btn-sm absolute right-2 top-1/2 -translate-y-1/2 hidden md:inline-flex"
              >
                {t("js.search.showResults") !== "js.search.showResults" ? t("js.search.showResults") : "Show results"}
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
        <div ref={userMenuRef} className="relative">
          <button
            type="button"
            onClick={() => setMenuOpen((o) => !o)}
            className="flex items-center gap-3 pl-3 pr-2 py-1 rounded-lg hover:bg-muted/50 transition-colors user-info-btn"
            aria-expanded={menuOpen}
            aria-haspopup="true"
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
          {menuOpen && (
            <div
              className="absolute right-0 top-full mt-1 py-1 min-w-[180px] rounded-lg border shadow-lg z-50"
              style={{
                background: "var(--background)",
                borderColor: "var(--border)",
              }}
            >
              <Link
                href="/profile/"
                onClick={() => setMenuOpen(false)}
                className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-muted/60 transition-colors"
                style={{ color: "var(--foreground)" }}
              >
                <User className="h-4 w-4" />
                {t("js.profile.menuMyData") !== "js.profile.menuMyData" ? t("js.profile.menuMyData") : "Meine Kontaktdaten"}
              </Link>
              <button
                type="button"
                onClick={() => {
                  setMenuOpen(false);
                  handleLogout();
                }}
                className="flex items-center gap-2 w-full px-3 py-2 text-sm hover:bg-muted/60 transition-colors text-left"
                style={{ color: "var(--foreground)" }}
              >
                <LogOut className="h-4 w-4" />
                {t("js.profile.menuLogout") !== "js.profile.menuLogout" ? t("js.profile.menuLogout") : "Logout"}
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
