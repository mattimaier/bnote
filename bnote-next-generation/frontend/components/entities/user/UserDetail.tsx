/**
 * BNote Next Generation - User detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import Link from "next/link";
import { useI18n } from "@/contexts/I18nContext";
import { usersApi, type UserDetail, type ContactOption, type PrivilegesResponse } from "@/lib/users-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { formatDateTimeShort } from "@/lib/date-time";
import { getStatusPillStyle } from "@/lib/entity-config";
import { DetailCard } from "@/components/DetailCard";
import { DetailEditButton, DetailPageHeader } from "@/components/DetailPageHeader";
import { Avatar } from "@/components/Avatar";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

function normalizeName(value: string) {
  return value.trim().toLowerCase();
}

function resolveContactId(
  user: UserDetail | null,
  contacts: ContactOption[]
): { id: number; label: string } {
  if (!user) return { id: 0, label: "" };
  const explicitId = Number(user.contact ?? 0) || 0;
  const explicitLabel =
    user.contactName ||
    [user.contactFirstName, user.contactSurname].filter(Boolean).join(" ").trim();
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

export function UserDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";

  const [user, setUser] = useState<UserDetail | null>(null);
  const [contacts, setContacts] = useState<ContactOption[]>([]);
  const [privileges, setPrivileges] = useState<PrivilegesResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!id || id === "new" || !ready) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    Promise.all([
      usersApi.get(numId),
      usersApi.getContacts().catch(() => [] as ContactOption[]),
      usersApi.getPrivileges(numId).catch(() => null as PrivilegesResponse | null),
    ])
      .then(([detail, contactList, privilegesData]) => {
        setUser(detail ?? null);
        setContacts(contactList ?? []);
        setPrivileges(privilegesData);
        setError("");
      })
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")))
      .finally(() => setLoading(false));
  }, [id, ready]);

  const contactInfo = useMemo(() => {
    const resolved = resolveContactId(user, contacts);
    return {
      id: resolved.id,
      label: resolved.label || emptyText,
    };
  }, [contacts, user, emptyText]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (id === "new") {
    router.replace(getEntityPath("user", "new", "edit"));
    return null;
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !user) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">{error || "User not found."}</p>
      </div>
    );
  }

  const title = user.login || emptyText;
  const contactDisplayName =
    [user.contactFirstName, user.contactSurname].filter(Boolean).join(" ").trim() || title;
  const titleWithAvatar = (
    <div className="flex items-center gap-3">
      <Avatar
        email={user.contactEmail}
        name={contactDisplayName}
        size={40}
        variant="solid"
        className="shrink-0"
      />
      <span className="truncate">{title}</span>
    </div>
  );
  const statusLabel = user.isActive
    ? t("js.users.active") !== "js.users.active"
      ? t("js.users.active")
      : "Active"
    : t("js.users.inactive") !== "js.users.inactive"
      ? t("js.users.inactive")
      : "Inactive";

  const formattedLastLogin = user.lastlogin
    ? formatDateTimeShort(user.lastlogin, lang) ?? emptyText
    : emptyText;

  return (
    <div className={PAGE_CONTENT_CLASS}>
      <DetailPageHeader
        title={titleWithAvatar}
        subtitle={t("js.users.subtitle") !== "js.users.subtitle" ? t("js.users.subtitle") : "Manage users and permissions"}
        right={<DetailEditButton onClick={() => router.push(getEntityPath("user", user.id, "edit"))} />}
      />

      <DetailCard className="space-y-6">
        <div>
          <span className="text-xs font-medium text-base-content/60">
            {t("js.users.status") !== "js.users.status" ? t("js.users.status") : "Status"}
          </span>
          <div className="mt-1">
            <span
              className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium border"
              style={getStatusPillStyle(user.isActive ? "active" : "inactive")}
            >
              {statusLabel}
            </span>
          </div>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {t("js.users.lastLogin") !== "js.users.lastLogin" ? t("js.users.lastLogin") : "Last login"}
          </span>
          <p className="text-sm mt-1 text-base-content/60">{formattedLastLogin}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {t("js.users.contact") !== "js.users.contact" ? t("js.users.contact") : "Contact"}
          </span>
          <div className="text-sm mt-1">
            {contactInfo.id > 0 ? (
              <Link
                href={getEntityPath("contact", contactInfo.id)}
                className="no-underline text-base-content"
              >
                {contactInfo.label}
              </Link>
            ) : (
              <span>{contactInfo.label}</span>
            )}
          </div>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {t("js.users.privileges") !== "js.users.privileges"
              ? t("js.users.privileges")
              : "Berechtigungen"}
          </span>
          {privileges ? (
            <div className="mt-2 flex flex-wrap gap-2">
              {privileges.modules
                .filter((mod) => mod.hasAccess)
                .map((mod) => (
                  <span key={mod.id} className="badge badge-primary badge-sm">
                    {mod.name}
                  </span>
                ))}
              {privileges.modules.filter((mod) => mod.hasAccess).length === 0 && (
                <span className="text-sm text-base-content/60">
                  {t("js.common.noSelection") !== "js.common.noSelection"
                    ? t("js.common.noSelection")
                    : "No selection"}
                </span>
              )}
            </div>
          ) : (
            <p className="mt-2 text-sm text-base-content/60">
              {t("js.common.loading") !== "js.common.loading" ? t("js.common.loading") : "Loading"}
            </p>
          )}
        </div>
      </DetailCard>
    </div>
  );
}
