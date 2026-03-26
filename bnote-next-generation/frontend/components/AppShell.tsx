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
    <div className="flex flex-col md:flex-row h-screen overflow-hidden bg-base-100">
      <AppSidebar />
      <div className="flex-1 flex flex-col min-w-0 min-h-0">
        <AppTopbar onOpenMobileNav={() => setMobileNavOpen(true)} />
        {editingBar != null && <EditingBar {...editingBar} />}
        <main className="flex-1 overflow-y-auto pt-2 px-2 pb-2 md:pt-4 md:px-3 md:py-3 min-h-0">{children}</main>
      </div>
      <MobileNavDrawer open={mobileNavOpen} onClose={() => setMobileNavOpen(false)} />
    </div>
  );
}
