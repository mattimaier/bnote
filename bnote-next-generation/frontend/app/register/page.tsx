/**
 * BNote Next Generation - Public registration (/register/)
 */

"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Suspense, useEffect, useMemo, useState } from "react";
import { checkSession } from "@/lib/auth";
import { api } from "@/lib/api";
import { I18nProvider, useI18n } from "@/contexts/I18nContext";
import { ThemeToggle } from "@/components/ThemeToggle";
import { BNoteLogo } from "@/components/BNoteLogo";
import { Spinner } from "@/components/Spinner";
import { SelectPicker } from "@/components/SelectPicker";
import { DatePicker } from "@/components/DatePicker";
import { PasswordStrengthField } from "@/components/auth/PasswordStrengthField";
import { LegalFooter } from "@/components/auth/LegalFooter";
import { translateRegisterApiError } from "@/lib/register-errors";
import {
  registerApiEmailValid,
  registerBirthdayValid,
  registerCityValid,
  registerNameValid,
  registerPasswordValid,
  registerPhoneValid,
  registerStreetValid,
  registerZipValid,
} from "@/lib/register-field-validation";

interface RegistrationOptions {
  instruments: Array<{ id: number; name: string }>;
  countries: Array<{ code: string; label: string; name: string }>;
  defaultCountry: string;
  autoUserActivation: boolean;
}

interface RegisterSuccess {
  userId: number;
  mailOk: boolean;
  autoUserActivation: boolean;
  nextStep: string;
}

/**
 * Plain WHATWG autocomplete tokens so password managers match identity fields reliably.
 * Order: name → email → phone → street → zip → city → country → birthday → …
 * (Birthday after address so “First, Last, Email, Phone, Address…” vaults don’t shift by one slot.)
 */
const REG_AC = {
  givenName: "given-name",
  familyName: "family-name",
  email: "email",
  tel: "tel",
  street: "street-address",
  zip: "postal-code",
  city: "address-level2",
} as const;

