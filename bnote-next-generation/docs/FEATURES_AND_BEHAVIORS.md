# BNote Next Generation – Features and Behaviors

This document describes each feature and expected behavior of the Next.js app. Use it for regression checks and bug fixing.

**Shared UI patterns** (lists, detail/edit, delete, URL for edit mode) are documented in **[UI_PATTERNS.md](UI_PATTERNS.md)**. All modules and entity pages must follow those patterns.

---

## 1. Overview

- **App:** Next.js UI (static export) in `frontend/`, PHP REST API in `api/`.
- **Entry points:** `/` (redirects to `/dashboard` or `/login`), `/login`, `/register` (when `user_registration` is enabled), `/reset-password`, `/reset-password/confirm`, `/participation/respond`, `/dashboard`, `/users`, `/contacts`, `/contacts/integration`, `/search`, `/settings`, `/profile`, `/profile/edit`, legal routes under `/legal/*`, module pages under **`frontend/app/_modules/*`**, entity detail at **`/entity?type=…&id=…`** (and **`&edit=1`** in edit mode; see **[UI_PATTERNS.md](UI_PATTERNS.md)**).
- **Auth:** Session-based; API uses PHP session cookie. Unauthenticated users are redirected to `/login?redirect=…`.

---

## 2. Authentication

- **Login flow:** User submits username and password on `/login`. API `POST /api/v1/auth/login` creates session. On success, redirect to `redirect` query param or `/dashboard`.
- **Registration:** When `user_registration` is on, `getPublicConfig` exposes it and `/register` loads `getRegistrationOptions`, then `register` creates the user via the API layer in `bnote-next-generation/api/nextgen_registration.php` (same DB rules as legacy). The contact row sets **`gdpr_ok = 1`** when registration completes with accepted terms on this path. Success paths depend on `auto_user_activation` and mail delivery. Legal footer includes `/legal/terms/`.
- **Admin email (new registration):** After success, the API may notify administrators via the Next Gen mail stack (`api/mail/`, PHPMailer): one send with the first admin in **To** and the rest in **BCC**. Copy lives in `lang/*.json` (`mail.*`). Configure SMTP with `MAIL_*` env vars; deep links use `NEXTGEN_PUBLIC_URL` (or `NEXTGEN_PUBLIC_ORIGIN` + `NEXT_PUBLIC_BASE_PATH`). Failures are logged only. Details: **[MAIL.md](MAIL.md)**.
- **Session check:** `GET /api/v1/auth/me` (or equivalent) used to verify session; 401 → redirect to login with current path as `redirect`.
- **Logout:** Calls API to clear session; redirect to `/login`.
- **Remember me:** If supported, document behavior here.

---

## 3. Shell and Layout

- **Sidebar (desktop):** BNote logo, nav links (Dashboard, Users, Contacts from API modules). Logo from `getBnoteLogoUrl()` in `lib/bnote-assets.ts` (BNote/style/images/BNote_Logo_white_transparent.svg); fallback “B” when URL missing.
- **Top bar:** Global search input, theme toggle (sun/moon), user indicator.
- **Mobile:** Sidebar hidden; hamburger opens full-screen nav drawer with same links and logo.
- **Theme:** Dark/light via CSS variables; persisted (e.g. localStorage). Use semantic variables (e.g. `--background`, `--foreground`, `--primary`) in `frontend/app/globals.css`.

---

## 4. Dashboard

- **Greeting:** Time-based greeting (e.g. Good morning) + user name; optional **week-based** personalized line when implemented; state and effect must run before any early returns (hooks order).
- **Company subtitle:** Band/company name from API `company`; use `normalizeCompany()` so SimpleXMLElement/cast issues are handled. Translation key `js.dashboard.subtitle` with `%p` for company.
- **Quick actions:** Card with quick action buttons; currently visible (product decision: was hidden in legacy UI).
- **Events needing response:** Section listing events where the user has not yet responded. Event cards show date, title, type tag, time, location; link to entity detail.
- **Admin overview (administrators only):** Includes a **Pending accounts** tile when there are users with **`isActive = 0`** whose contact has **no** rows in **`rehearsal_contact`**, **`concert_contact`**, **`rehearsalphase_contact`**, **`tour_contact`**, and **no** **`vote_group`** row for that user—i.e. inactive accounts that still need **phase-in** (typically brand-new registrations), not every deactivated user. The count is included in **`action_needed_count`**. The tile links to **Contacts → integration** with the default member group when applicable. Additional **band overview** / admin tiles may show actionable summaries (votes, tasks, etc.) when present in the API response.
- **Event cards:** Desktop: timeline + card layout. Mobile: compact list item without timeline.
- **Filters:** By type (rehearsal/concert), year, month if applicable.

