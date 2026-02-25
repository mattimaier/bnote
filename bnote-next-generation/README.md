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

Open http://localhost:3000/bnote-next-generation (default base path). Requests to `/api/*` are proxied to the PHP backend. Set `NEXT_PUBLIC_API_BASE` in `frontend/.env.local` if the API runs at a different URL. To run the app at root (http://localhost:3000), use `NEXT_PUBLIC_BASE_PATH= npm run dev`.

Optional: in `frontend/` run `nvm use` (`.nvmrc` is set to Node 20).

### Build (single folder to upload)

Use the build script to build the frontend and assemble **one folder** you can upload (api + frontend + lang + config):

```bash
./build.sh
```

This creates `build/` with everything. Copy the **contents** of `build/` to your server (e.g. into the folder served at `…/bnote-next-generation/`).

For local debugging use `npm run dev` (see Local development above); the dev server proxies API requests to your PHP backend.

**Options:**

- `./build.sh --out myfolder` – output folder name (default: `build/`)

**Share module not appearing / "Module not found: share":** The Share module requires `api/modules/share.php` on the server. Use `./build.sh` to create a full build that includes the API. If you deploy only `frontend/out/`, the API folder (and share.php) will be missing. The Share module must exist in BNote and the user must have permission (as in the old app).

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

**API modules:** auth, dashboard, users, contacts, rehearsals, concerts, participation, translations, share

### Not yet implemented

Additional BNote modules (Calendar, Messages, Venues, etc.) and features (advanced search, bulk operations, exports, etc.) as needed.

## Documentation

- **[docs/FEATURES_AND_BEHAVIORS.md](docs/FEATURES_AND_BEHAVIORS.md)** – Feature and behavior reference for regression checks and bug fixing.
- **[docs/API_ARCHITECTURE.md](docs/API_ARCHITECTURE.md)** – PHP API structure and patterns.
- **[docs/API_ENDPOINTS.md](docs/API_ENDPOINTS.md)** – API endpoint reference.
- **[docs/RICH_NOTES.md](docs/RICH_NOTES.md)** – Rich notes (EditorJS): why plain text and JSON are stored separately, load/save flow, and orphan cleanup. The `rich_notes` table is created automatically on first use of the rich-notes API; no manual migration is required.

## License

GPL-3.0 (same as BNote)
