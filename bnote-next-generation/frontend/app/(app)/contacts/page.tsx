/**
 * BNote Next Generation - Contacts Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { contactsApi, type Contact, type ContactGroup, type ContactsAccessProfile } from "@/lib/contacts-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareString, type SortDirection } from "@/lib/table-sort";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { Avatar } from "@/components/Avatar";
import { Plus, Users } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { AppPageHeader } from "@/components/AppPageHeader";
import { SortableTh } from "@/components/SortableTableHeader";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

export default function ContactsPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [accessProfile, setAccessProfile] = useState<ContactsAccessProfile | null>(null);
  const [selectedGroup, setSelectedGroup] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<"name" | "surname" | "nickname" | "instrument" | "email" | "phone" | "city" | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadGroups = useCallback(async () => {
    try {
      const list = await contactsApi.getGroups();
      setGroups(list ?? []);
    } catch {
      setGroups([]);
    }
  }, []);

  const loadContacts = useCallback(async () => {
    setLoading(true);
    try {
      const list = await contactsApi.list(selectedGroup ?? undefined);
      setContacts(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(t("js.error.contactsAccessDenied") !== "js.error.contactsAccessDenied" ? t("js.error.contactsAccessDenied") : "Access denied", "error");
      }
    } finally {
      setLoading(false);
    }
  }, [selectedGroup, showToast, t]);

  const canManageContacts = accessProfile?.canManageContacts ?? true;
  const membersOnlyAccess = accessProfile?.membersOnlyAccess ?? false;

  useEffect(() => {
    if (!ready) return;
    contactsApi
      .getAccessProfile()
      .then((profile) => {
        setAccessProfile(profile);
        if (profile.membersOnlyAccess) {
          setSelectedGroup(String(profile.membersGroupId));
        }
      })
      .catch(() => setAccessProfile(null));
  }, [ready]);

  useEffect(() => {
    if (!ready) return;
    loadGroups();
  }, [ready, loadGroups]);

  useEffect(() => {
    if (!ready) return;
    loadContacts();
  }, [ready, selectedGroup, loadContacts]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (contacts.some((c) => c.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("contact", id));
    }
  }, [ready, loading, contacts, searchParams, router]);

  const filteredContacts = search.trim()
    ? contacts.filter(
      (c) =>
        (c.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
        (c.surname ?? "").toLowerCase().includes(search.toLowerCase()) ||
        (c.nickname ?? "").toLowerCase().includes(search.toLowerCase()) ||
        (c.email ?? "").toLowerCase().includes(search.toLowerCase())
    )
    : contacts;

  const handleSort = (key: "name" | "surname" | "nickname" | "instrument" | "email" | "phone" | "city") => {
    if (sortKey === key) {
      setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setSortKey(key);
      setSortDir("asc");
    }
  };

  const sortedContacts =
    sortKey == null
      ? filteredContacts
      : [...filteredContacts].sort((a, b) => {
        switch (sortKey) {
          case "name":
            return compareString(a.name ?? "", b.name ?? "", sortDir);
          case "surname":
            return compareString(a.surname ?? "", b.surname ?? "", sortDir);
          case "nickname":
            return compareString(a.nickname ?? "", b.nickname ?? "", sortDir);
          case "instrument":
            return compareString(a.instrumentname ?? "", b.instrumentname ?? "", sortDir);
          case "email":
            return compareString(a.email ?? "", b.email ?? "", sortDir);
          case "phone":
            return compareString(a.phone ?? a.mobile ?? "", b.phone ?? b.mobile ?? "", sortDir);
          case "city":
            return compareString(a.city ?? "", b.city ?? "", sortDir);
          default:
            return 0;
        }
      });

  const openDetail = (id: number) => {
    router.push(getEntityPath("contact", id));
  };

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <AppPageHeader
        moduleKey="contact"
        title={t("js.contacts.title") !== "js.contacts.title" ? t("js.contacts.title") : "Contacts"}
        subtitle={t("js.contacts.subtitle") !== "js.contacts.subtitle" ? t("js.contacts.subtitle") : "Manage contacts and groups"}
        actions={(
          <>
            {canManageContacts ? (
              <>
                <ActionButton
                  variant="outline"
                  onClick={() => router.push("/contacts/groups/")}
                >
                  <Users className="h-4 w-4" />
                  {t("js.contacts.manageGroups") !== "js.contacts.manageGroups" ? t("js.contacts.manageGroups") : "Manage Groups"}
                </ActionButton>
                <ActionButton
                  variant="outline"
                  onClick={() =>
                    router.push(
                      selectedGroup != null
                        ? `/contacts/integration/?group=${encodeURIComponent(selectedGroup)}`
                        : "/contacts/integration/"
                    )
                  }
                >
                  {t("js.contacts.integration")}
                </ActionButton>
                <ActionButton onClick={() => router.push(getEntityPath("contact", "new", "edit"))}>
                  <Plus className="h-4 w-4" />
                  {t("js.contacts.addContact") !== "js.contacts.addContact" ? t("js.contacts.addContact") : "Add Contact"}
                </ActionButton>
              </>
            ) : null}
          </>
        )}
      />

      {error && (
        <div className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error">
          {error}
        </div>
      )}

      {/* Group tabs */}
      {!membersOnlyAccess ? (
        <div className="flex flex-wrap gap-2 pb-2">
          <button
            type="button"
            onClick={() => setSelectedGroup(null)}
            className={`filter-bubble filter-bubble-rehearsal ${selectedGroup === null ? "selected" : ""}`}
          >
            {t("js.contacts.all") !== "js.contacts.all" ? t("js.contacts.all") : "All"}
          </button>
          {groups.map((g) => (
            <button
              key={g.id}
              type="button"
              onClick={() => setSelectedGroup(String(g.id))}
              className={`filter-bubble filter-bubble-rehearsal ${selectedGroup === String(g.id) ? "selected" : ""}`}
            >
              {g.name}
            </button>
          ))}
        </div>
      ) : null}

      <div className="flex items-center gap-2">
        <div className="flex w-full items-center gap-3 rounded-lg bg-base-200 px-3 py-2">
          <input
            type="search"
            placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full bg-transparent px-0 py-1 text-sm outline-none text-base-content"
          />
        </div>
      </div>

      <div className="rounded-none border-0 shadow-none overflow-hidden bg-transparent text-base-content md:rounded-box md:border md:border-base-300 md:bg-base-100">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Spinner />
          </div>
        ) : (
          <ResponsiveTable<Contact, ContactsSortKey>
            rows={sortedContacts}
            getRowKey={(c) => c.id}
            renderMobileRow={(c) => {
              const fullName = [c.name ?? "", c.surname ?? ""].filter(Boolean).join(" ") || emptyText;
              return (
                <EntityListRow
                  icon={<Avatar email={c.email} name={fullName} size={24} variant="soft" />}
                  primary={fullName}
                  secondary={c.instrumentname ? <span>{c.instrumentname}</span> : undefined}
                  onClick={() => openDetail(c.id)}
                />
              );
            }}
            onRowClick={(c) => openDetail(c.id)}
            emptyMessage={t("js.contacts.noContacts") !== "js.contacts.noContacts" ? t("js.contacts.noContacts") : "No contacts found"}
            sortOptions={[
              { key: "name", label: t("js.contacts.firstName") !== "js.contacts.firstName" ? t("js.contacts.firstName") : "First name" },
              { key: "surname", label: t("js.contacts.lastName") !== "js.contacts.lastName" ? t("js.contacts.lastName") : "Last name" },
              { key: "nickname", label: t("js.contacts.nickname") !== "js.contacts.nickname" ? t("js.contacts.nickname") : "Nickname" },
              { key: "instrument", label: t("js.contacts.instrument") !== "js.contacts.instrument" ? t("js.contacts.instrument") : "Instrument" },
              { key: "email", label: t("js.contacts.email") !== "js.contacts.email" ? t("js.contacts.email") : "Email" },
              { key: "phone", label: t("js.contacts.phone") !== "js.contacts.phone" ? t("js.contacts.phone") : "Phone" },
              { key: "city", label: t("js.contacts.city") !== "js.contacts.city" ? t("js.contacts.city") : "City" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={handleSort}
          >
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-base-300 bg-base-200/50">
                  <th className="w-12 p-3" aria-hidden />
                  <SortableTh label={t("js.contacts.firstName")} sortKey="name" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.contacts.lastName")} sortKey="surname" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.contacts.nickname")} sortKey="nickname" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.contacts.instrument")} sortKey="instrument" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.contacts.email")} sortKey="email" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.contacts.phone")} sortKey="phone" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.contacts.city")} sortKey="city" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                </tr>
              </thead>
              <tbody>
                {sortedContacts.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="p-8 text-center text-base-content/60">
                      {t("js.contacts.noContacts") !== "js.contacts.noContacts" ? t("js.contacts.noContacts") : "No contacts found"}
                    </td>
                  </tr>
                ) : (
                  sortedContacts.map((c) => (
                    <tr
                      key={c.id}
                      className="border-b border-base-300 hover:bg-base-200/50 transition-colors cursor-pointer"
                      onClick={() => openDetail(c.id)}
                    >
                      <td className="p-3 w-12 align-middle">
                        <Avatar
                          email={c.email}
                          name={[c.name, c.surname].filter(Boolean).join(" ").trim() || emptyText}
                          size={32}
                          variant="soft"
                        />
                      </td>
                      <td className="p-3 font-medium">{c.name ?? emptyText}</td>
                      <td className="p-3">{c.surname ?? emptyText}</td>
                      <td className="p-3">{c.nickname ?? emptyText}</td>
                      <td className="p-3">{c.instrumentname ?? emptyText}</td>
                      <td className="p-3">{c.email ?? emptyText}</td>
                      <td className="p-3">{c.phone ?? c.mobile ?? emptyText}</td>
                      <td className="p-3">{c.city ?? emptyText}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </ResponsiveTable>
        )}
      </div>
    </div>
  );
}

type ContactsSortKey = "name" | "surname" | "nickname" | "instrument" | "email" | "phone" | "city";
