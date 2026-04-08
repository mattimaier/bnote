/**
 * BNote Next Generation - App Sidebar
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { checkSession } from "@/lib/auth";
import { useI18n } from "@/contexts/I18nContext";
import { BNoteLogo } from "@/components/BNoteLogo";
import { getEntityConfig } from "@/lib/entity-config";
import { getIcon } from "@/components/icons";
import { getSidebarModuleKey } from "@/lib/sidebar-active";
import { DEVELOPER_SIDEBAR_MODULE_ID, mergeDeveloperSidebarModule } from "@/lib/developer-tools";

interface SidebarModule {
  id: number;
  name: string;
  route: string;
  icon: string;
  i18n: string;
}

export function AppSidebar() {
  const pathname = usePathname();
  const { t } = useI18n();
  const [modules, setModules] = useState<SidebarModule[]>([]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const session = await checkSession();
      if (cancelled) return;
      const isAdminUser = Boolean(session.isAdmin);
      try {
        const res = await api.get<SidebarModule[] | { modules: SidebarModule[] }>("auth", "getModules");
        if (cancelled) return;
        const list = Array.isArray(res) ? res : (res as { modules: SidebarModule[] }).modules ?? [];
        const normalized = list.map((m) => ({
          ...m,
          route: (m.route ?? "").replace(".html", "") || m.name.toLowerCase(),
        }));
        setModules(mergeDeveloperSidebarModule(normalized, isAdminUser));
      } catch {
        if (cancelled) return;
        setModules(
          mergeDeveloperSidebarModule(
            [
              { id: 1, name: "Start", route: "dashboard", icon: "layout-dashboard", i18n: "js.sidebar.dashboard" },
              { id: 3, name: "Kontakte", route: "contacts", icon: "users", i18n: "js.sidebar.contacts" },
              { id: 4, name: "Benutzer", route: "users", icon: "user", i18n: "js.sidebar.users" },
            ],
            isAdminUser
          )
        );
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const activeModuleKey = getSidebarModuleKey(pathname);

  return (
    <aside className="hidden md:flex md:flex-col md:h-full md:w-64 md:shrink-0 border-r border-base-300 bg-base-200">
      <div className="flex h-16 min-w-0 items-center px-3">
        <Link
          href="/dashboard/"
          prefetch={false}
          className="flex min-h-0 w-full min-w-0 items-center gap-3 rounded-box px-3 py-2.5 transition-colors hover:bg-base-300/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
          aria-label={
            t("js.sidebar.dashboard") !== "js.sidebar.dashboard"
              ? t("js.sidebar.dashboard")
              : "Dashboard"
          }
        >
          <BNoteLogo size="sm" />
          <span className="font-semibold text-sm text-base-content">BNote</span>
        </Link>
      </div>
      <nav className="flex-1 overflow-y-auto p-3 space-y-1.5">
        {modules.map((m, index) => {
          const rawRoute = (m.route ?? "").replace(/^\/+/, "").replace(/\/+$/, "") || "dashboard";
          const path = rawRoute.startsWith("/") ? rawRoute : `/${rawRoute}`;
          // Match next.config trailingSlash: true so links resolve correctly with basePath
          const href = path.endsWith("/") ? path : `${path}/`;
          const routeKey = rawRoute.toLowerCase();
          const moduleKey = `${routeKey}::${m.id}::${m.name}::${index}`;
          const isActive = activeModuleKey === routeKey;
          const entityConfig = getEntityConfig(rawRoute);
          const iconName = entityConfig?.icon ?? m.icon;
          const Icon = getIcon(iconName);
          const iconColor = entityConfig?.color ?? undefined;
          return (
            <Link
              key={moduleKey}
              href={href}
              prefetch={false}
              className={`flex items-center gap-3 px-3 py-2.5 rounded-box transition-colors duration-200 ${
                isActive
                  ? "bg-primary/15 text-primary text-sm font-medium"
                  : "text-base-content/70 hover:text-base-content hover:bg-base-300/60 text-sm font-medium"
              }`}
            >
              <span
                className="flex shrink-0 items-center justify-center"
                style={isActive ? undefined : iconColor ? { color: iconColor } : undefined}
              >
                <Icon className="h-5 w-5" />
              </span>
              <span className="flex-1 truncate">
                {m.id === DEVELOPER_SIDEBAR_MODULE_ID ? m.name : t(m.i18n) || m.name}
              </span>
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}
