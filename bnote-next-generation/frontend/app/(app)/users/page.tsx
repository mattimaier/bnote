/**
 * BNote Next Generation - Users Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { usersApi, type User } from "@/lib/users-api";
import { getEntityPath } from "@/lib/entities/paths";
import { compareString, compareDate, type SortDirection } from "@/lib/table-sort";
import { formatDateTimeShort } from "@/lib/date-time";
import { getStatusPillStyle } from "@/lib/entity-config";
import { ResponsiveTable } from "@/components/ResponsiveTable";
import { EntityListRow } from "@/components/EntityListRow";
import { Avatar } from "@/components/Avatar";
import { Plus } from "@/components/icons";
import { ActionButton } from "@/components/ActionButton";
import { AppPageHeader } from "@/components/AppPageHeader";
import { Spinner } from "@/components/Spinner";
import { SortableTh } from "@/components/SortableTableHeader";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";

export default function UsersPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const openedIdFromUrl = useRef(false);
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sortKey, setSortKey] = useState<"login" | "firstName" | "lastName" | "status" | "lastLogin" | null>(null);
  const [sortDir, setSortDir] = useState<SortDirection>("asc");

  const loadUsers = useCallback(async () => {
    try {
      const list = await usersApi.list();
      setUsers(list ?? []);
      setError("");
    } catch (err) {
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
      if ((err as { status?: number }).status === 403) {
        showToast(t("js.error.usersAccessDenied") !== "js.error.usersAccessDenied" ? t("js.error.usersAccessDenied") : "Access denied", "error");
      }
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    loadUsers();
  }, [ready, loadUsers]);

  useEffect(() => {
    const idStr = searchParams.get("id");
    if (!ready || loading || !idStr || openedIdFromUrl.current) return;
    const id = parseInt(idStr, 10);
    if (Number.isNaN(id)) return;
    if (users.some((u) => u.id === id)) {
      openedIdFromUrl.current = true;
      router.replace(getEntityPath("user", id));
    }
  }, [ready, loading, users, searchParams, router]);

  const filteredUsers = search.trim()
    ? users.filter(
      (u) =>
        (u.login ?? "").toLowerCase().includes(search.toLowerCase()) ||
        (u.firstName ?? u.name ?? "").toLowerCase().includes(search.toLowerCase()) ||
        (u.lastName ?? "").toLowerCase().includes(search.toLowerCase())
    )
    : users;

  const handleSort = (key: "login" | "firstName" | "lastName" | "status" | "lastLogin") => {
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

  const openDetail = (id: number) => {
    router.push(getEntityPath("user", id));
  };

  const formatDate = (v: string | null | undefined) => {
    if (!v) return emptyText;
    return formatDateTimeShort(v, lang) ?? emptyText;
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
        title={t("js.users.title") !== "js.users.title" ? t("js.users.title") : "User Management"}
        subtitle={t("js.users.subtitle") !== "js.users.subtitle" ? t("js.users.subtitle") : "Manage users and permissions"}
        actions={(
          <ActionButton onClick={() => router.push(getEntityPath("user", "new", "edit"))}>
            <Plus className="h-4 w-4" />
            {t("js.users.addUser") !== "js.users.addUser" ? t("js.users.addUser") : "Add User"}
          </ActionButton>
        )}
      />

      {error && (
        <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">
          {error}
        </div>
      )}

      <div className="flex items-center gap-2">
        <input
          type="search"
          placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="input input-sm w-full max-w-xs text-base-content"
        />
      </div>

      <div className="rounded-none border-0 shadow-none overflow-hidden bg-transparent text-base-content md:rounded-box md:border md:border-base-300 md:bg-base-100">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Spinner />
          </div>
        ) : (
          <ResponsiveTable<User, "login" | "firstName" | "lastName" | "status" | "lastLogin">
            rows={sortedUsers}
            getRowKey={(u) => u.id}
            renderMobileRow={(u) => {
              const fullName = [u.firstName ?? u.name ?? "", u.lastName ?? ""].filter(Boolean).join(" ") || emptyText;
              const statusLabel = u.isActive ? (t("js.users.active") !== "js.users.active" ? t("js.users.active") : "Active") : (t("js.users.inactive") !== "js.users.inactive" ? t("js.users.inactive") : "Inactive");
              const secondary = fullName || undefined;
              return (
                <EntityListRow
                  icon={<Avatar email={u.email} name={fullName || u.login} size={24} variant="soft" />}
                  primary={u.login}
                  badge={
                    <span className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium border" style={getStatusPillStyle(u.isActive ? "active" : "inactive")}>{statusLabel}</span>
                  }
                  secondary={secondary ? <span>{secondary}</span> : undefined}
                  onClick={() => openDetail(u.id)}
                />
              );
            }}
            onRowClick={(u) => openDetail(u.id)}
            emptyMessage={t("js.users.noUsers") !== "js.users.noUsers" ? t("js.users.noUsers") : "No users found"}
            sortOptions={[
              { key: "login", label: t("js.users.login") !== "js.users.login" ? t("js.users.login") : "Login" },
              { key: "firstName", label: t("js.users.firstName") !== "js.users.firstName" ? t("js.users.firstName") : "First name" },
              { key: "lastName", label: t("js.users.lastName") !== "js.users.lastName" ? t("js.users.lastName") : "Last name" },
              { key: "status", label: t("js.users.status") !== "js.users.status" ? t("js.users.status") : "Status" },
              { key: "lastLogin", label: t("js.users.lastLogin") !== "js.users.lastLogin" ? t("js.users.lastLogin") : "Last login" },
            ]}
            sortKey={sortKey}
            sortDir={sortDir}
            onSort={handleSort}
          >
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-base-300 bg-base-200/50">
                  <th className="w-12 p-3" aria-hidden />
                  <SortableTh label={t("js.users.login")} sortKey="login" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.firstName")} sortKey="firstName" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.lastName")} sortKey="lastName" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.status")} sortKey="status" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                  <SortableTh label={t("js.users.lastLogin")} sortKey="lastLogin" currentSortKey={sortKey} sortDir={sortDir} onSort={handleSort} />
                </tr>
              </thead>
              <tbody>
                {sortedUsers.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="p-8 text-center text-base-content/60">
                      {t("js.users.noUsers") !== "js.users.noUsers" ? t("js.users.noUsers") : "No users found"}
                    </td>
                  </tr>
                ) : (
                  sortedUsers.map((u) => (
                    <tr
                      key={u.id}
                      className="border-b border-base-300 hover:bg-base-200/50 transition-colors cursor-pointer"
                      onClick={() => openDetail(u.id)}
                    >
                      <td className="p-3 w-12 align-middle">
                        <Avatar
                          email={u.email}
                          name={[u.firstName ?? u.name, u.lastName].filter(Boolean).join(" ").trim() || u.login}
                          size={32}
                          variant="soft"
                        />
                      </td>
                      <td className="p-3 font-medium">{u.login}</td>
                      <td className="p-3">{u.firstName ?? u.name ?? emptyText}</td>
                      <td className="p-3">{u.lastName ?? emptyText}</td>
                      <td className="p-3">
                        <span
                          className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium border"
                          style={getStatusPillStyle(u.isActive ? "active" : "inactive")}
                        >
                          {u.isActive ? (t("js.users.active") !== "js.users.active" ? t("js.users.active") : "Active") : (t("js.users.inactive") !== "js.users.inactive" ? t("js.users.inactive") : "Inactive")}
                        </span>
                      </td>
                      <td className="p-3 text-base-content/60">{formatDate(u.lastlogin)}</td>
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