function RegisterFormInner() {
  const router = useRouter();
  const { t, ready, lang } = useI18n();
  const [options, setOptions] = useState<RegistrationOptions | null>(null);
  const [loadError, setLoadError] = useState("");
  const [name, setName] = useState("");
  const [surname, setSurname] = useState("");
  const [birthday, setBirthday] = useState("");
  const [instrumentId, setInstrumentId] = useState(0);
  const [countryRowId, setCountryRowId] = useState(0);
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [street, setStreet] = useState("");
  const [zip, setZip] = useState("");
  const [city, setCity] = useState("");
  const [pw1, setPw1] = useState("");
  const [pw2, setPw2] = useState("");
  const [terms, setTerms] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState<RegisterSuccess | null>(null);
  const [emailSyntaxWarning, setEmailSyntaxWarning] = useState(false);

  useEffect(() => {
    const v = email.trim();
    if (!v) {
      setEmailSyntaxWarning(false);
      return;
    }
    const tmr = window.setTimeout(() => {
      setEmailSyntaxWarning(!registerApiEmailValid(email));
    }, 400);
    return () => window.clearTimeout(tmr);
  }, [email]);

  useEffect(() => {
    checkSession().then((session) => {
      if (session.authenticated) {
        router.replace("/dashboard");
      }
    });
  }, [router]);

  useEffect(() => {
    if (!ready) return;
    let cancelled = false;
    api
      .get<RegistrationOptions>("auth", "getRegistrationOptions")
      .then((data) => {
        if (cancelled || !data) return;
        setOptions(data);
        const dc = data.defaultCountry || "";
        if (dc && data.countries?.length) {
          const idx = data.countries.findIndex((c) => c.code === dc);
          if (idx >= 0) {
            setCountryRowId(idx + 1);
          }
        }
      })
      .catch((err: Error & { status?: number }) => {
        if (cancelled) return;
        const msg = err?.message ?? "";
        if (err.status === 403 || msg === "register_deactivated") {
          setLoadError(t("js.register.deactivated"));
        } else {
          setLoadError(translateRegisterApiError(msg, t));
        }
      });
    return () => {
      cancelled = true;
    };
  }, [ready, t]);

  const canSubmit = useMemo(() => {
    if (!name.trim()) return false;
    if (!registerNameValid(name)) return false;
    if (!surname.trim()) return false;
    if (!registerNameValid(surname)) return false;
    if (!email.trim()) return false;
    if (!registerApiEmailValid(email)) return false;
    if (!street.trim()) return false;
    if (!registerStreetValid(street)) return false;
    if (!zip.trim()) return false;
    if (!registerZipValid(zip)) return false;
    if (!city.trim()) return false;
    if (!registerCityValid(city)) return false;
    if (!registerBirthdayValid(birthday)) return false;
    if (!registerPhoneValid(phone)) return false;
    if (instrumentId < 1) return false;
    if (countryRowId < 1) return false;
    if (!terms) return false;
    if (pw1.length < 6) return false;
    if (!registerPasswordValid(pw1)) return false;
    if (pw1 !== pw2) return false;
    return true;
  }, [name, surname, email, street, zip, city, birthday, phone, instrumentId, countryRowId, terms, pw1, pw2]);

  function validateClient(): boolean {
    const fe: Record<string, string> = {};
    if (!name.trim()) fe.name = t("js.register.error.fieldRequired");
    else if (!registerNameValid(name)) fe.name = t("js.register.error.nameInvalid");
    if (!surname.trim()) fe.surname = t("js.register.error.fieldRequired");
    else if (!registerNameValid(surname)) fe.surname = t("js.register.error.nameInvalid");
    if (!email.trim()) fe.email = t("js.register.error.fieldRequired");
    else if (!registerApiEmailValid(email)) fe.email = t("js.register.error.emailInvalid");
    if (!street.trim()) fe.street = t("js.register.error.fieldRequired");
    else if (!registerStreetValid(street)) fe.street = t("js.register.error.streetInvalid");
    if (!zip.trim()) fe.zip = t("js.register.error.fieldRequired");
    else if (!registerZipValid(zip)) fe.zip = t("js.register.error.zipFormat");
    if (!city.trim()) fe.city = t("js.register.error.fieldRequired");
    else if (!registerCityValid(city)) fe.city = t("js.register.error.cityInvalid");
    if (!registerBirthdayValid(birthday)) fe.birthday = t("js.register.error.birthdayInvalid");
    if (!registerPhoneValid(phone)) fe.phone = t("js.register.error.phoneInvalid");
    if (instrumentId < 1) fe.instrument = t("js.register.error.fieldRequired");
    if (countryRowId < 1) fe.country = t("js.register.error.fieldRequired");
    if (!terms) fe.terms = t("js.register.error.register_terms_required");
    if (pw1.length < 6) fe.pw1 = t("js.register.error.passwordTooShort");
    else if (!registerPasswordValid(pw1)) fe.pw1 = t("js.register.error.passwordInvalid");
    if (pw1 !== pw2) fe.pw2 = t("js.register.error.register_password_mismatch");
    setFieldErrors(fe);
    return Object.keys(fe).length === 0;
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitError("");
    if (!validateClient()) return;
    const countryCode = options && countryRowId > 0 ? (options.countries[countryRowId - 1]?.code ?? "") : "";
    setSubmitting(true);
    try {
      const data = await api.post<RegisterSuccess>("auth", "register", {
        name: name.trim(),
        surname: surname.trim(),
        nickname: "",
        birthday: birthday.trim(),
        email: email.trim(),
        phone: phone.trim(),
        mobile: "",
        street: street.trim(),
        zip: zip.trim(),
        city: city.trim(),
        country: countryCode,
        instrument: instrumentId,
        pw1,
        pw2,
        terms: true,
      });
      setSuccess(data);
    } catch (err) {
      const e = err as Error & { status?: number };
      if (e.status === 429) {
        setSubmitError(t("js.register.error.register_rate_limited"));
      } else {
        setSubmitError(translateRegisterApiError(e.message || "register_failed", t));
      }
    } finally {
      setSubmitting(false);
    }
  }

  const req = (label: string) => (
    <>
      {label} <span className="text-error">*</span>
    </>
  );

  if (!ready) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
        <Spinner />
      </div>
    );
  }

  if (loadError) {
    return (
      <div className="w-full max-w-md pb-6 sm:mx-4 sm:pb-0">
        <div className="fixed top-3 right-3 z-50 sm:top-4 sm:right-4">
          <ThemeToggle />
        </div>
        <div className="login-form rounded-xl bg-base-200 p-6 sm:p-8">
          <p className="text-center text-error">{loadError}</p>
          <p className="mt-4 text-center">
            <Link href="/login/" className="link link-primary">
              {t("js.register.backToLogin")}
            </Link>
          </p>
        </div>
        <LegalFooter />
      </div>
    );
  }

  if (!options) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
        <Spinner />
      </div>
    );
  }

  if (success) {
    let message = t("js.register.successGeneric");
    if (success.nextStep === "confirm_email") {
      message = t("js.register.successConfirmEmail");
    } else if (success.nextStep === "wait_admin") {
      message = t("js.register.successWaitAdmin");
    } else if (success.nextStep === "mail_failed") {
      message = t("js.register.successMailFailed");
    }
    return (
      <div className="w-full max-w-md pb-6 sm:mx-4 sm:pb-0">
        <div className="fixed top-3 right-3 z-50 sm:top-4 sm:right-4">
          <ThemeToggle />
        </div>
        <div className="login-form rounded-xl bg-base-200 p-6 sm:p-8">
          <h1 className="mb-4 text-xl font-bold text-base-content">{t("js.register.successTitle")}</h1>
          <p className="text-base-content/85">{message}</p>
          <Link href="/login/" className="btn btn-primary btn-block mt-6">
            {t("js.register.backToLogin")}
          </Link>
        </div>
        <LegalFooter />
      </div>
    );
  }

  const instrumentOptions = [
    { id: 0, name: t("js.common.select") },
    ...options.instruments.map((i) => ({ id: i.id, name: i.name })),
  ];

  const countryOptions = [
    { id: 0, name: t("js.common.select") },
    ...options.countries.map((c, idx) => ({ id: idx + 1, name: c.label })),
  ];

  const pwdInputs = [email, name, surname].filter(Boolean);
  const emailIssue = fieldErrors.email || (emailSyntaxWarning ? t("js.register.error.emailInvalid") : "");

  return (
    <div className="w-full max-w-2xl pb-24 sm:mx-4 sm:pb-0">
      <div className="fixed top-3 right-3 z-50 sm:top-4 sm:right-4">
        <ThemeToggle />
      </div>
      <div className="construction-tape fixed right-0 bottom-0 left-0 z-40 sm:static">
        <div className="construction-tape-text">
          <span className="font-bold">BNote Next Generation</span>
          <span>Under Construction</span>
        </div>
      </div>

      <div className="login-form rounded-xl bg-base-200 p-4 shadow-none sm:relative sm:rounded-b-lg sm:bg-base-200 sm:p-8 sm:shadow-lg">
        <div className="mb-6 text-center sm:mb-8">
          <div className="mx-auto mb-4 flex justify-center">
            <BNoteLogo size="lg" padding="tight" />
          </div>
          <h1 className="mb-2 text-2xl font-bold text-base-content">{t("js.register.title")}</h1>
          <p className="text-sm text-base-content/60">{t("js.register.subtitle")}</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="reg-name" className="mb-1 block text-sm font-medium">
                {req(t("js.contacts.firstName"))}
              </label>
              <input
                id="reg-name"
                name="name"
                autoComplete={REG_AC.givenName}
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="input input-md w-full"
              />
              {fieldErrors.name ? <p className="mt-1 text-xs text-error">{fieldErrors.name}</p> : null}
            </div>
            <div>
              <label htmlFor="reg-surname" className="mb-1 block text-sm font-medium">
                {req(t("js.contacts.lastName"))}
              </label>
              <input
                id="reg-surname"
                name="surname"
                autoComplete={REG_AC.familyName}
                value={surname}
                onChange={(e) => setSurname(e.target.value)}
                className="input input-md w-full"
              />
              {fieldErrors.surname ? <p className="mt-1 text-xs text-error">{fieldErrors.surname}</p> : null}
            </div>
          </div>

          <div>
            <label htmlFor="reg-email" className="mb-1 block text-sm font-medium">
              {req(t("js.contacts.email"))}
            </label>
            <input
              id="reg-email"
              name="email"
              type="email"
              autoComplete={REG_AC.email}
              value={email}
              onChange={(e) => {
                setEmail(e.target.value);
                setFieldErrors((prev) => {
                  if (!prev.email) return prev;
                  const next = { ...prev };
                  delete next.email;
                  return next;
                });
              }}
              className={`input input-md w-full${emailIssue ? " input-error" : ""}`}
              aria-invalid={emailIssue ? true : undefined}
            />
            {emailIssue ? <p className="mt-1 text-xs text-error">{emailIssue}</p> : null}
          </div>

          <div>
            <label htmlFor="reg-phone" className="mb-1 block text-sm font-medium">
              {t("js.contacts.phone")}
            </label>
            <input
              id="reg-phone"
              name="phone"
              type="tel"
              autoComplete={REG_AC.tel}
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              className={`input input-md w-full${fieldErrors.phone ? " input-error" : ""}`}
            />
            {fieldErrors.phone ? <p className="mt-1 text-xs text-error">{fieldErrors.phone}</p> : null}
          </div>

          <div>
            <label htmlFor="reg-street" className="mb-1 block text-sm font-medium">
              {req(t("js.contacts.street"))}
            </label>
            <input
              id="reg-street"
              name="street"
              autoComplete={REG_AC.street}
              value={street}
              onChange={(e) => setStreet(e.target.value)}
              className="input input-md w-full"
            />
            {fieldErrors.street ? <p className="mt-1 text-xs text-error">{fieldErrors.street}</p> : null}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="reg-zip" className="mb-1 block text-sm font-medium">
                {req(t("js.contacts.zip"))}
              </label>
              <input
                id="reg-zip"
                name="zip"
                autoComplete={REG_AC.zip}
                value={zip}
                onChange={(e) => setZip(e.target.value)}
                className="input input-md w-full"
              />
              {fieldErrors.zip ? <p className="mt-1 text-xs text-error">{fieldErrors.zip}</p> : null}
            </div>
            <div>
              <label htmlFor="reg-city" className="mb-1 block text-sm font-medium">
                {req(t("js.contacts.city"))}
              </label>
              <input
                id="reg-city"
                name="city"
                autoComplete={REG_AC.city}
                value={city}
                onChange={(e) => setCity(e.target.value)}
                className="input input-md w-full"
              />
              {fieldErrors.city ? <p className="mt-1 text-xs text-error">{fieldErrors.city}</p> : null}
            </div>
          </div>

          <div className="w-full min-w-0 [&_div.relative]:max-w-none">
            <label className="mb-1 block text-sm font-medium">{req(t("js.locations.country"))}</label>
            <SelectPicker
              options={countryOptions}
              value={countryRowId}
              onChange={setCountryRowId}
              labelSelect={t("js.common.select")}
              labelNoMatches={t("js.common.noMatches")}
              labelClose={t("js.common.close")}
            />
            {fieldErrors.country ? <p className="mt-1 text-xs text-error">{fieldErrors.country}</p> : null}
          </div>

          <div>
            <label htmlFor="reg-birthday" className="mb-1 block text-sm font-medium">
              {t("js.contacts.birthday")}
            </label>
            <DatePicker
              id="reg-birthday"
              inputName="birthday"
              value={birthday}
              onChange={setBirthday}
              mode="date"
              locale={lang}
              autoComplete="bday"
              className={`input input-md w-full${fieldErrors.birthday ? " input-error" : ""}`}
            />
            {fieldErrors.birthday ? <p className="mt-1 text-xs text-error">{fieldErrors.birthday}</p> : null}
          </div>

          <div className="w-full min-w-0 [&_div.relative]:max-w-none">
            <label className="mb-1 block text-sm font-medium">{req(t("js.contacts.instrument"))}</label>
            <SelectPicker
              options={instrumentOptions}
              value={instrumentId}
              onChange={setInstrumentId}
              labelSelect={t("js.common.select")}
              labelNoMatches={t("js.common.noMatches")}
              labelClose={t("js.common.close")}
              passwordManagerIgnore
            />
            {fieldErrors.instrument ? <p className="mt-1 text-xs text-error">{fieldErrors.instrument}</p> : null}
          </div>

          <div>
            <label htmlFor="reg-pw1" className="mb-1 block text-sm font-medium">
              {req(t("js.register.password"))}
            </label>
            <PasswordStrengthField
              id="reg-pw1"
              value={pw1}
              onChange={setPw1}
              autoComplete="new-password"
              placeholder={t("js.register.passwordPlaceholder")}
              userInputs={pwdInputs}
            />
            {fieldErrors.pw1 ? <p className="mt-1 text-xs text-error">{fieldErrors.pw1}</p> : null}
          </div>

          <div>
            <label htmlFor="reg-pw2" className="mb-1 block text-sm font-medium">
              {req(t("js.register.passwordConfirm"))}
            </label>
            <input
              id="reg-pw2"
              type="password"
              autoComplete="new-password"
              value={pw2}
              onChange={(e) => setPw2(e.target.value)}
              placeholder={t("js.register.passwordConfirmPlaceholder")}
              className="input input-md w-full"
            />
            {fieldErrors.pw2 ? <p className="mt-1 text-xs text-error">{fieldErrors.pw2}</p> : null}
          </div>

          <div className="flex items-start gap-2">
            <input
              id="reg-terms"
              type="checkbox"
              className="checkbox checkbox-sm mt-1 shrink-0 border-2 border-white bg-white ring-1 ring-base-300/45 dark:border-white dark:bg-white dark:ring-base-content/15 checked:border-primary checked:bg-primary checked:ring-primary/35 checked:text-primary-content"
              checked={terms}
              onChange={(e) => setTerms(e.target.checked)}
            />
            <div className="text-sm leading-snug">
              <label htmlFor="reg-terms" className="cursor-pointer">
                {t("js.register.termsIntro")}
              </label>{" "}
              <Link href="/legal/terms/" className="link link-primary" target="_blank" rel="noopener noreferrer">
                {t("js.legal.terms")}
              </Link>{" "}
              <label htmlFor="reg-terms" className="cursor-pointer">
                {t("js.register.termsEnd")}
              </label>
            </div>
          </div>
          {fieldErrors.terms ? <p className="text-xs text-error">{fieldErrors.terms}</p> : null}

          {submitError ? (
            <div className="rounded-lg border border-error/20 bg-error/10 px-4 py-3 text-sm text-error">
              {submitError}
            </div>
          ) : null}

          <button type="submit" disabled={submitting || !canSubmit} className="btn btn-primary btn-lg btn-block">
            {submitting ? t("js.register.submitting") : t("js.register.submit")}
          </button>
        </form>
      </div>
      <LegalFooter />
    </div>
  );
}

function RegisterForm() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-base-200 sm:bg-base-100">
          <Spinner />
        </div>
      }
    >
      <RegisterFormInner />
    </Suspense>
  );
}

export default function RegisterPage() {
  return (
    <I18nProvider>
      <div className="flex min-h-screen justify-center bg-base-200 sm:bg-base-100 px-0 py-6 sm:px-4 sm:py-8">
        <RegisterForm />
      </div>
    </I18nProvider>
  );
}
