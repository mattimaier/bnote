"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { DetailPageHeader } from "@/components/DetailPageHeader";
import { DetailSection } from "@/components/DetailSection";
import { ConfirmModal } from "@/components/ConfirmModal";
import { Spinner } from "@/components/Spinner";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import {
  configurationApi,
  type InstrumentSectionConfig,
} from "@/lib/configuration-api";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

function createTemporaryId(index: number): string {
  return `tmp_${Date.now()}_${index}`;
}

function instrumentCountMap(instrumentIds: number[] | undefined): Map<number, number> {
  const map = new Map<number, number>();
  (instrumentIds ?? []).forEach((rawId) => {
    const id = Number(rawId);
    if (!Number.isFinite(id) || id < 1) return;
    map.set(id, (map.get(id) ?? 0) + 1);
  });
  return map;
}

function expandInstrumentCounts(counts: Map<number, number>): number[] {
  const out: number[] = [];
  counts.forEach((count, id) => {
    const safeCount = Math.max(0, Math.floor(Number(count) || 0));
    for (let index = 0; index < safeCount; index += 1) {
      out.push(id);
    }
  });
  return out;
}

export default function InstrumentSetupPage() {
  const router = useRouter();
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [loading, setLoading] = useState(true);
  const [savingSections, setSavingSections] = useState(false);
  const [seedingDefaults, setSeedingDefaults] = useState(false);
  const [applyingBigBand, setApplyingBigBand] = useState(false);
  const [pendingSectionDeleteId, setPendingSectionDeleteId] = useState<string | null>(null);
  const [pendingConcertTargetDelete, setPendingConcertTargetDelete] = useState<{
    sectionId: string;
    index: number;
  } | null>(null);

  const [instruments, setInstruments] = useState<Array<{ id: number; name: string; category_name?: string }>>([]);
  const [sections, setSections] = useState<InstrumentSectionConfig[]>([]);

  const [selectedSectionId, setSelectedSectionId] = useState<string | null>(null);

  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const cfgRes = await configurationApi.getConfig();
      const sectionCoverageEnabled = cfgRes?.values?.beta_section_coverage_enabled === true
        || cfgRes?.values?.beta_section_coverage_enabled === 1
        || cfgRes?.values?.beta_section_coverage_enabled === "1";
      if (!sectionCoverageEnabled) {
        showToast(
          label("js.configuration.instruments.setupDisabled", "Section setup is disabled. You were redirected to instrument minimums."),
          "default"
        );
        router.replace("/settings/instrument-minimums");
        return;
      }

      const res = await configurationApi.getInstrumentAdminData();
      const nextInstruments = Array.isArray(res?.instruments) ? res.instruments : [];
      const nextSectionsRaw = Array.isArray(res?.sections) ? res.sections : [];
      const nextSections = nextSectionsRaw.map((section, index) => ({
        id: String(section.id || createTemporaryId(index)),
        name: String(section.name || "").trim(),
        instrument_ids: Array.isArray(section.instrument_ids)
          ? section.instrument_ids.map((id) => Number(id)).filter((id) => id > 0)
          : [],
        rehearsal_min_total: Math.max(0, Number(section.rehearsal_min_total || 0)),
        concert_min_total: Math.max(0, Number(section.concert_min_total || 0)),
        concert_instrument_targets: Array.isArray(section.concert_instrument_targets)
          ? section.concert_instrument_targets
              .map((target) => ({
                instrument_id: Number(target.instrument_id || 0),
                required: Math.max(0, Number(target.required || 0)),
              }))
              .filter((target) => target.instrument_id > 0 && target.required > 0)
          : [],
      }));
      setInstruments(nextInstruments);
      setSections(nextSections);
      setSelectedSectionId((prev) => (prev && nextSections.some((section) => section.id === prev) ? prev : (nextSections[0]?.id ?? null)));
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToLoad"), "error");
    } finally {
      setLoading(false);
    }
  }, [router, showToast, t]);

  useEffect(() => {
    if (!ready) return;
    void load();
  }, [ready, load]);

  const instrumentOptions = useMemo(
    () =>
      instruments.map((instrument) => ({
        id: instrument.id,
        name: instrument.name,
        subtitle: instrument.category_name || undefined,
      })),
    [instruments]
  );

  const selectedSection = useMemo(
    () => sections.find((section) => section.id === selectedSectionId) ?? null,
    [sections, selectedSectionId]
  );

  function addSection() {
    const baseName = label("js.configuration.instruments.sectionDefaultName", "New section");
    const candidateName = `${baseName} ${sections.length + 1}`;
    const next: InstrumentSectionConfig = {
      id: createTemporaryId(sections.length + 1),
      name: candidateName,
      instrument_ids: [],
      rehearsal_min_total: 0,
      concert_min_total: 0,
      concert_instrument_targets: [],
    };
    setSections((prev) => [...prev, next]);
    setSelectedSectionId(next.id);
  }

  function updateSection(id: string, patch: Partial<InstrumentSectionConfig>) {
    setSections((prev) => prev.map((section) => (section.id === id ? { ...section, ...patch } : section)));
  }

  function deleteSection(id: string) {
    setSections((prev) => prev.filter((section) => section.id !== id));
    setSelectedSectionId((prev) => (prev === id ? null : prev));
  }

  function deleteConcertTarget(sectionId: string, index: number) {
    const section = sections.find((entry) => entry.id === sectionId);
    if (!section) return;
    const nextTargets = (section.concert_instrument_targets ?? []).filter(
      (_, targetIndex) => targetIndex !== index
    );
    updateSection(sectionId, { concert_instrument_targets: nextTargets });
  }

  async function saveSections() {
    setSavingSections(true);
    try {
      const sanitized = sections
        .map((section) => ({
          id: String(section.id ?? "").trim().startsWith("tmp_") ? "" : String(section.id ?? "").trim(),
          name: String(section.name ?? "").trim(),
          instrument_ids: (section.instrument_ids ?? []).map((id) => Number(id)).filter((id) => id > 0),
          rehearsal_min_total: Math.max(0, Math.floor(Number(section.rehearsal_min_total) || 0)),
          concert_min_total: Math.max(0, Math.floor(Number(section.concert_min_total) || 0)),
          concert_instrument_targets: (section.concert_instrument_targets ?? [])
            .map((target) => ({
              instrument_id: Number(target.instrument_id || 0),
              required: Math.max(0, Math.floor(Number(target.required || 0))),
            }))
            .filter((target) => target.instrument_id > 0 && target.required > 0),
        }))
        .filter((section) => section.name !== "");
      const res = await configurationApi.saveInstrumentSections(sanitized);
      const nextSections = Array.isArray(res?.sections) ? res.sections : sanitized;
      setSections(nextSections);
      setSelectedSectionId((prev) => (prev && nextSections.some((section) => section.id === prev) ? prev : (nextSections[0]?.id ?? null)));
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingSections(false);
    }
  }

  async function importDefaults() {
    setSeedingDefaults(true);
    try {
      await configurationApi.seedInstrumentDefaults();
      await load();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSeedingDefaults(false);
    }
  }

  async function applyBigBandPreset() {
    setApplyingBigBand(true);
    try {
      const res = await configurationApi.applyBigBandPresetMerge();
      if (Array.isArray(res?.sections)) {
        setSections(res.sections);
      }
      await load();
      showToast(label("js.settings.saved", "Saved"), "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setApplyingBigBand(false);
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
    <div className={`${PAGE_CONTENT_CLASS} space-y-6`}>
      <DetailPageHeader title={label("js.configuration.instruments.setupTitle", "Instrument setup")} />

      <DetailSection className="space-y-3">
        <p className="text-sm text-base-content/70">
          {label(
            "js.configuration.instruments.setupHelpSimplified",
            "Configure sections with rehearsal totals and optional concert seat targets. Instrument minimums are only used as fallback for instruments outside sections."
          )}
        </p>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="btn btn-soft"
            disabled={seedingDefaults}
            onClick={() => {
              void importDefaults();
            }}
          >
            {seedingDefaults
              ? label("js.common.saving", "Saving…")
              : label("js.configuration.instruments.seedDefaults", "Import default presets")}
          </button>
          <button
            type="button"
            className="btn btn-soft"
            disabled={applyingBigBand}
            onClick={() => {
              void applyBigBandPreset();
            }}
          >
            {applyingBigBand
              ? label("js.common.saving", "Saving…")
              : label("js.configuration.instruments.applyBigBandMerge", "Apply Big Band preset (merge missing only)")}
          </button>
          <button
            type="button"
            className="btn btn-soft"
            onClick={() => {
              void load();
            }}
          >
            {label("js.common.refresh", "Refresh")}
          </button>
        </div>
      </DetailSection>

      <DetailSection className="space-y-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 className="text-lg font-semibold text-base-content">
              {label("js.configuration.instruments.sectionsStructured", "Sections")}
            </h2>
            <p className="text-sm text-base-content/70">
              {label(
                "js.configuration.instruments.sectionsHelpSimplified",
                "Rehearsal checks use section totals. Concert checks use section totals and optional fixed required seats per instrument."
              )}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <button type="button" className="btn btn-soft" onClick={addSection}>
              {label("js.configuration.instruments.addSection", "Add section")}
            </button>
            <button
              type="button"
              className="btn btn-soft btn-primary"
              disabled={savingSections}
              onClick={() => {
                void saveSections();
              }}
            >
              {savingSections ? label("js.common.saving", "Saving…") : label("js.common.save", "Save")}
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-[340px_minmax(0,1fr)] gap-4">
          <div className="rounded-box border border-base-300 overflow-hidden">
            <div className="divide-y divide-base-300">
              {sections.map((section) => (
                <button
                  type="button"
                  key={section.id}
                  className={`w-full px-4 py-3 text-left ${selectedSectionId === section.id ? "bg-base-200/60" : ""}`}
                  onClick={() => setSelectedSectionId(section.id)}
                >
                  <div className="flex items-start justify-between gap-2">
                    <span className="font-medium text-sm">{section.name || label("js.common.untitled", "Untitled")}</span>
                    <span className="text-xs text-base-content/60">
                      {Math.max(0, Number(section.rehearsal_min_total || 0))} / {Math.max(0, Number(section.concert_min_total || 0))}
                    </span>
                  </div>
                </button>
              ))}
              {sections.length === 0 ? (
                <p className="px-4 py-3 text-sm text-base-content/60">{label("js.common.noData", "No data")}</p>
              ) : null}
            </div>
          </div>

          <div className="rounded-box border border-base-300 p-4 space-y-4">
            {selectedSection ? (
              <>
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <label className="form-control flex-1 min-w-[12rem]">
                    <span className="label-text text-xs font-medium text-base-content/70">
                      {label("js.common.name", "Name")}
                    </span>
                    <input
                      type="text"
                      className="input input-bordered"
                      value={selectedSection.name}
                      onChange={(event) => updateSection(selectedSection.id, { name: event.target.value })}
                    />
                  </label>
                  <button
                    type="button"
                    className="btn btn-outline btn-error btn-sm"
                    onClick={() => setPendingSectionDeleteId(selectedSection.id)}
                  >
                    {label("js.common.delete", "Delete")}
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <label className="form-control">
                    <span className="label-text text-xs font-medium text-base-content/70">
                      {label("js.configuration.instruments.rehearsalMinTotal", "Rehearsal minimum (total)")}
                    </span>
                    <input
                      type="number"
                      min={0}
                      className="input input-bordered"
                      value={selectedSection.rehearsal_min_total ?? 0}
                      onChange={(event) => {
                        const next = Math.max(0, Math.floor(Number(event.target.value) || 0));
                        updateSection(selectedSection.id, { rehearsal_min_total: next });
                      }}
                    />
                  </label>
                  <label className="form-control">
                    <span className="label-text text-xs font-medium text-base-content/70">
                      {label("js.configuration.instruments.concertMinTotal", "Concert minimum (total)")}
                    </span>
                    <input
                      type="number"
                      min={0}
                      className="input input-bordered"
                      value={selectedSection.concert_min_total ?? 0}
                      onChange={(event) => {
                        const next = Math.max(0, Math.floor(Number(event.target.value) || 0));
                        updateSection(selectedSection.id, { concert_min_total: next });
                      }}
                    />
                  </label>
                </div>

                <div>
                  <span className="label-text text-xs font-medium text-base-content/70 block mb-2">
                    {label("js.configuration.instruments.sectionInstruments", "Instruments in this section")}
                  </span>
                  <div className="max-h-72 overflow-auto rounded-box border border-base-300">
                    <table className="table table-sm">
                      <thead>
                        <tr>
                          <th>{label("js.common.name", "Name")}</th>
                          <th className="w-28">{label("js.configuration.instruments.targetCount", "Count")}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {instrumentOptions.map((instrument) => {
                          const counts = instrumentCountMap(selectedSection.instrument_ids);
                          const currentCount = counts.get(instrument.id) ?? 0;
                          return (
                            <tr key={instrument.id}>
                              <td>
                                <div className="flex flex-col">
                                  <span>{instrument.name}</span>
                                  {instrument.subtitle ? (
                                    <span className="text-xs text-base-content/60">{instrument.subtitle}</span>
                                  ) : null}
                                </div>
                              </td>
                              <td>
                                <input
                                  type="number"
                                  min={0}
                                  max={20}
                                  className="input input-bordered input-sm w-24"
                                  value={currentCount}
                                  onChange={(event) => {
                                    const nextCount = Math.max(0, Math.floor(Number(event.target.value) || 0));
                                    const nextMap = instrumentCountMap(selectedSection.instrument_ids);
                                    if (nextCount <= 0) nextMap.delete(instrument.id);
                                    else nextMap.set(instrument.id, nextCount);
                                    updateSection(selectedSection.id, { instrument_ids: expandInstrumentCounts(nextMap) });
                                  }}
                                />
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="space-y-2">
                  <p className="text-xs text-base-content/60">
                    {label(
                      "js.configuration.instruments.strictSeatHelp",
                      "Rehearsals use the section minimum. Concerts can additionally define fixed required seats per instrument."
                    )}
                  </p>
                  <div className="rounded-box border border-base-300 overflow-hidden">
                    <table className="table table-sm">
                      <thead>
                        <tr>
                          <th>{label("js.settings.minimums.instrument", "Instrument")}</th>
                          <th className="w-28">{label("js.configuration.instruments.targetCount", "Required")}</th>
                          <th className="w-24" />
                        </tr>
                      </thead>
                      <tbody>
                        {(selectedSection.concert_instrument_targets ?? []).map((target, index) => (
                          <tr key={`${target.instrument_id}-${index}`}>
                            <td>
                              <select
                                className="select select-bordered select-sm w-full"
                                value={target.instrument_id}
                                onChange={(event) => {
                                  const nextInstrumentId = Number(event.target.value || 0);
                                  const nextTargets = [...(selectedSection.concert_instrument_targets ?? [])];
                                  nextTargets[index] = {
                                    ...nextTargets[index],
                                    instrument_id: nextInstrumentId,
                                  };
                                  updateSection(selectedSection.id, {
                                    concert_instrument_targets: nextTargets.filter((item) => item.instrument_id > 0 && item.required > 0),
                                  });
                                }}
                              >
                                <option value={0}>{label("js.configuration.instruments.selectInstrument", "Select instrument")}</option>
                                {instrumentOptions.map((instrument) => (
                                  <option key={instrument.id} value={instrument.id}>
                                    {instrument.name}
                                  </option>
                                ))}
                              </select>
                            </td>
                            <td>
                              <input
                                type="number"
                                min={1}
                                className="input input-bordered input-sm w-24"
                                value={target.required}
                                onChange={(event) => {
                                  const nextRequired = Math.max(1, Math.floor(Number(event.target.value) || 1));
                                  const nextTargets = [...(selectedSection.concert_instrument_targets ?? [])];
                                  nextTargets[index] = {
                                    ...nextTargets[index],
                                    required: nextRequired,
                                  };
                                  updateSection(selectedSection.id, { concert_instrument_targets: nextTargets });
                                }}
                              />
                            </td>
                            <td>
                              <button
                                type="button"
                                className="btn btn-outline btn-error btn-xs"
                                onClick={() =>
                                  setPendingConcertTargetDelete({
                                    sectionId: selectedSection.id,
                                    index,
                                  })
                                }
                              >
                                {label("js.common.delete", "Delete")}
                              </button>
                            </td>
                          </tr>
                        ))}
                        {(selectedSection.concert_instrument_targets ?? []).length === 0 ? (
                          <tr>
                            <td colSpan={3} className="text-base-content/60">
                              {label("js.common.noData", "No data")}
                            </td>
                          </tr>
                        ) : null}
                      </tbody>
                    </table>
                  </div>
                  <button
                    type="button"
                    className="btn btn-soft btn-primary btn-sm"
                    onClick={() => {
                      const next = [...(selectedSection.concert_instrument_targets ?? []), { instrument_id: 0, required: 1 }];
                      updateSection(selectedSection.id, { concert_instrument_targets: next });
                    }}
                  >
                    {label("js.configuration.instruments.addStrictSeat", "Add strict seat target")}
                  </button>
                </div>
              </>
            ) : (
              <p className="text-sm text-base-content/60">
                {label("js.configuration.instruments.selectSectionHint", "Select or create a section to edit details.")}
              </p>
            )}
          </div>
        </div>
      </DetailSection>

      <DetailSection>
        <p className="text-sm text-base-content/70">
          {label(
            "js.configuration.instruments.fallbackHint",
            "Fallback instrument minimums are managed in Instrument Minimums and are only used for instruments not assigned to any section."
          )}
        </p>
      </DetailSection>

      <ConfirmModal
        open={pendingSectionDeleteId != null}
        onClose={() => setPendingSectionDeleteId(null)}
        title={label("js.common.confirmDeleteTitle", "Delete?")}
        message={label("js.common.confirmDeleteMessage", "This cannot be undone.")}
        confirmLabel={label("js.common.delete", "Delete")}
        cancelLabel={label("js.common.cancel", "Cancel")}
        onConfirm={() => {
          if (!pendingSectionDeleteId) return;
          deleteSection(pendingSectionDeleteId);
          setPendingSectionDeleteId(null);
        }}
        variant="danger"
      />

      <ConfirmModal
        open={pendingConcertTargetDelete != null}
        onClose={() => setPendingConcertTargetDelete(null)}
        title={label("js.common.confirmDeleteTitle", "Delete?")}
        message={label("js.common.confirmDeleteMessage", "This cannot be undone.")}
        confirmLabel={label("js.common.delete", "Delete")}
        cancelLabel={label("js.common.cancel", "Cancel")}
        onConfirm={() => {
          if (!pendingConcertTargetDelete) return;
          deleteConcertTarget(
            pendingConcertTargetDelete.sectionId,
            pendingConcertTargetDelete.index
          );
          setPendingConcertTargetDelete(null);
        }}
        variant="danger"
      />
    </div>
  );
}
