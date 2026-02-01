# BNote Next Generation – Features Overview

This document synthesizes the **vanilla JavaScript app**, **your prompts**, and **the screenshots** you provided to give a single reference for features and parity.

---

## 1. Your Prompts (Summary)

| Prompt | Issue / Goal | Resolution / Status |
|--------|--------------|--------------------|
| **Band name missing, showing [module module]** | Subtitle should show company/band name; translations broken | **Fixed:** API returns company as SimpleXMLElement → cast to string in PHP; frontend uses `normalizeCompany()`; added missing translation keys (saved, deleted, confirmDelete, search, noEventsNeedingResponse, contacts.*, users.*). |
| **Quick action bar was hidden** | Dashboard quick actions not visible | **Clarified:** In vanilla, Quick Actions card has `class="hidden"` in `dashboard.html` – it is intentionally hidden. Next.js shows it. Decide: match vanilla (hide) or keep visible. |
| **React Hooks order error (DashboardPage)** | `useState` for greeting was after early returns → hook order changed between renders | **Fixed:** Moved `greeting` state and greeting-update logic to top of component (before any `if (!ready \|\| loading)` / `if (error)` returns). |
| **Make Next.js app as close to old one as possible; icons/images working** | Parity + assets | **Plan created:** `NEXT_JS_PARITY_PLAN.md` – icons, logo, entity config, Quick Actions visibility, verification checklist. |
| **Check every module; focus mobile vs desktop** | Module behaviour and responsive layout | Dashboard, Contacts, Users, Search, Entity Detail, Login – each has mobile/desktop considerations (sidebar vs mobile drawer, search overlay, event cards, etc.). |
| **Check development of vanilla JS app + screenshots for thorough overview** | Single feature reference | **This document** – maps screenshots and prompts to features. |

---

## 2. Branding & Shell (Screenshot: BNote logo + name)

**What the screenshot shows:**
- Dark background.
- **Logo:** Square icon, rounded corners, light blue outline; inside: stylized calendar with music note (quaver), filled blue.
- **Text:** “BNote” in white, sans-serif, next to the logo.

**Implementation:**
- **Vanilla:** Logo from `BNote/style/images/BNote_Logo_white_transparent.svg` via `BNoteConfig.getBNoteResource(...)`. Used in sidebar, mobile nav, login.
- **Next.js:** `getBnoteLogoUrl()` in `lib/bnote-assets.ts` builds URL to same path; fallback “B” when URL is empty.
- **Translations:** App name “BNote” is static or from `js.common.appName`; band/company name in dashboard subtitle comes from API `company` + `js.dashboard.subtitle` with `%p`.

**Parity:** Logo path and fallback are aligned; ensure logo loads in sidebar, mobile nav, and login in Next.js.

---

## 3. User Management – Benutzerverwaltung (Screenshots)

**What the screenshots show:**
- **Title:** “Benutzerverwaltung” (User Management).
- **Subtitle:** “Benutzer, Berechtigungen und Zugriff verwalten”.
- **Action bar:** “+ Benutzer hinzufügen” (blue, plus icon), “DSGVO” (shield icon).
- **Search:** “Suchen…” with magnifying glass.
- **Table:** ID, ANMELDENAME ↑ (sortable), VORNAME, NACHNAME, STATUS, LETZTE ANMELDUNG, AKTIONEN.
- **Status:** “Aktiv” = amber/orange pill, “Inaktiv” = red pill.
- **Actions column:** Three-dots menu per row → Bearbeiten (pencil), Deaktivieren (X), Berechtigungen verwalten (key), Löschen (trash, red).

**Features to replicate:**
- Page title + subtitle (i18n: `js.users.title`, `js.users.subtitle`).
- Add User button (primary), GDPR button.
- Table search.
- Sortable columns (e.g. login name).
- Status pills (Active / Inactive) with correct colors.
- Row actions dropdown: Edit, Deactivate, Manage Permissions, Delete (destructive style).
- Icons: plus, shield, search, pencil, X, key, trash.

