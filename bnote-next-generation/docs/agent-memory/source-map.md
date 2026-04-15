# Source Map (Rule Derivation)

## Hard Rules

### H-01 Scope boundary: only `bnote-next-generation/` is writable

- Derived from:
  - `.cursorrules`
  - `README.md`

### H-02 i18n mandatory, all user-facing strings localized across 4 locales

- Derived from:
  - `.cursorrules`
  - transcripts `0160b66c-f29c-4fdf-b794-095209368ea0`, `794bd768-3f83-4459-8aa9-dcf7b4e5de22`
  - `docs/FEATURES_AND_BEHAVIORS.md`

### H-03 Mobile verification is required for UI completion

- Derived from:
  - transcripts `d7cbed5c-e697-450a-8e83-886738db3b75`, `33081d0d-f5ce-4cbb-9e6b-8954232bcb48`, `794bd768-3f83-4459-8aa9-dcf7b4e5de22`
  - `README.md` (mobile-first goal)
  - `docs/FEATURES_AND_BEHAVIORS.md` (mobile shell expectations)

### H-04 Dark mode and semantic theming must remain compatible

- Derived from:
  - `.cursorrules`
  - `docs/FEATURES_AND_BEHAVIORS.md`

### H-05 Shared component/pattern reuse over duplication

- Derived from:
  - `.cursorrules`
  - transcript `4cff830b-3493-448b-ad8a-5de9e6ba33b2`
  - `docs/UI_PATTERNS.md`
- Interpretation used in agent guidance:
  - reuse existing entity/module components first,
  - maintain consistent UX behavior across modules,
  - avoid introducing new UI variants when an equivalent shared pattern already exists.

### H-06 URL-driven edit mode and route conventions

- Derived from:
  - `.cursorrules`
  - `docs/UI_PATTERNS.md`
  - `docs/FEATURES_AND_BEHAVIORS.md`

### H-07 Date/time formatting through shared helpers only

- Derived from:
  - `.cursorrules`
  - `docs/UI_PATTERNS.md`

### H-08 API/frontend contract synchronization before shipping

- Derived from:
  - transcript `0160b66c-f29c-4fdf-b794-095209368ea0` (create-action mismatch and backend alignment)
  - `docs/API_ARCHITECTURE.md`

### H-09 Build and bundle verification are part of release-safe completion

- Derived from:
  - `build.sh`
  - `README.md`
  - `docs/MAIL.md`

### H-10 Security posture must be maintained or improved vs legacy

- Derived from:
  - stakeholder migration requirement (explicit security objective)
  - `docs/API_ARCHITECTURE.md` (auth/authz and security sections)
  - `docs/ARCHITECTURE_DECISIONS.md` (permission-bound behavior)
- Interpretation used in agent guidance:
  - never weaken existing authorization controls,
  - enforce permission checks server-side,
  - validate allowed and denied rights paths for affected flows.

## Strong Preferences

### P-01 Avoid explicit Back buttons unless requested

- Derived from:
  - `.cursor/rules/no-back-buttons.mdc`
- Status: kept as strong preference (not hard prohibition).

### P-02 Keep fixes small and localized

- Derived from:
  - repeated transcript patterns emphasizing safe, incremental patches
  - transcript `4cff830b-3493-448b-ad8a-5de9e6ba33b2`

### P-03 Update docs when behavior/contracts change

- Derived from:
  - transcript `2dc8ed2a-886a-4ce0-a866-bbb62e202a4d`
  - `README.md`, docs ecosystem structure

## Historical Context (Still Relevant)

### C-01 Contacts route merged for Kontakte/Mitspieler access profiles

- Derived from:
  - `docs/ARCHITECTURE_DECISIONS.md` (AD-2026-03-31)

### C-02 Debug/developer routes are intentionally gated and pruned

- Derived from:
  - `README.md`
  - `build.sh`
  - `docs/UI_PATTERNS.md`

### C-03 Repeated mobile regressions shaped current development emphasis

- Derived from:
  - transcripts `d7cbed5c-e697-450a-8e83-886738db3b75`, `33081d0d-f5ce-4cbb-9e6b-8954232bcb48`, `794bd768-3f83-4459-8aa9-dcf7b4e5de22`
