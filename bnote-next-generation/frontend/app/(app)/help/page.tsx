"use client";

import { AppPageHeader } from "@/components/AppPageHeader";
import { PageContent } from "@/components/PageContent";
import { useI18n } from "@/contexts/I18nContext";

interface ShortcutRow {
  keyLabel: string;
  actionLabel: string;
}

export default function HelpPage() {
  const { t } = useI18n();
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const shortcuts: ShortcutRow[] = [
    {
      keyLabel: label("js.help.hotkeys.openEdit.keys", "E"),
      actionLabel: label("js.help.hotkeys.openEdit.action", "Enter edit mode on the current view when available."),
    },
    {
      keyLabel: label("js.help.hotkeys.escape.keys", "Esc"),
      actionLabel: label("js.help.hotkeys.escape.action", "Close the current modal or exit edit mode."),
    },
    {
      keyLabel: label("js.help.hotkeys.openHelp.keys", "?"),
      actionLabel: label("js.help.hotkeys.openHelp.action", "Open this help page."),
    },
    {
      keyLabel: label("js.help.hotkeys.goDashboard.keys", "G, then D"),
      actionLabel: label("js.help.hotkeys.goDashboard.action", "Go to Dashboard."),
    },
    {
      keyLabel: label("js.help.hotkeys.goProfile.keys", "G, then P"),
      actionLabel: label("js.help.hotkeys.goProfile.action", "Go to My Contact Data."),
    },
    {
      keyLabel: label("js.help.hotkeys.goSettings.keys", "G, then S"),
      actionLabel: label("js.help.hotkeys.goSettings.action", "Go to Preferences."),
    },
    {
      keyLabel: label("js.help.hotkeys.goHelp.keys", "G, then H"),
      actionLabel: label("js.help.hotkeys.goHelp.action", "Go to Help."),
    },
  ];

  return (
    <PageContent className="space-y-6">
      <AppPageHeader
        moduleKey="help"
        title={label("js.help.pageTitle", "Help")}
        subtitle={label("js.help.pageSubtitle", "Keyboard shortcuts and quick navigation.")}
      />

      <section className="rounded-box border border-base-300 bg-base-100 p-4">
        <h2 className="text-base font-semibold text-base-content">
          {label("js.help.hotkeys.title", "Keyboard shortcuts")}
        </h2>
        <p className="mt-1 text-sm text-base-content/70">
          {label(
            "js.help.hotkeys.description",
            "Shortcuts are available on macOS and Windows. Typing fields are excluded automatically."
          )}
        </p>

        <div className="mt-4 overflow-x-auto">
          <table className="table">
            <thead>
              <tr>
                <th>{label("js.help.hotkeys.keyColumn", "Shortcut")}</th>
                <th>{label("js.help.hotkeys.actionColumn", "Action")}</th>
              </tr>
            </thead>
            <tbody>
              {shortcuts.map((shortcut) => (
                <tr key={`${shortcut.keyLabel}-${shortcut.actionLabel}`}>
                  <td className="font-mono text-xs sm:text-sm">{shortcut.keyLabel}</td>
                  <td className="text-sm">{shortcut.actionLabel}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </PageContent>
  );
}
