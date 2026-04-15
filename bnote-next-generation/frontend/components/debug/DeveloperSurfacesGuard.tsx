/**
 * Restricts /debug/* and /developer to admins when developer tools are build-enabled.
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { checkSession } from "@/lib/auth";
import { isDeveloperNavVisibleForSession } from "@/lib/developer-tools";
import { prefixPath } from "@/lib/path";

export function DeveloperSurfacesGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [allowed, setAllowed] = useState<boolean | null>(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const session = await checkSession();
      if (cancelled) return;
      const ok = isDeveloperNavVisibleForSession(session.isAdmin);
      setAllowed(ok);
      if (!ok) {
        router.replace(prefixPath("/dashboard/"));
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [router]);

  if (allowed === false) {
    return (
      <div className="flex min-h-[30vh] items-center justify-center px-4 text-sm text-base-content/60">
        Redirecting…
      </div>
    );
  }
  if (allowed !== true) {
    return (
      <div className="flex min-h-[30vh] items-center justify-center">
        <span className="loading loading-spinner loading-md text-primary" aria-hidden />
      </div>
    );
  }

  return <>{children}</>;
}
