"use client";

import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { kontaktdatenApi } from "@/lib/kontaktdaten-api";
import { TablerIconByName } from "@/components/icons";
import { DetailSection } from "@/components/DetailSection";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getModuleHeadlineConfig } from "@/lib/module-headline-config";

export default function SettingsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const moduleConfig = getModuleHeadlineConfig("settings");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [emailNotification, setEmailNotification] = useState(true);
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const prefs = await kontaktdatenApi.getUserPreferences();
      setEmailNotification(Boolean(prefs?.email_notification));
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

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div className="mb-4">
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
            <span>{label("js.settings.title", "Preferences")}</span>
          </span>
        </h1>
      </div>
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
              onChange={(e) => void persist(e.target.checked)}
            />
            <span className="min-w-0 flex-1 space-y-1">
              <span className="text-sm font-medium text-base-content block">
                {label("js.settings.emailNotificationsLabel", "Email me about activity")}
              </span>
              <span className="text-sm text-base-content/70 block leading-snug">
                {label("js.settings.emailNotificationsHelp", "Task updates, discussion messages, and similar notices. You can turn this off anytime.")}
              </span>
            </span>
          </label>
        </div>
        <p className="text-xs text-base-content/50 leading-relaxed">
          {label("js.settings.footerNote", "Account emails (sign-up, password reset, activation) may still be sent when required.")}
        </p>
      </DetailSection>
    </div>
  );
}
