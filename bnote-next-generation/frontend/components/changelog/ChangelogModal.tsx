"use client";

import { Modal } from "@/components/Modal";
import { useI18n } from "@/contexts/I18nContext";
import type { ChangelogEntry } from "@/lib/changelog-api";
import { formatDateShortDisplay } from "@/lib/date-time";

interface ChangelogModalProps {
  open: boolean;
  releaseId: string;
  entries: ChangelogEntry[];
  onClose: () => void;
  onViewDetails: () => void;
}

export function ChangelogModal({ open, releaseId, entries, onClose, onViewDetails }: ChangelogModalProps) {
  const { t, lang } = useI18n();
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);
  const changeTypeLabel = (type: ChangelogEntry["changeType"]) => {
    if (type === "added") return label("js.changelog.type.added", "Added");
    if (type === "fixed") return label("js.changelog.type.fixed", "Fixed");
    if (type === "removed") return label("js.changelog.type.removed", "Removed");
    return label("js.changelog.type.changed", "Changed");
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={label("js.changelog.title", "What's New in BNote")}
      dialogClassName="max-w-2xl"
    >
      <div className="space-y-4">
        <p className="text-sm text-base-content/70">
          {label("js.changelog.subtitle", "Product updates and fixes in BNote.")}
        </p>
        <div>
          {entries.length > 0 ? (
            <ul className="space-y-3">
              {entries.slice(0, 6).map((entry) => (
                <li key={`${entry.bugId ?? "note"}-${entry.title}-${entry.date}`} className="text-sm">
                  <div className="text-base-content">
                    - {changeTypeLabel(entry.changeType)}: {entry.title}
                  </div>
                  <div className="mt-1 text-xs text-base-content/65 pl-4">
                    {[entry.bugId ?? "", entry.date ? formatDateShortDisplay(entry.date, lang) : ""]
                      .filter(Boolean)
                      .join(" · ")}
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <div className="px-3 py-3 text-sm text-base-content/70">
              {label("js.changelog.empty", "No changelog entries available yet.")}
            </div>
          )}
        </div>
        {releaseId ? (
          <p className="text-xs text-base-content/60">
            {label("js.changelog.releaseId", "Release")}: {releaseId}
          </p>
        ) : null}
        <div className="flex flex-wrap justify-end gap-2">
          <button type="button" className="btn btn-primary" onClick={onViewDetails}>
            {label("js.changelog.viewDetails", "View changelog")}
          </button>
        </div>
      </div>
    </Modal>
  );
}
