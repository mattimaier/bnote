# BNote Next Generation

## Overview

BNote Next Generation is a modern UI for BNote, an open-source ensemble management system for bands, orchestras, and choirs. The **only** UI is the Next.js app in `frontend/`. It talks to a PHP REST API in `api/` and keeps full compatibility with the existing BNote backend.

## Goals

- **Single UI:** Next.js (React) with static export; no other frontend in this repo.
- **Fast, responsive:** No full-page reloads for in-app navigation.
- **Backend unchanged:** PHP API wraps existing BNote data/logic; no changes to BNote core.
- **Mobile-first:** Responsive layout, sidebar on desktop, drawer on mobile.

## Technology Stack

**Frontend:** Next.js (static export), React, TypeScript, Tailwind CSS, Lucide Icons. Lives in `frontend/`.  
**Backend:** PHP REST API in `api/` (router, module handlers), existing BNote data/logic (unchanged).  
**Auth:** PHP session-based; cookie sent with same-origin API requests.

## Directory Structure

```
bnote-next-generation/
├── api/                    # PHP REST API (router, modules)
├── frontend/                # Next.js app (only UI)
│   ├── app/                 # App Router pages
│   ├── components/
│   ├── lib/
│   ├── config/
│   └── out/                 # Static export (after build)
├── lang/                    # Translation files (de, en, es, fr)
├── config/                  # Optional server config
├── docs/                    # Documentation
└── iso3166-alpha3-to-alpha2.json  # Used by API
```

## Getting Started

Access: `http://your-bnote-installation/bnote-next-generation/`

After deployment, the app routes to login or dashboard based on session. The only UI is the Next.js static export (see Build and distribute).

### Local development (Next.js frontend)

**Requirements:** Node.js 18+ (LTS recommended).

**One-time setup** (run from `bnote-next-generation/`). Ensure your PHP/BNote server is running (e.g. `http://localhost:8888/Bnote/bnote-next-generation/`):

```bash
node -e "const m=parseInt(process.versions.node.split('.')[0],10); if(m<18) { console.error('Node 18+ required'); process.exit(1); }" && \
cd frontend && npm install && \
(test -f .env.example && cp -n .env.example .env.local) || true
```

**Start dev server:**

```bash
cd frontend && npm run dev
```

Open http://localhost:3000. Requests to `/api/*` are proxied to the PHP backend. Set `NEXT_PUBLIC_API_BASE` in `frontend/.env.local` if the API runs at a different URL.

Optional: in `frontend/` run `nvm use` (`.nvmrc` is set to Node 20).

### Build and distribute (Next.js frontend)

1. **Build:** From `bnote-next-generation/`:
   ```bash
   cd frontend && npm run build
   ```
   This produces the static export in `frontend/out/`.

2. **Deploy:** Copy the **contents** of `frontend/out/` into the web document root that already serves the PHP API (e.g. the `bnote-next-generation` folder). Do **not** overwrite `api/` or `lang/`; only add or update the static frontend files (e.g. `index.html`, `_next/`, `dashboard/`, `login/`, etc.).

3. **Server:** Ensure `…/api/*` is handled by PHP; all other requests serve static files. With static export, each route has its own HTML (e.g. `dashboard/index.html`), so no catch-all is required for basic routing.

The root `index.html` in production is the one from `frontend/out/index.html` (Next.js).

**Copy-paste for distributors:**

```bash
cd frontend && npm run build && echo "Copy the contents of frontend/out/ to your web root (do not overwrite api/, lang/)."
```

## Routing and Navigation

Routing is handled by the Next.js App Router (static export).

**Main routes:** `/` (redirects to `/dashboard` or `/login`), `/login`, `/dashboard`, `/users`, `/contacts`, `/search`, `/entity` (entity detail with query params).

**Deep linking:** Unauthenticated users hitting a protected URL are redirected to `/login?redirect=…`. After login they are sent to the original URL.

## Current Status

### Completed

- REST API router and authentication
- Login and dashboard
- Users module (CRUD, privileges, sortable table including date column)
- Contacts module (CRUD, groups, sortable table)
- Event participation system
- Internationalization (DE, EN, ES, FR)
- Dark mode and responsive sidebar
- Search (top-bar overlay and search results page)
- Entity detail (rehearsal/concert with participation)

**API modules:** auth, dashboard, users, contacts, rehearsals, concerts, participation, translations

### Not yet implemented

Additional BNote modules (Calendar, Messages, Venues, etc.) and features (advanced search, bulk operations, exports, etc.) as needed.

## Documentation

- **[docs/FEATURES_AND_BEHAVIORS.md](docs/FEATURES_AND_BEHAVIORS.md)** – Feature and behavior reference for regression checks and bug fixing.
- **[docs/API_ARCHITECTURE.md](docs/API_ARCHITECTURE.md)** – PHP API structure and patterns.
- **[docs/API_ENDPOINTS.md](docs/API_ENDPOINTS.md)** – API endpoint reference.

## License

GPL-3.0 (same as BNote)
