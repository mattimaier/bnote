# Next.js App Parity Plan

**Goal:** Ensure the Next.js frontend matches the vanilla JavaScript app as closely as possible, with all icons, images, and assets working correctly.

---

## 1. Icons

### 1.1 Entity Config Icons (Missing in `icons.tsx`)

The `config/entity-config.json` references icons that may not exist in `frontend/components/icons.tsx`:

| Entity Config Icon | Lucide Equivalent | Status |
|--------------------|-------------------|--------|
| `trumpet`          | Lucide has no trumpet | **Missing** – use `Music2` or `Music` as fallback |
| `user-circle`      | `UserCircle`      | **Add** |
| `check-square`     | `CheckSquare`     | **Add** |
| `package`          | `Package`         | **Add** |
| `map`              | `Map`             | **Add** |

**Action:** Extend `frontend/components/icons.tsx` to include:
- `UserCircle`, `CheckSquare`, `Package`, `Map`
- Map `trumpet` → `Music` or `Music2` (Lucide has no trumpet icon)
- Add any other icons used in vanilla: `plus`, `x`, `arrow-left`, `sun`, `moon`, `menu`, `box-arrow-in-up-right`, `people`, `printer`, `file-text`, `shield-alert`, `clock`, `mail`, `phone`, `tag`, `chevron-right`, `chevron-down`, `more-vertical`, `inbox`, `construction`

### 1.2 Vanilla App Icons (data-lucide)

All vanilla icons use `data-lucide` from unpkg Lucide. Ensure Next.js `getIcon()` covers every icon name used:
- Topbar: `menu`, `search`, `x`, `sun`, `moon`
- Sidebar/Mobile nav: `layout-dashboard`, `users`, `user`, `user-cog`, `chevron-right`, `x`
- Dashboard: `calendar-days`, `message-square`, `music`, `clock`, `map-pin`
- Contacts: `plus`, `box-arrow-in-up-right`, `people`, `printer`, `file-text`, `shield-alert`, `arrow-left`, `x`
- Users: `plus`, `shield-alert`, `arrow-left`, `x`, `key`, `pencil`, `trash-2`
- Search results: `mail`, `phone`, `music`, `calendar`, `user`, `tag`, `map-pin`
- Event detail: `map-pin`, entity icons from config
- Forms/Tables: `chevron-down`, `more-vertical`, `search`, `inbox`

---

## 2. Images & Assets

### 2.1 BNote Logo

**Vanilla:** Uses `BNoteConfig.getBNoteResource('style/images/BNote_Logo_white_transparent.svg')`  
**Next.js:** `getBnoteLogoUrl()` in `lib/bnote-assets.ts` – points to `BNote/style/images/BNote_Logo_white_transparent.svg`

**Actual path:** `BNote/style/images/BNote_Logo_white_transparent.svg` exists in the BNote repo.

**Verification:**
- [ ] Logo loads on sidebar (desktop)
- [ ] Logo loads on mobile nav drawer
- [ ] Logo loads on login page (if applicable)
- [ ] Fallback "B" shown when logo URL is empty (e.g. dev without BNote path)

**Environment:** `NEXT_PUBLIC_API_BASE` or URL path must correctly resolve to BNote folder. For local dev: `http://localhost:8888/Bnote/` → logo at `.../BNote/style/images/BNote_Logo_white_transparent.svg`.

### 2.2 Favicon

**Vanilla:** Likely inherits from host or has favicon in root.  
**Next.js:** `app/favicon.ico` exists.

**Action:** Ensure favicon matches BNote branding if desired.

### 2.3 Public Assets

**Next.js `public/`:** Contains `file.svg`, `globe.svg`, `next.svg`, `vercel.svg`, `window.svg` (default Next.js assets).  
**Action:** Replace or remove default Next.js SVGs if not used; add any BNote-specific assets if needed.

---

## 3. Feature Parity Checklist

