import { api } from "./api";

export interface ReminderConfig {
  enabled: boolean;
  recipient_scope: "actionable_only" | "all_opted_in";
  event_window_days: number;
  max_events: number;
  include_votes: boolean;
  max_votes: number;
  include_tasks: boolean;
  max_tasks: number;
  escalation: {
    enabled: boolean;
    deadline_windows_hours: number[];
    dropout_window_hours: number;
    pending_threshold_percent: number;
    escalation_target_group_id: number;
    include_event_organizer: boolean;
  };
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

export interface EscalationGroup {
  id: number;
  name: string;
}

export interface EscalationAuditEntry {
  id: number;
  created_at: string;
  is_test: boolean;
  trigger_kind: string;
  delivery_mode: string;
  otype: string;
  oid: number;
  event_title: string;
  reason_summary: string;
  payload?: Record<string, unknown> | null;
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
  getEscalationGroups: () => api.get<{ groups: EscalationGroup[] }>("reminders", "getEscalationGroups"),
  runEscalationNow: (payload: {
    dryRun: boolean;
    force?: boolean;
    eventType?: "R" | "C";
    eventId?: number;
    testRecipients?: string[];
    isTest?: boolean;
  }) => api.post<Record<string, unknown>>("reminders", "runEscalationNow", payload as Record<string, unknown>),
  simulateEscalationDropout: (payload: {
    dryRun: boolean;
    eventType: "R" | "C";
    eventId: number;
    contactId?: number;
    testRecipients?: string[];
  }) => api.post<Record<string, unknown>>("reminders", "simulateEscalationDropout", payload as Record<string, unknown>),
  getEscalationEligibility: (eventType: "R" | "C", eventId: number) =>
    api.get<Record<string, unknown>>("reminders", "getEscalationEligibility", { eventType, eventId: String(eventId) }),
  getEscalationAudit: (limit = 50) =>
    api.get<{ entries: EscalationAuditEntry[] }>("reminders", "getEscalationAudit", { limit: String(limit) }),
};
