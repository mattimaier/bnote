/**
 * BNote Next Generation - Auth Guard
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import { useRouter, usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import { checkSession } from "@/lib/auth";

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;
    checkSession().then((session) => {
      if (cancelled) return;
      if (!session.authenticated) {
        const redirect = `/login?redirect=${encodeURIComponent(pathname || "/")}`;
        router.replace(redirect);
        return;
      }
      setReady(true);
    });
    return () => {
      cancelled = true;
    };
  }, [router, pathname]);

  if (!ready) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-950">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-zinc-400 border-t-transparent dark:border-zinc-500" />
      </div>
    );
  }
  return <>{children}</>;
}
