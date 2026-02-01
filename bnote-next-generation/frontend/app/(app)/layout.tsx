/**
 * BNote Next Generation - App Shell Layout
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import { AuthGuard } from "@/components/AuthGuard";
import { I18nProvider } from "@/contexts/I18nContext";
import { ToastProvider } from "@/contexts/ToastContext";
import { SearchProvider } from "@/contexts/SearchContext";
import { AppShell } from "@/components/AppShell";
import { ToastContainer } from "@/components/ToastContainer";

export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <I18nProvider>
      <ToastProvider>
        <SearchProvider>
        <AuthGuard>
          <AppShell>{children}</AppShell>
          <ToastContainer />
        </AuthGuard>
        </SearchProvider>
      </ToastProvider>
    </I18nProvider>
  );
}
