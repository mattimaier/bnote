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

  useEffect(() => {
    const initFlyonUI = async () => {
      await import("flyonui/flyonui");
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
    }, 100);
    return () => clearTimeout(timer);
  }, [path]);

  return null;
}
