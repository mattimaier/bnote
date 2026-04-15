/**
 * BNote Next Generation - Internationalization Context
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

"use client";

import React, { createContext, useCallback, useContext, useEffect, useState } from "react";
import { api } from "@/lib/api";
import { formatDateShort, formatDateTimeShort, formatTimeShort } from "@/lib/date-time";

interface I18nState {
  lang: string;
  country: string | null;
  translations: Record<string, string>;
  ready: boolean;
}

const defaultState: I18nState = {
  lang: "de",
  country: null,
  translations: {},
  ready: false,
};

const I18nContext = createContext<
  I18nState & {
    t: (key: string, params?: string[]) => string;
    formatDate: (date: Date) => string;
    formatTime: (date: Date) => string;
    formatDateTime: (date: Date) => string;
  }
>({
  ...defaultState,
  t: (key) => key,
  formatDate: (d) => d.toLocaleDateString(),
  formatTime: (d) => d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
  formatDateTime: (d) => d.toLocaleString(),
});

export function I18nProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<I18nState>(defaultState);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const data = await api.get<{
          lang: string;
          translations: Record<string, string>;
        }>("translations", "get");
        if (cancelled) return;
        setState({
          lang: data?.lang ?? "de",
          country: null,
          translations: data?.translations ?? {},
          ready: true,
        });
      } catch {
        if (!cancelled) setState((s) => ({ ...s, ready: true }));
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const t = useCallback(
    (key: string, params?: string[]) => {
      let value = state.translations[key] ?? key;
      if (typeof value !== "string") value = key;
      if (params?.length) {
        params.forEach((p) => {
          value = value.replace(`%p`, String(p ?? ""));
        });
      }
      return value;
    },
    [state.translations]
  );

  const formatDate = useCallback(
    (date: Date) => {
      return formatDateShort(date, state.lang) ?? date.toLocaleDateString();
    },
    [state.lang]
  );

  const formatTime = useCallback(
    (date: Date) => {
      return formatTimeShort(date, state.lang) ?? date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    },
    [state.lang]
  );

  const formatDateTime = useCallback(
    (date: Date) => {
      return formatDateTimeShort(date, state.lang) ?? date.toLocaleString();
    },
    [state.lang]
  );

  return (
    <I18nContext.Provider
      value={{
        ...state,
        t,
        formatDate,
        formatTime,
        formatDateTime,
      }}
    >
      {children}
    </I18nContext.Provider>
  );
}

export function useI18n() {
  return useContext(I18nContext);
}
