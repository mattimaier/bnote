/**
 * Developer tools visibility (sidebar, /developer hub, /debug/*).
 * Requires admin (superuser or group 1) plus build/dev unlock.
 *
 * Copyright (C) 2026 BNote Contributors
 */

export interface DeveloperSidebarModule {
  id: number;
  name: string;
  route: string;
  icon: string;
  i18n: string;
}

/** Synthetic sidebar row; label is always English (not passed through i18n). */
export const DEVELOPER_SIDEBAR_MODULE_ID = -2;

export const DEVELOPER_SIDEBAR_MODULE: DeveloperSidebarModule = {
  id: DEVELOPER_SIDEBAR_MODULE_ID,
  name: "Developer",
  route: "/developer",
  icon: "terminal",
  i18n: "",
};

/** Whether the bundle may expose developer/debug surfaces (prune script, env). Not sufficient alone for UI. */
export function isDeveloperToolsBuildEnabled(): boolean {
  if (process.env.NODE_ENV === "development") return true;
  return process.env.NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS === "1";
}

/** Sidebar + in-app access: build unlock and admin session. */
export function isDeveloperNavVisibleForSession(isAdmin: boolean | undefined): boolean {
  return isDeveloperToolsBuildEnabled() && Boolean(isAdmin);
}

/**
 * @param isAdmin from `session.isAdmin` (BNote superuser or admin group member).
 */
export function mergeDeveloperSidebarModule<T extends DeveloperSidebarModule>(modules: T[], isAdmin?: boolean): T[] {
  if (!isDeveloperNavVisibleForSession(isAdmin)) return modules;
  return [...modules, { ...DEVELOPER_SIDEBAR_MODULE } as T];
}
