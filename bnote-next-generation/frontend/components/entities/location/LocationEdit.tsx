/**
 * BNote Next Generation - Location edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { locationsApi, type LocationDetail } from "@/lib/locations-api";
import { getEntityPath } from "@/lib/entities/paths";
import { EditingBar } from "@/components/EditingBar";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";

export function LocationEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const isNew = id === "new";

  const [name, setName] = useState("");
  const [notes, setNotes] = useState("");
  const [street, setStreet] = useState("");
  const [zip, setZip] = useState("");
  const [city, setCity] = useState("");
  const [state, setState] = useState("");
  const [country, setCountry] = useState("");
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const loadLocation = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    locationsApi
      .get(numId)
      .then((loc: LocationDetail) => {
        setName(loc.name ?? "");
        setNotes(loc.notes ?? "");
        setStreet(loc.street ?? "");
        setZip(loc.zip ?? "");
        setCity(loc.city ?? "");
        setState(loc.state ?? "");
        setCountry(loc.country ?? "");
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Failed to load"))
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadLocation();
  }, [ready, loadLocation]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      const payload = { name, notes, street, zip, city, state, country };
      if (isNew) {
        const res = await locationsApi.create(payload);
        showToast(
          t("js.locations.created") !== "js.locations.created" ? t("js.locations.created") : "Location created",
          "success"
        );
        router.replace(getEntityPath("location", res.id, "view"));
      } else {
        await locationsApi.update(parseInt(id, 10), payload);
        showToast(
          t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved",
          "success"
        );
        router.replace(getEntityPath("location", id, "view"));
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Save failed");
      showToast(err instanceof Error ? err.message : "Save failed", "error");
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    if (isNew) router.push("/locations");
    else router.push(getEntityPath("location", id, "view"));
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

  if (!isNew && error && !name && !city) {
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
        submitFormId="location-edit-form"
      />
      <form id="location-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div
            className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error"
          >
            {error}
          </div>
        )}

        <div
          className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content"
        >
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.locations.name") !== "js.locations.name" ? t("js.locations.name") : "Name"}
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="input input-sm w-full"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.locations.notes") !== "js.locations.notes" ? t("js.locations.notes") : "Notes"}
              </label>
              <textarea
                rows={3}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="textarea textarea-sm w-full"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.locations.street") !== "js.locations.street" ? t("js.locations.street") : "Street"}
              </label>
              <input
                type="text"
                value={street}
                onChange={(e) => setStreet(e.target.value)}
                className="input input-sm w-full"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.locations.zip") !== "js.locations.zip" ? t("js.locations.zip") : "ZIP"}
                </label>
                <input
                  type="text"
                  value={zip}
                  onChange={(e) => setZip(e.target.value)}
                  className="input input-sm w-full"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.locations.city") !== "js.locations.city" ? t("js.locations.city") : "City"}
                </label>
                <input
                  type="text"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  className="input input-sm w-full"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.locations.state") !== "js.locations.state" ? t("js.locations.state") : "State"}
                </label>
                <input
                  type="text"
                  value={state}
                  onChange={(e) => setState(e.target.value)}
                  className="input input-sm w-full"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.locations.country") !== "js.locations.country" ? t("js.locations.country") : "Country"}
                </label>
                <input
                  type="text"
                  value={country}
                  onChange={(e) => setCountry(e.target.value)}
                  className="input input-sm w-full"
                />
              </div>
            </div>
          </div>
        </div>
      </form>

      {!isNew && (
        <DetailDeleteSection
          canDelete={true}
          entityTitle={name || undefined}
          onDelete={async () => {
            await locationsApi.delete(parseInt(id, 10));
            showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
            router.push("/locations/");
          }}
        />
      )}
    </div>
  );
}
