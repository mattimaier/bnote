/**
 * BNote Next Generation - Event entity actions (placeholder UI)
 *
 * Copyright (C) 2026 BNote Contributors
 */

export interface EntityAction {
  id: string;
  titleKey: string;
  descKey: string;
  icon: string;
  href: string;
  comingSoon?: boolean;
  colorClass?: string;
}

const DEFAULT_COLOR = "bg-primary/10 text-primary hover:bg-primary/20";

export const rehearsalViewActions: EntityAction[] = [
  { id: "series", titleKey: "js.event.actions.series", descKey: "js.event.actions.seriesDesc", icon: "calendar-days", href: "#", comingSoon: true, colorClass: DEFAULT_COLOR },
  { id: "participant-overview", titleKey: "js.event.actions.participantOverview", descKey: "js.event.actions.participantOverviewDesc", icon: "users", href: "#", comingSoon: true, colorClass: DEFAULT_COLOR },
  { id: "history", titleKey: "js.event.actions.history", descKey: "js.event.actions.historyDesc", icon: "history", href: "#", comingSoon: true, colorClass: DEFAULT_COLOR },
];

export const concertViewActions: EntityAction[] = [
  { id: "overview", titleKey: "js.event.actions.overview", descKey: "js.event.actions.overviewDesc", icon: "layout-list", href: "#", comingSoon: true, colorClass: DEFAULT_COLOR },
  { id: "programs", titleKey: "js.event.actions.programs", descKey: "js.event.actions.programsDesc", icon: "music", href: "#", comingSoon: true, colorClass: DEFAULT_COLOR },
  { id: "history", titleKey: "js.event.actions.history", descKey: "js.event.actions.historyDesc", icon: "history", href: "#", comingSoon: true, colorClass: DEFAULT_COLOR },
];

export function getEventViewActions(type: "rehearsal" | "concert"): EntityAction[] {
  return type === "concert" ? concertViewActions : rehearsalViewActions;
}
