---
name: Localization and TBA Fix
overview: Fix dashboard TBA-for-dates bug, "Willkommen bei [object Object]" subtitle bug, and localize all user-facing strings in the next app. No hardcoded strings—every user-visible label, message, placeholder, or fallback must use i18n (translated strings only).
todos: []
isProject: false
---

# Localization and Dashboard TBA / Subtitle Fixes

## Golden rule: no hardcoded strings

**All user-facing text must come from translations.** No hardcoded English, German, or other language strings in HTML or JS. Use `i18n.t(key)` or `data-i18n` / `data-i18n-label` / `data-i18n-params` everywhere.

**In scope:** Labels, buttons, headings, placeholders, error messages, toast messages, form labels, table headers, empty states, fallbacks (e.g. "User", "Event", "BNote"), status text, modal copy, aria-labels, and any other text rendered in the UI.

**Out of scope:** Developer-only content (e.g. `console.log`/`console.warn`/`console.error`), API module/action names, CSS classes, data attributes used for logic, and numeric/technical constants.

## 1. Fix TBA where event date should appear (Dashboard)

**Problem:** The first prominent line on each event card shows "TBA" instead of the event date. The dashboard uses `formatEventDate(event.eventBegin || event.dueDate)` and `formatEventTime(...)` in [dashboard.js](next/assets/js/dashboard.js) (lines 816–817, 889–900). TBA is returned when `dateStr` is missing or parsing fails.

**Root causes to address:**

- **Robust date parsing:** Use a single helper that normalizes `YYYY-MM-DD HH:MM:SS` to ISO (`replace(' ', 'T')`) before `new Date(...)`. Both [dashboard.js](next/assets/js/dashboard.js) (`formatEventDate` / `formatEventTime`) and [event-detail.js](next/assets/js/event-detail.js) (`formatDateTime`, `formatDate`, `formatTime`) must use it. `event-detail`’s `formatDateTime` currently uses `new Date(dateStr)` with no normalisation, which can fail in some engines.
- **Correct field usage:** Ensure we use `event.eventBegin ?? event.dueDate` (or equivalent). The API ([dashboard.php](next/api/modules/dashboard.php) `formatInboxItems`) provides both; confirm the frontend never drops or renames these.
- **Defensive fallback:** If parsing fails, log a warning (e.g. `console.warn`) with the raw value and key used, to simplify debugging. Keep returning localized TBA only when there is truly no valid date.

**Concrete changes:**

- Add a small `parseEventDate(str)` helper (e.g. in `i18n.js` or a shared util) that normalizes and parses, returns `null` on failure.
- Use it in `formatEventDate`, `formatEventTime` (dashboard) and in `formatDate`, `formatTime`, `formatDateTime` (event-detail). Use `parseEventDate` output for `Intl` formatting; only use TBA when result is `null`.
- Ensure dashboard never passes an object or non-string into the formatters (e.g. if API ever sent `begin` as object).

---

## 2. Fix "Willkommen bei [object Object]" (subtitle)

**Problem:** The dashboard subtitle uses `i18n.t('js.dashboard.subtitle', [companyName])`. The `%p` placeholder should be the **Band Name** from configuration (same source as the old UI: `config/company.xml` → `getCompany()`). If `companyName` is an object, it stringifies to `[object Object]`.

**Fix:**

- **Backend:** Dashboard API already returns `company` from `$system_data->getCompany()` ([dashboard.php](next/api/modules/dashboard.php) line 78). Ensure this is always a string (config "Name" = Band Name).
- **Frontend:** In [dashboard.js](next/assets/js/dashboard.js) `updateDashboardSubtitle()`, ensure we always pass a string. Normalise: `const raw = this.dashboardData?.company;` then `companyName = typeof raw === 'string' ? raw : (raw?.name ?? i18n.t('js.common.appName'));`. Use `i18n.t('js.dashboard.subtitle', [companyName])`. No hardcoded `'BNote'`; use `i18n.t('js.common.appName')` for fallback.

---

## 3. Localization plan – strings to cover

**Principles:**

