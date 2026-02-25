/**
 * BNote Next Generation - Dashboard Page (modules)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";
import { checkSession, type Session } from "@/lib/auth";
import { type InboxEvent } from "@/components/EventCard";
import { getErrorMessage } from "@/lib/error-utils";
import DashboardContent from "@/components/dashboard/DashboardContent";
import type { DashboardData, EventsNeedingResponse } from "@/components/dashboard/DashboardContent";

export default function DashboardPage() {
  const { t, ready } = useI18n();
  const [session, setSession] = useState<Session | null>(null);
  const [dashboard, setDashboard] = useState<DashboardData | null>(null);
  const [eventsNeedingResponse, setEventsNeedingResponse] = useState<InboxEvent[]>([]);
  const [needResponseConfig, setNeedResponseConfig] = useState<{ max_show?: number }>({});
  const [needResponseCounts, setNeedResponseCounts] = useState<{ rehearsal: number; performance: number; meeting: number }>({
    rehearsal: 0,
    performance: 0,
    meeting: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    checkSession().then((s) => setSession(s));
  }, []);

  const loadDashboard = useCallback(async () => {
    try {
      const [dash, needResp] = await Promise.all([
        api.get<DashboardData>("dashboard", "dashboard"),
        api.get<EventsNeedingResponse>("dashboard", "eventsNeedingResponse"),
      ]);
      setDashboard(dash);
      setEventsNeedingResponse(needResp?.events ?? []);
      setNeedResponseConfig(needResp?.config ?? {});
      setNeedResponseCounts(needResp?.counts ?? { rehearsal: 0, performance: 0, meeting: 0 });
    } catch (err) {
      setError(getErrorMessage(err, t, "js.dashboard.loadError"));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    if (!ready) return;
    loadDashboard();
  }, [ready, loadDashboard]);

  return (
    <DashboardContent
      session={session}
      dashboard={dashboard}
      needResponse={{
        events: eventsNeedingResponse,
        config: needResponseConfig,
        counts: needResponseCounts,
      }}
      loading={loading}
      error={error}
      onReload={loadDashboard}
      filterHiddenEvents={true}
    />
  );
}
