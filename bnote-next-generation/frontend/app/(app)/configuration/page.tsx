"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";
import { DetailSection } from "@/components/DetailSection";
import { Spinner } from "@/components/Spinner";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { checkSession } from "@/lib/auth";
import { configurationApi, type ConfigurationResponse } from "@/lib/configuration-api";
import { remindersApi, type EscalationGroup, type ReminderConfig } from "@/lib/reminders-api";
import { getEscalationWarningUiConfig } from "@/lib/entity-config";
import { TablerIconByName } from "@/components/icons";
import { getModuleHeadlineConfig } from "@/lib/module-headline-config";

interface EscalationConfigDraft {
  enabled: boolean;
  pending_threshold_percent: number;
  dropout_window_hours: number;
  escalation_target_group_id: number;
  include_event_organizer: boolean;
  warning_window_hours: number;
  critical_window_hours: number;
}

const SECTION_LABELS: Record<string, string> = {
  notifications: "Notifications",
  calendar: "Calendar and time",
  defaults: "Defaults and registration",
  display: "Display options",
  system: "System options",
};

const PARAM_HELP_FALLBACK: Record<string, string> = {
  rehearsal_show_max: "Used on Dashboard and Response Needed lists: controls how many rehearsal items are shown per load step.",
  concert_show_max: "Used on Dashboard and Response Needed lists: controls how many concert items are shown per load step.",
  beta_bug_report_enabled: "Show a global bug-report action for logged-in beta testers.",
  beta_bug_report_email: "Destination inbox for beta bug reports.",
};
const LOCALE_COUNTRY_PARAM = "default_country";

