/**
 * BNote Next Generation - Event detail data loading hook
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { api } from "@/lib/api";
import { getErrorMessage } from "@/lib/error-utils";
import type { ConcertMeta, RehearsalMeta } from "@/lib/entities/event/types";

export type TranslateFn = (key: string) => string;

export interface UseEventDetailDataResult {
  data: Record<string, unknown> | null;
  meta: RehearsalMeta | ConcertMeta | null;
  loading: boolean;
  error: string;
  setError: (s: string) => void;
  reload: () => Promise<void>;
  loadMeta: () => Promise<void>;
}

export function useEventDetailData(
  type: string | undefined,
  id: string | undefined,
  initialData: Record<string, unknown> | undefined | null,
  ready: boolean,
  t: TranslateFn
): UseEventDetailDataResult {
  const [data, setData] = useState<Record<string, unknown> | null>(initialData ?? null);
  const [meta, setMeta] = useState<RehearsalMeta | ConcertMeta | null>(null);
  const [loading, setLoading] = useState(!initialData);
  const [error, setError] = useState("");

  const module = type === "rehearsal" ? "rehearsals" : "concerts";
  const isNew = id === "new";
  const numId = id && !isNew ? parseInt(String(id), 10) : NaN;

  const loadData = useCallback(async () => {
    if (!ready || !type || !id || isNaN(numId)) return;
    try {
      const result = await api.get<Record<string, unknown>>(module, "", { id: String(numId) });
      setData(result);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [ready, type, id, numId, module, t]);

  const loadMeta = useCallback(async () => {
    if (!ready) return;
    const canEdit = isNew || Boolean((data as { canEdit?: boolean } | null)?.canEdit);
    if (!canEdit) return;
    try {
      const result = await api.get<RehearsalMeta | ConcertMeta>(module, "meta");
      setMeta(result);
    } catch {
      setMeta(null);
    }
  }, [ready, module, data, isNew]);

  useEffect(() => {
    if (initialData != null) {
      setLoading(false);
      return;
    }
    if (!ready || !type || !id) {
      setLoading(false);
      return;
    }
    if (isNew) {
      setData({ canEdit: true, canEditParticipation: true });
      setLoading(false);
      return;
    }
    if (isNaN(numId)) {
      setLoading(false);
      return;
    }
    loadData();
  }, [ready, type, id, numId, loadData, initialData, isNew]);

  return {
    data,
    meta,
    loading,
    error,
    setError,
    reload: loadData,
    loadMeta,
  };
}
