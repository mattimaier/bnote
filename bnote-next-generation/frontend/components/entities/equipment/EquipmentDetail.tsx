/**
 * BNote Next Generation - Equipment detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { equipmentApi, type EquipmentDetail as EquipmentDetailType } from "@/lib/equipment-api";
import { getEntityPath } from "@/lib/entities/paths";
import { MarkdownText } from "@/components/MarkdownText";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";

export function EquipmentDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [item, setItem] = useState<EquipmentDetailType | null>(null);
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
    equipmentApi
      .get(numId)
      .then(setItem)
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Failed to load")
      )
      .finally(() => setLoading(false));
  }, [id, ready]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (id === "new") {
    router.replace(getEntityPath("equipment", "new", "edit"));
    return null;
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (error || !item) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-error">
          {error || "Equipment not found."}
        </p>
      </div>
    );
  }

  const notes = String(item.notes ?? "").trim();
  const emptyLabel = emptyText;

  return (
    <div className="mx-auto max-w-2xl space-y-4 p-4 md:space-y-6 md:p-6">
      <DetailPageHeader
        title={item.name || emptyText}
        right={<DetailEditButton onClick={() => router.push(getEntityPath("equipment", item.id, "edit"))} />}
      />

      <DetailCard className="space-y-4">
        <dl className="grid gap-3 sm:grid-cols-2">
          {item.make != null && item.make !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.equipment.make") !== "js.equipment.make"
                  ? t("js.equipment.make")
                  : "Make"}
              </dt>
              <dd>{item.make}</dd>
            </>
          )}
          {item.model != null && item.model !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.equipment.model") !== "js.equipment.model"
                  ? t("js.equipment.model")
                  : "Model"}
              </dt>
              <dd>{item.model}</dd>
            </>
          )}
          {item.quantity != null && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.equipment.quantity") !== "js.equipment.quantity"
                  ? t("js.equipment.quantity")
                  : "Quantity"}
              </dt>
              <dd>{item.quantity}</dd>
            </>
          )}
          {item.purchase_price != null && String(item.purchase_price).trim() !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.equipment.purchase_price") !== "js.equipment.purchase_price"
                  ? t("js.equipment.purchase_price")
                  : "Purchase price"}
              </dt>
              <dd>{item.purchase_price}</dd>
            </>
          )}
          {item.current_value != null && String(item.current_value).trim() !== "" && (
            <>
              <dt className="text-sm font-medium text-base-content/60">
                {t("js.equipment.current_value") !== "js.equipment.current_value"
                  ? t("js.equipment.current_value")
                  : "Current value"}
              </dt>
              <dd>{item.current_value}</dd>
            </>
          )}
        </dl>

        <div>
          <h2 className="text-sm font-semibold text-base-content/60">
            {t("js.equipment.notes") !== "js.equipment.notes"
              ? t("js.equipment.notes")
              : "Notes"}
          </h2>
          <div className="mt-1 prose prose-sm max-w-none dark:prose-invert">
            {notes.length > 0 ? <MarkdownText value={notes} /> : <p>{emptyLabel}</p>}
          </div>
        </div>
      </DetailCard>
    </div>
  );
}
