/**
 * BNote Next Generation - Conditional app shell wrapper
 * Wraps authenticated routes with AppShell; public routes (login, register, password reset, participation magic links, legal) stay unwrapped.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { usePathname } from "next/navigation";
import { AuthGuard } from "@/components/AuthGuard";
import { I18nProvider } from "@/contexts/I18nContext";
import { ToastProvider } from "@/contexts/ToastContext";
import { SearchProvider } from "@/contexts/SearchContext";
import { EditingBarProvider } from "@/contexts/EditingBarContext";
import { AppShell } from "@/components/AppShell";
import { ToastContainer } from "@/components/ToastContainer";
import { AppQueryProvider } from "@/components/query/AppQueryProvider";

const PUBLIC_PATHS = [
  "/",
  "/login",
  "/register",
  "/reset-password",
  "/participation",
  "/legal/imprint",
  "/legal/privacy",
  "/legal/terms",
];

function isPublicPath(pathname: string | null): boolean {
  if (!pathname) return true;
  const p = pathname.replace(/\/$/, "") || "/";
  return PUBLIC_PATHS.some((pub) => p === pub || p.startsWith(`${pub}/`));
}

export default function AppShellLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const publicRoute = isPublicPath(pathname ?? null);

  if (publicRoute) {
    return <>{children}</>;
  }

  return (
    <AppQueryProvider>
      <I18nProvider>
        <ToastProvider>
          <SearchProvider>
            <EditingBarProvider>
              <AuthGuard>
                <AppShell>{children}</AppShell>
                <ToastContainer />
              </AuthGuard>
            </EditingBarProvider>
          </SearchProvider>
        </ToastProvider>
      </I18nProvider>
    </AppQueryProvider>
  );
}
