/**
 * BNote Next Generation - Session-based Redirect
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { checkSession } from "@/lib/auth";
import { Spinner } from "@/components/Spinner";
export function Redirect() {
  const router = useRouter();
  const [done, setDone] = useState(false);

  useEffect(() => {
    let cancelled = false;
    checkSession().then((session) => {
      if (cancelled) return;
      if (session.authenticated) {
        router.replace("/dashboard");
      } else {
        router.replace("/login");
      }
      setDone(true);
    });
    return () => {
      cancelled = true;
    };
  }, [router]);

  if (!done) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-950">
        <Spinner variant="muted" />
      </div>
    );
  }
  return null;
}
