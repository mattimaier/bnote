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
import { api } from "@/lib/api";
import { configurationApi } from "@/lib/configuration-api";
import { prefixPath } from "@/lib/path";
import { useSearch } from "@/contexts/SearchContext";
import { SearchAutocompleteOverlay } from "@/components/SearchAutocompleteOverlay";
import { useEffect, useRef, useState } from "react";
import { checkSession, type SessionUser } from "@/lib/auth";
import { Search, Menu, X, LogOut, User, getIcon } from "@/components/icons";
import { Avatar } from "@/components/Avatar";
import { BugReportModal } from "@/components/bug-report/BugReportModal";
import { initBugReportDiagnostics } from "@/lib/bug-report-diagnostics";

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

interface PublicConfig {
  beta_bug_report_enabled?: boolean;
}

export function AppTopbar({ onOpenMobileNav }: AppTopbarProps) {
  const router = useRouter();
  const { t } = useI18n();
  const { query, setQuery, setOverlayOpen } = useSearch();
  const [user, setUser] = useState<SessionUser | null>(null);
  const [canConfigure, setCanConfigure] = useState(false);
  const [bugReportEnabled, setBugReportEnabled] = useState(false);
  const [bugReportOpen, setBugReportOpen] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const searchAnchorRef = useRef<HTMLDivElement>(null);
  const userMenuRef = useRef<HTMLDivElement>(null);
  const isDesktop = useMediaQuery("(min-width: 768px)");

  useEffect(() => {
    initBugReportDiagnostics();
    Promise.all([
      checkSession(),
      configurationApi.canAccess().catch(() => ({ canAccess: false })),
      api.get<PublicConfig>("auth", "getPublicConfig").catch(() => ({ beta_bug_report_enabled: false })),
    ]).then(([s, access, publicConfig]) => {
      if (s.user) setUser(s.user);
      setCanConfigure(Boolean(access?.canAccess));
      setBugReportEnabled(Boolean(s.authenticated && s.user && s.user.id && publicConfig?.beta_bug_report_enabled));
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

  const fullName = [user?.name, user?.surname].filter(Boolean).join(" ") || t("js.common.user");
  const SettingsMenuIcon = getIcon("settings");
  const ConfigurationMenuIcon = getIcon("key");

  async function handleLogout() {
    await logout();
    router.replace("/login");
  }

  return (
    <header className="app-topbar-safe fixed inset-x-0 top-0 z-50 w-full border-b border-base-300 flex items-center gap-2 px-3 md:sticky md:left-auto md:right-auto md:gap-3 md:px-4 lg:px-6 bg-base-100">
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
          <form action={prefixPath("/search/")} method="get" role="search" className="flex items-center gap-1 md:gap-2 w-full">
            <div className="relative flex-1 min-w-0">
              <Search
                className="absolute left-3 h-4 w-4 -translate-y-1/2 text-base-content/50 z-10 top-1/2 pointer-events-none"
                aria-hidden
              />
              <input
                type="search"
                name="q"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onFocus={() => setOverlayOpen(true)}
                placeholder={t("js.dashboard.searchPlaceholder")}
                className="input input-sm h-10 md:h-8 w-full pl-9 pr-9"
                aria-label={t("js.dashboard.searchPlaceholder")}
                aria-autocomplete="list"
                aria-controls={query.trim().length >= 2 ? "search-autocomplete" : undefined}
                id="topbar-search"
              />
              {query.length > 0 && (
                <button
                  type="button"
                  onClick={() => setQuery("")}
                  className="btn btn-soft btn-square btn-xs absolute right-2 top-1/2 -translate-y-1/2 z-10"
                  aria-label={t("js.search.clear") !== "js.search.clear" ? t("js.search.clear") : "Clear search"}
                >
                  <X className="h-4 w-4" />
                </button>
              )}
            </div>
            {query.length > 0 && (
              <button
                type="submit"
                className="btn btn-primary btn-sm shrink-0 hidden md:inline-flex"
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
      <div className="flex shrink-0 items-center gap-1.5 md:gap-3">
        <ThemeToggle inline />
        {bugReportEnabled ? (
          <button
            type="button"
            onClick={() => setBugReportOpen(true)}
            className="btn btn-soft btn-sm gap-2"
            title={t("js.bugReport.openButton") !== "js.bugReport.openButton" ? t("js.bugReport.openButton") : "Report bug"}
          >
            <span className="icon-[tabler--bug] h-4 w-4" aria-hidden />
            <span className="hidden lg:inline">
              {t("js.bugReport.openButton") !== "js.bugReport.openButton" ? t("js.bugReport.openButton") : "Report bug"}
            </span>
          </button>
        ) : null}
        <div className="h-6 w-px hidden sm:block opacity-30 bg-base-300" />
        <div ref={userMenuRef} className="relative">
          <button
            type="button"
            onClick={() => setMenuOpen((o) => !o)}
            className="flex items-center gap-2 md:gap-3 pl-2 md:pl-3 pr-1.5 md:pr-2 py-1 rounded-box hover:bg-base-200 transition-colors user-info-btn"
            aria-expanded={menuOpen}
            aria-haspopup="true"
          >
            <Avatar email={user?.email} name={fullName} size={32} variant="solid" />
            <span className="hidden sm:block text-xs font-semibold text-left text-base-content">
              {fullName}
            </span>
          </button>
          {menuOpen && (
            <div className="absolute right-0 top-full mt-1 py-1 min-w-[180px] rounded-box border border-base-300 bg-base-100 shadow-lg z-50">
              <Link
                href="/profile/"
                onClick={() => setMenuOpen(false)}
                className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-base-200 transition-colors text-base-content"
              >
                <User className="h-4 w-4" />
                {t("js.profile.menuMyData") !== "js.profile.menuMyData" ? t("js.profile.menuMyData") : "My Contact Data"}
              </Link>
              <Link
                href="/settings/"
                onClick={() => setMenuOpen(false)}
                className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-base-200 transition-colors text-base-content"
              >
                <SettingsMenuIcon className="h-4 w-4" />
                {t("js.profile.menuSettings") !== "js.profile.menuSettings" ? t("js.profile.menuSettings") : "Preferences"}
              </Link>
              {canConfigure ? (
                <Link
                  href="/configuration/"
                  onClick={() => setMenuOpen(false)}
                  className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-base-200 transition-colors text-base-content"
                >
                  <ConfigurationMenuIcon className="h-4 w-4" />
                  {t("js.profile.menuConfiguration") !== "js.profile.menuConfiguration" ? t("js.profile.menuConfiguration") : "Configuration"}
                </Link>
              ) : null}
              {bugReportEnabled ? (
                <button
                  type="button"
                  onClick={() => {
                    setMenuOpen(false);
                    setBugReportOpen(true);
                  }}
                  className="flex items-center gap-2 w-full px-3 py-2 text-sm hover:bg-base-200 transition-colors text-left text-base-content"
                >
                  <span className="icon-[tabler--bug] h-4 w-4" aria-hidden />
                  {t("js.bugReport.openButton") !== "js.bugReport.openButton" ? t("js.bugReport.openButton") : "Report bug"}
                </button>
              ) : null}
              <button
                type="button"
                onClick={() => {
                  setMenuOpen(false);
                  handleLogout();
                }}
                className="flex items-center gap-2 w-full px-3 py-2 text-sm hover:bg-base-200 transition-colors text-left text-base-content"
              >
                <LogOut className="h-4 w-4" />
                {t("js.profile.menuLogout") !== "js.profile.menuLogout" ? t("js.profile.menuLogout") : "Logout"}
              </button>
            </div>
          )}
        </div>
      </div>
      <BugReportModal open={bugReportOpen} onClose={() => setBugReportOpen(false)} />
    </header>
  );
}
