/**
 * BNote Next Generation - Conditional app shell wrapper
 * Wraps authenticated routes with AppShell; leaves login and root unwrapped.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { usePathname } from "next/navigation";
import { AuthGuard } from "@/components/AuthGuard";
import { I18nProvider } from "@/contexts/I18nContext";
import { ToastProvider } from "@/contexts/ToastContext";
import { SearchProvider } from "@/contexts/SearchContext";
import { AppShell } from "@/components/AppShell";
import { ToastContainer } from "@/components/ToastContainer";

const PUBLIC_PATHS = ["/", "/login"];

function isPublicPath(pathname: string | null): boolean {
  if (!pathname) return true;
  const p = pathname.replace(/\/$/, "") || "/";
  return PUBLIC_PATHS.some((pub) => p === pub || p.startsWith(`${pub}/`));
}

export default function AppShellLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const publicRoute = isPublicPath(pathname ?? null);

  if (publicRoute) {
    return <>{children}</>;
  }

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
