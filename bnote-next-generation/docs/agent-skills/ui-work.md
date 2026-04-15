# UI Work

Use for layout, component, interaction, and visual behavior changes.

- Follow shared patterns from `docs/UI_PATTERNS.md` before adding new UI patterns.
- Reuse existing shared entity/module UI components whenever possible; avoid new one-off controls/components.
- Keep i18n and theme semantics intact (no hardcoded user-facing strings/colors).
- Implement mobile-first: verify small-screen structure while coding, not only at end.
- Preserve accessibility basics: visible actions, readable text, usable tap targets.
- Check cross-module consistency: if a similar screen exists, match its interaction and visual language.
- Run `cd frontend && npm run lint` and `cd frontend && npm run build`.
- Required output: changed routes/components, visual behavior notes, mobile verification notes.

Required mobile checks:

- widths ~375px and ~430px,
- no horizontal overflow or clipped overlays,
- topbar/search/header usable,
- edit/create/detail workflows fully operable.
