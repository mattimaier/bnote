/**
 * BNote Next Generation - Users Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { usersApi, type User, type UserDetail, type ContactOption, type PrivilegesResponse } from "@/lib/users-api";
import { Modal } from "@/components/Modal";
import { compareNumber, compareString, compareDate, type SortDirection } from "@/lib/table-sort";
import { formatDateTimeShort } from "@/lib/date-time";
import { getStatusPillStyle } from "@/lib/entity-config";
import { Plus, Key, CheckCircle, XCircle, Pencil, Trash2, ArrowUp, ArrowDown, ArrowUpDown } from "lucide-react";

export default function UsersPage() {
  const { t, ready, lang } = useI18n();
  const { showToast } = useToast();
  const [users, setUsers] = useState<User[]>([]);
  const [contacts, setContacts] = useState<ContactOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [addOpen, setAddOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [privilegesOpen, setPrivilegesOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [privilegesUserId, setPrivilegesUserId] = useState<number | null>(null);
  const [privilegesData, setPrivilegesData] = useState<PrivilegesResponse | null>(null);
  const [saving, setSaving] = useState(false);
  const [sortKey, setSortKey] = useState<"id" | "login" | "firstName" | "lastName" | "status" | "lastLogin" | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadUsers = useCallback(async () => {
    try {
      const list = await usersApi.list();
      setUsers(list ?? []);
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load users");
      if ((err as { status?: number }).status === 403) {
        showToast(t("js.error.usersAccessDenied") !== "js.error.usersAccessDenied" ? t("js.error.usersAccessDenied") : "Access denied", "error");
      }
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  const loadContacts = useCallback(async () => {
    try {
      const list = await usersApi.getContacts();
      setContacts(list ?? []);
    } catch {
      setContacts([]);
    }
  }, []);

  useEffect(() => {
    if (!ready) return;
    loadContacts();
    loadUsers();
  }, [ready, loadUsers, loadContacts]);

  const filteredUsers = search.trim()
    ? users.filter(
        (u) =>
          (u.login ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (u.firstName ?? u.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
          (u.lastName ?? "").toLowerCase().includes(search.toLowerCase())
      )
    : users;

  const handleSort = (key: "id" | "login" | "firstName" | "lastName" | "status" | "lastLogin") => {
    if (sortKey === key) {
      setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    } else {
      setSortKey(key);
      setSortDir("asc");
    }
  };

  const sortedUsers =
    sortKey == null
      ? filteredUsers
      : [...filteredUsers].sort((a, b) => {
          switch (sortKey) {
            case "id":
              return compareNumber(a.id, b.id, sortDir);
            case "login":
              return compareString(a.login, b.login, sortDir);
            case "firstName":
              return compareString(a.firstName ?? a.name ?? "", b.firstName ?? b.name ?? "", sortDir);
            case "lastName":
              return compareString(a.lastName ?? "", b.lastName ?? "", sortDir);
            case "status":
              return compareString(a.isActive ? "active" : "inactive", b.isActive ? "active" : "inactive", sortDir);
            case "lastLogin":
              return compareDate(a.lastlogin, b.lastlogin, sortDir);
            default:
              return 0;
          }
        });

  const handleAdd = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const form = e.currentTarget;
    const login = (form.querySelector('[name="login"]') as HTMLInputElement)?.value?.trim();
    const password = (form.querySelector('[name="password"]') as HTMLInputElement)?.value;
    const contact = (form.querySelector('[name="contact"]') as HTMLSelectElement)?.value;
    const isActive = (form.querySelector('[name="isActive"]') as HTMLInputElement)?.checked ?? true;
    if (!login || !password) {
      showToast(t("js.users.login") + " / " + t("js.users.password") + " required", "error");
      return;
    }
    setSaving(true);
    try {
      await usersApi.create({
        login,
        password,
        contact: contact ? Number(contact) : 0,
        isActive,
      });
      showToast(t("js.users.userCreated") !== "js.users.userCreated" ? t("js.users.userCreated") : "User created", "success");
      setAddOpen(false);
      loadUsers();
      loadContacts();
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
    const password = (form.querySelector('[name="password"]') as HTMLInputElement)?.value?.trim();
    const contact = (form.querySelector('[name="contact"]') as HTMLSelectElement)?.value;
    const isActive = (form.querySelector('[name="isActive"]') as HTMLInputElement)?.checked ?? true;
    setSaving(true);
    try {
      await usersApi.update(editingId, {
        ...(password ? { password } : {}),
        contact: contact ? Number(contact) : 0,
        isActive,
      });
      showToast(t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved", "success");
      setEditOpen(false);
      setEditingId(null);
      loadUsers();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Update failed", "error");
    } finally {
      setSaving(false);
    }
  };

  const openEdit = async (id: number) => {
    setEditingId(id);
    setEditOpen(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm(t("js.common.confirmDelete") !== "js.common.confirmDelete" ? t("js.common.confirmDelete") : "Delete this user?")) return;
    try {
      await usersApi.delete(id);
      showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
      loadUsers();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Delete failed", "error");
    }
  };

  const handleActivate = async (id: number) => {
    try {
      await usersApi.activate(id);
      showToast(t("js.users.activated") !== "js.users.activated" ? t("js.users.activated") : "User activated/deactivated", "success");
      loadUsers();
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Update failed", "error");
    }
  };

  const openPrivileges = async (id: number) => {
    setPrivilegesUserId(id);
    try {
      const data = await usersApi.getPrivileges(id);
      setPrivilegesData(data);
      setPrivilegesOpen(true);
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Failed to load privileges", "error");
    }
  };

  const handlePrivilegesSave = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (privilegesUserId == null || !privilegesData) return;
    const form = e.currentTarget;
    const checkboxes = form.querySelectorAll<HTMLInputElement>('input[name="mod"]:checked');
    const privileges = Array.from(checkboxes).map((cb) => Number(cb.value));
    setSaving(true);
    try {
      await usersApi.updatePrivileges(privilegesUserId, privileges);
      showToast(t("js.users.privilegesUpdated") !== "js.users.privilegesUpdated" ? t("js.users.privilegesUpdated") : "Privileges updated", "success");
      setPrivilegesOpen(false);
      setPrivilegesUserId(null);
      setPrivilegesData(null);
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Save failed", "error");
    } finally {
      setSaving(false);
    }
  };

  const formatDate = (v: string | null | undefined) => {
    if (!v) return "—";
    return formatDateTimeShort(v, lang) ?? "—";
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
            {t("js.users.title") !== "js.users.title" ? t("js.users.title") : "User Management"}
          </h1>
          <p className="text-sm mt-1" style={{ color: "var(--muted-foreground)" }}>
            {t("js.users.subtitle") !== "js.users.subtitle" ? t("js.users.subtitle") : "Manage users and permissions"}
          </p>
        </div>
        <button
          type="button"
          onClick={() => setAddOpen(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-white shrink-0"
          style={{ background: "var(--primary)" }}
        >
          <Plus className="h-4 w-4" />
          {t("js.users.addUser") !== "js.users.addUser" ? t("js.users.addUser") : "Add User"}
        </button>
      </div>

      {error && (
        <div className="rounded-lg border px-4 py-3 text-sm" style={{ borderColor: "var(--destructive)", background: "color-mix(in oklch, var(--destructive) 15%, transparent)", color: "var(--destructive-foreground)" }}>
          {error}
        </div>
      )}

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
                  <SortableTh label={t("js.table.id")} sortKey="id" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.login")} sortKey="login" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.firstName")} sortKey="firstName" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.lastName")} sortKey="lastName" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.status")} sortKey="status" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.lastLogin")} sortKey="lastLogin" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <th className="w-32 p-3" />
                </tr>
              </thead>
              <tbody>
                {sortedUsers.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="p-8 text-center" style={{ color: "var(--muted-foreground)" }}>
                      {t("js.users.noUsers") !== "js.users.noUsers" ? t("js.users.noUsers") : "No users found"}
                    </td>
                  </tr>
                ) : (
                  sortedUsers.map((u) => (
                    <tr
                      key={u.id}
                      className="border-b hover:bg-[var(--muted)]/30 transition-colors"
                      style={{ borderColor: "var(--border)" }}
                    >
                      <td className="p-3">{u.id}</td>
                      <td className="p-3 font-medium">{u.login}</td>
                      <td className="p-3">{u.firstName ?? u.name ?? "—"}</td>
                      <td className="p-3">{u.lastName ?? "—"}</td>
                      <td className="p-3">
                        <span
                          className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium border"
                          style={getStatusPillStyle(u.isActive ? "active" : "inactive")}
                        >
                          {u.isActive ? (t("js.users.active") !== "js.users.active" ? t("js.users.active") : "Active") : (t("js.users.inactive") !== "js.users.inactive" ? t("js.users.inactive") : "Inactive")}
                        </span>
                      </td>
                      <td className="p-3" style={{ color: "var(--muted-foreground)" }}>{formatDate(u.lastlogin)}</td>
                      <td className="p-3">
                        <div className="flex items-center gap-1">
                          <button type="button" onClick={() => openEdit(u.id)} className="p-1.5 rounded hover:bg-[var(--muted)]" title={t("js.common.edit")}><Pencil className="h-4 w-4" /></button>
                          <button type="button" onClick={() => openPrivileges(u.id)} className="p-1.5 rounded hover:bg-[var(--muted)]" title={t("js.users.managePrivileges")}><Key className="h-4 w-4" /></button>
                          <button type="button" onClick={() => handleActivate(u.id)} className="p-1.5 rounded hover:bg-[var(--muted)]" title={u.isActive ? t("js.users.deactivate") : t("js.users.activate")}>
                            {u.isActive ? <XCircle className="h-4 w-4" /> : <CheckCircle className="h-4 w-4" />}
                          </button>
                          <button type="button" onClick={() => handleDelete(u.id)} className="p-1.5 rounded hover:bg-[var(--destructive)]/20 text-[var(--destructive)]" title={t("js.common.delete")}><Trash2 className="h-4 w-4" /></button>
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

      {/* Add User Modal */}
      <Modal open={addOpen} onClose={() => setAddOpen(false)} title={t("js.users.addUserModalTitle") !== "js.users.addUserModalTitle" ? t("js.users.addUserModalTitle") : "Add User"}>
        <form onSubmit={handleAdd} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.users.login")}</label>
            <input name="login" type="text" required placeholder={t("js.users.loginPlaceholder")} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.users.password")}</label>
            <input name="password" type="password" required placeholder={t("js.users.passwordPlaceholder")} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.users.contact")}</label>
            <select name="contact" className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }}>
              <option value="">{t("js.users.noContact") !== "js.users.noContact" ? t("js.users.noContact") : "—"}</option>
              {contacts.map((c) => (
                <option key={c.id} value={c.id}>{c.label}</option>
              ))}
            </select>
          </div>
          <div className="flex items-center gap-2">
            <input name="isActive" type="checkbox" defaultChecked className="rounded border-[var(--border)]" />
            <label className="text-sm">{t("js.users.active")}</label>
          </div>
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" onClick={() => setAddOpen(false)} className="px-4 py-2 rounded-md border border-[var(--border)] text-sm font-medium" style={{ color: "var(--foreground)" }}>{t("js.common.cancel")}</button>
            <button type="submit" disabled={saving} className="px-4 py-2 rounded-md text-sm font-medium text-white" style={{ background: "var(--primary)" }}>{t("js.users.create") !== "js.users.create" ? t("js.users.create") : "Create"}</button>
          </div>
        </form>
      </Modal>

      {/* Edit User Modal */}
      <Modal open={editOpen} onClose={() => { setEditOpen(false); setEditingId(null); }} title={t("js.users.editUserModalTitle") !== "js.users.editUserModalTitle" ? t("js.users.editUserModalTitle") : "Edit User"}>
        {editingId != null && <EditUserForm userId={editingId} contacts={contacts} onSave={handleEdit} onCancel={() => { setEditOpen(false); setEditingId(null); }} saving={saving} t={t} />}
      </Modal>

      {/* Privileges Modal */}
      <Modal open={privilegesOpen} onClose={() => { setPrivilegesOpen(false); setPrivilegesUserId(null); setPrivilegesData(null); }} title={t("js.users.managePrivileges") !== "js.users.managePrivileges" ? t("js.users.managePrivileges") : "Manage Privileges"}>
        {privilegesData && (
          <form onSubmit={handlePrivilegesSave} className="space-y-4">
            <p className="text-sm" style={{ color: "var(--muted-foreground)" }}>Select modules this user can access.</p>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {privilegesData.modules.map((mod) => (
                <label key={mod.id} className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" name="mod" value={mod.id} defaultChecked={mod.hasAccess} className="rounded border-[var(--border)]" />
                  <span className="text-sm">{mod.name}</span>
                </label>
              ))}
            </div>
            <div className="flex gap-2 justify-end pt-2">
              <button type="button" onClick={() => { setPrivilegesOpen(false); setPrivilegesData(null); }} className="px-4 py-2 rounded-md border border-[var(--border)] text-sm font-medium" style={{ color: "var(--foreground)" }}>{t("js.common.cancel")}</button>
              <button type="submit" disabled={saving} className="px-4 py-2 rounded-md text-sm font-medium text-white" style={{ background: "var(--primary)" }}>{t("js.common.save")}</button>
            </div>
          </form>
        )}
      </Modal>
    </div>
  );
}

