/**
 * BNote Next Generation - FlyonUI JavaScript loader
 * Loads FlyonUI JS for interactive components (modal, dropdown, etc.)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { usePathname } from "next/navigation";
import { useEffect } from "react";
import { applyTheme, isThemeApplied, resolveTheme } from "@/lib/theme";

export default function FlyonuiScript() {
  const path = usePathname();

  const applyResolvedTheme = () => {
    const resolvedTheme = resolveTheme();
    if (!isThemeApplied(resolvedTheme)) {
      applyTheme(resolvedTheme);
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
        typeof (window as unknown as { HSStaticMethods: { autoInit: () => void } }).HSStaticMethods.autoInit ===
          "function"
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
