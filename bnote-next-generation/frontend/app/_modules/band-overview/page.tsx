/**
 * BNote Next Generation - Band Overview Page
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * Extended admin dashboard with tile-based Band Overview and Your Overview sections.
 * Route: /band-overview (admin-only in sidebar)
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";
import { checkSession, type Session } from "@/lib/auth";
import { getErrorMessage } from "@/lib/error-utils";
import { BandOverviewContent } from "@/components/dashboard/BandOverviewContent";
import type { AdminOverviewData } from "@/components/dashboard/BandOverviewContent";

export default function BandOverviewPage() {
  const { t, ready } = useI18n();
  const [session, setSession] = useState<Session | null>(null);
  const [adminOverview, setAdminOverview] = useState<AdminOverviewData | null>(null);
  const [dashboardData, setDashboardData] = useState<{ inbox?: unknown[]; news?: string } | null>(null);
  const [eventsNeedingResponse, setEventsNeedingResponse] = useState<unknown[]>([]);
  const [activityFeed, setActivityFeed] = useState<unknown[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    checkSession().then((s) => setSession(s));
  }, []);

  const loadData = useCallback(async () => {
    try {
      setLoading(true);
      setError("");
      const isAdmin = session?.isAdmin ?? false;

      const promises: Promise<unknown>[] = [
        api.get("dashboard", "dashboard"),
        api.get("dashboard", "eventsNeedingResponse"),
        isAdmin ? api.get<AdminOverviewData>("dashboard", "getAdminOverview") : Promise.resolve(null),
        api.get<{ items?: unknown[] }>("dashboard", "getActivityFeed").catch(() => ({ items: [] })),
      ];

      const results = await Promise.all(promises);
      const dash = results[0] as { inbox?: unknown[]; news?: string };
      const needResp = results[1] as { events?: unknown[] };
      setDashboardData(dash);
      setEventsNeedingResponse(needResp?.events ?? []);

      setAdminOverview((isAdmin && results[2]) ? (results[2] as AdminOverviewData) : null);
      setActivityFeed((results[3] as { items?: unknown[] })?.items ?? []);
    } catch (err) {
      setError(getErrorMessage(err, t, "js.dashboard.loadError"));
    } finally {
      setLoading(false);
    }
  }, [session?.isAdmin, t]);

  useEffect(() => {
    if (!ready || !session) return;
    loadData();
  }, [ready, session, loadData]);

  return (
    <BandOverviewContent
      session={session}
      adminOverview={session?.isAdmin ? adminOverview : null}
      dashboardData={dashboardData}
      eventsNeedingResponse={eventsNeedingResponse}
      activityFeed={activityFeed}
      loading={loading}
      error={error}
      onReload={loadData}
    />
  );
}
