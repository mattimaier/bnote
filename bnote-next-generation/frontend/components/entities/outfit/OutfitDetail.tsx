/**
 * BNote Next Generation - Outfit detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { outfitsApi, type OutfitDetail as OutfitDetailType } from "@/lib/outfits-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { MarkdownText } from "@/components/MarkdownText";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

export function OutfitDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const [item, setItem] = useState<OutfitDetailType | null>(null);
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
    outfitsApi
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
    router.replace(getEntityPath("outfit", "new", "edit"));
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
          {error || "Outfit not found."}
        </p>
      </div>
    );
  }

  const description = String(item.description ?? "").trim();
  const emptyLabel = emptyText;

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader
        title={item.name || emptyText}
        right={<DetailEditButton onClick={() => router.push(getEntityPath("outfit", item.id, "edit"))} />}
      />

      <DetailCard className="space-y-4">
        <div>
          <h2 className="text-sm font-semibold text-base-content/60">
            {t("js.outfits.description") !== "js.outfits.description"
              ? t("js.outfits.description")
              : "Description"}
          </h2>
          <div className="mt-1 prose prose-sm max-w-none dark:prose-invert">
            {description.length > 0 ? <MarkdownText value={description} /> : <p>{emptyLabel}</p>}
          </div>
        </div>
      </DetailCard>
    </div>
  );
}