export default function ConfigurationPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const moduleConfig = getModuleHeadlineConfig("configuration");
  const [loading, setLoading] = useState(true);
  const [savingConfig, setSavingConfig] = useState(false);
  const [savingReminderConfig, setSavingReminderConfig] = useState(false);
  const [savingEscalation, setSavingEscalation] = useState(false);
  const [savingCalendarTimezone, setSavingCalendarTimezone] = useState(false);
  const [runningReminder, setRunningReminder] = useState(false);
  const [runOutput, setRunOutput] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [config, setConfig] = useState<ConfigurationResponse | null>(null);
  const [configDraft, setConfigDraft] = useState<Record<string, unknown>>({});
  const [reminderConfig, setReminderConfig] = useState<ReminderConfig | null>(null);
  const [escalationGroups, setEscalationGroups] = useState<EscalationGroup[]>([]);
  const [escalationDraft, setEscalationDraft] = useState<EscalationConfigDraft | null>(null);
  const [calendarTimezone, setCalendarTimezone] = useState("Europe/Berlin");

  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const session = await checkSession();
      if (!session?.authenticated) {
        showToast(label("js.common.notFound", "Not found"), "error");
        setLoading(false);
        return;
      }

      const [cfgRes, reminderRes, timezoneRes, groupsRes] = await Promise.all([
        configurationApi.getConfig(),
        remindersApi.getConfig(),
        remindersApi.getCalendarTimezone(),
        remindersApi.getEscalationGroups().catch(() => ({ groups: [] as EscalationGroup[] })),
      ]);

      setConfig(cfgRes);
      setConfigDraft(cfgRes?.values ?? {});
      setReminderConfig(reminderRes?.config ?? null);
      setCalendarTimezone(
        typeof timezoneRes?.timezone === "string" && timezoneRes.timezone.trim() !== ""
          ? timezoneRes.timezone.trim()
          : "Europe/Berlin"
      );

      const groups = Array.isArray(groupsRes?.groups)
        ? groupsRes.groups
            .map((g) => ({ id: Number(g.id) || 0, name: String(g.name ?? "") }))
            .filter((g) => g.id > 0 && g.name.trim() !== "")
        : [];
      setEscalationGroups(groups);

      const esc = reminderRes?.config?.escalation;
      setEscalationDraft(
        esc
          ? {
              enabled: Boolean(esc.enabled),
              pending_threshold_percent: Number(esc.pending_threshold_percent ?? 20),
              dropout_window_hours: Number(esc.dropout_window_hours ?? 24),
              escalation_target_group_id: Number(esc.escalation_target_group_id ?? 0),
              include_event_organizer: Boolean(esc.include_event_organizer ?? true),
              warning_window_hours: Number(esc.deadline_windows_hours?.[0] ?? 48),
              critical_window_hours: Number(esc.deadline_windows_hours?.[1] ?? 12),
            }
          : null
      );
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.settings.loadError"), "error");
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    void load();
  }, [ready, load]);

  async function saveConfiguration() {
    setSavingConfig(true);
    try {
      const next = await configurationApi.updateConfig(configDraft);
      setConfig(next);
      setConfigDraft(next.values);
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingConfig(false);
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
      void load();
    } finally {
      setSavingReminderConfig(false);
    }
  }

  async function saveEscalationConfig() {
    if (!reminderConfig || !escalationDraft) return;
    setSavingEscalation(true);
    try {
      const warningHours = Math.max(1, Number(escalationDraft.warning_window_hours || 48));
      const criticalHours = Math.max(1, Number(escalationDraft.critical_window_hours || 12));
      const windows = warningHours >= criticalHours ? [warningHours, criticalHours] : [criticalHours, warningHours];
      const { warning_window_hours, critical_window_hours, ...restDraft } = escalationDraft;
      const res = await remindersApi.updateConfig({
        escalation: {
          ...reminderConfig.escalation,
          ...restDraft,
          deadline_windows_hours: windows,
        },
      });
      setReminderConfig(res.config);
      setEscalationDraft((prev) => prev ? { ...prev, warning_window_hours: windows[0], critical_window_hours: windows[1] } : prev);
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingEscalation(false);
    }
  }

  async function saveCalendarTimezone() {
    const input = calendarTimezone.trim();
    if (input === "") {
      showToast(label("js.common.saveFailed", "Save failed"), "error");
      return;
    }
    setSavingCalendarTimezone(true);
    try {
      const res = await remindersApi.updateCalendarTimezone(input);
      setCalendarTimezone(res.timezone || input);
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingCalendarTimezone(false);
    }
  }

  async function runReminderNow(dryRun: boolean) {
    setRunningReminder(true);
    setRunOutput(null);
    try {
      const out = await remindersApi.runNow(dryRun, true, undefined, false);
      setRunOutput(JSON.stringify(out, null, 2));
      showToast(dryRun ? label("js.settings.reminder.dryRunDone", "Dry run complete") : label("js.settings.reminder.sendDone", "Reminder run complete"), "success");
    } catch (err) {
      setRunOutput(getErrorMessage(err, t, "js.common.saveFailed"));
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setRunningReminder(false);
    }
  }

  const groupedParameters = useMemo(() => {
    const out: Record<string, ConfigurationResponse["parameters"]> = {};
    (config?.parameters ?? [])
      .filter((p) => p.used_in_nextgen && p.param !== LOCALE_COUNTRY_PARAM)
      .forEach((p) => {
      if (!out[p.section]) out[p.section] = [];
      out[p.section].push(p);
    });
    return out;
  }, [config]);

  const legacyReadonlyParameters = useMemo(
    () => (config?.parameters ?? []).filter((p) => !p.used_in_nextgen),
    [config]
  );

  const filteredGroupedParameters = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    if (q === "") return groupedParameters;
    const out: Record<string, ConfigurationResponse["parameters"]> = {};
    Object.entries(groupedParameters).forEach(([section, params]) => {
      const sectionName = label(`js.configuration.section.${section}`, SECTION_LABELS[section] ?? section).toLowerCase();
      const filtered = params.filter((p) => {
        const localized = label(`js.configuration.param.${p.param}`, p.caption).toLowerCase();
        return (
          localized.includes(q) ||
          p.param.toLowerCase().includes(q) ||
          sectionName.includes(q)
        );
      });
      if (filtered.length > 0) out[section] = filtered;
    });
    return out;
  }, [groupedParameters, searchQuery]);

  const filteredLegacyReadonlyParameters = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    if (q === "") return legacyReadonlyParameters;
    const sectionName = label("js.configuration.legacyReadonly.title", "Legacy-only configuration (read-only)").toLowerCase();
    return legacyReadonlyParameters.filter((p) => {
      const localized = label(`js.configuration.param.${p.param}`, p.caption).toLowerCase();
      return localized.includes(q) || p.param.toLowerCase().includes(q) || sectionName.includes(q);
    });
  }, [legacyReadonlyParameters, searchQuery]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={`${PAGE_CONTENT_CLASS} space-y-6`}>
      <div>
        <h1 className="text-2xl font-bold break-words whitespace-normal leading-tight text-base-content">
          <span className="inline-flex items-center gap-3">
            {moduleConfig && (
              <span
                className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border"
                style={{
                  color: moduleConfig.color,
                  borderColor: `color-mix(in oklch, ${moduleConfig.color} 30%, transparent)`,
                  background: `color-mix(in oklch, ${moduleConfig.color} 14%, transparent)`,
                }}
              >
                <TablerIconByName name={moduleConfig.icon} className="h-5 w-5" />
              </span>
            )}
            <span>{label("js.configuration.title", "Configuration")}</span>
          </span>
        </h1>
      </div>

      <div className="flex justify-end">
        <button type="button" className="btn btn-soft btn-primary" disabled={savingConfig} onClick={() => void saveConfiguration()}>
          {savingConfig ? label("js.common.saving", "Saving…") : label("js.common.save", "Save")}
        </button>
      </div>

      <DetailSection className="space-y-4">
        <p className="text-sm text-base-content/70">
          {label("js.configuration.help", "System-wide configuration. Changes affect all users.")}
        </p>
        <label className="form-control max-w-2xl">
          <span className="label-text text-xs font-medium text-base-content/70">
            {label("js.configuration.search.label", "Search settings")}
          </span>
          <input
            type="search"
            className="input input-bordered mb-2"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder={label("js.configuration.search.placeholder", "Search by setting name or key")}
          />
        </label>
      </DetailSection>

      <DetailSection className="space-y-4">
        <div>
          <span className="text-lg font-bold text-base-content">
            {label("js.configuration.locale.title", "Locale and timezone")}
          </span>
          <p className="mt-1 text-sm text-base-content/70">
            {label("js.configuration.locale.help", "Country and timezone defaults. Language selection will be added here next.")}
          </p>
        </div>
        <label className="form-control max-w-2xl">
          <span className="label-text text-xs font-medium text-base-content/70">
            {label("js.configuration.param.default_country", "Default country")}
          </span>
          <input
            type="text"
            className="input input-bordered mb-2"
            value={String(configDraft[LOCALE_COUNTRY_PARAM] ?? "")}
            onChange={(e) => setConfigDraft((prev) => ({ ...prev, [LOCALE_COUNTRY_PARAM]: e.target.value }))}
          />
          <p className="mt-2 text-xs text-base-content/60">
            {label("js.configuration.locale.countryHelp", "Saved with the main Save button at the top.")}
          </p>
        </label>
        <div className="flex flex-wrap items-end gap-4">
          <label className="form-control w-full max-w-sm">
            <span className="label-text text-xs font-medium text-base-content/70">
              {label("js.settings.calendarTimezone.label", "IANA timezone")}
            </span>
            <input
              type="text"
              className="input input-bordered mb-2"
              value={calendarTimezone}
              disabled={savingCalendarTimezone}
              onChange={(e) => setCalendarTimezone(e.target.value)}
              placeholder="Europe/Berlin"
            />
          </label>
          <button
            type="button"
            className="btn btn-soft btn-primary self-end"
            disabled={savingCalendarTimezone || calendarTimezone.trim() === ""}
            onClick={() => void saveCalendarTimezone()}
          >
            {savingCalendarTimezone ? label("js.common.saving", "Saving…") : label("js.common.save", "Save timezone")}
          </button>
        </div>
      </DetailSection>

      {Object.entries(filteredGroupedParameters).map(([section, params]) => (
        <DetailSection key={section} className="space-y-4">
          <h3 className="text-lg font-bold text-base-content">
            {label(`js.configuration.section.${section}`, SECTION_LABELS[section] ?? section)}
          </h3>
          <div className="space-y-4">
            {params.map((p) => {
              const value = configDraft[p.param];
              const help = PARAM_HELP_FALLBACK[p.param];
              if (p.type === "boolean") {
                return (
                  <div key={p.param}>
                    <label className="flex cursor-pointer items-center gap-2 min-w-0">
                      <input
                        type="checkbox"
                        className="checkbox checkbox-primary checkbox-sm"
                        checked={Boolean(value)}
                        onChange={(e) => setConfigDraft((prev) => ({ ...prev, [p.param]: e.target.checked }))}
                      />
                      <span className="text-sm">{label(`js.configuration.param.${p.param}`, p.caption)}</span>
                    </label>
                    {help ? (
                      <p className="mt-2 text-xs text-base-content/60">
                        {label(`js.configuration.paramHelp.${p.param}`, help)}
                      </p>
                    ) : null}
                  </div>
                );
              }
              if (p.type === "reference_group" || p.type === "reference_conductor") {
                const options = p.type === "reference_group" ? config?.options?.groups ?? [] : config?.options?.conductors ?? [];
                return (
                  <label key={p.param} className="form-control max-w-2xl">
                    <span className="label-text text-xs font-medium text-base-content/70">
                      {label(`js.configuration.param.${p.param}`, p.caption)}
                    </span>
                    <select
                      className="select select-bordered mb-2"
                      value={String(Number(value) || 0)}
                      onChange={(e) => setConfigDraft((prev) => ({ ...prev, [p.param]: Number(e.target.value) }))}
                    >
                      {options.map((o) => (
                        <option key={o.id} value={o.id}>
                          {o.name}
                        </option>
                      ))}
                    </select>
                    {help ? (
                      <p className="mt-2 text-xs text-base-content/60">
                        {label(`js.configuration.paramHelp.${p.param}`, help)}
                      </p>
                    ) : null}
                  </label>
                );
              }
              return (
                <label key={p.param} className="form-control max-w-2xl">
                  <span className="label-text text-xs font-medium text-base-content/70">
                    {label(`js.configuration.param.${p.param}`, p.caption)}
                  </span>
                  <input
                    type={p.type === "integer" ? "number" : "text"}
                    className="input input-bordered mb-2"
                    value={String(value ?? "")}
                    onChange={(e) =>
                      setConfigDraft((prev) => ({
                        ...prev,
                        [p.param]: p.type === "integer" ? Number(e.target.value) : e.target.value,
                      }))
                    }
                  />
                  {help ? (
                    <p className="mt-2 text-xs text-base-content/60">
                      {label(`js.configuration.paramHelp.${p.param}`, help)}
                    </p>
                  ) : null}
                </label>
              );
            })}
          </div>
        </DetailSection>
      ))}

      {Object.keys(filteredGroupedParameters).length === 0 && filteredLegacyReadonlyParameters.length === 0 ? (
        <DetailSection>
          <p className="text-sm text-base-content/70">
            {label("js.configuration.search.noResults", "No settings match your search.")}
          </p>
        </DetailSection>
      ) : null}

      {reminderConfig && (
        <>
          <DetailSection className="space-y-4">
            <div>
              <span className="text-lg font-bold text-base-content">
                {label("js.settings.reminder.sectionTitle", "Reminder Emails")}
              </span>
              <p className="mt-2 text-sm text-base-content/70">
                {label("js.settings.reminder.sectionHelp", "Configure regular reminder/digest emails. Triggered by external server scheduler.")}
              </p>
            </div>
            <label className="mt-1 flex cursor-pointer items-center gap-2 min-w-0">
              <input
                type="checkbox"
                className="checkbox checkbox-primary checkbox-sm shrink-0"
                checked={Boolean(reminderConfig.enabled)}
                disabled={savingReminderConfig}
                onChange={(e) => {
                  void saveReminderConfig({ enabled: e.target.checked });
                }}
              />
              <span className="text-sm text-base-content">
                {label("js.settings.reminder.enabled", "Enable regular reminder/digest emails")}
              </span>
            </label>
            <div className="space-y-4">
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70">
                  {label("js.settings.reminder.recipientScope", "Recipients")}
                </span>
                <select
                  className="select select-bordered mb-2"
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
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70">
                  {label("js.settings.reminder.windowDays", "Event window (days)")}
                </span>
                <input
                  type="number"
                  min={1}
                  max={180}
                  className="input input-bordered mb-2"
                  value={reminderConfig.event_window_days}
                  disabled={savingReminderConfig}
                  onChange={(e) => {
                    void saveReminderConfig({ event_window_days: Number(e.target.value) });
                  }}
                />
              </label>
            </div>
            <div className="flex flex-wrap gap-2">
              <button type="button" className="btn btn-soft" disabled={runningReminder} onClick={() => void runReminderNow(true)}>
                {runningReminder ? "Running…" : label("js.settings.reminder.runDry", "Run dry-run")}
              </button>
              <button type="button" className="btn btn-soft btn-primary" disabled={runningReminder} onClick={() => void runReminderNow(false)}>
                {runningReminder ? "Sending…" : label("js.settings.reminder.runReal", "Send now")}
              </button>
            </div>
            {runOutput ? (
              <pre className="max-h-56 overflow-auto rounded-box bg-base-200/70 p-3 text-xs font-mono whitespace-pre-wrap">{runOutput}</pre>
            ) : null}
          </DetailSection>

          <DetailSection className="space-y-4">
            <div>
              <span className="text-lg font-bold text-base-content">
                {label("js.settings.escalation.title", "Escalation alerts")}
              </span>
              <p className="mt-2 text-sm text-base-content/70">
                {label("js.settings.escalation.help", "Escalation warnings are triggered by an external server scheduler.")}
              </p>
            </div>
            <div className="space-y-4">
              <label className="flex cursor-pointer items-center gap-2 max-w-2xl">
                <input
                  type="checkbox"
                  className="checkbox checkbox-primary checkbox-sm"
                  checked={Boolean(escalationDraft?.enabled)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, enabled: e.target.checked } : prev))}
                />
                <span className="text-sm">
                  {label("js.settings.escalation.enabled", "Enable escalation alerts")}
                </span>
              </label>
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70">
                  {label("js.settings.escalation.group", "Escalation group")}
                </span>
                <select
                  className="select select-bordered mb-2"
                  value={String(escalationDraft?.escalation_target_group_id ?? 0)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, escalation_target_group_id: Number(e.target.value) } : prev))}
                >
                  <option value="0">{label("js.settings.escalation.groupNone", "Select group…")}</option>
                  {escalationGroups.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name}
                    </option>
                  ))}
                </select>
              </label>
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70">
                  {label("js.settings.escalation.pendingThreshold", "Open-response threshold (%)")}
                </span>
                <input
                  type="number"
                  min={1}
                  max={100}
                  className="input input-bordered mb-2"
                  value={Number(escalationDraft?.pending_threshold_percent ?? 20)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, pending_threshold_percent: Number(e.target.value) } : prev))}
                />
              </label>
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70">
                  {label("js.settings.escalation.dropoutWindow", "Late-dropout window (hours)")}
                </span>
                <input
                  type="number"
                  min={1}
                  max={240}
                  className="input input-bordered mb-2"
                  value={Number(escalationDraft?.dropout_window_hours ?? 24)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, dropout_window_hours: Number(e.target.value) } : prev))}
                />
              </label>
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70 inline-flex items-center gap-2 flex-wrap leading-tight">
                  <span
                    className="inline-flex h-5 w-5 items-center justify-center rounded-full border"
                    style={getEscalationWarningUiConfig("soon").badgeStyle}
                  >
                    <TablerIconByName
                      name={getEscalationWarningUiConfig("soon").iconName}
                      className={`h-3.5 w-3.5 ${getEscalationWarningUiConfig("soon").iconClassName}`}
                    />
                  </span>
                  {label("js.settings.escalation.warningWindowHours", "Warning window (hours)")}
                </span>
                <input
                  type="number"
                  min={1}
                  max={240}
                  className="input input-bordered mb-2"
                  value={Number(escalationDraft?.warning_window_hours ?? 48)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, warning_window_hours: Number(e.target.value) } : prev))}
                />
              </label>
              <label className="form-control max-w-2xl">
                <span className="label-text text-xs font-medium text-base-content/70 inline-flex items-center gap-2 flex-wrap leading-tight">
                  <span
                    className="inline-flex h-5 w-5 items-center justify-center rounded-full border"
                    style={getEscalationWarningUiConfig("critical").badgeStyle}
                  >
                    <TablerIconByName
                      name={getEscalationWarningUiConfig("critical").iconName}
                      className={`h-3.5 w-3.5 ${getEscalationWarningUiConfig("critical").iconClassName}`}
                    />
                  </span>
                  {label("js.settings.escalation.criticalWindowHours", "Critical window (hours)")}
                </span>
                <input
                  type="number"
                  min={1}
                  max={240}
                  className="input input-bordered mb-2"
                  value={Number(escalationDraft?.critical_window_hours ?? 12)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, critical_window_hours: Number(e.target.value) } : prev))}
                />
              </label>
              <label className="flex cursor-pointer items-center gap-2 max-w-2xl">
                <input
                  type="checkbox"
                  className="checkbox checkbox-primary checkbox-sm"
                  checked={Boolean(escalationDraft?.include_event_organizer)}
                  disabled={savingEscalation}
                  onChange={(e) => setEscalationDraft((prev) => (prev ? { ...prev, include_event_organizer: e.target.checked } : prev))}
                />
                <span className="text-sm">
                  {label("js.configuration.escalation.includeOrganizer", "Include event organizer")}
                </span>
              </label>
            </div>
            <div className="flex flex-wrap items-center gap-3">
              <button type="button" className="btn btn-soft btn-primary" disabled={savingEscalation} onClick={() => void saveEscalationConfig()}>
                {savingEscalation ? label("js.common.saving", "Saving…") : label("js.common.save", "Save")}
              </button>
              <Link href="/settings/instrument-minimums" className="btn btn-soft">
                {label("js.settings.escalation.minimumsOpen", "Open instrument minimum table")}
              </Link>
            </div>
          </DetailSection>

        </>
      )}

      {filteredLegacyReadonlyParameters.length > 0 ? (
          <DetailSection className="space-y-4">
          <div>
            <span className="text-lg font-bold text-base-content">
              {label("js.configuration.legacyReadonly.title", "Legacy-only configuration (read-only)")}
            </span>
            <p className="mt-1 text-sm text-base-content/70">
              {label("js.configuration.legacyReadonly.help", "These settings are used by the old app only and are shown here for visibility.")}
            </p>
          </div>
          <div className="space-y-4">
            {filteredLegacyReadonlyParameters.map((p) => {
              const value = configDraft[p.param];
              if (p.type === "boolean") {
                return (
                  <label key={p.param} className="flex items-center gap-2 opacity-75">
                    <input type="checkbox" className="checkbox checkbox-primary checkbox-sm" checked={Boolean(value)} disabled />
                    <span className="text-sm">{label(`js.configuration.param.${p.param}`, p.caption)}</span>
                  </label>
                );
              }
              if (p.type === "reference_group" || p.type === "reference_conductor") {
                const options = p.type === "reference_group" ? config?.options?.groups ?? [] : config?.options?.conductors ?? [];
                return (
                  <label key={p.param} className="form-control max-w-2xl opacity-75">
                    <span className="label-text text-xs font-medium text-base-content/70">
                      {label(`js.configuration.param.${p.param}`, p.caption)}
                    </span>
                    <select className="select select-bordered mb-2" value={String(Number(value) || 0)} disabled>
                      {options.map((o) => (
                        <option key={o.id} value={o.id}>
                          {o.name}
                        </option>
                      ))}
                    </select>
                  </label>
                );
              }
              return (
                <label key={p.param} className="form-control max-w-2xl opacity-75">
                  <span className="label-text text-xs font-medium text-base-content/70">
                    {label(`js.configuration.param.${p.param}`, p.caption)}
                  </span>
                  <input type="text" className="input input-bordered mb-2" value={String(value ?? "")} disabled />
                </label>
              );
            })}
          </div>
        </DetailSection>
      ) : null}
    </div>
  );
}
