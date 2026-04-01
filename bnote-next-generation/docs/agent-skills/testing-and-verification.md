# Testing and Verification

Use before handoff or release-sensitive merges.

- Run commands relevant to touched areas:
  - `cd frontend && npm run lint`
  - `cd frontend && npm run build`
  - `./build.sh` or `./build.sh --verify-only` when bundle integrity matters.
- Perform route-level smoke checks for changed flows (happy path + error path).
- For permission-sensitive changes, verify at least one allowed and one denied role/profile.
- For i18n changes, verify no raw keys appear in UI.
- Required output: exact commands run, key route checks, failures/warnings, untested gaps.

Mobile gate (for UI changes): validate ~375px, ~430px, desktop; include overflow/tap/visibility checks.
