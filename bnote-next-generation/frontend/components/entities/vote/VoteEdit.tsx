/**
 * BNote Next Generation - Vote edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { votesApi, type VoteDetail } from "@/lib/votes-api";
import { contactsApi, type ContactGroup } from "@/lib/contacts-api";
import { formatDateShortDisplay } from "@/lib/date-time";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { MultiSelect } from "@/components/entities/event/MultiSelect";
import { DatePicker } from "@/components/DatePicker";
import { RemoveOptionButton } from "@/components/RemoveOptionButton";
import { Plus } from "@/components/icons";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

export function VoteEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const isNew = id === "new";

  const [name, setName] = useState("");
  const [end, setEnd] = useState("");
  const [isDate, setIsDate] = useState(false);
  const [isMulti, setIsMulti] = useState(false);
  const [newOptionName, setNewOptionName] = useState("");
  const [newOptionDate, setNewOptionDate] = useState("");
  const [item, setItem] = useState<VoteDetail | null>(null);
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [selectedGroups, setSelectedGroups] = useState<number[]>([]);
  /** Pending options for new vote (before save) */
  const [pendingOptions, setPendingOptions] = useState<Array<{ id: string; name?: string; odate?: string }>>([]);
  const loadVote = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    votesApi
      .get(numId)
      .then((v: VoteDetail) => {
        setItem(v);
        setName(v.name ?? "");
        setEnd(v.end ?? "");
        setIsDate(v.is_date ?? false);
        setIsMulti(v.is_multi ?? false);
      })
      .catch((err) =>
        setError(getErrorMessage(err, t, "js.common.failedToLoad"))
      )
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadVote();
  }, [ready, loadVote]);

  useEffect(() => {
    if (!ready || !isNew) return;
    contactsApi.getGroups().then(setGroups).catch(() => setGroups([]));
  }, [ready, isNew]);

  const addPendingOption = () => {
    if (isDate) {
      if (!newOptionDate.trim()) return;
      setPendingOptions((prev) => [
        ...prev,
        { id: crypto.randomUUID(), odate: newOptionDate.trim() + "T00:00:00" },
      ]);
      setNewOptionDate("");
    } else {
      if (!newOptionName.trim()) return;
      setPendingOptions((prev) => [...prev, { id: crypto.randomUUID(), name: newOptionName.trim() }]);
      setNewOptionName("");
    }
  };

  const removePendingOption = (id: string) => {
    setPendingOptions((prev) => prev.filter((o) => o.id !== id));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (isNew && selectedGroups.length === 0) {
      setError(
        t("js.votes.groupsRequired") !== "js.votes.groupsRequired"
          ? t("js.votes.groupsRequired")
          : "At least one group must be selected"
      );
      showToast(
        t("js.votes.groupsRequired") !== "js.votes.groupsRequired"
          ? t("js.votes.groupsRequired")
          : "At least one group must be selected",
        "error"
      );
      return;
    }
    if (isNew && pendingOptions.length === 0) {
      setError(
        t("js.votes.optionsRequired") !== "js.votes.optionsRequired"
          ? t("js.votes.optionsRequired")
          : "Add at least one option"
      );
      showToast(
        t("js.votes.optionsRequired") !== "js.votes.optionsRequired"
          ? t("js.votes.optionsRequired")
          : "Add at least one option",
        "error"
      );
      return;
    }
    setSaving(true);
    setError("");
    try {
      if (isNew) {
        const endForApi = end ? end.replace(" ", "T") : "";
        const res = await votesApi.create({
          name,
          end: endForApi,
          is_date: isDate,
          is_multi: isMulti,
          groups: selectedGroups,
        });
        for (const opt of pendingOptions) {
          if (opt.odate) {
            await votesApi.addOption(res.id, { odate: opt.odate });
          } else if (opt.name) {
            await votesApi.addOption(res.id, { name: opt.name });
          }
        }
        showToast(
          t("js.votes.created") !== "js.votes.created"
            ? t("js.votes.created")
            : "Vote created",
          "success"
        );
        router.replace(getEntityPath("vote", res.id, "view"));
      } else {
        const endForApi = end ? end.replace(" ", "T") : "";
        await votesApi.update(parseInt(id, 10), { name, end: endForApi });
        showToast(
          t("js.common.saved") !== "js.common.saved"
            ? t("js.common.saved")
            : "Saved",
          "success"
        );
        router.replace(getEntityPath("vote", id, "view"));
      }
    } catch (err) {
      const msg = getErrorMessage(err, t, "js.common.saveFailed");
      setError(msg);
      showToast(msg, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleAddOption = async () => {
    if (!id || isNew) return;
    const numId = parseInt(id, 10);
    try {
      if (isDate) {
        const odate = newOptionDate.includes("T") ? newOptionDate : newOptionDate + "T00:00:00";
        await votesApi.addOption(numId, { odate });
        setNewOptionDate("");
      } else {
        await votesApi.addOption(numId, { name: newOptionName });
        setNewOptionName("");
      }
      loadVote();
    } catch (err) {
      showToast(
          err instanceof Error
            ? err.message
            : t("js.votes.addOptionFailed") !== "js.votes.addOptionFailed"
              ? t("js.votes.addOptionFailed")
              : "Add option failed",
          "error"
        );
    }
  };

  const handleAddOptionForDate = async (dateStr: string) => {
    if (!id || isNew || !dateStr) return;
    const numId = parseInt(id, 10);
    const odate = dateStr.includes("T") ? dateStr : dateStr + "T00:00:00";
    try {
      await votesApi.addOption(numId, { odate });
      loadVote();
    } catch (err) {
      showToast(
          err instanceof Error
            ? err.message
            : t("js.votes.addOptionFailed") !== "js.votes.addOptionFailed"
              ? t("js.votes.addOptionFailed")
              : "Add option failed",
          "error"
        );
    }
  };

  const handleRemoveOption = async (optionId: number) => {
    try {
      await votesApi.removeOption(optionId);
      loadVote();
    } catch (err) {
      showToast(
          err instanceof Error
            ? err.message
            : t("js.votes.removeFailed") !== "js.votes.removeFailed"
              ? t("js.votes.removeFailed")
              : "Remove failed",
          "error"
        );
    }
  };

  const handleCancel = useCallback(() => {
    if (isNew) router.push("/votes");
    else router.push(getEntityPath("vote", id, "view"));
  }, [isNew, id, router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "vote-edit-form",
      onCancel: () => onCancelRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isNew, saving, setEditingBar, clearEditingBar]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && error && !name) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">{error}</p>
      </div>
    );
  }

  const pageTitle = isNew
    ? (t("js.votes.addVote") !== "js.votes.addVote" ? t("js.votes.addVote") : "Add Vote")
    : (item?.name ?? (t("js.common.edit") !== "js.common.edit" ? t("js.common.edit") : "Edit"));
  const pageSubtitle = t("js.votes.subtitle") !== "js.votes.subtitle" ? t("js.votes.subtitle") : "Polls and voting";
  const canEditVote = isNew || Boolean(item?.can_edit ?? item?.is_author);

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div>
          <h1 className="text-2xl font-bold text-base-content">{pageTitle}</h1>
          <p className="mt-1 text-sm text-base-content/60">{pageSubtitle}</p>
        </div>
      </div>
      <form id="vote-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">
            {error}
          </div>
        )}

        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.votes.name") !== "js.votes.name" ? t("js.votes.name") : "Name"}
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.votes.endDate") !== "js.votes.endDate" ? t("js.votes.endDate") : "End (datetime)"}
              </label>
              <DatePicker
                value={end ? end.slice(0, 16) : ""}
                onChange={setEnd}
                mode="datetime"
                locale={lang}
                appendSeconds
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div className="flex flex-wrap gap-4">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={isDate}
                  onChange={(e) => setIsDate(e.target.checked)}
                  disabled={!isNew}
                  className="checkbox checkbox-primary checkbox-sm"
                />
                {t("js.votes.isDate") !== "js.votes.isDate" ? t("js.votes.isDate") : "Date vote"}
              </label>
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={isMulti}
                  onChange={(e) => setIsMulti(e.target.checked)}
                  disabled={!isNew}
                  className="checkbox checkbox-primary checkbox-sm"
                />
                {t("js.votes.isMulti") !== "js.votes.isMulti" ? t("js.votes.isMulti") : "Multiple choice"}
              </label>
            </div>
            {isNew && groups.length > 0 && (
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.votes.voterGroups") !== "js.votes.voterGroups" ? t("js.votes.voterGroups") : "Who can vote"}
                </label>
                <MultiSelect
                  options={groups.map((g) => ({ id: g.id, name: g.name }))}
                  selected={selectedGroups}
                  onChange={setSelectedGroups}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={(count) => {
                    const template = t("js.common.selectedCount");
                    if (template && template !== "js.common.selectedCount") {
                      return template.replace("{count}", String(count));
                    }
                    return `${count} selected`;
                  }}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
                <p className="mt-1 text-xs text-base-content/60">
                  {t("js.votes.groupsHint") !== "js.votes.groupsHint"
                    ? t("js.votes.groupsHint")
                    : "Members of selected groups can vote."}
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Options: show on create (pendingOptions) or on edit (item.options) */}
        {(isNew || item) && (
          <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
            <h2 className="text-sm font-semibold text-base-content/60">
              {t("js.votes.options") !== "js.votes.options" ? t("js.votes.options") : "Options"}
            </h2>
            <ul className="list-group mt-2 divide-y divide-base-300">
              {isNew
                ? pendingOptions.map((opt) => (
                    <li key={opt.id} className="list-group-item flex items-center justify-between gap-2 py-2">
                      <span>
                        {opt.odate
                          ? formatDateShortDisplay(opt.odate, lang)
                          : (opt.name ?? emptyText)}
                      </span>
                      <RemoveOptionButton
                        onClick={() => removePendingOption(opt.id)}
                        ariaLabel={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                      />
                    </li>
                  ))
                : item!.options.map((opt) => (
                    <li key={opt.id} className="list-group-item flex items-center justify-between gap-2 py-2">
                      <span>{opt.odate ? formatDateShortDisplay(opt.odate, lang) : (opt.name ?? emptyText)}</span>
                      {!item!.is_finished && canEditVote && (
                        <RemoveOptionButton
                          onClick={() => handleRemoveOption(opt.id)}
                          ariaLabel={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                        />
                      )}
                    </li>
                  ))}
            </ul>
            {((isNew && !item) || (item && !item.is_finished && canEditVote)) && (
              <div className="mt-4 space-y-3">
                {(isNew ? isDate : item!.is_date) ? (
                  <DatePicker
                    value={newOptionDate ? newOptionDate.slice(0, 10) : ""}
                    onChange={(val) => {
                      setNewOptionDate(val);
                      if (val) {
                        if (isNew) {
                          setPendingOptions((prev) => [
                            ...prev,
                            { id: crypto.randomUUID(), odate: val + "T00:00:00" },
                          ]);
                          setNewOptionDate("");
                        } else {
                          handleAddOptionForDate(val);
                        }
                      }
                    }}
                    mode="date"
                    locale={lang}
                    className="input input-sm w-full max-w-md text-base-content"
                  />
                ) : (
                  <div className="join join-horizontal w-full max-w-md">
                    <input
                      type="text"
                      value={newOptionName}
                      onChange={(e) => setNewOptionName(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          if (isNew) {
                            addPendingOption();
                          } else {
                            handleAddOption();
                          }
                        }
                      }}
                      placeholder={t("js.votes.optionName") !== "js.votes.optionName" ? t("js.votes.optionName") : "Option"}
                      className="input input-sm join-item flex-1 text-base-content"
                    />
                    <button
                      type="button"
                      onClick={isNew ? addPendingOption : handleAddOption}
                      disabled={!newOptionName.trim()}
                      className="btn btn-outline btn-sm join-item gap-1 text-base-content"
                    >
                      <Plus className="h-4 w-4" />
                      {t("js.votes.addOption") !== "js.votes.addOption" ? t("js.votes.addOption") : "Add"}
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </form>

      {!isNew && (
        <DetailDeleteSection
          canDelete={true}
          entityTitle={name || undefined}
          onDelete={async () => {
            await votesApi.delete(parseInt(id, 10));
            showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
            router.push("/votes/");
          }}
        />
      )}
    </div>
  );
}
