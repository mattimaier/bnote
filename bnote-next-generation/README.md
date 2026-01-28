# BNote Next Generation README

## Overview

BNote Next Generation is a modern, JavaScript-based UI rewrite of BNote, an open-source ensemble management system for bands, orchestras, and choirs. This new interface provides a faster, more responsive user experience while maintaining full compatibility with the existing PHP backend.

## Goals

The primary goal is to deliver a **faster, modern JavaScript-based UI** that:
- Provides instant, responsive interactions without full page reloads
- Offers a clean, intuitive interface built with modern web technologies
- Maintains full backward compatibility with the existing BNote backend
- Supports progressive migration (old and new UI can coexist)
- Delivers a mobile-first, responsive design experience

## Advantages

**Performance:** No page reloads, faster rendering, optimized JSON API  
**User Experience:** Modern UI with Tailwind CSS, dark mode support, responsive design, real-time updates  
**Developer Experience:** Vanilla JavaScript (no framework dependencies), modular architecture, built-in i18n support  
**Technical:** Progressive migration, session compatibility, backend preservation, future-ready for mobile apps

## Development Approach with Cursor

BNote Next Generation was developed using **Cursor AI** to accelerate the rewrite process:

**Strategy:**
- **Separate Modern UI**: Created `/bnote-next-generation` directory as a parallel implementation alongside the existing UI
- **REST API Layer**: Built lightweight JSON API (`/bnote-next-generation/api/`) that wraps existing BNote data/logic layers without modification
- **Path Configuration**: Uses `BNOTE_ROOT` constant (defined in `bnote-next-generation/api/paths.php`) to reference the old BNote codebase
- **Incremental Development**: Started with core modules (login, dashboard) and expanded feature by feature
- **AI-Assisted Coding**: Used Cursor's AI capabilities to:
  - Generate API endpoints that bridge to existing PHP classes
  - Convert React/v0.dev prototypes to vanilla JavaScript
  - Create reusable UI components following consistent patterns
  - Implement i18n support across all user-facing strings
  - Ensure responsive design with Tailwind CSS

**Key Decisions:**
- **No Backend Changes**: Existing `/src/data/` and `/src/logic/` layers remain untouched
- **Vanilla JavaScript**: No build tools or frameworks for faster iteration
- **CDN Dependencies**: Tailwind CSS and Lucide icons via CDN for simplicity
- **Progressive Enhancement**: Each module can be developed and tested independently

**Workflow:**
1. Design API endpoints that match existing BNote functionality
2. Create module handlers that call existing data/logic classes
3. Build frontend components using vanilla JavaScript
4. Test each module independently before moving to the next
5. Iterate based on user feedback and requirements

## Architecture

### High-Level Overview

```
Frontend (Browser)          Backend (PHP)
┌─────────────┐            ┌─────────────┐
│ HTML/CSS/JS │            │ REST API    │
│ (Vanilla)   │───────────▶│ Router      │
│ Tailwind    │  JSON     │             │
└─────────────┘            └──────┬──────┘
                                   │
                          ┌────────▼────────┐
                          │ Existing BNote │
                          │ Data/Logic     │
                          │ (Unchanged)    │
                          └────────────────┘
```

### Technology Stack

**Frontend:**
- Vanilla JavaScript (ES6+) - no frameworks
- Tailwind CSS (via CDN) - utility-first styling
- Lucide Icons - modern icon library
- Fetch API - HTTP requests

**Backend:**
- PHP REST API router (`/next/api/index.php`)
- Module-based handlers (`/next/api/modules/`)
- Existing BNote data/logic layers (unchanged)
- PHP session-based authentication

### Directory Structure

```
next/
├── assets/
│   ├── css/app.css       # Custom styles
│   └── js/               # JavaScript modules
│       ├── api.js        # API client
│       ├── dashboard.js  # Dashboard logic
│       └── ...
├── api/
│   ├── index.php         # API router
│   └── modules/          # API handlers
├── lang/                 # Translation files (de, en, es, fr)
└── *.html                # Page templates
```

### Data Flow

1. User interaction → JavaScript handler
2. API call via `api.js` → Backend router
3. Module handler → Existing BNote classes
4. JSON response → Frontend updates DOM

## Current Status

### ✅ Completed

**Core:**
- [x] REST API router and authentication
- [x] Login and dashboard pages
- [x] Users module (CRUD, privileges, GDPR)
- [x] Contacts module (CRUD, groups, VCard import)
- [x] Event participation system
- [x] Internationalization (DE, EN, ES, FR)
- [x] Dark mode and responsive sidebar

**API Modules:** auth, dashboard, users, contacts, rehearsals, concerts, participation, translations

### 🚧 In Progress

- [ ] Full localization coverage
- [ ] Address formatting improvements

### ❌ Not Yet Implemented

**Modules:** Calendar, Messages, Venues, Documents, Repertoire, Program, Finance, Tours, Equipment, Outfits, Accommodation, Travel, Stats, Admin, Help, Custom Fields, Voting, Tasks, Appointments, and more

**Features:** Advanced search, bulk operations, exports, file uploads, real-time notifications, data visualization, print views

## Getting Started

Access via: `http://your-bnote-installation/bnote-next-generation/`

The system automatically routes to login or dashboard based on session.

## Routing and Navigation

BNote Next Generation uses a **hybrid navigation approach**:

- **Full page loads** for module switching and entity detail views
- **AJAX updates** for dynamic content (search overlay, form submissions)
- **Browser-managed history** - back/forward buttons work automatically

### URL Structure

All URLs use query parameters:

```
{page}.html?module={module}&entity={entity}&id={id}&mode={mode}
```

### Examples

**Module Navigation:**
```
# Dashboard
app.html?module=dashboard

# Contacts
app.html?module=contacts

# Users
app.html?module=users
```

**Entity Detail Views:**
```
# Rehearsal detail (from dashboard)
entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view

# Concert detail (from search)
entity-detail.html?module=search&entity=concert&id=456&mode=view
```

**Search:**
```
# Search results
search.html?search=query
```

**Deep Linking:**
```
# Accessing protected page while logged out preserves URL
User tries: entity-detail.html?module=dashboard&entity=rehearsal&id=123
↓
Redirected to: login.html?redirect=entity-detail.html%3Fmodule%3Ddashboard%26entity%3Drehearsal%26id%3D123
↓
After login: entity-detail.html?module=dashboard&entity=rehearsal&id=123
```

### Navigation Services

**Router** (`router.js`):
```javascript
const route = Router.parseUrl();
// Returns: { module, entity, id, mode }
```

**NavigationService** (`navigation-service.js`):
```javascript
// Module URL
const url = NavigationService.getModuleUrl('contacts');
// Returns: app.html?module=contacts

// Entity URL
const url = NavigationService.getEntityUrl('rehearsal', 123, 'dashboard', 'view');
// Returns: entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view
```

**EntityService** (`entity-service.js`):
```javascript
// Entity detail URL
const url = EntityService.getEntityDetailUrl('rehearsal', 123, 'dashboard', 'view');
// Returns: entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view
```

For complete routing documentation, see [docs/ROUTING_ARCHITECTURE.md](./docs/ROUTING_ARCHITECTURE.md).

## License

GPL-3.0 (same as BNote)
