"use client";

import { useEffect, useMemo, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { type Session } from "@/lib/auth";
import { normalizeCompany } from "@/lib/dashboard-utils";
import { AppPageHeader } from "@/components/AppPageHeader";

interface CompanySource {
  company?: string | Record<string, string> | string[];
}

interface DashboardGreetingProps {
  session: Session | null;
  dashboard: CompanySource | null;
  pendingResponses: number;
  upcomingEvents: number;
  upcomingRehearsals: number;
  upcomingConcerts: number;
  todayDueTasks: number;
  todayRehearsals: number;
  todayConcerts: number;
}

export function DashboardGreeting({
  session,
  dashboard,
  pendingResponses,
  upcomingEvents,
  upcomingRehearsals,
  upcomingConcerts,
  todayDueTasks,
  todayRehearsals,
  todayConcerts,
}: DashboardGreetingProps) {
  const { t } = useI18n();
  const countKey = (baseKey: string, count: number): string =>
    `${baseKey}.${count === 1 ? "one" : "other"}`;
  const [selectedSubtitleKey, setSelectedSubtitleKey] = useState<string | null>(null);

  const greeting = useMemo(() => {
    const hour = new Date().getHours();
    const morning = t("js.common.greeting.morning");
    const afternoon = t("js.common.greeting.afternoon");
    const evening = t("js.common.greeting.evening");
    if (hour < 12) return morning !== "js.common.greeting.morning" ? morning : "Good morning";
    if (hour < 18) return afternoon !== "js.common.greeting.afternoon" ? afternoon : "Good afternoon";
    return evening !== "js.common.greeting.evening" ? evening : "Good evening";
  }, [t]);

  const firstName = session?.user?.name || t("js.common.user");
  const companyName = String(normalizeCompany(dashboard?.company) || t("js.common.appName")).trim();

  const subtitleVariants = useMemo(
    () => [
      { key: "default", text: t("js.dashboard.dynamicSubtitle.default", [companyName]), kind: "fallback" as const },
      {
        key: "pendingOpen",
        text: t(countKey("js.dashboard.dynamicSubtitle.pendingOpen", pendingResponses), [companyName, String(pendingResponses)]),
        enabled: pendingResponses > 0,
        kind: "signal" as const,
      },
      {
        key: "confirmToday",
        text: t(countKey("js.dashboard.dynamicSubtitle.confirmToday", pendingResponses), [companyName, String(pendingResponses)]),
        enabled: pendingResponses > 0,
        kind: "signal" as const,
      },
      {
        key: "upcomingRehearsals",
        text: t(countKey("js.dashboard.dynamicSubtitle.upcomingRehearsals", upcomingRehearsals), [companyName, String(upcomingRehearsals)]),
        enabled: upcomingRehearsals > 0,
        kind: "signal" as const,
      },
      {
        key: "upcomingConcerts",
        text: t(countKey("js.dashboard.dynamicSubtitle.upcomingConcerts", upcomingConcerts), [companyName, String(upcomingConcerts)]),
        enabled: upcomingConcerts > 0,
        kind: "signal" as const,
      },
      {
        key: "upcomingEvents",
        text: t(countKey("js.dashboard.dynamicSubtitle.upcomingEvents", upcomingEvents), [companyName, String(upcomingEvents)]),
        enabled: upcomingEvents > 0,
        kind: "signal" as const,
      },
      { key: "prepared", text: t("js.dashboard.dynamicSubtitle.prepared", [companyName]), kind: "fallback" as const },
    ],
    [t, companyName, pendingResponses, upcomingRehearsals, upcomingConcerts, upcomingEvents]
  );

  const eligibleSubtitles = subtitleVariants.filter((variant) => variant.enabled !== false);

  useEffect(() => {
    if (selectedSubtitleKey) return;
    if (eligibleSubtitles.length === 0) return;
    // Fresh random pick per page load.
    const picked = eligibleSubtitles[Math.floor(Math.random() * eligibleSubtitles.length)];
    const nextKey = picked?.key ?? null;
    if (!nextKey) return;
    setSelectedSubtitleKey(nextKey);
  }, [eligibleSubtitles, selectedSubtitleKey]);

  const selectedSubtitle = eligibleSubtitles.find((variant) => variant.key === selectedSubtitleKey);
  const baseSubtitle =
    selectedSubtitle?.text ||
    eligibleSubtitles[0]?.text ||
    t("js.dashboard.dynamicSubtitle.default", [companyName]);

  const todayParts: string[] = [];
  if (todayDueTasks > 0) todayParts.push(t("js.dashboard.todayHighlight.tasks", [String(todayDueTasks)]));
  if (todayRehearsals > 0) todayParts.push(t("js.dashboard.todayHighlight.rehearsals", [String(todayRehearsals)]));
  if (todayConcerts > 0) todayParts.push(t("js.dashboard.todayHighlight.concerts", [String(todayConcerts)]));

  const todayLine =
    todayParts.length > 0
      ? `${t("js.dashboard.todayHighlight.prefix")} ${todayParts.join(" · ")}`
      : "";
  const subtitle = todayLine ? `${baseSubtitle}. ${todayLine}` : baseSubtitle;

  return (
    <AppPageHeader
      title={`${greeting}, ${firstName}`}
      subtitle={subtitle}
    />
  );
}
