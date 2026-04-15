/**
 * BNote Next Generation - Command palette
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useMemo, useState } from "react";
import { Modal } from "@/components/Modal";
import { useI18n } from "@/contexts/I18nContext";

export interface CommandPaletteAction {
  id: string;
  label: string;
  keywords?: string[];
  run: () => void;
}

interface AppCommandPaletteProps {
  open: boolean;
  onClose: () => void;
  actions: CommandPaletteAction[];
}

export function AppCommandPalette({ open, onClose, actions }: AppCommandPaletteProps) {
  const { t } = useI18n();
  const [query, setQuery] = useState("");
  const [selectedIndex, setSelectedIndex] = useState(0);

  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  const filteredActions = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return actions;
    return actions.filter((action) => {
      const haystack = [action.label, ...(action.keywords ?? [])].join(" ").toLowerCase();
      return haystack.includes(needle);
    });
  }, [actions, query]);

  const safeSelectedIndex = filteredActions.length > 0 ? Math.min(selectedIndex, filteredActions.length - 1) : 0;

  useEffect(() => {
    if (!open) return;
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "ArrowDown") {
        event.preventDefault();
        setSelectedIndex((prev) => {
          if (filteredActions.length === 0) return 0;
          return (prev + 1) % filteredActions.length;
        });
      } else if (event.key === "ArrowUp") {
        event.preventDefault();
        setSelectedIndex((prev) => {
          if (filteredActions.length === 0) return 0;
          return (prev - 1 + filteredActions.length) % filteredActions.length;
        });
      } else if (event.key === "Enter") {
        const action = filteredActions[safeSelectedIndex];
        if (!action) return;
        event.preventDefault();
        onClose();
        action.run();
      }
    };
    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [open, filteredActions, safeSelectedIndex, onClose]);

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={label("js.commandPalette.title", "Command palette")}
      dialogClassName="max-w-2xl"
    >
      <div className="space-y-3">
        <input
          type="search"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder={label("js.commandPalette.searchPlaceholder", "Type a command…")}
          autoFocus
          className="input input-bordered w-full"
          aria-label={label("js.commandPalette.searchAria", "Search commands")}
        />

        {filteredActions.length > 0 ? (
          <ul className="max-h-[48vh] overflow-y-auto rounded-box border border-base-300">
            {filteredActions.map((action, index) => {
              const selected = index === safeSelectedIndex;
              return (
                <li key={action.id}>
                  <button
                    type="button"
                    className={`w-full px-3 py-2 text-left text-sm transition-colors ${
                      selected ? "bg-primary/15 text-primary" : "hover:bg-base-200 text-base-content"
                    }`}
                    onClick={() => {
                      onClose();
                      action.run();
                    }}
                  >
                    {action.label}
                  </button>
                </li>
              );
            })}
          </ul>
        ) : (
          <p className="rounded-box border border-base-300 px-3 py-2 text-sm text-base-content/70">
            {label("js.commandPalette.noResults", "No commands found.")}
          </p>
        )}
      </div>
    </Modal>
  );
}
