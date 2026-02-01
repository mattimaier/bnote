# BNote Next Generation – Frontend

This is the **only** UI for BNote Next Generation: a Next.js app with static export. It uses the PHP REST API in `../api/` and does not run a Node server in production.

## Stack

- Next.js (App Router), React, TypeScript
- Tailwind CSS, Lucide Icons
- Static export (`output: 'export'`)

## Development

**Requirements:** Node.js 18+.

```bash
npm install
npm run dev
```

Open http://localhost:3000. Requests to `/api/*` are proxied to the PHP backend (see `next.config.ts`). Run your PHP/BNote server at the URL configured in `NEXT_PUBLIC_API_BASE` or the default (e.g. `http://localhost:8888/Bnote/bnote-next-generation`).

## Build and deploy

```bash
npm run build
```

Output is in `out/`. Copy the **contents** of `out/` into the same web root that serves `api/` and `lang/` (do not overwrite those folders). The root `index.html` in production is the one from this build.

## Project layout

- `app/` – App Router pages (login, dashboard, users, contacts, search, entity)
- `components/` – Shared React components
- `lib/` – API client, auth, entity config, utilities
- `config/` – e.g. `entity-config.json`
- `contexts/` – I18n, toast, search

See the repo root [README.md](../README.md) and [docs/FEATURES_AND_BEHAVIORS.md](../docs/FEATURES_AND_BEHAVIORS.md) for behavior and regression reference.
