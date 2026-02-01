/**
 * BNote Next Generation - App shell (sidebar, topbar, mobile nav state)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useState } from "react";
import { AppSidebar } from "@/components/AppSidebar";
import { AppTopbar } from "@/components/AppTopbar";
import { MobileNavDrawer } from "@/components/MobileNavDrawer";

export function AppShell({ children }: { children: React.ReactNode }) {
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  return (
    <div
      className="flex flex-col md:flex-row h-screen overflow-hidden"
      style={{ background: "var(--background)" }}
    >
      <AppSidebar />
      <div className="flex-1 flex flex-col min-w-0 min-h-0">
        <AppTopbar onOpenMobileNav={() => setMobileNavOpen(true)} />
        <main className="flex-1 overflow-y-auto px-4 py-4 lg:px-6 min-h-0">{children}</main>
      </div>
      <MobileNavDrawer open={mobileNavOpen} onClose={() => setMobileNavOpen(false)} />
    </div>
  );
}
