/**
 * BNote Next Generation - Song edit/create form
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
import { repertoireApi, type SongDetail, type RepertoireMeta } from "@/lib/repertoire-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { SelectPicker } from "@/components/SelectPicker";
import { StatusPicker } from "@/components/entities/event/StatusPicker";
import { NotesEditor } from "@/components/NotesEditor";
import { Spinner } from "@/components/Spinner";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { getErrorMessage } from "@/lib/error-utils";

export function SongEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const isNew = id === "new";

  const [title, setTitle] = useState("");
  const [length, setLength] = useState("");
  const [genre, setGenre] = useState<string>("");
  const [bpm, setBpm] = useState<string>("");
  const [musicKey, setMusicKey] = useState("");
  const [composer, setComposer] = useState("");
  const [status, setStatus] = useState<string>("");
  const [setting, setSetting] = useState("");
  const [notes, setNotes] = useState("");
  const [isActive, setIsActive] = useState(true);
  const [meta, setMeta] = useState<RepertoireMeta | null>(null);
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const toStatusKey = (name?: string | null) => (name ?? "").trim().toLowerCase().replace(/\s+/g, "-");

  const loadMeta = useCallback(async () => {
    try {
      const m = await repertoireApi.meta();
      setMeta(m);
    } catch {
      setMeta({ genres: [], statuses: [], composers: [] });
    }
  }, []);

  const loadSong = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    repertoireApi
      .get(numId)
      .then((s: SongDetail) => {
        setTitle(s.title ?? "");
        setLength(s.length ?? "");
        setGenre(s.genre != null ? String(s.genre) : "");
        setBpm(s.bpm != null ? String(s.bpm) : "");
        setMusicKey(s.music_key ?? "");
        setComposer(s.composer ?? "");
        setStatus(s.status != null ? String(s.status) : "");
        setSetting(s.setting ?? "");
        setNotes(s.notes ?? "");
        setIsActive(s.is_active ?? true);
      })
      .catch((err) =>
        setError(getErrorMessage(err, t, "js.common.failedToLoad"))
      )
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadMeta();
  }, [ready, loadMeta]);

  useEffect(() => {
    if (!ready) return;
    loadSong();
  }, [ready, loadSong]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      const normalizedNotes = isEmptyEditorJson(notes) || !notes.trim() ? undefined : notes.trim();
      const payload = {
        title,
        length: length || undefined,
        genre: genre === "" ? undefined : parseInt(genre, 10),
        bpm: bpm === "" ? undefined : parseInt(bpm, 10),
        music_key: musicKey || undefined,
        composer: composer || undefined,
        status: status === "" ? undefined : parseInt(status, 10),
        setting: setting || undefined,
        notes: normalizedNotes,
        is_active: isActive,
      };
      if (isNew) {
        const res = await repertoireApi.create(payload);
        showToast(
          t("js.repertoire.created") !== "js.repertoire.created"
            ? t("js.repertoire.created")
            : "Song created",
          "success"
        );
        router.replace(getEntityPath("song", res.id, "view"));
      } else {
        await repertoireApi.update(parseInt(id, 10), payload);
        showToast(
          t("js.common.saved") !== "js.common.saved"
            ? t("js.common.saved")
            : "Saved",
          "success"
        );
        router.replace(getEntityPath("song", id, "view"));
      }
    } catch (err) {
      const msg = getErrorMessage(err, t, "js.common.saveFailed");
      setError(msg);
      showToast(msg, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = useCallback(() => {
    if (isNew) router.push("/repertoire");
    else router.push(getEntityPath("song", id, "view"));
  }, [isNew, id, router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "song-edit-form",
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

  if (!isNew && error && !title) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">{error}</p>
      </div>
    );
  }

  const genreOptions = [{ id: 0, name: emptyText }, ...(meta?.genres ?? [])];
  const statusOptions = [{ id: 0, name: emptyText }, ...(meta?.statuses ?? [])];
  const statusKeyById = new Map(
    statusOptions.map((opt) => [String(opt.id), opt.id === 0 ? "" : toStatusKey(opt.name)])
  );
  const statusIdByKey = new Map(
    statusOptions.map((opt) => [opt.id === 0 ? "" : toStatusKey(opt.name), opt.id === 0 ? "" : String(opt.id)])
  );
  const statusValueKey = statusKeyById.get(status) ?? "";

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <form id="song-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">
            {error}
          </div>
        )}

        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.repertoire.songTitle") !== "js.repertoire.songTitle"
                  ? t("js.repertoire.songTitle")
                  : "Title"}
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.repertoire.composer") !== "js.repertoire.composer"
                  ? t("js.repertoire.composer")
                  : "Composer"}
              </label>
              <input
                type="text"
                value={composer}
                onChange={(e) => setComposer(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.repertoire.genre") !== "js.repertoire.genre"
                    ? t("js.repertoire.genre")
                    : "Genre"}
                </label>
                <SelectPicker
                  options={genreOptions}
                  value={genre ? parseInt(genre, 10) : 0}
                  onChange={(next) => setGenre(next === 0 ? "" : String(next))}
                  emptyLabel={emptyText}
                  labelSelect={t("js.repertoire.genre") !== "js.repertoire.genre" ? t("js.repertoire.genre") : "Genre"}
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.repertoire.status") !== "js.repertoire.status"
                    ? t("js.repertoire.status")
                    : "Status"}
                </label>
                <StatusPicker
                  options={statusOptions.map((opt) => (opt.id === 0 ? "" : toStatusKey(opt.name)))}
                  value={statusValueKey}
                  onChange={(nextKey) => setStatus(statusIdByKey.get(nextKey) ?? "")}
                  labelFor={(value) => {
                    if (value === "") return emptyText;
                    const opt = statusOptions.find((s) => toStatusKey(s.name) === value);
                    return opt?.name ?? value;
                  }}
                />
              </div>
            </div>
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.repertoire.length") !== "js.repertoire.length"
                    ? t("js.repertoire.length")
                    : "Length"}
                </label>
                <input
                  type="text"
                  value={length}
                  onChange={(e) => setLength(e.target.value)}
                  placeholder="mm:ss"
                  className="input input-sm w-full text-base-content"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.repertoire.bpm") !== "js.repertoire.bpm"
                    ? t("js.repertoire.bpm")
                    : "BPM"}
                </label>
                <input
                  type="number"
                  min={0}
                  value={bpm}
                  onChange={(e) => setBpm(e.target.value)}
                  className="input input-sm w-full text-base-content"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.repertoire.musicKey") !== "js.repertoire.musicKey"
                    ? t("js.repertoire.musicKey")
                    : "Key"}
                </label>
                <input
                  type="text"
                  value={musicKey}
                  onChange={(e) => setMusicKey(e.target.value)}
                  className="input input-sm w-full text-base-content"
                />
              </div>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.repertoire.setting") !== "js.repertoire.setting"
                  ? t("js.repertoire.setting")
                  : "Setting"}
              </label>
              <input
                type="text"
                value={setting}
                onChange={(e) => setSetting(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.repertoire.notes") !== "js.repertoire.notes"
                  ? t("js.repertoire.notes")
                  : "Notes"}
              </label>
              <NotesEditor
                value={notes}
                onChange={setNotes}
                placeholder={t("js.repertoire.notes") !== "js.repertoire.notes" ? t("js.repertoire.notes") : "Notes"}
                id="song-notes-editor"
              />
            </div>
            <div className="flex flex-col gap-2">
              <label className="text-sm font-medium">
                {t("js.repertoire.isActive") !== "js.repertoire.isActive"
                  ? t("js.repertoire.isActive")
                  : "Active"}
              </label>
              <StatusPicker
                options={["active", "inactive"]}
                value={isActive ? "active" : "inactive"}
                onChange={(next) => setIsActive(next === "active")}
                labelFor={(value) =>
                  value === "active"
                    ? t("js.common.active") !== "js.common.active"
                      ? t("js.common.active")
                      : "Active"
                    : t("js.common.inactive") !== "js.common.inactive"
                      ? t("js.common.inactive")
                      : "Inactive"
                }
              />
            </div>
          </div>
        </div>
      </form>

      {!isNew && (
        <DetailDeleteSection
          canDelete={true}
          entityTitle={title || undefined}
          onDelete={async () => {
            await repertoireApi.delete(parseInt(id, 10));
            showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
            router.push("/repertoire/");
          }}
        />
      )}
    </div>
  );
}
