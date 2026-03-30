# Entity View/Edit Refactor Plan (Unified)

Date: 2026-02-04

This document merges the original refactor plan, the feasibility review, edge cases, executable steps, and the product decisions into a single executable plan.

---

## 1. Goals

- Move edit mode into the URL using path-based routes.
- Split the monolithic event detail page into reusable components and logic.
- Provide a shared entity-detail foundation with customization hooks.
- Add debug pages per entity with mocked data (discoverable from the **`/developer`** hub when developer tools are enabled).
- Preserve and share UX patterns across entities.
- Support creating new entities in the edit UI with safe error handling.
- Include special handling for pickers used in rehearsal/concert pages.
- Support special per-entity actions in view and edit modes (based on old app).
- Preserve the latest layout/UI changes from the most recent commit.
- Entity detail pages link to related entities (contacts, users, locations, etc.) when the user has view rights.

---

## 2. Decisions (Locked In)

| # | Decision | Meaning |
|---|----------|--------|
| 1 | **Create (A)** | Implement create **UI + client contract** (form, Create/Save/Cancel, error preservation). Assume future `rehearsals.create` / `concerts.create` API or use a stub until backend exists. |
| 2 | **Non-event types (C)** | For `/entity/[type]/[id]` when type is not rehearsal/concert: **redirect to type-specific list/detail** (e.g. `/contacts`, `/users?id=5`) so "open entity" works with current UX. |
| 3 | **Special actions (A)** | **Placeholder UI only.** Style like **dashboard quick actions**: grid of cards with icon, title, description, href or "Coming soon". |
| 4 | **Debug mocks (A)** | Mock data must use the **same API response shape** as real rehearsals/concerts so the same EventDetail and types work without branching. |
| 5 | **Permissions (Yes)** | View rights come from existing **module permissions** in the old app. **Exception:** rehearsals and concerts – **read is not gated by module**; only write (getMeta, update) requires Proben/Konzerte. |

---

## 3. Entity Coverage and Scope

**Backend API (as of 2026-03):** The Next Gen PHP API exposes many modules under `api/modules/` — not only the list below. **Authoritative inventory:** files in [`api/modules/`](../api/modules/) (e.g. `auth`, `dashboard`, `users`, `contacts`, `rehearsals`, `concerts`, `participation`, `search`, `tasks`, `comments`, `votes`, `news`, `share`, `calendar`, `appointments`, `reservations`, `repertoire`, `equipment`, `outfits`, `locations`, `kontaktdaten`, `translations`). This plan’s **UI scope** for unified entity detail/edit remains focused on **rehearsal** and **concert** first; other types follow the redirect / module-specific rules in later sections.

**Search categories returned:** `rehearsals`, `concerts`, `users`, `contacts`, `tasks`, `repertoire`, `locations`.

**Configured in UI config:** `rehearsal`, `concert`, `contact`, `location`, `user`, `song`, `repertoire`, `task`, `meeting`, `appointment`, `equipment`, `tour` (plus plural variants).

**Implementation scope (this refactor):** Full detail + edit only for **rehearsal** and **concert**. Other types (contact, user, location) are supported only as **redirect targets** when opened from entity links (see Cross-entity links and Non-event redirects below).

---

## 4. Route Changes

### New routes

- View: `app/(app)/entity/[type]/[id]/page.tsx`
- Edit: `app/(app)/entity/[type]/[id]/edit/page.tsx`

### URL helper

Create `lib/entities/paths.ts`:

- `getEntityPath(type: string, id: string | number, mode?: "view" | "edit"): string`
- Rules: `mode === "edit"` → `/entity/${type}/${id}/edit`; else → `/entity/${type}/${id}`.
- **Do not** add basePath inside this function (Next.js applies basePath to `Link`/`router` automatically). For full URLs (e.g. email), caller can use `prefixPath(getEntityPath(...))`.
- Export type: `EventEntityType = "rehearsal" | "concert"`.

