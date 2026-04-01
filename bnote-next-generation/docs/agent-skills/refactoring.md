# Refactoring

Use for cleanup, deduplication, and structure improvements without behavior change.

- Start from a concrete pain: duplication, drift from documented patterns, or dead code.
- Keep behavior stable; if behavior changes, treat as feature work and document explicitly.
- Prefer extracting/reusing shared utilities/components over adding parallel implementations.
- Align module UX behavior with existing equivalent flows instead of introducing module-specific variants.
- Align with `docs/UI_PATTERNS.md` and current route conventions.
- Remove only high-confidence dead code (no speculative deletions).
- Validate with `cd frontend && npm run lint` and targeted smoke checks.
- Required output: before/after structure, why safe, what was intentionally not changed.

Mobile gate (if UI paths changed): confirm responsive behavior remains equivalent at ~375px/~430px.
