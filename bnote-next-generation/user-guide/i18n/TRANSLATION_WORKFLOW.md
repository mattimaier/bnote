# Translation Workflow (DE/EN First)

## Scope

This workflow applies to all content in `user-guide/i18n/`.

## Language Priority

1. **Tier 1 (required first):** `de`, `en`
2. **Tier 2 (required after Tier 1):** `es`, `fr`

Critical pages must exist in all four languages:

- `index.md`
- `getting-started/quick-start.md`
- `migration/whats-new-and-migration-guide.md`
- `migration/old-vs-nextgen.md`
- `troubleshooting/index.md`

## Update Process

1. Draft content in EN.
2. Create DE version with editorial review.
3. Freeze EN+DE wording for the change set.
4. Translate to ES and FR.
5. Run parity check (file presence + links).
6. Publish.

## Editorial Rules

- Use plain language for non-technical users.
- Keep section headings parallel across languages.
- Keep migration actions explicit ("Do this now").
- Avoid tool-internal jargon unless briefly explained.

## "What's New" Update Process

1. Publish EN and DE update first.
2. Publish ES and FR parity updates within the same release cycle.
3. Cross-link each update from language home pages if impact is high.
