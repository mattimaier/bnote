/**
 * BNote Next Generation - App shell (sidebar, topbar, mobile nav state)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { AppSidebar } from "@/components/AppSidebar";
import { AppTopbar } from "@/components/AppTopbar";
import { MobileNavDrawer } from "@/components/MobileNavDrawer";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { EditingBar } from "@/components/EditingBar";
import { checkSession } from "@/lib/auth";
import { changelogApi, type ChangelogEntry } from "@/lib/changelog-api";
import { ChangelogModal } from "@/components/changelog/ChangelogModal";
import { LegalFooter } from "@/components/auth/LegalFooter";

export function AppShell({ children }: { children: React.ReactNode }) {
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [changelogOpen, setChangelogOpen] = useState(false);
  const [changelogReleaseId, setChangelogReleaseId] = useState("");
  const [changelogEntries, setChangelogEntries] = useState<ChangelogEntry[]>([]);
  const [changelogSeenKey, setChangelogSeenKey] = useState("");
  const { editingBar } = useEditingBar();
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    (async () => {
      try {
        const session = await checkSession();
        const userId = session?.user?.id ? String(session.user.id) : "";
        if (!session?.authenticated || !userId || cancelled) return;

        const seenKey = `bnote_changelog_seen::${userId}`;
        const legacySeenKey = `bnote_whats_new_seen::${userId}`;
        const seenValue = typeof window !== "undefined"
          ? localStorage.getItem(seenKey) ?? localStorage.getItem(legacySeenKey) ?? ""
          : "";
        const changelog = await changelogApi.get();
        if (cancelled) return;
        const releaseId = String(changelog?.releaseId ?? "").trim();
        const entries = Array.isArray(changelog?.entries) ? changelog.entries : [];
        if (!releaseId || entries.length === 0 || seenValue === releaseId) return;

        setChangelogSeenKey(seenKey);
        setChangelogReleaseId(releaseId);
        setChangelogEntries(entries);
        timer = setTimeout(() => {
          if (cancelled) return;
          const path = String(pathname ?? "").toLowerCase();
          if (path.startsWith("/changelog") || path.startsWith("/whats-new")) return;
          setChangelogOpen(true);
        }, 1200);
      } catch {
        // Non-blocking: changelog modal must never break shell load.
      }
    })();

    return () => {
      cancelled = true;
      if (timer) clearTimeout(timer);
    };
  }, [pathname]);

  function markReleaseSeen() {
    if (typeof window === "undefined") return;
    if (!changelogSeenKey || !changelogReleaseId) return;
    localStorage.setItem(changelogSeenKey, changelogReleaseId);
  }

  function handleLater() {
    markReleaseSeen();
    setChangelogOpen(false);
  }

  function handleViewDetails() {
    markReleaseSeen();
    setChangelogOpen(false);
    router.push("/changelog/");
  }

  return (
    <div data-bnote-capture-root="1" className="flex min-h-screen flex-col bg-base-100 md:h-screen md:flex-row md:overflow-hidden">
      <AppSidebar />
      <div className="app-shell-mobile-topbar-offset flex min-w-0 flex-1 flex-col md:min-h-0">
        <AppTopbar onOpenMobileNav={() => setMobileNavOpen(true)} />
        {editingBar != null && <EditingBar {...editingBar} />}
        <main className="flex flex-1 flex-col px-2 pb-2 pt-2 md:min-h-0 md:overflow-y-auto md:px-3 md:py-3">
          <div className="flex-1">{children}</div>
          <div className="mt-8 px-2 py-6 sm:mt-10 sm:px-3 sm:py-8">
            <LegalFooter routeMode="app" />
          </div>
        </main>
      </div>
      <MobileNavDrawer open={mobileNavOpen} onClose={() => setMobileNavOpen(false)} />
      <ChangelogModal
        open={changelogOpen}
        releaseId={changelogReleaseId}
        entries={changelogEntries}
        onClose={handleLater}
        onViewDetails={handleViewDetails}
      />
    </div>
  );
}
