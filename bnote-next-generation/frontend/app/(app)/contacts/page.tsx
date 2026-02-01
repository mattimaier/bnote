/**
 * BNote Next Generation - Contacts Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { contactsApi, type Contact, type ContactDetail, type ContactGroup } from "@/lib/contacts-api";
import { Modal } from "@/components/Modal";
import { compareNumber, compareString, type SortDirection } from "@/lib/table-sort";
import { Plus, Pencil, Trash2, ArrowUp, ArrowDown, ArrowUpDown } from "lucide-react";

export default function ContactsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [selectedGroup, setSelectedGroup] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [addOpen, setAddOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [sortKey, setSortKey] = useState<"id" | "name" | "surname" | "nickname" | "instrument" | "email" | "phone" | "city" | null>(null);
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
      setError(err instanceof Error ? err.message : "Failed to load contacts");
      if ((err as { status?: number }).status === 403) {
        showToast(t("js.error.contactsAccessDenied") !== "js.error.contactsAccessDenied" ? t("js.error.contactsAccessDenied") : "Access denied", "error");
      }
    } finally {
      setLoading(false);
    }
  }, [selectedGroup, showToast, t]);

  useEffect(() => {
    if (!ready) return;
    loadGroups();
  }, [ready, loadGroups]);

  useEffect(() => {
    if (!ready) return;
    loadContacts();
  }, [ready, selectedGroup, loadContacts]);

  const filteredContacts = search.trim()
    ? contacts.filter(
        (c) =>
          (c.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (c.surname ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (c.nickname ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (c.email ?? "").toLowerCase().includes(search.toLowerCase())
      )
    : contacts;

  const handleSort = (key: "id" | "name" | "surname" | "nickname" | "instrument" | "email" | "phone" | "city") => {
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
            case "id":
              return compareNumber(a.id, b.id, sortDir);
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

  const handleAdd = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const form = e.currentTarget;
    const data = getFormData(form);
    setSaving(true);
    try {
      await contactsApi.create(data);
      showToast(t("js.contacts.contactCreated") !== "js.contacts.contactCreated" ? t("js.contacts.contactCreated") : "Contact created", "success");
      setAddOpen(false);
      loadContacts();
      loadGroups();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Create failed", "error");
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (editingId == null) return;
    const form = e.currentTarget;
    const data = getFormData(form);
    setSaving(true);
    try {
      await contactsApi.update(editingId, data);
      showToast(t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved", "success");
      setEditOpen(false);
      setEditingId(null);
      loadContacts();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Update failed", "error");
    } finally {
      setSaving(false);
    }
  };

  function getFormData(form: HTMLFormElement): Record<string, unknown> & { groups?: number[] } {
    const fd = new FormData(form);
    const data: Record<string, unknown> = {};
    for (const [k, v] of fd.entries()) {
      if (k === "groups") continue;
      data[k] = v;
    }
    const groupVals = form.querySelectorAll<HTMLInputElement>('input[name="groups"]:checked');
    data.groups = Array.from(groupVals).map((cb) => Number(cb.value));
    return data as Record<string, unknown> & { groups?: number[] };
  }

  const openEdit = (id: number) => {
    setEditingId(id);
    setEditOpen(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm(t("js.common.confirmDelete") !== "js.common.confirmDelete" ? t("js.common.confirmDelete") : "Delete this contact?")) return;
    try {
      await contactsApi.delete(id);
      showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
      loadContacts();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Delete failed", "error");
    }
  };

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-5xl space-y-4">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold" style={{ color: "var(--foreground)" }}>
            {t("js.contacts.title") !== "js.contacts.title" ? t("js.contacts.title") : "Contacts"}
          </h1>
          <p className="text-sm mt-1" style={{ color: "var(--muted-foreground)" }}>
            {t("js.contacts.subtitle") !== "js.contacts.subtitle" ? t("js.contacts.subtitle") : "Manage contacts and groups"}
          </p>
        </div>
        <button
          type="button"
          onClick={() => setAddOpen(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-white shrink-0"
          style={{ background: "var(--primary)" }}
        >
          <Plus className="h-4 w-4" />
          {t("js.contacts.addContact") !== "js.contacts.addContact" ? t("js.contacts.addContact") : "Add Contact"}
        </button>
      </div>

      {error && (
        <div className="rounded-lg border px-4 py-3 text-sm" style={{ borderColor: "var(--destructive)", background: "color-mix(in oklch, var(--destructive) 15%, transparent)", color: "var(--destructive-foreground)" }}>
          {error}
        </div>
      )}

      {/* Group tabs */}
      <div className="flex gap-2 overflow-x-auto pb-2">
        <button
          type="button"
          onClick={() => setSelectedGroup(null)}
          className={`shrink-0 px-4 py-2 rounded-lg font-medium transition-colors ${selectedGroup === null ? "text-white" : ""}`}
          style={{ background: selectedGroup === null ? "var(--primary)" : "var(--muted)", color: selectedGroup === null ? "white" : "var(--foreground)" }}
        >
          {t("js.contacts.all") !== "js.contacts.all" ? t("js.contacts.all") : "All"}
        </button>
        {groups.map((g) => (
          <button
            key={g.id}
            type="button"
            onClick={() => setSelectedGroup(String(g.id))}
            className={`shrink-0 px-4 py-2 rounded-lg font-medium transition-colors ${selectedGroup === String(g.id) ? "text-white" : ""}`}
            style={{ background: selectedGroup === String(g.id) ? "var(--primary)" : "var(--muted)", color: selectedGroup === String(g.id) ? "white" : "var(--foreground)" }}
          >
            {g.name}
          </button>
        ))}
      </div>

      <div className="flex items-center gap-2">
        <input
          type="search"
          placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm w-full max-w-xs"
          style={{ color: "var(--foreground)" }}
        />
      </div>

      <div className="rounded-xl border overflow-hidden" style={{ borderColor: "var(--border)", background: "var(--card)", color: "var(--card-foreground)" }}>
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b" style={{ borderColor: "var(--border)", background: "var(--muted)/30" }}>
                  <ContactsSortableTh label={t("js.table.id")} sortKey="id" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.firstName")} sortKey="name" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.lastName")} sortKey="surname" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.nickname")} sortKey="nickname" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.instrument")} sortKey="instrument" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.email")} sortKey="email" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.phone")} sortKey="phone" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <ContactsSortableTh label={t("js.contacts.city")} sortKey="city" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <th className="w-24 p-3" />
                </tr>
              </thead>
              <tbody>
                {sortedContacts.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="p-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                      {t("js.contacts.noContacts") !== "js.contacts.noContacts" ? t("js.contacts.noContacts") : "No contacts found"}
                    </td>
                  </tr>
                ) : (
                  sortedContacts.map((c) => (
                    <tr
                      key={c.id}
                      className="border-b hover:bg-[var(--muted)]/30 transition-colors cursor-pointer"
                      style={{ borderColor: "var(--border)" }}
                      onClick={() => openEdit(c.id)}
                    >
                      <td className="p-3">{c.id}</td>
                      <td className="p-3 font-medium">{c.name ?? "—"}</td>
                      <td className="p-3">{c.surname ?? "—"}</td>
                      <td className="p-3">{c.nickname ?? "—"}</td>
                      <td className="p-3">{c.instrumentname ?? "—"}</td>
                      <td className="p-3">{c.email ?? "—"}</td>
                      <td className="p-3">{c.phone ?? c.mobile ?? "—"}</td>
                      <td className="p-3">{c.city ?? "—"}</td>
                      <td className="p-3" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center gap-1">
                          <button type="button" onClick={() => openEdit(c.id)} className="p-1.5 rounded hover:bg-[var(--muted)]" title={t("js.common.edit")}><Pencil className="h-4 w-4" /></button>
                          <button type="button" onClick={() => handleDelete(c.id)} className="p-1.5 rounded hover:bg-[var(--destructive)]/20 text-[var(--destructive)]" title={t("js.common.delete")}><Trash2 className="h-4 w-4" /></button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Add Contact Modal */}
      <Modal open={addOpen} onClose={() => setAddOpen(false)} title={t("js.contacts.addContactModalTitle") !== "js.contacts.addContactModalTitle" ? t("js.contacts.addContactModalTitle") : "Add Contact"}>
        <ContactForm groups={groups} onSubmit={handleAdd} onCancel={() => setAddOpen(false)} saving={saving} t={t} />
      </Modal>

      {/* Edit Contact Modal */}
      <Modal open={editOpen} onClose={() => { setEditOpen(false); setEditingId(null); }} title={t("js.contacts.editContactModalTitle") !== "js.contacts.editContactModalTitle" ? t("js.contacts.editContactModalTitle") : "Edit Contact"}>
        {editingId != null && <ContactFormEdit contactId={editingId} groups={groups} onSubmit={handleEdit} onCancel={() => { setEditOpen(false); setEditingId(null); }} saving={saving} t={t} />}
      </Modal>
    </div>
  );
}