### Replace existing query links

- `components/EventCard.tsx`: use `getEntityPath(entityType, event.oid)`.
- `components/SearchAutocompleteOverlay.tsx`: for event results only, use `getEntityPath(entityType, oid)`. Leave users/contacts/tasks/locations/repertoire hrefs unchanged.
- Event detail: Edit button → `getEntityPath(type, id, "edit")`; Cancel/Save success → `getEntityPath(type, id, "view")`.

### Legacy URL redirect

Keep a single legacy route at `app/(app)/entity/page.tsx` that only reads query params. On mount: if `type` is rehearsal/concert and `id` is present and not `"new"`, call `router.replace(getEntityPath(type, id, edit === "1" ? "edit" : "view"))`, then render "Redirecting…" or null. Invalid type/id → redirect to dashboard. No duplicate page logic; static export means redirect is client-side.

### Non-event types: redirect to list/detail (Decision 2C)

For `type` not in `["rehearsal", "concert"]`, the entity route handler must **redirect** so cross-entity links still work:

- `contact` → `/contacts` (or `/contacts?id=...` when contacts page supports it).
- `user` → `/users?id={id}`.
- `location` → `/contacts` or a dedicated locations route; else dashboard.

Maintain a small map: **entity type → { pathname, query? }** (e.g. in `lib/entities/paths.ts` or `lib/entities/redirects.ts`). Use it in both view and edit entity pages so one place defines redirect targets.

### Cross-entity links from detail pages

Entity detail (e.g. rehearsal, concert) shows related entities: participants (contact/user), location, conductor, program, outfit, equipment, event contacts. These must be **links** when the user has view rights for that entity type; otherwise plain text.

- Use **`getEntityPath(relatedType, relatedId)`** for every related-entity link.
- **Rights:** Implement `canViewEntityType(type: string): boolean` from existing **getModules** (auth, getModules). Map entity type → backend module name (`contact` → `Kontakte`, `user` → `User`). For **rehearsal** and **concert**, treat "can view" as **true** (read not gated by module). For others, true iff the user’s modules list contains that module name. Use a hook or context that reuses the same getModules data as the sidebar; add map in `lib/entity-config.ts` or `lib/entities/permissions.ts`.
- Apply to: participants, location, conductor, program, outfit, equipment, event contacts. No hardcoded `/contacts` or `/entity/...` for these; always `getEntityPath` so redirects stay in one place.

---

## 5. Event Detail Split

### New folders

- `components/entities/event/`
- `lib/entities/event/`

### New files

**Components**

- `components/entities/event/EventDetail.tsx` (main detail renderer)
- `components/entities/event/ParticipationTrafficLight.tsx`
- `components/entities/event/MultiSelect.tsx`
- `components/entities/event/SelectPicker.tsx`
- `components/entities/event/SelectedItemsList.tsx` (chips + remove; used for groups, equipment, contacts)
- `components/entities/event/StatusPicker.tsx`
- `components/entities/event/ParticipantEditor.tsx`

**Logic/types**

- `lib/entities/event/types.ts` (all interfaces: LocationObj, ConductorObj, SimpleOption, RehearsalMeta, ConcertMeta, EditableParticipant, EditableSong, form shape, etc.)
- (Optional) `lib/entities/event/api.ts`
- (Optional) `lib/entities/event/adapters.ts` (buildForm, mapParticipationToStatus, mapStatusToParticipation, payload building)

### EventDetail dependencies

- UI: ParticipationWidget, ParticipationDiagram, ParticipantOverview, AddressLink, MarkdownText
- Lib: api, event-utils, entity-config, date-time, address-utils, string-utils, lib/entities/paths, lib/entities/event/types

---

## 6. UI/Layout and Picker Behavior to Preserve

Source: latest commit ("Update event edit UI and metadata").

