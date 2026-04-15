/**
 * BNote Next Generation - Instrument Coverage Settings Modal
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * Modal to configure minimum participants per instrument for the Instrument Coverage tile.
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";

export interface InstrumentCoverageSettingsModalProps {
  open: boolean;
  onClose: () => void;
  onSaved?: () => void;
}

interface Instrument {
  id: number;
  name: string;
}

export function InstrumentCoverageSettingsModal({ open, onClose, onSaved }: InstrumentCoverageSettingsModalProps) {
  const { t } = useI18n();
  const [instruments, setInstruments] = useState<Instrument[]>([]);
  const [minimums, setMinimums] = useState<Record<number, number>>({});
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    if (!open) return;
    setLoading(true);
    setError("");
    try {
      const [instRes, minRes] = await Promise.all([
        api.get<{ instruments?: Instrument[] }>("dashboard", "getInstruments"),
        api.get<{ minimums?: Record<string, number> }>("dashboard", "getInstrumentMinimums"),
      ]);
      setInstruments(instRes?.instruments ?? []);
      const mins: Record<number, number> = {};
      Object.entries(minRes?.minimums ?? {}).forEach(([k, v]) => {
        const id = parseInt(k, 10);
        if (!isNaN(id)) mins[id] = v;
      });
      setMinimums(mins);
    } catch {
      setError(t("js.common.failedToLoad") || "Failed to load");
    } finally {
      setLoading(false);
    }
  }, [open, t]);

  useEffect(() => {
    load();
  }, [load]);

  const handleSave = async () => {
    setSaving(true);
    setError("");
    try {
      const payload: Record<string, number> = {};
      Object.entries(minimums).forEach(([k, v]) => {
        if (v > 0) payload[k] = v;
      });
      await api.post("dashboard", "setInstrumentMinimums", { minimums: payload });
      onSaved?.();
      onClose();
    } catch {
      setError(t("js.common.saveFailed") || "Save failed");
    } finally {
      setSaving(false);
    }
  };

  const handleMinChange = (instId: number, value: number) => {
    setMinimums((prev) => ({
      ...prev,
      [instId]: Math.max(0, value),
    }));
  };

  useEffect(() => {
    if (!open) return;
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape" && !saving) onClose();
    };
    document.addEventListener("keydown", handleEscape);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "";
    };
  }, [open, saving, onClose]);

  if (!open) return null;

  const modalContent = (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="instrument-coverage-modal-title"
    >
      <div className="absolute inset-0 bg-base-content/20" aria-hidden="true" onClick={() => !saving && onClose()} />
      <div className="modal-dialog modal-dialog-sm modal-middle relative z-10 w-full max-w-md">
        <div className="modal-content rounded-box border border-base-300 bg-base-100 shadow-xl p-4">
          <h3 id="instrument-coverage-modal-title" className="font-bold text-lg mb-2">
            {t("js.bandOverview.instrumentCoverageSettings")}
          </h3>
          <p className="text-sm text-base-content/70 py-2">{t("js.bandOverview.instrumentCoverageSettingsDesc")}</p>
          {loading ? (
            <div className="py-8 text-center">{t("js.common.loading")}</div>
          ) : (
            <div className="space-y-3 max-h-64 overflow-y-auto py-2">
              {instruments.map((inst) => (
                <div key={inst.id} className="flex items-center gap-3">
                  <label className="flex-1 truncate text-sm" htmlFor={`min-${inst.id}`}>
                    {inst.name}
                  </label>
                  <input
                    id={`min-${inst.id}`}
                    type="number"
                    min={0}
                    className="input input-bordered input-sm w-20"
                    value={minimums[inst.id] ?? 0}
                    onChange={(e) => handleMinChange(inst.id, parseInt(e.target.value, 10) || 0)}
                  />
                </div>
              ))}
              {instruments.length === 0 && (
                <p className="text-sm text-base-content/60">{t("js.common.empty") || "—"}</p>
              )}
            </div>
          )}
          {error && <p className="text-sm text-error py-1">{error}</p>}
          <div className="modal-action mt-4">
            <button type="button" className="btn btn-soft" onClick={onClose} disabled={saving}>
              {t("js.common.cancel")}
            </button>
            <button
              type="button"
              className="btn btn-primary text-primary-content"
              onClick={handleSave}
              disabled={loading || saving}
            >
              {saving ? t("js.common.saving") : t("js.common.save")}
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  return typeof document !== "undefined" ? createPortal(modalContent, document.body) : null;
}