---

## 5. Users

- **Route:** `/users`. Page: `app/(app)/users/page.tsx`.
- **Title/subtitle:** i18n `js.users.title`, `js.users.subtitle`.
- **Actions:** “Add User” (primary), optional GDPR (shield) if implemented.
- **Search:** Client-side filter by login, first name, last name.
- **Table (see [UI_PATTERNS.md](UI_PATTERNS.md)):** Columns: **Login, First name, Last name, Status, Last login, Actions**. No ID column. No Edit (pencil) or Delete (trash) in list rows. All columns sortable; **date sorting for Last login** uses timestamp comparison (null/missing sorts to end in asc).
- **Status pills:** Active = amber; Inactive = red (destructive-style).
- **Row actions:** Manage privileges (key), Activate/Deactivate (X/check) only. Edit and Delete are not in the list; editing is via modal (e.g. from URL `?id=…`); delete is not offered from the list.
- **Modals:** Add user (login, password, contact, isActive); Edit user (password optional, contact, isActive); Manage privileges (checkboxes per module).

---

## 6. Contacts

- **Route:** `/contacts`. Page: `app/(app)/contacts/page.tsx`.
- **Phase in (integration):** Route **`/contacts/integration`**. Same **Contacts** module permission as the list. A **`SelectPicker`** (same shared control as profile / entity edits) at the top drives the member list; it defaults to the **members group** (same id as legacy `KontakteData::$GROUP_MEMBER`, usually **2** / “Mitglieder” in default installs). A valid **`?group=`** in the URL overrides the default; invalid or missing `group` is replaced with the default. Users search/filter per list, then multi-select members, rehearsals, concerts, and votes. **Save** → `POST contacts` **`integrate`**. Data: **`getIntegrationBundle`**. Header **Phase in** opens the page; optional **`?group=`** from the contacts list filter.
- **Title/subtitle:** i18n `js.contacts.title`, `js.contacts.subtitle`.
- **Actions:** **Phase in** (outline) and **Add Contact** (primary); other secondary actions as implemented.
- **Filter tabs:** “All” + group tabs from API; selection filters list.
- **Search:** Client-side filter by name, surname, nickname, email.
- **Table (see [UI_PATTERNS.md](UI_PATTERNS.md)):** Columns: **First name, Last name, Nickname, Instrument, Email, Phone, City**. No ID column. No Edit or Delete buttons in list rows.
- **Row click:** Opens edit modal. Edit and Delete are not in the list.
- **Modals:** Add/Edit contact form: name, surname, nickname, email, phone, mobile, street, zip, city, notes, groups (checkboxes).

---

## 7. Search

- **Top-bar search:** Typing shows overlay with categorized results (e.g. Konzerte, Proben). Overlay styled like search results page: type icon, date, tag, location; primary-colored separator.
- **Search results page:** Route `/search` with query in URL. Summary: “Suche: {query} • {count} Ergebnisse”. Filters: type (Rehearsal/Performance), Year, Month.
- **Result list:** Grouped by type (e.g. “Proben (373)”). Each row: vertical line, type icon (from entity config), date, description, type tag (pill), time, location (map-pin). Entity config: `frontend/config/entity-config.json`; icons/colors via `lib/entity-config.ts` (e.g. getEventTypeConfig, getPillStyle). Icon name “trumpet” mapped to Music in `icons.tsx` if needed.

---

## 8. Entity Detail

- **Routes (see [UI_PATTERNS.md](UI_PATTERNS.md)):** Production uses **query params** on **`/entity`:** `type`, `id`, optional **`edit=1`**. Links must use **`getEntityPath`** from `lib/entities/paths.ts`. Path-segment URLs exist under **`/debug/entity/…`** only; a path-based `(app)/entity/[type]/[id]` route may land later (see **[entity-view-edit-plan.md](entity-view-edit-plan.md)**).
- **Header:** Event type title + icon + tag from entity config. **DetailPageHeader** with **DetailEditButton** (right, baseline-aligned).
- **Metadata:** Date, time, status, response deadline, conductor, location (full address). Buttons: “In Google Maps öffnen”, “In Apple Maps öffnen” (or equivalent).
- **Participation widget:** Yes / Maybe / No with correct colors (green, orange, red). Submission via API.
- **Participation overview:** Bar showing counts by status (e.g. 10 green, 2 red, 13 grey).
- **Participants:** “Group by: Category | Instrument”. Group headers with summary bars; rows with initials, status icon (clock = pending, check = confirmed). Components: `ParticipationDiagram`, `ParticipantOverview`.
- **Delete:** For entity types that support delete (e.g. location, equipment, outfit, song, vote), delete is only in the **edit view**: **DetailDeleteSection** at the bottom with a Delete button that opens **ConfirmModal** (confirm/cancel). Shown only when the user has delete rights (`canDelete`). See [UI_PATTERNS.md](UI_PATTERNS.md).

