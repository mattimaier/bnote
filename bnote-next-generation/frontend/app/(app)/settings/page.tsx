/**
 * BNote Next Generation — Preferences (notification opt-in)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { kontaktdatenApi } from "@/lib/kontaktdaten-api";
import { contactsApi } from "@/lib/contacts-api";
import { remindersApi, type EscalationGroup, type ReminderConfig } from "@/lib/reminders-api";
import { checkSession } from "@/lib/auth";
import { DetailPageHeader } from "@/components/DetailPageHeader";
import { DetailSection } from "@/components/DetailSection";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

interface EscalationConfigDraft {
  enabled: boolean;
  pending_threshold_percent: number;
  dropout_window_hours: number;
  escalation_target_group_id: number;
}

export default function SettingsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [emailNotification, setEmailNotification] = useState(true);
  const [isAdmin, setIsAdmin] = useState(false);
  const [reminderConfig, setReminderConfig] = useState<ReminderConfig | null>(null);
  const [escalationGroups, setEscalationGroups] = useState<EscalationGroup[]>([]);
  const [escalationDraft, setEscalationDraft] = useState<EscalationConfigDraft | null>(null);
  const [savingReminderConfig, setSavingReminderConfig] = useState(false);
  const [savingEscalation, setSavingEscalation] = useState(false);
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
        setEscalationDraft(cfgRes?.config?.escalation ? {
          enabled: Boolean(cfgRes.config.escalation.enabled),
          pending_threshold_percent: Number(cfgRes.config.escalation.pending_threshold_percent ?? 20),
          dropout_window_hours: Number(cfgRes.config.escalation.dropout_window_hours ?? 24),
          escalation_target_group_id: Number(cfgRes.config.escalation.escalation_target_group_id ?? 0),
        } : null);
        let groups: EscalationGroup[] = [];
        try {
          const groupsRes = await remindersApi.getEscalationGroups();
          if (Array.isArray(groupsRes?.groups)) {
            groups = groupsRes.groups
              .map((g) => ({ id: Number(g.id) || 0, name: String(g.name ?? "") }))
              .filter((g) => g.id > 0 && g.name.trim() !== "");
          }
        } catch {
          // fall back below
        }
        if (groups.length === 0) {
          try {
            const fallback = await contactsApi.getGroups();
            if (Array.isArray(fallback)) {
              groups = fallback
                .map((g) => ({ id: Number(g.id) || 0, name: String(g.name ?? "") }))
                .filter((g) => g.id > 0 && g.name.trim() !== "");
            }
          } catch {
            // keep empty if fallback also fails
          }
        }
        setEscalationGroups(groups);
      } else {
        setReminderConfig(null);
        setEscalationGroups([]);
        setEscalationDraft(null);
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
      if (res.config?.escalation) {
        setEscalationDraft({
          enabled: Boolean(res.config.escalation.enabled),
          pending_threshold_percent: Number(res.config.escalation.pending_threshold_percent ?? 20),
          dropout_window_hours: Number(res.config.escalation.dropout_window_hours ?? 24),
          escalation_target_group_id: Number(res.config.escalation.escalation_target_group_id ?? 0),
        });
      }
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

  async function saveEscalationConfig() {
    if (!reminderConfig || !escalationDraft) return;
    setSavingEscalation(true);
    try {
      const res = await remindersApi.updateConfig({
        escalation: {
          ...reminderConfig.escalation,
          ...escalationDraft,
          include_event_organizer: false,
        },
      });
      setReminderConfig(res.config);
      setEscalationDraft({
        enabled: Boolean(res.config.escalation?.enabled),
        pending_threshold_percent: Number(res.config.escalation?.pending_threshold_percent ?? 20),
        dropout_window_hours: Number(res.config.escalation?.dropout_window_hours ?? 24),
        escalation_target_group_id: Number(res.config.escalation?.escalation_target_group_id ?? 0),
      });
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingEscalation(false);
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
        <>
        <DetailSection className="space-y-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.settings.reminder.sectionTitle", "Reminder Emails")}
            </span>
            <p className="mt-1 text-sm text-base-content/70">
              {label(
                "js.settings.reminder.sectionHelp",
                "Configure weekly summary emails sent by the external scheduler."
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

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.recipientScope", "Recipients")}
              </span>
              <select
                className="select select-bordered"
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
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.windowDays", "Event window (days)")}
              </span>
              <input
                type="number"
                min={1}
                max={180}
                className="input input-bordered"
                value={reminderConfig.event_window_days}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ event_window_days: Number(e.target.value) });
                }}
              />
            </label>
          </div>

          <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
            <label className="form-control">
              <span className="label-text text-xs font-medium text-base-content/70">
                {label("js.settings.reminder.maxEvents", "Max events")}
              </span>
              <input
                type="number"
                min={1}
                max={99}
                className="input input-bordered"
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
                max={99}
                className="input input-bordered"
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
                max={99}
                className="input input-bordered"
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
                  className="btn btn-soft"
                disabled={runningReminder}
                onClick={() => {
                  void runReminderNow(true);
                }}
              >
                {runningReminder ? "Running…" : label("js.settings.reminder.runDry", "Run dry-run")}
              </button>
              <button
                type="button"
                  className="btn btn-soft btn-primary"
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

        <DetailSection className="space-y-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.settings.escalation.title", "Escalation alerts")}
            </span>
            <p className="mt-1 text-sm text-base-content/70">
              {label(
                "js.settings.escalation.help",
                "Escalate at-risk events to a dedicated group. Scheduling is handled by GitHub workflow."
              )}
            </p>
          </div>

          <div className="overflow-x-auto rounded-box border border-base-300">
            <table className="table">
              <tbody>
                <tr>
                  <th className="w-72 align-middle text-sm font-medium text-base-content/80">
                    {label("js.settings.escalation.enabled", "Enable escalation alerts")}
                  </th>
                  <td className="align-middle">
                    <label className="flex cursor-pointer items-center gap-3">
                      <input
                        type="checkbox"
                        className="checkbox checkbox-primary checkbox-sm"
                        checked={Boolean(escalationDraft?.enabled)}
                        disabled={savingEscalation}
                        onChange={(e) => {
                          setEscalationDraft((prev) =>
                            prev ? { ...prev, enabled: e.target.checked } : prev
                          );
                        }}
                      />
                      <span className="text-sm text-base-content/70">
                        {label("js.settings.escalation.enabledHelp", "Turn escalation warning emails on or off.")}
                      </span>
                    </label>
                  </td>
                </tr>
                <tr>
                  <th className="align-middle text-sm font-medium text-base-content/80">
                    {label("js.settings.escalation.group", "Escalation group")}
                  </th>
                  <td className="align-middle">
                    <select
                      className="select select-bordered w-full max-w-xl"
                      value={String(escalationDraft?.escalation_target_group_id ?? 0)}
                      disabled={savingEscalation}
                      onChange={(e) => {
                        setEscalationDraft((prev) =>
                          prev
                            ? { ...prev, escalation_target_group_id: Number(e.target.value) }
                            : prev
                        );
                      }}
                    >
                      <option value="0">{label("js.settings.escalation.groupNone", "Select group…")}</option>
                      {escalationGroups.map((g) => (
                        <option key={g.id} value={g.id}>
                          {g.name}
                        </option>
                      ))}
                    </select>
                  </td>
                </tr>
                <tr>
                  <th className="align-middle text-sm font-medium text-base-content/80">
                    {label("js.settings.escalation.pendingThreshold", "Pending threshold (%)")}
                  </th>
                  <td className="align-middle">
                    <input
                      type="number"
                      min={1}
                      max={100}
                      className="input input-bordered w-full max-w-xs"
                      value={Number(escalationDraft?.pending_threshold_percent ?? 20)}
                      disabled={savingEscalation}
                      onChange={(e) => {
                        setEscalationDraft((prev) =>
                          prev
                            ? { ...prev, pending_threshold_percent: Number(e.target.value) }
                            : prev
                        );
                      }}
                    />
                  </td>
                </tr>
                <tr>
                  <th className="align-middle text-sm font-medium text-base-content/80">
                    {label("js.settings.escalation.dropoutWindow", "Dropout window (hours)")}
                  </th>
                  <td className="align-middle">
                    <input
                      type="number"
                      min={1}
                      max={240}
                      className="input input-bordered w-full max-w-xs"
                      value={Number(escalationDraft?.dropout_window_hours ?? 24)}
                      disabled={savingEscalation}
                      onChange={(e) => {
                        setEscalationDraft((prev) =>
                          prev
                            ? { ...prev, dropout_window_hours: Number(e.target.value) }
                            : prev
                        );
                      }}
                    />
                  </td>
                </tr>
                <tr>
                  <th className="align-middle text-sm font-medium text-base-content/80">
                    {label("js.settings.escalation.minimums", "Instrument minimums")}
                  </th>
                  <td className="align-middle">
                    <div className="flex flex-wrap items-center gap-3">
                      <Link
                        href="/settings/instrument-minimums"
                        className="btn btn-soft btn-primary"
                      >
                        {label("js.settings.escalation.minimumsOpen", "Open instrument minimum table")}
                      </Link>
                      <span className="text-sm text-base-content/70">
                        {label("js.settings.escalation.minimumsHelpShort", "Manage defaults in a dedicated table view.")}
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              className="btn btn-soft btn-primary"
              disabled={savingEscalation || !escalationDraft}
              onClick={() => {
                void saveEscalationConfig();
              }}
            >
              {savingEscalation ? label("js.common.saving", "Saving…") : label("js.common.save", "Save")}
            </button>
          </div>
        </DetailSection>
        </>
      )}
    </div>
  );
}
