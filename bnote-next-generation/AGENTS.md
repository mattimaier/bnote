# AGENTS.md

## Project Purpose

BNote Next Generation is the modern UI/API layer for BNote (ensemble management). This repo is the active codebase: Next.js frontend (`frontend/`) + PHP API (`api/`) + translations (`lang/`).

## Stack and Major Subsystems

- Frontend: Next.js App Router, React, TypeScript, Tailwind (`frontend/`)
- Backend: PHP module router (`api/index.php`) + module handlers (`api/modules/*.php`)
- i18n: JSON locale files (`lang/de.json`, `lang/en.json`, `lang/es.json`, `lang/fr.json`)
- Build/deploy: `build.sh`, `deploy.sh`

## Non-Negotiable Invariants

- Scope boundary: modify only `bnote-next-generation/`; legacy `../BNote/` is read-only unless explicitly requested.
- Development cleanup policy: while BNote Next Generation is not shipped yet, do not add new legacy fallbacks, compatibility aliases, migration shims, or dual-path behavior unless explicitly requested.
- i18n required: no hardcoded user-facing text in app flows; add keys to all 4 locales.
- Mobile quality is required: every UI change must be verified for phone + desktop before done.
- Dark mode compatibility: use semantic theme variables/classes; avoid hardcoded color-only solutions.
- Shared UX is required: reuse existing entity/module components and flows before building anything new.
- Do not introduce new component variants when an equivalent shared component/pattern already exists.
- Security baseline: Next Generation must not be less secure than legacy; changes should maintain or improve security posture.
- Authorization is mandatory: all read/write actions must respect role/module rights and server-side permission checks.
- URL-driven edit mode conventions must stay consistent with shipped behavior and `docs/UI_PATTERNS.md`.
- Date/time formatting must use shared helpers from `frontend/lib/date-time.ts`.
- Changelog discipline is required: bugfixes and user-visible behavior changes must update changelog inputs in the same change.

## Coding Conventions Used in This Repo

- Prefer small, localized edits over broad rewrites.
- Preserve existing naming and module boundaries unless a deliberate harmonization change is requested.
- For UI errors, prefer user-facing copy (localized), not technical internal text.
- Keep list/detail/edit behaviors consistent across modules (see `docs/UI_PATTERNS.md`).
- Keep module UX consistent: similar operations (list, detail, edit, save/cancel, delete, status display) should behave the same across modules.
- Do not add explicit Back buttons by default (strong preference). Use existing navigation unless product asks otherwise.
- For bugfixes originating from the in-app email bug dialog, include the bug report ID (for example `BUG-...`) in the commit message.
- For bugfixes, include `BUG-...` in commit messages when available.
- For non-bug user-visible changes, ask the user before committing whether to include a changelog note.
- If the user confirms, guide them to add one concise final-result note in `docs/changelog-beta-overrides.json` and include it in the same change.
- Relevant change = bug fix or user-visible behavior/UI/API result change. Internal-only refactor/chore/test/docs-only changes are excluded.
- Maintain changelog via hybrid flow: automatic BUG-fix entries + optional curated release notes for non-bug user-visible outcomes.

## Harmonization and Naming Policy

- Default language for code identifiers, contracts, comments, and docs is English.
- German is allowed only in:
  - `lang/*.json` localized content,
  - legally required legal/imprint/privacy copy,
  - `api/legacy_module_names.php` as the legacy-core compatibility boundary.
- Next Generation API contracts must use English module/action names (hard cut for old German Next Gen names).
- Legacy-core module names must not be used directly in handlers; use `getLegacyModuleId(..., LegacyModuleKey::...)`.
- File/folder naming:
  - `PascalCase` for React component files and PHP classes.
  - `kebab-case` for general-purpose files/folders.
  - `snake_case` only for protocol/schema fields or required legacy payload names.

## Formatting Policy

- Prettier is the canonical formatter for TS/JS/JSON/MD/YAML and PHP.
- JSON files must be deterministic: sorted object keys + pretty-printed with 2-space indent and trailing newline.
- Run these commands for harmonization/format changes:
  - `cd frontend && npm run format`
  - `cd frontend && npm run format:check`
- `npm run format:check` must include:
  - JSON sort/pretty verification,
  - Prettier check,
  - harmonization audit,
  - license audit report generation.

