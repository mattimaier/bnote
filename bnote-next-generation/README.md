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

If `.deploy.env` exists, `build.sh` also generates runtime mail config files in the build bundle (`.htaccess` and `api/config/mail.local.php`) from the same `MAIL_*` values. This ensures **manual upload deployments** work without running `deploy.sh`.

Build-time config behavior:

- `.deploy.env` present + mail keys set: build includes generated mail runtime config
- `.deploy.env` missing: build succeeds, but prints a warning and skips mail runtime config generation
- `.deploy.env` present but required mail keys missing: build fails with a clear error

**Options:**

- `./build.sh --out myfolder` – output folder name (default: `build/`)

### Email (password reset, notifications)

Outbound mail is sent by **PHP** on your server (SMTP), not by the Next.js app. You set **`MAIL_*`** and **`NEXTGEN_PUBLIC_URL`** in the **web server / PHP environment** (e.g. Strato **`.htaccess`** with `SetEnv`). Never put the SMTP password in git or in any **`NEXT_PUBLIC_*`** variable.

Step-by-step setup—including a **Strato shared hosting** tutorial, security notes, and other hosts—is in **[docs/MAIL.md](docs/MAIL.md)**. The `./build.sh` script runs **Composer** in `api/` so **PHPMailer** is included in the folder you upload.

### Deploy (remote SFTP, one-time credentials setup)

Use the deploy script to upload the built bundle via SFTP (credentials from 1Password).

Mail and deploy credentials are configured once in `.deploy.env`.  
`build.sh` generates `.htaccess` and `api/config/mail.local.php` in the bundle from those values.

1. One-time setup (local files, gitignored):

```bash
cp .deploy.env.example .deploy.env
```

2. Fill `.deploy.env`:
   - `SFTP_URL`, `OP_USERNAME_REF`, `OP_PASSWORD_REF`
   - `MAIL_*`, `NEXTGEN_PUBLIC_URL` (single source of truth for mail credentials)
   - optional: `DEPLOY_WITH_BUILD`, `BUILD_DIR`
   - keep `DEPLOY_SYNC_HTACCESS=true` and `DEPLOY_SYNC_MAIL_CONFIG=true` (recommended)

3. Build and deploy:

```bash
./deploy.sh --with-build                 # build + remote deploy
./deploy.sh --no-build                   # deploy existing build only
./deploy.sh --test-only                  # validate target connection/config only
```

Manual deployment (without `deploy.sh`):

```bash
./build.sh
# then upload build/bnote-next-generation/ manually
```

This works for mail too, because `build.sh` already generated:
- `build/bnote-next-generation/.htaccess`
- `build/bnote-next-generation/api/.htaccess`
- `build/bnote-next-generation/api/config/mail.local.php`

Requirements:
- `op` (1Password CLI, signed in)
- `lftp`

Security notes:
- `.deploy.env` is gitignored, so real URL and secret references stay local.
- No password is stored in the repository; credentials are fetched from 1Password on each deploy run.
- `.deploy.env` is also the one source of truth for mail secrets used during deploy generation.

Local development remains unchanged:

```bash
cd frontend && npm run dev
```

**Share module not appearing / "Module not found: share":** The Share module requires `api/modules/share.php` on the server. Use `./build.sh` to create a full build that includes the API. If you deploy only `frontend/out/`, the API folder (and share.php) will be missing. The Share module must exist in BNote and the user must have permission (as in the old app).

## Routing and Navigation

Routing uses the Next.js App Router (static export). Module pages under **`frontend/app/_modules/*`** map to BNote modules returned by the API (sidebar `route`). Other flows live beside them in **`frontend/app/`**.

**Core:** `/` (dashboard or login), `/login`, `/register` (when enabled in config), `/dashboard`, `/users`, `/contacts`, `/contacts/integration`, `/search`, `/settings`, `/profile`, `/profile/edit`.

**Auth / mail flows:** `/reset-password`, `/reset-password/confirm`, `/participation/respond` (magic-link participation from invite mail).

