# Architecture Changes

Use for cross-module structure, routing model, or system-level pattern changes.

- State the target architecture delta in 3-5 bullets before editing.
- Verify against current decisions (`docs/ARCHITECTURE_DECISIONS.md`, `docs/API_ARCHITECTURE.md`).
- Migrate incrementally with compatibility guards where needed.
- Update docs in the same change to avoid code/docs drift.
- Validate critical flows and permissions after structural moves.
- Required output: decision summary, consequences, rollback considerations, updated docs list.

If architecture changes touch UI routes/components, include mandatory mobile smoke verification.
