/**
 * BNote Next Generation - Contact edit/create form
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useEntityParams } from "@/lib/entities/use-entity-params";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { contactsApi, type ContactDetail, type ContactGroup } from "@/lib/contacts-api";
import { kontaktdatenApi, type InstrumentOption } from "@/lib/kontaktdaten-api";
import { getEntityPath } from "@/lib/entities/paths";
import { EditingBar } from "@/components/EditingBar";
import { DetailDeleteSection } from "@/components/DetailDeleteSection";
import { SelectPicker } from "@/components/SelectPicker";
import { MultiSelect } from "@/components/entities/event/MultiSelect";

export function ContactEdit() {
  const { id } = useEntityParams();
  const router = useRouter();
  const { t, ready } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const isNew = id === "new";

  const [groups, setGroups] = useState<ContactGroup[]>([]);
  const [instruments, setInstruments] = useState<InstrumentOption[]>([]);

  const [name, setName] = useState("");
  const [surname, setSurname] = useState("");
  const [nickname, setNickname] = useState("");
  const [email, setEmail] = useState("");
  const [birthday, setBirthday] = useState("");
  const [instrumentId, setInstrumentId] = useState(0);
  const [instrumentName, setInstrumentName] = useState("");
  const [phone, setPhone] = useState("");
  const [mobile, setMobile] = useState("");
  const [company, setCompany] = useState("");
  const [business, setBusiness] = useState("");
  const [web, setWeb] = useState("");
  const [street, setStreet] = useState("");
  const [zip, setZip] = useState("");
  const [city, setCity] = useState("");
  const [notes, setNotes] = useState("");
  const [selectedGroups, setSelectedGroups] = useState<number[]>([]);
  const [shareEmail, setShareEmail] = useState(false);
  const [shareAddress, setShareAddress] = useState(false);
  const [sharePhones, setSharePhones] = useState(false);
  const [shareBirthday, setShareBirthday] = useState(false);
  const [isConductor, setIsConductor] = useState(false);

  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const loadGroups = useCallback(() => {
    contactsApi
      .getGroups()
      .then((list) => setGroups(list ?? []))
      .catch(() => setGroups([]));
  }, []);

  const loadInstruments = useCallback(() => {
    kontaktdatenApi
      .getInstruments()
      .then((list) => setInstruments(list ?? []))
      .catch(() => setInstruments([]));
  }, []);

  const loadContact = useCallback(() => {
    if (isNew || !id) return;
    const numId = parseInt(id, 10);
    if (Number.isNaN(numId)) {
      setLoading(false);
      return;
    }
    setLoading(true);
    contactsApi
      .get(numId)
      .then((contact: ContactDetail) => {
        setName(contact.name ?? "");
        setSurname(contact.surname ?? "");
        setNickname(contact.nickname ?? "");
        setEmail(contact.email ?? "");
        setBirthday(contact.birthday && contact.birthday !== "0000-00-00" ? String(contact.birthday).slice(0, 10) : "");
        setInstrumentId(contact.instrument ?? 0);
        setInstrumentName(contact.instrumentname ?? "");
        setPhone(contact.phone ?? "");
        setMobile(contact.mobile ?? "");
        setCompany(contact.company ?? "");
        setBusiness(contact.business ?? "");
        setWeb(contact.web ?? "");
        setStreet(contact.street ?? "");
        setZip(contact.zip ?? "");
        setCity(contact.city ?? "");
        setNotes(contact.notes ?? "");
        setSelectedGroups(Array.isArray(contact.groups) ? contact.groups : []);
        setShareEmail(Boolean(contact.share_email));
        setShareAddress(Boolean(contact.share_address));
        setSharePhones(Boolean(contact.share_phones));
        setShareBirthday(Boolean(contact.share_birthday));
        setIsConductor(Boolean(contact.is_conductor));
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Failed to load"))
      .finally(() => setLoading(false));
  }, [id, isNew]);

  useEffect(() => {
    if (!ready) return;
    loadGroups();
    loadInstruments();
    loadContact();
  }, [ready, loadGroups, loadInstruments, loadContact]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      const payload = {
        name,
        surname,
        nickname,
        email,
        birthday: birthday || null,
        instrument: instrumentId,
        phone,
        mobile,
        company,
        business,
        web,
        street,
        zip,
        city,
        notes,
        groups: selectedGroups,
        share_email: shareEmail,
        share_address: shareAddress,
        share_phones: sharePhones,
        share_birthday: shareBirthday,
        is_conductor: isConductor,
      } as Partial<ContactDetail> & { groups?: number[] };

      if (isNew) {
        const res = await contactsApi.create(payload);
        showToast(
          t("js.contacts.contactCreated") !== "js.contacts.contactCreated"
            ? t("js.contacts.contactCreated")
            : "Contact created",
          "success"
        );
        router.replace(getEntityPath("contact", res.id, "view"));
      } else {
        await contactsApi.update(parseInt(id, 10), payload);
        showToast(
          t("js.common.saved") !== "js.common.saved" ? t("js.common.saved") : "Saved",
          "success"
        );
        router.replace(getEntityPath("contact", id, "view"));
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : "Save failed";
      setError(message);
      showToast(message, "error");
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    if (isNew) router.push("/contacts");
    else router.push(getEntityPath("contact", id, "view"));
  };

  const handleDelete = async () => {
    if (isNew) return;
    try {
      await contactsApi.delete(parseInt(id, 10));
      showToast(
        t("js.common.deleted") !== "js.common.deleted" ? t("js.common.deleted") : "Deleted",
        "success"
      );
      router.replace("/contacts");
    } catch (err) {
      showToast(err instanceof Error ? err.message : "Delete failed", "error");
    }
  };

  const groupOptions = useMemo(() => groups ?? [], [groups]);

  if (!ready) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (!isNew && loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    );
  }

  if (!isNew && error && !name && !surname) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
        <p className="text-sm text-error">{error}</p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl space-y-4 p-4 md:space-y-6 md:p-6">
      <EditingBar
        isNew={isNew}
        saving={saving}
        onCancel={handleCancel}
        submitFormId="contact-edit-form"
      />

      <form id="contact-edit-form" onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div
            className="rounded-lg border px-4 py-3 text-sm"
            className="border border-error bg-error/15 text-error"
          >
            {error}
          </div>
        )}

        <div
          className="rounded-none border-0 shadow-none p-4 md:rounded-box md:border md:border-base-300 md:shadow-sm md:p-6 bg-base-100 md:bg-base-100 text-base-content"
        >
          <div className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  {t("js.contacts.firstName")}
                </label>
                <input
                  name="name"
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="input input-sm w-full"
                  className="text-base-content"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">
                  {t("js.contacts.lastName")}
                </label>
                <input
                  name="surname"
                  type="text"
                  value={surname}
                  onChange={(e) => setSurname(e.target.value)}
                  className="input input-sm w-full"
                  className="text-base-content"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.nickname")}
              </label>
              <input
                name="nickname"
                type="text"
                value={nickname}
                onChange={(e) => setNickname(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.email")}
              </label>
              <input
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.birthday") !== "js.contacts.birthday"
                  ? t("js.contacts.birthday")
                  : "Birthday"}
              </label>
              <input
                name="birthday"
                type="date"
                value={birthday}
                onChange={(e) => setBirthday(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.instrument") !== "js.contacts.instrument"
                  ? t("js.contacts.instrument")
                  : "Instrument"}
              </label>
              {instruments.length > 0 ? (
                <SelectPicker
                  options={[
                    {
                      id: 0,
                      name:
                        t("js.common.select") !== "js.common.select"
                          ? t("js.common.select")
                          : "Auswählen…",
                    },
                    ...instruments,
                  ]}
                  value={instrumentId}
                  onChange={setInstrumentId}
                  emptyLabel={emptyText}
                  labelSelect={
                    t("js.common.select") !== "js.common.select"
                      ? t("js.common.select")
                      : "Auswählen…"
                  }
                  labelNoMatches={
                    t("js.common.noMatches") !== "js.common.noMatches"
                      ? t("js.common.noMatches")
                      : "Keine Treffer"
                  }
                  labelClose={
                    t("js.common.close") !== "js.common.close"
                      ? t("js.common.close")
                      : "Schließen"
                  }
                />
              ) : (
                <div
                  className="rounded-md border px-3 py-2 text-sm"
                  className="border border-base-300 bg-base-200/50 text-base-content/60"
                >
                  {instrumentName || emptyText}
                  <p className="text-xs mt-1">
                    {t("js.profile.instrumentsEmpty") !==
                    "js.profile.instrumentsEmpty"
                      ? t("js.profile.instrumentsEmpty")
                      : "Keine Instrumente hinterlegt. Ein Administrator kann Instrumente in der Konfiguration anlegen."}
                  </p>
                </div>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  {t("js.contacts.phone")}
                </label>
                <input
                  name="phone"
                  type="text"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="input input-sm w-full"
                  className="text-base-content"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">
                  {t("js.contacts.mobile")}
                </label>
                <input
                  name="mobile"
                  type="text"
                  value={mobile}
                  onChange={(e) => setMobile(e.target.value)}
                  className="input input-sm w-full"
                  className="text-base-content"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.company") !== "js.contacts.company"
                  ? t("js.contacts.company")
                  : "Company"}
              </label>
              <input
                name="company"
                type="text"
                value={company}
                onChange={(e) => setCompany(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.business") !== "js.contacts.business"
                  ? t("js.contacts.business")
                  : "Business"}
              </label>
              <input
                name="business"
                type="text"
                value={business}
                onChange={(e) => setBusiness(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.web") !== "js.contacts.web"
                  ? t("js.contacts.web")
                  : "Website"}
              </label>
              <input
                name="web"
                type="text"
                value={web}
                onChange={(e) => setWeb(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.street")}
              </label>
              <input
                name="street"
                type="text"
                value={street}
                onChange={(e) => setStreet(e.target.value)}
                className="input input-sm w-full"
                className="text-base-content"
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  {t("js.contacts.zip")}
                </label>
                <input
                  name="zip"
                  type="text"
                  value={zip}
                  onChange={(e) => setZip(e.target.value)}
                  className="input input-sm w-full"
                  className="text-base-content"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">
                  {t("js.contacts.city")}
                </label>
                <input
                  name="city"
                  type="text"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  className="input input-sm w-full"
                  className="text-base-content"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                {t("js.contacts.notes")}
              </label>
              <textarea
                name="notes"
                rows={3}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="textarea textarea-sm w-full"
              />
            </div>

            <div className="space-y-3 rounded-none border-0 p-4 md:rounded-box md:border md:border-base-300 md:p-4 bg-base-100 md:bg-transparent">
              <h3 className="text-sm font-semibold text-base-content">
                {t("js.profile.privacyTitle") !== "js.profile.privacyTitle"
                  ? t("js.profile.privacyTitle")
                  : "Visibility"}
              </h3>
              <div className="space-y-2">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    name="share_email"
                    checked={shareEmail}
                    onChange={(e) => setShareEmail(e.target.checked)}
                    className="checkbox checkbox-primary checkbox-sm"
                  />
                  <span className="text-sm">
                    {t("js.profile.shareEmail") !== "js.profile.shareEmail"
                      ? t("js.profile.shareEmail")
                      : "E-Mail mit anderen Mitgliedern teilen"}
                  </span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    name="share_address"
                    checked={shareAddress}
                    onChange={(e) => setShareAddress(e.target.checked)}
                    className="checkbox checkbox-primary checkbox-sm"
                  />
                  <span className="text-sm">
                    {t("js.profile.shareAddress") !== "js.profile.shareAddress"
                      ? t("js.profile.shareAddress")
                      : "Adresse mit anderen Mitgliedern teilen"}
                  </span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    name="share_phones"
                    checked={sharePhones}
                    onChange={(e) => setSharePhones(e.target.checked)}
                    className="checkbox checkbox-primary checkbox-sm"
                  />
                  <span className="text-sm">
                    {t("js.profile.sharePhones") !== "js.profile.sharePhones"
                      ? t("js.profile.sharePhones")
                      : "Telefonnummern mit anderen Mitgliedern teilen"}
                  </span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    name="share_birthday"
                    checked={shareBirthday}
                    onChange={(e) => setShareBirthday(e.target.checked)}
                    className="checkbox checkbox-primary checkbox-sm"
                  />
                  <span className="text-sm">
                    {t("js.profile.shareBirthday") !== "js.profile.shareBirthday"
                      ? t("js.profile.shareBirthday")
                      : "Geburtstag mit anderen Mitgliedern teilen"}
                  </span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    name="is_conductor"
                    checked={isConductor}
                    onChange={(e) => setIsConductor(e.target.checked)}
                    className="checkbox checkbox-primary checkbox-sm"
                  />
                  <span className="text-sm">
                    {t("js.contacts.isConductor") !== "js.contacts.isConductor"
                      ? t("js.contacts.isConductor")
                      : "Conductor"}
                  </span>
                </label>
              </div>
            </div>

            {groupOptions.length > 0 && (
              <div>
                <label className="block text-sm font-medium mb-2">
                  {t("js.contacts.groups")}
                </label>
                <MultiSelect
                  options={groupOptions.map((g) => ({ id: g.id, name: g.name }))}
                  selected={selectedGroups}
                  onChange={setSelectedGroups}
                  placeholder={t("js.common.search") !== "js.common.search" ? t("js.common.search") : "Search…"}
                  showChips
                  labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Select…"}
                  labelSelectedCount={(count) => {
                    const template = t("js.common.selectedCount");
                    if (template && template !== "js.common.selectedCount") {
                      return template.replace("{count}", String(count));
                    }
                    return `${count} selected`;
                  }}
                  labelNoSelection={t("js.common.noSelection") !== "js.common.noSelection" ? t("js.common.noSelection") : "No selection"}
                  labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "No matches"}
                  labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Close"}
                  labelRemove={t("js.common.remove") !== "js.common.remove" ? t("js.common.remove") : "Remove"}
                />
              </div>
            )}
          </div>
        </div>
      </form>

      <DetailDeleteSection
        canDelete={!isNew}
        onDelete={handleDelete}
        entityTitle={`${name} ${surname}`.trim() || undefined}
      />
    </div>
  );
}