type ContactsSortKey = "id" | "name" | "surname" | "nickname" | "instrument" | "email" | "phone" | "city";

function ContactsSortableTh({
  label,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
}: {
  label: string;
  sortKey: ContactsSortKey;
  currentSortKey: ContactsSortKey | null;
  sortDir: SortDirection;
  onSort: (key: ContactsSortKey) => void;
}) {
  const active = currentSortKey === sortKey;
  const Icon = active ? (sortDir === "asc" ? ArrowUp : ArrowDown) : ArrowUpDown;
  return (
    <th className="text-left p-3 font-semibold">
      <button
        type="button"
        onClick={() => onSort(sortKey)}
        className="inline-flex items-center gap-1.5 hover:opacity-80 transition-opacity"
        style={{ color: "var(--foreground)" }}
      >
        {label}
        <Icon className="h-4 w-4 opacity-70" />
      </button>
    </th>
  );
}

function ContactForm({
  groups,
  onSubmit,
  onCancel,
  saving,
  t,
}: {
  groups: ContactGroup[];
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
  onCancel: () => void;
  saving: boolean;
  t: (k: string) => string;
}) {
  return (
    <form onSubmit={onSubmit} className="space-y-4 max-h-[70vh] overflow-y-auto">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.firstName")}</label>
          <input name="name" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.lastName")}</label>
          <input name="surname" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.nickname")}</label>
        <input name="nickname" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.email")}</label>
        <input name="email" type="email" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.phone")}</label>
          <input name="phone" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.mobile")}</label>
          <input name="mobile" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.street")}</label>
        <input name="street" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.zip")}</label>
          <input name="zip" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.city")}</label>
          <input name="city" type="text" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.notes")}</label>
        <textarea name="notes" rows={3} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      {groups.length > 0 && (
        <div>
          <label className="block text-sm font-medium mb-2">{t("js.contacts.groups")}</label>
          <div className="flex flex-wrap gap-2">
            {groups.map((g) => (
              <label key={g.id} className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="groups" value={g.id} className="rounded border-[var(--border)]" />
                <span className="text-sm">{g.name}</span>
              </label>
            ))}
          </div>
        </div>
      )}
      <div className="flex gap-2 justify-end pt-2">
        <button type="button" onClick={onCancel} className="px-4 py-2 rounded-md border border-[var(--border)] text-sm font-medium" style={{ color: "var(--foreground)" }}>{t("js.common.cancel")}</button>
        <button type="submit" disabled={saving} className="px-4 py-2 rounded-md text-sm font-medium text-white" style={{ background: "var(--primary)" }}>{t("js.contacts.create") !== "js.contacts.create" ? t("js.contacts.create") : "Create"}</button>
      </div>
    </form>
  );
}