### 3.1 Dashboard
| Feature | Vanilla | Next.js | Notes |
|---------|---------|---------|-------|
| Welcome header (greeting + band name) | ✓ | ✓ | Fixed company name normalization |
| Quick Actions bar | Hidden in vanilla (`class="hidden"`) | Shown | **Decision:** Keep visible in Next.js or match vanilla? |
| Events needing response | ✓ | ✓ | |
| Filter bubbles (rehearsal/performance) | ✓ | ✓ | |
| Upcoming events timeline | ✓ | ✓ | |
| Event cards (mobile vs desktop layout) | ✓ | ✓ | |
| Participation buttons | ✓ | ✓ | |
| Load more | ✓ | ✓ | |

**Quick Actions:** Vanilla `dashboard.html` has `id="quick-actions-card" class="hidden"` – the bar is intentionally hidden. Next.js shows it. **Align:** Either unhide in vanilla or hide in Next.js per product decision.

### 3.2 Contacts
| Feature | Vanilla | Next.js |
|---------|---------|---------|
| Contact list | ✓ | ✓ |
| Add/Edit modals | ✓ | ✓ |
| Action buttons (Integrate, Print, vCard, GDPR, etc.) | ✓ | Partial (some `#` placeholders) |
| Groups filter | ✓ | ✓ |
| Search | ✓ | ✓ |

### 3.3 Users
| Feature | Vanilla | Next.js |
|---------|---------|---------|
| User list | ✓ | ✓ |
| Add/Edit modals | ✓ | ✓ |
| Manage privileges | ✓ | ✓ |
| Activate/Deactivate | ✓ | ✓ |
| GDPR modal | ✓ | ✓ |

### 3.4 Search
| Feature | Vanilla | Next.js |
|---------|---------|---------|
| Search input + autocomplete overlay | ✓ | ✓ |
| Search results page | ✓ | ✓ |
| Filters (year, month, entity type) | ✓ | ✓ |
| Entity-type icons in results | ✓ | ✓ (verify all icons mapped) |

### 3.5 Entity Detail (Rehearsals/Concerts)
| Feature | Vanilla | Next.js |
|---------|---------|---------|
| Event metadata (date, time, location, etc.) | ✓ | ✓ |
| Participation widget | ✓ | ✓ |
| Participation diagram | ✓ | ✓ |
| Participant overview | ✓ | ✓ |
| Map links (Google/Apple) | ✓ | ✓ |
| Songs to practice | ✓ | ✓ |
| Additional metadata (line-up, programme, outfit, etc.) | ✓ | ✓ |

### 3.6 Layout & Shell
| Feature | Vanilla | Next.js |
|---------|---------|---------|
| Sidebar (desktop) | ✓ | ✓ |
| Mobile nav drawer | ✓ | ✓ |
| Topbar (search, theme toggle, user) | ✓ | ✓ |
| Theme toggle (light/dark) | ✓ | ✓ |
| Search overlay (desktop vs mobile) | ✓ | ✓ |

### 3.7 Login
| Feature | Vanilla | Next.js |
|---------|---------|---------|
| Username/Password form | ✓ | ✓ |
| Theme toggle | ✓ | ✓ |
| BNote logo | ✓ | ✓ |
| Redirect after login | ✓ | ✓ |

---

## 4. CSS & Styling Parity

### 4.1 Source Files
- **Vanilla:** `assets/css/app.css` (shared)
- **Next.js:** `app/globals.css` + Tailwind

**Action:** Compare `app.css` with `globals.css` for:
- CSS variables (--primary, --accent, etc.)
- Participation button styles
- Filter bubble styles
- Event badge styles
- Sidebar/topbar styles

### 4.2 Entity Colors
Entity config uses hex and oklch. Ensure Next.js uses the same values for badges, dots, and type-specific styling.

---

## 5. Translations

