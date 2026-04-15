"use client";

import { useCallback, useEffect, useMemo, useState, type Dispatch, type SetStateAction } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { contactsApi, type Contact, type ContactGroupListItem } from "@/lib/contacts-api";
import { PAGE_CONTENT_CLASS } from "@/lib/layout";
import { getErrorMessage } from "@/lib/error-utils";
import { AppPageHeader } from "@/components/AppPageHeader";
import { ActionButton } from "@/components/ActionButton";
import { Avatar } from "@/components/Avatar";
import { CheckboxRow, CheckboxSelectAllRow } from "@/components/CheckboxRow";
import { ConfirmModal } from "@/components/ConfirmModal";
import { SearchField } from "@/components/SearchField";
import { Spinner } from "@/components/Spinner";
import { Plus, Trash2, Users } from "@/components/icons";
import { isEmptyEditorJson } from "@/lib/editorjs-notes";

const PROTECTED_GROUP_ID = 2;

function isProtectedGroup(group: ContactGroupListItem | null): boolean {
  if (!group) return false;
  const normalized = (group.name ?? "").trim().toLowerCase();
  return group.id === PROTECTED_GROUP_ID || normalized === "games participants" || normalized === "mitglieder";
}

function sortedNumeric(values: Iterable<number>): number[] {
  return [...values].sort((a, b) => a - b);
}

