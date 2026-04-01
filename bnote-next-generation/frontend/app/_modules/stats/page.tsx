"use client";

import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { getErrorMessage } from "@/lib/error-utils";
import { statsApi, type StatsDashboardData } from "@/lib/stats-api";
import { StatsDashboardContent } from "@/components/dashboard/StatsDashboardContent";

export default function StatsModulePage() {
  const { t, ready } = useI18n();
  const [data, setData] = useState<StatsDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const currentYear = new Date().getFullYear();
  const [scope, setScope] = useState<"year" | "all">("year");
  const [year, setYear] = useState<number>(currentYear);

  const loadData = useCallback(async () => {
    try {
      setLoading(true);
      setError("");
      const response = await statsApi.getDashboard(scope, year);
      setData(response);
    } catch (err) {
      setError(getErrorMessage(err, t, "js.stats.loadError"));
    } finally {
      setLoading(false);
    }
  }, [scope, t, year]);

  useEffect(() => {
    if (!ready) return;
    void loadData();
  }, [ready, loadData]);

  return (
    <StatsDashboardContent
      data={data}
      loading={loading}
      error={error}
      scope={scope}
      year={year}
      onScopeChange={setScope}
      onYearChange={setYear}
      onReload={loadData}
    />
  );
}