**Vanilla:** `users.html`, `assets/js/users.js`, `assets/js/modules/users.js`.  
**Next.js:** `app/(app)/users/page.tsx`.  
**Parity:** Structure exists; verify labels, status colors, and all action icons match screenshots.

---

## 4. Search Results – Suchergebnisse (Screenshot)

**What the screenshot shows:**
- **URL:** `search.html?search=probe`.
- **Sidebar:** BNote logo, Dashboard, Benutzer, Kontakte.
- **Top:** Sun (theme), “User”.
- **Main:** Global search “Alles durchsuchen…”; title “Suchergebnisse”; “Suche: probe • 379 Ergebnisse”.
- **Filters:** “Probe” (blue, active), “Auftritt” (orange), “Jahr”, “Monat” dropdowns.
- **Results:** “Proben (373)”; list with blue vertical line, music-note icon per row; each row: date, “Probe am DD.MM.YYYY 19:30”, blue “Probe” tag, clock 19:30, map pin “Haus der Vereine”.

**Features to replicate:**
- Search entry from top bar → search results page with query in URL.
- Summary line: query + result count.
- Filter chips: Rehearsal (Probe), Performance (Auftritt), Year, Month.
- Result type header with count (e.g. “Proben (373)”).
- Timeline list: vertical line, type icon, date, description, type tag, time, location.
- Icons: music note (rehearsal), clock, map-pin; theme and user in header.

**Vanilla:** `search.html`, `assets/js/search-results-page.js`, `assets/js/search-results.js`, `config/entity-config.json` for type icon/color.  
**Next.js:** `app/(app)/search/page.tsx`, search context, entity config.  
**Parity:** Filters, result layout, and entity-type icons (e.g. rehearsal = music) must match; ensure entity-config icons (e.g. trumpet for concert) are mapped in Next.js.

---

## 5. Entity Detail – Rehearsal “Probe” (Screenshot)

**What the screenshot shows:**
- **URL:** `entity-detail.html?module=dashboard&entity=rehearsal&id=536&mode=view`.
- **Sidebar:** BNote, Dashboard (active), Benutzer, Kontakte.
- **Top:** “Alles durchsuchen…”, sun, bell, user “U”.
- **Main:**
  - **Header:** “Probe” with music note icon and blue “Probe” tag.
  - **Info panel:** Datum, Uhrzeit, Status (Geplant), Rückmeldung bis, Dirigent (Franz Schledorn), Ort (full address), buttons “In Google Maps öffnen” / “In Apple Maps öffnen”.
  - **Deine Teilnahme:** Green check (confirm), orange “?” (maybe), red X (decline).
  - **Teilnahme-Übersicht:** Bar (e.g. 10 green, 2 red, 13 grey).
  - **Teilnehmer:** “Gruppieren nach: Kategorie | Instrument”; groups e.g. Blechbläser, Dirigent, Holzbläser with summary bars and participant rows (initials, clock or checkmark).

**Features to replicate:**
- Entity detail route: module, entity type, id, mode.
- Event type title + icon + tag (from entity config).
- Metadata: date, time, status, deadline, conductor, location (clickable), map links (Google + Apple).
- Participation widget: Yes / Maybe / No with correct colors.
- Participation overview bar (counts by status).
- Participants list with “Group by: Category | Instrument”, group headers with small bars, per-participant status icon (clock = pending, check = confirmed).

**Vanilla:** `entity-detail.html`, `assets/js/event-detail.js`, participation diagram, participant overview.  
**Next.js:** `app/(app)/entity/page.tsx`, `ParticipationWidget`, `ParticipationDiagram`, `ParticipantOverview`.  
**Parity:** Layout, labels, map buttons, and grouping options should match; ensure “Probe”/type label and band name (if shown) use translations and API data.

---

## 6. Contacts – Kontakte (Screenshot)

