# Decision Log

## DL-001: Single Active Codebase Boundary
- Decision: Treat `bnote-next-generation/` as the only writable codebase for normal work; legacy `../BNote/` remains read-only.
- Rationale: Prevent regressions in legacy runtime while evolving Next Gen.
- Consequences: Features/bugfixes must be implemented in Next Gen API/frontend layer, not old UI code.
- Confidence: High
- Source: `.cursorrules`; `README.md`

## DL-002: i18n Coverage Is Mandatory
- Decision: No user-facing hardcoded strings for product UI; translations must be added to `de/en/es/fr`.
- Rationale: Repeated transcript corrections showed missing translations regress quickly.
- Consequences: Every UI text change must include locale updates and key verification.
- Confidence: High
- Source: `.cursorrules`; transcript `0160b66c-f29c-4fdf-b794-095209368ea0`; `docs/FEATURES_AND_BEHAVIORS.md`

## DL-003: Mobile Quality Is a Delivery Requirement
- Decision: UI tasks are not done until mobile and desktop checks pass.
- Rationale: Recurrent mobile-specific regressions in topbar, spacing, readability, and action accessibility.
- Consequences: All UI skills and DoD include phone-width smoke checks.
- Confidence: High
- Source: transcripts `d7cbed5c-e697-450a-8e83-886738db3b75`, `33081d0d-f5ce-4cbb-9e6b-8954232bcb48`, `794bd768-3f83-4459-8aa9-dcf7b4e5de22`; `README.md`

## DL-004: Shared UI Pattern Consistency Over Local Reinvention
- Decision: Prefer shared detail/edit/list patterns and shared components over per-page custom variants.
- Rationale: Historical drift and duplication triggered architecture-maintenance efforts.
- Consequences: Refactors should consolidate, not fork behavior; new module work must reuse existing entity/module components and keep UX consistent with equivalent flows.
- Confidence: High
- Source: `.cursorrules`; transcript `4cff830b-3493-448b-ad8a-5de9e6ba33b2`; `docs/UI_PATTERNS.md`

## DL-005: Contacts Permission Unification (Kontakte + Mitspieler)
- Decision: Keep one `/contacts` route with runtime profile branching instead of separate members route.
- Rationale: Reduce module duplication while preserving readonly boundaries.
- Consequences: New contacts actions must account for profile restrictions.
- Confidence: High
- Source: `docs/ARCHITECTURE_DECISIONS.md` (AD-2026-03-31)

## DL-006: Edit Mode Must Follow URL Conventions
- Decision: Edit/view behavior is URL-derived per current shipped convention docs.
- Rationale: Back/refresh/share behavior depends on URL-driven state.
- Consequences: Avoid state-only edit toggles that desync navigation behavior.
- Confidence: High
- Source: `docs/UI_PATTERNS.md`; `docs/FEATURES_AND_BEHAVIORS.md`

## DL-007: Use Shared Date-Time Formatters Only
- Decision: User-visible date/time formatting must go through `frontend/lib/date-time.ts` helpers.
- Rationale: Ad-hoc formatting caused inconsistency and null/placeholder issues.
- Consequences: New UI display code must use shared formatter functions with locale.
- Confidence: High
- Source: `.cursorrules`; `docs/UI_PATTERNS.md`

## DL-008: API/Frontend Contract Sync Is Required
- Decision: Do not ship UI flows that depend on missing backend actions/contracts.
- Rationale: Transcript evidence showed runtime failures when UI invoked unsupported actions.
- Consequences: Contract changes require paired backend/frontend updates and verification.
- Confidence: High
- Source: transcript `0160b66c-f29c-4fdf-b794-095209368ea0`; `docs/API_ARCHITECTURE.md`

## DL-009: Default Navigation Avoids Explicit Back Buttons
- Decision: Treat “no explicit back button” as strong default preference.
- Rationale: Rule exists to avoid redundant navigation clutter and inconsistent UX.
- Consequences: Add Back buttons only with explicit product intent.
- Confidence: Medium
- Source: `.cursor/rules/no-back-buttons.mdc`

## DL-010: Build Pipeline Is Contractual for Release Safety
- Decision: Use `build.sh` as canonical bundle assembly + verification path.
- Rationale: Deploy consistency depends on full bundle contents and debug gating behavior.
- Consequences: Release-safe changes should validate build output, not only local dev server.
- Confidence: High
- Source: `build.sh`; `README.md`; `docs/MAIL.md`

## DL-011: Security Must Be Maintained or Improved vs Legacy
- Decision: Next Generation changes must not reduce security compared to legacy BNote; authorization must be enforced server-side for read/write actions.
- Rationale: Product goal is improved security posture while preserving rights management integrity.
- Consequences: Permission-sensitive changes require explicit allow/deny verification; UI-only checks are insufficient.
- Confidence: High
- Source: stakeholder requirement (migration guidance), `docs/API_ARCHITECTURE.md`, `docs/ARCHITECTURE_DECISIONS.md`
