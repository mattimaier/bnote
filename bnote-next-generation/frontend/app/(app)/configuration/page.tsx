"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";
import { AppPageHeader } from "@/components/AppPageHeader";
import { DetailSection } from "@/components/DetailSection";
import { ConfirmModal } from "@/components/ConfirmModal";
import { Spinner } from "@/components/Spinner";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { checkSession } from "@/lib/auth";
import { getApiPhpDirectoryUrl } from "@/lib/api";
import { useSectionAutosave } from "@/lib/use-section-autosave";
import {
  CONFIGURATION_GROUPS,
  type ConfigurationGroupId,
  getConfigurationGroupForSection,
} from "@/lib/configuration-groups";
import {
  configurationApi,
  type ConfigurationResponse,
  type InstrumentAdminCategory,
  type InstrumentAdminInstrument,
  type InstrumentSectionConfig,
} from "@/lib/configuration-api";
import { remindersApi, type EscalationGroup, type ReminderConfig } from "@/lib/reminders-api";
import { getEscalationWarningUiConfig } from "@/lib/entity-config";
import { TablerIconByName } from "@/components/icons";

interface EscalationConfigDraft {
  enabled: boolean;
  pending_threshold_percent: { rehearsal: number; concert: number };
  dropout_window_hours: { rehearsal: number; concert: number };
  escalation_target_group_id: number;
  include_event_organizer: boolean;
  warning_window_hours: { rehearsal: number; concert: number };
  critical_window_hours: { rehearsal: number; concert: number };
}

const SECTION_LABELS: Record<string, string> = {
  notifications: "Notifications",
  calendar: "Calendar and time",
  defaults: "Defaults and registration",
  display: "Display options",
  system: "System options",
  feature_flags: "Feature flags",
  public_concerts_feed: "Public concerts feed",
};

const PARAM_HELP_FALLBACK: Record<string, string> = {
  rehearsal_show_max:
    "Used on Dashboard and Response Needed lists: controls how many rehearsal items are shown per load step.",
  concert_show_max:
    "Used on Dashboard and Response Needed lists: controls how many concert items are shown per load step.",
  beta_bug_report_enabled: "Show a global bug-report action for logged-in beta testers.",
  beta_bug_report_email: "Destination inbox for beta bug reports.",
};
const LOCALE_COUNTRY_PARAM = "default_country";

