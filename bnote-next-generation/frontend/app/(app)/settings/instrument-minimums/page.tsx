"use client";

import { Fragment, useCallback, useEffect, useMemo, useState } from "react";
import { DetailPageHeader } from "@/components/DetailPageHeader";
import { DetailSection } from "@/components/DetailSection";
import { Spinner } from "@/components/Spinner";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { checkSession } from "@/lib/auth";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { api } from "@/lib/api";

interface Instrument {
  id: number;
  name: string;
  category_id?: number;
  category_name?: string;
}

export default function InstrumentMinimumsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [instruments, setInstruments] = useState<Instrument[]>([]);
  const [minimums, setMinimums] = useState<Record<number, number>>({});
  const [filter, setFilter] = useState("");

  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const session = await checkSession();
      if (!session?.isAdmin) {
        showToast(label("js.common.notFound", "Not found"), "error");
        setLoading(false);
        return;
      }

      const [instrumentsRes, minimumsRes] = await Promise.all([
        api.get<{ instruments?: Instrument[] }>("dashboard", "getInstruments"),
        api.get<{ minimums?: Record<string, number> }>("dashboard", "getInstrumentMinimums"),
      ]);

      setInstruments(Array.isArray(instrumentsRes?.instruments) ? instrumentsRes.instruments : []);
      const mins: Record<number, number> = {};
      Object.entries(minimumsRes?.minimums ?? {}).forEach(([k, v]) => {
        const id = Number(k);
        if (Number.isFinite(id) && id > 0) {
          mins[id] = Math.max(0, Number(v) || 0);
        }
      });
      setMinimums(mins);
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToLoad"), "error");
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    void load();
  }, [ready, load]);

  async function save() {
    setSaving(true);
    try {
      const payload: Record<string, number> = {};
      Object.entries(minimums).forEach(([id, value]) => {
        const min = Math.max(0, Math.floor(Number(value) || 0));
        if (min > 0) payload[id] = min;
      });
      await api.post("dashboard", "setInstrumentMinimums", { minimums: payload });
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSaving(false);
    }
  }

  const groupedFilteredInstruments = useMemo(() => {
    const q = filter.trim().toLowerCase();
    const filtered = q === ""
      ? instruments
      : instruments.filter((inst) =>
          inst.name.toLowerCase().includes(q) ||
          (inst.category_name ?? "").toLowerCase().includes(q)
        );
    const groups = new Map<string, Instrument[]>();
    filtered.forEach((inst) => {
      const key = (inst.category_name ?? "").trim() || "Other";
      const arr = groups.get(key) ?? [];
      arr.push(inst);
      groups.set(key, arr);
    });
    return Array.from(groups.entries()).sort(([a], [b]) => a.localeCompare(b));
  }, [filter, instruments]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader title={label("js.settings.minimums.title", "Instrument minimums")} />

      <DetailSection className="space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-base-content/70">
            {label("js.settings.minimums.help", "Configure minimum required players per instrument for escalation checks.")}
          </p>
          <button
            type="button"
            className="btn btn-soft btn-primary"
            disabled={saving}
            onClick={() => {
              void save();
            }}
          >
            {saving ? label("js.common.saving", "Saving…") : label("js.common.save", "Save")}
          </button>
        </div>
        <p className="text-sm text-base-content/70">
          {label(
            "js.settings.minimums.zeroHint",
            "Value 0 means this instrument is excluded from critical alert minimum checks."
          )}
        </p>

        <label className="form-control">
          <span className="label-text text-sm font-medium text-base-content/70">
            {label("js.settings.minimums.filter", "Search instrument or family")}
          </span>
          <input
            type="text"
            className="input input-bordered"
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
            placeholder={label("js.settings.minimums.filterPlaceholder", "e.g. Brass, Trumpet")}
          />
        </label>

        <div className="overflow-x-auto rounded-box border border-base-300">
          <table className="table">
            <thead>
              <tr>
                <th className="w-2/3 border-b border-base-300">{label("js.settings.minimums.instrument", "Instrument")}</th>
                <th className="w-1/3 border-b border-base-300">{label("js.settings.minimums.value", "Minimum required")}</th>
              </tr>
            </thead>
            <tbody>
              {groupedFilteredInstruments.length === 0 && (
                <tr>
                  <td colSpan={2} className="text-base-content/60">
                    {label("js.common.noMatches", "No matches")}
                  </td>
                </tr>
              )}
              {groupedFilteredInstruments.map(([family, items]) => (
                <Fragment key={family}>
                  <tr className="bg-base-200/60">
                    <td colSpan={2} className="border-y-2 border-base-300 text-sm font-semibold uppercase tracking-wide text-base-content/70">
                      {family}
                    </td>
                  </tr>
                  {items.map((inst) => (
                    <tr key={inst.id} className="border-b border-base-300">
                      <td>{inst.name}</td>
                      <td>
                      <input
                        type="number"
                        min={0}
                        className="input input-bordered w-full max-w-xs"
                        value={minimums[inst.id] ?? 0}
                        onChange={(e) => {
                          const next = Math.max(0, Math.floor(Number(e.target.value) || 0));
                          setMinimums((prev) => ({ ...prev, [inst.id]: next }));
                        }}
                      />
                      </td>
                    </tr>
                  ))}
                </Fragment>
              ))}
            </tbody>
          </table>
        </div>

        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="btn btn-soft"
            disabled={saving}
            onClick={() => {
              const preset: Record<number, number> = {};
              instruments.forEach((inst) => {
                preset[inst.id] = 1;
              });
              setMinimums(preset);
            }}
          >
            {label("js.settings.minimums.setDefaults", "Set defaults to 1")}
          </button>
        </div>
      </DetailSection>
    </div>
  );
}
