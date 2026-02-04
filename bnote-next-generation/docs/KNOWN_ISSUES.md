# Known Issues

This file tracks current known issues discovered during ongoing refactors and QA. Update as fixes land.

---

## Repertoire save errors (legacy backend)

**Symptoms**
- Save request for repertoire update returns `500` with:
  - `Internal server error: The number of variables must match the number of parameters in the prepared statement`
  - PHP warning: `Uninitialized string offset 1` in `BNote/src/data/database.php` (line 94)

**Example request payload**
```json
{"id":34,"title":"A Tribute to the Duke","length":"00:06:00","genre":15,"bpm":123,"music_key":"123","composer":"Sammy Nestico","status":4,"is_active":true,"action":"update"}
```

**Notes**
- The error originates in the legacy backend (outside this repo):
  - `/Users/stefan/dev/bnote/BNote/src/data/database.php:94`
- Likely a SQL bind mismatch for optional fields (e.g., `status`, `genre`, `bpm`).
- Frontend changes alone cannot fully resolve this.

---

## Repertoire update warning (legacy backend)

**Symptoms**
- PHP warning when saving repertoire:
  - `Undefined array key "bpm"` in `BNote/src/data/modules/repertoiredata.php` (line 110)

**Notes**
- The warning originates in the legacy backend (outside this repo):
  - `/Users/stefan/dev/bnote/BNote/src/data/modules/repertoiredata.php:110`
- Backend should guard missing keys (e.g., `$_POST["bpm"] ?? null`).

---

## Users contact id missing in legacy API (mitigated)

**Symptoms**
- `users/get` returned `contact: 0` while `contactName`, `contactFirstName`, and `contactSurname` were populated.
- User detail/edit could not link or preselect the correct contact without an id.

**Mitigation**
- Backend API updated in `api/modules/users.php` to use an explicit join query so `contact` id is returned reliably.
- Frontend still keeps a best-effort fallback:
  - If `contact === 0`, it tries to resolve the contact id by matching **first + last name** against `users/getContacts`.
  - It only assigns the id when there is exactly one match; otherwise it leaves the contact unlinked.

**Notes**
- Long-term fix should remain in backend (return the contact id in `users/get`).