- **Translated strings only.** Every user-visible string must use `i18n.t(key)` or `data-i18n` / `data-i18n-label`. No hardcoded fallbacks (e.g. `'Good morning'`, `'User'`, `'Event'`) in UI code.
- Language/country from system config; date/time via `i18n.getBrowserLocale()`. Add all new keys to [next/lang](next/lang) JSON files. [Translations API](next/api/modules/translations.php) must keep including all `js.*` keys.

**A. Dashboard**

- **"Your Response Needed" / "Deine Antwort benötigt"** → use `js.dashboard.responseNeeded`. **Exact translations:** EN "Open Invitations", DE "Offene Rückmeldungen", FR "Invitations ouvertes", ES "Invitaciones abiertas". Same for `js.rehearsals.responseNeeded` if used for the same UX.
- **"Upcoming Events"** – Add `data-i18n="js.dashboard.upcomingEvents"` in [dashboard.html](next/dashboard.html) (line 208). Provide de, en, es, fr.
- **"Back to Dashboard"**, **"Load More"** – `data-i18n` or `i18n.t('js.dashboard.backToDashboard')`, `i18n.t('js.common.loadMore')`; add keys in all four lang files.

**B. Event detail page**

- **Labels:** Date, Time, Status, Location, Deadline, Conductor, Meeting Time, Songs to practice, Notes, Open in Google Maps – all hardcoded in [event-detail.js](next/assets/js/event-detail.js). Replace with `i18n.t(...)` and new keys (e.g. `js.event.detail.date`, `js.event.detail.time`, …).
- **Sections:** "Your Participation", "Participation Overview", "Participants", "Additional Information" – same approach.
- **Status badges:** "Geplant", "Bestätigt", "Abgesagt", "Versteckt" in `renderStatusBadge` (event-detail.js). Use keys like `js.event.status.planned`, `js.event.status.confirmed`, etc., and `i18n.t()`.
- **"(Past)"** next to deadline – add `js.event.detail.past` and use `i18n.t()`.

**C. Participant overview**

- **"Group by:"**, **"Category"**, **"Instrument"** – in [participant-overview.js](next/assets/js/participant-overview.js) (lines 79, 85, 92). Use `i18n.t('js.participants.groupBy')`, `i18n.t('js.participants.category')`, `i18n.t('js.participants.instrument')`.

**D. Event metadata (concerts)**

- **Labels:** Organisation, Besetzung, Programm, Outfit, Equipment, Unterkunft, Gage, Konditionen, Treffpunkt, Kontakt – in [event-metadata.js](next/assets/js/event-metadata.js). Replace with `i18n.t(...)` and keys (e.g. `js.event.metadata.organisation`, `js.event.metadata.besetzung`, …).

**E. Participation modal**

- **"Reason for …"**, **"Optional reason..."**, **"Cancel"**, **"Confirm"** – in [dashboard.html](next/dashboard.html) (lines 299–313). Use `data-i18n` where possible. The dynamic part (status label) should use an already-translated status string when setting `#modal-status-label`.

**F. Sidebar**

- **Dashboard, Users, Contacts, Calendar, Members, Messages, Venues, Documents** – in [dashboard.html](next/dashboard.html) and other pages that share the same sidebar. Add `data-i18n` on the `<span class="sidebar-text">` elements (or equivalent) with keys like `js.sidebar.dashboard`, `js.sidebar.users`, etc. Ensure `translatePage` (or a shared init) runs on those pages so sidebar labels are translated.

**G. Shared**

- **"Clear" vs "Delete":** Use distinct keys: `js.common.clear` (e.g. "Zurücksetzen") vs `js.common.delete` ("Löschen").

**H. Users, Contacts, Login, Tables/forms**

- **Users** ([users.js](next/assets/js/users.js), [users.html](next/users.html)): All table/form labels, placeholders, toasts ("Failed to load users", "User created successfully", etc.), "Deactivate"/"Activate", "Manage Privileges", "Never", GDPR copy → `i18n.t('js.users.*')` / `i18n.t('js.form.*')` / `i18n.t('js.error.*')`.
- **Contacts** ([contacts.js](next/assets/js/contacts.js), [contacts.html](next/contacts.html)): Same; "Add Contact", "Edit Contact", "coming soon" toasts → `i18n.t('js.contacts.*')`.
- **Login** ([login.html](next/login.html)): Placeholders, "Anmelden", "Anmeldung...", error message → `i18n.t('js.login.*')`.
- **Tables/forms** ([table.js](next/assets/js/table.js), [form.js](next/assets/js/form.js)): "Search...", "Type to search...", default form/table strings → `i18n.t('js.table.*')` / `i18n.t('js.form.*')`. Callers pass i18n keys or pre-translated strings.

