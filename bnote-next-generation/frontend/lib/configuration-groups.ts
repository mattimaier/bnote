export type ConfigurationGroupId =
  | "general"
  | "notifications-reminders"
  | "events-calendar"
  | "public-feed"
  | "instruments-coverage"
  | "advanced-system";

export interface ConfigurationGroupDescriptor {
  id: ConfigurationGroupId;
  anchorId: string;
  titleKey: string;
  titleFallback: string;
  helpKey: string;
  helpFallback: string;
  moduleKey?: string;
  iconName?: string;
  iconColor?: string;
}

export const CONFIGURATION_GROUPS: ConfigurationGroupDescriptor[] = [
  {
    id: "general",
    anchorId: "cfg-general",
    titleKey: "js.configuration.group.general.title",
    titleFallback: "General",
    helpKey: "js.configuration.group.general.help",
    helpFallback: "Core defaults and behavior for all users.",
    moduleKey: "settings",
  },
  {
    id: "notifications-reminders",
    anchorId: "cfg-notifications-reminders",
    titleKey: "js.configuration.group.notifications.title",
    titleFallback: "Notifications and reminders",
    helpKey: "js.configuration.group.notifications.help",
    helpFallback: "Email digests, escalation thresholds, and reminder behavior.",
    iconName: "bell",
    iconColor: "#f59e0b",
  },
  {
    id: "events-calendar",
    anchorId: "cfg-events-calendar",
    titleKey: "js.configuration.group.eventsCalendar.title",
    titleFallback: "Events and calendar",
    helpKey: "js.configuration.group.eventsCalendar.help",
    helpFallback: "Event list behavior and calendar settings.",
    moduleKey: "calendar",
  },
  {
    id: "public-feed",
    anchorId: "cfg-public-feed",
    titleKey: "js.configuration.group.publicFeed.title",
    titleFallback: "Public feed",
    helpKey: "js.configuration.group.publicFeed.help",
    helpFallback: "External feed access and tokenized URL handling.",
    moduleKey: "share",
  },
  {
    id: "instruments-coverage",
    anchorId: "cfg-instruments-coverage",
    titleKey: "js.configuration.group.instruments.title",
    titleFallback: "Instruments and coverage",
    helpKey: "js.configuration.group.instruments.help",
    helpFallback: "Instrument catalog, setup, and minimum coverage controls.",
    iconName: "trumpet",
    iconColor: "#3399FF",
  },
  {
    id: "advanced-system",
    anchorId: "cfg-advanced-system",
    titleKey: "js.configuration.group.advanced.title",
    titleFallback: "Advanced and system",
    helpKey: "js.configuration.group.advanced.help",
    helpFallback: "Feature flags and legacy-readonly compatibility values.",
    moduleKey: "wrapped",
  },
];

const SECTION_TO_GROUP: Record<string, ConfigurationGroupId> = {
  defaults: "general",
  display: "general",
  notifications: "notifications-reminders",
  calendar: "events-calendar",
  public_concerts_feed: "public-feed",
  system: "general",
  feature_flags: "advanced-system",
};

export function getConfigurationGroupForSection(section: string): ConfigurationGroupId {
  const key = String(section || "").trim().toLowerCase();
  return SECTION_TO_GROUP[key] ?? "advanced-system";
}
