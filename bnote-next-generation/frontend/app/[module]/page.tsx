/**
 * BNote Next Generation - Single module route
 * Same routing pattern for all sidebar modules: /dashboard, /locations, /outfits, etc.
 *
 * Copyright (C) 2026 BNote Contributors
 */

import { notFound } from "next/navigation";
import type { ReactNode } from "react";
import DashboardPage from "@/app/_modules/dashboard/page";
import LocationsPage from "@/app/_modules/locations/page";
import ContactsPage from "@/app/(app)/contacts/page";
import UsersPage from "@/app/(app)/users/page";
import EquipmentPage from "@/app/_modules/equipment/page";
import OutfitsPage from "@/app/_modules/outfits/page";
import RepertoirePage from "@/app/_modules/repertoire/page";
import VotesPage from "@/app/_modules/votes/page";
import TasksPage from "@/app/_modules/tasks/page";
import ConcertsPage from "@/app/_modules/concerts/page";
import RehearsalsPage from "@/app/_modules/rehearsals/page";
import SearchPage from "@/app/_modules/search/page";
import SharePage from "@/app/_modules/share/page";
import NewsPage from "@/app/_modules/news/page";
import CalendarPage from "@/app/_modules/calendar/page";
import BandOverviewPage from "@/app/_modules/band-overview/page";

const MODULE_PAGES: Record<string, () => ReactNode> = {
  dashboard: () => <DashboardPage />,
  news: () => <NewsPage />,
  locations: () => <LocationsPage />,
  contacts: () => <ContactsPage />,
  users: () => <UsersPage />,
  equipment: () => <EquipmentPage />,
  outfits: () => <OutfitsPage />,
  repertoire: () => <RepertoirePage />,
  votes: () => <VotesPage />,
  tasks: () => <TasksPage />,
  concerts: () => <ConcertsPage />,
  rehearsals: () => <RehearsalsPage />,
  search: () => <SearchPage />,
  share: () => <SharePage />,
  calendar: () => <CalendarPage />,
  "band-overview": () => <BandOverviewPage />,
};

const MODULE_KEYS = Object.keys(MODULE_PAGES);

export async function generateStaticParams() {
  return MODULE_KEYS.map((module) => ({ module }));
}

interface PageProps {
  params: Promise<{ module: string }> | { module: string };
}

export default async function ModuleRoutePage(props: PageProps) {
  const params = await Promise.resolve(props.params);
  const moduleKey = params.module?.toLowerCase() ?? "";

  const render = MODULE_PAGES[moduleKey];
  if (!render) {
    notFound();
  }

  return <>{render()}</>;
}