- Sticky edit bar at top in edit mode (Save/Cancel + "Editing" label).
- Edit mode driven by **route** (`/edit`), not query.
- Edit button primary style in header; participation widget hidden during edit.
- Status picker: inline control with label mapping and pill styling.
- Metadata sections: keep current layout and text labels.
- MultiSelect: chips, subtitles, full-screen overlay for large option sets, all label props.
- SelectPicker: search, empty label, loading state, label overrides.
- Participation diagram "pending" and ParticipantOverview "pending" styling.

**Picker safety:** Options from `meta`; use `?? []`. Keep current selection when options load late; do not overwrite form when meta arrives.

---

## 7. Create Mode (Decision 1A)

- Support **create** on edit route with `id === "new"` for rehearsal and concert. No load; empty or default form. Button label "Create" when `isNew`, "Save" otherwise.
- On success: call create API (or stub). If API returns new id, navigate to `getEntityPath(type, newId, "view")`; else redirect to dashboard/list.
- **Backend:** No create in rehearsals/concerts API today. Frontend uses a **stub** (e.g. `api.post(module, "create", payload)` that returns `{ id: 0 }` or is skipped) until backend adds create. Document that backend must add `create` action.
- On validation or API error: preserve form state; show top-level error (and optionally field-level). Sticky bar stays; user can fix and retry.

---

## 8. Special Actions (Decision 3A)

- **Actions registry** per entity: `viewActions[]`, optionally `editActions[]`. Each action: `id`, `titleKey`, `descKey`, `icon`, `href`, `comingSoon?: boolean`.
- **Placeholder UI:** Same pattern as **dashboard quick actions** (grid of cards: icon, titleKey, descKey, colorClass, href). First version: all actions can have `href: "#"` and `comingSoon: true` (or "Coming soon" badge). Render on event detail (view mode). No backend calls for actions in this phase.
- Reference: Rehearsals (series add/edit, participant overview, history); Concerts (overview, programs, history).

---

## 9. Debug Pages and Mocks (Decision 4A)

- Routes: `app/debug/entity/[type]/[id]/page.tsx` and `.../edit/page.tsx`.
- Mock data: `lib/entities/debug/mock-data.ts` with payloads that **match the same API response shape** as `getRehearsal` and `getConcert` (same fields and structure). Debug pages pass mock data into the same EventDetail; no "isDebug" branching in the component.

---

## 10. Customization Hooks (Later)

Optional render overrides: `renderHeader`, `renderSections`, `renderSidebar`, `renderExtraPanels`. Optional behavior: `validate(form)`, `beforeSave(form)`, `readonlyFields` / `fieldVisibility`. Document under `components/entities/` when added.

---

## 11. Edge Cases and Guards

- **`id === "new"`:** Create mode; only for rehearsal/concert.
- **Invalid or missing type/id:** Guard at top of entity pages; if type not rehearsal/concert, redirect using the non-event redirect map (or dashboard). Non-numeric id (except `"new"`) → no load (keep current `isNaN(numId)` check).
- **Edit route without canEdit:** Redirect to view route `getEntityPath(type, id, "view")`.
- **Empty meta:** Use `?? []` for picker options.
- **Static export:** All redirects are client-side (no server).

---

## 12. Executable Implementation Order

### Phase A – Paths and routes

1. Add `lib/entities/paths.ts` (getEntityPath, EventEntityType). Add non-event redirect map (type → pathname/query).
2. Add `app/(app)/entity/[type]/[id]/page.tsx` and `.../edit/page.tsx`. Both read params; if type not rehearsal/concert, redirect using map. Edit page: if `id === "new"`, treat as create (Phase C).
3. In existing `app/(app)/entity/page.tsx`: redirect legacy query URL to path-based URL; then reduce to redirect-only (no duplicate logic).
4. EventCard, SearchAutocompleteOverlay (events only), and entity detail edit/cancel/save: use `getEntityPath`.

### Phase B – Event detail split

