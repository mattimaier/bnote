/**
 * BNote Next Generation - App shell (sidebar, topbar, mobile nav state)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { AppSidebar } from "@/components/AppSidebar";
import { AppTopbar } from "@/components/AppTopbar";
import { MobileNavDrawer } from "@/components/MobileNavDrawer";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { EditingBar } from "@/components/EditingBar";
import { changelogApi, type ChangelogEntry } from "@/lib/changelog-api";
import { ChangelogModal } from "@/components/changelog/ChangelogModal";
import { LegalFooter } from "@/components/auth/LegalFooter";
import { useSessionQuery } from "@/lib/query/hooks/use-session-query";
import { queryKeys } from "@/lib/query/keys";
import { api } from "@/lib/api";

export function AppShell({ children }: { children: React.ReactNode }) {
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [changelogOpen, setChangelogOpen] = useState(false);
  const [changelogReleaseId, setChangelogReleaseId] = useState("");
  const [changelogEntries, setChangelogEntries] = useState<ChangelogEntry[]>([]);
  const [changelogSeenKey, setChangelogSeenKey] = useState("");
  const { editingBar } = useEditingBar();
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const queryClient = useQueryClient();
  const { data: session } = useSessionQuery();

  useEffect(() => {
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    (async () => {
      try {
        const userId = session?.user?.id ? String(session.user.id) : "";
        if (!session?.authenticated || !userId || cancelled) return;

        const seenKey = `bnote_changelog_seen::${userId}`;
        const seenValue = typeof window !== "undefined"
          ? localStorage.getItem(seenKey) ?? ""
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
          if (path.startsWith("/changelog")) return;
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
  }, [pathname, session?.authenticated, session?.user?.id]);

  useEffect(() => {
    if (!session?.authenticated) return;
    void queryClient.prefetchQuery({
      queryKey: queryKeys.auth.modules,
      queryFn: async ({ signal }) => {
        const res = await api.get<Array<{ id: number; name: string; route?: string; icon?: string; i18n?: string }> | { modules: Array<{ id: number; name: string; route?: string; icon?: string; i18n?: string }> }>(
          "auth",
          "getModules",
          undefined,
          { signal }
        );
        const list = Array.isArray(res) ? res : res.modules ?? [];
        return list.map((m) => ({
          id: m.id,
          name: m.name ?? "",
          route: m.route,
          icon: m.icon,
          i18n: m.i18n,
        }));
      },
    });
    void queryClient.prefetchQuery({
      queryKey: queryKeys.dashboard.home,
      queryFn: ({ signal }) => api.get("dashboard", "bundle", undefined, { signal }),
    });
  }, [queryClient, session?.authenticated]);

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

  function isTypingContext(target: EventTarget | null): boolean {
    const node = target instanceof HTMLElement ? target : null;
    if (!node) return false;
    if (node.isContentEditable) return true;
    const tag = node.tagName.toLowerCase();
    if (tag === "input" || tag === "textarea" || tag === "select") return true;
    return Boolean(node.closest("input, textarea, select, [contenteditable=''], [contenteditable='true']"));
  }

  function isActionElementDisabled(element: HTMLElement): boolean {
    if (element instanceof HTMLButtonElement) return element.disabled;
    if (element instanceof HTMLInputElement) return element.disabled;
    if (element instanceof HTMLSelectElement) return element.disabled;
    if (element instanceof HTMLTextAreaElement) return element.disabled;
    return element.getAttribute("aria-disabled") === "true";
  }

  function isActionElementVisible(element: HTMLElement): boolean {
    if (element.getAttribute("aria-hidden") === "true") return false;
    if (isActionElementDisabled(element)) return false;
    const style = window.getComputedStyle(element);
    if (style.display === "none" || style.visibility === "hidden") return false;
    return element.getClientRects().length > 0;
  }

  function findVisibleActionTarget(actionName: "edit" | "delete"): HTMLElement | null {
    const allTargets = Array.from(
      document.querySelectorAll<HTMLElement>(`[data-bnote-hotkey-action="${actionName}"]`)
    );
    for (const target of allTargets) {
      if (isActionElementVisible(target)) return target;
    }
    return null;
  }

  function hasOpenOverlay(): boolean {
    return Boolean(document.querySelector("[role='dialog'][aria-modal='true']"));
  }

  function closeOrExitEditMode(): boolean {
    const path = String(pathname ?? "");
    if (path.startsWith("/profile/edit")) {
      router.replace("/profile/");
      return true;
    }
    if (path.startsWith("/entity")) {
      const params = new URLSearchParams(searchParams?.toString() ?? "");
      if (params.get("edit") === "1") {
        params.delete("edit");
        const query = params.toString();
        router.replace(query ? `/entity?${query}` : "/entity");
        return true;
      }
    }
    if (path.startsWith("/rehearsals/series/detail")) {
      const params = new URLSearchParams(searchParams?.toString() ?? "");
      if (params.get("edit") === "1") {
        params.delete("edit");
        const query = params.toString();
        router.replace(query ? `/rehearsals/series/detail?${query}` : "/rehearsals/series/detail");
        return true;
      }
    }
    return false;
  }

  useEffect(() => {
    let goChordActive = false;
    let goChordTimer: ReturnType<typeof setTimeout> | null = null;
    const setGoChord = () => {
      goChordActive = true;
      if (goChordTimer) clearTimeout(goChordTimer);
      goChordTimer = setTimeout(() => {
        goChordActive = false;
        goChordTimer = null;
      }, 1200);
    };
    const clearGoChord = () => {
      goChordActive = false;
      if (goChordTimer) {
        clearTimeout(goChordTimer);
        goChordTimer = null;
      }
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      const key = event.key;
      const lowered = key.toLowerCase();
      if (isTypingContext(event.target)) return;

      if (goChordActive) {
        clearGoChord();
        if (lowered === "d") {
          event.preventDefault();
          router.push("/dashboard/");
        } else if (lowered === "p") {
          event.preventDefault();
          router.push("/profile/");
        } else if (lowered === "s") {
          event.preventDefault();
          router.push("/settings/");
        } else if (lowered === "h") {
          event.preventDefault();
          router.push("/help/");
        }
        return;
      }

      if (!event.altKey && !event.metaKey && !event.ctrlKey && !event.shiftKey && lowered === "g") {
        setGoChord();
        return;
      }

      if (!event.altKey && !event.metaKey && !event.ctrlKey && key === "?") {
        event.preventDefault();
        router.push("/help/");
        return;
      }

      if (!event.altKey && !event.metaKey && !event.ctrlKey && !event.shiftKey && lowered === "e") {
        const editTarget = findVisibleActionTarget("edit");
        if (editTarget) {
          event.preventDefault();
          editTarget.click();
        }
        return;
      }

      if (key === "Escape") {
        if (hasOpenOverlay()) return;
        if (closeOrExitEditMode()) {
          event.preventDefault();
        }
      }
    };

    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.removeEventListener("keydown", handleKeyDown);
      clearGoChord();
    };
  }, [pathname, router, searchParams]);

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
