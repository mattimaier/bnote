"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { AddressLink } from "@/components/AddressLink";
import { DatePicker } from "@/components/DatePicker";
import { SelectPicker } from "@/components/SelectPicker";
import { Spinner } from "@/components/Spinner";
import { MultiSelect } from "@/components/entities/event/MultiSelect";
import { SelectedItemsList } from "@/components/entities/event/SelectedItemsList";
import { ConfirmModal } from "@/components/ConfirmModal";
import { RehearsalsTable } from "@/components/rehearsals/RehearsalsTable";
import { ActionButton } from "@/components/ActionButton";
import { DETAIL_SECTION_CLASS } from "@/components/DetailSection";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { type RehearsalMeta, type SimpleOption } from "@/lib/entities/event/types";
import { getEntityPath } from "@/lib/entities/paths";
import { formatDateShortDisplay } from "@/lib/date-time";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { rehearsalsApi, type RehearsalListItem } from "@/lib/rehearsals-api";
import { type SortDirection } from "@/lib/table-sort";

interface SeriesFormState {
  name: string;
  cycle: 1 | 2;
  firstSession: string;
  lastSession: string;
  defaultTime: string;
  duration: string;
  location: number;
  conductor: number;
  groups: number[];
}

export default function RehearsalSeriesDetailPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang, formatDateTime } = useI18n();
  const { showToast } = useToast();
  const isNew = searchParams.get("new") === "1";
  const seriesId = useMemo(() => Number(searchParams.get("seriesId") ?? 0), [searchParams]);
  const isEditing = isNew || searchParams.get("edit") === "1";
  const [meta, setMeta] = useState<RehearsalMeta | null>(null);
  const [rehearsals, setRehearsals] = useState<RehearsalListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [showPropagationWarning, setShowPropagationWarning] = useState(false);
  const [error, setError] = useState("");
  const [sortKey, setSortKey] = useState<"begin" | "status" | "location" | "notes" | null>("begin");
  const [sortDir, setSortDir] = useState<SortDirection>("asc");
  const [originalForm, setOriginalForm] = useState<SeriesFormState | null>(null);
  const [form, setForm] = useState<SeriesFormState>({
    name: "",
    cycle: 1,
    firstSession: "",
    lastSession: "",
    defaultTime: "",
    duration: "",
    location: 0,
    conductor: 0,
    groups: [],
  });

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const metaResponse = (await rehearsalsApi.meta()) as unknown as RehearsalMeta;
      setMeta(metaResponse);
      if (isNew) {
        const firstLocationId = Number((metaResponse.locations ?? []).find((entry) => Number(entry.id) > 0)?.id ?? 0);
        setForm((prev) => ({
          ...prev,
          defaultTime: prev.defaultTime || String(metaResponse.defaultStartTime ?? "").slice(0, 5),
          duration: prev.duration || (Number(metaResponse.defaultDurationMinutes ?? 0) > 0 ? String(metaResponse.defaultDurationMinutes) : ""),
          location: prev.location > 0 ? prev.location : firstLocationId,
          conductor: prev.conductor > 0 ? prev.conductor : Number(metaResponse.defaultConductorId ?? 0),
        }));
        setOriginalForm(null);
        setRehearsals([]);
      } else {
        if (seriesId <= 0) {
          setError(t("js.rehearsals.series.error.invalidSeriesId"));
          setLoading(false);
          return;
        }
        const [series, seriesRehearsals] = await Promise.all([
          rehearsalsApi.getSeries(seriesId),
          rehearsalsApi.listBySeries(seriesId),
        ]);
        const loadedForm = {
          name: series.name ?? "",
          cycle: series.cycle ?? 1,
          firstSession: series.firstSession ?? "",
          lastSession: series.lastSession ?? "",
          defaultTime: series.defaultTime ?? "",
          duration: String(series.duration ?? ""),
          location: Number(series.location ?? 0),
          conductor: Number(series.conductor ?? 0),
          groups: series.groupIds ?? [],
        };
        setForm(loadedForm);
        setOriginalForm(loadedForm);
        setRehearsals(seriesRehearsals ?? []);
      }
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [isNew, seriesId, t]);

  useEffect(() => {
    if (!ready) return;
    void load();
  }, [ready, load]);

  const selectedCountLabel = (count: number) =>
    (t("js.common.selectedCount") !== "js.common.selectedCount" ? t("js.common.selectedCount") : "{count} selected").replace("{count}", String(count));

  const validate = () => {
    if (!form.name.trim()) return t("js.rehearsals.series.error.requiredName");
    if (!form.firstSession || !form.lastSession) return t("js.rehearsals.series.error.requiredDates");
    if (new Date(form.lastSession).getTime() < new Date(form.firstSession).getTime()) return t("js.rehearsals.series.error.dateRange");
    if (!form.defaultTime) return t("js.rehearsals.series.error.invalidTime");
    if (!form.duration || Number(form.duration) <= 0) return t("js.rehearsals.series.error.durationPositive");
    if (form.location <= 0) return t("js.rehearsals.series.error.requiredLocation");
    if (form.groups.length === 0) return t("js.rehearsals.series.error.requiredGroups");
    return "";
  };

  const hasPropagationOverrideChanges = () => {
    if (!originalForm || isNew) return false;
    const oldDuration = String(originalForm.duration ?? "").trim();
    const nextDuration = String(form.duration ?? "").trim();
    const oldGroups = [...(originalForm.groups ?? [])].map(Number).filter((id) => id > 0).sort((a, b) => a - b);
    const nextGroups = [...(form.groups ?? [])].map(Number).filter((id) => id > 0).sort((a, b) => a - b);
    const groupsChanged =
      oldGroups.length !== nextGroups.length || oldGroups.some((id, idx) => id !== nextGroups[idx]);
    return (
      Number(originalForm.location) !== Number(form.location) ||
      String(originalForm.defaultTime ?? "").trim() !== String(form.defaultTime ?? "").trim() ||
      oldDuration !== nextDuration ||
      groupsChanged
    );
  };

  const save = async (skipPropagationWarning = false) => {
    const validationMessage = validate();
    if (validationMessage) {
      setError(validationMessage);
      return;
    }
    if (!isNew && rehearsals.length > 0 && !skipPropagationWarning && hasPropagationOverrideChanges()) {
      setShowPropagationWarning(true);
      return;
    }
    setSaving(true);
    setError("");
    try {
      if (isNew) {
        const result = await rehearsalsApi.createSeries({
          name: form.name.trim(),
          cycle: form.cycle,
          firstSession: form.firstSession,
          lastSession: form.lastSession,
          defaultTime: form.defaultTime,
          duration: Number(form.duration),
          status: "planned",
          location: form.location,
          conductor: form.conductor,
          notes: "",
          groupIds: form.groups,
          contacts: [],
        });
        const successTpl = t("js.rehearsals.series.createSuccess") !== "js.rehearsals.series.createSuccess" ? t("js.rehearsals.series.createSuccess") : "Created {count} rehearsals.";
        showToast(successTpl.replace("{count}", String(result.createdCount)), "success");
      } else {
        const updateResult = await rehearsalsApi.updateSeries({
          id: seriesId,
          name: form.name.trim(),
          cycle: form.cycle,
          firstSession: form.firstSession,
          lastSession: form.lastSession,
          defaultTime: form.defaultTime,
          duration: Number(form.duration),
          status: "planned",
          location: form.location,
          conductor: form.conductor,
          notes: "",
          groupIds: form.groups,
          contacts: [],
        });
        const updateTpl = t("js.rehearsals.series.updateSuccessDetailed") !== "js.rehearsals.series.updateSuccessDetailed"
          ? t("js.rehearsals.series.updateSuccessDetailed")
          : "Updated {updated}, created {created}, removed {removed}.";
        showToast(
          updateTpl
            .replace("{updated}", String(updateResult.updatedRehearsals ?? 0))
            .replace("{created}", String(updateResult.createdRehearsals ?? 0))
            .replace("{removed}", String(updateResult.removedRehearsals ?? 0)),
          "success"
        );
      }
      router.push("/rehearsals/series");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.saveFailed"));
    } finally {
      setSaving(false);
    }
  };

  const deleteSeries = async () => {
    if (isNew || seriesId <= 0 || !isEditing) return;
    setDeleting(true);
    try {
      const result = await rehearsalsApi.deleteSeries(seriesId);
      const successTpl = t("js.rehearsals.series.deleteSuccess") !== "js.rehearsals.series.deleteSuccess" ? t("js.rehearsals.series.deleteSuccess") : "Deleted {count} rehearsals.";
      showToast(successTpl.replace("{count}", String(result.deletedCount)), "success");
      router.push("/rehearsals/series");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.deleteFailed"));
    } finally {
      setDeleting(false);
      setShowDeleteConfirm(false);
    }
  };

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const barTokenRef = useRef<number | null>(null);
  const toSeriesDetailPath = useCallback(
    (edit: boolean) => {
      const params = new URLSearchParams();
      if (isNew) {
        params.set("new", "1");
      } else if (seriesId > 0) {
        params.set("seriesId", String(seriesId));
      }
      if (edit) params.set("edit", "1");
      const query = params.toString();
      return query ? `/rehearsals/series/detail?${query}` : "/rehearsals/series/detail";
    },
    [isNew, seriesId]
  );

  useEffect(() => {
    if (!isEditing) return;
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "rehearsal-series-detail-form",
      onCancel: () => {
        if (isNew) router.push("/rehearsals/series");
        else {
          router.replace(toSeriesDetailPath(false));
          void load();
        }
      },
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isEditing, isNew, saving, setEditingBar, clearEditingBar, router, load, toSeriesDetailPath]);

  if (!ready || loading) {
    return <div className="flex items-center justify-center py-12"><Spinner /></div>;
  }

  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const locationOptions: SimpleOption[] = [{ id: 0, name: "-" }, ...((meta?.locations ?? []).map((entry) => ({ ...entry, name: entry.name || `#${entry.id}` })))];
  const conductorOptions: SimpleOption[] = [{ id: 0, name: "-" }, ...((meta?.conductors ?? []).map((entry) => ({ ...entry, name: entry.name || `#${entry.id}` })))];
  const localizeDate = (value: string) => formatDateShortDisplay(value, lang, emptyText);
  const locationName = locationOptions.find((entry) => entry.id === form.location)?.name || emptyText;
  const conductorName = conductorOptions.find((entry) => entry.id === form.conductor)?.name || emptyText;
  const pageTitle = form.name.trim() || (
    isNew
      ? (t("js.rehearsals.series.createTitle") !== "js.rehearsals.series.createTitle" ? t("js.rehearsals.series.createTitle") : "Create series")
      : (t("js.rehearsals.series.title") !== "js.rehearsals.series.title" ? t("js.rehearsals.series.title") : "Recurring rehearsal series")
  );

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
          {pageTitle}
        </h1>
        <div className="flex items-center gap-2">
          {!isEditing && (
            <ActionButton onClick={() => router.push(toSeriesDetailPath(true))} data-bnote-hotkey-action="edit">
              {t("js.common.edit") !== "js.common.edit" ? t("js.common.edit") : "Edit"}
            </ActionButton>
          )}
          {isEditing && !isNew && (
            <ActionButton
              variant="danger"
              onClick={() => setShowDeleteConfirm(true)}
              disabled={deleting}
              data-bnote-hotkey-action="delete"
            >
              {t("js.rehearsals.series.deleteButton") !== "js.rehearsals.series.deleteButton" ? t("js.rehearsals.series.deleteButton") : "Delete series"}
            </ActionButton>
          )}
        </div>
      </div>

      {error && <div className="rounded-lg border border-error bg-error/15 text-error px-4 py-3 text-sm">{error}</div>}

      <form id="rehearsal-series-detail-form" onSubmit={(event) => { event.preventDefault(); void save(); }} className="space-y-4">
        <section className={DETAIL_SECTION_CLASS}>
          <h2 className="text-lg font-semibold mb-3 md:mb-4 text-base-content">
            {t("js.event.detail.additionalInfo") !== "js.event.detail.additionalInfo"
              ? t("js.event.detail.additionalInfo")
              : "Details"}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div className="md:col-span-2">
              <span className="text-xs font-medium text-base-content/60">
                {(t("js.common.name") !== "js.common.name" ? t("js.common.name") : "Name")}:
              </span>
              {isEditing ? (
                <input className="ml-2 input input-sm text-base-content" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              ) : (
                <span className="ml-2 text-sm">{form.name || emptyText}</span>
              )}
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.rehearsals.series.firstSession")}:
              </span>
              {isEditing ? (
                <DatePicker value={form.firstSession} onChange={(next) => setForm({ ...form, firstSession: next })} mode="date" locale={lang} className="ml-2 input input-sm text-base-content" />
              ) : (
                <span className="ml-2 text-sm">{localizeDate(form.firstSession)}</span>
              )}
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.rehearsals.series.lastSession")}:
              </span>
              {isEditing ? (
                <DatePicker value={form.lastSession} onChange={(next) => setForm({ ...form, lastSession: next })} mode="date" locale={lang} className="ml-2 input input-sm text-base-content" />
              ) : (
                <span className="ml-2 text-sm">{localizeDate(form.lastSession)}</span>
              )}
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.rehearsals.series.cycle")}:
              </span>
              {isEditing ? (
                <span className="ml-2 inline-block align-middle">
                  <SelectPicker
                    options={[
                      { id: 1, name: t("js.rehearsals.series.weekly") !== "js.rehearsals.series.weekly" ? t("js.rehearsals.series.weekly") : "Weekly" },
                      { id: 2, name: t("js.rehearsals.series.biweekly") !== "js.rehearsals.series.biweekly" ? t("js.rehearsals.series.biweekly") : "Biweekly" },
                    ]}
                    value={form.cycle}
                    onChange={(next) => setForm({ ...form, cycle: next === 2 ? 2 : 1 })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                </span>
              ) : (
                <span className="ml-2 text-sm">
                  {form.cycle === 2
                    ? (t("js.rehearsals.series.biweekly") !== "js.rehearsals.series.biweekly" ? t("js.rehearsals.series.biweekly") : "Biweekly")
                    : (t("js.rehearsals.series.weekly") !== "js.rehearsals.series.weekly" ? t("js.rehearsals.series.weekly") : "Weekly")}
                </span>
              )}
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.rehearsals.series.startTime")}:
              </span>
              {isEditing ? (
                <DatePicker value={form.defaultTime} onChange={(next) => setForm({ ...form, defaultTime: next })} mode="time" locale={lang} className="ml-2 input input-sm text-base-content" />
              ) : (
                <span className="ml-2 text-sm">{form.defaultTime || emptyText}</span>
              )}
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.rehearsals.series.durationMinutes")}:
              </span>
              {isEditing ? (
                <input className="ml-2 input input-sm text-base-content" type="number" min={1} value={form.duration} onChange={(e) => setForm({ ...form, duration: e.target.value })} />
              ) : (
                <span className="ml-2 text-sm">{form.duration || emptyText}</span>
              )}
            </div>
            <div className="md:col-span-2">
              <span className="text-xs font-medium text-base-content/60">
                {(t("js.event.location") !== "js.event.location" ? t("js.event.location") : "Location")}:
              </span>
              {isEditing ? (
                <SelectPicker
                  options={locationOptions}
                  value={form.location}
                  onChange={(next) => setForm({ ...form, location: next })}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  emptyLabel="-"
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                />
              ) : (
                <span className="ml-2 text-sm">
                  {form.location > 0 ? (
                    <Link href={getEntityPath("location", form.location)} className="text-inherit no-underline">
                      <AddressLink value={locationName} t={t} renderRawIfNoAddress />
                    </Link>
                  ) : (
                    locationName
                  )}
                </span>
              )}
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {t("js.event.detail.conductor")}:
              </span>
              {isEditing ? (
                <SelectPicker
                  options={conductorOptions}
                  value={form.conductor}
                  onChange={(next) => setForm({ ...form, conductor: next })}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  emptyLabel="-"
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                />
              ) : (
                <span className="ml-2 text-sm">{conductorName}</span>
              )}
            </div>
          </div>

        </section>
      </form>

      {(isEditing || form.groups.length > 0) && (
        <section className={DETAIL_SECTION_CLASS}>
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold text-base-content">
              {t("js.event.metadata.besetzung") !== "js.event.metadata.besetzung"
                ? t("js.event.metadata.besetzung")
                : "Groups"}
            </h2>
            {isEditing ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={meta?.groups ?? []}
                  selected={form.groups}
                  onChange={(next) => setForm({ ...form, groups: next })}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={selectedCountLabel}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
              </div>
            ) : null}
          </div>
          {isEditing ? (
            <SelectedItemsList
              options={meta?.groups ?? []}
              selected={form.groups}
              onRemove={(id) => setForm({ ...form, groups: form.groups.filter((gid) => gid !== id) })}
              labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
              className="mt-4"
            />
          ) : (
            <div className="mt-4 text-sm">
              {(meta?.groups ?? [])
                .filter((entry) => form.groups.includes(entry.id))
                .map((entry) => entry.name || `#${entry.id}`)
                .join(", ") || emptyText}
            </div>
          )}
        </section>
      )}

      {!isNew && (
        <RehearsalsTable
          title={t("js.rehearsals.series.rehearsalsTitle") !== "js.rehearsals.series.rehearsalsTitle" ? t("js.rehearsals.series.rehearsalsTitle") : "Series rehearsals"}
          items={rehearsals}
          loading={false}
          emptyLabel={t("js.rehearsals.noRehearsals") !== "js.rehearsals.noRehearsals" ? t("js.rehearsals.noRehearsals") : "No rehearsals"}
          formatDateTime={formatDateTime}
          onRowClick={(rehearsalId) => router.push(getEntityPath("rehearsal", rehearsalId))}
          emptyText={emptyText}
          sortKey={sortKey}
          sortDir={sortDir}
          onSort={(nextKey) => {
            if (sortKey === nextKey) setSortDir((prev) => prev === "asc" ? "desc" : "asc");
            else { setSortKey(nextKey); setSortDir("asc"); }
          }}
          defaultSortKey="begin"
          defaultSortDir="asc"
          t={t}
          lang={lang}
        />
      )}

      <ConfirmModal
        open={showPropagationWarning}
        onClose={() => setShowPropagationWarning(false)}
        title={t("js.rehearsals.series.propagationWarningTitle") !== "js.rehearsals.series.propagationWarningTitle"
          ? t("js.rehearsals.series.propagationWarningTitle")
          : "Overwrite rehearsal data?"}
        message={t("js.rehearsals.series.propagationWarningMessage") !== "js.rehearsals.series.propagationWarningMessage"
          ? t("js.rehearsals.series.propagationWarningMessage")
          : "Changing groups, location, start time, or duration will overwrite these values for all rehearsals in this series."}
        confirmLabel={t("js.common.save") !== "js.common.save" ? t("js.common.save") : "Save"}
        cancelLabel={t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
        onConfirm={() => {
          setShowPropagationWarning(false);
          void save(true);
        }}
        variant="danger"
      />

      <ConfirmModal
        open={showDeleteConfirm}
        onClose={() => setShowDeleteConfirm(false)}
        title={t("js.rehearsals.series.deleteTitle") !== "js.rehearsals.series.deleteTitle" ? t("js.rehearsals.series.deleteTitle") : "Delete series?"}
        message={(t("js.rehearsals.series.deleteMessage") !== "js.rehearsals.series.deleteMessage"
          ? t("js.rehearsals.series.deleteMessage")
          : "Delete all rehearsals in this series? This cannot be undone.").replace("{name}", form.name || `#${seriesId}`)}
        confirmLabel={t("js.common.delete") !== "js.common.delete" ? t("js.common.delete") : "Delete"}
        cancelLabel={t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
        onConfirm={deleteSeries}
        variant="danger"
      />
    </div>
  );
}
