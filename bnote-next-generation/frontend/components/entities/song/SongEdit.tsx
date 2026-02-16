/**
 * BNote Next Generation - Song edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { repertoireApi, type SongDetail, type RepertoireMeta } from "@/lib/repertoire-api";
import { getEntityPath } from "@/lib/entities/paths";
import { EditingBar } from "@/components/EditingBar";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { SelectPicker } from "@/components/SelectPicker";
import { StatusPicker } from "@/components/entities/event/StatusPicker";

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
        setError(err instanceof Error ? err.message : "Failed to load")
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
      const payload = {
        title,
        length: length || undefined,
        genre: genre === "" ? undefined : parseInt(genre, 10),
        bpm: bpm === "" ? undefined : parseInt(bpm, 10),
        music_key: musicKey || undefined,
        composer: composer || undefined,
        status: status === "" ? undefined : parseInt(status, 10),
        setting: setting || undefined,
        notes: notes || undefined,
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
      setError(err instanceof Error ? err.message : "Save failed");
      showToast(
        err instanceof Error ? err.message : "Save failed",
        "error"
      );
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    if (isNew) router.push("/repertoire");
    else router.push(getEntityPath("song", id, "view"));
  };

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (!isNew && error && !title) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-[var(--destructive)]">{error}</p>
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
    <div className="mx-auto max-w-4xl space-y-4 p-4 md:space-y-6 md:p-6">
      <EditingBar
        isNew={isNew}
        saving={saving}
        onCancel={handleCancel}
        submitFormId="song-edit-form"
      />
      <form id="song-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div
            className="rounded-lg border px-4 py-3 text-sm"
            style={{
              borderColor: "var(--destructive)",
              background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
              color: "var(--destructive-foreground)",
            }}
          >
            {error}
          </div>
        )}

        <div
          className="rounded-none border-0 shadow-none p-4 md:rounded-xl md:border md:shadow-sm md:p-6 bg-[var(--background)] md:bg-[var(--card)]"
          style={{
            borderColor: "var(--border)",
            color: "var(--card-foreground)",
          }}
        >
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
                className="input input-sm w-full"
                style={{ color: "var(--foreground)" }}
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
                className="input input-sm w-full"
                style={{ color: "var(--foreground)" }}
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
                  className="input input-sm w-full"
                  style={{ color: "var(--foreground)" }}
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
                  className="input input-sm w-full"
                  style={{ color: "var(--foreground)" }}
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
                  className="input input-sm w-full"
                  style={{ color: "var(--foreground)" }}
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
                className="input input-sm w-full"
                style={{ color: "var(--foreground)" }}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.repertoire.notes") !== "js.repertoire.notes"
                  ? t("js.repertoire.notes")
                  : "Notes"}
              </label>
              <textarea
                rows={3}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="textarea textarea-sm w-full"
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
