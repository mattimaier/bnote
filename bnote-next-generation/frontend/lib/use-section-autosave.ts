"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";

export type SectionAutosaveStatus = "idle" | "saving" | "saved" | "error";

interface UseSectionAutosaveOptions<T> {
  value: T;
  enabled?: boolean;
  debounceMs?: number;
  save: (value: T) => Promise<void>;
  shouldSave?: (value: T) => boolean;
  toKey?: (value: T) => string;
  onError?: (error: unknown, value: T) => void;
}

interface UseSectionAutosaveResult {
  status: SectionAutosaveStatus;
  flush: () => Promise<void>;
  cancel: () => void;
}

function defaultToKey<T>(value: T): string {
  try {
    return JSON.stringify(value) ?? "";
  } catch {
    return String(value);
  }
}

export function useSectionAutosave<T>({
  value,
  enabled = true,
  debounceMs = 700,
  save,
  shouldSave,
  toKey = defaultToKey,
  onError,
}: UseSectionAutosaveOptions<T>): UseSectionAutosaveResult {
  const [status, setStatus] = useState<SectionAutosaveStatus>("idle");
  const mountedRef = useRef(false);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const inFlightRef = useRef(false);
  const queuedRef = useRef<T | null>(null);
  const lastSavedKeyRef = useRef<string>("");
  const latestValueRef = useRef<T>(value);
  const latestKey = useMemo(() => toKey(value), [toKey, value]);
  latestValueRef.current = value;

  const clearTimer = useCallback(() => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }
  }, []);

  const runSave = useCallback(
    async (nextValue: T) => {
      const nextKey = toKey(nextValue);
      if (!enabled) return;
      if (shouldSave && !shouldSave(nextValue)) return;
      if (nextKey === lastSavedKeyRef.current) return;
      if (inFlightRef.current) {
        queuedRef.current = nextValue;
        return;
      }

      inFlightRef.current = true;
      setStatus("saving");
      try {
        await save(nextValue);
        lastSavedKeyRef.current = nextKey;
        setStatus("saved");
      } catch (error) {
        setStatus("error");
        onError?.(error, nextValue);
      } finally {
        inFlightRef.current = false;
        const queued = queuedRef.current;
        queuedRef.current = null;
        if (queued) {
          const queuedKey = toKey(queued);
          if (queuedKey !== lastSavedKeyRef.current) {
            void runSave(queued);
          }
          return;
        }
        if (setStatus) {
          window.setTimeout(() => {
            setStatus((prev) => (prev === "saved" ? "idle" : prev));
          }, 1200);
        }
      }
    },
    [enabled, onError, save, shouldSave, toKey]
  );

  const flush = useCallback(async () => {
    clearTimer();
    await runSave(latestValueRef.current);
  }, [clearTimer, runSave]);

  const cancel = useCallback(() => {
    clearTimer();
    queuedRef.current = null;
  }, [clearTimer]);

  useEffect(() => {
    if (!enabled) return;
    if (!mountedRef.current) {
      mountedRef.current = true;
      lastSavedKeyRef.current = latestKey;
      return;
    }
    if (latestKey === lastSavedKeyRef.current) return;
    if (shouldSave && !shouldSave(value)) return;
    clearTimer();
    timerRef.current = setTimeout(() => {
      timerRef.current = null;
      void runSave(latestValueRef.current);
    }, debounceMs);
    return clearTimer;
  }, [clearTimer, debounceMs, enabled, latestKey, runSave, shouldSave, value]);

  useEffect(() => cancel, [cancel]);

  return { status, flush, cancel };
}
