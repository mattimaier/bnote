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
import { useI18n } from "@/contexts/I18nContext";
import { getBnoteLogoUrl } from "@/lib/bnote-assets";
import { getEntityConfig } from "@/lib/entity-config";
import { getIcon } from "@/components/icons";

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
  const [logoUrl, setLogoUrl] = useState("");

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
    setLogoUrl(getBnoteLogoUrl());
  }, []);

  const currentRoute = pathname?.replace("/", "") || "dashboard";

  return (
    <aside className="hidden md:flex md:flex-col md:h-full md:w-64 md:shrink-0 border-r border-base-300 bg-base-200">
      <div className="flex h-16 items-center px-4 lg:px-6">
        <div className="flex items-center gap-3">
          <div className="h-9 w-9 rounded-box flex items-center justify-center ring-1 ring-primary/20 bg-gradient-to-br from-primary/30 to-primary/10">
            {logoUrl ? (
              <img
                src={logoUrl}
                alt="BNote"
                className="h-5 w-5"
                style={{
                  filter:
                    "brightness(0) saturate(100%) invert(58%) sepia(95%) saturate(2878%) hue-rotate(195deg) brightness(102%) contrast(101%)",
                }}
              />
            ) : (
              <span className="text-primary font-bold text-xs">B</span>
            )}
          </div>
          <span className="font-semibold text-sm text-base-content">BNote</span>
        </div>
      </div>
      <nav className="flex-1 overflow-y-auto p-3 space-y-1.5">
        {modules.map((m) => {
          const rawRoute = (m.route ?? "").replace(/^\/+/, "") || "dashboard";
          const path = rawRoute.startsWith("/") ? rawRoute : `/${rawRoute}`;
          // Match next.config trailingSlash: true so links resolve correctly with basePath
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
              className={`flex items-center gap-3 px-3 py-2.5 rounded-box transition-all duration-200 ${
                isActive
                  ? "bg-primary/15 text-primary font-semibold shadow-sm"
                  : "text-base-content/70 hover:text-base-content hover:bg-base-300/60 text-sm font-medium"
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
    </aside>
  );
}
