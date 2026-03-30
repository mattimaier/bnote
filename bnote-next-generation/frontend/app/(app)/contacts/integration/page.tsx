/**
 * Phase-in (integration): bulk-assign contacts to rehearsals, concerts, votes.
 * Rehearsal phases are not exposed in the Next Gen integration UI yet (API still accepts rehearsalphases).
 */

"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useMemo, useState, type Dispatch, type SetStateAction } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { AppPageHeader } from "@/components/AppPageHeader";
import { Spinner } from "@/components/Spinner";
import { ActionButton } from "@/components/ActionButton";
import { Avatar } from "@/components/Avatar";
import { SelectPicker } from "@/components/SelectPicker";
import { CheckboxRow, CheckboxSelectAllRow } from "@/components/CheckboxRow";
import { Clock, LayoutList, Save, getIcon } from "@/components/icons";
import {
  contactsApi,
  type ContactGroup,
  type IntegrationBundle,
  type IntegrationEventRow,
  type IntegrationMemberRow,
} from "@/lib/contacts-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getErrorMessage } from "@/lib/error-utils";
import { formatEventDate, formatEventTime } from "@/lib/event-utils";
import { getEventTypeConfig, getStatusPillStyle } from "@/lib/entity-config";

/** Same id as `KontakteData::$GROUP_MEMBER` (Mitglieder / members). */
const BNOTE_MEMBER_GROUP_ID = 2;

function defaultIntegrationGroupId(groups: ContactGroup[]): string | null {
  const member = groups.find((g) => g.id === BNOTE_MEMBER_GROUP_ID) ?? groups[0];
  return member != null ? String(member.id) : null;
}

function statusLabelFor(value: string | undefined, t: (k: string) => string, emptyText: string): string {
  if (!value) return emptyText;
  const v = value.toLowerCase();
  if (v === "confirmed") return t("js.event.status.confirmed");
  if (v === "cancelled" || v === "canceled") return t("js.event.status.cancelled");
  if (v === "hidden") return t("js.event.status.hidden");
  if (v === "planned") return t("js.event.status.planned");
  return value;
}

function SearchField({
  value,
  onChange,
  placeholder,
}: {
  value: string;
  onChange: (v: string) => void;
  placeholder: string;
}) {
  return (
    <div className="flex w-full items-center gap-3 rounded-lg bg-base-200 px-3 py-2 mb-2">
      <input
        type="search"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full bg-transparent px-0 py-1 text-sm outline-none text-base-content"
        aria-label={placeholder}
      />
    </div>
  );
}

