/**
 * BNote Next Generation - Song detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { repertoireApi, type SongDetail as SongDetailType } from "@/lib/repertoire-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { NotesContent } from "@/components/NotesContent";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { getStatusPillStyle } from "@/lib/entity-config";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

export function SongDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [item, setItem] = useState<SongDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!id || id === "new" || !ready) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    repertoireApi
      .get(numId)
      .then(setItem)
      .catch((err) =>
        setError(getErrorMessage(err, t, "js.common.failedToLoad"))
      )
      .finally(() => setLoading(false));
  }, [id, ready]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (id === "new") {
    router.replace(getEntityPath("song", "new", "edit"));
    return null;
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">
          {error || "Song not found."}
        </p>
      </div>
    );
  }

  const isActiveLabel =
    item.is_active
      ? t("js.common.active") !== "js.common.active"
        ? t("js.common.active")
        : "Active"
      : t("js.common.inactive") !== "js.common.inactive"
        ? t("js.common.inactive")
        : "Inactive";
  const statusKey = item.is_active ? "active" : "inactive";
  const songStatusLabel = item.statusname ?? "";
  const songStatusKey = songStatusLabel.trim().toLowerCase().replace(/\s+/g, "-");
  const notes = String(item.notes ?? "").trim();
  const emptyLabel = emptyText;

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader
        title={item.title || emptyText}
        subtitle={
          (item.composer ?? item.genrename ?? item.statusname)
            ? [item.composer, item.genrename, item.statusname].filter(Boolean).join(" · ")
            : undefined
        }
        right={
          <DetailEditButton onClick={() => router.push(getEntityPath("song", item.id, "edit"))} />
        }
      />

      <DetailCard>
        <dl className="grid gap-3 sm:grid-cols-2">
          {item.composer && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.composer") !== "js.repertoire.composer"
                  ? t("js.repertoire.composer")
                  : "Composer"}
              </dt>
              <dd>{item.composer}</dd>
            </>
          )}
          {item.genrename && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.genre") !== "js.repertoire.genre" ? t("js.repertoire.genre") : "Genre"}
              </dt>
              <dd>{item.genrename}</dd>
            </>
          )}
          {item.length != null && item.length !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.length") !== "js.repertoire.length"
                  ? t("js.repertoire.length")
                  : "Length"}
              </dt>
              <dd>{item.length}</dd>
            </>
          )}
          {item.bpm != null && item.bpm > 0 && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.bpm") !== "js.repertoire.bpm"
                  ? t("js.repertoire.bpm")
                  : "BPM"}
              </dt>
              <dd>{item.bpm}</dd>
            </>
          )}
          {item.music_key != null && item.music_key !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.musicKey") !== "js.repertoire.musicKey"
                  ? t("js.repertoire.musicKey")
                  : "Key"}
              </dt>
              <dd>{item.music_key}</dd>
            </>
          )}
          {item.setting != null && item.setting !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.setting") !== "js.repertoire.setting"
                  ? t("js.repertoire.setting")
                  : "Setting"}
              </dt>
              <dd>{item.setting}</dd>
            </>
          )}
          {songStatusLabel && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.repertoire.status") !== "js.repertoire.status" ? t("js.repertoire.status") : "Status"}
              </dt>
              <dd>
                <span
                  className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
                  style={getStatusPillStyle(songStatusKey)}
                >
                  {songStatusLabel}
                </span>
              </dd>
            </>
          )}
          <dt className="text-sm font-medium text-base-content/60">
            {t("js.repertoire.isActive") !== "js.repertoire.isActive"
              ? t("js.repertoire.isActive")
              : "Active"}
          </dt>
          <dd>
            <span
              className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
              style={getStatusPillStyle(statusKey)}
            >
              {isActiveLabel}
            </span>
          </dd>
        </dl>

        <div className="mt-4">
          <h2 className="text-sm font-semibold text-base-content/60">
            {t("js.repertoire.notes") !== "js.repertoire.notes"
              ? t("js.repertoire.notes")
              : "Notes"}
          </h2>
          <div className="mt-1 prose prose-sm max-w-none dark:prose-invert">
            {notes.length > 0 ? <NotesContent value={notes} /> : <p>{emptyLabel}</p>}
          </div>
        </div>
      </DetailCard>
    </div>
  );
}
