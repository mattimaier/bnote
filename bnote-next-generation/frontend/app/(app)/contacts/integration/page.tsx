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
import { ConfirmModal } from "@/components/ConfirmModal";
import { Clock, LayoutList, Save, Trash2, getIcon } from "@/components/icons";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
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
import { compareDate, compareString, type SortDirection } from "@/lib/table-sort";

/** Same id as `KontakteData::$GROUP_MEMBER` (Mitglieder / members). */
const BNOTE_MEMBER_GROUP_ID = 2;
type IntegrationMode = "add" | "remove";
type RemoveEventRow = IntegrationEventRow & {
  eventType: "rehearsal" | "concert";
};

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
  const mode: IntegrationMode = searchParams.get("mode") === "remove" ? "remove" : "add";
  const contactParam = Number.parseInt(searchParams.get("contact") ?? "", 10);
  const removeContactId = Number.isNaN(contactParam) ? 0 : contactParam;

  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const tba = t("js.event.tba") !== "js.event.tba" ? t("js.event.tba") : "TBA";

  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [groupsLoading, setGroupsLoading] = useState(true);
  const [selectedGroup, setSelectedGroup] = useState<string | null>(() => groupFromUrl);
  const [bundle, setBundle] = useState<IntegrationBundle | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [confirmRemoveOpen, setConfirmRemoveOpen] = useState(false);
  const [error, setError] = useState("");

  const [qMembers, setQMembers] = useState("");
  const [qEvents, setQEvents] = useState("");
  const [qReh, setQReh] = useState("");
  const [qPhases, setQPhases] = useState("");
  const [qCon, setQCon] = useState("");
  const [qVotes, setQVotes] = useState("");

  const [selMembers, setSelMembers] = useState<Set<number>>(() => new Set());
  const [selReh, setSelReh] = useState<Set<number>>(() => new Set());
  const [selPhases, setSelPhases] = useState<Set<number>>(() => new Set());
  const [selCon, setSelCon] = useState<Set<number>>(() => new Set());
  const [selVotes, setSelVotes] = useState<Set<number>>(() => new Set());
  const [removeEventSortKey, setRemoveEventSortKey] = useState<"title" | "begin" | "status">("begin");
  const [removeEventSortDir, setRemoveEventSortDir] = useState<SortDirection>("desc");

  useEffect(() => {
    if (!ready || mode === "remove") return;
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
  }, [ready, mode]);

  useEffect(() => {
    if (mode === "remove") return;
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
  }, [groups, searchParams, router, mode]);

  const loadBundle = useCallback(async () => {
    if (!ready) return;
    if (mode === "remove" && removeContactId <= 0) {
      setBundle(null);
      setError(t("js.contacts.integrationRemoveContactMissing") !== "js.contacts.integrationRemoveContactMissing"
        ? t("js.contacts.integrationRemoveContactMissing")
        : "Missing contact for remove mode.");
      setLoading(false);
      return;
    }
    setLoading(true);
    setError("");
    try {
      const b =
        mode === "remove"
          ? await contactsApi.getRemovalBundle(removeContactId)
          : await contactsApi.getIntegrationBundle(selectedGroup ?? undefined);
      setBundle(b);
      if (mode === "remove") {
        setSelMembers(new Set((b.members ?? []).map((m) => m.id)));
        setSelReh(new Set((b.rehearsals ?? []).map((r) => r.id)));
        setSelPhases(new Set((b.phases ?? []).map((p) => p.id)));
        setSelCon(new Set((b.concerts ?? []).map((c) => c.id)));
        setSelVotes(new Set((b.votes ?? []).map((v) => v.id)));
      } else {
        setSelMembers(new Set());
        setSelReh(new Set());
        setSelPhases(new Set());
        setSelCon(new Set());
        setSelVotes(new Set());
      }
    } catch (err) {
      setBundle(null);
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setLoading(false);
    }
  }, [ready, selectedGroup, t, mode, removeContactId]);

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

  const save = async (skipRemoveConfirm = false) => {
    if (selMembers.size < 1) {
      showToast(t("js.contacts.integrationSelectMembers"), "default");
      return;
    }
    if (
      mode === "remove" &&
      selReh.size < 1 &&
      selCon.size < 1 &&
      selPhases.size < 1 &&
      selVotes.size < 1
    ) {
      showToast(
        t("js.contacts.integrationRemoveSelectAnything") !== "js.contacts.integrationRemoveSelectAnything"
          ? t("js.contacts.integrationRemoveSelectAnything")
          : "Select at least one assignment to remove.",
        "default"
      );
      return;
    }
    if (mode === "remove" && !skipRemoveConfirm) {
      setConfirmRemoveOpen(true);
      return;
    }
    setSaving(true);
    try {
      const result =
        mode === "remove"
          ? await contactsApi.bulkRemove({
              members: [...selMembers],
              rehearsals: [...selReh],
              rehearsalphases: [...selPhases],
              concerts: [...selCon],
              votes: [...selVotes],
            })
          : await contactsApi.integrate({
              group: selectedGroup,
              members: [...selMembers],
              rehearsals: [...selReh],
              rehearsalphases: [...selPhases],
              concerts: [...selCon],
              votes: [...selVotes],
            });
      const errs = result?.errors ?? [];
      if (errs.length > 0) {
        showToast(
          mode === "remove"
            ? (t("js.contacts.integrationRemovePartialErrors") !== "js.contacts.integrationRemovePartialErrors"
              ? t("js.contacts.integrationRemovePartialErrors")
              : "Some assignments could not be removed.")
            : t("js.contacts.integrationPartialErrors"),
          "default"
        );
      } else {
        showToast(
          mode === "remove"
            ? (t("js.contacts.integrationRemoveSuccess") !== "js.contacts.integrationRemoveSuccess"
              ? t("js.contacts.integrationRemoveSuccess")
              : "Assignments removed.")
            : t("js.contacts.integrationSuccess"),
          "success"
        );
      }
      await loadBundle();
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToLoad"), "error");
    } finally {
      setSaving(false);
    }
  };

  const members: IntegrationMemberRow[] = useMemo(() => bundle?.members ?? [], [bundle]);
  const rehearsals: IntegrationEventRow[] = useMemo(() => bundle?.rehearsals ?? [], [bundle]);
  const phases: IntegrationEventRow[] = useMemo(() => bundle?.phases ?? [], [bundle]);
  const concerts: IntegrationEventRow[] = useMemo(() => bundle?.concerts ?? [], [bundle]);
  const votes: IntegrationEventRow[] = useMemo(() => bundle?.votes ?? [], [bundle]);

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

  const qp = qPhases.trim().toLowerCase();
  const filteredPhases = useMemo(() => {
    if (!qp) return phases;
    return phases.filter((p) => (p.name ?? p.label ?? "").toLowerCase().includes(qp));
  }, [phases, qp]);

  const qe = qEvents.trim().toLowerCase();
  const filteredRemoveRehearsals = useMemo(() => {
    if (!qe) return rehearsals;
    return rehearsals.filter((r) => {
      const begin = (r.begin ?? "").toLowerCase();
      const loc = (r.location_name ?? "").toLowerCase();
      const notes = (r.notes ?? "").toLowerCase();
      const status = (r.status ?? "").toLowerCase();
      const ds = r.begin ? (formatEventDate(r.begin, lang, tba) ?? "").toLowerCase() : "";
      const ts = r.begin ? (formatEventTime(r.begin, lang, tba) ?? "").toLowerCase() : "";
      return begin.includes(qe) || loc.includes(qe) || notes.includes(qe) || status.includes(qe) || ds.includes(qe) || ts.includes(qe);
    });
  }, [rehearsals, qe, lang, tba]);
  const filteredRemoveConcerts = useMemo(() => {
    if (!qe) return concerts;
    return concerts.filter((c) => {
      const title = (c.title ?? "").toLowerCase();
      const begin = (c.begin ?? "").toLowerCase();
      const loc = (c.location_name ?? "").toLowerCase();
      const notes = (c.notes ?? "").toLowerCase();
      const ds = c.begin ? (formatEventDate(c.begin, lang, tba) ?? "").toLowerCase() : "";
      const ts = c.begin ? (formatEventTime(c.begin, lang, tba) ?? "").toLowerCase() : "";
      return title.includes(qe) || begin.includes(qe) || loc.includes(qe) || notes.includes(qe) || ds.includes(qe) || ts.includes(qe);
    });
  }, [concerts, qe, lang, tba]);

  const removeEvents = useMemo<RemoveEventRow[]>(
    () => [
      ...filteredRemoveRehearsals.map((row) => ({ ...row, eventType: "rehearsal" as const })),
      ...filteredRemoveConcerts.map((row) => ({ ...row, eventType: "concert" as const })),
    ],
    [filteredRemoveRehearsals, filteredRemoveConcerts]
  );

  const sortedRemoveEvents = useMemo(() => {
    return [...removeEvents].sort((a, b) => {
      if (removeEventSortKey === "begin") {
        return compareDate(a.begin, b.begin, removeEventSortDir);
      }
      if (removeEventSortKey === "status") {
        return compareString(a.status ?? "", b.status ?? "", removeEventSortDir);
      }
      const aTitle =
        a.eventType === "concert"
          ? (a.title ?? t("js.event.performance"))
          : t("js.event.rehearsal");
      const bTitle =
        b.eventType === "concert"
          ? (b.title ?? t("js.event.performance"))
          : t("js.event.rehearsal");
      return compareString(aTitle, bTitle, removeEventSortDir);
    });
  }, [removeEvents, removeEventSortKey, removeEventSortDir, t]);
  const visibleRehearsalIds = useMemo(
    () => sortedRemoveEvents.filter((row) => row.eventType === "rehearsal").map((row) => row.id),
    [sortedRemoveEvents]
  );
  const visibleConcertIds = useMemo(
    () => sortedRemoveEvents.filter((row) => row.eventType === "concert").map((row) => row.id),
    [sortedRemoveEvents]
  );
  const allVisibleEventsSelected = useMemo(() => {
    if (sortedRemoveEvents.length === 0) return false;
    return (
      visibleRehearsalIds.every((id) => selReh.has(id)) &&
      visibleConcertIds.every((id) => selCon.has(id))
    );
  }, [sortedRemoveEvents.length, visibleRehearsalIds, visibleConcertIds, selReh, selCon]);
  const toggleAllVisibleEvents = (checked: boolean) => {
    setSelReh((prev) => {
      const next = new Set(prev);
      if (checked) visibleRehearsalIds.forEach((id) => next.add(id));
      else visibleRehearsalIds.forEach((id) => next.delete(id));
      return next;
    });
    setSelCon((prev) => {
      const next = new Set(prev);
      if (checked) visibleConcertIds.forEach((id) => next.add(id));
      else visibleConcertIds.forEach((id) => next.delete(id));
      return next;
    });
  };

  const handleRemoveEventSort = (key: "title" | "begin" | "status") => {
    if (removeEventSortKey === key) {
      setRemoveEventSortDir((d) => (d === "asc" ? "desc" : "asc"));
      return;
    }
    setRemoveEventSortKey(key);
    setRemoveEventSortDir(key === "begin" ? "desc" : "asc");
  };

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
  const removeConfirmTitle =
    t("js.contacts.integrationRemoveTitle") !== "js.contacts.integrationRemoveTitle"
      ? t("js.contacts.integrationRemoveTitle")
      : "Remove From Future Events";
  const removeConfirmMessage =
    t("js.contacts.integrationRemoveConfirm") !== "js.contacts.integrationRemoveConfirm"
      ? t("js.contacts.integrationRemoveConfirm")
      : "Remove selected assignments?";
  const removeConfirmLabel =
    t("js.contacts.integrationRemoveSave") !== "js.contacts.integrationRemoveSave"
      ? t("js.contacts.integrationRemoveSave")
      : "Remove Selected";
  const cancelLabel = t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel";

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
        title={
          mode === "remove"
            ? (t("js.contacts.integrationRemoveTitle") !== "js.contacts.integrationRemoveTitle"
              ? t("js.contacts.integrationRemoveTitle")
              : "Remove From Future Events")
            : t("js.contacts.integrationTitle")
        }
        subtitle={
          mode === "remove"
            ? (t("js.contacts.integrationRemoveIntro") !== "js.contacts.integrationRemoveIntro"
              ? t("js.contacts.integrationRemoveIntro")
              : "Review upcoming assignments and remove this member from single events or all selected entries.")
            : t("js.contacts.integrationIntro")
        }
        actions={(
          <ActionButton variant="primary" onClick={() => save()} disabled={saving || loading}>
            {mode === "remove" ? <Trash2 className="h-4 w-4" /> : <Save className="h-4 w-4" />}
            {saving
              ? t("js.contacts.integrationSaving")
              : mode === "remove"
                ? (t("js.contacts.integrationRemoveSave") !== "js.contacts.integrationRemoveSave"
                  ? t("js.contacts.integrationRemoveSave")
                  : "Remove Selected")
                : t("js.contacts.integrationSave")}
          </ActionButton>
        )}
      />

      {error ? (
        <div className="alert alert-error mb-4 text-sm" role="alert">
          {error}
        </div>
      ) : null}

      {mode === "add" ? (
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
      ) : null}

      {loading ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : (
        mode === "remove" ? (
          <div className="space-y-4">
            {members[0] ? (
              <section className="rounded-lg border border-base-300 bg-base-100 p-3">
                <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationMembers")}</h3>
                <div className="flex items-center gap-3">
                  <Avatar
                    email={members[0].email}
                    name={[members[0].name ?? "", members[0].surname ?? ""].filter(Boolean).join(" ") || emptyText}
                    size={32}
                    variant="soft"
                  />
                  <div className="min-w-0">
                    <p className="truncate font-medium">
                      {[members[0].name ?? "", members[0].surname ?? ""].filter(Boolean).join(" ") || emptyText}
                    </p>
                    {members[0].instrumentname ? (
                      <p className="text-xs text-base-content/60 truncate">{members[0].instrumentname}</p>
                    ) : null}
                  </div>
                </div>
              </section>
            ) : null}

            <section className="rounded-lg border border-base-300 bg-base-100 p-3">
              <h3 className="text-sm font-semibold mb-2">
                {t("js.contacts.integrationFutureEvents") !== "js.contacts.integrationFutureEvents"
                  ? t("js.contacts.integrationFutureEvents")
                  : "Future Events"}
              </h3>
              <SearchField value={qEvents} onChange={setQEvents} placeholder={searchPh} />
              <ResponsiveTable<RemoveEventRow, "title" | "begin" | "status">
                rows={sortedRemoveEvents}
                getRowKey={(row) => `${row.eventType}-${row.id}`}
                renderMobileRow={(row) => {
                  const eventType = row.eventType === "concert" ? "performance" : "rehearsal";
                  const typeConfig = getEventTypeConfig(eventType, t);
                  const Icon = getIcon(typeConfig.icon);
                  const dateStr = formatEventDate(row.begin, lang, tba);
                  const timeStr = formatEventTime(row.begin, lang, tba);
                  const title = row.eventType === "concert" ? (row.title ?? t("js.event.performance")) : t("js.event.rehearsal");
                  const location = row.location_name || emptyText;
                  return (
                    <div className="flex items-center gap-2 pr-2">
                      <label className="checkbox checkbox-sm checkbox-primary shrink-0 ml-1">
                        <input
                          type="checkbox"
                          checked={selReh.has(row.id) || selCon.has(row.id)}
                          onChange={() => (row.eventType === "rehearsal" ? toggle(setSelReh)(row.id) : toggle(setSelCon)(row.id))}
                        />
                      </label>
                      <div className="min-w-0 flex-1">
                        <EntityListRow
                          icon={
                            <span className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}>
                              <Icon className="h-3 w-3" />
                            </span>
                          }
                          primary={title}
                          badge={row.status ? (
                            <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(row.status)}>
                              {statusLabelFor(row.status, t, emptyText)}
                            </span>
                          ) : undefined}
                          secondary={<span>{[dateStr, timeStr, location].filter(Boolean).join(" · ")}</span>}
                        />
                      </div>
                    </div>
                  );
                }}
                onRowClick={() => {}}
                emptyMessage={t("js.contacts.integrationNoMatches")}
                sortOptions={[
                  { key: "title", label: t("js.event.title") !== "js.event.title" ? t("js.event.title") : "Title" },
                  { key: "begin", label: t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin" },
                  { key: "status", label: t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status" },
                ]}
                sortKey={removeEventSortKey}
                sortDir={removeEventSortDir}
                onSort={handleRemoveEventSort}
              >
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-base-300 bg-base-200/50">
                      <th className="p-3 w-10">
                        <input
                          type="checkbox"
                          className="checkbox checkbox-sm checkbox-primary"
                          checked={allVisibleEventsSelected}
                          onChange={(e) => toggleAllVisibleEvents(e.target.checked)}
                          aria-label={t("js.contacts.integrationSelectAll") !== "js.contacts.integrationSelectAll"
                            ? t("js.contacts.integrationSelectAll")
                            : "Select all"}
                        />
                      </th>
                      <th className="p-3 text-left font-semibold">
                        <button type="button" className="hover:opacity-80" onClick={() => handleRemoveEventSort("title")}>
                          {t("js.event.title") !== "js.event.title" ? t("js.event.title") : "Title"}
                        </button>
                      </th>
                      <th className="p-3 text-left font-semibold">
                        <button type="button" className="hover:opacity-80" onClick={() => handleRemoveEventSort("begin")}>
                          {t("js.event.begin") !== "js.event.begin" ? t("js.event.begin") : "Begin"}
                        </button>
                      </th>
                      <th className="p-3 text-left font-semibold">
                        <button type="button" className="hover:opacity-80" onClick={() => handleRemoveEventSort("status")}>
                          {t("js.common.status") !== "js.common.status" ? t("js.common.status") : "Status"}
                        </button>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {sortedRemoveEvents.map((row) => {
                      const eventType = row.eventType === "concert" ? "performance" : "rehearsal";
                      const typeConfig = getEventTypeConfig(eventType, t);
                      const Icon = getIcon(typeConfig.icon);
                      const title = row.eventType === "concert" ? (row.title ?? t("js.event.performance")) : t("js.event.rehearsal");
                      return (
                        <tr key={`${row.eventType}-${row.id}`} className="border-b border-base-300 hover:bg-base-200/50">
                          <td className="p-3">
                            <input
                              type="checkbox"
                              className="checkbox checkbox-sm checkbox-primary"
                              checked={row.eventType === "rehearsal" ? selReh.has(row.id) : selCon.has(row.id)}
                              onChange={() => (row.eventType === "rehearsal" ? toggle(setSelReh)(row.id) : toggle(setSelCon)(row.id))}
                            />
                          </td>
                          <td className="p-3">
                            <span className="flex items-center gap-2">
                              <span className={`rounded-full flex items-center justify-center w-6 h-6 text-white ${typeConfig.dotClass}`}>
                                <Icon className="h-3 w-3" />
                              </span>
                              <span className="truncate">{title}</span>
                            </span>
                          </td>
                          <td className="p-3">
                            {[formatEventDate(row.begin, lang, tba), formatEventTime(row.begin, lang, tba), row.location_name || ""]
                              .filter(Boolean)
                              .join(" · ")}
                          </td>
                          <td className="p-3">
                            {row.status ? (
                              <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(row.status)}>
                                {statusLabelFor(row.status, t, emptyText)}
                              </span>
                            ) : emptyText}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </ResponsiveTable>
            </section>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <section className="rounded-lg border border-base-300 bg-base-100 p-3">
                <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationPhases")}</h3>
                <SearchField value={qPhases} onChange={setQPhases} placeholder={searchPh} />
                <div className="-mx-3">
                  {filteredPhases.length > 0 && (
                    <CheckboxSelectAllRow
                      checked={filteredPhases.every((p) => selPhases.has(p.id))}
                      onChange={(on) => toggleAll(setSelPhases)(filteredPhases.map((p) => p.id), on)}
                      label={selectAllLabel}
                    />
                  )}
                  <ul>
                    {filteredPhases.map((row) => (
                      <li key={row.id}>
                        <CheckboxRow checked={selPhases.has(row.id)} onToggle={() => toggle(setSelPhases)(row.id)}>
                          <span className="truncate">{row.name ?? row.label ?? `#${row.id}`}</span>
                        </CheckboxRow>
                      </li>
                    ))}
                  </ul>
                </div>
              </section>

              <section className="rounded-lg border border-base-300 bg-base-100 p-3">
                <h3 className="text-sm font-semibold mb-2">{t("js.contacts.integrationVotes")}</h3>
                <SearchField value={qVotes} onChange={setQVotes} placeholder={searchPh} />
                <div className="-mx-3">
                  {filteredVotes.length > 0 && (
                    <CheckboxSelectAllRow
                      checked={filteredVotes.every((v) => selVotes.has(v.id))}
                      onChange={(on) => toggleAll(setSelVotes)(filteredVotes.map((v) => v.id), on)}
                      label={selectAllLabel}
                    />
                  )}
                  <ul>
                    {filteredVotes.map((row) => (
                      <li key={row.id}>
                        <CheckboxRow checked={selVotes.has(row.id)} onToggle={() => toggle(setSelVotes)(row.id)}>
                          <span className="truncate">{row.name ?? row.label ?? `#${row.id}`}</span>
                        </CheckboxRow>
                      </li>
                    ))}
                  </ul>
                </div>
              </section>
            </div>
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
                              <CheckboxRow checked={selMembers.has(m.id)} onToggle={() => toggle(setSelMembers)(m.id)}>
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
              <SimpleEventChecklist
                title={t("js.contacts.integrationRehearsals")}
                rows={filteredRehearsals}
                checkedIds={selReh}
                onToggle={toggle(setSelReh)}
                onToggleAll={toggleAll(setSelReh)}
                searchValue={qReh}
                onSearch={setQReh}
                t={t}
                lang={lang}
                tba={tba}
                emptyText={emptyText}
                type="rehearsal"
                selectAllLabel={selectAllLabel}
              />
              <SimplePhaseChecklist
                title={t("js.contacts.integrationPhases")}
                rows={filteredPhases}
                checkedIds={selPhases}
                onToggle={toggle(setSelPhases)}
                onToggleAll={toggleAll(setSelPhases)}
                searchValue={qPhases}
                onSearch={setQPhases}
                selectAllLabel={selectAllLabel}
                noMatchesLabel={t("js.contacts.integrationNoMatches")}
              />
            </section>
            <section className="space-y-4">
              <SimpleEventChecklist
                title={t("js.contacts.integrationConcerts")}
                rows={filteredConcerts}
                checkedIds={selCon}
                onToggle={toggle(setSelCon)}
                onToggleAll={toggleAll(setSelCon)}
                searchValue={qCon}
                onSearch={setQCon}
                t={t}
                lang={lang}
                tba={tba}
                emptyText={emptyText}
                type="concert"
                selectAllLabel={selectAllLabel}
              />
              <SimpleVoteChecklist
                title={t("js.contacts.integrationVotes")}
                rows={filteredVotes}
                checkedIds={selVotes}
                onToggle={toggle(setSelVotes)}
                onToggleAll={toggleAll(setSelVotes)}
                searchValue={qVotes}
                onSearch={setQVotes}
                selectAllLabel={selectAllLabel}
                noMatchesLabel={t("js.contacts.integrationNoMatches")}
                noVotesLabel={t("js.contacts.integrationNoVotes")}
                allRows={votes}
              />
            </section>
          </div>
        )
      )}

      <div className="fixed bottom-0 left-0 right-0 p-3 bg-base-200/95 border-t border-base-300 md:hidden flex justify-center z-30">
        <button type="button" className="btn btn-primary btn-wide" disabled={saving || loading} onClick={() => save()}>
          {saving
            ? t("js.contacts.integrationSaving")
            : mode === "remove"
              ? (t("js.contacts.integrationRemoveSave") !== "js.contacts.integrationRemoveSave"
                ? t("js.contacts.integrationRemoveSave")
                : "Remove Selected")
              : t("js.contacts.integrationSave")}
        </button>
      </div>
      <ConfirmModal
        open={confirmRemoveOpen}
        onClose={() => setConfirmRemoveOpen(false)}
        title={removeConfirmTitle}
        message={removeConfirmMessage}
        confirmLabel={removeConfirmLabel}
        cancelLabel={cancelLabel}
        onConfirm={() => save(true)}
        variant="danger"
      />
    </div>
  );
}

function SimpleEventChecklist({
  title,
  rows,
  checkedIds,
  onToggle,
  onToggleAll,
  searchValue,
  onSearch,
  t,
  lang,
  tba,
  emptyText,
  type,
  selectAllLabel,
}: {
  title: string;
  rows: IntegrationEventRow[];
  checkedIds: Set<number>;
  onToggle: (id: number) => void;
  onToggleAll: (ids: number[], checked: boolean) => void;
  searchValue: string;
  onSearch: (v: string) => void;
  t: (k: string) => string;
  lang: string;
  tba: string;
  emptyText: string;
  type: "rehearsal" | "concert";
  selectAllLabel: string;
}) {
  return (
    <div>
      <h3 className="text-sm font-semibold mb-2">{title}</h3>
      <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
        <SearchField value={searchValue} onChange={onSearch} placeholder={t("js.common.search")} />
        <div className="-mx-3">
          {rows.length > 0 && (
            <CheckboxSelectAllRow
              checked={rows.every((r) => checkedIds.has(r.id))}
              onChange={(on) => onToggleAll(rows.map((r) => r.id), on)}
              label={selectAllLabel}
            />
          )}
          {rows.length === 0 ? (
            <p className="text-sm text-base-content/50 py-2 px-3">{t("js.contacts.integrationNoMatches")}</p>
          ) : (
            <ul>
              {rows.map((row) => {
                const typeConfig = getEventTypeConfig(type === "concert" ? "performance" : "rehearsal", t);
                const Icon = getIcon(typeConfig.icon);
                const dateStr = formatEventDate(row.begin, lang, tba);
                const timeStr = formatEventTime(row.begin, lang, tba);
                const loc = row.location_name || emptyText;
                const titleValue =
                  type === "concert" && (row.title ?? "").trim().length > 0
                    ? row.title!.trim()
                    : undefined;
                const metaParts = [timeStr];
                if (loc !== emptyText) metaParts.push(loc);
                if (titleValue) metaParts.unshift(titleValue);
                return (
                  <li key={row.id}>
                    <CheckboxRow checked={checkedIds.has(row.id)} onToggle={() => onToggle(row.id)}>
                      <span className="flex items-center gap-3 min-w-0 flex-1">
                        <div className={`shrink-0 flex items-center justify-center size-8 rounded-full text-white ${typeConfig.dotClass}`}>
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
                            <span className="truncate">{metaParts.join(" · ")}</span>
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
  );
}

function SimplePhaseChecklist({
  title,
  rows,
  checkedIds,
  onToggle,
  onToggleAll,
  searchValue,
  onSearch,
  selectAllLabel,
  noMatchesLabel,
}: {
  title: string;
  rows: IntegrationEventRow[];
  checkedIds: Set<number>;
  onToggle: (id: number) => void;
  onToggleAll: (ids: number[], checked: boolean) => void;
  searchValue: string;
  onSearch: (v: string) => void;
  selectAllLabel: string;
  noMatchesLabel: string;
}) {
  return (
    <div>
      <h3 className="text-sm font-semibold mb-2">{title}</h3>
      <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
        <SearchField value={searchValue} onChange={onSearch} placeholder="Search…" />
        <div className="-mx-3">
          {rows.length > 0 && (
            <CheckboxSelectAllRow
              checked={rows.every((r) => checkedIds.has(r.id))}
              onChange={(on) => onToggleAll(rows.map((r) => r.id), on)}
              label={selectAllLabel}
            />
          )}
          {rows.length === 0 ? (
            <p className="text-sm text-base-content/50 py-2 px-3">{noMatchesLabel}</p>
          ) : (
            <ul>
              {rows.map((row) => (
                <li key={row.id}>
                  <CheckboxRow checked={checkedIds.has(row.id)} onToggle={() => onToggle(row.id)}>
                    <span className="truncate">{row.name ?? row.label ?? `#${row.id}`}</span>
                  </CheckboxRow>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}

function SimpleVoteChecklist({
  title,
  rows,
  checkedIds,
  onToggle,
  onToggleAll,
  searchValue,
  onSearch,
  selectAllLabel,
  noMatchesLabel,
  noVotesLabel,
  allRows,
}: {
  title: string;
  rows: IntegrationEventRow[];
  checkedIds: Set<number>;
  onToggle: (id: number) => void;
  onToggleAll: (ids: number[], checked: boolean) => void;
  searchValue: string;
  onSearch: (v: string) => void;
  selectAllLabel: string;
  noMatchesLabel: string;
  noVotesLabel: string;
  allRows: IntegrationEventRow[];
}) {
  return (
    <div>
      <h3 className="text-sm font-semibold mb-2">{title}</h3>
      <div className="rounded-lg border border-base-300 bg-base-100 p-3 max-h-[min(780px,78vh)] overflow-y-auto">
        <SearchField value={searchValue} onChange={onSearch} placeholder="Search…" />
        <div className="-mx-3">
          {rows.length > 0 && (
            <CheckboxSelectAllRow
              checked={rows.every((r) => checkedIds.has(r.id))}
              onChange={(on) => onToggleAll(rows.map((r) => r.id), on)}
              label={selectAllLabel}
            />
          )}
          {rows.length === 0 ? (
            <p className="text-sm text-base-content/50 py-2 px-3">{allRows.length === 0 ? noVotesLabel : noMatchesLabel}</p>
          ) : (
            <ul>
              {rows.map((row) => (
                <li key={row.id}>
                  <CheckboxRow checked={checkedIds.has(row.id)} onToggle={() => onToggle(row.id)}>
                    <span className="truncate">{row.name ?? row.label ?? `#${row.id}`}</span>
                  </CheckboxRow>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}