export default function ConfigurationPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [runningReminder, setRunningReminder] = useState(false);
  const [runOutput, setRunOutput] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [config, setConfig] = useState<ConfigurationResponse | null>(null);
  const [configDraft, setConfigDraft] = useState<Record<string, unknown>>({});
  const [confirmedConfigDraft, setConfirmedConfigDraft] = useState<Record<string, unknown>>({});
  const [reminderConfig, setReminderConfig] = useState<ReminderConfig | null>(null);
  const [confirmedReminderConfig, setConfirmedReminderConfig] = useState<ReminderConfig | null>(null);
  const [escalationGroups, setEscalationGroups] = useState<EscalationGroup[]>([]);
  const [escalationDraft, setEscalationDraft] = useState<EscalationConfigDraft | null>(null);
  const [confirmedEscalationDraft, setConfirmedEscalationDraft] = useState<EscalationConfigDraft | null>(null);
  const [calendarTimezone, setCalendarTimezone] = useState("Europe/Berlin");
  const [confirmedCalendarTimezone, setConfirmedCalendarTimezone] = useState("Europe/Berlin");
  const [instrumentCategories, setInstrumentCategories] = useState<InstrumentAdminCategory[]>([]);
  const [instrumentItems, setInstrumentItems] = useState<InstrumentAdminInstrument[]>([]);
  const [pendingCategoryDelete, setPendingCategoryDelete] = useState<InstrumentAdminCategory | null>(null);
  const [pendingInstrumentDelete, setPendingInstrumentDelete] = useState<InstrumentAdminInstrument | null>(null);
  const [sections, setSections] = useState<InstrumentSectionConfig[]>([]);
  const [newCategoryName, setNewCategoryName] = useState("");
  const [newInstrumentName, setNewInstrumentName] = useState("");
  const [newInstrumentCategoryId, setNewInstrumentCategoryId] = useState(0);
  const [savingInstrumentAdmin, setSavingInstrumentAdmin] = useState(false);
  const [regeneratingPublicConcertsToken, setRegeneratingPublicConcertsToken] = useState(false);
  const [confirmRegeneratePublicConcertsToken, setConfirmRegeneratePublicConcertsToken] = useState(false);

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

      const [cfgRes, reminderRes, timezoneRes, groupsRes, instrumentAdminRes] = await Promise.all([
        configurationApi.getConfig(),
        remindersApi.getConfig(),
        remindersApi.getCalendarTimezone(),
        remindersApi.getEscalationGroups().catch(() => ({ groups: [] as EscalationGroup[] })),
        configurationApi.getInstrumentAdminData().catch(() => null),
      ]);

      setConfig(cfgRes);
      setConfigDraft(cfgRes?.values ?? {});
      setConfirmedConfigDraft(cfgRes?.values ?? {});
      setReminderConfig(reminderRes?.config ?? null);
      setConfirmedReminderConfig(reminderRes?.config ?? null);
      setCalendarTimezone(
        typeof timezoneRes?.timezone === "string" && timezoneRes.timezone.trim() !== ""
          ? timezoneRes.timezone.trim()
          : "Europe/Berlin"
      );
      setConfirmedCalendarTimezone(
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
      const pendingRaw = esc?.pending_threshold_percent as unknown;
      const dropoutRaw = esc?.dropout_window_hours as unknown;
      const deadlineRaw = esc?.deadline_windows_hours as unknown;
      const pendingRehearsal =
        typeof pendingRaw === "object" && pendingRaw !== null
          ? Number((pendingRaw as Record<string, unknown>).rehearsal ?? 20)
          : Number(pendingRaw ?? 20);
      const pendingConcert =
        typeof pendingRaw === "object" && pendingRaw !== null
          ? Number((pendingRaw as Record<string, unknown>).concert ?? pendingRehearsal)
          : Number(pendingRaw ?? 20);
      const dropoutRehearsal =
        typeof dropoutRaw === "object" && dropoutRaw !== null
          ? Number((dropoutRaw as Record<string, unknown>).rehearsal ?? 24)
          : Number(dropoutRaw ?? 24);
      const dropoutConcert =
        typeof dropoutRaw === "object" && dropoutRaw !== null
          ? Number((dropoutRaw as Record<string, unknown>).concert ?? dropoutRehearsal)
          : Number(dropoutRaw ?? 24);
      const rehearsalWindows = Array.isArray((deadlineRaw as Record<string, unknown> | null)?.rehearsal)
        ? ((deadlineRaw as Record<string, unknown>).rehearsal as unknown[])
        : Array.isArray(deadlineRaw)
          ? (deadlineRaw as unknown[])
          : [48, 12];
      const concertWindows = Array.isArray((deadlineRaw as Record<string, unknown> | null)?.concert)
        ? ((deadlineRaw as Record<string, unknown>).concert as unknown[])
        : rehearsalWindows;
      setEscalationDraft(
        esc
          ? {
              enabled: Boolean(esc.enabled),
              pending_threshold_percent: {
                rehearsal: Number(pendingRehearsal),
                concert: Number(pendingConcert),
              },
              dropout_window_hours: {
                rehearsal: Number(dropoutRehearsal),
                concert: Number(dropoutConcert),
              },
              escalation_target_group_id: Number(esc.escalation_target_group_id ?? 0),
              include_event_organizer: Boolean(esc.include_event_organizer ?? true),
              warning_window_hours: {
                rehearsal: Number(rehearsalWindows?.[0] ?? 48),
                concert: Number(concertWindows?.[0] ?? rehearsalWindows?.[0] ?? 48),
              },
              critical_window_hours: {
                rehearsal: Number(rehearsalWindows?.[1] ?? 12),
                concert: Number(concertWindows?.[1] ?? rehearsalWindows?.[1] ?? 12),
              },
            }
          : null
      );
      setConfirmedEscalationDraft(
        esc
          ? {
              enabled: Boolean(esc.enabled),
              pending_threshold_percent: {
                rehearsal: Number(pendingRehearsal),
                concert: Number(pendingConcert),
              },
              dropout_window_hours: {
                rehearsal: Number(dropoutRehearsal),
                concert: Number(dropoutConcert),
              },
              escalation_target_group_id: Number(esc.escalation_target_group_id ?? 0),
              include_event_organizer: Boolean(esc.include_event_organizer ?? true),
              warning_window_hours: {
                rehearsal: Number(rehearsalWindows?.[0] ?? 48),
                concert: Number(concertWindows?.[0] ?? rehearsalWindows?.[0] ?? 48),
              },
              critical_window_hours: {
                rehearsal: Number(rehearsalWindows?.[1] ?? 12),
                concert: Number(concertWindows?.[1] ?? rehearsalWindows?.[1] ?? 12),
              },
            }
          : null
      );
      setInstrumentCategories(Array.isArray(instrumentAdminRes?.categories) ? instrumentAdminRes.categories : []);
      setInstrumentItems(Array.isArray(instrumentAdminRes?.instruments) ? instrumentAdminRes.instruments : []);
      const nextSections = Array.isArray(instrumentAdminRes?.sections) ? instrumentAdminRes.sections : [];
      setSections(nextSections);
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

  function normalizeEscalationDraft(input: EscalationConfigDraft): {
    draft: EscalationConfigDraft;
    deadline_windows_hours: { rehearsal: number[]; concert: number[] };
  } {
    const warningRehearsal = Math.max(1, Number(input.warning_window_hours.rehearsal || 48));
    const criticalRehearsal = Math.max(1, Number(input.critical_window_hours.rehearsal || 12));
    const warningConcert = Math.max(1, Number(input.warning_window_hours.concert || 48));
    const criticalConcert = Math.max(1, Number(input.critical_window_hours.concert || 12));
    const rehearsalWindows =
      warningRehearsal >= criticalRehearsal
        ? [warningRehearsal, criticalRehearsal]
        : [criticalRehearsal, warningRehearsal];
    const concertWindows =
      warningConcert >= criticalConcert ? [warningConcert, criticalConcert] : [criticalConcert, warningConcert];
    return {
      draft: {
        ...input,
        warning_window_hours: {
          rehearsal: rehearsalWindows[0],
          concert: concertWindows[0],
        },
        critical_window_hours: {
          rehearsal: rehearsalWindows[1],
          concert: concertWindows[1],
        },
      },
      deadline_windows_hours: {
        rehearsal: rehearsalWindows,
        concert: concertWindows,
      },
    };
  }

  const configAutosave = useSectionAutosave({
    value: configDraft,
    enabled: !loading && config != null,
    save: async (nextValues) => {
      const next = await configurationApi.updateConfig(nextValues);
      setConfig(next);
      setConfirmedConfigDraft(next.values);
    },
    onError: (err) => {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
      setConfigDraft(confirmedConfigDraft);
    },
  });

  const reminderAutosave = useSectionAutosave({
    value: reminderConfig,
    enabled: !loading && reminderConfig != null,
    save: async (nextReminderConfig) => {
      if (!nextReminderConfig) return;
      const res = await remindersApi.updateConfig(nextReminderConfig);
      setReminderConfig(res.config);
      setConfirmedReminderConfig(res.config);
    },
    onError: (err) => {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
      setReminderConfig(confirmedReminderConfig);
    },
  });

  const escalationAutosave = useSectionAutosave({
    value: escalationDraft,
    enabled: !loading && reminderConfig != null && escalationDraft != null,
    save: async (nextEscalationDraft) => {
      if (!nextEscalationDraft || !reminderConfig) return;
      const normalized = normalizeEscalationDraft(nextEscalationDraft);
      const { warning_window_hours, critical_window_hours, ...restDraft } = normalized.draft;
      const res = await remindersApi.updateConfig({
        escalation: {
          ...reminderConfig.escalation,
          ...restDraft,
          deadline_windows_hours: normalized.deadline_windows_hours,
        },
      });
      setReminderConfig(res.config);
      setEscalationDraft(normalized.draft);
      setConfirmedEscalationDraft(normalized.draft);
    },
    onError: (err) => {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
      setEscalationDraft(confirmedEscalationDraft);
    },
  });

  const timezoneAutosave = useSectionAutosave({
    value: calendarTimezone,
    enabled: !loading,
    shouldSave: (value) => value.trim() !== "",
    save: async (nextTimezone) => {
      const input = nextTimezone.trim();
      if (input === "") return;
      const res = await remindersApi.updateCalendarTimezone(input);
      setCalendarTimezone(res.timezone || input);
      setConfirmedCalendarTimezone(res.timezone || input);
    },
    onError: (err) => {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
      setCalendarTimezone(confirmedCalendarTimezone);
    },
  });

  async function regeneratePublicConcertsFeedToken() {
    setRegeneratingPublicConcertsToken(true);
    try {
      const next = await configurationApi.regeneratePublicConcertsFeedToken();
      setConfig(next);
      setConfigDraft(next.values);
      showToast(
        label("js.configuration.publicConcerts.rotateSuccess", "Public concerts feed token rotated."),
        "success"
      );
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setRegeneratingPublicConcertsToken(false);
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

  async function reloadInstrumentAdminData() {
    try {
      const res = await configurationApi.getInstrumentAdminData();
      setInstrumentCategories(Array.isArray(res?.categories) ? res.categories : []);
      setInstrumentItems(Array.isArray(res?.instruments) ? res.instruments : []);
      const nextSections = Array.isArray(res?.sections) ? res.sections : [];
      setSections(nextSections);
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToLoad"), "error");
    }
  }

  async function createCategory() {
    const name = newCategoryName.trim();
    if (name === "") return;
    setSavingInstrumentAdmin(true);
    try {
      await configurationApi.createCategory(name);
      setNewCategoryName("");
      await reloadInstrumentAdminData();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingInstrumentAdmin(false);
    }
  }

  async function deleteCategory(id: number) {
    setSavingInstrumentAdmin(true);
    try {
      await configurationApi.deleteCategory(id);
      await reloadInstrumentAdminData();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.deleteFailed"), "error");
    } finally {
      setSavingInstrumentAdmin(false);
    }
  }

  async function createInstrument() {
    const name = newInstrumentName.trim();
    if (name === "") return;
    setSavingInstrumentAdmin(true);
    try {
      await configurationApi.createInstrument({
        name,
        category_id: newInstrumentCategoryId > 0 ? newInstrumentCategoryId : 0,
        rank: 0,
      });
      setNewInstrumentName("");
      await reloadInstrumentAdminData();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingInstrumentAdmin(false);
    }
  }

  async function deleteInstrument(id: number) {
    setSavingInstrumentAdmin(true);
    try {
      await configurationApi.deleteInstrument(id);
      await reloadInstrumentAdminData();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.deleteFailed"), "error");
    } finally {
      setSavingInstrumentAdmin(false);
    }
  }

  async function seedInstrumentDefaults() {
    setSavingInstrumentAdmin(true);
    try {
      await configurationApi.seedInstrumentDefaults();
      await reloadInstrumentAdminData();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingInstrumentAdmin(false);
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
      const sectionName = label(
        `js.configuration.section.${section}`,
        SECTION_LABELS[section] ?? section
      ).toLowerCase();
      const filtered = params.filter((p) => {
        const localized = label(`js.configuration.param.${p.param}`, p.caption).toLowerCase();
        return localized.includes(q) || p.param.toLowerCase().includes(q) || sectionName.includes(q);
      });
      if (filtered.length > 0) out[section] = filtered;
    });
    return out;
  }, [groupedParameters, searchQuery]);

  const filteredLegacyReadonlyParameters = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    if (q === "") return legacyReadonlyParameters;
    const sectionName = label(
      "js.configuration.legacyReadonly.title",
      "Legacy-only configuration (read-only)"
    ).toLowerCase();
    return legacyReadonlyParameters.filter((p) => {
      const localized = label(`js.configuration.param.${p.param}`, p.caption).toLowerCase();
      return localized.includes(q) || p.param.toLowerCase().includes(q) || sectionName.includes(q);
    });
  }, [legacyReadonlyParameters, searchQuery]);

  const groupedByUx = useMemo(() => {
    const out: Record<ConfigurationGroupId, Array<[string, ConfigurationResponse["parameters"]]>> = {
      general: [],
      "notifications-reminders": [],
      "events-calendar": [],
      "public-feed": [],
      "instruments-coverage": [],
      "advanced-system": [],
    };
    Object.entries(filteredGroupedParameters).forEach(([section, params]) => {
      const group = getConfigurationGroupForSection(section);
      out[group].push([section, params]);
    });
    const sectionOrder: Record<string, number> = {
      system: 0,
      defaults: 1,
      display: 2,
      notifications: 3,
      calendar: 4,
      public_concerts_feed: 5,
      feature_flags: 6,
    };
    (Object.keys(out) as ConfigurationGroupId[]).forEach((group) => {
      out[group].sort((a, b) => (sectionOrder[a[0]] ?? 99) - (sectionOrder[b[0]] ?? 99));
    });
    return out;
  }, [filteredGroupedParameters]);

  const visibleGroupIds = useMemo(() => {
    const ids: ConfigurationGroupId[] = [];
    const q = searchQuery.trim().toLowerCase();
    CONFIGURATION_GROUPS.forEach((group) => {
      const hasParams = groupedByUx[group.id].length > 0;
      const hasStatic =
        group.id === "general" ||
        (group.id === "notifications-reminders" && reminderConfig != null) ||
        group.id === "instruments-coverage" ||
        (group.id === "advanced-system" && filteredLegacyReadonlyParameters.length > 0);
      const matchesGroupName = q === "" ? true : label(group.titleKey, group.titleFallback).toLowerCase().includes(q);
      if ((hasParams || hasStatic) && (q === "" || hasParams || hasStatic || matchesGroupName)) {
        ids.push(group.id);
      }
    });
    return ids;
  }, [groupedByUx, searchQuery, reminderConfig, filteredLegacyReadonlyParameters, label]);

  const statusLabel = (status: "idle" | "saving" | "saved" | "error") => {
    if (status === "saving") return label("js.configuration.autosave.saving", "Saving...");
    if (status === "saved") return label("js.configuration.autosave.saved", "Saved");
    if (status === "error") return label("js.configuration.autosave.error", "Error");
    return label("js.configuration.autosave.ready", "Live");
  };

  const headingWithIcon = (title: string, iconName: string) => (
    <h2 className="inline-flex items-center gap-2 text-lg font-semibold text-base-content">
      <span className="inline-flex h-7 w-7 items-center justify-center rounded-full border border-base-300 bg-base-200/60">
        <TablerIconByName name={iconName} className="h-4 w-4 text-base-content/70" />
      </span>
      <span>{title}</span>
    </h2>
  );

  const featureFlagsParams = filteredGroupedParameters.feature_flags ?? [];
  const advancedSystemSections = groupedByUx["advanced-system"].filter(([section]) => section !== "feature_flags");

  const renderConfigParamInput = (p: ConfigurationResponse["parameters"][number]) => {
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
            <p className="mt-2 text-xs text-base-content/60">{label(`js.configuration.paramHelp.${p.param}`, help)}</p>
          ) : null}
        </div>
      );
    }
    if (p.type === "reference_group" || p.type === "reference_conductor") {
      const options =
        p.type === "reference_group" ? (config?.options?.groups ?? []) : (config?.options?.conductors ?? []);
      return (
        <label key={p.param} className="form-control max-w-2xl">
          <span className="label-text text-xs font-medium text-base-content/70">
            {label(`js.configuration.param.${p.param}`, p.caption)}
          </span>
          <select
            className="select select-bordered mb-2"
            value={String(Number(value) || 0)}
            onChange={(e) => setConfigDraft((prev) => ({ ...prev, [p.param]: Number(e.target.value) }))}
            onBlur={() => void configAutosave.flush()}
          >
            {options.map((o) => (
              <option key={o.id} value={o.id}>
                {o.name}
              </option>
            ))}
          </select>
          {help ? (
            <p className="mt-2 text-xs text-base-content/60">{label(`js.configuration.paramHelp.${p.param}`, help)}</p>
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
          onBlur={() => void configAutosave.flush()}
          onKeyDown={(e) => {
            if (e.key === "Enter") {
              void configAutosave.flush();
            }
          }}
        />
        {help ? (
          <p className="mt-2 text-xs text-base-content/60">{label(`js.configuration.paramHelp.${p.param}`, help)}</p>
        ) : null}
      </label>
    );
  };

  const sectionCoverageEnabled =
    configDraft.beta_section_coverage_enabled === true ||
    configDraft.beta_section_coverage_enabled === 1 ||
    configDraft.beta_section_coverage_enabled === "1";
  const publicConcertsFeedEnabled =
    configDraft.public_gigs_feed_enabled === true ||
    configDraft.public_gigs_feed_enabled === 1 ||
    configDraft.public_gigs_feed_enabled === "1";
  const derivedPublicConcertsFeedUrl = String(config?.derived?.publicConcertsFeedUrl ?? "").trim();
  const publicConcertsFeedUrl =
    derivedPublicConcertsFeedUrl !== ""
      ? derivedPublicConcertsFeedUrl
      : `${getApiPhpDirectoryUrl()}/public-concerts.json.php`;
  const derivedPublicConcertsFeedTokenizedUrl = String(config?.derived?.publicConcertsFeedTokenizedUrl ?? "").trim();
  const publicConcertsFeedTokenizedUrl =
    derivedPublicConcertsFeedTokenizedUrl !== "" ? derivedPublicConcertsFeedTokenizedUrl : publicConcertsFeedUrl;
  const publicConcertsFeedRangeExampleUrl = `${publicConcertsFeedTokenizedUrl}${
    publicConcertsFeedTokenizedUrl.includes("?") ? "&" : "?"
  }from=2026-01-01&to=2026-12-31`;

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={`${PAGE_CONTENT_CLASS} space-y-6`}>
      <AppPageHeader moduleKey="configuration" title={label("js.configuration.title", "Configuration")} />

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

      <div className="space-y-6">
        <div id="cfg-general">
          <DetailSection className="space-y-4">
            <div className="space-y-1">
              {headingWithIcon(label("js.configuration.group.general.title", "General"), "settings")}
              <p className="text-sm text-base-content/70">
                {label("js.configuration.group.general.help", "Core defaults and behavior for all users.")}
              </p>
              <p className="text-xs text-base-content/60">{statusLabel(configAutosave.status)}</p>
            </div>
            <div>
              <span className="text-lg font-bold text-base-content">
                {label("js.configuration.locale.title", "Locale and timezone")}
              </span>
              <p className="mt-1 text-sm text-base-content/70">
                {label(
                  "js.configuration.locale.help",
                  "Country and timezone defaults. Language selection will be added here next."
                )}
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
                onBlur={() => void configAutosave.flush()}
                onKeyDown={(e) => {
                  if (e.key === "Enter") {
                    void configAutosave.flush();
                  }
                }}
              />
              <p className="mt-2 text-xs text-base-content/60">
                {label("js.configuration.locale.countryHelp", "Saved automatically after your changes.")}
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
                  onChange={(e) => setCalendarTimezone(e.target.value)}
                  onBlur={() => void timezoneAutosave.flush()}
                  onKeyDown={(e) => {
                    if (e.key === "Enter") {
                      void timezoneAutosave.flush();
                    }
                  }}
                  placeholder="Europe/Berlin"
                />
              </label>
              <span className="text-xs text-base-content/60 pb-2">{statusLabel(timezoneAutosave.status)}</span>
            </div>
            {groupedByUx.general.map(([section, params]) => (
              <div key={`general-${section}`} className="rounded-box border border-base-300 p-3 space-y-4">
                <h3 className="text-base font-semibold text-base-content">
                  {label(`js.configuration.section.${section}`, SECTION_LABELS[section] ?? section)}
                </h3>
                <div className="space-y-4">{params.map((p) => renderConfigParamInput(p))}</div>
              </div>
            ))}
          </DetailSection>
        </div>

        {CONFIGURATION_GROUPS.filter(
          (group) =>
            !["general", "notifications-reminders", "instruments-coverage", "advanced-system"].includes(group.id) &&
            visibleGroupIds.includes(group.id)
        ).map((group) => (
          <div key={group.id} id={group.anchorId} className="">
            <DetailSection className="space-y-5">
              <div className="space-y-1">
                {headingWithIcon(
                  label(group.titleKey, group.titleFallback),
                  group.id === "events-calendar" ? "calendar-days" : "share-2"
                )}
                <p className="text-sm text-base-content/70">{label(group.helpKey, group.helpFallback)}</p>
              </div>
              {groupedByUx[group.id].map(([section, params]) => (
                <div key={`${group.id}-${section}`} className="rounded-box border border-base-300 p-3 space-y-4">
                  <h3 className="text-base font-semibold text-base-content">
                    {label(`js.configuration.section.${section}`, SECTION_LABELS[section] ?? section)}
                  </h3>
                  <div className="space-y-4">
                    {params.map((p) => renderConfigParamInput(p))}
                    {section === "public_concerts_feed" ? (
                      <div className="rounded-box border border-base-300 p-3 space-y-2">
                        <p className="text-sm font-medium text-base-content">
                          {label("js.configuration.publicConcerts.feedUrl", "Public concerts feed URL")}
                        </p>
                        <input
                          type="text"
                          className="input input-bordered w-full font-mono text-xs"
                          value={publicConcertsFeedTokenizedUrl}
                          readOnly
                        />
                        <p className="text-xs text-base-content/60">
                          {publicConcertsFeedEnabled
                            ? label(
                                "js.configuration.publicConcerts.feedHintEnabled",
                                "Feed is enabled. Only requests with a valid tokenized URL can read it."
                              )
                            : label(
                                "js.configuration.publicConcerts.feedHintDisabled",
                                "Feed is disabled and returns access denied until enabled."
                              )}
                        </p>
                        <div className="rounded-box bg-base-200/40 p-3">
                          <p className="text-xs font-semibold text-base-content">
                            {label("js.configuration.publicConcerts.paramsTitle", "Optional URL parameters")}
                          </p>
                          <ul className="mt-2 list-disc space-y-1 pl-5 text-xs text-base-content/70">
                            <li>
                              <code>from=YYYY-MM-DD</code>{" "}
                              {label(
                                "js.configuration.publicConcerts.paramsFromHelp",
                                "Include concerts that end on or after this day."
                              )}
                            </li>
                            <li>
                              <code>to=YYYY-MM-DD</code>{" "}
                              {label(
                                "js.configuration.publicConcerts.paramsToHelp",
                                "Include concerts that start on or before this day."
                              )}
                            </li>
                            <li>
                              {label(
                                "js.configuration.publicConcerts.paramsRangeHelp",
                                "Using both returns concerts overlapping the date range."
                              )}
                            </li>
                          </ul>
                          <p className="mt-2 text-xs text-base-content/70">
                            {label("js.configuration.publicConcerts.paramsExample", "Example:")}{" "}
                            <code>{publicConcertsFeedRangeExampleUrl}</code>
                          </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                          <button
                            type="button"
                            className="btn btn-soft btn-sm"
                            onClick={async () => {
                              try {
                                await navigator.clipboard.writeText(publicConcertsFeedTokenizedUrl);
                                showToast(
                                  label(
                                    "js.configuration.publicConcerts.copySuccess",
                                    "Public concerts feed URL copied."
                                  ),
                                  "success"
                                );
                              } catch {
                                showToast(label("js.configuration.publicConcerts.copyFailed", "Copy failed"), "error");
                              }
                            }}
                          >
                            {label("js.configuration.publicConcerts.copyUrl", "Copy URL")}
                          </button>
                          <button
                            type="button"
                            className="btn btn-soft btn-sm"
                            disabled={regeneratingPublicConcertsToken}
                            onClick={() => setConfirmRegeneratePublicConcertsToken(true)}
                          >
                            {regeneratingPublicConcertsToken
                              ? label("js.common.saving", "Saving…")
                              : label("js.configuration.publicConcerts.rotateToken", "Regenerate token")}
                          </button>
                          <Link href="/settings/public-concerts-preview" className="btn btn-soft btn-sm">
                            {label("js.configuration.publicConcerts.previewTitle", "Public concerts preview")}
                          </Link>
                        </div>
                      </div>
                    ) : null}
                  </div>
                </div>
              ))}
            </DetailSection>
          </div>
        ))}

        {Object.keys(filteredGroupedParameters).length === 0 && filteredLegacyReadonlyParameters.length === 0 ? (
          <DetailSection>
            <p className="text-sm text-base-content/70">
              {label("js.configuration.search.noResults", "No settings match your search.")}
            </p>
          </DetailSection>
        ) : null}

        {reminderConfig && (
          <div id="cfg-notifications-reminders" className="space-y-6">
            <DetailSection className="space-y-4">
              <div className="space-y-1">
                {headingWithIcon(
                  label("js.configuration.group.notifications.title", "Notifications and reminders"),
                  "bell"
                )}
                <p className="text-sm text-base-content/70">
                  {label(
                    "js.configuration.group.notifications.help",
                    "Email digests, escalation thresholds, and reminder behavior."
                  )}
                </p>
                <p className="text-xs text-base-content/60">
                  {statusLabel(
                    reminderAutosave.status === "error" || escalationAutosave.status === "error"
                      ? "error"
                      : reminderAutosave.status === "saving" || escalationAutosave.status === "saving"
                        ? "saving"
                        : "idle"
                  )}
                </p>
              </div>
              {groupedByUx["notifications-reminders"].map(([section, params]) => (
                <div key={`notify-${section}`} className="rounded-box border border-base-300 p-3 space-y-4">
                  <h3 className="text-base font-semibold text-base-content">
                    {label(`js.configuration.section.${section}`, SECTION_LABELS[section] ?? section)}
                  </h3>
                  <div className="space-y-4">{params.map((p) => renderConfigParamInput(p))}</div>
                </div>
              ))}
              <div>
                <span className="text-lg font-bold text-base-content">
                  {label("js.settings.reminder.sectionTitle", "Reminder Emails")}
                </span>
                <p className="mt-2 text-sm text-base-content/70">
                  {label(
                    "js.settings.reminder.sectionHelp",
                    "Configure regular reminder/digest emails. Triggered by external server scheduler."
                  )}
                </p>
              </div>
              <label className="mt-1 flex cursor-pointer items-center gap-2 min-w-0">
                <input
                  type="checkbox"
                  className="checkbox checkbox-primary checkbox-sm shrink-0"
                  checked={Boolean(reminderConfig.enabled)}
                  disabled={reminderAutosave.status === "saving"}
                  onChange={(e) => {
                    setReminderConfig((prev) => (prev ? { ...prev, enabled: e.target.checked } : prev));
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
                    disabled={reminderAutosave.status === "saving"}
                    onChange={(e) => {
                      setReminderConfig((prev) =>
                        prev ? { ...prev, recipient_scope: e.target.value as ReminderConfig["recipient_scope"] } : prev
                      );
                    }}
                    onBlur={() => void reminderAutosave.flush()}
                  >
                    <option value="actionable_only">
                      {label(
                        "js.settings.reminder.recipient.actionable",
                        "Users with upcoming events (open responses optional)"
                      )}
                    </option>
                    <option value="all_opted_in">
                      {label("js.settings.reminder.recipient.all", "All opted-in active users")}
                    </option>
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
                    disabled={reminderAutosave.status === "saving"}
                    onChange={(e) => {
                      setReminderConfig((prev) =>
                        prev ? { ...prev, event_window_days: Number(e.target.value) } : prev
                      );
                    }}
                    onBlur={() => void reminderAutosave.flush()}
                  />
                </label>
              </div>
              <div className="flex flex-wrap gap-2">
                <button
                  type="button"
                  className="btn btn-soft"
                  disabled={runningReminder}
                  onClick={() => void runReminderNow(true)}
                >
                  {runningReminder ? "Running…" : label("js.settings.reminder.runDry", "Run dry-run")}
                </button>
                <button
                  type="button"
                  className="btn btn-soft btn-primary"
                  disabled={runningReminder}
                  onClick={() => void runReminderNow(false)}
                >
                  {runningReminder ? "Sending…" : label("js.settings.reminder.runReal", "Send now")}
                </button>
              </div>
              {runOutput ? (
                <pre className="max-h-56 overflow-auto rounded-box bg-base-200/70 p-3 text-xs font-mono whitespace-pre-wrap">
                  {runOutput}
                </pre>
              ) : null}
            </DetailSection>

            <DetailSection className="space-y-4">
              <div>
                <span className="text-lg font-bold text-base-content">
                  {label("js.settings.escalation.title", "Escalation alerts")}
                </span>
                <p className="mt-2 text-sm text-base-content/70">
                  {label(
                    "js.settings.escalation.help",
                    "Escalation warnings are triggered by an external server scheduler."
                  )}
                </p>
              </div>
              <div className="space-y-4">
                <label className="flex cursor-pointer items-center gap-2 max-w-2xl">
                  <input
                    type="checkbox"
                    className="checkbox checkbox-primary checkbox-sm"
                    checked={Boolean(escalationDraft?.enabled)}
                    disabled={escalationAutosave.status === "saving"}
                    onChange={(e) =>
                      setEscalationDraft((prev) => (prev ? { ...prev, enabled: e.target.checked } : prev))
                    }
                  />
                  <span className="text-sm">{label("js.settings.escalation.enabled", "Enable escalation alerts")}</span>
                </label>
                <label className="form-control max-w-2xl">
                  <span className="label-text text-xs font-medium text-base-content/70">
                    {label("js.settings.escalation.group", "Escalation group")}
                  </span>
                  <select
                    className="select select-bordered mb-2"
                    value={String(escalationDraft?.escalation_target_group_id ?? 0)}
                    disabled={escalationAutosave.status === "saving"}
                    onChange={(e) =>
                      setEscalationDraft((prev) =>
                        prev ? { ...prev, escalation_target_group_id: Number(e.target.value) } : prev
                      )
                    }
                    onBlur={() => void escalationAutosave.flush()}
                  >
                    <option value="0">{label("js.settings.escalation.groupNone", "Select group…")}</option>
                    {escalationGroups.map((g) => (
                      <option key={g.id} value={g.id}>
                        {g.name}
                      </option>
                    ))}
                  </select>
                </label>
                <div className="space-y-2 max-w-3xl">
                  <span className="label-text text-xs font-medium text-base-content/70">
                    {label("js.settings.escalation.pendingThreshold", "Open-response threshold (%)")}
                  </span>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.rehearsals", "Rehearsals")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={100}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.pending_threshold_percent.rehearsal ?? 20)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  pending_threshold_percent: {
                                    ...prev.pending_threshold_percent,
                                    rehearsal: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.concerts", "Concerts")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={100}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.pending_threshold_percent.concert ?? 20)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  pending_threshold_percent: {
                                    ...prev.pending_threshold_percent,
                                    concert: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                  </div>
                </div>
                <div className="space-y-2 max-w-3xl">
                  <span className="label-text text-xs font-medium text-base-content/70">
                    {label("js.settings.escalation.dropoutWindow", "Late-dropout window (hours)")}
                  </span>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.rehearsals", "Rehearsals")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={240}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.dropout_window_hours.rehearsal ?? 24)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  dropout_window_hours: {
                                    ...prev.dropout_window_hours,
                                    rehearsal: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.concerts", "Concerts")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={240}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.dropout_window_hours.concert ?? 24)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  dropout_window_hours: {
                                    ...prev.dropout_window_hours,
                                    concert: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                  </div>
                </div>
                <div className="space-y-2 max-w-3xl">
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
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.rehearsals", "Rehearsals")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={240}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.warning_window_hours.rehearsal ?? 48)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  warning_window_hours: {
                                    ...prev.warning_window_hours,
                                    rehearsal: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.concerts", "Concerts")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={240}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.warning_window_hours.concert ?? 48)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  warning_window_hours: {
                                    ...prev.warning_window_hours,
                                    concert: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                  </div>
                </div>
                <div className="space-y-2 max-w-3xl">
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
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.rehearsals", "Rehearsals")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={240}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.critical_window_hours.rehearsal ?? 12)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  critical_window_hours: {
                                    ...prev.critical_window_hours,
                                    rehearsal: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                    <label className="form-control">
                      <span className="label-text text-xs text-base-content/60">
                        {label("js.sidebar.concerts", "Concerts")}
                      </span>
                      <input
                        type="number"
                        min={1}
                        max={240}
                        className="input input-bordered mb-2"
                        value={Number(escalationDraft?.critical_window_hours.concert ?? 12)}
                        disabled={escalationAutosave.status === "saving"}
                        onChange={(e) =>
                          setEscalationDraft((prev) =>
                            prev
                              ? {
                                  ...prev,
                                  critical_window_hours: {
                                    ...prev.critical_window_hours,
                                    concert: Number(e.target.value),
                                  },
                                }
                              : prev
                          )
                        }
                      />
                    </label>
                  </div>
                </div>
                <label className="flex cursor-pointer items-center gap-2 max-w-2xl">
                  <input
                    type="checkbox"
                    className="checkbox checkbox-primary checkbox-sm"
                    checked={Boolean(escalationDraft?.include_event_organizer)}
                    disabled={escalationAutosave.status === "saving"}
                    onChange={(e) =>
                      setEscalationDraft((prev) =>
                        prev ? { ...prev, include_event_organizer: e.target.checked } : prev
                      )
                    }
                  />
                  <span className="text-sm">
                    {label("js.configuration.escalation.includeOrganizer", "Include event organizer")}
                  </span>
                </label>
              </div>
              <div className="flex flex-wrap items-center gap-3">
                <span className="text-xs text-base-content/60">{statusLabel(escalationAutosave.status)}</span>
                <Link href="/settings/instrument-minimums" className="btn btn-soft">
                  {label("js.settings.escalation.minimumsOpen", "Open instrument minimum table")}
                </Link>
              </div>
            </DetailSection>
          </div>
        )}

        <div id="cfg-instruments-coverage">
          <DetailSection className="space-y-4">
            <div className="space-y-1">
              {headingWithIcon(label("js.configuration.group.instruments.title", "Instruments and coverage"), "music")}
              <p className="text-sm text-base-content/70">
                {label(
                  "js.configuration.group.instruments.help",
                  "Instrument catalog, setup, and minimum coverage controls."
                )}
              </p>
            </div>
            <div>
              <span className="text-lg font-bold text-base-content">
                {label("js.configuration.instruments.title", "Instrument management")}
              </span>
              <p className="mt-1 text-sm text-base-content/70">
                {label(
                  "js.configuration.instruments.help",
                  "Manage instruments/categories here. Section setup and concert seat targets are configured in a dedicated setup page."
                )}
              </p>
              {sectionCoverageEnabled ? (
                <p className="mt-1 text-xs text-base-content/60">
                  {label("js.configuration.instruments.summary", "Sections: {sections}").replace(
                    "{sections}",
                    String(sections.length)
                  )}
                </p>
              ) : null}
            </div>
            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                className="btn btn-soft"
                disabled={savingInstrumentAdmin}
                onClick={() => void seedInstrumentDefaults()}
              >
                {label("js.configuration.instruments.seedDefaults", "Import default presets")}
              </button>
              <button
                type="button"
                className="btn btn-soft"
                disabled={savingInstrumentAdmin}
                onClick={() => void reloadInstrumentAdminData()}
              >
                {label("js.common.refresh", "Refresh")}
              </button>
              {sectionCoverageEnabled ? (
                <Link href="/settings/instrument-setup" className="btn btn-soft btn-primary">
                  {label("js.configuration.instruments.openSetup", "Open section setup")}
                </Link>
              ) : null}
              <Link href="/settings/instrument-minimums" className="btn btn-soft">
                {label("js.settings.escalation.minimumsOpen", "Open instrument minimum table")}
              </Link>
            </div>
            <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
              <div className="space-y-3 rounded-box border border-base-300 p-4">
                <h3 className="text-sm font-semibold text-base-content">
                  {label("js.configuration.instruments.categories", "Categories")}
                </h3>
                <div className="flex gap-2">
                  <input
                    type="text"
                    className="input input-bordered flex-1"
                    value={newCategoryName}
                    disabled={savingInstrumentAdmin}
                    onChange={(e) => setNewCategoryName(e.target.value)}
                    placeholder={label("js.configuration.instruments.newCategoryPlaceholder", "New category")}
                  />
                  <button
                    type="button"
                    className="btn btn-soft btn-primary"
                    disabled={savingInstrumentAdmin || newCategoryName.trim() === ""}
                    onClick={() => void createCategory()}
                  >
                    {label("js.common.add", "Add")}
                  </button>
                </div>
                <div className="max-h-56 overflow-auto rounded-box border border-base-300">
                  {instrumentCategories.map((category) => (
                    <div
                      key={category.id}
                      className="flex items-center justify-between gap-2 border-b border-base-300 px-3 py-2 text-sm"
                    >
                      <span>{category.name}</span>
                      <button
                        type="button"
                        className="btn btn-xs btn-soft"
                        disabled={savingInstrumentAdmin}
                        onClick={() => setPendingCategoryDelete(category)}
                      >
                        {label("js.common.delete", "Delete")}
                      </button>
                    </div>
                  ))}
                  {instrumentCategories.length === 0 && (
                    <p className="px-3 py-3 text-sm text-base-content/60">{label("js.common.noData", "No data")}</p>
                  )}
                </div>
              </div>
              <div className="space-y-3 rounded-box border border-base-300 p-4">
                <h3 className="text-sm font-semibold text-base-content">
                  {label("js.configuration.instruments.items", "Instruments")}
                </h3>
                <div className="flex flex-wrap gap-2">
                  <input
                    type="text"
                    className="input input-bordered flex-1 min-w-[12rem]"
                    value={newInstrumentName}
                    disabled={savingInstrumentAdmin}
                    onChange={(e) => setNewInstrumentName(e.target.value)}
                    placeholder={label("js.configuration.instruments.newInstrumentPlaceholder", "New instrument")}
                  />
                  <select
                    className="select select-bordered min-w-[11rem]"
                    value={String(newInstrumentCategoryId)}
                    disabled={savingInstrumentAdmin}
                    onChange={(e) => setNewInstrumentCategoryId(Number(e.target.value))}
                  >
                    <option value="0">{label("js.configuration.instruments.noCategory", "No category")}</option>
                    {instrumentCategories.map((category) => (
                      <option key={category.id} value={category.id}>
                        {category.name}
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    className="btn btn-soft btn-primary"
                    disabled={savingInstrumentAdmin || newInstrumentName.trim() === ""}
                    onClick={() => void createInstrument()}
                  >
                    {label("js.common.add", "Add")}
                  </button>
                </div>
                <div className="max-h-56 overflow-auto rounded-box border border-base-300">
                  {instrumentItems.map((instrument) => (
                    <div
                      key={instrument.id}
                      className="flex items-center justify-between gap-2 border-b border-base-300 px-3 py-2 text-sm"
                    >
                      <span className="truncate">
                        {instrument.name}
                        {instrument.category_name ? ` · ${instrument.category_name}` : ""}
                      </span>
                      <button
                        type="button"
                        className="btn btn-xs btn-soft"
                        disabled={savingInstrumentAdmin}
                        onClick={() => setPendingInstrumentDelete(instrument)}
                      >
                        {label("js.common.delete", "Delete")}
                      </button>
                    </div>
                  ))}
                  {instrumentItems.length === 0 && (
                    <p className="px-3 py-3 text-sm text-base-content/60">{label("js.common.noData", "No data")}</p>
                  )}
                </div>
              </div>
            </div>
            {sectionCoverageEnabled ? (
              <div className="rounded-box border border-base-300 p-4">
                <p className="text-sm text-base-content/70">
                  {label(
                    "js.configuration.instruments.setupBlurb",
                    "Use the dedicated setup page for section mapping, rehearsal/concert totals, and optional strict concert seats."
                  )}
                </p>
                <div className="mt-3">
                  <Link href="/settings/instrument-setup" className="btn btn-soft btn-primary">
                    {label("js.configuration.instruments.openSetup", "Open section setup")}
                  </Link>
                </div>
              </div>
            ) : null}
          </DetailSection>
        </div>

        {featureFlagsParams.length > 0 ? (
          <DetailSection className="space-y-4">
            <div className="space-y-1">
              {headingWithIcon(label("js.configuration.section.feature_flags", "Feature flags"), "key")}
            </div>
            <div className="space-y-4">{featureFlagsParams.map((p) => renderConfigParamInput(p))}</div>
          </DetailSection>
        ) : null}

        {advancedSystemSections.length > 0 ? (
          <div id="cfg-advanced-system">
            <DetailSection className="space-y-4">
              <div className="space-y-1">
                {headingWithIcon(label("js.configuration.group.advanced.title", "Advanced and system"), "settings")}
                <p className="text-sm text-base-content/70">
                  {label(
                    "js.configuration.group.advanced.help",
                    "Feature flags and legacy-readonly compatibility values."
                  )}
                </p>
              </div>
              {advancedSystemSections.map(([section, params]) => (
                <div key={`advanced-${section}`} className="rounded-box border border-base-300 p-3 space-y-4">
                  <h3 className="text-base font-semibold text-base-content">
                    {label(`js.configuration.section.${section}`, SECTION_LABELS[section] ?? section)}
                  </h3>
                  <div className="space-y-4">{params.map((p) => renderConfigParamInput(p))}</div>
                </div>
              ))}
            </DetailSection>
          </div>
        ) : null}

        {filteredLegacyReadonlyParameters.length > 0 ? (
          <DetailSection className="space-y-4">
            <div>
              {headingWithIcon(
                label("js.configuration.legacyReadonly.title", "Legacy-only configuration (read-only)"),
                "file-text"
              )}
              <p className="mt-1 text-sm text-base-content/70">
                {label(
                  "js.configuration.legacyReadonly.help",
                  "These settings are used by the old app only and are shown here for visibility."
                )}
              </p>
            </div>
            <div className="space-y-4">
              {filteredLegacyReadonlyParameters.map((p) => {
                const value = configDraft[p.param];
                if (p.type === "boolean") {
                  return (
                    <label key={p.param} className="flex items-center gap-2 opacity-75">
                      <input
                        type="checkbox"
                        className="checkbox checkbox-primary checkbox-sm"
                        checked={Boolean(value)}
                        disabled
                      />
                      <span className="text-sm">{label(`js.configuration.param.${p.param}`, p.caption)}</span>
                    </label>
                  );
                }
                if (p.type === "reference_group" || p.type === "reference_conductor") {
                  const options =
                    p.type === "reference_group"
                      ? (config?.options?.groups ?? [])
                      : (config?.options?.conductors ?? []);
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

      <ConfirmModal
        open={confirmRegeneratePublicConcertsToken}
        onClose={() => setConfirmRegeneratePublicConcertsToken(false)}
        title={label("js.configuration.publicConcerts.rotateConfirmTitle", "Regenerate feed token?")}
        message={label(
          "js.configuration.publicConcerts.rotateConfirmMessage",
          "The old feed URL will stop working immediately. Continue?"
        )}
        confirmLabel={label("js.configuration.publicConcerts.rotateToken", "Regenerate token")}
        cancelLabel={label("js.common.cancel", "Cancel")}
        onConfirm={async () => {
          setConfirmRegeneratePublicConcertsToken(false);
          await regeneratePublicConcertsFeedToken();
        }}
        variant="danger"
      />

      <ConfirmModal
        open={pendingCategoryDelete != null}
        onClose={() => setPendingCategoryDelete(null)}
        title={label("js.common.confirmDeleteTitle", "Delete?")}
        message={
          pendingCategoryDelete
            ? label("js.common.confirmDeleteMessageNamed", 'Delete "%s"? This cannot be undone.').replace(
                "%s",
                pendingCategoryDelete.name
              )
            : ""
        }
        confirmLabel={label("js.common.delete", "Delete")}
        cancelLabel={label("js.common.cancel", "Cancel")}
        onConfirm={async () => {
          if (!pendingCategoryDelete) return;
          await deleteCategory(pendingCategoryDelete.id);
          setPendingCategoryDelete(null);
        }}
        variant="danger"
      />

      <ConfirmModal
        open={pendingInstrumentDelete != null}
        onClose={() => setPendingInstrumentDelete(null)}
        title={label("js.common.confirmDeleteTitle", "Delete?")}
        message={
          pendingInstrumentDelete
            ? label("js.common.confirmDeleteMessageNamed", 'Delete "%s"? This cannot be undone.').replace(
                "%s",
                pendingInstrumentDelete.name
              )
            : ""
        }
        confirmLabel={label("js.common.delete", "Delete")}
        cancelLabel={label("js.common.cancel", "Cancel")}
        onConfirm={async () => {
          if (!pendingInstrumentDelete) return;
          await deleteInstrument(pendingInstrumentDelete.id);
          setPendingInstrumentDelete(null);
        }}
        variant="danger"
      />
    </div>
  );
}
