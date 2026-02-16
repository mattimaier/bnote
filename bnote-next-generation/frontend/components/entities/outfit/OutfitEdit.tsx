/**
 * BNote Next Generation - Outfit edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { outfitsApi, type OutfitDetail } from "@/lib/outfits-api";
import { getEntityPath } from "@/lib/entities/paths";
import { EditingBar } from "@/components/EditingBar";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";

export function OutfitEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const isNew = id === "new";

  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const loadOutfit = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    outfitsApi
      .get(numId)
      .then((o: OutfitDetail) => {
        setName(o.name ?? "");
        setDescription(o.description ?? "");
      })
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Failed to load")
      )
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadOutfit();
  }, [ready, loadOutfit]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      const payload = { name, description };
      if (isNew) {
        const res = await outfitsApi.create(payload);
        showToast(
          t("js.outfits.created") !== "js.outfits.created"
            ? t("js.outfits.created")
            : "Outfit created",
          "success"
        );
        router.replace(getEntityPath("outfit", res.id, "view"));
      } else {
        await outfitsApi.update(parseInt(id, 10), payload);
        showToast(
          t("js.common.saved") !== "js.common.saved"
            ? t("js.common.saved")
            : "Saved",
          "success"
        );
        router.replace(getEntityPath("outfit", id, "view"));
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
    if (isNew) router.push("/outfits");
    else router.push(getEntityPath("outfit", id, "view"));
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

  if (!isNew && error && !name) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-[var(--destructive)]">{error}</p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-4 md:space-y-6 md:p-6">
      <EditingBar
        isNew={isNew}
        saving={saving}
        onCancel={handleCancel}
        submitFormId="outfit-edit-form"
      />
      <form id="outfit-edit-form" onSubmit={handleSubmit} className="space-y-4">
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
                {t("js.outfits.name") !== "js.outfits.name"
                  ? t("js.outfits.name")
                  : "Name"}
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="input input-sm w-full"
                style={{ color: "var(--foreground)" }}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.outfits.description") !== "js.outfits.description"
                  ? t("js.outfits.description")
                  : "Description"}
              </label>
              <textarea
                rows={4}
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                className="textarea textarea-sm w-full"
              />
            </div>
          </div>
        </div>
      </form>

      {!isNew && (
        <DetailDeleteSection
          canDelete={true}
          entityTitle={name || undefined}
          onDelete={async () => {
            await outfitsApi.delete(parseInt(id, 10));
            showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
            router.push("/outfits/");
          }}
        />
      )}
    </div>
  );
}