**I. Systematic audit (no hardcoded strings)**

1. Grep `next/**/*.{js,html}` for `showToast(`, `showError(`, `placeholder=`, `title: '`, `label: '`, `textContent = '`, fallbacks like `|| 'TBA'`, `|| 'User'`, etc.
2. Replace each user-facing literal with `i18n.t(key)` or `data-i18n`; add keys to all lang JSON files.
3. Ensure `translatePage` (or equivalent) runs on every page that renders dynamic UI.
4. Test with each locale (de, en, es, fr); confirm no hardcoded strings remain visible.’s not confused with delete.

---

## 4. Date/time formatting (event detail and dashboard)

**Region = configuration `default_country`.** Language and country come from system config ([auth API](next/api/modules/auth.php) `getUserLang` → `i18n.init`). Use `i18n.getBrowserLocale(i18n.getLang())` for all date/time formatting (e.g. `de-DE`, `en-US`). No browser locale for region.

**Dates: always long format.** Use `Intl.DateTimeFormat` with `weekday: 'long'`, `month: 'long'`, `day: 'numeric'`, `year: 'numeric'` (or equivalent long forms). Do not use `month: 'short'` or `'2-digit'` for display dates.

**Time: hours and minutes only, no seconds.** Use `hour: 'numeric'`, `minute: '2-digit'`. Omit `second` entirely. Let `hour12` follow the locale (e.g. `en-US` → 12h, `de-DE` → 24h).

**Implementation:**

- **Event detail:** Use `parseEventDate` + `Intl.DateTimeFormat` with `i18n.getBrowserLocale(i18n.getLang())` for all date/time. Apply the options above.
- **Dashboard:** Same; use shared `parseEventDate` and locale. Update `formatEventDate` / `formatEventTime` in [dashboard.js](next/assets/js/dashboard.js) to use long date and time-without-seconds.
- **i18n.js:** Ensure `formatDate` / `formatDateTime` helpers use long date and time (no seconds) when used for UI. `getBrowserLocale` must use config country (already does via `currentCountry`).

---

## 5. Implementation order

1. **TBA fix:** Add `parseEventDate`, update dashboard and event-detail formatters, verify dates show correctly.
2. **Subtitle fix:** Normalise `company` to string in `updateDashboardSubtitle`; use `i18n.t('js.common.appName')` for fallback.
3. **New i18n keys:** Add all new keys to `de` / `en` (and `es` / `fr`) — see sections 3.A–3.K. No hardcoded strings; every key must have translations.
4. **Date/time:** Apply long-date and time-no-seconds rules (Section 4); use config country as region throughout.
5. **Address formatting:** Add `state` and `country` to location address in rehearsals/concerts API; implement `formatAddress(address)` and use it for location display (event-detail, event-metadata).
6. **Translations:** Add all keys to de, en, fr, es (Section 7 table and 3.A–3.K). Include `js.dashboard.responseNeeded` as Open Invitations / Offene Rückmeldungen / Invitations ouvertes / Invitaciones abiertas.
7. **Replace hardcoded strings:** Dashboard → Event detail → … → Tables/forms. Use `i18n.t(key)` or `data-i18n` throughout.
8. **Systematic audit** and **smoke-test** per plan; **screenshot review** if user provides screenshots.

---

## 6. Files to touch

| Area | Files |

|------|--------|

| Date parsing / TBA | [next/assets/js/dashboard.js](next/assets/js/dashboard.js), [next/assets/js/event-detail.js](next/assets/js/event-detail.js), optionally [next/assets/js/i18n.js](next/assets/js/i18n.js) or a shared util |

| Subtitle | [next/assets/js/dashboard.js](next/assets/js/dashboard.js) |