function SortableTh({
  label,
  sortKey,
  currentSortKey,
  sortDir,
  onSort,
}: {
  label: string;
  sortKey: "id" | "login" | "firstName" | "lastName" | "status" | "lastLogin";
  currentSortKey: "id" | "login" | "firstName" | "lastName" | "status" | "lastLogin" | null;
  sortDir: SortDirection;
  onSort: (key: typeof sortKey) => void;
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

function EditUserForm({
  userId,
  contacts,
  onSave,
  onCancel,
  saving,
  t,
}: {
  userId: number;
  contacts: ContactOption[];
  onSave: (e: React.FormEvent<HTMLFormElement>) => void;
  onCancel: () => void;
  saving: boolean;
  t: (k: string) => string;
}) {
  const [user, setUser] = useState<UserDetail | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    usersApi.get(userId).then(setUser).catch(() => setUser(null)).finally(() => setLoading(false));
  }, [userId]);

  if (loading) return <div className="py-4 text-sm" style={{ color: "var(--muted-foreground)" }}>{t("js.common.loading")}</div>;
  if (!user) return <div className="py-4 text-sm text-[var(--destructive)]">Failed to load user</div>;

  return (
    <form onSubmit={onSave} className="space-y-4">
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.users.login")}</label>
        <input name="login" type="text" value={user.login} readOnly disabled className="w-full rounded-md border border-[var(--border)] bg-[var(--muted)] px-3 py-2 text-sm cursor-not-allowed" style={{ color: "var(--foreground)" }} />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.users.newPassword") !== "js.users.newPassword" ? t("js.users.newPassword") : "New Password"}</label>
        <input name="password" type="password" placeholder={t("js.users.passwordLeaveEmpty")} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }} />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">{t("js.users.contact")}</label>
        <select name="contact" defaultValue={user.contact} className="w-full rounded-md border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm" style={{ color: "var(--foreground)" }}>
          <option value="0">{t("js.users.noContact")}</option>
          {contacts.map((c) => (
            <option key={c.id} value={c.id}>{c.label}</option>
          ))}
        </select>
      </div>
      <div className="flex items-center gap-2">
        <input name="isActive" type="checkbox" defaultChecked={user.isActive} className="rounded border-[var(--border)]" />
        <label className="text-sm">{t("js.users.active")}</label>
      </div>
      <div className="flex gap-2 justify-end pt-2">
        <button type="button" onClick={onCancel} className="px-4 py-2 rounded-md border border-[var(--border)] text-sm font-medium" style={{ color: "var(--foreground)" }}>{t("js.common.cancel")}</button>
        <button type="submit" disabled={saving} className="px-4 py-2 rounded-md text-sm font-medium text-white" style={{ background: "var(--primary)" }}>{t("js.common.save")}</button>
      </div>
    </form>
  );
}
