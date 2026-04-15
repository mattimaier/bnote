/**
 * BNote Next Generation - User edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { usersApi, type ContactOption, type PrivilegesResponse, type UserDetail } from "@/lib/users-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { SelectPicker } from "@/components/SelectPicker";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

function normalizeName(value: string) {
  return value.trim().toLowerCase();
}

function resolveContactId(user: UserDetail | null, contacts: ContactOption[]): { id: number; label: string } {
  if (!user) return { id: 0, label: "" };
  const explicitId = Number(user.contact ?? 0) || 0;
  const explicitLabel =
    user.contactName || [user.contactFirstName, user.contactSurname].filter(Boolean).join(" ").trim();
  if (explicitId > 0) return { id: explicitId, label: explicitLabel };

  const firstName = normalizeName(user.contactFirstName ?? "");
  const lastName = normalizeName(user.contactSurname ?? "");
  if (!firstName || !lastName) return { id: 0, label: explicitLabel };

  const matches = contacts.filter((c) => {
    const cFirst = normalizeName(c.name ?? "");
    const cLast = normalizeName(c.surname ?? "");
    return cFirst === firstName && cLast === lastName;
  });
  if (matches.length === 1) {
    return { id: matches[0].id, label: matches[0].label ?? explicitLabel };
  }
  return { id: 0, label: explicitLabel };
}

export function UserEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const isNew = id === "new";

  const [contacts, setContacts] = useState<ContactOption[]>([]);
  const [privileges, setPrivileges] = useState<PrivilegesResponse | null>(null);
  const [userDetail, setUserDetail] = useState<UserDetail | null>(null);

  const [login, setLogin] = useState("");
  const [password, setPassword] = useState("");
  const [contactId, setContactId] = useState(0);
  const [contactLabel, setContactLabel] = useState("");
  const [isActive, setIsActive] = useState(true);
  const [contactResolved, setContactResolved] = useState(false);

  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [savingPrivileges, setSavingPrivileges] = useState(false);
  const [error, setError] = useState("");

  const loadContacts = useCallback(() => {
    usersApi
      .getContacts()
      .then((list) => setContacts(list ?? []))
      .catch(() => setContacts([]));
  }, []);

  const loadUser = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    usersApi
      .get(numId)
      .then((user: UserDetail) => {
        setUserDetail(user);
        setLogin(user.login ?? "");
        const normalizedContactId = Number(user.contact ?? 0) || 0;
        setContactId(normalizedContactId);
        const name = user.contactName || [user.contactFirstName, user.contactSurname].filter(Boolean).join(" ").trim();
        setContactLabel(name || "");
        setIsActive(Boolean(user.isActive));
        setContactResolved(false);
      })
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
      .finally(() => setLoading(false));
  }, [id, isNew]);

  const loadPrivileges = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) return;
    usersApi
      .getPrivileges(numId)
      .then((data) => setPrivileges(data))
      .catch(() => setPrivileges(null));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadContacts();
    loadUser();
    loadPrivileges();
  }, [ready, loadContacts, loadUser, loadPrivileges]);

  useEffect(() => {
    if (contactResolved || !contacts.length) return;
    if (contactId > 0) return;
    const resolved = resolveContactId(
      userDetail ?? ({ login, contact: contactId, contactName: contactLabel } as UserDetail),
      contacts
    );
    if (resolved.id > 0) {
      setContactId(resolved.id);
      setContactLabel(resolved.label);
    }
    setContactResolved(true);
  }, [contactResolved, contacts, contactId, contactLabel, login, userDetail]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      if (isNew) {
        if (!login.trim() || !password.trim()) {
          showToast(t("js.users.login") + " / " + t("js.users.password") + " required", "error");
          setSaving(false);
          return;
        }
        const res = await usersApi.create({
          login: login.trim(),
          password,
          contact: contactId,
          isActive,
        });
        showToast(
          t("js.users.userCreated") !== "js.users.userCreated" ? t("js.users.userCreated") : "User created",
          "success"
        );
        router.replace(getEntityPath("user", res.id, "view"));
      } else {
        await usersApi.update(parseInt(id, 10), {
          ...(password.trim() ? { password: password.trim() } : {}),
          contact: contactId,
          isActive,
        });
        showToast(t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved", "success");
        router.replace(getEntityPath("user", id, "view"));
      }
    } catch (err) {
      const message = getErrorMessage(err, t, "js.common.saveFailed");
      setError(message);
      showToast(message, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = useCallback(() => {
    if (isNew) router.push("/users");
    else router.push(getEntityPath("user", id, "view"));
  }, [isNew, id, router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    const token = setEditingBar({
      isNew,
      saving,
      submitFormId: "user-edit-form",
      onCancel: () => onCancelRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isNew, saving, setEditingBar, clearEditingBar]);

  const handleDelete = async () => {
    if (isNew) return;
    try {
      await usersApi.delete(parseInt(id, 10));
      showToast(t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted", "success");
      router.replace("/users");
    } catch (err) {
      showToast(
        err instanceof Error
          ? err.message
          : t("js.common.deleteFailed") !== "js.common.deleteFailed"
            ? t("js.common.deleteFailed")
            : "Delete failed",
        "error"
      );
    }
  };

  const handlePrivilegesSave = async (selectedIds: number[]) => {
    if (!privileges || isNew || !id) return;
    setSavingPrivileges(true);
    try {
      await usersApi.updatePrivileges(parseInt(id, 10), selectedIds);
      showToast(
        t("js.users.privilegesUpdated") !== "js.users.privilegesUpdated"
          ? t("js.users.privilegesUpdated")
          : "Privileges updated",
        "success"
      );
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSavingPrivileges(false);
    }
  };

  const contactOptions = useMemo(() => {
    const options = [
      {
        id: 0,
        name: t("js.users.noContact") !== "js.users.noContact" ? t("js.users.noContact") : emptyText,
      },
      ...contacts.map((c) => ({
        id: c.id,
        name: [c.name, c.surname].filter(Boolean).join(" ").trim() || c.label,
        subtitle: c.instrument ?? "",
        email: c.email ?? null,
        instrument: c.instrument ?? null,
      })),
    ];
    if (contactId > 0 && !options.some((opt) => opt.id === contactId)) {
      options.push({ id: contactId, name: contactLabel || `${contactId}` });
    }
    return options;
  }, [contacts, contactId, contactLabel, emptyText, t]);

  const selectedPrivileges = useMemo(
    () => privileges?.modules.filter((mod) => mod.hasAccess).map((mod) => mod.id) ?? [],
    [privileges]
  );

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (!isNew && error && !login) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">{error}</p>
      </div>
    );
  }

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <form id="user-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">{error}</div>
        )}

        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">{t("js.users.login")}</label>
              <input
                name="login"
                type="text"
                value={login}
                onChange={(e) => setLogin(e.target.value)}
                readOnly={!isNew}
                disabled={!isNew}
                className={`w-full rounded-field border border-base-300 px-3 py-2 text-sm text-base-content ${!isNew ? "bg-base-200 cursor-not-allowed" : "bg-base-100"}`}
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {isNew
                  ? t("js.users.password") !== "js.users.password"
                    ? t("js.users.password")
                    : "Password"
                  : t("js.users.newPassword") !== "js.users.newPassword"
                    ? t("js.users.newPassword")
                    : "New Password"}
              </label>
              <input
                name="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder={isNew ? t("js.users.passwordPlaceholder") : t("js.users.passwordLeaveEmpty")}
                className="input input-sm w-full text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">{t("js.users.contact")}</label>
              <SelectPicker
                options={contactOptions}
                value={contactId}
                onChange={setContactId}
                emptyLabel="—"
                labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                labelNoMatches={
                  t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"
                }
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
              />
            </div>

            <label className="flex items-center gap-2 cursor-pointer">
              <input
                name="isActive"
                type="checkbox"
                checked={isActive}
                onChange={(e) => setIsActive(e.target.checked)}
                className="checkbox checkbox-primary checkbox-sm"
              />
              <span className="text-sm">
                {t("js.users.active") !== "js.users.active" ? t("js.users.active") : "Active"}
              </span>
            </label>
          </div>
        </div>
      </form>

      {!isNew && (
        <div className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content">
          <div className="flex items-center justify-between gap-4">
            <div>
              <h3 className="text-sm font-semibold text-base-content">
                {t("js.users.managePrivileges") !== "js.users.managePrivileges"
                  ? t("js.users.managePrivileges")
                  : "Manage Privileges"}
              </h3>
              <p className="text-xs mt-1 text-base-content/60">
                {t("js.users.privilegesHint") !== "js.users.privilegesHint"
                  ? t("js.users.privilegesHint")
                  : "Select modules this user can access."}
              </p>
            </div>
          </div>
          <div className="mt-4">
            {privileges ? (
              <div className="space-y-2">
                {privileges.modules.map((mod) => (
                  <label key={mod.id} className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      name="mod"
                      checked={mod.hasAccess}
                      disabled={savingPrivileges}
                      onChange={(e) => {
                        if (savingPrivileges) return;
                        const nextModules = privileges.modules.map((entry) =>
                          entry.id === mod.id ? { ...entry, hasAccess: e.target.checked } : entry
                        );
                        setPrivileges({ ...privileges, modules: nextModules });
                        const nextSelected = nextModules.filter((m) => m.hasAccess).map((m) => m.id);
                        handlePrivilegesSave(nextSelected);
                      }}
                      className="checkbox checkbox-primary checkbox-sm"
                    />
                    <span className="text-sm">{mod.name}</span>
                  </label>
                ))}
                {selectedPrivileges.length === 0 && (
                  <p className="text-xs text-base-content/60">
                    {t("js.common.noSelection") !== "js.common.noSelection"
                      ? t("js.common.noSelection")
                      : "No selection"}
                  </p>
                )}
              </div>
            ) : (
              <p className="text-sm text-base-content/60">
                {t("js.common.loading") !== "js.common.loading" ? t("js.common.loading") : "Loading"}
              </p>
            )}
          </div>
        </div>
      )}

      <DetailDeleteSection canDelete={!isNew} onDelete={handleDelete} entityTitle={login || undefined} />
    </div>
  );
}
