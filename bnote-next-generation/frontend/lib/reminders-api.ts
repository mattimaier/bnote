import { api } from "./api";

export interface ReminderConfig {
  enabled: boolean;
  weekday_utc: number;
  time_utc: string;
  recipient_scope: "actionable_only" | "all_opted_in";
  event_window_days: number;
  max_events: number;
  include_votes: boolean;
  max_votes: number;
  include_tasks: boolean;
  max_tasks: number;
}

export interface ReminderConfigResponse {
  isAdmin: boolean;
  config: ReminderConfig | null;
}

export interface ReminderRunResult {
  status: string;
  dryRun: boolean;
  runKey: string;
  ignore_limits?: boolean;
  only_user_id?: number | null;
  users_scanned?: number;
  users_eligible?: number;
  emails_sent?: number;
  skipped?: Record<string, number>;
}

export interface ReminderRecipient {
  id: number;
  name: string;
  email: string;
}

export const remindersApi = {
  getConfig: () => api.get<ReminderConfigResponse>("reminders", "getConfig"),
  getRecipients: () => api.get<{ recipients: ReminderRecipient[] }>("reminders", "getRecipients"),
  updateConfig: (config: Partial<ReminderConfig>) =>
    api.post<{ success: boolean; config: ReminderConfig }>("reminders", "updateConfig", config as Record<string, unknown>),
  runNow: (dryRun: boolean, force = true, onlyUserId?: number, ignoreLimits = false) =>
    api.post<ReminderRunResult>("reminders", "runNow", {
      dryRun,
      force,
      ignoreLimits,
      ...(typeof onlyUserId === "number" && onlyUserId > 0 ? { onlyUserId } : {}),
    }),
};
