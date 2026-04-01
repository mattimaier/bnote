# Feature Implementation

Use for net-new product behavior.

- Confirm scope and touched routes/modules from existing docs (`README.md`, `docs/FEATURES_AND_BEHAVIORS.md`, `docs/UI_PATTERNS.md`).
- Reuse existing entity/module components and shared helpers first; do not introduce parallel UI patterns.
- Keep behavior aligned with equivalent flows in other modules (list/detail/edit/save/cancel/delete consistency).
- Implement vertical slice: frontend + API + i18n + permissions + error handling.
- For UI work, design for phone-first behavior before desktop polish.
- Add translation keys to `lang/de.json`, `lang/en.json`, `lang/es.json`, `lang/fr.json`.
- Validate with `cd frontend && npm run lint` and relevant build/smoke checks.
- Required output: changed behavior summary, touched paths, verification done, residual risks.

Mobile gate (required for UI): test ~375px, ~430px, desktop; no overflow/clipping; actions remain reachable.