### 5.1 Keys
- [ ] All `js.*` keys used in Next.js exist in `lang/{en,de,fr,es}.json`
- [ ] `banner_Logout.welcome` and other PHP keys available from API
- [ ] Placeholder `%p` handling correct in `I18nContext.t()`

### 5.2 Recently Added
Keys added in recent fixes: `js.common.saved`, `js.common.deleted`, `js.common.confirmDelete`, `js.common.search`, `js.dashboard.noEventsNeedingResponse`, `js.contacts.*`, `js.users.*`.

---

## 6. API & Configuration

### 6.1 API Base URL
- **Env:** `NEXT_PUBLIC_API_BASE` (e.g. `http://localhost:8888/Bnote/bnote-next-generation`)
- **Rewrites:** Next.js proxies `/api/*` to PHP backend in dev
- **Static export:** Rewrites don't apply; app must be served with reverse proxy or same-origin API in production

### 6.2 Entity Config
- **Path:** `config/entity-config.json`
- **Next.js:** Load from `/config/entity-config.json` or fetch via API if needed
- **Usage:** EventCard, SearchAutocompleteOverlay, SearchResults, Entity detail

---

## 7. Implementation Order

### Phase 1: Icons & Images (High Impact)
1. Add missing icons to `icons.tsx`: `UserCircle`, `CheckSquare`, `Package`, `Map`, `Plus`, `X`, `ArrowLeft`, `Sun`, `Moon`, `Menu`, etc.
2. Map `trumpet` → `Music` or `Music2` in icon resolver
3. Verify logo URL resolution in dev and production
4. Test logo display in sidebar, mobile nav, login

### Phase 2: Quick Actions & UI Visibility
1. Decide: show or hide Quick Actions (match vanilla = hidden)
2. If hidden: add conditional or remove from dashboard
3. Verify no other elements are accidentally hidden

### Phase 3: Entity Config Integration
1. Ensure Next.js loads `entity-config.json` (or equivalent API)
2. Use entity config for search results, event badges, entity detail headers
3. Fallback for unknown entity types

### Phase 4: Remaining Feature Gaps
1. Contacts: wire up Integrate, Print, vCard, Send Mail (or mark as "coming soon")
2. Search: verify all entity-type icons render
3. Forms: ensure all Lucide icons in modals (plus, x, etc.) work

### Phase 5: Visual Regression
1. Side-by-side comparison: vanilla vs Next.js on desktop
2. Side-by-side comparison: vanilla vs Next.js on mobile
3. Theme toggle: light/dark in both
4. Cross-browser check

---

## 8. Verification Checklist

- [ ] All entity types show correct icon (rehearsal, concert, contact, user, task, etc.)
- [ ] BNote logo loads in sidebar, mobile nav, login
- [ ] No broken image placeholders
- [ ] Participation buttons styled like vanilla
- [ ] Filter bubbles styled like vanilla
- [ ] Event cards match mobile/desktop layouts
- [ ] Search autocomplete overlay works on desktop and mobile
- [ ] Entity detail shows all metadata and participation UI
- [ ] Theme toggle works and persists
- [ ] All translation keys resolve (no raw keys visible)
- [ ] Band/company name displays in dashboard subtitle
- [ ] Quick Actions visibility matches product decision

---

## 9. Files to Modify

| File | Changes |
|------|---------|
| `frontend/components/icons.tsx` | Add missing Lucide icons, map entity-config names |
| `frontend/lib/bnote-assets.ts` | Verify logo URL logic for all deployment scenarios |
| `frontend/app/(app)/dashboard/page.tsx` | Quick Actions visibility (if hiding) |
| `frontend/app/globals.css` | Align with vanilla `app.css` if gaps found |
| `frontend/components/EventCard.tsx` | Use entity config for icons |
| `frontend/components/SearchAutocompleteOverlay.tsx` | Verify entity icons |
| `frontend/app/(app)/search/page.tsx` | Verify entity icons in results |
| `frontend/app/(app)/entity/page.tsx` | Use entity config for header icon |
