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
import { useEditingBar } from "@/contexts/EditingBarContext";
import { EditingBar } from "@/components/EditingBar";

export function AppShell({ children }: { children: React.ReactNode }) {
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const { editingBar } = useEditingBar();

  return (
    <div data-bnote-capture-root="1" className="flex min-h-screen flex-col bg-base-100 md:h-screen md:flex-row md:overflow-hidden">
      <AppSidebar />
      <div className="app-shell-mobile-topbar-offset flex min-w-0 flex-1 flex-col md:min-h-0">
        <AppTopbar onOpenMobileNav={() => setMobileNavOpen(true)} />
        {editingBar != null && <EditingBar {...editingBar} />}
        <main className="flex-1 px-2 pb-2 pt-2 md:min-h-0 md:overflow-y-auto md:px-3 md:py-3">{children}</main>
      </div>
      <MobileNavDrawer open={mobileNavOpen} onClose={() => setMobileNavOpen(false)} />
    </div>
  );
}