## OSS License Compliance Policy (GPL-3.0)

- Project license remains GPL-3.0.
- Dependency license governance is required for both npm (`frontend/package-lock.json`) and composer (`api/composer.lock`).
- Current enforcement mode is report-only:
  - generate machine-readable report at `docs/reports/license-audit.json`,
  - flag unknown/review licenses for follow-up,
  - do not fail CI solely on license classification yet.

## Multi-Step Task Workflow

1. Inspect existing patterns in relevant module(s) before coding.
2. Reuse existing entity/module components and shared helpers first; do not create parallel UI patterns.
3. Implement changes with mobile-first constraints (small viewport behavior first).
4. For bugfixes, ensure `BUG-...` commit message is present. For non-bug user-visible changes, ask user before commit whether to add a curated changelog note.
5. Run required validation commands.
6. Run route-level smoke checks (mobile + desktop) for touched flows.
7. Update docs when behavior/contracts changed.

## Verification Commands

Run what applies to touched areas.

- Frontend lint:
  - `cd frontend && npm run lint`
- Frontend build:
  - `cd frontend && npm run build`
- Harmonization/format checks:
  - `cd frontend && npm run format`
  - `cd frontend && npm run format:check`
- Frontend dev:
  - `cd frontend && npm run dev`
  - optional fallback: `cd frontend && npm run dev:webpack`
- Full bundle build (frontend + api + lang):
  - `./build.sh`
- Build output verification:
  - `./build.sh --verify-only`

Notes:

- No dedicated repo-wide `typecheck` or unit-test script is currently standardized in `frontend/package.json`.
- API dependencies are installed via Composer inside `build.sh`.

## Mobile Smoke Checklist (Required for UI Changes)

Validate at ~375px and ~430px widths, plus desktop.

- No horizontal overflow or clipped cards/modals/drawers.
- Header/topbar/search remain usable and readable.
- Tap targets are reachable; primary actions are visible without desktop-only affordances.
- Edit/create/detail flows are fully usable on mobile.
- Light + dark theme and i18n strings still render correctly.

## Change Safety Rules

- Do not silently change API contracts without frontend alignment (and vice versa).
- Do not introduce new fallback paths for old field names, old routes, legacy payload shapes, or migration-era behavior; prefer a single Next Generation path.
- Preserve and verify permission checks; avoid UI-only authorization assumptions.
- Avoid introducing parallel implementations of the same UI behavior.
- Before adding a new UI component, verify an existing shared component cannot satisfy the requirement.
- Do not trust client input for authorization-sensitive behavior; enforce checks in backend handlers.
- Treat security-relevant changes (auth, permissions, tokens, sensitive endpoints) as high-risk and validate explicitly.
- Keep debug/dev-only behavior gated as documented (`NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS`).
- If you find conflicting docs vs code, resolve or document the drift in the same change.

## Changelog Source of Truth

- Auto source: git commits containing `BUG-...` IDs (bugfixes only).
- Optional curated source: changelog override notes for clearer user-facing release wording.
- Changelog entries use a simple model: `title`, `changeType`, optional `date`, optional `bugId`.
- Generated changelog artifact is consumed by System Information / What’s New surfaces.

## Definition of Done

A task is done only when:

- behavior is implemented and matches current architecture conventions,
- i18n coverage exists for all new user-facing text,
- mobile + desktop smoke checks pass for touched routes,
- rights/permission behavior is validated for affected flows (allowed and denied cases),
- no change weakens existing security controls; backend authorization remains enforced,
- lint/build checks pass for affected areas,
- related docs are updated when behavior/contracts changed,
- for bugfixes and user-visible changes, changelog input has been updated and will be reflected in generated changelog output,
- residual risks/open questions are explicitly noted.

## Deeper Docs

- Architecture/API: `docs/API_ARCHITECTURE.md`, `docs/API_ENDPOINTS.md`
- UI conventions: `docs/UI_PATTERNS.md`
- Feature behavior baseline: `docs/FEATURES_AND_BEHAVIORS.md`
- Architecture decisions: `docs/ARCHITECTURE_DECISIONS.md`
- Known issues: `docs/KNOWN_ISSUES.md`
- Agent playbooks: `docs/agent-skills/`
- Agent memory: `docs/agent-memory/`
