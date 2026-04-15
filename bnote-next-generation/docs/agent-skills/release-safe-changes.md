# Release-Safe Changes

Use for high-confidence production-ready updates.

- Prefer small, reversible increments over broad batch rewrites.
- Avoid hidden contract changes; call out every user-visible or API-visible difference.
- Verify shared UX consistency: changed module behavior should match equivalent patterns already used elsewhere.
- Verify no unnecessary new UI component was introduced where a shared one already exists.
- Run full relevant checks (`lint`, `build`, bundle checks) before declaring done.
- Verify critical user journeys and one failure path per changed feature.
- Verify security/rights impact: confirm no weakened authorization and test denied-access behavior where relevant.
- Document operational impact (env vars, deploy behavior, debug gating) when applicable.
- Required output: release notes summary, validation evidence, known residual risks.

UI release gate (required): mobile + desktop smoke checks must pass; no unresolved mobile regression.

Failure protocol:

- If checks fail, do not mark done.
- Reproduce, patch, re-run checks, and report remaining blockers explicitly.
