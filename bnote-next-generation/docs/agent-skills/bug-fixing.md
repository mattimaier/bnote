# Bug Fixing

Use for regressions, runtime errors, and behavior mismatches.

- Reproduce first: exact route, role/permission context, and environment.
- Identify root cause in existing flow; avoid symptom-only patches.
- Keep fix minimal and local; preserve current contracts unless explicitly changing them.
- If bug touches auth/permissions/tokens, require backend-side verification (not UI-only checks).
- If user-facing errors change, use i18n keys and friendly wording.
- Verify no adjacent regression on related routes/components.
- Run `cd frontend && npm run lint`; run `cd frontend && npm run build` for UI-impacting fixes.
- Required output: root cause, fix summary, reproduction/verification steps, remaining risk.

Mobile gate (if UI touched): confirm bug is fixed on phone widths and no new layout/tap regressions were introduced.
