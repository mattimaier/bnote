/**
 * BNote Next Generation - Entity Detail Page (rehearsal / concert)
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useCallback, useEffect, useRef, useState } from "react";
import { api } from "@/lib/api";
import { useI18n } from "@/contexts/I18nContext";
import { ParticipationWidget } from "@/components/ParticipationWidget";
import { ParticipationDiagram, type ParticipationStats } from "@/components/ParticipationDiagram";
import { ParticipantOverview, type InstrumentGroup } from "@/components/ParticipantOverview";
import { getIcon } from "@/components/icons";
import { getEventTypeConfig, type EventDisplayType } from "@/lib/event-utils";
import { safeString } from "@/lib/string-utils";
import { AddressLink } from "@/components/AddressLink";
import { MarkdownText } from "@/components/MarkdownText";
import { getAddressInfo } from "@/lib/address-utils";
import { formatDateShort, formatDateTimeShort, formatTimeShort } from "@/lib/date-time";
import { getStatusPillStyle } from "@/lib/entity-config";
import { ChevronLeft, LayoutList, Pencil, Save, Trash2, X } from "lucide-react";

interface LocationObj {
  id?: number;
  name?: string;
  address?: { street?: string; city?: string; zip?: string; state?: string; country?: string };
}

interface ConductorObj {
  id?: number;
  name?: string;
}

interface SongObj {
  id?: number;
  title?: string;
  notes?: string | null;
}

interface ProgramObj {
  id?: number;
  name?: string;
  notes?: string | null;
}

interface OutfitObj {
  id?: number;
  name?: string;
}

interface EquipmentObj {
  id?: number;
  name?: string;
}

interface GroupObj {
  id?: number;
  name?: string;
}

interface AccommodationObj {
  id?: number;
  name?: string;
  address?: Record<string, unknown>;
}

interface ContactObj {
  id?: number;
  name?: string;
  phone?: string;
  mobile?: string;
  email?: string;
}

interface SimpleOption {
  id: number;
  name: string | null;
  subtitle?: string | null;
}

interface SongOption {
  id: number;
  title: string;
}

interface RehearsalMeta {
  locations: SimpleOption[];
  groups: SimpleOption[];
  songs: SongOption[];
  conductors: SimpleOption[];
  contacts: SimpleOption[];
  statusOptions: string[];
  groupMembers?: Record<string, number[]>;
}

interface ConcertMeta {
  locations: SimpleOption[];
  groups: SimpleOption[];
  programs: SimpleOption[];
  outfits: SimpleOption[];
  equipment: SimpleOption[];
  contacts: SimpleOption[];
  statusOptions: string[];
  groupMembers?: Record<string, number[]>;
}

interface EventContact {
  id: number;
  name?: string | null;
}

interface EditableSong {
  id: number;
  title: string;
  notes: string;
}

interface EditableParticipant {
  userId: number;
  contactId: number;
  name: string;
  instrument: string;
  participate: "yes" | "maybe" | "no" | "pending";
}

const PARTICIPATION_BTN_BASE =
  "participation-btn w-9 h-9 md:w-10 md:h-10 rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-md";

function ParticipationTrafficLight({
  value,
  onChange,
  allowMaybe = true,
  disabled = false,
}: {
  value: EditableParticipant["participate"];
  onChange: (next: EditableParticipant["participate"]) => void;
  allowMaybe?: boolean;
  disabled?: boolean;
}) {
  const btnClass = (status: "yes" | "maybe" | "no", active: boolean) => {
    const roleClass =
      status === "yes"
        ? "participation-btn-yes"
        : status === "maybe"
          ? "participation-btn-maybe"
          : "participation-btn-no";
    const activeClass = active
      ? status === "yes"
        ? "participation-active-yes"
        : status === "maybe"
          ? "participation-active-maybe"
          : "participation-active-no"
      : "";
    return `${PARTICIPATION_BTN_BASE} ${roleClass} ${activeClass}`.trim();
  };

  const handleClick = (next: EditableParticipant["participate"]) => {
    if (disabled) return;
    if (next === value && value !== "pending") {
      onChange("pending");
      return;
    }
    onChange(next);
  };

  return (
    <div className="flex items-center gap-2">
      <button type="button" className={btnClass("yes", value === "yes")} onClick={() => handleClick("yes")}>
        <svg className="h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
        </svg>
      </button>
      {allowMaybe && (
        <button type="button" className={btnClass("maybe", value === "maybe")} onClick={() => handleClick("maybe")}>
          <svg className="h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </button>
      )}
      <button type="button" className={btnClass("no", value === "no")} onClick={() => handleClick("no")}>
        <svg className="h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  );
}

function MultiSelect({
  options,
  selected,
  onChange,
  placeholder = "Search…",
  showChips = true,
  labelSelect = "Select…",
  labelSelectedCount,
  labelNoSelection = "No selection",
  labelNoMatches = "No matches",
  labelClose = "Close",
  labelRemove = "Remove",
}: {
  options: SimpleOption[];
  selected: number[];
  onChange: (next: number[]) => void;
  placeholder?: string;
  showChips?: boolean;
  labelSelect?: string;
  labelSelectedCount?: (count: number) => string;
  labelNoSelection?: string;
  labelNoMatches?: string;
  labelClose?: string;
  labelRemove?: string;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const rootRef = useRef<HTMLDivElement | null>(null);
  const selectedSet = new Set(selected);
  const filtered = options.filter((opt) => (opt.name ?? "").toLowerCase().includes(query.toLowerCase()));
  const useFullscreen = options.length > 5;

  const toggle = (id: number) => {
    if (selectedSet.has(id)) {
      onChange(selected.filter((v) => v !== id));
    } else {
      onChange([...selected, id]);
    }
  };

  useEffect(() => {
    if (!open) return;
    const handle = (event: MouseEvent) => {
      if (!rootRef.current) return;
      if (!rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, [open]);

  return (
    <div ref={rootRef} className="relative w-full">
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="w-full rounded-md border px-3 py-2 text-sm font-medium"
        style={{
          borderColor: "var(--border)",
          color: "var(--foreground)",
          background: "color-mix(in oklch, var(--muted) 45%, var(--card))",
        }}
      >
        <span className="flex items-center justify-between gap-2">
          <span>{labelSelect}</span>
          <LayoutList className="h-4 w-4" />
        </span>
      </button>
      {showChips && (
        <div className="mt-4 w-full space-y-2">
          {selected.length === 0 && (
            <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
              {labelNoSelection}
            </span>
          )}
          {selected.map((id) => {
            const opt = options.find((o) => o.id === id);
            if (!opt?.name) return null;
            return (
              <div
                key={id}
                className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
                style={{ borderColor: "var(--border)" }}
              >
                <div className="flex flex-col">
                  <span className="font-medium">{opt.name}</span>
                  {opt.subtitle ? (
                    <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                      {opt.subtitle}
                    </span>
                  ) : null}
                </div>
                <button
                  type="button"
                  onClick={() => toggle(id)}
                  className="inline-flex items-center justify-center rounded-md border px-2 py-2 text-sm hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                  style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
                  aria-label={labelRemove}
                >
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            );
          })}
        </div>
      )}
      {open && !useFullscreen && (
        <div
          className="absolute left-0 top-full z-50 mt-2 w-full rounded-md border p-3 shadow-lg"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={placeholder}
            className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
            style={{ color: "var(--foreground)" }}
          />
          <div className="max-h-80 overflow-y-auto rounded-md border" style={{ borderColor: "var(--border)" }}>
              {filtered.map((opt) => (
              <label
                key={opt.id}
                className="flex items-center gap-3 px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                style={{ borderColor: "var(--border)" }}
              >
                <input type="checkbox" checked={selectedSet.has(opt.id)} onChange={() => toggle(opt.id)} />
                <span className="flex flex-col">
                    <span>{opt.name ?? "-"}</span>
                    {opt.subtitle ? (
                      <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                        {opt.subtitle}
                      </span>
                    ) : null}
                  </span>
                </label>
              ))}
            {filtered.length === 0 && (
              <div className="px-3 py-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
                {labelNoMatches}
              </div>
            )}
          </div>
        </div>
      )}
      {open && useFullscreen && (
        <div className="fixed inset-0 z-50">
          <div
            className="absolute inset-0"
            style={{ background: "color-mix(in oklch, var(--foreground) 20%, transparent)" }}
            onClick={() => setOpen(false)}
          />
          <div
            className="absolute inset-0 flex h-full w-full flex-col rounded-none border shadow-xl md:left-1/2 md:top-1/2 md:h-[90vh] md:w-[min(98vw,980px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-lg"
            style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
          >
          <div className="flex items-center justify-between border-b px-4 py-3" style={{ borderColor: "var(--border)" }}>
            <div className="text-sm font-semibold">{labelSelect}</div>
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="inline-flex items-center gap-2 rounded-md border px-3 py-1 text-sm"
              style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
            >
              {labelClose}
            </button>
          </div>
            <input
              type="search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder={placeholder}
              className="mx-4 mt-4 rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
              style={{ color: "var(--foreground)" }}
            />
            <div
              className="mx-4 mb-4 mt-3 flex-1 min-h-0 overflow-y-auto rounded-md border md:max-h-[78vh]"
              style={{ borderColor: "var(--border)" }}
            >
              {filtered.map((opt) => (
                <label
                  key={opt.id}
                  className="flex items-center gap-3 px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                  style={{ borderColor: "var(--border)" }}
                >
                  <input type="checkbox" checked={selectedSet.has(opt.id)} onChange={() => toggle(opt.id)} />
                  <span className="flex flex-col">
                    <span>{opt.name ?? "-"}</span>
                    {opt.subtitle ? (
                      <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                        {opt.subtitle}
                      </span>
                    ) : null}
                  </span>
                </label>
              ))}
              {filtered.length === 0 && (
                <div className="px-3 py-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
                  {labelNoMatches}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function SelectPicker({
  options,
  value,
  onChange,
  placeholder = "Search…",
  emptyLabel = "—",
  labelSelect = "Select…",
  labelNoMatches = "No matches",
  labelClose = "Close",
}: {
  options: SimpleOption[];
  value: number;
  onChange: (next: number) => void;
  placeholder?: string;
  emptyLabel?: string;
  labelSelect?: string;
  labelNoMatches?: string;
  labelClose?: string;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const rootRef = useRef<HTMLDivElement | null>(null);
  const filtered = options.filter((opt) => (opt.name ?? "").toLowerCase().includes(query.toLowerCase()));
  const selected = options.find((opt) => opt.id === value);
  const useFullscreen = options.length > 5;

  useEffect(() => {
    if (!open) return;
    const handle = (event: MouseEvent) => {
      if (!rootRef.current) return;
      if (!rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, [open]);

  return (
    <div ref={rootRef} className="relative space-y-2">
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="w-full rounded-md border px-3 py-2 text-sm font-medium"
        style={{
          borderColor: "var(--border)",
          color: "var(--foreground)",
          background: "color-mix(in oklch, var(--muted) 45%, var(--card))",
        }}
      >
        <span className="flex items-center justify-between gap-2">
          <span>{selected?.name ?? emptyLabel}</span>
          <LayoutList className="h-4 w-4" />
        </span>
      </button>
      {open && !useFullscreen && (
        <div
          className="absolute left-0 top-full z-50 mt-2 w-full rounded-md border p-3 shadow-lg"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={placeholder}
            className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
            style={{ color: "var(--foreground)" }}
          />
          <div className="max-h-80 overflow-y-auto rounded-md border" style={{ borderColor: "var(--border)" }}>
            {filtered.map((opt) => (
              <button
                key={opt.id}
                type="button"
                onClick={() => {
                  onChange(opt.id);
                  setOpen(false);
                }}
                className="w-full text-left px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              >
                {opt.name ?? emptyLabel}
              </button>
            ))}
            {filtered.length === 0 && (
              <div className="px-3 py-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
                {labelNoMatches}
              </div>
            )}
          </div>
        </div>
      )}
      {open && useFullscreen && (
        <div className="fixed inset-0 z-50">
          <div
            className="absolute inset-0"
            style={{ background: "color-mix(in oklch, var(--foreground) 20%, transparent)" }}
            onClick={() => setOpen(false)}
          />
          <div
            className="absolute inset-0 flex h-full w-full flex-col rounded-none border shadow-xl md:left-1/2 md:top-1/2 md:h-[90vh] md:w-[min(98vw,980px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-lg"
            style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
          >
            <div className="flex items-center justify-between border-b px-4 py-3" style={{ borderColor: "var(--border)" }}>
              <div className="text-sm font-semibold">{labelSelect}</div>
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="inline-flex items-center gap-2 rounded-md border px-3 py-1 text-sm"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              >
                {labelClose}
              </button>
            </div>
            <input
              type="search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder={placeholder}
              className="mx-4 mt-4 rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
              style={{ color: "var(--foreground)" }}
            />
            <div
              className="mx-4 mb-4 mt-3 flex-1 min-h-0 overflow-y-auto rounded-md border md:max-h-[78vh]"
              style={{ borderColor: "var(--border)" }}
            >
              {filtered.map((opt) => (
              <button
                key={opt.id}
                type="button"
                onClick={() => {
                  onChange(opt.id);
                  setOpen(false);
                }}
                className="w-full text-left px-3 py-3 text-sm border-b hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              >
                {opt.name ?? emptyLabel}
              </button>
              ))}
              {filtered.length === 0 && (
                <div className="px-3 py-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
                  {labelNoMatches}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function SelectedItemsList({
  options,
  selected,
  onRemove,
  labelRemove = "Remove",
  emptyLabel = "No selection",
  className = "",
}: {
  options: SimpleOption[];
  selected: number[];
  onRemove: (id: number) => void;
  labelRemove?: string;
  emptyLabel?: string;
  className?: string;
}) {
  if (selected.length === 0) {
    return (
      <div className={className}>
        <span className="text-sm" style={{ color: "var(--muted-foreground)" }}>
          {emptyLabel}
        </span>
      </div>
    );
  }

  return (
    <div className={`space-y-2 ${className}`.trim()}>
      {selected.map((id) => {
        const opt = options.find((o) => o.id === id);
        if (!opt?.name) return null;
        return (
          <div
            key={id}
            className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
            style={{ borderColor: "var(--border)" }}
          >
            <div className="flex flex-col">
              <span className="font-medium">{opt.name}</span>
              {opt.subtitle ? (
                <span className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                  {opt.subtitle}
                </span>
              ) : null}
            </div>
            <button
              type="button"
              onClick={() => onRemove(id)}
              className="inline-flex items-center justify-center rounded-md border px-2 py-2 text-sm hover:bg-[var(--muted)]/40 active:bg-[var(--muted)]/60"
              style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
              aria-label={labelRemove}
            >
              <Trash2 className="h-4 w-4" />
            </button>
          </div>
        );
      })}
    </div>
  );
}

function StatusPicker({
  options,
  value,
  onChange,
  labelFor,
}: {
  options: string[];
  value: string;
  onChange: (next: string) => void;
  labelFor?: (value: string) => string;
}) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);
  const displayLabel = labelFor ? labelFor(value) : value;

  useEffect(() => {
    if (!open) return;
    const handle = (event: MouseEvent) => {
      if (!rootRef.current) return;
      if (!rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handle);
    return () => document.removeEventListener("mousedown", handle);
  }, [open]);

  return (
    <div ref={rootRef} className="relative space-y-2">
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border"
        style={getStatusPillStyle(value)}
      >
        {displayLabel}
      </button>
      {open && (
        <div
          className="absolute left-0 top-full z-50 mt-2 min-w-[160px] rounded-md border p-2 shadow-lg"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <div className="flex flex-col gap-2">
            {options.map((opt) => (
              <button
                key={opt}
                type="button"
                onClick={() => {
                  onChange(opt);
                  setOpen(false);
                }}
                className="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-medium border"
                style={getStatusPillStyle(opt)}
              >
                {labelFor ? labelFor(opt) : opt}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

function ParticipantEditor({
  participants,
  onRemoveContact,
  onChange,
  t,
}: {
  participants: EditableParticipant[];
  onRemoveContact: (contactId: number) => void;
  onChange: (next: EditableParticipant[]) => void;
  t: (key: string) => string;
}) {
  const [query, setQuery] = useState("");
  const filtered = participants.filter((p) => {
    const haystack = `${p.name} ${p.instrument}`.toLowerCase();
    return haystack.includes(query.toLowerCase());
  });

  return (
    <div className="space-y-4">
      <input
        type="search"
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
        className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
        style={{ color: "var(--foreground)" }}
      />
      <div className="space-y-3 text-sm">
        {filtered.map((participant) => (
          <div
            key={`${participant.userId}-${participant.contactId}`}
            className="flex flex-col gap-3 rounded-md border px-3 py-2 md:flex-row md:items-center md:justify-between"
            style={{ borderColor: "var(--border)" }}
          >
            <div>
              <div className="font-medium">{participant.name}</div>
              <div className="text-xs" style={{ color: "var(--muted-foreground)" }}>
                {participant.instrument}
              </div>
            </div>
            <div className="flex items-center gap-3">
              <ParticipationTrafficLight
                value={participant.participate}
                onChange={(next) =>
                  onChange(
                    participants.map((entry) =>
                      entry.userId === participant.userId && entry.contactId === participant.contactId
                        ? { ...entry, participate: next }
                        : entry
                    )
                  )
                }
              />
              <button
                type="button"
                onClick={() => onRemoveContact(participant.contactId)}
                className="inline-flex items-center justify-center rounded-md border px-2 py-2 text-sm"
                style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
                aria-label={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          </div>
        ))}
        {participants.length === 0 && (
          <div className="text-sm" style={{ color: "var(--muted-foreground)" }}>
            {t("js.event.detail.noParticipants") !== "js.event.detail.noParticipants"
              ? t("js.event.detail.noParticipants")
              : "No participants available."}
          </div>
        )}
        {participants.length > 0 && filtered.length === 0 && (
          <div className="text-sm" style={{ color: "var(--muted-foreground)" }}>
            {t("js.common.noResults") !== "js.common.noResults" ? t("js.common.noResults") : "No results"}
          </div>
        )}
      </div>
    </div>
  );
}

function EntityDetailContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const type = searchParams.get("type") || "";
  const id = searchParams.get("id") || "";
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [meta, setMeta] = useState<RehearsalMeta | ConcertMeta | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState("");
  const [form, setForm] = useState<{
    title: string;
    begin: string;
    end: string;
    approveUntil: string;
    meetingtime: string;
    status: string;
    notes: string;
    organizer: string;
    payment: string;
    conditions: string;
    locationId: number;
    conductorId: number;
    contactId: number;
    programId: number;
    outfitId: number;
    accommodationId: number;
    groups: number[];
    equipment: number[];
    eventContacts: number[];
    manualContacts: number[];
    excludedContacts: number[];
    manualContactsInitialized: boolean;
    songs: EditableSong[];
    participants: EditableParticipant[];
  } | null>(null);

  const toInputDateTime = (value?: string) => {
    if (!value) return "";
    const normalized = value.replace(" ", "T");
    return normalized.length >= 16 ? normalized.slice(0, 16) : normalized;
  };

  const fromInputDateTime = (value: string) => {
    if (!value) return "";
    const normalized = value.replace(" ", "T");
    return normalized.length === 16 ? `${normalized}:00` : normalized;
  };

  const syncEndDate = (startValue: string, endValue: string) => {
    if (!startValue) return endValue;
    const [startDate] = startValue.split("T");
    if (!endValue) return `${startDate}T${startValue.split("T")[1] ?? "00:00"}`;
    const [, endTime] = endValue.split("T");
    return `${startDate}T${endTime ?? "00:00"}`;
  };

  const mapParticipationToStatus = (value: number | null | undefined): EditableParticipant["participate"] => {
    if (value === 1) return "yes";
    if (value === 2) return "maybe";
    if (value === 0) return "no";
    return "pending";
  };

  const mapStatusToParticipation = (value: EditableParticipant["participate"]) => {
    if (value === "yes") return 1;
    if (value === "maybe") return 2;
    if (value === "no") return 0;
    return null;
  };

  const arraysEqual = (a: number[], b: number[]) => {
    if (a.length !== b.length) return false;
    const aSorted = [...a].sort((x, y) => x - y);
    const bSorted = [...b].sort((x, y) => x - y);
    return aSorted.every((val, idx) => val === bSorted[idx]);
  };

  const getGroupContacts = (groupIds: number[]) => {
    const members =
      type === "concert" ? (meta as ConcertMeta | null)?.groupMembers : (meta as RehearsalMeta | null)?.groupMembers;
    const result = new Set<number>();
    if (!members) return result;
    groupIds.forEach((groupId) => {
      const ids = members[String(groupId)] ?? [];
      ids.forEach((id) => result.add(id));
    });
    return result;
  };

  const module = type === "rehearsal" ? "rehearsals" : "concerts";
  const numId = id ? parseInt(String(id), 10) : NaN;
  const eventType = type === "rehearsal" ? "R" : "C";
  const canEdit = Boolean((data as { canEdit?: boolean } | null)?.canEdit);
  const canEditParticipation = Boolean((data as { canEditParticipation?: boolean } | null)?.canEditParticipation);

  const loadData = useCallback(async () => {
    if (!ready || !type || !id || isNaN(numId)) return;
    try {
      const result = await api.get<Record<string, unknown>>(module, "", { id: String(numId) });
      setData(result);
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load");
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [ready, type, id, numId, module]);

  const loadMeta = useCallback(async () => {
    if (!ready || !canEdit) return;
    try {
      const result = await api.get<RehearsalMeta | ConcertMeta>(module, "meta");
      setMeta(result);
    } catch {
      setMeta(null);
    }
  }, [ready, canEdit, module]);

  useEffect(() => {
    if (!ready || !type || !id || isNaN(numId)) {
      setLoading(false);
      return;
    }
    loadData();
  }, [ready, type, id, numId, loadData]);

  const rehearsalMeta = type === "rehearsal" ? (meta as RehearsalMeta | null) : null;
  const concertMeta = type === "concert" ? (meta as ConcertMeta | null) : null;
  const shouldEdit = searchParams.get("edit") === "1";

  useEffect(() => {
    if (!shouldEdit || !canEdit || !data) return;
    if (!isEditing) {
      setForm(buildForm());
      setIsEditing(true);
      if (!meta) {
        loadMeta();
      }
    }
  }, [shouldEdit, canEdit, data, isEditing, meta, loadMeta]);

  useEffect(() => {
    if (shouldEdit || !isEditing) return;
    setIsEditing(false);
    setForm(null);
    setSaveError("");
  }, [shouldEdit, isEditing]);

  useEffect(() => {
    if (!isEditing || !form || form.manualContactsInitialized || !meta) return;
    const groupContacts = getGroupContacts(form.groups);
    const nextManual = form.eventContacts.filter((id) => !groupContacts.has(id));
    setForm({ ...form, manualContacts: nextManual, manualContactsInitialized: true });
  }, [isEditing, form?.manualContactsInitialized, form?.groups, form?.eventContacts, form, meta]);

  useEffect(() => {
    if (!isEditing || !form) return;
    const groupContacts = getGroupContacts(form.groups);
    const manualContacts = new Set(form.manualContacts);
    const validExcluded = form.excludedContacts.filter((id) => groupContacts.has(id));
    const excludedContacts = new Set(validExcluded);
    const nextContacts = new Set<number>();

    groupContacts.forEach((id) => {
      if (!excludedContacts.has(id)) {
        nextContacts.add(id);
      }
    });
    manualContacts.forEach((id) => nextContacts.add(id));

    const nextList = Array.from(nextContacts);
    if (!arraysEqual(nextList, form.eventContacts) || !arraysEqual(validExcluded, form.excludedContacts)) {
      setForm({ ...form, eventContacts: nextList, excludedContacts: validExcluded });
    }
  }, [isEditing, form?.groups, form?.manualContacts, form?.excludedContacts, form, meta, type, setForm]);

  useEffect(() => {
    if (!isEditing || !form) return;
    const contactOptions = (type === "concert" ? concertMeta?.contacts : rehearsalMeta?.contacts) ?? [];
    const currentByContact = new Map(form.participants.map((p) => [p.contactId, p]));
    const nextParticipants: EditableParticipant[] = [];

    form.eventContacts.forEach((contactId) => {
      const existing = currentByContact.get(contactId);
      if (existing) {
        nextParticipants.push(existing);
        return;
      }
      const fallback = contactOptions.find((c) => c.id === contactId);
      nextParticipants.push({
        userId: 0,
        contactId,
        name: fallback?.name ?? "—",
        instrument: "—",
        participate: "pending",
      });
    });

    if (
      nextParticipants.length !== form.participants.length ||
      nextParticipants.some((p, idx) => form.participants[idx]?.contactId !== p.contactId)
    ) {
      setForm({ ...form, participants: nextParticipants });
    }
  }, [isEditing, form?.eventContacts, form?.participants, form, setForm, type, concertMeta, rehearsalMeta]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="mx-auto max-w-4xl space-y-4">
        <Link
          href="/dashboard"
          className="inline-flex items-center gap-1 text-sm font-medium"
          style={{ color: "var(--primary)" }}
        >
          <ChevronLeft className="h-4 w-4" />
          {t("js.dashboard.backToDashboard")}
        </Link>
        <div
          className="rounded-lg border px-4 py-3"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {error || "Not found"}
        </div>
      </div>
    );
  }

  const loc = data.location as LocationObj | undefined;
  const conductor = data.conductor as ConductorObj | undefined;
  const locationName = safeString(loc?.name);
  const locationAddressInfo = loc?.address ? getAddressInfo(loc.address) : null;
  const hasLocationName = Boolean(locationName);
  const hasLocationAddress = Boolean(locationAddressInfo);
  const conductorName = safeString(conductor?.name);
  const title =
    safeString(data.title) ||
    locationName ||
    conductorName ||
    (type === "concert" ? t("js.event.performance") : t("js.event.rehearsal"));
  const begin = (data.begin ?? data.date ?? data.event_begin) as string | undefined;
  const end = data.end as string | undefined;
  const tba = t("js.event.tba");
  const dateStr = formatDateShort(begin, lang) ?? tba;
  const timeStr = formatTimeShort(begin, lang) ?? tba;
  const endTimeStr = end ? formatTimeShort(end, lang) ?? tba : null;
  const status = (data.status as string) ?? "planned";
  const approveUntil = data.approve_until as string | undefined;
  const notes = data.notes as string | undefined;
  const organizer = data.organizer as string | undefined;
  const meetingtime = data.meetingtime as string | undefined;
  const songsToPractice = (data.songsToPractice ?? data.songs_to_practice) as SongObj[] | undefined;
  const participationStats = data.participationStats as ParticipationStats | undefined;
  const participantsByInstrument = (data.participantsByInstrument ?? data.participants_by_instrument) as
    | InstrumentGroup[]
    | undefined;
  const eventContacts = (data.eventContacts ?? []) as EventContact[];

  // Concert metadata
  const groups = (data.groups ?? []) as GroupObj[];
  const program = data.program as ProgramObj | undefined;
  const outfit = data.outfit as OutfitObj | undefined;
  const equipment = (data.equipment ?? []) as EquipmentObj[];
  const accommodation = data.accommodation as AccommodationObj | undefined;
  const payment = data.payment as number | null | undefined;
  const conditions = data.conditions as string | undefined;
  const contact = data.contact as ContactObj | undefined;

  const displayType: EventDisplayType = type === "concert" ? "performance" : (type as EventDisplayType);
  const eventTypeConfig = getEventTypeConfig(displayType, t);
  const EventIcon = getIcon(eventTypeConfig.icon);

  const statusLabel =
    status === "confirmed"
      ? t("js.event.status.confirmed")
      : status === "cancelled"
        ? t("js.event.status.cancelled")
        : t("js.event.status.planned");
  const statusLabelFor = (value: string) =>
    value === "confirmed"
      ? t("js.event.status.confirmed")
      : value === "cancelled"
        ? t("js.event.status.cancelled")
        : value === "hidden"
          ? t("js.event.status.hidden")
          : t("js.event.status.planned");
  const selectedCountLabel = (count: number) =>
    (t("js.common.selectedCount") !== "js.common.selectedCount" ? t("js.common.selectedCount") : "{count} selected").replace(
      "{count}",
      String(count)
    );

  const safeDate = (value?: string) => {
    if (!value) return null;
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
  };
  const now = new Date();
  const eventEnd = safeDate(end) ?? safeDate(begin);
  const deadlineDate = safeDate(approveUntil);
  const isPastEvent = eventEnd ? eventEnd.getTime() < now.getTime() : false;
  const isPastDeadline = deadlineDate ? deadlineDate.getTime() < now.getTime() : false;
  const participationDisabled = isPastEvent || isPastDeadline;

  const buildForm = () => {
    const participantRows =
      participantsByInstrument?.flatMap((group) =>
        group.participants
          .filter((p) => typeof p.userId === "number")
          .map((p) => ({
            userId: p.userId,
            contactId: p.id,
            name: p.name,
            instrument: group.instrument.name,
            participate: mapParticipationToStatus(p.participate),
          }))
      ) ?? [];

    const initialContacts = Array.from(
      new Set(
        eventContacts.length > 0 ? eventContacts.map((c) => c.id) : participantRows.map((p) => p.contactId)
      )
    );

    return {
      title: safeString(data.title) || "",
      begin: toInputDateTime(begin),
      end: toInputDateTime(end),
      approveUntil: toInputDateTime(approveUntil),
      meetingtime: toInputDateTime(meetingtime),
      status,
      notes: notes ?? "",
      organizer: organizer ?? "",
      payment: payment != null ? String(payment) : "",
      conditions: conditions ?? "",
      locationId: loc?.id ?? 0,
      conductorId: conductor?.id ?? 0,
      contactId: contact?.id ?? 0,
      programId: program?.id ?? 0,
      outfitId: outfit?.id ?? 0,
      accommodationId: accommodation?.id ?? 0,
      groups: groups.map((g) => g.id ?? 0).filter((g) => g > 0),
      equipment: equipment.map((e) => e.id ?? 0).filter((e) => e > 0),
      eventContacts: initialContacts,
      manualContacts: initialContacts,
      excludedContacts: [],
      manualContactsInitialized: false,
      songs:
        songsToPractice
          ?.filter((song) => typeof song.id === "number" && song.id > 0)
          .map((song) => ({
            id: song.id ?? 0,
            title: safeString(song.title) || "",
            notes: song.notes ?? "",
          })) ?? [],
      participants: participantRows,
    };
  };

  const startEdit = () => {
    setSaveError("");
    setForm(buildForm());
    setIsEditing(true);
    if (!meta) {
      loadMeta();
    }
    const params = new URLSearchParams(searchParams.toString());
    params.set("edit", "1");
    router.push(`/entity?${params.toString()}`);
  };

  const cancelEdit = () => {
    setIsEditing(false);
    setForm(null);
    setSaveError("");
    const params = new URLSearchParams(searchParams.toString());
    params.delete("edit");
    router.replace(`/entity?${params.toString()}`);
  };

  const saveEdit = async () => {
    if (!form) return;
    setSaving(true);
    setSaveError("");
    try {
      const baseFields =
        type === "concert"
          ? {
              title: form.title.trim(),
              begin: fromInputDateTime(form.begin),
              end: fromInputDateTime(form.end),
              meetingtime: fromInputDateTime(form.meetingtime),
              approve_until: fromInputDateTime(form.approveUntil),
              status: form.status,
              notes: form.notes,
              organizer: form.organizer,
              payment: form.payment,
              conditions: form.conditions,
              location: form.locationId,
              contact: form.contactId,
              program: form.programId,
              outfit: form.outfitId,
              accommodation: form.accommodationId,
            }
          : {
              begin: fromInputDateTime(form.begin),
              end: fromInputDateTime(form.end),
              approve_until: fromInputDateTime(form.approveUntil),
              status: form.status,
              notes: form.notes,
              location: form.locationId,
              conductor: form.conductorId,
            };

      await api.post(module, "update", {
        id: numId,
        fields: baseFields,
        groups: form.groups,
        equipment: type === "concert" ? form.equipment : undefined,
        contacts: form.eventContacts,
        songs: type === "rehearsal" ? form.songs.map((song) => ({ id: song.id, notes: song.notes })) : undefined,
        participants: canEditParticipation
          ? form.participants.map((participant) => ({
              userId: participant.userId,
              participate: mapStatusToParticipation(participant.participate),
            }))
          : undefined,
      });
      setIsEditing(false);
      setForm(null);
      const params = new URLSearchParams(searchParams.toString());
      params.delete("edit");
      router.replace(`/entity?${params.toString()}`);
      await loadData();
    } catch (err) {
      setSaveError(err instanceof Error ? err.message : "Failed to save");
    } finally {
      setSaving(false);
    }
  };

  const updateSongSelection = (selectedIds: number[]) => {
    if (!form) return;
    const available = (type === "rehearsal" ? (meta as RehearsalMeta | null)?.songs : []) ?? [];
    const existing = new Map(form.songs.map((song) => [song.id, song]));
    const next = selectedIds.map((id) => {
      const current = existing.get(id);
      const metaSong = available.find((song) => song.id === id);
      return {
        id,
        title: current?.title ?? metaSong?.title ?? "",
        notes: current?.notes ?? "",
      };
    });
    setForm({ ...form, songs: next });
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      {isEditing && (
        <div
          className="sticky top-0 z-30 rounded-xl border px-4 py-3 shadow-sm"
          style={{
            borderColor: "var(--border)",
            background: "color-mix(in oklch, var(--primary) 12%, var(--card))",
            color: "var(--card-foreground)",
          }}
        >
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm font-medium">
              {t("js.common.editing") !== "js.common.editing" ? t("js.common.editing") : "Editing"}
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={saveEdit}
                disabled={saving}
                className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white"
                style={{ background: "var(--primary)" }}
              >
                <Save className="h-4 w-4" />
                {t("js.common.save") !== "js.common.save" ? t("js.common.save") : "Save"}
              </button>
              <button
                type="button"
                onClick={cancelEdit}
                disabled={saving}
                className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium"
                style={{
                  borderColor: "color-mix(in oklch, var(--destructive) 60%, var(--border))",
                  color: "var(--destructive)",
                }}
              >
                <X className="h-4 w-4" />
                {t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Header + participation widget */}
      <div
        className="rounded-xl border p-6 shadow-sm"
        style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
      >
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <div
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white"
                style={{
                  background: type === "rehearsal" ? "var(--primary)" : "var(--accent)",
                }}
              >
                <EventIcon className="h-5 w-5" />
              </div>
              <div className="flex flex-wrap items-center gap-2 min-w-0">
                {isEditing && form && type === "concert" ? (
                  <input
                    type="text"
                    value={form.title}
                    onChange={(event) => setForm({ ...form, title: event.target.value })}
                    className="text-2xl font-bold rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 min-w-0 flex-1"
                    style={{ color: "var(--foreground)" }}
                  />
                ) : (
                  <h1 className="text-2xl font-bold truncate" style={{ color: "var(--foreground)" }}>
                    {title}
                  </h1>
                )}
                <span className={`event-badge ${eventTypeConfig.badgeClass}`}>{eventTypeConfig.label}</span>
              </div>
            </div>
            <p className="mt-2 text-sm" style={{ color: "var(--muted-foreground)" }}>
              {dateStr} · {timeStr}
              {endTimeStr ? ` - ${endTimeStr}` : ""}
            </p>
            {locationName && (
              <p className="mt-1 text-sm" style={{ color: "var(--muted-foreground)" }}>
                {locationName}
              </p>
            )}
            {isPastEvent && (
              <p className="mt-2 text-xs font-medium" style={{ color: "var(--destructive)" }}>
                {t("js.event.detail.pastEvent")}
              </p>
            )}
            {!isPastEvent && isPastDeadline && (
              <p className="mt-2 text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.participationClosed")}
              </p>
            )}
            {/* Top section only shows name + date/time (address in details card) */}
          </div>
          <div className="shrink-0 flex flex-col items-end gap-2">
            {canEdit && !isEditing && (
              <div className="flex items-baseline">
                <button
                  type="button"
                  onClick={startEdit}
                  className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white"
                  style={{ background: "var(--primary)" }}
                >
                  <Pencil className="h-4 w-4" />
                  {t("js.common.edit") !== "js.common.edit" ? t("js.common.edit") : "Edit"}
                </button>
              </div>
            )}
            {!isEditing && (type === "rehearsal" || type === "concert") && (
              <ParticipationWidget
                eventId={numId}
                eventType={eventType}
                onStatusChange={loadData}
                disabled={participationDisabled}
              />
            )}
          </div>
        </div>
      </div>

      {saveError && (
        <div
          className="rounded-lg border px-4 py-3 text-sm"
          style={{
            borderColor: "var(--destructive)",
            background: "color-mix(in oklch, var(--destructive) 15%, transparent)",
            color: "var(--destructive-foreground)",
          }}
        >
          {saveError}
        </div>
      )}

      {/* Basic info */}
      <div
        className="rounded-xl border p-6 shadow-sm"
        style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
      >
        <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
          {t("js.event.detail.additionalInfo") !== "js.event.detail.additionalInfo"
            ? t("js.event.detail.additionalInfo")
            : "Details"}
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.start") !== "js.event.detail.start" ? t("js.event.detail.start") : "Start"}:
            </span>
            {isEditing && form ? (
              <input
                type="datetime-local"
                value={form.begin}
                onChange={(event) => {
                  const nextBegin = event.target.value;
                  const nextEnd = syncEndDate(nextBegin, form.end);
                  setForm({ ...form, begin: nextBegin, end: nextEnd });
                }}
                className="ml-2 rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                style={{ color: "var(--foreground)" }}
              />
            ) : (
              <span className="ml-2 text-sm">{formatDateTimeShort(begin, lang) ?? tba}</span>
            )}
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.end") !== "js.event.detail.end" ? t("js.event.detail.end") : "End"}:
            </span>
            {isEditing && form ? (
              <input
                type="datetime-local"
                value={form.end}
                onChange={(event) => setForm({ ...form, end: event.target.value })}
                className="ml-2 rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                style={{ color: "var(--foreground)" }}
              />
            ) : (
              <span className="ml-2 text-sm">{formatDateTimeShort(end, lang) ?? tba}</span>
            )}
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.status")}:
            </span>
            {isEditing && form ? (
              <span className="ml-2 inline-flex items-center gap-2 align-middle">
                <StatusPicker
                  options={(type === "concert" ? concertMeta?.statusOptions : rehearsalMeta?.statusOptions) ?? [
                    "planned",
                    "confirmed",
                    "cancelled",
                    "hidden",
                  ]}
                  value={form.status}
                  onChange={(next) => setForm({ ...form, status: next })}
                  labelFor={statusLabelFor}
                />
              </span>
            ) : (
              <span
                className="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium border align-middle"
                style={getStatusPillStyle(status)}
              >
                {statusLabel}
              </span>
            )}
          </div>
          {(approveUntil || isEditing) && (
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.deadline") !== "js.event.detail.deadline" ? t("js.event.detail.deadline") : "Reply by"}:
              </span>
              {isEditing && form ? (
                <input
                  type="datetime-local"
                  value={form.approveUntil}
                  onChange={(event) => setForm({ ...form, approveUntil: event.target.value })}
                  className="ml-2 rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                  style={{ color: "var(--foreground)" }}
                />
              ) : (
                <span className="ml-2 text-sm">{formatDateTimeShort(approveUntil, lang) ?? tba}</span>
              )}
            </div>
          )}
          {type === "rehearsal" && (conductor?.name || isEditing) && (
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.conductor")}:
              </span>
            {isEditing && form ? (
              <SelectPicker
                options={[{ id: 0, name: "-" }, ...(rehearsalMeta?.conductors ?? [])]}
                value={form.conductorId}
                onChange={(next) => setForm({ ...form, conductorId: next })}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel="-"
                labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
              />
            ) : (
                <span className="ml-2 text-sm">{conductorName}</span>
              )}
            </div>
          )}
          {type === "concert" && (meetingtime || isEditing) && (
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.meetingTime") !== "js.event.detail.meetingTime"
                  ? t("js.event.detail.meetingTime")
                  : "Meeting time"}:
              </span>
              {isEditing && form ? (
                <input
                  type="datetime-local"
                  value={form.meetingtime}
                  onChange={(event) => setForm({ ...form, meetingtime: event.target.value })}
                  className="ml-2 rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                  style={{ color: "var(--foreground)" }}
                />
              ) : (
                <span className="ml-2 text-sm">{formatDateTimeShort(meetingtime, lang) ?? tba}</span>
              )}
            </div>
          )}
          <div className="md:col-span-2">
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
              {t("js.event.detail.location")}:
            </span>
            {isEditing && form ? (
              <SelectPicker
                options={[
                  { id: 0, name: "-" },
                  ...(((type === "concert" ? concertMeta?.locations : rehearsalMeta?.locations) ?? []) as SimpleOption[]),
                ]}
                value={form.locationId}
                onChange={(next) => setForm({ ...form, locationId: next })}
                placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                emptyLabel="-"
                labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
              />
            ) : (
              <div className="mt-1 flex items-center gap-2 flex-wrap text-sm">
                {hasLocationAddress ? (
                  <AddressLink name={locationName} value={loc?.address} t={t} />
                ) : hasLocationName ? (
                  <AddressLink value={locationName} t={t} renderRawIfNoAddress />
                ) : (
                  "TBA"
                )}
              </div>
            )}
          </div>
          {type === "concert" && (notes?.trim() || isEditing) && (
            <div className="md:col-span-2">
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                {t("js.event.detail.notes")}:
              </span>
              {isEditing && form ? (
                <textarea
                  value={form.notes}
                  onChange={(event) => setForm({ ...form, notes: event.target.value })}
                  className="mt-1 w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
                  style={{ color: "var(--foreground)" }}
                  rows={4}
                />
              ) : (
                <MarkdownText value={notes} className="mt-1 text-sm" />
              )}
            </div>
          )}
        </div>
      </div>

      {type === "rehearsal" && (isEditing || groups.length > 0) && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
              {t("js.event.metadata.besetzung") !== "js.event.metadata.besetzung"
                ? t("js.event.metadata.besetzung")
                : "Groups"}
            </h2>
            {isEditing && form ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={rehearsalMeta?.groups ?? []}
                  selected={form.groups}
                  onChange={(next) => setForm({ ...form, groups: next })}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={selectedCountLabel}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
              </div>
            ) : null}
          </div>
          {isEditing && form ? (
            <SelectedItemsList
              options={rehearsalMeta?.groups ?? []}
              selected={form.groups}
              onRemove={(id) => setForm({ ...form, groups: form.groups.filter((gid) => gid !== id) })}
              labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
              className="mt-4"
            />
          ) : (
            <div className="mt-4 text-sm">
              {groups.map((group) => safeString(group.name)).filter(Boolean).join(", ") || "—"}
            </div>
          )}
        </div>
      )}

      {/* Participation overview (diagram) */}
      {!isEditing && participationStats && (participationStats.total ?? 0) > 0 && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
            {t("js.event.detail.participationOverview") !== "js.event.detail.participationOverview"
              ? t("js.event.detail.participationOverview")
              : "Participation overview"}
          </h2>
          <ParticipationDiagram stats={participationStats} />
        </div>
      )}

      {/* Participants by instrument */}
      {participantsByInstrument && participantsByInstrument.length > 0 && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
              {t("js.event.detail.participants") !== "js.event.detail.participants"
                ? t("js.event.detail.participants")
                : "Participants"}
            </h2>
            {isEditing && form && canEditParticipation ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={(type === "concert" ? concertMeta?.contacts : rehearsalMeta?.contacts) ?? []}
                  selected={form.eventContacts}
                  onChange={(next) => {
                    if (!form) return;
                    const groupContacts = getGroupContacts(form.groups);
                    const nextManual = next.filter((id) => !groupContacts.has(id));
                    const nextExcluded = form.excludedContacts.filter((id) => next.includes(id));
                    setForm({
                      ...form,
                      manualContacts: nextManual,
                      excludedContacts: nextExcluded,
                      manualContactsInitialized: true,
                    });
                  }}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={selectedCountLabel}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
              </div>
            ) : null}
          </div>
          {isEditing && form && canEditParticipation ? (
            <div className="mt-4">
              <ParticipantEditor
              participants={form.participants}
              onRemoveContact={(contactId) => {
                if (!form) return;
                const groupContacts = getGroupContacts(form.groups);
                if (groupContacts.has(contactId)) {
                  if (!form.excludedContacts.includes(contactId)) {
                    setForm({
                      ...form,
                      excludedContacts: [...form.excludedContacts, contactId],
                      manualContacts: form.manualContacts.filter((id) => id !== contactId),
                      manualContactsInitialized: true,
                    });
                  }
                } else {
                  setForm({
                    ...form,
                    manualContacts: form.manualContacts.filter((id) => id !== contactId),
                    manualContactsInitialized: true,
                  });
                }
              }}
              onChange={(next) => setForm({ ...form, participants: next })}
              t={t}
              />
            </div>
          ) : (
            <ParticipantOverview participantsByInstrument={participantsByInstrument} />
          )}
        </div>
      )}

      {/* Concert metadata */}
      {type === "concert" &&
        (isEditing ||
          groups.length > 0 ||
          program?.name ||
          outfit?.name ||
          equipment.length > 0 ||
          accommodation?.name ||
          (payment != null && payment !== undefined) ||
          conditions ||
          contact) && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-4" style={{ color: "var(--foreground)" }}>
            {t("js.event.metadata.organisation") !== "js.event.metadata.organisation"
              ? t("js.event.metadata.organisation")
              : "Organisation"}
          </h2>
          <div className="space-y-4">
            {(isEditing || groups.length > 0) && (
              <div>
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                  <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                    {t("js.event.metadata.besetzung") !== "js.event.metadata.besetzung"
                      ? t("js.event.metadata.besetzung")
                      : "Groups"}
                    :
                  </span>
                  {isEditing && form ? (
                    <div className="w-[min(100%,260px)]">
                      <MultiSelect
                        options={concertMeta?.groups ?? []}
                        selected={form.groups}
                        onChange={(next) => setForm({ ...form, groups: next })}
                        placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                        showChips={false}
                        labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                        labelSelectedCount={selectedCountLabel}
                        labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                        labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                        labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                        labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                      />
                    </div>
                  ) : null}
                </div>
                {!isEditing && (
                  <div className="mt-4 text-sm">
                    {groups.map((g) => safeString(g.name)).filter(Boolean).join(", ") || "—"}
                  </div>
                )}
                {isEditing && form ? (
                  <SelectedItemsList
                    options={concertMeta?.groups ?? []}
                    selected={form.groups}
                    onRemove={(id) => setForm({ ...form, groups: form.groups.filter((gid) => gid !== id) })}
                    labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                    emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                    className="mt-4"
                  />
                ) : null}
              </div>
            )}
            {(isEditing || safeString(program?.name)) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.programm") !== "js.event.metadata.programm"
                    ? t("js.event.metadata.programm")
                    : "Program"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={[{ id: 0, name: "-" }, ...(concertMeta?.programs ?? [])]}
                    value={form.programId}
                    onChange={(next) => setForm({ ...form, programId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">{safeString(program?.name)}</span>
                )}
              </div>
            )}
            {(isEditing || safeString(outfit?.name)) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.outfit") !== "js.event.metadata.outfit"
                    ? t("js.event.metadata.outfit")
                    : "Outfit"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={[{ id: 0, name: "-" }, ...(concertMeta?.outfits ?? [])]}
                    value={form.outfitId}
                    onChange={(next) => setForm({ ...form, outfitId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">{safeString(outfit?.name)}</span>
                )}
              </div>
            )}
            {(isEditing || equipment.length > 0) && (
              <div>
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                  <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                    {t("js.event.metadata.equipment") !== "js.event.metadata.equipment"
                      ? t("js.event.metadata.equipment")
                      : "Equipment"}
                    :
                  </span>
                  {isEditing && form ? (
                    <div className="w-[min(100%,260px)]">
                      <MultiSelect
                        options={concertMeta?.equipment ?? []}
                        selected={form.equipment}
                        onChange={(next) => setForm({ ...form, equipment: next })}
                        placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                        showChips={false}
                        labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                        labelSelectedCount={selectedCountLabel}
                        labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                        labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                        labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                        labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                      />
                    </div>
                  ) : null}
                </div>
                {!isEditing && (
                  <div className="mt-4 text-sm">
                    {equipment.map((e) => safeString(e.name)).filter(Boolean).join(", ") || "—"}
                  </div>
                )}
                {isEditing && form ? (
                  <SelectedItemsList
                    options={concertMeta?.equipment ?? []}
                    selected={form.equipment}
                    onRemove={(id) => setForm({ ...form, equipment: form.equipment.filter((eid) => eid !== id) })}
                    labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                    emptyLabel={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                    className="mt-4"
                  />
                ) : null}
              </div>
            )}
          </div>
          <h3 className="text-sm font-semibold mt-6 mb-3" style={{ color: "var(--foreground)" }}>
            {t("js.event.metadata.details") !== "js.event.metadata.details"
              ? t("js.event.metadata.details")
              : "Details"}
          </h3>
          <div className="space-y-2">
            {(isEditing || safeString(accommodation?.name)) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.unterkunft") !== "js.event.metadata.unterkunft"
                    ? t("js.event.metadata.unterkunft")
                    : "Accommodation"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={[{ id: 0, name: "-" }, ...(concertMeta?.locations ?? [])]}
                    value={form.accommodationId}
                    onChange={(next) => setForm({ ...form, accommodationId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">{safeString(accommodation?.name)}</span>
                )}
              </div>
            )}
            {(isEditing || (payment != null && payment !== undefined)) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.gage") !== "js.event.metadata.gage" ? t("js.event.metadata.gage") : "Payment"}:
                </span>
                {isEditing && form ? (
                  <input
                    type="number"
                    value={form.payment}
                    onChange={(event) => setForm({ ...form, payment: event.target.value })}
                    className="ml-2 rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                    style={{ color: "var(--foreground)" }}
                  />
                ) : (
                  <span className="ml-2 text-sm">
                    {new Intl.NumberFormat(lang === "de" ? "de-DE" : "en-US", {
                      style: "currency",
                      currency: "EUR",
                    }).format(payment)}
                  </span>
                )}
              </div>
            )}
            {(isEditing || conditions?.trim()) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.konditionen") !== "js.event.metadata.konditionen"
                    ? t("js.event.metadata.konditionen")
                    : "Conditions"}:
                </span>
                {isEditing && form ? (
                  <textarea
                    value={form.conditions}
                    onChange={(event) => setForm({ ...form, conditions: event.target.value })}
                    className="mt-1 w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
                    style={{ color: "var(--foreground)" }}
                    rows={3}
                  />
                ) : (
                  <MarkdownText value={conditions} className="mt-1 text-sm" />
                )}
              </div>
            )}
            {!isEditing && meetingtime && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.treffpunkt") !== "js.event.metadata.treffpunkt"
                    ? t("js.event.metadata.treffpunkt")
                    : "Meeting point"}:
                </span>
                <span className="ml-2 text-sm">{formatDateTimeShort(meetingtime, lang) ?? tba}</span>
              </div>
            )}
            {(isEditing ||
              (contact && (safeString(contact.name) || safeString(contact.phone) || safeString(contact.mobile) || safeString(contact.email)))) && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.kontakt") !== "js.event.metadata.kontakt"
                    ? t("js.event.metadata.kontakt")
                    : "Contact"}:
                </span>
                {isEditing && form ? (
                  <SelectPicker
                    options={concertMeta?.contacts ?? []}
                    value={form.contactId}
                    onChange={(next) => setForm({ ...form, contactId: next })}
                    placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                    emptyLabel="-"
                    labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                    labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                    labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  />
                ) : (
                  <span className="ml-2 text-sm">
                    {[safeString(contact?.name), safeString(contact?.phone), safeString(contact?.mobile), safeString(contact?.email)]
                      .filter(Boolean)
                      .join(" | ")}
                  </span>
                )}
              </div>
            )}
            {isEditing && form && (
              <div>
                <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.event.metadata.organizer") !== "js.event.metadata.organizer" ? t("js.event.metadata.organizer") : "Organizer"}:
                </span>
                <input
                  type="text"
                  value={form.organizer}
                  onChange={(event) => setForm({ ...form, organizer: event.target.value })}
                  className="ml-2 rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                  style={{ color: "var(--foreground)" }}
                />
              </div>
            )}
          </div>
        </div>
      )}

      {/* Notes (rehearsal) */}
      {type === "rehearsal" && (notes?.trim() || isEditing) && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <h2 className="text-lg font-semibold mb-2" style={{ color: "var(--foreground)" }}>
            {t("js.event.detail.notes")}
          </h2>
          <div style={{ color: "var(--muted-foreground)" }}>
            {isEditing && form ? (
              <textarea
                value={form.notes}
                onChange={(event) => setForm({ ...form, notes: event.target.value })}
                className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm"
                style={{ color: "var(--foreground)" }}
                rows={4}
              />
            ) : (
              <MarkdownText value={notes} className="text-sm" />
            )}
          </div>
        </div>
      )}

      {type === "rehearsal" && (isEditing || (Array.isArray(songsToPractice) && songsToPractice.length > 0)) && (
        <div
          className="rounded-xl border p-6 shadow-sm"
          style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}
        >
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <h2 className="text-lg font-semibold" style={{ color: "var(--foreground)" }}>
              {t("js.event.detail.songsToPractice")}
            </h2>
            {isEditing && form ? (
              <div className="w-[min(100%,260px)]">
                <MultiSelect
                  options={(rehearsalMeta?.songs ?? []).map((song) => ({ id: song.id, name: song.title }))}
                  selected={form.songs.map((song) => song.id)}
                  onChange={(next) => updateSongSelection(next)}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips={false}
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelSelectedCount={selectedCountLabel}
                labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
              />
              </div>
            ) : null}
          </div>
          {isEditing && form ? (
            <div className="mt-6 space-y-2">
              {form.songs.map((song) => (
                <div key={song.id} className="rounded-md border px-3 py-2" style={{ borderColor: "var(--border)" }}>
                  <div className="flex items-start justify-between gap-2">
                    <div className="text-sm font-medium">{song.title}</div>
                    <button
                      type="button"
                      onClick={() => setForm({ ...form, songs: form.songs.filter((entry) => entry.id !== song.id) })}
                      className="inline-flex items-center justify-center rounded-md border px-2 py-2 text-sm"
                      style={{ borderColor: "var(--border)", color: "var(--foreground)" }}
                      aria-label={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                  <textarea
                    value={song.notes}
                    onChange={(event) =>
                      setForm({
                        ...form,
                        songs: form.songs.map((entry) =>
                          entry.id === song.id ? { ...entry, notes: event.target.value } : entry
                        ),
                      })
                    }
                    className="mt-2 w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-2 py-1 text-sm"
                    style={{ color: "var(--foreground)" }}
                    rows={2}
                  />
                </div>
              ))}
              {form.songs.length === 0 && (
                <div className="text-sm" style={{ color: "var(--muted-foreground)" }}>
                  {t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                </div>
              )}
            </div>
          ) : (
            <ul className="list-disc list-inside space-y-1 text-sm">
              {songsToPractice?.map((song, i) => (
                <li key={song.id ?? i}>
                  {song.title}
                  {song.notes?.trim() ? (
                    <div className="mt-1 text-xs" style={{ color: "var(--muted-foreground)" }}>
                      <MarkdownText value={song.notes} />
                    </div>
                  ) : null}
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

export default function EntityDetailPage() {
  return (
    <Suspense
      fallback={
        <div className="flex items-center justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
        </div>
      }
    >
      <EntityDetailContent />
    </Suspense>
  );
}