function ContactFormEdit({
  contactId,
  groups,
  onSubmit,
  onCancel,
  saving,
  t,
}: {
  contactId: number;
  groups: ContactGroup[];
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
  onCancel: () => void;
  saving: boolean;
  t: (k: string) => string;
}) {
  const [contact, setContact] = useState<ContactDetail | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    contactsApi.get(contactId).then(setContact).catch(() => setContact(null)).finally(() => setLoading(false));
  }, [contactId]);

  if (loading) return <div className="py-4 text-sm" style={{ color: "var(--muted-foreground)" }}>{t("js.common.loading")}</div>;
  if (!contact) return <div className="py-4 text-sm text-[var(--destructive)]">Failed to load contact</div>;

  const groupIds = Array.isArray(contact.groups) ? contact.groups : [];

  return (
    <form onSubmit={onSubmit} className="space-y-4 max-h-[70vh] overflow-y-auto">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.firstName")}</label>
          <input name="name" type="text" defaultValue={contact.name} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.lastName")}</label>
          <input name="surname" type="text" defaultValue={contact.surname} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.nickname")}</label>
        <input name="nickname" type="text" defaultValue={contact.nickname} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.email")}</label>
        <input name="email" type="email" defaultValue={contact.email} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.phone")}</label>
          <input name="phone" type="text" defaultValue={contact.phone} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.mobile")}</label>
          <input name="mobile" type="text" defaultValue={contact.mobile} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.street")}</label>
        <input name="street" type="text" defaultValue={contact.street} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.zip")}</label>
          <input name="zip" type="text" defaultValue={contact.zip} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.city")}</label>
          <input name="city" type="text" defaultValue={contact.city} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.contacts.notes")}</label>
        <textarea name="notes" rows={3} defaultValue={contact.notes} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      {groups.length > 0 && (
        <div>
          <label className="block text-sm font-medium mb-2">{t("js.contacts.groups")}</label>
          <div className="flex flex-wrap gap-2">
            {groups.map((g) => (
              <label key={g.id} className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="groups" value={g.id} defaultChecked={groupIds.includes(g.id)} className="rounded border-[var(--border)]" />
                <span className="text-sm">{g.name}</span>
              </label>
            ))}
          </div>
        </div>
      )}
      <div className="flex gap-2 justify-end pt-2">
        <button type="button" onClick={onCancel} className="px-4 py-2 rounded-md border border-[var(--border)] text-sm font-medium" style={{ color: "var(--foreground)" }}>{t("js.common.cancel")}</button>
        <button type="submit" disabled={saving} className="px-4 py-2 rounded-md text-sm font-medium text-white" style={{ background: "var(--primary)" }}>{t("js.common.save")}</button>
      </div>
    </form>
  );
}
