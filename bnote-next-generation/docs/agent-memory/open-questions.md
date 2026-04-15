# Open Questions

## OQ-001: Browser Matrix for Mobile Validation

- Missing: explicit supported mobile browsers/versions (iOS Safari, Android Chrome variants, WebView expectations).
- Risk: mobile checks may be inconsistent across contributors.
- Suggested follow-up: define target browser matrix in docs and CI/manual QA checklist.

## OQ-002: Standardized Non-UI Test Strategy

- Missing: dedicated typecheck/unit/integration scripts in repo-level workflow.
- Risk: behavior regressions may pass lint/build but still break runtime paths.
- Suggested follow-up: document or add canonical test commands per subsystem.

## OQ-003: Contract Change Policy (Versioning/Compatibility)

- Missing: explicit policy for handling breaking API contract changes.
- Risk: frontend/backend drift during incremental development.
- Suggested follow-up: add lightweight API change protocol in docs.

## OQ-004: Translation Quality Gate

- Missing: automated check that all new keys exist in all locales and no raw keys render.
- Risk: frequent translation omissions in iterative UI work.
- Suggested follow-up: add script/checklist for locale parity.

## OQ-005: Mobile Regression Automation

- Missing: repeatable automated viewport checks for key routes.
- Risk: recurring mobile regressions rely on manual memory.
- Suggested follow-up: define minimal scripted smoke suite (or checklist with required screenshots).

## OQ-006: Documented Priority Routes for Mobile Smoke

- Missing: explicit short list of must-test routes after UI changes.
- Risk: uneven coverage and missed critical user journeys.
- Suggested follow-up: document canonical “critical 8 routes” list.
