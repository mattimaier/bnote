# BNote Next Generation - Routing Architecture
**Version:** 1.0  
**Date:** 2026-01-28  
**Purpose:** Documentation of routing patterns and navigation architecture

---

## Table of Contents

1. [Overview](#overview)
2. [Routing Philosophy](#routing-philosophy)
3. [URL Structure](#url-structure)
4. [Navigation Patterns](#navigation-patterns)
5. [Page Types](#page-types)
6. [Deep Linking](#deep-linking)
7. [Browser History](#browser-history)
8. [Implementation Details](#implementation-details)
9. [Examples](#examples)

---

## 1. Overview

BNote Next Generation uses a **hybrid navigation approach** that combines traditional HTML page loads with modern AJAX updates:

- **Full Page Loads**: Module switching and entity detail views use traditional HTML navigation
- **Inline Updates**: Dynamic elements (search overlay, form submissions) use AJAX/fetch
- **Browser-Managed History**: Browser handles back/forward navigation automatically
- **Query Parameter URLs**: Simple, readable URLs using query parameters

### Key Principles

1. **KISS (Keep It Simple)**: Leverage browser's native navigation capabilities
2. **Single-Layer URLs**: URLs represent current state, not deep navigation stacks
3. **Predictable Behavior**: Users expect browser back button to work - it does
4. **Deep Linking**: Any URL can be bookmarked or shared
5. **Progressive Enhancement**: Works without JavaScript for basic navigation

---

## 2. Routing Philosophy

### Why Full Page Loads?

**Traditional HTML navigation** (full page loads) is used for:
- Module switching (dashboard → contacts → users)
- Opening entity detail views (rehearsal, concert, contact)
- Navigation between major sections

**Benefits:**
- ✅ Browser handles history automatically
- ✅ Back button works as users expect
- ✅ URLs are bookmarkable and shareable
- ✅ No complex client-side routing logic
- ✅ Simpler debugging (each page is independent)
- ✅ Better SEO (if needed in future)

### When to Use AJAX/Inline Updates

**AJAX/fetch** is reserved for:
- Search overlay with typeahead results
- Form submissions (participation, user edits)
- Dynamic content updates within a page
- Real-time updates (notifications, live data)

**Benefits:**
- ✅ Instant feedback without page reload
- ✅ Better UX for quick interactions
- ✅ Preserves scroll position
- ✅ Reduces server load for small updates

---

## 3. URL Structure

### URL Format

All URLs use **query parameters** for routing:

```
{page}.html?module={module}&entity={entity}&id={id}&mode={mode}
```

### URL Components

| Component | Description | Example | Required |
|-----------|-------------|---------|----------|
| `page` | HTML page file | `app.html`, `entity-detail.html`, `search.html` | Yes |
| `module` | Current module context | `dashboard`, `contacts`, `users` | Yes (for app.html) |
| `entity` | Entity type | `rehearsal`, `concert`, `contact` | No |
| `id` | Entity ID | `123`, `456` | No (required if entity present) |
| `mode` | View mode | `view`, `edit`, `create` | No (defaults to `view`) |

### URL Examples

```
# Dashboard module
app.html?module=dashboard

# Contacts module
app.html?module=contacts

# Rehearsal detail view (from dashboard)
entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view

# Concert detail view (from search)
entity-detail.html?module=search&entity=concert&id=456&mode=view

# Search results
search.html?search=query

# Login (with redirect)
login.html?redirect=entity-detail.html%3Fmodule%3Ddashboard%26entity%3Drehearsal%26id%3D123
```

---

## 4. Navigation Patterns

### 4.1 Module Navigation

**Pattern:** Click sidebar link → Full page load to `app.html`

```javascript
// Generated URL
app.html?module=contacts

// Implementation
<a href="app.html?module=contacts">Contacts</a>
```

**Flow:**
1. User clicks sidebar link
2. Browser navigates to `app.html?module=contacts`
3. `app.html` loads and initializes `ContactsModule`
4. Browser history entry created automatically

### 4.2 Entity Detail Navigation

**Pattern:** Click entity link → Full page load to `entity-detail.html`

```javascript
// Generated URL
entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view

// Implementation
<a href="entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view">
  View Rehearsal
</a>
```

**Flow:**
1. User clicks entity link (e.g., rehearsal card on dashboard)
2. Browser navigates to `entity-detail.html` with entity parameters
3. `entity-detail.html` loads and initializes entity handler
4. Entity detail view rendered
5. Browser history entry created automatically

### 4.3 Search Navigation

**Pattern:** Search overlay → Full page load to `search.html`

```javascript
// Generated URL
search.html?search=query

// Implementation
window.location.href = `search.html?search=${encodeURIComponent(query)}`;
```

**Flow:**
1. User types in search bar (overlay shows typeahead)
2. User clicks "Show all results" button
3. Browser navigates to `search.html?search=query`
4. Full search results page loads
5. Browser history entry created automatically

### 4.4 Back Navigation

**Pattern:** Browser back button → Previous page loads

**Flow:**
1. User clicks browser back button
2. Browser loads previous page from history
3. Previous page initializes normally
4. No special handling needed - browser does it all

---

## 5. Page Types

### 5.1 App Shell (`app.html`)

**Purpose:** Main application shell for module views

**URL Pattern:**
```
app.html?module={module}
```

**Parameters:**
- `module` (required): Module route (e.g., `dashboard`, `contacts`, `users`)

**Initialization:**
1. Check authentication (redirect to login if needed)
2. Render shell (sidebar, topbar)
3. Load module registry (`modules.json`)
4. Initialize module class (e.g., `DashboardModule`, `ContactsModule`)
5. Module renders its content

**Example:**
```html
<!-- app.html -->
<script>
(async () => {
    const session = await Auth.checkSession();
    if (!session.authenticated) {
        const currentUrl = window.location.pathname + window.location.search;
        window.location.href = `login.html?redirect=${encodeURIComponent(currentUrl)}`;
        return;
    }
    
    // Initialize app shell and module
    await AppShell.init();
})();
</script>
```

### 5.2 Entity Detail (`entity-detail.html`)

**Purpose:** Standalone page for entity detail views

**URL Pattern:**
```
entity-detail.html?module={module}&entity={entity}&id={id}&mode={mode}
```

**Parameters:**
- `module` (required): Module context (where user came from)
- `entity` (required): Entity type (e.g., `rehearsal`, `concert`)
- `id` (required): Entity ID
- `mode` (optional): View mode (`view`, `edit`, `create`), defaults to `view`

**Initialization:**
1. Check authentication (redirect to login if needed)
2. Render shell (sidebar, topbar)
3. Parse URL parameters
4. Load entity handler (e.g., `RehearsalHandler`, `ConcertHandler`)
5. Handler fetches entity data and renders detail view

**Example:**
```html
<!-- entity-detail.html -->
<script>
(async () => {
    const route = Router.parseUrl();
    if (route.entity && route.id) {
        const container = document.getElementById('entity-detail-container');
        await EntityService.renderEntityDetail(
            route.entity, 
            route.id, 
            route.mode, 
            route.module, 
            container
        );
    }
})();
</script>
```

### 5.3 Search Results (`search.html`)

**Purpose:** Full search results page

**URL Pattern:**
```
search.html?search={query}
```

**Parameters:**
- `search` (optional): Search query

**Initialization:**
1. Check authentication (redirect to login if needed)
2. Render shell (sidebar, topbar)
3. Parse search query from URL
4. Initialize search results page
5. Render search results

**Example:**
```html
<!-- search.html -->
<script>
(async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('q') || urlParams.get('search') || '';
    await SearchResultsPage.init(query, {});
})();
</script>
```

### 5.4 Login (`login.html`)

**Purpose:** Authentication page with redirect support

**URL Pattern:**
```
login.html?redirect={encodedUrl}
```

**Parameters:**
- `redirect` (optional): URL to redirect to after login (encoded)

**Flow:**
1. Check if already authenticated → redirect to target URL or dashboard
2. Show login form
3. On successful login → redirect to `redirect` parameter or dashboard

**Example:**
```html
<!-- login.html -->
<script>
(async () => {
    const session = await Auth.checkSession();
    if (session.authenticated) {
        const urlParams = new URLSearchParams(window.location.search);
        const redirectUrl = urlParams.get('redirect');
        if (redirectUrl) {
            window.location.href = decodeURIComponent(redirectUrl);
        } else {
            window.location.href = 'app.html?module=dashboard';
        }
    }
})();
</script>
```

---

## 6. Deep Linking

### 6.1 Protected Page Access

When a user tries to access a protected page while logged out:

1. **Capture Current URL**: Full URL (pathname + search) is captured
2. **Encode as Redirect**: URL is encoded and added as `redirect` parameter
3. **Redirect to Login**: User is redirected to `login.html?redirect={encodedUrl}`
4. **After Login**: User is redirected back to original URL

**Example:**
```
User tries: entity-detail.html?module=dashboard&entity=rehearsal&id=123
↓
Redirected to: login.html?redirect=entity-detail.html%3Fmodule%3Ddashboard%26entity%3Drehearsal%26id%3D123
↓
After login: entity-detail.html?module=dashboard&entity=rehearsal&id=123
```

### 6.2 Implementation

**In `index.html`, `app.html`, `entity-detail.html`, `search.html`:**

```javascript
(async () => {
    const session = await Auth.checkSession();
    if (!session.authenticated) {
        const currentUrl = window.location.pathname + window.location.search;
        if (currentUrl && !currentUrl.includes('index.html')) {
            window.location.href = `login.html?redirect=${encodeURIComponent(currentUrl)}`;
        } else {
            window.location.href = 'login.html';
        }
    }
})();
```

**In `login.html`:**

```javascript
(async () => {
    const session = await Auth.checkSession();
    if (session.authenticated) {
        const urlParams = new URLSearchParams(window.location.search);
        const redirectUrl = urlParams.get('redirect');
        if (redirectUrl) {
            window.location.href = decodeURIComponent(redirectUrl);
        } else {
            window.location.href = 'app.html?module=dashboard';
        }
    }
    
    // On login form submission:
    await Auth.login(username, password);
    const urlParams = new URLSearchParams(window.location.search);
    const redirectUrl = urlParams.get('redirect');
    if (redirectUrl) {
        window.location.href = decodeURIComponent(redirectUrl);
    } else {
        window.location.href = 'app.html?module=dashboard';
    }
})();
```

---

## 7. Browser History

### 7.1 Automatic History Management

The browser automatically manages history for full page loads:

- **Navigation**: Each page load creates a history entry
- **Back Button**: Browser loads previous page from history
- **Forward Button**: Browser loads next page from history
- **No JavaScript Needed**: Browser handles everything

### 7.2 History State

For search results page, `history.replaceState` is used on initial load to prevent duplicate entries:

```javascript
// In SearchResultsPage.init()
updateUrl(useReplaceState = false) {
    const urlParams = new URLSearchParams();
    urlParams.set('search', encodeURIComponent(this.currentQuery));
    const url = '?' + urlParams.toString();
    
    if (useReplaceState || (history.state && history.state.view === 'search-results')) {
        history.replaceState({ view: 'search-results', query: this.currentQuery }, '', url);
    } else {
        history.pushState({ view: 'search-results', query: this.currentQuery }, '', url);
    }
}

// On initial load
await SearchResultsPage.init(query, filters);
this.updateUrl(true); // Use replaceState to avoid duplicate entry
```

---

## 8. Implementation Details

### 8.1 Router (`router.js`)

Simple URL parser - no client-side routing logic:

```javascript
const Router = {
    parseUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            module: urlParams.get('module') || 'dashboard',
            entity: urlParams.get('entity'),
            id: urlParams.get('id') ? parseInt(urlParams.get('id'), 10) : null,
            mode: urlParams.get('mode') || 'view'
        };
    }
};
```

### 8.2 Navigation Service (`navigation-service.js`)

URL generation helpers for `<a href>` links:

```javascript
const NavigationService = {
    getModuleUrl(moduleRoute) {
        return `app.html?module=${moduleRoute}`;
    },
    
    getEntityUrl(entityType, id, moduleContext, mode = 'view') {
        return `entity-detail.html?module=${moduleContext}&entity=${entityType}&id=${id}&mode=${mode}`;
    }
};
```

### 8.3 Entity Service (`entity-service.js`)

Entity detail URL generation and rendering:

```javascript
const EntityService = {
    getEntityDetailUrl(entityType, id, moduleContext, mode = 'view') {
        return NavigationService.getEntityUrl(entityType, id, moduleContext, mode);
    },
    
    async renderEntityDetail(entityType, id, mode, moduleContext, container) {
        // Load entity handler and render detail view
    }
};
```

### 8.4 Module Context

The `module` parameter in URLs represents the **context** (where the user came from), not ownership:

- **Dashboard → Rehearsal**: `module=dashboard`
- **Search → Rehearsal**: `module=search`
- **Contacts → Contact**: `module=contacts`

This allows:
- Proper back navigation (back button returns to correct module)
- Context-aware UI (e.g., different breadcrumbs)
- Analytics tracking (where did user come from?)

---

## 9. Examples

### 9.1 Module Navigation

**Sidebar Link:**
```html
<a href="app.html?module=contacts" class="sidebar-link">
  Contacts
</a>
```

**JavaScript Generation:**
```javascript
const url = NavigationService.getModuleUrl('contacts');
// Result: app.html?module=contacts
```

### 9.2 Entity Detail Navigation

**Dashboard Event Card:**
```javascript
// In DashboardModule or EventRenderer
const url = EntityService.getEntityDetailUrl('rehearsal', 123, 'dashboard', 'view');
// Result: entity-detail.html?module=dashboard&entity=rehearsal&id=123&mode=view

const link = `<a href="${url}">View Rehearsal</a>`;
```

**Search Results:**
```javascript
// In SearchResults
const url = EntityService.getEntityDetailUrl('concert', 456, 'search', 'view');
// Result: entity-detail.html?module=search&entity=concert&id=456&mode=view
```

### 9.3 Search Navigation

**Search Overlay Button:**
```javascript
// In SearchIntegration
openSearchResultsPage() {
    const query = this.searchInput.value.trim();
    const urlParams = new URLSearchParams();
    urlParams.set('search', query);
    window.location.href = `search.html?${urlParams.toString()}`;
}
```

### 9.4 Deep Link Example

**User shares URL:**
```
https://example.com/bnote-next-generation/entity-detail.html?module=dashboard&entity=rehearsal&id=123
```

**Flow:**
1. User opens URL (logged out)
2. Redirected to: `login.html?redirect=entity-detail.html%3Fmodule%3Ddashboard%26entity%3Drehearsal%26id%3D123`
3. User logs in
4. Redirected back to: `entity-detail.html?module=dashboard&entity=rehearsal&id=123`
5. Rehearsal detail view loads

---

## Summary

BNote Next Generation uses a **simple, browser-native routing approach**:

- ✅ **Full page loads** for major navigation (modules, entities)
- ✅ **AJAX updates** for dynamic content (search, forms)
- ✅ **Query parameters** for URL structure
- ✅ **Browser history** for back/forward navigation
- ✅ **Deep linking** with login redirect support
- ✅ **Single-layer URLs** (no complex navigation stacks)

This approach provides:
- **Predictable behavior** (users understand browser navigation)
- **Simple implementation** (no complex routing library)
- **Better performance** (browser optimizes page loads)
- **SEO-friendly** (if needed in future)
- **Maintainable** (less code, fewer bugs)
