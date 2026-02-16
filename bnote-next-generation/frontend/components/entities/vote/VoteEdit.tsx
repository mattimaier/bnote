/**
 * BNote Next Generation - Vote edit/create form (author)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { votesApi, type VoteDetail } from "@/lib/votes-api";
import { formatDateShortDisplay } from "@/lib/date-time";
import { getEntityPath } from "@/lib/entities/paths";
import { EditingBar } from "@/components/EditingBar";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { Plus, Trash2 } from "@/components/icons";

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
        setError(err instanceof Error ? err.message : "Failed to load")
      )
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadVote();
  }, [ready, loadVote]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      if (isNew) {
        const res = await votesApi.create({
          name,
          end,
          is_date: isDate,
          is_multi: isMulti,
          groups: [],
        });
        showToast(
          t("js.votes.created") !== "js.votes.created"
            ? t("js.votes.created")
            : "Vote created",
          "success"
        );
        router.replace(getEntityPath("vote", res.id, "edit"));
      } else {
        await votesApi.update(parseInt(id, 10), { name, end });
        showToast(
          t("js.common.saved") !== "js.common.saved"
            ? t("js.common.saved")
            : "Saved",
          "success"
        );
        loadVote();
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Save failed");
      showToast(err instanceof Error ? err.message : "Save failed", "error");
    } finally {
      setSaving(false);
    }
  };

  const handleAddOption = async () => {
    if (!id || isNew) return;
    const numId = parseInt(id, 10);
    try {
      if (isDate) {
        await votesApi.addOption(numId, { odate: newOptionDate });
        setNewOptionDate("");
      } else {
        await votesApi.addOption(numId, { name: newOptionName });
        setNewOptionName("");
      }
      loadVote();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Add option failed", "error");
    }
  };

  const handleRemoveOption = async (optionId: number) => {
    try {
      await votesApi.removeOption(optionId);
      loadVote();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Remove failed", "error");
    }
  };

  const handleFinish = async () => {
    if (!id || isNew) return;
    if (!confirm(t("js.votes.finishConfirm") !== "js.votes.finishConfirm" ? t("js.votes.finishConfirm") : "Finish this vote? Results will be final.")) return;
    try {
      await votesApi.finish(parseInt(id, 10));
      showToast(
        t("js.votes.finishDone") !== "js.votes.finishDone" ? t("js.votes.finishDone") : "Vote finished",
        "success"
      );
      loadVote();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Finish failed", "error");
    }
  };

  const handleCancel = () => {
    if (isNew) router.push("/votes");
    else router.push(getEntityPath("vote", id, "view"));
  };

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (!isNew && error && !name) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-error">{error}</p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-4 md:space-y-6 md:p-6">
      <EditingBar
        isNew={isNew}
        saving={saving}
        onCancel={handleCancel}
        submitFormId="vote-edit-form"
      />
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
              <input
                type="datetime-local"
                value={end ? end.replace(" ", "T").slice(0, 16) : ""}
                onChange={(e) => setEnd(e.target.value ? e.target.value.replace("T", " ") + ":00" : "")}
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
          </div>
        </div>

        {!isNew && item && (
          <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
            <h2 className="text-sm font-semibold text-base-content/60">
              {t("js.votes.options") !== "js.votes.options" ? t("js.votes.options") : "Options"}
            </h2>
            <ul className="mt-2 space-y-2">
              {item.options.map((opt) => (
                <li key={opt.id} className="flex items-center justify-between gap-2">
                  <span>{opt.odate ? formatDateShortDisplay(opt.odate, lang) : (opt.name ?? emptyText)}</span>
                  {!item.is_finished && item.is_author && (
                    <button
                      type="button"
                      onClick={() => handleRemoveOption(opt.id)}
                      className="rounded p-1 text-error hover:bg-error/20"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  )}
                </li>
              ))}
            </ul>
            {!item.is_finished && item.is_author && (
              <div className="mt-4 flex flex-wrap items-end gap-2">
                {item.is_date ? (
                  <input
                    type="datetime-local"
                    value={newOptionDate ? newOptionDate.replace(" ", "T").slice(0, 16) : ""}
                    onChange={(e) => setNewOptionDate(e.target.value ? e.target.value.replace("T", " ") + ":00" : "")}
                    className="input input-sm text-base-content"
                  />
                ) : (
                  <input
                    type="text"
                    value={newOptionName}
                    onChange={(e) => setNewOptionName(e.target.value)}
                    placeholder={t("js.votes.optionName") !== "js.votes.optionName" ? t("js.votes.optionName") : "Option"}
                    className="input input-sm text-base-content"
                  />
                )}
                <button
                  type="button"
                  onClick={handleAddOption}
                  className="btn btn-outline btn-sm gap-1 text-base-content"
                >
                  <Plus className="h-4 w-4" />
                  {t("js.votes.addOption") !== "js.votes.addOption" ? t("js.votes.addOption") : "Add"}
                </button>
              </div>
            )}
            {!item.is_finished && item.is_author && (
              <div className="mt-4">
                <button
                  type="button"
                  onClick={handleFinish}
                  className="btn btn-error btn-sm gap-2"
                >
                  {t("js.votes.finish") !== "js.votes.finish" ? t("js.votes.finish") : "Finish vote"}
                </button>
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
