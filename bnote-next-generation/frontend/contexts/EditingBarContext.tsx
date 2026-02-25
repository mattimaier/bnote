/**
 * BNote Next Generation - Editing bar context
 * Pages in edit mode set bar props here; AppShell renders the bar between header and main.
 * Uses a token so cleanup only clears when the bar was set by this mount (avoids Strict Mode flicker).
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import React, { createContext, useCallback, useRef, useContext, useState } from "react";
import type { EditingBarProps } from "@/components/EditingBar";

type EditingBarState = EditingBarProps | null;

interface BarEntry {
  props: EditingBarProps;
  token: number;
}

interface EditingBarContextValue {
  editingBar: EditingBarState;
  /** Set bar props. Returns a token to pass to clearEditingBar in cleanup. */
  setEditingBar: (props: EditingBarState) => number | void;
  /** Clear only if the current bar has this token (avoids flicker from Strict Mode double-mount). */
  clearEditingBar: (token: number) => void;
}

const EditingBarContext = createContext<EditingBarContextValue | null>(null);

export function EditingBarProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<BarEntry | null>(null);
  const tokenRef = useRef(0);

  const setEditingBar = useCallback((props: EditingBarState): number | void => {
    if (props === null) {
      setState(null);
      return;
    }
    const token = ++tokenRef.current;
    setState({ props, token });
    return token;
  }, []);

  const clearEditingBar = useCallback((token: number) => {
    setState((prev) => (prev && prev.token === token ? null : prev));
  }, []);

  const editingBar = state?.props ?? null;

  return (
    <EditingBarContext.Provider value={{ editingBar, setEditingBar, clearEditingBar }}>
      {children}
    </EditingBarContext.Provider>
  );
}

export function useEditingBar() {
  const ctx = useContext(EditingBarContext);
  if (!ctx) throw new Error("useEditingBar must be used within EditingBarProvider");
  return ctx;
}
