/**
 * BNote Next Generation - Mobile navigation drawer (full-screen overlay)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { BNoteLogo } from "@/components/BNoteLogo";
import { getEntityConfig } from "@/lib/entity-config";
import { getIcon } from "@/components/icons";
import { api } from "@/lib/api";
import { X } from "@/components/icons";

interface SidebarModule {
  id: number;
  name: string;
  route: string;
  icon: string;
  i18n: string;
}

interface MobileNavDrawerProps {
  open: boolean;
  onClose: () => void;
}

export function MobileNavDrawer({ open, onClose }: MobileNavDrawerProps) {
  const pathname = usePathname();
  const { t } = useI18n();
  const [modules, setModules] = useState<SidebarModule[]>([]);

  useEffect(() => {
    api
      .get<SidebarModule[] | { modules: SidebarModule[] }>("auth", "getModules")
      .then((res) => {
        const list = Array.isArray(res) ? res : (res as { modules: SidebarModule[] }).modules ?? [];
        setModules(
          list.map((m) => ({
            ...m,
            route: (m.route ?? "").replace(".html", "") || m.name.toLowerCase(),
          }))
        );
      })
      .catch(() => {
        setModules([
          { id: 1, name: "Start", route: "dashboard", icon: "layout-dashboard", i18n: "js.sidebar.dashboard" },
          { id: 3, name: "Kontakte", route: "contacts", icon: "users", i18n: "js.sidebar.contacts" },
          { id: 4, name: "Benutzer", route: "users", icon: "user", i18n: "js.sidebar.users" },
        ]);
      });
  }, []);

  const currentRoute = pathname?.replace("/", "") || "dashboard";

  // Prevent body scroll when open
  useEffect(() => {
    if (open) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [open]);

  // Close on Escape
  useEffect(() => {
    if (!open) return;
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[100] md:hidden"
      role="dialog"
      aria-modal="true"
      aria-label={t("js.common.menu") !== "js.common.menu" ? t("js.common.menu") : "Menu"}
    >
      {/* Backdrop — keep below the panel so taps hit the sheet first */}
      <div
        className="fixed inset-0 z-0 bg-black/50 backdrop-blur-sm"
        onClick={onClose}
        aria-hidden
      />
      {/* Panel: full-screen on mobile */}
      <div className="fixed inset-0 z-[1] flex flex-col bg-base-100 text-base-content">
        <div className="grid h-16 shrink-0 grid-cols-[minmax(0,1fr)_auto] items-stretch gap-2 border-b border-base-300 px-3">
          <Link
            href="/dashboard/"
            prefetch={false}
            onClick={onClose}
            className="flex h-full min-h-0 w-full min-w-0 items-center gap-3 rounded-box px-3 py-2.5 transition-all duration-200 hover:bg-base-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
            aria-label={
              t("js.sidebar.dashboard") !== "js.sidebar.dashboard"
                ? t("js.sidebar.dashboard")
                : "Dashboard"
            }
          >
            <BNoteLogo size="sm" />
            <span className="font-semibold text-sm">BNote</span>
          </Link>
          <button
            type="button"
            onClick={onClose}
            className="btn btn-soft btn-square btn-sm shrink-0 self-center"
            aria-label={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
          >
            <X className="h-5 w-5" />
          </button>
        </div>
        <nav className="flex-1 overflow-y-auto p-3 space-y-2">
          {modules.map((m) => {
            const rawRoute = (m.route ?? "").replace(/^\/+/, "") || "dashboard";
            const path = rawRoute.startsWith("/") ? rawRoute : `/${rawRoute}`;
            const href = path.endsWith("/") ? path : `${path}/`;
            const isActive = currentRoute === rawRoute || currentRoute === path.replace(/^\//, "") || currentRoute === rawRoute.replace(/\/$/, "");
            const entityConfig = getEntityConfig(rawRoute);
            const iconName = entityConfig?.icon ?? m.icon;
            const Icon = getIcon(iconName);
            const iconColor = entityConfig?.color ?? undefined;
            return (
              <Link
                key={m.id}
                href={href}
                prefetch={false}
                onClick={onClose}
                className={`flex items-center gap-3 px-3 py-2.5 rounded-box transition-all duration-200 ${
                  isActive
                    ? "bg-primary/15 text-primary font-semibold shadow-sm"
                    : "hover:bg-base-200 font-medium text-base-content"
                }`}
              >
                <span
                  className="flex shrink-0 items-center justify-center"
                  style={isActive ? undefined : iconColor ? { color: iconColor } : undefined}
                >
                  <Icon className="h-5 w-5" />
                </span>
                <span className="flex-1 truncate">{t(m.i18n) || m.name}</span>
              </Link>
            );
          })}
        </nav>
      </div>
    </div>
  );
}
