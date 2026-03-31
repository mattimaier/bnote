/**
 * BNote Next Generation — Preferences (notification opt-in)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { kontaktdatenApi } from "@/lib/kontaktdaten-api";
import { remindersApi, type ReminderConfig } from "@/lib/reminders-api";
import { checkSession } from "@/lib/auth";
import { DetailPageHeader } from "@/components/DetailPageHeader";
import { DetailSection } from "@/components/DetailSection";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

export default function SettingsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [emailNotification, setEmailNotification] = useState(true);
  const [isAdmin, setIsAdmin] = useState(false);
  const [reminderConfig, setReminderConfig] = useState<ReminderConfig | null>(null);
  const [savingReminderConfig, setSavingReminderConfig] = useState(false);
  const [runningReminder, setRunningReminder] = useState(false);
  const [runOutput, setRunOutput] = useState<string | null>(null);

  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [prefs, session] = await Promise.all([
        kontaktdatenApi.getUserPreferences(),
        checkSession(),
      ]);
      setEmailNotification(Boolean(prefs?.email_notification));
      const admin = Boolean(session?.isAdmin);
      setIsAdmin(admin);
      if (admin) {
        const cfgRes = await remindersApi.getConfig();
        setReminderConfig(cfgRes?.config ?? null);
      } else {
        setReminderConfig(null);
      }
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.settings.loadError"), "error");
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    load();
  }, [ready, load]);

  async function persist(next: boolean) {
    setSaving(true);
    try {
      await kontaktdatenApi.updateUserPreferences({ email_notification: next });
      setEmailNotification(next);
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function saveReminderConfig(patch: Partial<ReminderConfig>) {
    if (!reminderConfig) return;
    const next = { ...reminderConfig, ...patch };
    setReminderConfig(next);
    setSavingReminderConfig(true);
    try {
      const res = await remindersApi.updateConfig(next);
      setReminderConfig(res.config);
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
      try {
        const cfgRes = await remindersApi.getConfig();
        setReminderConfig(cfgRes?.config ?? null);
      } catch {
        // keep optimistic state if reload fails
      }
    } finally {
      setSavingReminderConfig(false);
    }
  }

  async function runReminderNow(dryRun: boolean) {
    setRunningReminder(true);
    setRunOutput(null);
    try {
      const out = await remindersApi.runNow(dryRun, true, undefined, false);
      setRunOutput(JSON.stringify(out, null, 2));
      showToast(
        dryRun
          ? label("js.settings.reminder.dryRunDone", "Dry run complete")
          : label("js.settings.reminder.sendDone", "Reminder run complete"),
        "success"
      );
    } catch (err) {
      setRunOutput(getErrorMessage(err, t, "js.common.saveFailed"));
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setRunningReminder(false);
    }
  }

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader title={label("js.settings.title", "Preferences")} />

      <DetailSection className="space-y-4">
        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.settings.sectionNotifications", "Notifications")}
          </span>
          <label className="mt-2 flex cursor-pointer items-start gap-2">
            <input
              type="checkbox"
              className="checkbox checkbox-primary checkbox-sm mt-0.5 shrink-0"
              checked={emailNotification}
              disabled={saving}
              aria-busy={saving}
              onChange={(e) => {
                void persist(e.target.checked);
              }}
            />
            <span className="min-w-0 flex-1 space-y-1">
              <span className="text-sm font-medium text-base-content block">
                {label(
                  "js.settings.emailNotificationsLabel",
                  "Email me about activity"
                )}
              </span>
              <span className="text-sm text-base-content/70 block leading-snug">
                {label(
                  "js.settings.emailNotificationsHelp",
                  "Task updates, discussion messages, and similar notices. You can turn this off anytime."
                )}
              </span>
            </span>
          </label>
        </div>

        <p className="text-xs text-base-content/50 leading-relaxed">
          {label(
            "js.settings.footerNote",
            "Account emails (sign-up, password reset, activation) may still be sent when required."
          )}
        </p>
      </DetailSection>

      {isAdmin && reminderConfig && (
        <DetailSection className="space-y-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.settings.reminder.sectionTitle", "Reminder Emails")}
            </span>
            <p className="mt-1 text-sm text-base-content/70">
              {label(
                "js.settings.reminder.sectionHelp",
                "Configure weekly summary emails sent by the external scheduler (UTC)."
              )}
            </p>
          </div>

          <label className="mt-1 flex cursor-pointer items-start gap-2">
            <input
              type="checkbox"
              className="checkbox checkbox-primary checkbox-sm mt-0.5 shrink-0"
              checked={Boolean(reminderConfig.enabled)}
              disabled={savingReminderConfig}
              onChange={(e) => {
                void saveReminderConfig({ enabled: e.target.checked });
              }}
            />
            <span className="text-sm text-base-content">
              {label("js.settings.reminder.enabled", "Enable weekly reminder emails")}
            </span>
          </label>

          <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.weekday", "Weekday (UTC)")}
              </span>
              <select
                className="select select-bordered select-sm"
                value={String(reminderConfig.weekday_utc)}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ weekday_utc: Number(e.target.value) });
                }}
              >
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thursday</option>
                <option value="5">Friday</option>
                <option value="6">Saturday</option>
                <option value="7">Sunday</option>
              </select>
            </label>
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.timeUtc", "Time (UTC)")}
              </span>
              <input
                type="time"
                className="input input-bordered input-sm"
                value={reminderConfig.time_utc}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ time_utc: e.target.value });
                }}
              />
            </label>
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.recipientScope", "Recipients")}
              </span>
              <select
                className="select select-bordered select-sm"
                value={reminderConfig.recipient_scope}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ recipient_scope: e.target.value as ReminderConfig["recipient_scope"] });
                }}
              >
                <option value="actionable_only">{label("js.settings.reminder.recipient.actionable", "Users with upcoming events (open responses optional)")}</option>
                <option value="all_opted_in">{label("js.settings.reminder.recipient.all", "All opted-in active users")}</option>
              </select>
            </label>
          </div>

          <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.windowDays", "Event window (days)")}
              </span>
              <input
                type="number"
                min={1}
                max={180}
                className="input input-bordered input-sm"
                value={reminderConfig.event_window_days}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ event_window_days: Number(e.target.value) });
                }}
              />
            </label>
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.maxEvents", "Max events")}
              </span>
              <input
                type="number"
                min={1}
                max={50}
                className="input input-bordered input-sm"
                value={reminderConfig.max_events}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ max_events: Number(e.target.value) });
                }}
              />
            </label>
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.maxVotes", "Max votes")}
              </span>
              <input
                type="number"
                min={1}
                max={50}
                className="input input-bordered input-sm"
                value={reminderConfig.max_votes}
                disabled={savingReminderConfig || !reminderConfig.include_votes}
                onChange={(e) => {
                  void saveReminderConfig({ max_votes: Number(e.target.value) });
                }}
              />
            </label>
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.maxTasks", "Max tasks")}
              </span>
              <input
                type="number"
                min={1}
                max={50}
                className="input input-bordered input-sm"
                value={reminderConfig.max_tasks}
                disabled={savingReminderConfig || !reminderConfig.include_tasks}
                onChange={(e) => {
                  void saveReminderConfig({ max_tasks: Number(e.target.value) });
                }}
              />
            </label>
          </div>

          <div className="flex flex-wrap items-center gap-5">
            <label className="flex cursor-pointer items-center gap-2">
              <input
                type="checkbox"
                className="checkbox checkbox-primary checkbox-sm"
                checked={Boolean(reminderConfig.include_votes)}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ include_votes: e.target.checked });
                }}
              />
              <span className="text-sm text-base-content/80">{label("js.settings.reminder.includeVotes", "Include votes")}</span>
            </label>
            <label className="flex cursor-pointer items-center gap-2">
              <input
                type="checkbox"
                className="checkbox checkbox-primary checkbox-sm"
                checked={Boolean(reminderConfig.include_tasks)}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ include_tasks: e.target.checked });
                }}
              />
              <span className="text-sm text-base-content/80">{label("js.settings.reminder.includeTasks", "Include tasks")}</span>
            </label>
          </div>

          <div className="border border-base-300 rounded-box p-3 space-y-3">
            <p className="text-sm text-base-content/70">
              {label("js.settings.reminder.manualRunHelp", "Test now without waiting for scheduler runs.")}
            </p>
            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                className="btn btn-soft btn-sm"
                disabled={runningReminder}
                onClick={() => {
                  void runReminderNow(true);
                }}
              >
                {runningReminder ? "Running…" : label("js.settings.reminder.runDry", "Run dry-run")}
              </button>
              <button
                type="button"
                className="btn btn-soft btn-primary btn-sm"
                disabled={runningReminder}
                onClick={() => {
                  void runReminderNow(false);
                }}
              >
                {runningReminder ? "Sending…" : label("js.settings.reminder.runReal", "Send now")}
              </button>
            </div>
            {runOutput ? (
              <pre className="max-h-56 overflow-auto rounded-box bg-base-200/70 p-3 text-xs font-mono whitespace-pre-wrap">
                {runOutput}
              </pre>
            ) : null}
          </div>
        </DetailSection>
      )}
    </div>
  );
}
