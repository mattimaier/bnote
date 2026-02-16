/**
 * BNote Next Generation - Profile (My Contact Data) Page
 *
 * Copyright (C) 2026 BNote Contributors
 */

"use client";

import { usePathname, useRouter } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { useI18n } from "@/contexts/I18nContext";
import { useToast } from "@/contexts/ToastContext";
import { kontaktdatenApi, type MyContactDetail, type InstrumentOption } from "@/lib/kontaktdaten-api";
import { EditingBar } from "@/components/EditingBar";
import { DetailPageHeader, DetailEditButton } from "@/components/DetailPageHeader";
import { DetailCard } from "@/components/DetailCard";
import { SelectPicker } from "@/components/SelectPicker";
import { MarkdownText } from "@/components/MarkdownText";
import { formatDateShortDisplay } from "@/lib/date-time";

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
      setError(err instanceof Error ? err.message : "Failed to load profile");
      showToast(t("js.profile.loadError") !== "js.profile.loadError" ? t("js.profile.loadError") : "Failed to load profile", "error");
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
      showToast(err instanceof Error ? err.message : "Failed to save", "error");
    } finally {
      setSaving(false);
    }
  }

  function handleCancel() {
    router.replace("/profile/");
  }

  if (!ready || loading) {
    return (
      <div className="flex items-center justify-center py-24">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent" />
      </div>
    );
  }

  if (contact === null) {
    return (
      <div className="mx-auto max-w-2xl space-y-4">
        <DetailPageHeader
          title={t("js.profile.title") !== "js.profile.title" ? t("js.profile.title") : "Meine Kontaktdaten"}
        />
        <p className="rounded-lg border px-4 py-3 text-sm" style={{ borderColor: "var(--border)", color: "var(--muted-foreground)" }}>
          {t("js.profile.noContact") !== "js.profile.noContact" ? t("js.profile.noContact") : "Ihrem Benutzer wurde kein Kontakt zugeordnet."}
        </p>
      </div>
    );
  }

  const c = contact as MyContactDetail;
  const birthdayValue = c.birthday && c.birthday !== "0000-00-00" ? String(c.birthday).slice(0, 10) : "";
  const label = (key: string, fallback: string) => (t(key) !== key ? t(key) : fallback);

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {isEditing && (
        <EditingBar
          submitFormId="profile-form"
          saving={saving}
          onCancel={handleCancel}
        />
      )}

      <DetailPageHeader
        title={t("js.profile.title") !== "js.profile.title" ? t("js.profile.title") : "Meine Kontaktdaten"}
        subtitle={t("js.profile.subtitle") !== "js.profile.subtitle" ? t("js.profile.subtitle") : "Persönliche Daten bearbeiten"}
        right={!isEditing ? <DetailEditButton onClick={() => router.push("/profile/edit/")} /> : undefined}
      />

      {error && (
        <div
          className="rounded-lg border px-4 py-3 text-sm"
          style={{ borderColor: "var(--destructive)", background: "color-mix(in oklch, var(--destructive) 15%, transparent)", color: "var(--destructive-foreground)" }}
        >
          {error}
        </div>
      )}

      {!isEditing ? (
        <DetailCard className="space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.firstName", "Vorname")}</span>
              <p className="text-sm mt-1">{c.name || emptyText}</p>
            </div>
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.lastName", "Nachname")}</span>
              <p className="text-sm mt-1">{c.surname || emptyText}</p>
            </div>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.nickname", "Spitzname")}</span>
            <p className="text-sm mt-1">{c.nickname || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.email", "E-Mail")}</span>
            <p className="text-sm mt-1">{c.email || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.birthday", "Geburtstag")}</span>
            <p className="text-sm mt-1">{formatDateShortDisplay(c.birthday, lang)}</p>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.instrument", "Instrument")}</span>
            <p className="text-sm mt-1">{c.instrumentname || emptyText}</p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.phone", "Telefon")}</span>
              <p className="text-sm mt-1">{c.phone || emptyText}</p>
            </div>
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.mobile", "Mobil")}</span>
              <p className="text-sm mt-1">{c.mobile || emptyText}</p>
            </div>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.company", "Firma")}</span>
            <p className="text-sm mt-1">{c.company || emptyText}</p>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.street", "Straße")}</span>
            <p className="text-sm mt-1">{c.street || emptyText}</p>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.zip", "PLZ")}</span>
              <p className="text-sm mt-1">{c.zip || emptyText}</p>
            </div>
            <div>
              <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.city", "Ort")}</span>
              <p className="text-sm mt-1">{c.city || emptyText}</p>
            </div>
          </div>
          <div>
            <span className="text-xs font-medium" style={{ color: "var(--muted-foreground)" }}>{label("js.contacts.notes", "Notizen")}</span>
            <div className="text-sm mt-1 prose prose-sm max-w-none dark:prose-invert">
              {c.notes ? <MarkdownText value={c.notes} /> : <p>{emptyText}</p>}
            </div>
          </div>
        </DetailCard>
      ) : (
        <DetailCard>
      <form id="profile-form" onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.firstName") !== "js.contacts.firstName" ? t("js.contacts.firstName") : "Vorname"}</label>
            <input name="name" type="text" defaultValue={c.name} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.lastName") !== "js.contacts.lastName" ? t("js.contacts.lastName") : "Nachname"}</label>
            <input name="surname" type="text" defaultValue={c.surname} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.nickname") !== "js.contacts.nickname" ? t("js.contacts.nickname") : "Spitzname"}</label>
          <input name="nickname" type="text" defaultValue={c.nickname} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.email") !== "js.contacts.email" ? t("js.contacts.email") : "E-Mail"}</label>
          <input name="email" type="email" defaultValue={c.email} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.birthday") !== "js.contacts.birthday" ? t("js.contacts.birthday") : "Geburtstag"}</label>
          <input name="birthday" type="date" defaultValue={birthdayValue} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
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
            <div className="rounded-md border px-3 py-2 text-sm" style={{ borderColor: "var(--border)", background: "var(--muted)/30", color: "var(--muted-foreground)" }}>
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
            <input name="phone" type="text" defaultValue={c.phone} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.mobile") !== "js.contacts.mobile" ? t("js.contacts.mobile") : "Mobil"}</label>
            <input name="mobile" type="text" defaultValue={c.mobile} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.company") !== "js.contacts.company" ? t("js.contacts.company") : "Firma"}</label>
          <input name="company" type="text" defaultValue={c.company} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.business") !== "js.contacts.business" ? t("js.contacts.business") : "Geschäftlich"}</label>
          <input name="business" type="text" defaultValue={c.business} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.street") !== "js.contacts.street" ? t("js.contacts.street") : "Straße"}</label>
          <input name="street" type="text" defaultValue={c.street} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.zip") !== "js.contacts.zip" ? t("js.contacts.zip") : "PLZ"}</label>
            <input name="zip" type="text" defaultValue={c.zip} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">{t("js.contacts.city") !== "js.contacts.city" ? t("js.contacts.city") : "Ort"}</label>
            <input name="city" type="text" defaultValue={c.city} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">{t("js.contacts.notes") !== "js.contacts.notes" ? t("js.contacts.notes") : "Notizen"}</label>
          <textarea name="notes" rows={3} defaultValue={c.notes} className="input input-sm w-full" style={{ color: "var(--foreground)" }} />
        </div>

        {/* Privacy / share settings */}
        <div className="space-y-3 rounded-lg border p-4" style={{ borderColor: "var(--border)" }}>
          <h3 className="text-sm font-semibold" style={{ color: "var(--foreground)" }}>
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
                <input type="checkbox" name={name} defaultChecked={Boolean((c as unknown as Record<string, unknown>)[name])} className="rounded border-[var(--border)]" />
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
