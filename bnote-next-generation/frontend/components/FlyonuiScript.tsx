/**
 * BNote Next Generation - FlyonUI JavaScript loader
 * Loads FlyonUI JS for interactive components (modal, dropdown, etc.)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { usePathname } from "next/navigation";
import { useEffect } from "react";

export default function FlyonuiScript() {
  const path = usePathname();

  const applyResolvedTheme = () => {
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

    const storedTheme = readStoredTheme();
    const isDark = storedTheme ? storedTheme === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
    const themeColor = isDark ? "#2d2e38" : "#fcfcfd";
    document.documentElement.classList.toggle("dark", isDark);
    document.documentElement.setAttribute("data-theme", isDark ? "bnotedark" : "bnotelight");
    document.documentElement.style.colorScheme = isDark ? "dark" : "light";
    const themeMeta = document.getElementById("app-theme-color");
    if (themeMeta) {
      themeMeta.setAttribute("content", themeColor);
    }
  };

  useEffect(() => {
    const initFlyonUI = async () => {
      await import("flyonui/flyonui");
      applyResolvedTheme();
    };
    initFlyonUI();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      if (
        typeof window !== "undefined" &&
        (window as unknown as { HSStaticMethods?: { autoInit?: () => void } }).HSStaticMethods &&
        typeof (window as unknown as { HSStaticMethods: { autoInit: () => void } }).HSStaticMethods.autoInit === "function"
      ) {
        (window as unknown as { HSStaticMethods: { autoInit: () => void } }).HSStaticMethods.autoInit();
      }
      // Re-assert app theme after FlyonUI auto-init to avoid iOS/system overrides.
      applyResolvedTheme();
    }, 100);
    return () => clearTimeout(timer);
  }, [path]);

  return null;
}