**Legal:** `/legal/terms`, `/legal/privacy`, `/legal/imprint` (and mirrored `_modules/*` where used).

**Entities:** The main entity screen is **`/entity`** with **query params** `type`, `id`, and optional `edit=1` (see **`docs/UI_PATTERNS.md`**). Debug builds also expose path-shaped URLs under **`/debug/entity/…`**.

**Rehearsals:** `/rehearsals/series`, `/rehearsals/series/detail` (series flows).

**Developer tools:** Sidebar entry **Developer** (`/developer/`) appears only for **admin** users (same **`isAdmin`** rule as Band Overview: BNote superuser or admin group) and when running **`npm run dev`** or when the frontend is built with **`NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1`**. The hub links to **`/debug`** (API tester, entity mocks) and to loopback-only PHP scripts under **`api/debug/`** (mail previews, config JSON, etc.).

**Debug routes:** **`/debug`**, **`/debug/entity`**, etc. (source **`frontend/app/(dev)/debug/`**) are **admin-only** in the app under the same conditions. For production static bundles, `npm run build` runs **`scripts/prune-dev-artifacts.mjs`**, which removes **`/debug`** and **`/developer`** from **`frontend/out/`** unless **`NEXT_PUBLIC_ENABLE_DEVELOPER_TOOLS=1`**. **`./build.sh`** also removes **`api/debug/`** from the deploy copy unless that same variable is set when you run the script.

**Deep linking:** Unauthenticated users on a protected URL go to `/login?redirect=…`; after login they return to the original URL.

## Current Status

### PHP API (`api/modules/`)

Each file is one **`?module=`** handler (see **[docs/API_ARCHITECTURE.md](docs/API_ARCHITECTURE.md)**):

- **Core:** `auth`, `dashboard`, `users`, `contacts`, `translations`, `search`, `participation`, `kontaktdaten`
- **Events & scheduling:** `rehearsals`, `concerts`, `calendar`, `appointments`, `reservations`
- **Tasks & collaboration:** `tasks`, `comments`, `votes`, `news`
- **Resources:** `locations`, `equipment`, `outfits`, `repertoire`, `share`

Outbound mail, registration, and password-reset helpers live beside the router under **`api/`** (e.g. **`api/mail/`**, `nextgen_registration.php`, `nextgen_password_reset.php`); see **[docs/MAIL.md](docs/MAIL.md)**.

### Frontend (summary)

Session auth; REST API via `api/index.php`; internationalization (DE, EN, ES, FR); FlyonUI-based UI; dark/light theme; entity detail with participation, discussion, and module-specific lists (votes, tasks, news, share, calendar, etc.). Behavior details: **[docs/FEATURES_AND_BEHAVIORS.md](docs/FEATURES_AND_BEHAVIORS.md)**.

### Not covered in this UI (examples)

Legacy-only or not exposed as first-class Next Gen features may still exist in BNote core (e.g. some “Messages” workflows). Extend the app by adding routes and wiring new **`api/modules/*`** handlers as needed.

## Documentation

- **[docs/MAIL.md](docs/MAIL.md)** – SMTP, subsystem map, transactional mail, password reset.
- **[docs/FEATURES_AND_BEHAVIORS.md](docs/FEATURES_AND_BEHAVIORS.md)** – Feature and behavior reference for regression checks.
- **[docs/UI_PATTERNS.md](docs/UI_PATTERNS.md)** – Lists, entity edit URLs, delete patterns.
- **[docs/API_ARCHITECTURE.md](docs/API_ARCHITECTURE.md)** – PHP API structure and patterns.
- **[docs/API_ENDPOINTS.md](docs/API_ENDPOINTS.md)** – Endpoint reference.
- **[docs/KNOWN_ISSUES.md](docs/KNOWN_ISSUES.md)** – Known backend/UI issues (legacy + mitigations).
- **[docs/entity-view-edit-plan.md](docs/entity-view-edit-plan.md)** – Entity view/edit refactor notes.

## License

GPL-3.0 (same as BNote)