**What the screenshot shows:**
- **URL:** `app.html?module=contacts`.
- **Sidebar:** BNote, Dashboard, Benutzer, Kontakte (active).
- **Main:** “Kontakte” / “Kontakte, Gruppen und Integrationen verwalten”.
- **Action bar:** “+ Kontakt hinzufügen” (blue), then grey: Integration, Gruppen, Drucken, vCard, Datenschutz.
- **Filter tabs:** All (active), Administratoren, Mitglieder, Externe, Bewerber, Sonstige, etc., “Inaktive Mits”.
- **Table search:** “Suchen…”.
- **Table:** ID, VORNAME ↑, NACHNAME, SPITZNAME, INSTRUMENT, E-MAIL, TELEFON, MOBIL, S.

**Features to replicate:**
- Title + subtitle (i18n).
- Primary action: Add contact.
- Secondary actions: Integration, Groups, Print, vCard, Data protection (can be “coming soon” or wired later).
- Category filter tabs.
- Table search and full column set; sortable VORNAME.
- Instrument and contact data columns.

**Vanilla:** `contacts.html`, `assets/js/contacts.js`, `assets/js/modules/contacts.js`.  
**Next.js:** `app/(app)/contacts/page.tsx`.  
**Parity:** Action bar and tabs present; secondary actions may be placeholders until backend/design is final.

---

## 7. Cross-Cutting Features (From All Screenshots & Prompts)

| Area | Details |
|------|--------|
| **Shell** | Sidebar (desktop) with logo + nav; mobile drawer; top bar with global search, theme toggle, user. |
| **Theme** | Dark (and light) mode; sun/moon icon; CSS variables in `app.css` / `globals.css`. |
| **i18n** | All UI in German in screenshots; keys under `js.*`, `banner_Logout.*`, etc.; placeholder `%p` for dynamic text (e.g. company name). |
| **Icons** | Lucide via `data-lucide` (vanilla) or `getIcon()` (Next.js): dashboard, user(s), search, plus, shield, pencil, key, trash, clock, map-pin, music note, etc. |
| **Logo** | BNote calendar+music-note SVG from `BNote/style/images/BNote_Logo_white_transparent.svg`; fallback “B” when URL missing. |
| **Quick Actions (dashboard)** | In vanilla the block is hidden (`class="hidden"`). In Next.js it is visible. Align by product decision. |
| **Band/company name** | From API `company`; shown in dashboard subtitle; must be a string (PHP cast + `normalizeCompany()` in frontend). |
| **Entity types** | Rehearsal, concert, meeting, contact, user, task, etc. – colors and icons from `config/entity-config.json`; Next.js must map all icon names (e.g. trumpet → Music if no trumpet in Lucide). |
| **Mobile vs desktop** | Sidebar hidden on small screens; mobile nav drawer; search overlay fullscreen on mobile, dropdown on desktop; event cards compact on mobile, timeline on desktop. |

---

## 8. Feature Checklist (High Level)

Use this to confirm parity and behaviour:

- [ ] **Branding:** Logo and “BNote” match screenshot; favicon if required.
- [ ] **Dashboard:** Greeting + company subtitle; events needing response; timeline; filters; participation on cards; optional Quick Actions (if enabled).
- [ ] **Users:** Title/subtitle; Add User + DSGVO; search; table with sort; Active/Inactive pills; row menu (Edit, Deactivate, Permissions, Delete) and icons.
- [ ] **Contacts:** Title/subtitle; Add Contact; Integration, Groups, Print, vCard, Datenschutz; category tabs; table + search.
- [ ] **Search:** Query in URL; result count; filters (type, year, month); result list with type icon, date, tag, time, location.
- [ ] **Entity detail:** Type title + icon + tag; full metadata; map links; participation widget; overview bar; participants with group-by and status icons.
- [ ] **Shell:** Sidebar, mobile drawer, top bar (search, theme, user); logo everywhere.
- [ ] **Translations:** No “[module module]” or raw keys; company name in subtitle; all German (or selected locale) strings from API/JSON.
- [ ] **Icons & images:** All Lucide icons used in screenshots present in Next.js; logo and optional assets load; no broken placeholders.
- [ ] **Mobile:** Drawer, fullscreen search overlay, compact event cards, no layout breaks.

This overview should give you a single reference for “what the vanilla app does and what we’re matching” across prompts and screenshots.
