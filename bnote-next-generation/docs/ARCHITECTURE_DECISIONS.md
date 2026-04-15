# BNote Next Generation - Architecture Decisions

This file records explicit architecture decisions that affect permissions, data access, and cross-module behavior.

---

## AD-2026-03-31: Merge `Mitspieler` Access Into Contacts Route

- **Status:** Accepted
- **Date:** 2026-03-31
- **Scope:** Contacts module permissions and runtime behavior

### Context

Legacy BNote had a separate `Mitspieler` module for members-only contact visibility. In Next Gen, we want to avoid duplicate modules/routes while preserving permission boundaries and readonly behavior for members.

### Decision

Use a single UI route (`/contacts`) for both contact-related permissions, with runtime access profiles:

- **`Kontakte` permission** -> full contacts mode
  - all groups visible
  - group filters visible
  - top actions visible (`Manage Groups`, `Phase in`, `Add Contact`)
  - contact edit-related actions available
- **`Mitspieler`-only permission** -> members-only readonly mode
  - only members/default group visible (legacy `GROUP_MEMBER`, id 2)
  - no group filter UI
  - no top edit/manage actions
  - no contact edit/remove actions
  - write actions blocked by backend authorization

Users with neither permission cannot access contacts endpoints.

### Implementation Notes

- **Backend**
  - `api/modules/contacts.php`
    - accepts either `Kontakte` or `Mitspieler` for access
    - computes access profile (`canManageContacts`, `membersOnlyAccess`, `membersGroupId`)
    - enforces readonly for members-only users at action-dispatch level
    - restricts list/group responses to the members group in members-only mode
  - `api/modules/auth.php`
    - maps `Mitspieler` to `/contacts`
    - de-duplicates sidebar modules by route so Contacts appears once
- **Frontend**
  - `frontend/lib/contacts-api.ts` adds `getAccessProfile()`
  - `frontend/app/(app)/contacts/page.tsx` adapts action/filter UI by access profile
  - `frontend/components/entities/contact/ContactDetail.tsx` hides edit/remove actions for members-only mode

### Consequences

- **Pros**
  - one canonical contacts route and UX surface
  - no separate members module to maintain
  - backend-enforced readonly protections for members-only users
- **Cons**
  - contacts route now has permission-profile branching
  - tests and docs must cover both profiles to prevent regressions

### Guardrails

- Do not reintroduce a separate `/members` route unless this decision is superseded.
- Any new contacts write endpoint must remain blocked for members-only mode.
- Any new contacts UI action must check access profile before rendering.
