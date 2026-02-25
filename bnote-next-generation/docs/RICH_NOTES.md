# Rich Notes Architecture

The next-generation app uses an **EditorJS** rich-text editor for notes. The legacy BNote app expects **plain text** in the existing `notes` columns and validates with `Regex::isText()`, which rejects JSON. To keep both UIs working, we use two stores.

## Two stores

| Store | Content | Used by |
|-------|--------|--------|
| **Existing `notes` columns** (e.g. `rehearsal.notes`, `contact.notes`) | Plain text only | Old app (read/write); new app sends plain on create/update |
| **`rich_notes` table** | EditorJS JSON | New app only (read/write via rich-notes API) |

The **single source of truth for “display in the old app”** is the existing `notes` column: it must always contain plain text. When the new app saves notes, it sends plain text to the normal create/update APIs and stores the raw EditorJS JSON in `rich_notes` via a dedicated API.

## Table creation

The `rich_notes` table is **created on first use** when any request hits the rich-notes API. No manual migration or `update_db.php` step is required. See `api/rich_notes_helper.php` and `api/modules/richnotes.php`.

## Load / save flow (new app)

- **Load**
  - Fetch the entity (e.g. rehearsal, contact) from the existing API → you get `notes` (plain).
  - Call the rich-notes API: GET with `entity_type` and `entity_id`.
  - If the rich-notes API returns 200 with non-empty `content`, use that JSON as the EditorJS initial data.
  - Otherwise, use `notes` as plain text (e.g. single paragraph) for the editor.

- **Save**
  - Compute plain text from the editor: `editorJsonToPlainText(editorValue)`.
  - Call the existing create/update API with `notes: plain` (and other fields). This keeps the DB and old app in sync.
  - Call the rich-notes API POST with the raw EditorJS JSON so the new app can load rich content again later.

So: **DB `notes` = plain only; `rich_notes` table = JSON only; old app only reads `notes`.**

## Sync rule and edge case

When the **new app** saves notes, it always writes (1) plain text to the entity’s `notes` column and (2) JSON to `rich_notes`. When the **new app** loads notes, it uses `rich_notes` if a row exists for that entity, otherwise it uses `notes` (plain). When the **old app** edits notes, only `notes` is updated; `rich_notes` is not touched, so the new app may show the previous rich content until the next save from the new app. This is an accepted trade-off.

## Orphan cleanup

When an entity is deleted (contact, equipment, location, song, etc.), the corresponding row in `rich_notes` should be removed. The API modules that perform delete (contacts, equipment, locations, repertoire) call `rich_notes_delete_for_entity($entityType, $entityId)` after a successful delete. See `api/rich_notes_helper.php`. Rehearsals/concerts currently have no delete in the API; if delete is added later, add the same cleanup there (and for `rehearsal_song` use composite id `rehearsalId_songId`).

## Entity types and IDs

- **Simple entities:** `entity_type` = `rehearsal`, `concert`, `contact`, `song`, `equipment`, `location`, `program`, `news`; `entity_id` = the entity’s primary key (e.g. `"123"`).
- **rehearsal_song:** `entity_type` = `rehearsal_song`, `entity_id` = `"{rehearsalId}_{songId}"` (e.g. `"123_456"`).
- **News:** single global content → `entity_type` = `news`, `entity_id` = `"0"`.
- **Concert conditions:** `entity_type` = `concert_conditions`, `entity_id` = concert id.

## Code references

- **Backend:** `api/rich_notes_helper.php`, `api/modules/richnotes.php`
- **Frontend:** `lib/editorjs-notes.ts` (`editorJsonToPlainText`), `lib/rich-notes-api.ts` (`getRichNotes`, `saveRichNotes`); entity edit components (EventDetail, ContactEdit, EquipmentEdit, LocationEdit, SongEdit, profile page, news page) load/save plain + rich as described above.
