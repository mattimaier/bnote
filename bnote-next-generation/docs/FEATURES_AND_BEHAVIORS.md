# BNote Next Generation – Features and Behaviors

This document describes each feature and expected behavior of the Next.js app. Use it for regression checks and bug fixing.

---

## 1. Overview

- **App:** Next.js UI (static export) in `frontend/`, PHP REST API in `api/`.
- **Entry points:** `/` (redirects to `/dashboard` or `/login`), `/login`, `/dashboard`, `/users`, `/contacts`, `/search`, `/entity` (entity detail).
- **Auth:** Session-based; API uses PHP session cookie. Unauthenticated users are redirected to `/login?redirect=…`.

---

## 2. Authentication

- **Login flow:** User submits username and password on `/login`. API `POST /api/v1/auth/login` creates session. On success, redirect to `redirect` query param or `/dashboard`.
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

- **Greeting:** Time-based greeting (e.g. Good morning) + user name; state and effect must run before any early returns (hooks order).
- **Company subtitle:** Band/company name from API `company`; use `normalizeCompany()` so SimpleXMLElement/cast issues are handled. Translation key `js.dashboard.subtitle` with `%p` for company.
- **Quick actions:** Card with quick action buttons; currently visible (product decision: was hidden in legacy UI).
- **Events needing response:** Section listing events where the user has not yet responded. Event cards show date, title, type tag, time, location; link to entity detail.
- **Event cards:** Desktop: timeline + card layout. Mobile: compact list item without timeline.
- **Filters:** By type (rehearsal/concert), year, month if applicable.

---

## 5. Users

- **Route:** `/users`. Page: `app/(app)/users/page.tsx`.
- **Title/subtitle:** i18n `js.users.title`, `js.users.subtitle`.
- **Actions:** “Add User” (primary), optional GDPR (shield) if implemented.
- **Search:** Client-side filter by login, first name, last name.
- **Table:** Columns: ID, Login, First name, Last name, Status, Last login, Actions. All columns sortable; **date sorting for Last login** uses timestamp comparison (null/missing sorts to end in asc).
- **Status pills:** Active = amber; Inactive = red (destructive-style).
- **Row actions:** Edit (pencil), Manage privileges (key), Activate/Deactivate (X/check), Delete (trash, destructive). Icons: Pencil, Key, XCircle, CheckCircle, Trash2.
- **Modals:** Add user (login, password, contact, isActive); Edit user (password optional, contact, isActive); Manage privileges (checkboxes per module).

---

## 6. Contacts

- **Route:** `/contacts`. Page: `app/(app)/contacts/page.tsx`.
- **Title/subtitle:** i18n `js.contacts.title`, `js.contacts.subtitle`.
- **Actions:** “Add Contact” (primary). Secondary (Integration, Groups, Print, vCard, Datenschutz) as implemented.
- **Filter tabs:** “All” + group tabs from API; selection filters list.
- **Search:** Client-side filter by name, surname, nickname, email.
- **Table:** Columns: ID, First name, Last name, Nickname, Instrument, Email, Phone, City, Actions. All columns sortable (string/number comparators).
- **Row click:** Opens edit modal. Action buttons: Edit, Delete.
- **Modals:** Add/Edit contact form: name, surname, nickname, email, phone, mobile, street, zip, city, notes, groups (checkboxes).

---

## 7. Search

- **Top-bar search:** Typing shows overlay with categorized results (e.g. Konzerte, Proben). Overlay styled like search results page: type icon, date, tag, location; primary-colored separator.
- **Search results page:** Route `/search` with query in URL. Summary: “Suche: {query} • {count} Ergebnisse”. Filters: type (Rehearsal/Performance), Year, Month.
- **Result list:** Grouped by type (e.g. “Proben (373)”). Each row: vertical line, type icon (from entity config), date, description, type tag (pill), time, location (map-pin). Entity config: `frontend/config/entity-config.json`; icons/colors via `lib/entity-config.ts` (e.g. getEventTypeConfig, getPillStyle). Icon name “trumpet” mapped to Music in `icons.tsx` if needed.

---

## 8. Entity Detail

- **Route:** `/entity` with query params (e.g. `id`, `entity` type, `module` for back-context). Page: `app/(app)/entity/page.tsx`.
- **Header:** Event type title + icon + tag from entity config.
- **Metadata:** Date, time, status, response deadline, conductor, location (full address). Buttons: “In Google Maps öffnen”, “In Apple Maps öffnen” (or equivalent).
- **Participation widget:** Yes / Maybe / No with correct colors (green, orange, red). Submission via API.
- **Participation overview:** Bar showing counts by status (e.g. 10 green, 2 red, 13 grey).
- **Participants:** “Group by: Category | Instrument”. Group headers with summary bars; rows with initials, status icon (clock = pending, check = confirmed). Components: `ParticipationDiagram`, `ParticipantOverview`.

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
  - Auth: login, me, logout.  
  - Dashboard: company, events, events needing response.  
  - Users: list, get, create, update, delete, activate, getPrivileges, updatePrivileges, getContacts.  
  - Contacts: list, get, create, update, delete, getGroups.  
  - Search: search (with filters).  
  - Participation: get participation, set participation.  
  - Translations: list of keys or full locale JSON.
- **Module routes:** API returns sidebar modules with `route` (e.g. `/dashboard`, `/users`, `/contacts`). Frontend uses these as Next.js paths (e.g. `dashboard/index.html`, `users/index.html` in static export).

---

## 12. Feature Checklist (Regression)

- **Branding:** Logo and “BNote” in sidebar/mobile/login; favicon if required.
- **Dashboard:** Greeting + company subtitle; events needing response; event cards; Quick Actions (if enabled); filters.
- **Users:** Title/subtitle; Add User; search; sortable table (including correct last-login date sort); Active/Inactive pills; row actions (Edit, Privileges, Activate/Deactivate, Delete).
- **Contacts:** Title/subtitle; Add Contact; group tabs; search; sortable table; row click to edit; Edit/Delete.
- **Search:** Top-bar overlay; search results page with query in URL; filters; result list with type icon, date, tag, time, location.
- **Entity detail:** Type title + icon + tag; metadata; map links; participation widget; overview bar; participants with group-by and status icons.
- **Shell:** Sidebar, mobile drawer, top bar (search, theme, user); logo everywhere.
- **Translations:** No “[module module]” or raw keys; company name in subtitle; all strings from lang/ or API.
- **Icons:** All entity types and actions use icons from `entity-config` + `icons.tsx`; logo loads; no broken placeholders.
- **Mobile:** Drawer, search overlay, compact event cards, no layout breaks.
