/**
 * BNote Next Generation - Equipment edit/create form
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
import { equipmentApi, type EquipmentDetail } from "@/lib/equipment-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { NotesEditor } from "@/components/NotesEditor";
import { Spinner } from "@/components/Spinner";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";
import { getErrorMessage } from "@/lib/error-utils";

export function EquipmentEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const isNew = id === "new";

  const [name, setName] = useState("");
  const [make, setMake] = useState("");
  const [model, setModel] = useState("");
  const [quantity, setQuantity] = useState<string>("");
  const [purchasePrice, setPurchasePrice] = useState("");
  const [currentValue, setCurrentValue] = useState("");
  const [notes, setNotes] = useState("");
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const loadEquipment = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    equipmentApi
      .get(numId)
      .then((eq: EquipmentDetail) => {
        setName(eq.name ?? "");
        setMake(eq.make ?? "");
        setModel(eq.model ?? "");
        setQuantity(eq.quantity != null ? String(eq.quantity) : "");
        setPurchasePrice(eq.purchase_price ?? "");
        setCurrentValue(eq.current_value ?? "");
        setNotes(eq.notes ?? "");
      })
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadEquipment();
  }, [ready, loadEquipment]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      const normalizedNotes = isEmptyEditorJson(notes) ? "" : notes;
      const payload = {
        name,
        make,
        model,
        quantity: quantity === "" ? undefined : parseInt(quantity, 10),
        purchase_price: purchasePrice || undefined,
        current_value: currentValue || undefined,
        notes: normalizedNotes,
      };
      if (isNew) {
        const res = await equipmentApi.create(payload);
        showToast(
          t("js.equipment.created") !== "js.equipment.created" ? t("js.equipment.created") : "Equipment created",
          "success"
        );
        router.replace(getEntityPath("equipment", res.id, "view"));
      } else {
        await equipmentApi.update(parseInt(id, 10), payload);
        showToast(t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved", "success");
        router.replace(getEntityPath("equipment", id, "view"));
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
    if (isNew) router.push("/equipment");
    else router.push(getEntityPath("equipment", id, "view"));
  }, [isNew, id, router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "equipment-edit-form",
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

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <form id="equipment-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error">{error}</div>
        )}

        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.equipment.name") !== "js.equipment.name" ? t("js.equipment.name") : "Name"}
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.equipment.make") !== "js.equipment.make" ? t("js.equipment.make") : "Make"}
                </label>
                <input
                  type="text"
                  value={make}
                  onChange={(e) => setMake(e.target.value)}
                  className="input input-sm w-full text-base-content"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.equipment.model") !== "js.equipment.model" ? t("js.equipment.model") : "Model"}
                </label>
                <input
                  type="text"
                  value={model}
                  onChange={(e) => setModel(e.target.value)}
                  className="input input-sm w-full text-base-content"
                />
              </div>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.equipment.quantity") !== "js.equipment.quantity" ? t("js.equipment.quantity") : "Quantity"}
              </label>
              <input
                type="number"
                min={0}
                value={quantity}
                onChange={(e) => setQuantity(e.target.value)}
                className="input input-sm w-full text-base-content"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.equipment.purchase_price") !== "js.equipment.purchase_price"
                    ? t("js.equipment.purchase_price")
                    : "Purchase price"}
                </label>
                <input
                  type="text"
                  value={purchasePrice}
                  onChange={(e) => setPurchasePrice(e.target.value)}
                  className="input input-sm w-full text-base-content"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">
                  {t("js.equipment.current_value") !== "js.equipment.current_value"
                    ? t("js.equipment.current_value")
                    : "Current value"}
                </label>
                <input
                  type="text"
                  value={currentValue}
                  onChange={(e) => setCurrentValue(e.target.value)}
                  className="input input-sm w-full text-base-content"
                />
              </div>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">
                {t("js.equipment.notes") !== "js.equipment.notes" ? t("js.equipment.notes") : "Notes"}
              </label>
              <NotesEditor
                value={notes}
                onChange={setNotes}
                placeholder={t("js.equipment.notes") !== "js.equipment.notes" ? t("js.equipment.notes") : "Notes"}
                id="equipment-notes-editor"
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
            await equipmentApi.delete(parseInt(id, 10));
            showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
            router.push("/equipment/");
          }}
        />
      )}
    </div>
  );
}
