"use client";

import { useEffect, useState } from "react";
import { toPng } from "html-to-image";
import { Modal } from "@/components/Modal";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { bugReportApi } from "@/lib/bug-report-api";
import {
  getBugReportClientContext,
  getBugReportLogEvents,
  getBugReportNetworkEvents,
} from "@/lib/bug-report-diagnostics";

interface BugReportModalProps {
  open: boolean;
  onClose: () => void;
}

export function BugReportModal({ open, onClose }: BugReportModalProps) {
  const { t } = useI18n();
  const { showToast } = useToast();
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const [message, setMessage] = useState("");

  const [includeScreenshot, setIncludeScreenshot] = useState(true);
  const [includeNetwork, setIncludeNetwork] = useState(true);
  const [includeLogs, setIncludeLogs] = useState(true);
  const [sending, setSending] = useState(false);
  const [snapshotCounts, setSnapshotCounts] = useState({ network: 0, logs: 0 });

  useEffect(() => {
    if (!open) return;
    const refresh = () => {
      setSnapshotCounts({
        network: getBugReportNetworkEvents().length,
        logs: getBugReportLogEvents().length,
      });
    };
    refresh();
    const id = window.setInterval(refresh, 600);
    return () => window.clearInterval(id);
  }, [open]);

  function resetForm() {
    setMessage("");
    setIncludeScreenshot(true);
    setIncludeNetwork(true);
    setIncludeLogs(true);
  }

  async function captureScreenshot(): Promise<string | undefined> {
    if (!includeScreenshot) return undefined;
    const captureRoot = document.querySelector("[data-bnote-capture-root='1']") as HTMLElement | null;
    if (!captureRoot) return undefined;
    try {
      const dataUrl = await toPng(captureRoot, {
        pixelRatio: 1,
        cacheBust: true,
      });
      return dataUrl;
    } catch {
      showToast(label("js.bugReport.screenshotCaptureFailed", "Screenshot capture failed. Sending report without screenshot."), "error");
      return undefined;
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!message.trim()) {
      showToast(label("js.bugReport.requiredFields", "Please fill all required fields."), "error");
      return;
    }

    setSending(true);
    try {
      const screenshotDataUrl = await captureScreenshot();
      const clientContext = getBugReportClientContext();
      const networkEvents = includeNetwork ? getBugReportNetworkEvents() : [];
      const logEvents = includeLogs ? getBugReportLogEvents() : [];

      const response = await bugReportApi.send({
        message: message.trim(),
        screenshotDataUrl,
        clientContext,
        networkEvents,
        logEvents,
      });

      const success = label("js.bugReport.sent", "Bug report sent");
      showToast(
        response?.reportId
          ? `${success} (${response.reportId})`
          : success,
        "success"
      );
      resetForm();
      onClose();
    } catch (err) {
      const msg = err instanceof Error ? err.message : label("js.bugReport.sendFailed", "Failed to send bug report.");
      showToast(msg, "error");
    } finally {
      setSending(false);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title={label("js.bugReport.titleModal", "Report bug")} dialogClassName="max-w-2xl">
      <form className="space-y-4" onSubmit={handleSubmit}>
        <label className="form-control">
          <span className="label-text text-xs font-medium text-base-content/70">
            {label("js.bugReport.message", "Message")} *
          </span>
          <textarea
            className="textarea textarea-bordered min-h-40"
            value={message}
            maxLength={6000}
            onChange={(e) => setMessage(e.target.value)}
            required
          />
        </label>

        <div className="rounded-box border border-base-300 p-3 space-y-2">
          <p className="text-xs font-medium text-base-content/70">
            {label("js.bugReport.includeDiagnostics", "Include diagnostics")}
          </p>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" className="checkbox checkbox-sm checkbox-primary" checked={includeScreenshot} onChange={(e) => setIncludeScreenshot(e.target.checked)} />
            <span>{label("js.bugReport.includeScreenshot", "Include screenshot")}</span>
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" className="checkbox checkbox-sm checkbox-primary" checked={includeNetwork} onChange={(e) => setIncludeNetwork(e.target.checked)} />
            <span>{label("js.bugReport.includeNetwork", "Include recent network requests")} ({snapshotCounts.network})</span>
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" className="checkbox checkbox-sm checkbox-primary" checked={includeLogs} onChange={(e) => setIncludeLogs(e.target.checked)} />
            <span>{label("js.bugReport.includeLogs", "Include recent logs")} ({snapshotCounts.logs})</span>
          </label>
        </div>

        <div className="flex justify-end gap-2">
          <button type="button" className="btn btn-soft" onClick={onClose} disabled={sending}>
            {label("js.common.cancel", "Cancel")}
          </button>
          <button type="submit" className="btn btn-primary" disabled={sending}>
            {sending ? label("js.bugReport.sending", "Sending…") : label("js.bugReport.send", "Send bug report")}
          </button>
        </div>
      </form>
    </Modal>
  );
}