---

## 9. Internationalization

- **Languages:** DE, EN, ES, FR. Files in `lang/*.json` (e.g. `en.json`, `de.json`). API can serve translations; frontend may load from `lang/` or API.
- **Keys:** Format `js.{module}.{key}` (e.g. `js.dashboard.welcome`, `js.users.title`). Placeholder `%p` for dynamic text (e.g. company name in subtitle).
- **Adding keys:** Add to all `lang/*.json` files so no key appears raw in UI. No hardcoded user-facing strings in code.

---

## 10. Theme

- **Dark/light:** Toggle in top bar. CSS variables in `frontend/app/globals.css`; theme class (e.g. `.dark`) or media/attribute. Persist choice (e.g. localStorage) and apply on load.
- **Variables:** Use semantic names (`--background`, `--foreground`, `--primary`, `--muted`, `--border`, `--destructive`, etc.) so components stay theme-agnostic.

---

## 11. API

- **Base URL:** In dev, `/api/*` is proxied to PHP backend (e.g. `NEXT_PUBLIC_API_BASE` or default `http://localhost:8888/Bnote/bnote-next-generation`). For static export, same origin or configure proxy so `/api` hits PHP.
- **Auth:** Session cookie; requests are same-origin or credentials included so cookie is sent.
- **Main endpoints used by UI:**  
  - Auth: login, session/me, logout, getPublicConfig, getRegistrationOptions, register, requestPasswordReset, completePasswordReset, participation token helpers as needed.  
  - Dashboard: company, events, events needing response, admin/action summaries.  
  - Users: list, get, create, update, delete, activate, getPrivileges, updatePrivileges, getContacts.  
  - Contacts: list, get, create, update, delete, getGroups, getIntegrationBundle, integrate.  
  - Rehearsals / concerts / calendar / appointments / reservations: per module.  
  - Tasks, votes, comments, news, share: per module.  
  - Search: search (with filters).  
  - Participation: get participation, set participation.  
  - Translations: list of keys or full locale JSON.
- **Module routes:** API returns sidebar modules with `route` (e.g. `/dashboard`, `/users`, `/contacts`). Frontend uses these as Next.js paths (e.g. `dashboard/index.html`, `users/index.html` in static export).
- **Transactional email (PHP):** When contacts are added to rehearsals/concerts (participation invites), when tasks are assigned/updated, and when entity discussion notifications fire, mail uses **`NextGenMailPolicy`**: contacts **without** a BNote user still receive mail when appropriate; contacts linked only to an **inactive** user do **not**; active users follow the **`email_notification`** preference. Invites include participation magic-link URLs when configured. Details: **[MAIL.md](MAIL.md)** (subsystem map and “Transactional mail: who receives it”).

---

## 12. Feature Checklist (Regression)

- **Branding:** Logo and “BNote” in sidebar/mobile/login; favicon if required.
- **Dashboard:** Greeting + company subtitle; events needing response; event cards; Quick Actions (if enabled); filters.
- **Users:** Title/subtitle; Add User; search; sortable table (no ID column; correct last-login date sort); Active/Inactive pills; row actions only Privileges and Activate/Deactivate (no Edit/Delete in list).
- **Contacts:** Title/subtitle; Phase in + Add Contact in header; group tabs; search; sortable table (no ID column; no Edit/Delete in list); row click opens detail/edit flow; integration page uses event-style rows (icon, date, time, location) and Avatars for members.
- **Dashboard (admin):** Pending accounts tile and action count use the “inactive + unintegrated” rule above, not all inactive users.
- **Lists (all modules):** No ID column; no Edit (pencil) or Delete (trash) in list rows. See [UI_PATTERNS.md](UI_PATTERNS.md).
- **Search:** Top-bar overlay; search results page with query in URL; filters; result list with type icon, date, tag, time, location.
- **Entity detail:** Path-based view/edit; type title + icon + tag; metadata; map links; participation widget; overview bar; participants with group-by and status icons. Edit mode in URL; delete only in edit view (DetailDeleteSection + ConfirmModal).
- **Shell:** Sidebar, mobile drawer, top bar (search, theme, user); logo everywhere.
- **Translations:** No “[module module]” or raw keys; company name in subtitle; all strings from lang/ or API.
- **Icons:** All entity types and actions use icons from `entity-config` + `icons.tsx`; logo loads; no broken placeholders.
- **Mobile:** Drawer, search overlay, compact event cards, no layout breaks.
