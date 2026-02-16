# FlyonUI Component Upgrade Plan

**BNote Next Generation** – Plan to replace/upgrade existing components with [FlyonUI](https://flyonui.com/docs/component/) components.

---

## 1. Executive Summary

| Aspect | Current State | Target State |
|--------|---------------|--------------|
| **UI Stack** | Custom Tailwind + hand-rolled components | FlyonUI (daisyUI + Preline) + Tailwind v4 |
| **Components** | ~25 custom components | FlyonUI equivalents where available |
| **Forms** | Native inputs with custom styles | FlyonUI Input, Select, Textarea, Checkbox |
| **Modals** | Custom Modal, ConfirmModal, ParticipationModal | FlyonUI Modal |
| **Layout** | Custom Sidebar, Topbar, MobileNavDrawer | FlyonUI Sidebar, Navbar |
| **Feedback** | Custom ToastContainer | FlyonUI Notyf (Toasts) |

---

## 2. Current State Analysis

### 2.1 App Structure

- **Framework**: Next.js 16, React 19
- **Styling**: Tailwind CSS v4, CSS variables for theming (light/dark)
- **No UI library**: All components are custom-built with Tailwind utilities

### 2.2 Component Inventory

#### **Layout & Shell**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `AppSidebar` | `components/AppSidebar.tsx` | Desktop nav | **Sidebar** |
| `AppTopbar` | `components/AppTopbar.tsx` | Header, search, user menu | **Navbar** |
| `MobileNavDrawer` | `components/MobileNavDrawer.tsx` | Mobile nav overlay | **Drawer (Offcanvas)** |
| `AppShell` | `components/AppShell.tsx` | Layout wrapper | Keep (orchestration) |
| `AppShellLayout` | `components/AppShellLayout.tsx` | Root layout | Keep |

#### **Modals & Overlays**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `Modal` | `components/Modal.tsx` | Generic modal | **Modal** |
| `ConfirmModal` | `components/ConfirmModal.tsx` | Delete/confirm dialogs | **Modal** (styled) |
| `ParticipationModal` | `components/ParticipationModal.tsx` | Reason for maybe/no | **Modal** |
| `SearchAutocompleteOverlay` | `components/SearchAutocompleteOverlay.tsx` | Search dropdown | **Dropdown** or **Popover** |

#### **Form & Picker Components**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `SelectPicker` | `components/SelectPicker.tsx` | Single-select (entities) | **Advanced select** or **Combo Box** |
| `MultiSelect` | `components/entities/event/MultiSelect.tsx` | Multi-select (participants) | **Advanced select** (multi) |
| `StatusPicker` | `components/entities/event/StatusPicker.tsx` | Status pill popover | **Dropdown** + **Badge** |
| `ThemeToggle` | `components/ThemeToggle.tsx` | Light/dark switch | **Theme Controller** or **Swap** |

#### **Data Display**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `DetailCard` | `components/DetailCard.tsx` | Card container for detail sections | **Card** |
| `ResponsiveTable` | `components/ResponsiveTable.tsx` | Table + mobile list | **Table** + **List Group** |
| `ResizableTable` | `components/ResizableTable.tsx` | Resizable columns | **Table** (custom resize logic) |
| `EntityListRow` | `components/EntityListRow.tsx` | List row (icon, primary, secondary) | **List Group** or keep |
| `EventCard` | `components/EventCard.tsx` | Event timeline card | **Card** + **Timeline** |

#### **Feedback & Actions**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `ToastContainer` | `components/ToastContainer.tsx` | Toast notifications | **Notyf (Toasts)** |
| `EditingBar` | `components/EditingBar.tsx` | Sticky save/cancel bar | **Join** or custom |
| `DetailPageHeader` | `components/DetailPageHeader.tsx` | Page title + Edit button | Keep (layout) |
| `DetailDeleteSection` | `components/DetailDeleteSection.tsx` | Delete button + ConfirmModal | **Modal** + **Button** |

#### **Entity-Specific**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `ParticipationWidget` | `components/ParticipationWidget.tsx` | Yes/Maybe/No buttons | **Button** group |
| `ParticipationModal` | `components/ParticipationModal.tsx` | Reason input modal | **Modal** + **Textarea** |
| `ParticipationDiagram` | `components/ParticipationDiagram.tsx` | Stats visualization | **Radial progress** or **Progress** |
| `ParticipantOverview` | `components/ParticipantOverview.tsx` | Participant list | **List Group** |
| `ParticipantEditor` | `components/entities/event/ParticipantEditor.tsx` | Edit participants | Uses SelectPicker, MultiSelect |
| `SelectedItemsList` | `components/entities/event/SelectedItemsList.tsx` | Chips list | **Badge** + **List** |

#### **Other**
| Component | Location | Usage | FlyonUI Equivalent |
|-----------|----------|-------|--------------------|
| `MarkdownText` | `components/MarkdownText.tsx` | Renders markdown | Keep (logic) |
| `AddressLink` | `components/AddressLink.tsx` | Address/maps link | Keep (logic) |
| `AuthGuard` | `components/AuthGuard.tsx` | Auth wrapper | Keep |
| `Redirect` | `components/Redirect.tsx` | Redirect logic | Keep |

### 2.3 Form Inputs (Native, Used in Edit Views)

- **Text inputs**: `LocationEdit`, `ContactEdit`, `UserEdit`, `EquipmentEdit`, `OutfitEdit`, `SongEdit`, `VoteEdit`, `profile/page`, `login/page`
- **Textarea**: `LocationEdit` (notes), `ParticipationModal`, `ContactEdit`, etc.
- **Select**: `ResponsiveTable` (mobile sort dropdown)
- **Checkbox**: `MultiSelect`
- **Buttons**: Throughout (primary, secondary, danger)

**FlyonUI replacements**: **Input**, **Textarea**, **Select**, **Checkbox**, **Button**

---

## 3. FlyonUI Integration Prerequisites

### 3.1 Installation

```bash
npm install flyonui
```

### 3.2 CSS Configuration (`globals.css`)

```css
@import "tailwindcss";
@plugin "flyonui";
@import "./node_modules/flyonui/variants.css";  /* For JS components */
@source "./node_modules/flyonui/dist/index.js"; /* For JS components */
```

### 3.3 Theme Compatibility

- **Current**: Custom CSS variables (`--primary`, `--card`, `--destructive`, etc.)
- **FlyonUI/daisyUI**: Uses semantic tokens (`primary`, `secondary`, `accent`, `error`, etc.)
- **Action**: Map BNote theme to FlyonUI theme or create a custom FlyonUI theme that uses existing variables.

### 3.4 Tailwind v4 Compatibility

- FlyonUI supports Tailwind v4 (per docs).
- Verify `@plugin "flyonui"` works with `@tailwindcss/postcss` v4.

---

## 4. Phase-by-Phase Upgrade Plan

### Phase 1: Foundation (Low Risk)
**Goal**: Install FlyonUI, validate theme, replace primitives.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 1.1 | Install FlyonUI, add plugin to `globals.css` | `package.json`, `globals.css` | 0.5h |
| 1.2 | Create/adapt FlyonUI theme to match BNote colors | `globals.css` or `tailwind.config` | 1h |
| 1.3 | Replace native **buttons** with FlyonUI Button | `EditingBar`, `DetailPageHeader`, `DetailDeleteSection`, `ConfirmModal`, `login/page`, etc. | 2h |
| 1.4 | Replace native **inputs** with FlyonUI Input | `login/page`, all Edit forms | 2h |
| 1.5 | Replace native **textarea** with FlyonUI Textarea | `LocationEdit`, `ParticipationModal`, `ContactEdit`, etc. | 1h |
| 1.6 | Replace native **checkbox** with FlyonUI Checkbox | `MultiSelect` | 0.5h |

**Deliverable**: All form primitives use FlyonUI. Visual parity.

---

### Phase 2: Modals & Overlays (Medium Risk)
**Goal**: Replace custom modals with FlyonUI Modal.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 2.1 | Replace `Modal` with FlyonUI Modal | `Modal.tsx`, all consumers | 1h |
| 2.2 | Replace `ConfirmModal` with FlyonUI Modal | `ConfirmModal.tsx`, `DetailDeleteSection` | 1h |
| 2.3 | Replace `ParticipationModal` with FlyonUI Modal | `ParticipationModal.tsx` | 1h |
| 2.4 | Refactor `SearchAutocompleteOverlay` to use FlyonUI Dropdown/Popover | `SearchAutocompleteOverlay.tsx` | 2h |

**Deliverable**: All modals use FlyonUI. Consistent behavior (escape, backdrop, focus).

---

### Phase 3: Select & Picker Components (Higher Risk)
**Goal**: Replace custom SelectPicker and MultiSelect.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 3.1 | Evaluate FlyonUI **Advanced select** / **Combo Box** for `SelectPicker` | `SelectPicker.tsx` | 1h |
| 3.2 | Replace or wrap `SelectPicker` with FlyonUI component | `SelectPicker.tsx`, all consumers | 2–3h |
| 3.3 | Replace `MultiSelect` with FlyonUI multi-select or custom wrapper | `MultiSelect.tsx`, EventDetail, ContactEdit | 2–3h |
| 3.4 | Replace `StatusPicker` with FlyonUI Dropdown + Badge | `StatusPicker.tsx` | 1h |

**Risk**: SelectPicker/MultiSelect have custom logic (fullscreen overlay for >5 options, search, i18n). May need to keep wrapper and only style with FlyonUI, or extend FlyonUI component.

---

### Phase 4: Layout & Shell (Medium Risk)
**Goal**: Replace Sidebar, Topbar, MobileNavDrawer.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 4.1 | Replace `AppSidebar` with FlyonUI Sidebar | `AppSidebar.tsx` | 2h |
| 4.2 | Replace `AppTopbar` with FlyonUI Navbar | `AppTopbar.tsx` | 2h |
| 4.3 | Replace `MobileNavDrawer` with FlyonUI Drawer (Offcanvas) | `MobileNavDrawer.tsx` | 1.5h |

**Risk**: Current layout is tightly integrated with routing and module config. Ensure FlyonUI Sidebar/Navbar support dynamic nav items and active states.

---

### Phase 5: Cards, Tables & Lists (Lower Risk)
**Goal**: Use FlyonUI Card, Table, List Group where beneficial.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 5.1 | Replace `DetailCard` with FlyonUI Card | `DetailCard.tsx`, all Detail/Edit views | 1h |
| 5.2 | Enhance `ResponsiveTable` with FlyonUI Table classes | `ResponsiveTable.tsx` | 1h |
| 5.3 | Consider FlyonUI Table for `ResizableTable` (keep resize logic) | `ResizableTable.tsx` | 1h |
| 5.4 | Align `EntityListRow` with FlyonUI List Group styling | `EntityListRow.tsx` | 1h |
| 5.5 | Upgrade `EventCard` with FlyonUI Card + Timeline | `EventCard.tsx` | 2h |

---

### Phase 6: Toasts & Theme (Lower Risk)
**Goal**: Replace ToastContainer, ThemeToggle.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 6.1 | Replace `ToastContainer` with FlyonUI Notyf (Toasts) | `ToastContainer.tsx`, `ToastContext.tsx` | 2h |
| 6.2 | Replace `ThemeToggle` with FlyonUI Theme Controller or Swap | `ThemeToggle.tsx` | 1h |

**Note**: Notyf may require different API. May need adapter in `ToastContext`.

---

### Phase 7: Polish & Entity-Specific (As Needed)
**Goal**: Upgrade remaining entity components.

| Step | Task | Files | Effort |
|------|------|-------|--------|
| 7.1 | Upgrade `ParticipationWidget` with FlyonUI Button group | `ParticipationWidget.tsx` | 1h |
| 7.2 | Upgrade `ParticipationDiagram` with FlyonUI Progress/Radial progress | `ParticipationDiagram.tsx` | 1.5h |
| 7.3 | Upgrade `SelectedItemsList` with FlyonUI Badge/List | `SelectedItemsList.tsx` | 1h |
| 7.4 | Replace native `select` in `ResponsiveTable` with FlyonUI Select | `ResponsiveTable.tsx` | 0.5h |

---

## 5. Module & View Coverage

### Modules

| Module | Key Components Used | Phases |
|--------|---------------------|--------|
| **Dashboard** | EventCard, ParticipationWidget | 5, 7 |
| **Concerts** | ResponsiveTable, ResizableTable, EntityListRow, ParticipationDiagram | 5 |
| **Rehearsals** | Same as Concerts | 5 |
| **Locations** | DetailCard, ResponsiveTable, ResizableTable, EntityListRow, LocationEdit | 5, 1 |
| **Equipment** | Same pattern | 5, 1 |
| **Outfits** | Same pattern | 5, 1 |
| **Repertoire** | Same pattern | 5 |
| **Votes** | Same pattern | 5 |
| **Contacts** | Same + SelectPicker, MultiSelect | 5, 1, 3 |
| **Users** | Same + SelectPicker | 5, 1, 3 |
| **Search** | EventCard, SearchAutocompleteOverlay | 5, 2 |
| **Profile** | DetailCard, EditingBar, SelectPicker | 5, 1, 3 |
| **Login** | Input, Button, ThemeToggle | 1, 6 |
| **Event Detail** | Modal, ConfirmModal, ParticipationModal, SelectPicker, MultiSelect, StatusPicker, EditingBar, DetailCard | 1, 2, 3, 5 |

### Views Summary

- **List views** (7): locations, equipment, outfits, repertoire, votes, contacts, users  
- **Detail views** (8): location, equipment, outfit, song, vote, contact, user, event  
- **Edit views** (8): same entities + profile  
- **Special**: dashboard, concerts, rehearsals, search, login

---

## 6. Risk & Considerations

| Risk | Mitigation |
|------|------------|
| **Theme mismatch** | Create custom FlyonUI theme mapping BNote CSS variables |
| **SelectPicker/MultiSelect complexity** | Keep custom logic, apply FlyonUI styling; or contribute to FlyonUI |
| **FlyonUI JS + Next.js** | Use `@source` or import in client bundle; ensure no SSR issues |
| **Breaking changes** | Phase incrementally; run E2E/visual tests per phase |
| **Bundle size** | Use `include` to load only needed FlyonUI components |
| **Accessibility** | FlyonUI uses Preline (accessible); verify ARIA and keyboard nav |

---

## 7. Questions for You

1. **Theme**: Do you want to keep the exact BNote color palette (`--primary: #3399ff`, etc.) or are you open to adopting a FlyonUI preset (e.g. `corporate`, `shadcn`) and adjusting?

2. **Icons**: The app uses `lucide-react`. FlyonUI docs suggest Tabler icons. Prefer:
   - Keep lucide-react (no change), or
   - Migrate to Tabler/Iconify for consistency with FlyonUI examples?

3. **SelectPicker / MultiSelect**: These have custom behavior (fullscreen overlay for long lists, search, i18n). Prefer:
   - Full replacement with FlyonUI Advanced select (if it supports all features), or
   - Keep logic, restyle with FlyonUI classes?

4. **Priority**: Which phase matters most to you first? (Recommendation: Phase 1 for quick wins, then Phase 2 for modals.)

5. **Testing**: Do you have E2E tests (Playwright, Cypress) or visual regression tests? If yes, we should run them after each phase.

6. **FlyonUI Pro**: Are you considering FlyonUI Pro for additional components, or sticking with the free tier?

---

## 8. Estimated Total Effort

| Phase | Effort (hours) |
|-------|----------------|
| Phase 1 | 7 |
| Phase 2 | 5 |
| Phase 3 | 6–7 |
| Phase 4 | 5.5 |
| Phase 5 | 6.5 |
| Phase 6 | 3 |
| Phase 7 | 4 |
| **Total** | **~37–39 hours** |

---

*Document created: 2026-02-15*
