/**
 * BNote Next Generation - Theme Toggle
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";

interface ThemeToggleProps {
  /** When true, render inline in the topbar (no fixed positioning). When false, fixed top-right (e.g. login page). */
  inline?: boolean;
}

export function ThemeToggle({ inline = false }: ThemeToggleProps) {
  const { t } = useI18n();
  const [dark, setDark] = useState(false);
  const toggleLabel = t("js.common.toggleTheme") !== "js.common.toggleTheme" ? t("js.common.toggleTheme") : "Toggle theme";
  const [mounted, setMounted] = useState(false);

  const applyTheme = (isDark: boolean) => {
    const themeColor = isDark ? "#2d2e38" : "#fcfcfd";
    document.documentElement.classList.toggle("dark", isDark);
    document.documentElement.setAttribute("data-theme", isDark ? "bnotedark" : "bnotelight");
    document.documentElement.style.colorScheme = isDark ? "dark" : "light";
    const themeMeta = document.getElementById("app-theme-color");
    if (themeMeta) {
      themeMeta.setAttribute("content", themeColor);
    }
  };

  const readStoredTheme = (): "dark" | "light" | null => {
    let storedTheme: string | null = null;
    try {
      storedTheme = localStorage.getItem("theme");
    } catch (_error) {
      storedTheme = null;
    }
    if (storedTheme === "dark" || storedTheme === "light") return storedTheme;
    const cookieMatch = document.cookie.match(/(?:^|;\s*)theme=(dark|light)(?:;|$)/);
    if (cookieMatch?.[1] === "dark" || cookieMatch?.[1] === "light") return cookieMatch[1];
    return null;
  };

  const persistTheme = (value: "dark" | "light") => {
    try {
      localStorage.setItem("theme", value);
    } catch (_error) {
      // Ignore storage failures (private mode / blocked storage).
    }
    document.cookie = `theme=${value}; path=/; max-age=31536000; SameSite=Lax`;
  };

  useEffect(() => {
    setMounted(true);
    const storedTheme = readStoredTheme();
    const isDark = storedTheme ? storedTheme === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
    setDark(isDark);
    applyTheme(isDark);

    // Follow system changes only while no explicit user preference exists.
    const media = window.matchMedia("(prefers-color-scheme: dark)");
    const onSystemThemeChange = (event: MediaQueryListEvent) => {
      if (readStoredTheme() !== null) return;
      setDark(event.matches);
      applyTheme(event.matches);
    };
    media.addEventListener("change", onSystemThemeChange);
    return () => media.removeEventListener("change", onSystemThemeChange);
  }, []);

  function toggle() {
    const next = !dark;
    setDark(next);
    applyTheme(next);
    persistTheme(next ? "dark" : "light");
  }

  if (!mounted) return null;

  return (
    <button
      type="button"
      onClick={toggle}
      className={
        inline
          ? "flex h-9 w-9 shrink-0 items-center justify-center rounded-box text-base-content/60 transition-colors hover:bg-base-200 hover:text-base-content"
          : "fixed top-4 right-4 z-50 flex h-10 w-10 items-center justify-center rounded-box text-base-content/60 transition-colors hover:bg-base-200 hover:text-base-content"
      }
      title={toggleLabel}
      aria-label={toggleLabel}
    >
      {dark ? (
        <svg
          className="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
          />
        </svg>
      ) : (
        <svg
          className="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
          />
        </svg>
      )}
    </button>
  );
}
