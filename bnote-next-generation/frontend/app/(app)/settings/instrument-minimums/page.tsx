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
import { configurationApi } from "@/lib/configuration-api";

interface Instrument {
  id: number;
  name: string;
  category_id?: number;
  category_name?: string;
}

interface SectionConfig {
  id: string;
  name: string;
  instrument_ids?: number[];
  concert_instrument_targets?: Array<{ instrument_id: number; required: number }>;
}

interface MinimumRow {
  key: string;
  name: string;
  family: string;
}

interface MinimumsByType {
  rehearsal: Record<string, number>;
  concert: Record<string, number>;
}

type CoverageMode = "instrument" | "section";

export default function InstrumentMinimumsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [instruments, setInstruments] = useState<Instrument[]>([]);
  const [sections, setSections] = useState<SectionConfig[]>([]);
  const [minimumsByType, setMinimumsByType] = useState<MinimumsByType>({
    rehearsal: {},
    concert: {},
  });
  const [coverageMode, setCoverageMode] = useState<CoverageMode>("instrument");
  const [sectionCoverageEnabled, setSectionCoverageEnabled] = useState(false);
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

      const [instrumentsRes, minimumsRes, cfgRes] = await Promise.all([
        api.get<{ instruments?: Instrument[] }>("dashboard", "getInstruments"),
        api.get<{
          mode?: CoverageMode;
          minimums?: Record<string, number> | { rehearsal?: Record<string, number>; concert?: Record<string, number> };
          sections?: SectionConfig[];
        }>("dashboard", "getInstrumentMinimums"),
        configurationApi.getConfig(),
      ]);

      const featureEnabled = cfgRes?.values?.beta_section_coverage_enabled === true
        || cfgRes?.values?.beta_section_coverage_enabled === 1
        || cfgRes?.values?.beta_section_coverage_enabled === "1";
      setSectionCoverageEnabled(featureEnabled);

      setInstruments(Array.isArray(instrumentsRes?.instruments) ? instrumentsRes.instruments : []);
      setSections(featureEnabled && Array.isArray(minimumsRes?.sections) ? minimumsRes.sections : []);
      setCoverageMode(featureEnabled && minimumsRes?.mode === "section" ? "section" : "instrument");

      const rawMinimums = minimumsRes?.minimums;
      const normalize = (input: unknown): Record<string, number> => {
        const out: Record<string, number> = {};
        if (!input || typeof input !== "object") return out;
        Object.entries(input as Record<string, unknown>).forEach(([k, v]) => {
          const key = String(k).trim();
          if (key === "") return;
          if (!/^\d+$/.test(key)) return;
          out[key] = Math.max(0, Number(v) || 0);
        });
        return out;
      };

      if (
        rawMinimums &&
        typeof rawMinimums === "object" &&
        ("rehearsal" in rawMinimums || "concert" in rawMinimums)
      ) {
        const byType = rawMinimums as { rehearsal?: Record<string, number>; concert?: Record<string, number> };
        const rehearsal = normalize(byType.rehearsal ?? {});
        const concertRaw = normalize(byType.concert ?? {});
        setMinimumsByType({
          rehearsal,
          concert: Object.keys(concertRaw).length > 0 ? concertRaw : rehearsal,
        });
      } else {
        const legacy = normalize(rawMinimums ?? {});
        setMinimumsByType({ rehearsal: legacy, concert: legacy });
      }
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
      const sanitize = (input: Record<string, number>) => {
        const out: Record<string, number> = {};
        Object.entries(input).forEach(([id, value]) => {
          if (!/^\d+$/.test(String(id))) return;
          const min = Math.max(0, Math.floor(Number(value) || 0));
          if (min > 0) out[id] = min;
        });
        return out;
      };
      const payload = {
        rehearsal: sanitize(minimumsByType.rehearsal),
        concert: sanitize(minimumsByType.concert),
      };
      await api.post("dashboard", "setInstrumentMinimums", { mode: sectionCoverageEnabled ? coverageMode : "instrument", minimums: payload });
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSaving(false);
    }
  }

  const sectionAssignedInstrumentIds = useMemo(() => {
    const ids = new Set<number>();
    sections.forEach((section) => {
      (section.instrument_ids ?? []).forEach((rawId) => {
        const id = Number(rawId);
        if (id > 0) ids.add(id);
      });
      (section.concert_instrument_targets ?? []).forEach((target) => {
        const id = Number(target?.instrument_id || 0);
        if (id > 0) ids.add(id);
      });
    });
    return ids;
  }, [sections]);

  const groupedRows = useMemo(() => {
    const fallbackOnly = coverageMode === "section";

    const rows: MinimumRow[] = [];
    instruments.forEach((inst) => {
      if (fallbackOnly && sectionAssignedInstrumentIds.has(inst.id)) {
        return;
      }
      const family = (inst.category_name ?? "").trim() || label("js.common.other", "Other");
      rows.push({
        key: String(inst.id),
        name: inst.name,
        family,
      });
    });

    const q = filter.trim().toLowerCase();
    const filtered = q === ""
      ? rows
      : rows.filter((row) => row.name.toLowerCase().includes(q) || row.family.toLowerCase().includes(q));

    const groups = new Map<string, MinimumRow[]>();
    filtered.forEach((row) => {
      const arr = groups.get(row.family) ?? [];
      arr.push(row);
      groups.set(row.family, arr);
    });

    return Array.from(groups.entries())
      .map(([family, items]) => [
        family,
        [...items].sort((a, b) => a.name.localeCompare(b.name)),
      ] as const)
      .sort(([a], [b]) => a.localeCompare(b));
  }, [coverageMode, filter, instruments, label, sectionAssignedInstrumentIds]);

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

        {sectionCoverageEnabled ? (
          <div className="flex items-center gap-3 flex-wrap">
            <span className="text-sm font-medium text-base-content/80">
              {label("js.settings.minimums.modeLabel", "Coverage mode")}
            </span>
            <button
              type="button"
              onClick={() => setCoverageMode("instrument")}
              className={`filter-bubble ${coverageMode === "instrument" ? "filter-bubble-performance selected" : "filter-bubble-performance"}`}
            >
              {label("js.settings.minimums.modeInstrument", "Instruments")}
            </button>
            <button
              type="button"
              onClick={() => setCoverageMode("section")}
              className={`filter-bubble ${coverageMode === "section" ? "filter-bubble-rehearsal selected" : "filter-bubble-rehearsal"}`}
            >
              {label("js.settings.minimums.modeSection", "Sections")}
            </button>
          </div>
        ) : null}

        {sectionCoverageEnabled ? (
          <p className="text-xs text-base-content/60">
            {coverageMode === "section"
              ? label(
                  "js.settings.minimums.modeSectionHelpSimplified",
                  "Section checks are configured in Instrument Setup. This table is fallback-only for instruments not assigned to any section."
                )
              : label("js.settings.minimums.modeInstrumentHelp", "Only instrument minimums are active for escalation checks.")}
          </p>
        ) : null}

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
                <th className="w-2/3 border-b border-base-300">
                  {label("js.settings.minimums.instrument", "Instrument")}
                </th>
                <th className="w-1/6 border-b border-base-300">
                  {label("js.sidebar.rehearsals", "Rehearsals")}
                </th>
                <th className="w-1/6 border-b border-base-300">
                  {label("js.sidebar.concerts", "Concerts")}
                </th>
              </tr>
            </thead>
            <tbody>
              {groupedRows.length === 0 && (
                <tr>
                  <td colSpan={3} className="text-base-content/60">
                    {label("js.common.noMatches", "No matches")}
                  </td>
                </tr>
              )}
              {groupedRows.map(([family, items]) => (
                <Fragment key={family}>
                  <tr className="bg-base-200/60">
                    <td colSpan={3} className="border-y-2 border-base-300 text-sm font-semibold uppercase tracking-wide text-base-content/70">
                      {family}
                    </td>
                  </tr>
                  {items.map((row) => (
                    <tr key={row.key} className="border-b border-base-300">
                      <td>{row.name}</td>
                      <td>
                        <input
                          type="number"
                          min={0}
                          className="input input-bordered w-full max-w-[7rem]"
                          value={minimumsByType.rehearsal[row.key] ?? 0}
                          onChange={(e) => {
                            const next = Math.max(0, Math.floor(Number(e.target.value) || 0));
                            setMinimumsByType((prev) => ({
                              ...prev,
                              rehearsal: { ...prev.rehearsal, [row.key]: next },
                            }));
                          }}
                        />
                      </td>
                      <td>
                        <input
                          type="number"
                          min={0}
                          className="input input-bordered w-full max-w-[7rem]"
                          value={minimumsByType.concert[row.key] ?? 0}
                          onChange={(e) => {
                            const next = Math.max(0, Math.floor(Number(e.target.value) || 0));
                            setMinimumsByType((prev) => ({
                              ...prev,
                              concert: { ...prev.concert, [row.key]: next },
                            }));
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
      </DetailSection>
    </div>
  );
}
