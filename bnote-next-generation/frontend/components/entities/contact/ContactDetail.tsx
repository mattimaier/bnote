/**
 * BNote Next Generation - Contact detail view
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useI18n } from "@/contexts/I18nContext";
import { contactsApi, type ContactDetail, type ContactGroup, type ContactsAccessProfile } from "@/lib/contacts-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getEntityPath } from "@/lib/entities/paths";
import { DetailEditButton } from "@/components/DetailPageHeader";
import { EntityDetailViewLayout } from "@/components/EntityDetailViewLayout";
import { ActionButton } from "@/components/ActionButton";
import { Trash2 } from "@/components/icons";
import { Avatar } from "@/components/Avatar";
import { NotesContent } from "@/components/NotesContent";
import { formatDateShortDisplay } from "@/lib/date-time";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";

export function ContactDetail() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";

  const [contact, setContact] = useState<ContactDetail | null | undefined>(undefined);
  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [accessProfile, setAccessProfile] = useState<ContactsAccessProfile | null>(null);
  const [error, setError] = useState("");
  const parsedId = useMemo(() => {
    if (!id || id === "new") return null;
    const numId = parseInt(id, 10);
    return Number.isNaN(numId) ? null : numId;
  }, [id]);

  useEffect(() => {
    if (!ready || parsedId == null) return;
    Promise.all([
      contactsApi.get(parsedId),
      contactsApi.getGroups().catch(() => [] as ContactGroup[]),
      contactsApi.getAccessProfile().catch(() => null as ContactsAccessProfile | null),
    ])
      .then(([detail, groupList, profile]) => {
        setContact(detail ?? null);
        setGroups(groupList ?? []);
        setAccessProfile(profile);
        setError("");
      })
      .catch((err) => setError(getErrorMessage(err, t, "js.common.failedToLoad")));
  }, [parsedId, ready, t]);

  const groupLabels = useMemo(() => {
    if (!contact?.groups || contact.groups.length === 0) return emptyText;
    const groupMap = new Map(groups.map((g) => [g.id, g.name]));
    const labels = contact.groups
      .map((gid) => groupMap.get(gid) ?? "")
      .filter(Boolean);
    return labels.length > 0 ? labels.join(", ") : emptyText;
  }, [contact?.groups, groups, emptyText]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (id === "new") {
    router.replace(getEntityPath("contact", "new", "edit"));
    return null;
  }

  if (contact === undefined && !error) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (error || !contact) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <p className="text-sm text-error">{error || "Contact not found."}</p>
      </div>
    );
  }

  const title = `${contact.name ?? ""} ${contact.surname ?? ""}`.trim() || emptyText;
  const titleWithAvatar = (
    <div className="flex items-center gap-3">
      <Avatar
        email={contact.email}
        name={title}
        size={40}
        variant="solid"
        className="shrink-0"
      />
      <span className="truncate">{title}</span>
    </div>
  );
  const birthdayValue =
    contact.birthday && contact.birthday !== "0000-00-00"
      ? formatDateShortDisplay(contact.birthday, lang)
      : emptyText;

  const label = (key: string, fallback: string) =>
    t(key) !== key ? t(key) : fallback;
  const yesLabel =
    t("js.common.yes") !== "js.common.yes" ? t("js.common.yes") : "Yes";
  const noLabel =
    t("js.common.no") !== "js.common.no" ? t("js.common.no") : "No";
  const getBadgeClass = (value?: boolean) =>
    value ? "badge badge-success badge-sm" : "badge badge-error badge-sm";
  const removeFromFutureLabel =
    t("js.contacts.removeFromFutureEvents") !== "js.contacts.removeFromFutureEvents"
      ? t("js.contacts.removeFromFutureEvents")
      : "Remove From Future Events";
  const canManageContacts = accessProfile?.canManageContacts ?? true;

  return (
    <EntityDetailViewLayout
      title={titleWithAvatar}
      subtitle={label("js.contacts.subtitle", "Manage contacts and groups")}
      right={
        canManageContacts ? (
          <div className="flex items-center gap-2">
            <ActionButton
              variant="outline"
              onClick={() =>
                router.push(
                  `/contacts/integration/?mode=remove&contact=${encodeURIComponent(String(contact.id))}`
                )
              }
            >
              <Trash2 className="h-4 w-4" />
              {removeFromFutureLabel}
            </ActionButton>
            <DetailEditButton
              onClick={() => router.push(getEntityPath("contact", contact.id, "edit"))}
            />
          </div>
        ) : null
      }
    >
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.contacts.firstName", "First name")}
            </span>
            <p className="text-sm mt-1">{contact.name || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.contacts.lastName", "Last name")}
            </span>
            <p className="text-sm mt-1">{contact.surname || emptyText}</p>
          </div>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.nickname", "Nickname")}
          </span>
          <p className="text-sm mt-1">{contact.nickname || emptyText}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.instrument", "Instrument")}
          </span>
          <p className="text-sm mt-1">{contact.instrumentname || emptyText}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.email", "E-Mail")}
          </span>
          <p className="text-sm mt-1">{contact.email || emptyText}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.birthday", "Birthday")}
          </span>
          <p className="text-sm mt-1">{birthdayValue}</p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.contacts.phone", "Phone")}
            </span>
            <p className="text-sm mt-1">{contact.phone || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.contacts.mobile", "Mobile")}
            </span>
            <p className="text-sm mt-1">{contact.mobile || emptyText}</p>
          </div>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.street", "Street")}
          </span>
          <p className="text-sm mt-1">{contact.street || emptyText}</p>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.contacts.zip", "ZIP")}
            </span>
            <p className="text-sm mt-1">{contact.zip || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">
              {label("js.contacts.city", "City")}
            </span>
            <p className="text-sm mt-1">{contact.city || emptyText}</p>
          </div>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.company", "Company")}
          </span>
          <p className="text-sm mt-1">{contact.company || emptyText}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.business", "Business")}
          </span>
          <p className="text-sm mt-1">{contact.business || emptyText}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.web", "Website")}
          </span>
          <p className="text-sm mt-1">{contact.web || emptyText}</p>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.notes", "Notes")}
          </span>
          <div className="text-sm mt-1 prose prose-sm max-w-none dark:prose-invert">
            {contact.notes ? <NotesContent value={contact.notes} /> : <p>{emptyText}</p>}
          </div>
        </div>

        <div>
          <span className="text-xs font-medium text-base-content/60">
            {label("js.contacts.groups", "Groups")}
          </span>
          <p className="text-sm mt-1">{groupLabels}</p>
        </div>

        <div className="rounded-none border-0 p-4 md:rounded-box md:border md:border-base-300 md:p-4 bg-base-100 md:bg-transparent">
          <h3 className="text-sm font-semibold text-base-content">
            {label("js.profile.privacyTitle", "Visibility")}
          </h3>
          <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {label("js.profile.shareEmail", "Share email")}
              </span>
              <div className="mt-1">
                <span className={getBadgeClass(contact.share_email)}>
                  {contact.share_email ? yesLabel : noLabel}
                </span>
              </div>
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {label("js.profile.shareAddress", "Share address")}
              </span>
              <div className="mt-1">
                <span className={getBadgeClass(contact.share_address)}>
                  {contact.share_address ? yesLabel : noLabel}
                </span>
              </div>
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {label("js.profile.sharePhones", "Share phones")}
              </span>
              <div className="mt-1">
                <span className={getBadgeClass(contact.share_phones)}>
                  {contact.share_phones ? yesLabel : noLabel}
                </span>
              </div>
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">
                {label("js.profile.shareBirthday", "Share birthday")}
              </span>
              <div className="mt-1">
                <span className={getBadgeClass(contact.share_birthday)}>
                  {contact.share_birthday ? yesLabel : noLabel}
                </span>
              </div>
            </div>
          </div>
        </div>
    </EntityDetailViewLayout>
  );
}
