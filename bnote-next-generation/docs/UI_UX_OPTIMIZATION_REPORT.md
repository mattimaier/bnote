# UI/UX Optimization Report

**Date:** February 2026  
**Scope:** Codebase review for UI/UX improvements

---

## Summary

A full codebase scan identified and fixed **40+ duplicate `className` bugs** across 15+ files. These caused the second `className` to overwrite the first, leading to broken styling (e.g. missing `text-white` on primary buttons, missing `text-base-content/60` on labels).

---

## 1. Duplicate `className` Fixes (Completed)

### Root cause
React/JSX allows only one `className` per element. When two are present, the second overwrites the first, so styles are lost.

### Files updated

| File | Fixes |
|------|-------|
| `ParticipationWidget.tsx` | 2× `<p>` labels |
| `app/_modules/equipment/page.tsx` | h1, p, Add button, error block, search input, table wrapper, sort button |
| `EquipmentDetail.tsx` | 5× `<dt>`, 1× `<h2>` |
| `ContactDetail.tsx` | 20× `<span>` labels |
| `UserDetail.tsx` | 1× `<p>` |
| `LocationDetail.tsx` | 2× `<h2>`, sort button |
| `UserEdit.tsx` | 1× `<p>` |
| `ShareFileList.tsx` | Icon, table header |
| `ShareUploadZone.tsx` | Icon |
| `EntityListRow.tsx` | secondary text |
| `app/_modules/repertoire/page.tsx` | error block, empty td, sort button |
| `app/_modules/outfits/page.tsx` | error block, sort button |
| `app/_modules/locations/page.tsx` | error block, table wrapper, thead row, sort button |
| `OutfitEdit.tsx` | error block, input |
| `EquipmentEdit.tsx` | error block, 6× inputs |
| `LocationEdit.tsx` | error block |
| `ContactEdit.tsx` | error block, 12× inputs, notes textarea |

### Pattern used
Merged duplicate classes into a single `className`, e.g.:
- `className="btn btn-primary btn-sm gap-2 shrink-0"` for primary action buttons
- `className="text-xs font-medium text-base-content/60"` for labels
- `className="rounded-lg border border-error bg-error/15 px-4 py-3 text-sm text-error"` for error blocks

---

## 2. Remaining `var()` Usage (Migration Opportunities)

These components still use `var(--*)` inline styles. Migrating to FlyonUI semantic classes would improve theme consistency.

| File | Usage |
|------|-------|
| `ParticipationDiagram.tsx` | `var(--color-success)`, `var(--color-warning)`, `var(--color-error)` for segment colors |
| `app/_modules/repertoire/page.tsx` | `var(--border)`, `var(--muted)` for table styling |
| `app/(app)/search/page.tsx` | `var(--foreground)`, `var(--muted-foreground)`, `var(--border)`, `var(--primary)`, etc. |
| `EventDetail_body.txt` | Reference/template file – many `var()` usages |

**Recommendation:** Replace with `text-success`, `text-warning`, `text-error`, `border-base-300`, `bg-base-200`, `text-base-content`, `text-base-content/60`, etc.

---

## 3. Accessibility Notes

- **Focus states:** Several components use `outline-none`; ensure `focus-visible:ring-2 focus-visible:ring-primary` (or similar) is present for keyboard users.
- **ARIA:** AppTopbar, ParticipationModal, ToastContainer, ConfirmModal, MobileNavDrawer use `aria-*` attributes.
- **FlyonUI:** `btn`, `checkbox`, `radio`, `input` components include built-in focus styles.

---

## 4. Consistency Checklist

- [x] Primary action buttons use `btn btn-primary btn-sm`
- [x] Error blocks use `border border-error bg-error/15 text-error`
- [x] Muted labels use `text-base-content/60`
- [x] Form inputs use `input input-sm w-full text-base-content`
- [x] Sort header buttons use `text-base-content`
- [x] Table wrappers use `border border-base-300 bg-base-100 text-base-content`

---

## 5. Future Optimizations

1. **Search page:** Migrate `app/(app)/search/page.tsx` from `var()` to FlyonUI classes.
2. **Repertoire page:** Replace `var(--border)`, `var(--muted)` in table/card styling.
3. **ParticipationDiagram:** Use `bg-success`, `bg-warning`, `bg-error` for segment colors if FlyonUI supports it for SVG/div backgrounds.
4. **ESLint rule:** Add a rule to flag duplicate `className` attributes to prevent regressions.