export default function ContactsIntegrationPage() {
  const { t, ready, lang } = useI18n();
  const { showToast } = useToast();
  const router = useRouter();
  const searchParams = useSearchParams();
  const groupFromUrl = searchParams.get("group");

  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const tba = t("js.event.tba") !== "js.event.tba" ? t("js.event.tba") : "TBA";

  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [groupsLoading, setGroupsLoading] = useState(true);
  const [selectedGroup, setSelectedGroup] = useState<string | null>(() => groupFromUrl);
  const [bundle, setBundle] = useState<IntegrationBundle | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const [qMembers, setQMembers] = useState("");
  const [qReh, setQReh] = useState("");
  const [qCon, setQCon] = useState("");
  const [qVotes, setQVotes] = useState("");

  const [selMembers, setSelMembers] = useState<Set<number>>(() => new Set());
  const [selReh, setSelReh] = useState<Set<number>>(() => new Set());
  const [selCon, setSelCon] = useState<Set<number>>(() => new Set());
  const [selVotes, setSelVotes] = useState<Set<number>>(() => new Set());

  useEffect(() => {
    if (!ready) return;
    let cancelled = false;
    setGroupsLoading(true);
    (async () => {
      try {
        const g = await contactsApi.getGroups();
        if (!cancelled) setGroups(g ?? []);
      } catch {
        if (!cancelled) setGroups([]);
      } finally {
        if (!cancelled) setGroupsLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [ready]);

  useEffect(() => {
    if (groups.length === 0) return;
    const urlRaw = searchParams.get("group");
    const defaultId = defaultIntegrationGroupId(groups);
    const urlValid = Boolean(urlRaw && groups.some((g) => String(g.id) === urlRaw));
    const effective = urlValid ? urlRaw! : defaultId;
    if (effective == null) return;
    setSelectedGroup((prev) => (prev === effective ? prev : effective));
    if (!urlValid && defaultId != null) {
      router.replace(`/contacts/integration/?group=${encodeURIComponent(defaultId)}`);
    }
  }, [groups, searchParams, router]);

  const loadBundle = useCallback(async () => {
    if (!ready) return;
    setLoading(true);
    setError("");
    try {
      const b = await contactsApi.getIntegrationBundle(selectedGroup ?? undefined);
      setBundle(b);
      setSelMembers(new Set());
      setSelReh(new Set());
      setSelCon(new Set());
      setSelVotes(new Set());
    } catch (err) {
      setBundle(null);
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [ready, selectedGroup, t]);

  useEffect(() => {
    if (!ready) return;
    loadBundle();
  }, [ready, loadBundle]);

  const selectAllLabel = t("js.contacts.all");

  const toggle = (setter: Dispatch<SetStateAction<Set<number>>>) => (id: number) => {
    setter((prev) => {
      const n = new Set(prev);
      if (n.has(id)) n.delete(id);
      else n.add(id);
      return n;
    });
  };

  const toggleAll =
    (setter: Dispatch<SetStateAction<Set<number>>>) => (ids: number[], checked: boolean) => {
      setter((prev) => {
        const n = new Set(prev);
        if (checked) ids.forEach((i) => n.add(i));
        else ids.forEach((i) => n.delete(i));
        return n;
      });
    };

  const save = async () => {
    if (selMembers.size < 1) {
      showToast(t("js.contacts.integrationSelectMembers"), "default");
      return;
    }
    setSaving(true);
    try {
      const result = await contactsApi.integrate({
        group: selectedGroup,
        members: [...selMembers],
        rehearsals: [...selReh],
        rehearsalphases: [],
        concerts: [...selCon],
        votes: [...selVotes],
      });
      const errs = result?.errors ?? [];
      if (errs.length > 0) {
        showToast(t("js.contacts.integrationPartialErrors"), "default");
      } else {
        showToast(t("js.contacts.integrationSuccess"), "success");
      }
      await loadBundle();
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToLoad"), "error");
    } finally {
      setSaving(false);
    }
  };

  const members: IntegrationMemberRow[] = bundle?.members ?? [];
  const rehearsals: IntegrationEventRow[] = bundle?.rehearsals ?? [];
  const concerts: IntegrationEventRow[] = bundle?.concerts ?? [];
  const votes: IntegrationEventRow[] = bundle?.votes ?? [];

  const qm = qMembers.trim().toLowerCase();
  const filteredMembers = useMemo(() => {
    if (!qm) return members;
    return members.filter((m) => {
      const parts = [
        m.name,
        m.surname,
        m.nickname,
        m.email,
        m.instrumentname,
        m.label,
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
      return parts.includes(qm);
    });
  }, [members, qm]);

  const qr = qReh.trim().toLowerCase();
  const filteredRehearsals = useMemo(() => {
    if (!qr) return rehearsals;
    return rehearsals.filter((r) => {
      const begin = (r.begin ?? "").toLowerCase();
      const loc = (r.location_name ?? "").toLowerCase();
      const notes = (r.notes ?? "").toLowerCase();
      const status = (r.status ?? "").toLowerCase();
      const ds = r.begin ? (formatEventDate(r.begin, lang, tba) ?? "").toLowerCase() : "";
      const ts = r.begin ? (formatEventTime(r.begin, lang, tba) ?? "").toLowerCase() : "";
      return begin.includes(qr) || loc.includes(qr) || notes.includes(qr) || status.includes(qr) || ds.includes(qr) || ts.includes(qr);
    });
  }, [rehearsals, qr, lang, tba]);

  const qc = qCon.trim().toLowerCase();
  const filteredConcerts = useMemo(() => {
    if (!qc) return concerts;
    return concerts.filter((c) => {
      const title = (c.title ?? "").toLowerCase();
      const begin = (c.begin ?? "").toLowerCase();
      const loc = (c.location_name ?? "").toLowerCase();
      const notes = (c.notes ?? "").toLowerCase();
      const ds = c.begin ? (formatEventDate(c.begin, lang, tba) ?? "").toLowerCase() : "";
      const ts = c.begin ? (formatEventTime(c.begin, lang, tba) ?? "").toLowerCase() : "";
      return title.includes(qc) || begin.includes(qc) || loc.includes(qc) || notes.includes(qc) || ds.includes(qc) || ts.includes(qc);
    });
  }, [concerts, qc, lang, tba]);

  const qv = qVotes.trim().toLowerCase();
  const filteredVotes = useMemo(() => {
    if (!qv) return votes;
    return votes.filter((v) => (v.name ?? v.label ?? "").toLowerCase().includes(qv));
  }, [votes, qv]);

  const groupPickerOptions = useMemo(
    () => groups.map((g) => ({ id: g.id, name: g.name })),
    [groups]
  );

  const groupPickerValue = useMemo(() => {
    if (groups.length === 0) return 0;
    const fromState =
      selectedGroup != null ? Number.parseInt(selectedGroup, 10) : Number.NaN;
    if (!Number.isNaN(fromState) && groups.some((g) => g.id === fromState)) {
      return fromState;
    }
    const defStr = defaultIntegrationGroupId(groups);
    if (defStr != null) {
      const def = Number.parseInt(defStr, 10);
      if (!Number.isNaN(def)) return def;
    }
    return groups[0]!.id;
  }, [groups, selectedGroup]);

  const searchPh = t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…";
  const groupPickerTitle = t("js.contacts.integrationGroupPicker").replace(/:\s*$/, "");

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={`${PAGE_CONTENT_CLASS} pb-24 md:pb-8`}>
      <AppPageHeader
        title={t("js.contacts.integrationTitle")}
        subtitle={t("js.contacts.integrationIntro")}
        actions={(
          <ActionButton variant="primary" onClick={save} disabled={saving || loading}>
            <Save className="h-4 w-4" />
            {saving ? t("js.contacts.integrationSaving") : t("js.contacts.integrationSave")}
          </ActionButton>
        )}
      />

      {error ? (
        <div className="alert alert-error mb-4 text-sm" role="alert">
          {error}
        </div>
      ) : null}

      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-3">
        <span className="text-sm text-base-content/70 sm:shrink-0 sm:pt-2.5">{t("js.contacts.integrationGroupPicker")}</span>
        <div className="w-full min-w-0 sm:max-w-md">
          {groupsLoading ? (
            <button type="button" className="btn btn-outline w-full justify-between opacity-70" disabled>
              <span className="truncate">{t("js.common.loading")}</span>
              <LayoutList className="h-4 w-4 shrink-0 opacity-50" aria-hidden />
            </button>
          ) : groups.length === 0 ? (
            <p className="text-sm text-base-content/60 py-2">{t("js.contacts.integrationNoGroups")}</p>
          ) : (
            <SelectPicker
              options={groupPickerOptions}
              value={groupPickerValue}
              onChange={(id) => {
                const next = String(id);
                setSelectedGroup(next);
                router.replace(`/contacts/integration/?group=${encodeURIComponent(next)}`);
              }}
              placeholder={searchPh}
              labelSelect={groupPickerTitle}
              labelNoMatches={t("js.contacts.integrationNoMatches")}
              labelClose={t("js.common.close")}
              emptyLabel={emptyText}
            />
          )}
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
          <section>
            <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationMembers")}</h3>
            {groupsLoading || groups.length === 0 ? (
              <p className="text-sm text-base-content/60">
                {groupsLoading ? t("js.common.loading") : t("js.contacts.integrationNoGroups")}
              </p>
            ) : selectedGroup == null ? (
              <p className="text-sm text-base-content/60">{t("js.common.loading")}</p>
            ) : (
              <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
                <SearchField value={qMembers} onChange={setQMembers} placeholder={searchPh} />
                <div className="-mx-3">
                  {filteredMembers.length > 0 && (
                    <CheckboxSelectAllRow
                      checked={filteredMembers.length > 0 && filteredMembers.every((m) => selMembers.has(m.id))}
                      onChange={(on) => toggleAll(setSelMembers)(filteredMembers.map((m) => m.id), on)}
                      label={selectAllLabel}
                    />
                  )}
                  {filteredMembers.length === 0 ? (
                    <p className="text-sm text-base-content/50 py-2 px-3">{t("js.contacts.integrationNoMatches")}</p>
                  ) : (
                    <ul>
                      {filteredMembers.map((m) => {
                        const fullName = [m.name ?? "", m.surname ?? ""].filter(Boolean).join(" ") || emptyText;
                        return (
                          <li key={m.id}>
                            <CheckboxRow
                              checked={selMembers.has(m.id)}
                              onToggle={() => toggle(setSelMembers)(m.id)}
                            >
                              <span className="flex items-center gap-3 min-w-0 flex-1">
                                <Avatar email={m.email} name={fullName} size={32} variant="soft" className="shrink-0" />
                                <span className="flex flex-col min-w-0 flex-1">
                                  <span className="truncate font-medium">{fullName}</span>
                                  {m.instrumentname ? (
                                    <span className="text-xs text-base-content/60 truncate">{m.instrumentname}</span>
                                  ) : null}
                                </span>
                              </span>
                            </CheckboxRow>
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              </div>
            )}
          </section>

          <section className="space-y-4">
            <div>
              <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationRehearsals")}</h3>
              <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
                <SearchField value={qReh} onChange={setQReh} placeholder={searchPh} />
                <div className="-mx-3">
                  {filteredRehearsals.length > 0 && (
                    <CheckboxSelectAllRow
                      checked={filteredRehearsals.every((r) => selReh.has(r.id))}
                      onChange={(on) => toggleAll(setSelReh)(filteredRehearsals.map((r) => r.id), on)}
                      label={selectAllLabel}
                    />
                  )}
                  {filteredRehearsals.length === 0 ? (
                    <p className="text-sm text-base-content/50 py-2 px-3">{t("js.contacts.integrationNoMatches")}</p>
                  ) : (
                    <ul>
                      {filteredRehearsals.map((row) => {
                        const typeConfig = getEventTypeConfig("rehearsal", t);
                        const Icon = getIcon(typeConfig.icon);
                        const dateStr = formatEventDate(row.begin, lang, tba);
                        const timeStr = formatEventTime(row.begin, lang, tba);
                        const loc = row.location_name || emptyText;
                        const metaParts = [timeStr];
                        if (loc !== emptyText) metaParts.push(loc);
                        const metaLine = metaParts.join(" · ");
                        return (
                          <li key={row.id}>
                            <CheckboxRow checked={selReh.has(row.id)} onToggle={() => toggle(setSelReh)(row.id)}>
                              <span className="flex items-center gap-3 min-w-0 flex-1">
                                <div
                                  className={`shrink-0 flex items-center justify-center size-8 rounded-full text-white ${typeConfig.dotClass}`}
                                >
                                  <Icon className="h-3.5 w-3.5" />
                                </div>
                                <span className="flex flex-col min-w-0 flex-1 gap-0.5">
                                  <span className="flex items-center gap-2 min-w-0">
                                    <span className="truncate font-medium text-primary">{dateStr}</span>
                                    {row.status ? (
                                      <span
                                        className="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium border"
                                        style={getStatusPillStyle(row.status)}
                                      >
                                        {statusLabelFor(row.status, t, emptyText)}
                                      </span>
                                    ) : null}
                                  </span>
                                  <span className="text-xs text-base-content/60 flex items-center gap-1 min-w-0">
                                    <Clock className="h-3 w-3 shrink-0 opacity-70" />
                                    <span className="truncate">{metaLine}</span>
                                  </span>
                                </span>
                              </span>
                            </CheckboxRow>
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              </div>
            </div>
            {/*
            Rehearsal phases: not supported in Next Gen integration UI yet.
            */}
          </section>

          <section className="space-y-4">
            <div>
              <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationConcerts")}</h3>
              <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
                <SearchField value={qCon} onChange={setQCon} placeholder={searchPh} />
                <div className="-mx-3">
                  {filteredConcerts.length > 0 && (
                    <CheckboxSelectAllRow
                      checked={filteredConcerts.every((c) => selCon.has(c.id))}
                      onChange={(on) => toggleAll(setSelCon)(filteredConcerts.map((c) => c.id), on)}
                      label={selectAllLabel}
                    />
                  )}
                  {filteredConcerts.length === 0 ? (
                    <p className="text-sm text-base-content/50 py-2 px-3">{t("js.contacts.integrationNoMatches")}</p>
                  ) : (
                    <ul>
                      {filteredConcerts.map((row) => {
                        const typeConfig = getEventTypeConfig("performance", t);
                        const Icon = getIcon(typeConfig.icon);
                        const dateStr = formatEventDate(row.begin, lang, tba);
                        const timeStr = formatEventTime(row.begin, lang, tba);
                        const loc = row.location_name || emptyText;
                        const title = (row.title ?? "").trim();
                        const metaParts: string[] = [];
                        if (title) metaParts.push(title);
                        metaParts.push(timeStr);
                        if (loc !== emptyText) metaParts.push(loc);
                        const metaLine = metaParts.join(" · ");
                        return (
                          <li key={row.id}>
                            <CheckboxRow checked={selCon.has(row.id)} onToggle={() => toggle(setSelCon)(row.id)}>
                              <span className="flex items-center gap-3 min-w-0 flex-1">
                                <div
                                  className={`shrink-0 flex items-center justify-center size-8 rounded-full text-white ${typeConfig.dotClass}`}
                                >
                                  <Icon className="h-3.5 w-3.5" />
                                </div>
                                <span className="flex flex-col min-w-0 flex-1 gap-0.5">
                                  <span className="flex items-center gap-2 min-w-0">
                                    <span className="truncate font-medium text-primary">{dateStr}</span>
                                    {row.status ? (
                                      <span
                                        className="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium border"
                                        style={getStatusPillStyle(row.status)}
                                      >
                                        {statusLabelFor(row.status, t, emptyText)}
                                      </span>
                                    ) : null}
                                  </span>
                                  <span className="text-xs text-base-content/60 flex items-center gap-1 min-w-0">
                                    <Clock className="h-3 w-3 shrink-0 opacity-70" />
                                    <span className="truncate">{metaLine}</span>
                                  </span>
                                </span>
                              </span>
                            </CheckboxRow>
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              </div>
            </div>
            <div>
              <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationVotes")}</h3>
              <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
                <SearchField value={qVotes} onChange={setQVotes} placeholder={searchPh} />
                <div className="-mx-3">
                  {filteredVotes.length > 0 && (
                    <CheckboxSelectAllRow
                      checked={filteredVotes.every((v) => selVotes.has(v.id))}
                      onChange={(on) => toggleAll(setSelVotes)(filteredVotes.map((v) => v.id), on)}
                      label={selectAllLabel}
                    />
                  )}
                  {filteredVotes.length === 0 ? (
                    <p className="text-sm text-base-content/50 py-2 px-3">
                      {votes.length === 0 ? t("js.contacts.integrationNoVotes") : t("js.contacts.integrationNoMatches")}
                    </p>
                  ) : (
                    <ul>
                      {filteredVotes.map((row) => {
                        const typeConfig = getEventTypeConfig("vote", t);
                        const Icon = getIcon(typeConfig.icon);
                        const label = row.name ?? row.label ?? `#${row.id}`;
                        return (
                          <li key={row.id}>
                            <CheckboxRow checked={selVotes.has(row.id)} onToggle={() => toggle(setSelVotes)(row.id)}>
                              <span className="flex items-center gap-3 min-w-0 flex-1">
                                <div
                                  className={`shrink-0 flex items-center justify-center size-8 rounded-full text-white ${typeConfig.dotClass}`}
                                >
                                  <Icon className="h-3.5 w-3.5" />
                                </div>
                                <span className="flex flex-col min-w-0 flex-1">
                                  <span className="truncate font-medium">{label}</span>
                                </span>
                              </span>
                            </CheckboxRow>
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              </div>
            </div>
          </section>
        </div>
      )}

      <div className="fixed bottom-0 left-0 right-0 p-3 bg-base-200/95 border-t border-base-300 md:hidden flex justify-center z-30">
        <button type="button" className="btn btn-primary btn-wide" disabled={saving || loading} onClick={save}>
          {saving ? t("js.contacts.integrationSaving") : t("js.contacts.integrationSave")}
        </button>
      </div>
    </div>
  );
}
