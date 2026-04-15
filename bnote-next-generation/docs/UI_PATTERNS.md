# BNote Next Generation – UI Patterns

This document describes the shared UI patterns for lists, detail views, edit mode, and delete. All modules and entity pages must follow these patterns for consistent behavior and optics.

---

## 1. List and Table Behavior

**Rule:** Module and entity **lists do not show** an ID column, and do **not** show Edit (pencil) or Delete (trash) buttons in the row.

- **Tables:** Sortable columns as needed (e.g. name, date, status). **No ID column.** Row click or primary action (e.g. opening an item) navigates to the **detail/view** or opens the edit context (e.g. modal for users/contacts).
- **Edit/Delete:** Editing and deleting are done in the **detail or edit view**, not from the list row. Users open an item (e.g. click row or link) to view it, then use the header Edit button to enter edit mode; delete is at the bottom of the edit form with a confirm step.
- **Optional row actions:** Other actions (e.g. Manage privileges, Activate/Deactivate on users) may remain in the list if they are not “edit entity” or “delete entity.” Pencil and Trash must not appear in list rows.

Applies to: Users, Contacts, Locations, Equipment, Outfits, Repertoire (songs), Votes, Rehearsals, Concerts (and any future list pages).

---

## 2. Edit Mode in the URL

**Rule:** Edit mode is **represented in the URL** so that refresh, back/forward, and sharing work correctly. The URL is the source of truth; do not drive edit mode from component state alone.

### Entities (rehearsal, concert, location, equipment, etc.)

- **View (shipped app):** `/entity?type={type}&id={id}` — e.g. `/entity?type=location&id=5`. **Edit:** same with **`&edit=1`** (see **`(app)/entity/page.tsx`**).
- Use **`getEntityPath(type, id, "view" | "edit")`** from `lib/entities/paths.ts`; it builds these query URLs (do not hand-roll query strings).
- The detail component reads **`type`**, **`id`**, and **`edit`** from the URL (see **`useEntityParams`**). Use **`router.push` / `router.replace`** when toggling edit or after save/cancel.
- **Debug only:** Path-shaped URLs exist under **`/debug/entity/[type]/[id]`** for development (source: **`app/(dev)/debug/`**). The **`/developer`** hub and **`/debug/*`** are available only to **admin** users (`session.isAdmin`) when developer tools are build-enabled (`next dev` or **`NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1`**). That hub is **English-only** (no `lang/*.json` keys). Production entry remains query-based via `/entity`.

### Profile (my contact data)

- **View:** `/profile/`
- **Edit:** `/profile/edit/`
- Route: `profile/edit/page.tsx` renders the same profile component. The component derives **`isEditing`** from **`usePathname()`** (e.g. pathname ends with `/edit` or `/edit/`) and navigates with **`router.push("/profile/edit/")`** for Edit and **`router.replace("/profile/")`** on Cancel/Save.

---

## 3. Detail and Edit Shared Components

All entity detail views and profile/edit screens use the **same** components and layout.

| Component            | Location                                     | Use                                                                                                                                              |
| -------------------- | -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **DetailPageHeader** | `components/DetailPageHeader.tsx`            | Every detail page: title (left), optional subtitle, optional right slot.                                                                         |
| **DetailEditButton** | same file                                    | Right slot when not editing. Edit button is always **right, baseline-aligned** with the title.                                                   |
| **EditingBar**       | `components/EditingBar.tsx`                  | Shown when in edit mode: Save/Cancel, optional form submit. Same styling everywhere.                                                             |
| **SelectPicker**     | `components/SelectPicker.tsx`                | Single-select in edit mode (instruments, conductors, locations, etc.). **Never** use native `<select>` for entity/profile pickers.               |
| **StatusPicker**     | `components/entities/event/StatusPicker.tsx` | Badge-style status picker in edit mode. Use for any status-like field (active/inactive, event status, finished state, etc.).                     |
| **DetailCard**       | `components/DetailCard.tsx`                  | Wrap all detail content (view and edit) in at least one card: rounded border, card background (“white boxes”). Same look as rehearsals/concerts. |

**Layout rules:**

