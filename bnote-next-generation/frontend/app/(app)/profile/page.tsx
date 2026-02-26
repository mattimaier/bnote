/**
 * BNote Next Generation - Profile (My Contact Data) Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { usePathname, useRouter } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { useEditingBar } from "@/contexts/EditingBarContext";
import { kontaktdatenApi, type MyContactDetail, type InstrumentOption } from "@/lib/kontaktdaten-api";
import { DatePicker } from "@/components/DatePicker";
import { DetailPageHeader, DetailEditButton } from "@/components/DetailPageHeader";
import { DetailCard } from "@/components/DetailCard";
import { SelectPicker } from "@/components/SelectPicker";
import { NotesContent } from "@/components/NotesContent";
import { NotesEditor } from "@/components/NotesEditor";
import { formatDateShortDisplay } from "@/lib/date-time";
import { Spinner } from "@/components/Spinner";
import { getErrorMessage } from "@/lib/error-utils";
import { PAGE_CONTENT_BASE_CLASS, PAGE_CONTENT_CLASS } from "@/lib/layout";

export default function ProfilePage() {
  const router = useRouter();
  const pathname = usePathname() ?? "";
  const isEditing = pathname.endsWith("/edit") || pathname.endsWith("/edit/");

  const { t, ready, lang } = useI18n();
  const emptyText = t("js.common.empty") !== "js.common.empty" ? t("js.common.empty") : "";
  const { showToast } = useToast();
  const [contact, setContact] = useState<MyContactDetail | null | undefined>(undefined);
  const [instruments, setInstruments] = useState<InstrumentOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [instrumentId, setInstrumentId] = useState(0);
  const [profileNotes, setProfileNotes] = useState("");
  const [birthday, setBirthday] = useState("");

  const loadData = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const [contactRes, instrumentsRes] = await Promise.all([
        kontaktdatenApi.getMine(),
        kontaktdatenApi.getInstruments().catch(() => []),
      ]);
      setContact(contactRes ?? null);
      setInstruments(instrumentsRes ?? []);
    } catch (err) {
      const msg = getErrorMessage(err, t, "js.profile.loadError");
      setError(msg);
      showToast(msg, "error");
    } finally {
      setLoading(false);
    }
  }, [showToast, t]);

  useEffect(() => {
    if (!ready) return;
    loadData();
  }, [ready, loadData]);

  useEffect(() => {
    if (contact != null && contact !== undefined && typeof (contact as MyContactDetail).instrument === "number") {
      setInstrumentId((contact as MyContactDetail).instrument ?? 0);
    }
  }, [contact]);

  useEffect(() => {
    if (contact != null && contact !== undefined) {
      setProfileNotes((contact as MyContactDetail).notes ?? "");
    }
  }, [contact]);

  useEffect(() => {
    if (contact != null && contact !== undefined) {
      const b = (contact as MyContactDetail).birthday;
      setBirthday(b && b !== "0000-00-00" ? String(b).slice(0, 10) : "");
    }
  }, [contact]);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);
    const data: Record<string, unknown> = {
      name: formData.get("name") ?? "",
      surname: formData.get("surname") ?? "",
      nickname: formData.get("nickname") ?? "",
      email: formData.get("email") ?? "",
      birthday: formData.get("birthday") || null,
      instrument: formData.get("instrument") ? Number(formData.get("instrument")) : 0,
      street: formData.get("street") ?? "",
      zip: formData.get("zip") ?? "",
      city: formData.get("city") ?? "",
      phone: formData.get("phone") ?? "",
      mobile: formData.get("mobile") ?? "",
      company: formData.get("company") ?? "",
      business: formData.get("business") ?? "",
      notes: formData.get("notes") ?? "",
      share_email: formData.get("share_email") === "on",
      share_address: formData.get("share_address") === "on",
      share_phones: formData.get("share_phones") === "on",
      share_birthday: formData.get("share_birthday") === "on",
    };
    setSaving(true);
    try {
      await kontaktdatenApi.updateMine(data);
      router.replace("/profile/");
      loadData();
      showToast(t("js.profile.saved") !== "js.profile.saved" ? t("js.profile.saved") : "Data saved successfully", "success");
    } catch (err) {
      showToast(getErrorMessage(err, t, "js.common.saveFailed"), "error");
    } finally {
      setSaving(false);
    }
  }

  const handleCancel = useCallback(() => {
    router.replace("/profile/");
  }, [router]);

  const { setEditingBar, clearEditingBar } = useEditingBar();
  const onCancelRef = useRef(handleCancel);
  onCancelRef.current = handleCancel;
  const barTokenRef = useRef<number | null>(null);
  useEffect(() => {
    if (!isEditing) {
      barTokenRef.current = null;
      setEditingBar(null);
      return;
    }
    const token = setEditingBar({
      submitFormId: "profile-form",
      saving,
      onCancel: () => onCancelRef.current?.(),
    });
    barTokenRef.current = typeof token === "number" ? token : null;
    return () => {
      if (barTokenRef.current != null) {
        clearEditingBar(barTokenRef.current);
        barTokenRef.current = null;
      }
    };
  }, [isEditing, saving, setEditingBar, clearEditingBar]);

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Spinner />
      </div>
    );
  }

  if (contact === null) {
    return (
      <div className={PAGE_CONTENT_CLASS}>
        <DetailPageHeader
          title={t("js.profile.title") !== "js.profile.title" ? t("js.profile.title") : "My Contact Data"}
        />
        <p className="rounded-box border border-base-300 px-4 py-3 text-sm text-base-content/60">
          {t("js.profile.noContact") !== "js.profile.noContact" ? t("js.profile.noContact") : "Ihrem Benutzer wurde kein Kontakt zugeordnet."}
        </p>
      </div>
    );
  }

  const c = contact as MyContactDetail;
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  return (
    <div className={`${PAGE_CONTENT_BASE_CLASS} space-y-6`}>
      <DetailPageHeader
        title={t("js.profile.title") !== "js.profile.title" ? t("js.profile.title") : "My Contact Data"}
        subtitle={t("js.profile.subtitle") !== "js.profile.subtitle" ? t("js.profile.subtitle") : "Edit your personal data"}
        right={!isEditing ? <DetailEditButton onClick={() => router.push("/profile/edit/")} /> : undefined}
      />

      {error && (
        <div className="rounded-box border border-error bg-error/15 px-4 py-3 text-sm text-error">
          {error}
        </div>
      )}

      {!isEditing ? (
        <DetailCard className="space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <span className="text-xs font-medium text-base-content/60">{label("js.contacts.firstName", "Vorname")}</span>
              <p className="text-sm mt-1">{c.name || emptyText}</p>
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">{label("js.contacts.lastName", "Nachname")}</span>
              <p className="text-sm mt-1">{c.surname || emptyText}</p>
            </div>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.nickname", "Spitzname")}</span>
            <p className="text-sm mt-1">{c.nickname || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.email", "E-Mail")}</span>
            <p className="text-sm mt-1">{c.email || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.birthday", "Geburtstag")}</span>
            <p className="text-sm mt-1">{formatDateShortDisplay(c.birthday, lang)}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.instrument", "Instrument")}</span>
            <p className="text-sm mt-1">{c.instrumentname || emptyText}</p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <span className="text-xs font-medium text-base-content/60">{label("js.contacts.phone", "Telefon")}</span>
              <p className="text-sm mt-1">{c.phone || emptyText}</p>
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">{label("js.contacts.mobile", "Mobil")}</span>
              <p className="text-sm mt-1">{c.mobile || emptyText}</p>
            </div>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.company", "Firma")}</span>
            <p className="text-sm mt-1">{c.company || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.street", "Straße")}</span>
            <p className="text-sm mt-1">{c.street || emptyText}</p>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <span className="text-xs font-medium text-base-content/60">{label("js.contacts.zip", "PLZ")}</span>
              <p className="text-sm mt-1">{c.zip || emptyText}</p>
            </div>
            <div>
              <span className="text-xs font-medium text-base-content/60">{label("js.contacts.city", "Ort")}</span>
              <p className="text-sm mt-1">{c.city || emptyText}</p>
            </div>
          </div>
          <div>
            <span className="text-xs font-medium text-base-content/60">{label("js.contacts.notes", "Notizen")}</span>
            <div className="text-sm mt-1 prose prose-sm max-w-none dark:prose-invert">
              {c.notes ? <NotesContent value={c.notes} /> : <p>{emptyText}</p>}
            </div>
          </div>
        </DetailCard>
      ) : (
        <DetailCard>
      <form id="profile-form" onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.firstName") !== "js.contacts.firstName" ? t("js.contacts.firstName") : "Vorname"}</label>
            <input name="name" type="text" defaultValue={c.name} className="input input-sm w-full text-base-content" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.lastName") !== "js.contacts.lastName" ? t("js.contacts.lastName") : "Nachname"}</label>
            <input name="surname" type="text" defaultValue={c.surname} className="input input-sm w-full text-base-content" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.nickname") !== "js.contacts.nickname" ? t("js.contacts.nickname") : "Spitzname"}</label>
          <input name="nickname" type="text" defaultValue={c.nickname} className="input input-sm w-full text-base-content" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.email") !== "js.contacts.email" ? t("js.contacts.email") : "E-Mail"}</label>
          <input name="email" type="email" defaultValue={c.email} className="input input-sm w-full text-base-content" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.birthday") !== "js.contacts.birthday" ? t("js.contacts.birthday") : "Geburtstag"}</label>
          <input type="hidden" name="birthday" value={birthday} />
          <DatePicker
            value={birthday}
            onChange={setBirthday}
            mode="date"
            locale={lang}
            className="input input-sm w-full text-base-content"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{label("js.contacts.instrument", "Instrument")}</label>
          {instruments.length > 0 ? (
            <>
              <input type="hidden" name="instrument" value={instrumentId} />
              <SelectPicker
                options={[
                  { id: 0, name: t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Auswählen…" },
                  ...instruments,
                ]}
                value={instrumentId}
                onChange={setInstrumentId}
                emptyLabel={emptyText}
                labelSelect={t("js.common.select") !== "js.common.select" ? t("js.common.select") : "Auswählen…"}
                labelNoMatches={t("js.common.noMatches") !== "js.common.noMatches" ? t("js.common.noMatches") : "Keine Treffer"}
                labelClose={t("js.common.close") !== "js.common.close" ? t("js.common.close") : "Schließen"}
              />
            </>
          ) : (
            <div className="rounded-field border border-base-300 bg-base-200/50 px-3 py-2 text-sm text-base-content/60">
              {c.instrumentname || emptyText}
              <p className="text-xs mt-1">
                {t("js.profile.instrumentsEmpty") !== "js.profile.instrumentsEmpty"
                  ? t("js.profile.instrumentsEmpty")
                  : "Keine Instrumente hinterlegt. Ein Administrator kann Instrumente in der Konfiguration anlegen."}
              </p>
            </div>
          )}
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.phone") !== "js.contacts.phone" ? t("js.contacts.phone") : "Telefon"}</label>
            <input name="phone" type="text" defaultValue={c.phone} className="input input-sm w-full text-base-content" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.mobile") !== "js.contacts.mobile" ? t("js.contacts.mobile") : "Mobil"}</label>
            <input name="mobile" type="text" defaultValue={c.mobile} className="input input-sm w-full text-base-content" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.company") !== "js.contacts.company" ? t("js.contacts.company") : "Firma"}</label>
          <input name="company" type="text" defaultValue={c.company} className="input input-sm w-full text-base-content" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.business") !== "js.contacts.business" ? t("js.contacts.business") : "Geschäftlich"}</label>
          <input name="business" type="text" defaultValue={c.business} className="input input-sm w-full text-base-content" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.street") !== "js.contacts.street" ? t("js.contacts.street") : "Straße"}</label>
          <input name="street" type="text" defaultValue={c.street} className="input input-sm w-full text-base-content" />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.zip") !== "js.contacts.zip" ? t("js.contacts.zip") : "PLZ"}</label>
            <input name="zip" type="text" defaultValue={c.zip} className="input input-sm w-full text-base-content" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.city") !== "js.contacts.city" ? t("js.contacts.city") : "Ort"}</label>
            <input name="city" type="text" defaultValue={c.city} className="input input-sm w-full text-base-content" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.notes") !== "js.contacts.notes" ? t("js.contacts.notes") : "Notizen"}</label>
          <input type="hidden" name="notes" value={profileNotes} />
          <NotesEditor
            value={profileNotes}
            onChange={setProfileNotes}
            placeholder={t("js.contacts.notes") !== "js.contacts.notes" ? t("js.contacts.notes") : "Notizen"}
            id="profile-notes-editor"
          />
        </div>

        {/* Privacy / share settings */}
        <div className="space-y-3 rounded-box border border-base-300 p-4">
          <h3 className="text-sm font-semibold text-base-content">
            {t("js.profile.privacyTitle") !== "js.profile.privacyTitle" ? t("js.profile.privacyTitle") : "Sichtbarkeit"}
          </h3>
          <div className="space-y-2">
            {[
              { name: "share_email", key: "js.profile.shareEmail", fallback: "E-Mail mit anderen Mitgliedern teilen" },
              { name: "share_address", key: "js.profile.shareAddress", fallback: "Adresse mit anderen Mitgliedern teilen" },
              { name: "share_phones", key: "js.profile.sharePhones", fallback: "Telefonnummern mit anderen Mitgliedern teilen" },
              { name: "share_birthday", key: "js.profile.shareBirthday", fallback: "Geburtstag mit anderen Mitgliedern teilen" },
            ].map(({ name, key, fallback }) => (
              <label key={name} className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name={name} defaultChecked={Boolean((c as unknown as Record<string, unknown>)[name])} className="checkbox checkbox-primary checkbox-sm" />
                <span className="text-sm">{t(key) !== key ? t(key) : fallback}</span>
              </label>
            ))}
          </div>
        </div>
      </form>
        </DetailCard>
      )}
    </div>
  );
}