| Translations | [next/lang/de.json](next/lang/de.json), [next/lang/en.json](next/lang/en.json), [next/lang/es.json](next/lang/es.json), [next/lang/fr.json](next/lang/fr.json) |

| Dashboard UI | [next/dashboard.html](next/dashboard.html), [next/assets/js/dashboard.js](next/assets/js/dashboard.js) |

| Event detail | [next/assets/js/event-detail.js](next/assets/js/event-detail.js) |

| Participant overview | [next/assets/js/participant-overview.js](next/assets/js/participant-overview.js) |

| Event metadata | [next/assets/js/event-metadata.js](next/assets/js/event-metadata.js) |

| Modal | [next/dashboard.html](next/dashboard.html); participation modal wiring likely in [next/assets/js/participation.js](next/assets/js/participation.js) |

| Sidebar | [next/dashboard.html](next/dashboard.html), [next/users.html](next/users.html), [next/contacts.html](next/contacts.html) (and any other pages that include the same sidebar) |

| Users, Contacts, Login | [next/assets/js/users.js](next/assets/js/users.js), [next/assets/js/contacts.js](next/assets/js/contacts.js), [next/users.html](next/users.html), [next/contacts.html](next/contacts.html), [next/login.html](next/login.html) |

| Tables, forms | [next/assets/js/table.js](next/assets/js/table.js), [next/assets/js/form.js](next/assets/js/form.js) |

| Date/time, locale | [next/assets/js/i18n.js](next/assets/js/i18n.js), [next/assets/js/dashboard.js](next/assets/js/dashboard.js), [next/assets/js/event-detail.js](next/assets/js/event-detail.js), [next/assets/js/event-metadata.js](next/assets/js/event-metadata.js) |

| Address formatting | [next/api/modules/rehearsals.php](next/api/modules/rehearsals.php), [next/api/modules/concerts.php](next/api/modules/concerts.php); `formatAddress` helper + [next/assets/js/event-detail.js](next/assets/js/event-detail.js), [next/assets/js/event-metadata.js](next/assets/js/event-metadata.js) |

---

## 7. Translations (DE, EN, FR, ES) and address formatting

**Provide translations for German, English, French, and Spanish** in [next/lang/de.json](next/lang/de.json), [next/lang/en.json](next/lang/en.json), [next/lang/fr.json](next/lang/fr.json), [next/lang/es.json](next/lang/es.json). **Screenshot review:** User can provide screenshots; verify all UI strings match these translations and that date/time and addresses render correctly.

**Key strings (user-specified):**

| Key | EN | DE | FR | ES |
|-----|----|----|----|-----|
| `js.dashboard.responseNeeded` | Open Invitations | Offene Rückmeldungen | Invitations ouvertes | Invitaciones abiertas |
| `js.dashboard.subtitle` | Welcome to %p | Willkommen bei %p | Bienvenue chez %p | Bienvenido a %p |
| `js.common.appName` | BNote | BNote | BNote | BNote |

(`%p` = Band Name from config.) Ensure all other keys in sections 3.A–3.K have explicit de, en, fr, es entries.

**Address formatting:** Format addresses **by the location’s country** (not the app config). Multiple lines allowed.

- **API:** Include `country` (and `state` if useful) in location `address` for rehearsals and concerts. [AbstractLocationData::getAddress](src/data/abstractlocationdata.php) already returns `street`, `city`, `zip`, `state`, `country`. [Rehearsals](next/api/modules/rehearsals.php) and [concerts](next/api/modules/concerts.php) currently expose only `street`, `city`, `zip`; add `state` and `country` to the location `address` object.
- **Frontend:** Add a shared helper (e.g. `formatAddress(address)`) that takes `{ street, city, zip, state, country }` and formats according to the **address country** (e.g. `de-DE` vs `en-US` for order, separators, optional state). Output **multiple lines** (e.g. street; zip + city; country) where appropriate. Use in [event-detail.js](next/assets/js/event-detail.js) and [event-metadata.js](next/assets/js/event-metadata.js) for location/accommodation display.

---

## 8. Optional checks

- **API:** Log or assert dashboard API response shape (`eventBegin`, `dueDate`, `company`), and location `address.country`.
- **Translations API:** Confirm `filterByPermissions` continues to expose all `js.*` keys used by the frontend.