export default function ContactsGroupsPage() {
  const { t, ready } = useI18n();
  const { showToast } = useToast();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const searchPlaceholder = t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search...";

  const [groups, setGroups] = useState<ContactGroupListItem[]>([]);
  const [groupsLoading, setGroupsLoading] = useState(true);
  const [selectedGroupId, setSelectedGroupId] = useState<number | null>(null);
  const [allContacts, setAllContacts] = useState<Contact[]>([]);
  const [contactsLoading, setContactsLoading] = useState(true);
  const [groupMemberIds, setGroupMemberIds] = useState<Set<number>>(new Set());
  const [membersLoading, setMembersLoading] = useState(false);
  const [savingMembers, setSavingMembers] = useState(false);
  const [creatingGroup, setCreatingGroup] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [error, setError] = useState("");

  const [newGroupName, setNewGroupName] = useState("");
  const [qGroups, setQGroups] = useState("");
  const [qMembers, setQMembers] = useState("");
  const [qAvailable, setQAvailable] = useState("");
  const [selectedToAdd, setSelectedToAdd] = useState<Set<number>>(new Set());
  const [selectedToRemove, setSelectedToRemove] = useState<Set<number>>(new Set());

  const loadGroups = useCallback(async () => {
    setGroupsLoading(true);
    try {
      const list = await contactsApi.listGroups();
      const normalized = list ?? [];
      setGroups(normalized);
      setSelectedGroupId((prev) => {
        if (normalized.length < 1) return null;
        if (prev != null && normalized.some((g) => g.id === prev)) return prev;
        return normalized[0]!.id;
      });
      setError("");
    } catch (err) {
      setGroups([]);
      setSelectedGroupId(null);
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setGroupsLoading(false);
    }
  }, [t]);

  const loadAllContacts = useCallback(async () => {
    setContactsLoading(true);
    try {
      const list = await contactsApi.list();
      setAllContacts(list ?? []);
      setError("");
    } catch (err) {
      setAllContacts([]);
      setError(getErrorMessage(err, t, "js.common.failedToLoad"));
    } finally {
      setContactsLoading(false);
    }
  }, [t]);

  const loadGroupMembers = useCallback(
    async (groupId: number | null) => {
      if (groupId == null) {
        setGroupMemberIds(new Set());
        return;
      }
      setMembersLoading(true);
      try {
        const members = await contactsApi.list(String(groupId));
        setGroupMemberIds(new Set((members ?? []).map((m) => m.id)));
      } catch (err) {
        setGroupMemberIds(new Set());
        showToast(getErrorMessage(err, t, "js.common.failedToLoad"), "error");
      } finally {
        setMembersLoading(false);
      }
    },
    [showToast, t]
  );

  useEffect(() => {
    if (!ready) return;
    void loadGroups();
    void loadAllContacts();
  }, [ready, loadGroups, loadAllContacts]);

  useEffect(() => {
    if (!ready) return;
    void loadGroupMembers(selectedGroupId);
    setSelectedToAdd(new Set());
    setSelectedToRemove(new Set());
  }, [ready, selectedGroupId, loadGroupMembers]);

  const selectedGroup = useMemo(() => groups.find((g) => g.id === selectedGroupId) ?? null, [groups, selectedGroupId]);
  const selectedGroupProtected = isProtectedGroup(selectedGroup);

  const filteredGroups = useMemo(() => {
    const q = qGroups.trim().toLowerCase();
    if (!q) return groups;
    return groups.filter((g) => g.name.toLowerCase().includes(q));
  }, [groups, qGroups]);

  const members = useMemo(() => allContacts.filter((c) => groupMemberIds.has(c.id)), [allContacts, groupMemberIds]);
  const availableContacts = useMemo(
    () => allContacts.filter((c) => !groupMemberIds.has(c.id)),
    [allContacts, groupMemberIds]
  );

  const filteredMembers = useMemo(() => {
    const q = qMembers.trim().toLowerCase();
    if (!q) return members;
    return members.filter((c) =>
      [c.name, c.surname, c.nickname, c.email, c.instrumentname].filter(Boolean).join(" ").toLowerCase().includes(q)
    );
  }, [members, qMembers]);

  const filteredAvailable = useMemo(() => {
    const q = qAvailable.trim().toLowerCase();
    if (!q) return availableContacts;
    return availableContacts.filter((c) =>
      [c.name, c.surname, c.nickname, c.email, c.instrumentname].filter(Boolean).join(" ").toLowerCase().includes(q)
    );
  }, [availableContacts, qAvailable]);

  const toggleSetValue = (setter: Dispatch<SetStateAction<Set<number>>>, id: number) => {
    setter((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const toggleAllSetValues = (setter: Dispatch<SetStateAction<Set<number>>>, ids: number[], checked: boolean) => {
    setter((prev) => {
      const next = new Set(prev);
      if (checked) ids.forEach((id) => next.add(id));
      else ids.forEach((id) => next.delete(id));
      return next;
    });
  };

  const updateMembership = async (contactIds: number[], mode: "add" | "remove") => {
    if (selectedGroupId == null || contactIds.length < 1) return;
    setSavingMembers(true);
    try {
      let changed = 0;
      for (const contactId of contactIds) {
        const detail = await contactsApi.get(contactId);
        const nextGroups = new Set(detail.groups ?? []);
        if (mode === "add") nextGroups.add(selectedGroupId);
        else nextGroups.delete(selectedGroupId);
        const before = JSON.stringify(sortedNumeric(detail.groups ?? []));
        const after = JSON.stringify(sortedNumeric(nextGroups));
        if (before === after) continue;

        // Contacts backend expects a regular contact update payload and fails
        // when only group ids are sent; keep existing values unchanged.
        await contactsApi.update(contactId, {
          name: detail.name,
          surname: detail.surname,
          nickname: detail.nickname,
          company: detail.company,
          phone: detail.phone,
          mobile: detail.mobile,
          business: detail.business,
          email: detail.email,
          web: detail.web,
          notes: isEmptyEditorJson(detail.notes ?? "") ? "" : detail.notes,
          instrument: detail.instrument,
          is_conductor: detail.is_conductor,
          birthday: detail.birthday,
          status: detail.status,
          street: detail.street,
          city: detail.city,
          zip: detail.zip,
          share_address: detail.share_address,
          share_phones: detail.share_phones,
          share_birthday: detail.share_birthday,
          share_email: detail.share_email,
          groups: sortedNumeric(nextGroups),
        });
        changed += 1;
      }
      showToast(
        changed > 0
          ? mode === "add"
            ? t("js.contacts.groupsManager.membersAdded") !== "js.contacts.groupsManager.membersAdded"
              ? t("js.contacts.groupsManager.membersAdded")
              : "Members added."
            : t("js.contacts.groupsManager.membersRemoved") !== "js.contacts.groupsManager.membersRemoved"
              ? t("js.contacts.groupsManager.membersRemoved")
              : "Members removed."
          : t("js.contacts.groupsManager.noChanges") !== "js.contacts.groupsManager.noChanges"
            ? t("js.contacts.groupsManager.noChanges")
            : "No changes to save.",
        "success"
      );
      await Promise.all([loadGroupMembers(selectedGroupId), loadGroups()]);
      if (mode === "add") setSelectedToAdd(new Set());
      else setSelectedToRemove(new Set());
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToSave"), "error");
    } finally {
      setSavingMembers(false);
    }
  };

  const createGroup = async () => {
    const name = newGroupName.trim();
    if (!name) {
      showToast(
        t("js.contacts.groupsManager.nameRequired") !== "js.contacts.groupsManager.nameRequired"
          ? t("js.contacts.groupsManager.nameRequired")
          : "Group name is required.",
        "default"
      );
      return;
    }
    setCreatingGroup(true);
    try {
      const result = await contactsApi.createGroup(name, true);
      await loadGroups();
      if (result?.id) setSelectedGroupId(result.id);
      setNewGroupName("");
      showToast(
        t("js.contacts.groupsManager.groupCreated") !== "js.contacts.groupsManager.groupCreated"
          ? t("js.contacts.groupsManager.groupCreated")
          : "Group created.",
        "success"
      );
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToSave"), "error");
    } finally {
      setCreatingGroup(false);
    }
  };

  const deleteSelectedGroup = async () => {
    if (selectedGroup == null) return;
    if (selectedGroupProtected) {
      setDeleteOpen(false);
      showToast(
        t("js.contacts.groupsManager.protectedGroupHint") !== "js.contacts.groupsManager.protectedGroupHint"
          ? t("js.contacts.groupsManager.protectedGroupHint")
          : "This default group cannot be removed.",
        "default"
      );
      return;
    }
    try {
      await contactsApi.deleteGroup(selectedGroup.id);
      setDeleteOpen(false);
      showToast(
        t("js.contacts.groupsManager.groupDeleted") !== "js.contacts.groupsManager.groupDeleted"
          ? t("js.contacts.groupsManager.groupDeleted")
          : "Group deleted.",
        "success"
      );
      await loadGroups();
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.failedToSave"), "error");
    }
  };

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <Spinner />
      </div>
    );
  }

  return (
    <div className={`${PAGE_CONTENT_CLASS} pb-10`}>
      <AppPageHeader
        moduleKey="contact"
        title={
          t("js.contacts.groupsManager.title") !== "js.contacts.groupsManager.title"
            ? t("js.contacts.groupsManager.title")
            : "Group Management"
        }
        subtitle={
          t("js.contacts.groupsManager.subtitle") !== "js.contacts.groupsManager.subtitle"
            ? t("js.contacts.groupsManager.subtitle")
            : "Create and remove groups, then manage group members."
        }
      />

      {error ? (
        <div className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error">{error}</div>
      ) : null}

      <section className="rounded-lg border border-base-300 bg-base-100 p-3">
        <h3 className="text-sm font-semibold mb-2">
          {t("js.contacts.groupsManager.createGroup") !== "js.contacts.groupsManager.createGroup"
            ? t("js.contacts.groupsManager.createGroup")
            : "Create Group"}
        </h3>
        <div className="flex flex-col gap-2 sm:flex-row">
          <input
            type="text"
            value={newGroupName}
            onChange={(e) => setNewGroupName(e.target.value)}
            placeholder={
              t("js.contacts.groupsManager.groupNamePlaceholder") !== "js.contacts.groupsManager.groupNamePlaceholder"
                ? t("js.contacts.groupsManager.groupNamePlaceholder")
                : "Group name"
            }
            className="input input-bordered w-full"
          />
          <ActionButton onClick={createGroup} disabled={creatingGroup}>
            <Plus className="h-4 w-4" />
            {creatingGroup
              ? t("js.common.loading") !== "js.common.loading"
                ? t("js.common.loading")
                : "Loading..."
              : t("js.contacts.groupsManager.addGroup") !== "js.contacts.groupsManager.addGroup"
                ? t("js.contacts.groupsManager.addGroup")
                : "Add Group"}
          </ActionButton>
        </div>
      </section>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <section className="rounded-lg border border-base-300 bg-base-100 p-3">
          <div className="flex items-center justify-between gap-2 mb-2">
            <h3 className="text-sm font-semibold">
              {t("js.contacts.groups") !== "js.contacts.groups" ? t("js.contacts.groups") : "Groups"}
            </h3>
            <ActionButton
              variant="outline-error"
              onClick={() => setDeleteOpen(true)}
              disabled={selectedGroup == null || selectedGroupProtected}
              title={
                selectedGroupProtected
                  ? t("js.contacts.groupsManager.protectedGroupHint") !== "js.contacts.groupsManager.protectedGroupHint"
                    ? t("js.contacts.groupsManager.protectedGroupHint")
                    : "This default group cannot be removed."
                  : undefined
              }
            >
              <Trash2 className="h-4 w-4" />
              {t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
            </ActionButton>
          </div>
          <SearchField value={qGroups} onChange={setQGroups} placeholder={searchPlaceholder} className="mb-2" />
          {groupsLoading ? (
            <div className="flex justify-center py-8">
              <Spinner />
            </div>
          ) : filteredGroups.length < 1 ? (
            <p className="text-sm text-base-content/60">
              {t("js.contacts.integrationNoGroups") !== "js.contacts.integrationNoGroups"
                ? t("js.contacts.integrationNoGroups")
                : "No contact groups available."}
            </p>
          ) : (
            <ul className="space-y-1">
              {filteredGroups.map((group) => {
                const selected = group.id === selectedGroupId;
                const protectedGroup = isProtectedGroup(group);
                return (
                  <li key={group.id}>
                    <button
                      type="button"
                      onClick={() => setSelectedGroupId(group.id)}
                      className={`w-full rounded-lg border px-3 py-2 text-left transition-colors ${
                        selected ? "border-primary bg-primary/10" : "border-base-300 hover:bg-base-200/60"
                      }`}
                    >
                      <span className="flex items-center justify-between gap-3">
                        <span className="truncate font-medium">{group.name}</span>
                        <span className="shrink-0 text-xs text-base-content/60">{group.memberCount ?? 0}</span>
                      </span>
                      {protectedGroup ? (
                        <span className="mt-1 inline-flex rounded-full border border-base-300 px-2 py-0.5 text-xs text-base-content/70">
                          {t("js.contacts.groupsManager.defaultGroup") !== "js.contacts.groupsManager.defaultGroup"
                            ? t("js.contacts.groupsManager.defaultGroup")
                            : "Default group"}
                        </span>
                      ) : null}
                    </button>
                  </li>
                );
              })}
            </ul>
          )}
        </section>

        <section className="rounded-lg border border-base-300 bg-base-100 p-3">
          <div className="flex items-center justify-between gap-2 mb-2">
            <h3 className="text-sm font-semibold">
              {t("js.contacts.groupsManager.groupMembers") !== "js.contacts.groupsManager.groupMembers"
                ? t("js.contacts.groupsManager.groupMembers")
                : "Group Members"}
            </h3>
            <ActionButton
              variant="outline-error"
              onClick={() => void updateMembership([...selectedToRemove], "remove")}
              disabled={savingMembers || selectedToRemove.size < 1 || selectedGroupId == null}
            >
              <Trash2 className="h-4 w-4" />
              {t("js.contacts.groupsManager.removeMembers") !== "js.contacts.groupsManager.removeMembers"
                ? t("js.contacts.groupsManager.removeMembers")
                : "Remove Selected"}
            </ActionButton>
          </div>
          <SearchField value={qMembers} onChange={setQMembers} placeholder={searchPlaceholder} className="mb-2" />
          {membersLoading || contactsLoading ? (
            <div className="flex justify-center py-8">
              <Spinner />
            </div>
          ) : (
            <div className="-mx-3">
              {filteredMembers.length > 0 ? (
                <CheckboxSelectAllRow
                  checked={filteredMembers.every((m) => selectedToRemove.has(m.id))}
                  onChange={(on) =>
                    toggleAllSetValues(
                      setSelectedToRemove,
                      filteredMembers.map((m) => m.id),
                      on
                    )
                  }
                  label={
                    t("js.contacts.integrationSelectAll") !== "js.contacts.integrationSelectAll"
                      ? t("js.contacts.integrationSelectAll")
                      : "Select all"
                  }
                />
              ) : null}
              {filteredMembers.length < 1 ? (
                <p className="text-sm text-base-content/50 py-2 px-3">
                  {t("js.contacts.groupsManager.noMembersInGroup") !== "js.contacts.groupsManager.noMembersInGroup"
                    ? t("js.contacts.groupsManager.noMembersInGroup")
                    : "No members in this group."}
                </p>
              ) : (
                <ul>
                  {filteredMembers.map((m) => {
                    const fullName = [m.name ?? "", m.surname ?? ""].filter(Boolean).join(" ") || emptyText;
                    return (
                      <li key={m.id}>
                        <CheckboxRow
                          checked={selectedToRemove.has(m.id)}
                          onToggle={() => toggleSetValue(setSelectedToRemove, m.id)}
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
          )}
        </section>

        <section className="rounded-lg border border-base-300 bg-base-100 p-3">
          <div className="flex items-center justify-between gap-2 mb-2">
            <h3 className="text-sm font-semibold">
              {t("js.contacts.groupsManager.availableContacts") !== "js.contacts.groupsManager.availableContacts"
                ? t("js.contacts.groupsManager.availableContacts")
                : "Available Contacts"}
            </h3>
            <ActionButton
              onClick={() => void updateMembership([...selectedToAdd], "add")}
              disabled={savingMembers || selectedToAdd.size < 1 || selectedGroupId == null}
            >
              <Users className="h-4 w-4" />
              {t("js.contacts.groupsManager.addMembers") !== "js.contacts.groupsManager.addMembers"
                ? t("js.contacts.groupsManager.addMembers")
                : "Add Selected"}
            </ActionButton>
          </div>
          <SearchField value={qAvailable} onChange={setQAvailable} placeholder={searchPlaceholder} className="mb-2" />
          {membersLoading || contactsLoading ? (
            <div className="flex justify-center py-8">
              <Spinner />
            </div>
          ) : (
            <div className="-mx-3">
              {filteredAvailable.length > 0 ? (
                <CheckboxSelectAllRow
                  checked={filteredAvailable.every((m) => selectedToAdd.has(m.id))}
                  onChange={(on) =>
                    toggleAllSetValues(
                      setSelectedToAdd,
                      filteredAvailable.map((m) => m.id),
                      on
                    )
                  }
                  label={
                    t("js.contacts.integrationSelectAll") !== "js.contacts.integrationSelectAll"
                      ? t("js.contacts.integrationSelectAll")
                      : "Select all"
                  }
                />
              ) : null}
              {filteredAvailable.length < 1 ? (
                <p className="text-sm text-base-content/50 py-2 px-3">
                  {t("js.contacts.groupsManager.noAvailableContacts") !==
                  "js.contacts.groupsManager.noAvailableContacts"
                    ? t("js.contacts.groupsManager.noAvailableContacts")
                    : "No available contacts."}
                </p>
              ) : (
                <ul>
                  {filteredAvailable.map((m) => {
                    const fullName = [m.name ?? "", m.surname ?? ""].filter(Boolean).join(" ") || emptyText;
                    return (
                      <li key={m.id}>
                        <CheckboxRow
                          checked={selectedToAdd.has(m.id)}
                          onToggle={() => toggleSetValue(setSelectedToAdd, m.id)}
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
          )}
        </section>
      </div>

      <ConfirmModal
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        title={
          t("js.contacts.groupsManager.deleteGroupTitle") !== "js.contacts.groupsManager.deleteGroupTitle"
            ? t("js.contacts.groupsManager.deleteGroupTitle")
            : "Remove Group"
        }
        message={
          t("js.contacts.groupsManager.deleteGroupConfirm") !== "js.contacts.groupsManager.deleteGroupConfirm"
            ? t("js.contacts.groupsManager.deleteGroupConfirm")
            : "Remove the selected group?"
        }
        confirmLabel={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
        cancelLabel={t("js.common.cancel") !== "js.common.cancel" ? t("js.common.cancel") : "Cancel"}
        onConfirm={() => void deleteSelectedGroup()}
        variant="danger"
      />
    </div>
  );
}