5. Create `components/entities/event/` and `lib/entities/event/`. Add `lib/entities/event/types.ts`; optionally api.ts, adapters.ts.
6. Extract in order: ParticipationTrafficLight, MultiSelect, SelectPicker, SelectedItemsList, StatusPicker, ParticipantEditor, then EventDetail (using extracted components and types).
7. Wire `entity/[type]/[id]/page.tsx` and `.../edit/page.tsx` to EventDetail (load data/meta as today; pass mode, canEdit, callbacks). Preserve sticky bar, participation visibility, all sections/labels.
8. **Cross-entity links:** In EventDetail, replace related-entity labels with conditional `<Link href={getEntityPath(...)}>` when `canViewEntityType(relatedType)` else text. Add `canViewEntityType` (hook or helper) from getModules + entityType → module name map; rehearsal/concert view always true.
9. Strip legacy entity page down to redirect-only.

### Phase C – Create mode and errors

10. Create mode: empty form for `id === "new"`, "Create" button, stub create API, navigate on success, error preservation.
11. Error preservation: do not clear form on validation/API error; top-level (and optional field-level) error display.

### Phase D – Actions and debug

12. Actions registry + placeholder UI (grid like dashboard quick actions) on event detail view.
13. Debug routes + `lib/entities/debug/mock-data.ts` (same shape as real API); debug pages use same EventDetail.
14. (Later) Customization hooks on EventDetail and documentation.

---

## 13. Checklist Before Done

- [ ] All event links use `getEntityPath` (EventCard, SearchAutocompleteOverlay, entity edit/cancel/save).
- [ ] Legacy `/entity?type=…&id=…` and `&edit=1` redirect to path-based URL.
- [ ] Only rehearsal/concert get full EventDetail; other types redirect per map.
- [ ] Edit mode driven only by route (`/edit`).
- [ ] Sticky edit bar, pickers (MultiSelect, SelectPicker, SelectedItemsList, StatusPicker), participation behavior and labels unchanged.
- [ ] Create mode (UI + stub) and error preservation implemented.
- [ ] Cross-entity links use `getEntityPath` when `canViewEntityType`; otherwise plain text; permissions from getModules + rehearsal/concert exception.
- [ ] Placeholder actions UI (quick-actions style) on event detail.
- [ ] Debug mocks match API shape; debug pages use same EventDetail.
- [ ] Legacy entity page is redirect-only; no duplicate components.

---

## 14. File Change Summary

| Action | Path |
|--------|------|
| Add | `lib/entities/paths.ts` (and redirect map) |
| Add | `lib/entities/permissions.ts` or extend entity-config (canViewEntityType + module map) |
| Add | `app/(app)/entity/[type]/[id]/page.tsx` |
| Add | `app/(app)/entity/[type]/[id]/edit/page.tsx` |
| Change | `app/(app)/entity/page.tsx` → redirect-only |
| Change | `components/EventCard.tsx` |
| Change | `components/SearchAutocompleteOverlay.tsx` (events only) |
| Add | `lib/entities/event/types.ts` (optional: api.ts, adapters.ts) |
| Add | `components/entities/event/ParticipationTrafficLight.tsx` |
| Add | `components/entities/event/MultiSelect.tsx` |
| Add | `components/entities/event/SelectPicker.tsx` |
| Add | `components/entities/event/SelectedItemsList.tsx` |
| Add | `components/entities/event/StatusPicker.tsx` |
| Add | `components/entities/event/ParticipantEditor.tsx` |
| Add | `components/entities/event/EventDetail.tsx` |
| Add | `app/debug/entity/[type]/[id]/page.tsx`, `.../edit/page.tsx` |
| Add | `lib/entities/debug/mock-data.ts` |

---

## 15. Notes

- Plan is intentionally breaking: old query-based entity URLs are redirected, not kept.
- Search and dashboard use new paths after Phase A.
- Rehearsals/concerts: read access is not gated by module (last commit); write is. Other entities: view gated by module (getModules).
