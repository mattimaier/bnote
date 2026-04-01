# Project Context (Distilled)

## System Understanding
- This repo is the active Next Gen layer for BNote: Next.js frontend + PHP API modules + translation packs.
- Frontend and backend are tightly coupled through `api/index.php?module=...&action=...` patterns.
- Deployment model is static frontend export + PHP runtime; `build.sh` assembles deployable bundle.

## Important Business Logic
- Session/permission model is inherited from BNote; API enforces access rules.
- Contacts route supports two permission profiles (full Kontakte vs Mitspieler-only readonly) on one route.
- Event workflows (rehearsals/concerts) depend on consistent detail/edit conventions and participation behavior.

## Recurring Implementation Patterns
- Shared UI components should be reused across modules (not duplicated per page).
- Edit/view state is URL-driven per documented conventions.
- User-facing text always comes from i18n keys in all four languages.
- Date/time display uses shared `frontend/lib/date-time.ts` helpers.

## Mobile-First Reality
- Many regressions historically appeared only on mobile (topbar spacing, search usability, header/action layout, overflow/clipping, edit flow usability).
- Durable rule: UI work is incomplete without phone-width verification (~375px and ~430px) plus desktop.
- Key mobile-sensitive surfaces: topbar/search overlays, dashboard cards, entity detail/edit headers and action bars, modal/drawer layouts.

## Known Pitfalls
- Desktop-only validation misses mobile breakage.
- Hardcoded text/colors cause translation/theme drift.
- Backend/frontend contract mismatch (action names/payload shape) causes runtime failures.
- Stale duplicated UI logic causes pattern drift and inconsistent behavior.

## Historical Context That Still Matters
- Legacy `../BNote/` code is often referenced but should remain unmodified for Next Gen work.
- Earlier refactors introduced/reinforced shared UI contracts (Detail header/editing/pickers/cards), still treated as stability anchors.
- Developer/debug routes are intentionally gated and pruned in production unless explicitly enabled.

## Preferred Strategies
- Minimal patch first, then structural cleanup only when needed.
- Keep API and frontend changes synchronized for contract shifts.
- Document decision-level changes immediately to avoid code/docs drift.
- Treat mobile quality, i18n completeness, and permissions as release blockers.

## Areas Requiring Extra Caution
- `frontend/components/entities/event/*`
- `frontend/components/dashboard/*`
- `frontend/components/AppTopbar.tsx` and search-related components
- `api/modules/rehearsals.php`, `api/modules/concerts.php`, `api/modules/contacts.php`, `api/modules/auth.php`
- `lang/*.json` consistency across all locales