- **Header:** Title + subtitle on the left; Edit (or other actions) on the right, **baseline-aligned** with the title.
- **Body:** Content (view or edit) sits inside at least one **DetailCard** so sections look consistent.
- **Edit mode:** **EditingBar** at top; **SelectPicker** for any single-select field.
- **Status fields:** Any status-like field must render as a **badge** in view mode and use **StatusPicker** (badge-style picker) in edit mode. Do not render status as plain text or a native `<select>`.
- **Status in tables:** When status-like fields appear in list/table rows (e.g., song status), render them as badges (not plain text).

---

## 4. Delete in Edit Mode

**Rule:** Deleting an entity is done **only from the edit view**, in a **section at the bottom** with a Delete button that opens a **confirm/cancel modal**. The Delete button is shown **only if the user has the right to delete** (when the backend or permissions provide that flag).

### Shared components

- **ConfirmModal** (`components/ConfirmModal.tsx`): Reusable confirm dialog (title, message, Cancel, Confirm). Use `variant="danger"` for delete. Supports async `onConfirm`.
- **DetailDeleteSection** (`components/DetailDeleteSection.tsx`): Renders a card at the bottom of the edit form with a short hint and a Delete button. Only rendered when **`canDelete`** is true. On Delete click → opens ConfirmModal → on confirm calls **`onDelete()`** (e.g. call API delete, show toast, redirect to list).

### Usage in entity edit pages

- **LocationEdit, EquipmentEdit, OutfitEdit, SongEdit, VoteEdit:** At the bottom of the page (after the main form), when **`!isNew`**, render **`<DetailDeleteSection canDelete={…} entityTitle={…} onDelete={…} />`**.
- **`onDelete`:** Call the entity’s API `delete(id)`, show a “Deleted” toast, then **`router.push("/{listPath}/")`** (e.g. `/locations/`, `/equipment/`, `/outfits/`, `/repertoire/`, `/votes/`).
- **`canDelete`:** Pass `true` when the user may delete (e.g. from API or permission). When the backend exposes a delete-permission flag, pass it here so the section is hidden when the user may not delete.

**Profile:** No delete section (user cannot “delete” their own profile/contact record in the same way).

---

## 5. Date and Time Formatting

**Rule:** All dates and times use the shared formatters; no ad-hoc formatting.

- Use **`lib/date-time.ts`** only: **`formatDateShort`**, **`formatDateTimeShort`**, **`formatTimeShort`**, or **`formatDateShortDisplay`** for date-only display (handles null/empty/`0000-00-00` and returns a consistent empty label like "—").
- Always pass the current **`lang`** from **`useI18n()`**.
- **Never** use `.slice(0, 10)`, raw `toLocaleDateString()`, or custom strings for user-visible dates.

---

## 6. Notes Fields (Markdown)

**Rule:** All notes fields across entities must render Markdown in view mode using **MarkdownText**.

- Always use `components/MarkdownText.tsx` for notes display.
- Do not render notes as raw text; Markdown is supported for every entity.
- If notes appear in tables or list rows, render them via `MarkdownText` as well (or use a truncated Markdown preview).

---

## 7. Translation Keys for Delete and Confirm

- **`js.common.confirmDeleteTitle`** – Modal title (e.g. “Delete?”).
- **`js.common.confirmDeleteMessage`** – Generic message (e.g. “This action cannot be undone.”).
- **`js.common.confirmDeleteMessageNamed`** – Message with entity name, use `%s` for the name.
- **`js.common.deleteSectionHint`** – Short hint in the delete section (e.g. “Permanently remove this item.”).
- **`js.common.delete`** – Label for the Delete button.
- **`js.common.cancel`** – Label for the Cancel button in the modal.

Add these to all `lang/*.json` files (de, en, es, fr).

---

## 8. Summary Checklist

- [ ] Lists: no ID column; no Edit (pencil) or Delete (trash) in rows.
- [ ] Edit mode in URL: entities use `/entity?type={type}&id={id}&edit=1`; profile uses `/profile/edit/`.
- [ ] Detail views: DetailPageHeader + DetailEditButton (right, baseline); body in DetailCard(s).
- [ ] Edit mode: EditingBar at top; SelectPicker for single-select (no native `<select>`).
- [ ] Status fields: render as badge in view mode; use StatusPicker in edit mode.
- [ ] Delete: only in edit view; DetailDeleteSection at bottom with ConfirmModal; only when `canDelete`.
- [ ] Dates: only `lib/date-time.ts` formatters with `lang` from `useI18n()`.
- [ ] Notes: render Markdown in view mode via `MarkdownText` for all entities.
