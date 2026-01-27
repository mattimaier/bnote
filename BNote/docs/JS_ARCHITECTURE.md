# BNote Next Generation JavaScript Architecture
**Version:** 2.0  
**Date:** 2026-01-27  
**Purpose:** Vanilla JavaScript architecture for BNote Next Generation frontend

---

## Table of Contents

1. [Overview](#overview)
2. [Directory Structure](#directory-structure)
3. [Core Modules](#core-modules)
4. [Internationalization (i18n)](#internationalization-i18n)
5. [Dark Mode Support](#dark-mode-support)
6. [API Client](#api-client)
7. [Routing and Deeplinks](#routing-and-deeplinks)
8. [Component System](#component-system)
9. [UI Components](#ui-components)
10. [Best Practices](#best-practices)
11. [Module Examples](#module-examples)

---

## 1. Overview

### 1.1 Goals

- **Vanilla JavaScript:** No frameworks (React/Vue/Angular)
- **Modular:** ES6 modules, clear separation of concerns
- **Reusable:** Component library for common UI patterns
- **Maintainable:** Clean code, well-documented
- **Performant:** Lazy loading, efficient DOM updates
- **Mobile-Friendly:** Responsive design, touch support

### 1.2 Constraints

- **No Build Tools:** Must run directly in browser
- **No Node.js:** Pure client-side JavaScript
- **ES6+ Support:** Modern browsers only (IE11+ or modern browsers)
- **CDN Dependencies:** External libraries via CDN

### 1.3 Technology Stack

- **JavaScript:** ES6+ (vanilla JavaScript, no frameworks)
- **CSS:** Tailwind CSS 3.x (via CDN)
- **Icons:** Lucide Icons (via CDN)
- **HTTP:** Fetch API
- **Storage:** localStorage (theme preference), sessionStorage
- **DOM:** Native DOM APIs
- **Internationalization:** Custom i18n service with JSON translations
- **Theming:** CSS variables with dark mode support

---

## 2. Directory Structure

```
next/
├── assets/
│   ├── css/
│   │   └── app.css          # Custom styles and CSS variables
│   └── js/                   # JavaScript modules
│       ├── api.js            # API client
│       ├── app.js            # Application initialization
│       ├── auth.js           # Authentication helpers
│       ├── i18n.js           # Internationalization service
│       ├── theme-toggle.js   # Dark mode toggle utility
│       ├── dashboard.js      # Dashboard module
│       ├── sidebar.js       # Sidebar navigation
│       ├── routing.js        # Routing and deeplink utility
│       ├── users.js          # User management
│       ├── contacts.js       # Contact management
│       ├── participation.js  # Participation widget
│       ├── event-detail.js   # Event detail view
│       ├── form.js           # Form component
│       ├── table.js          # Table component
│       ├── badge.js          # Badge component
│       └── ...               # Other modules
├── lang/                     # Translation files
│   ├── de.json              # German translations
│   ├── en.json              # English translations
│   ├── es.json              # Spanish translations
│   └── fr.json              # French translations
└── *.html                    # Page templates
```

---

## 3. Core Modules

### 3.1 Application Entry (`next/assets/js/app.js`)

The `App` object handles application initialization:

```javascript
const App = {
    async init() {
        // Initialize i18n first
        await this.initI18n();
        
        // Global error handlers
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
        });
    },
    
    async initI18n() {
        // Get language from system configuration
        const configResponse = await api.get('auth', 'getUserLang');
        const langCode = configResponse.lang || 'de';
        const countryCode = configResponse.country || null;
        
        // Initialize i18n with system config language
        await i18n.init(langCode, countryCode);
    }
};
```

### 3.2 Authentication (`next/assets/js/auth.js`)

The `Auth` object handles authentication:

```javascript
const Auth = {
    async login(username, password) {
        await AuthApi.login(username, password);
        window.location.href = 'dashboard.html';
    },
    
    async logout() {
        await AuthApi.logout();
        window.location.href = 'login.html';
    },
    
    async checkSession() {
        return await AuthApi.checkSession();
    },
    
    redirectIfNotAuthenticated() {
        // Check session and redirect if needed
    }
};
```

---

## 4. Internationalization (i18n)

### 4.1 Mandatory Requirement

**ALL user-facing strings MUST use the i18n system.** No hardcoded text in HTML or JavaScript.

### 4.2 i18n Service (`next/assets/js/i18n.js`)

The `i18n` object provides translation and localization:

```javascript
// Initialize with language and country
await i18n.init('de', 'DE');

// Translate a key
const text = i18n.t('js.dashboard.welcome');

// Translate with parameters
const greeting = i18n.t('js.dashboard.greeting', ['John']);

// Format dates/times using locale
const dateStr = i18n.formatDate(new Date());
const timeStr = i18n.formatTime(new Date());
```

### 4.3 Translation Files

Location: `next/lang/*.json`

**Key Format:** `js.{module}.{key}`

Example (`next/lang/de.json`):
```json
{
  "js.dashboard.welcome": "Willkommen",
  "js.dashboard.subtitle": "Willkommen bei %p",
  "js.common.save": "Speichern",
  "js.common.cancel": "Abbrechen"
}
```

### 4.4 HTML Usage

**Text Content:**
```html
<h1 data-i18n="js.dashboard.welcome">Welcome</h1>
```

**Placeholders:**
```html
<input data-i18n-placeholder="js.login.usernamePlaceholder" placeholder="Username" />
```

**Labels:**
```html
<button data-i18n-label="js.common.save">Save</button>
```

**Dynamic Translation:**
```javascript
// After i18n.init() and i18n.translatePage()
i18n.translatePage(); // Translates all data-i18n attributes
```

### 4.5 JavaScript Usage

**Always use i18n.t() for user-facing strings:**
```javascript
// GOOD
showToast(i18n.t('js.users.created'));
const errorMsg = i18n.t('js.error.loadFailed');

// BAD - Never hardcode strings
showToast('User created');
const errorMsg = 'Failed to load';
```

### 4.6 Date/Time Formatting

Uses system configuration language and country:

```javascript
// Format date (uses locale from system config)
const dateStr = i18n.formatDate(new Date());

// Format time (no seconds, follows locale hour12)
const timeStr = i18n.formatTime(new Date());

// Format date and time
const datetimeStr = i18n.formatDateTime(new Date());

// Parse API date strings
const date = i18n.parseEventDate('2026-01-27 19:00:00');
```

### 4.7 Supported Languages

- German (de) - Default
- English (en)
- Spanish (es)
- French (fr)

---

## 5. Dark Mode Support

### 5.1 Mandatory Requirement

**ALL UI components MUST support dark mode.** Use CSS variables and Tailwind `dark:` prefix.

### 5.2 Theme Toggle (`next/assets/js/theme-toggle.js`)

The `ThemeToggle` utility handles theme switching:

```javascript
// Initialize (respects system preference on first load)
ThemeToggle.init();

// Toggle theme
ThemeToggle.toggle();

// Get current theme
const theme = ThemeToggle.getCurrentTheme(); // 'light' or 'dark'

// Check if dark mode
if (ThemeToggle.isDark()) {
    // Dark mode specific logic
}
```

### 5.3 CSS Variables (`next/assets/css/app.css`)

Use semantic color variables that automatically adapt to dark mode:

```css
:root {
    --background: oklch(0.99 0.001 250);
    --foreground: oklch(0.18 0.01 250);
    --card: oklch(1 0 0);
    --border: oklch(0.93 0.002 250);
    --muted: oklch(0.96 0.002 250);
    --muted-foreground: oklch(0.50 0.01 250);
}

.dark {
    --background: oklch(0.22 0.01 250);
    --foreground: oklch(0.95 0.01 250);
    --card: oklch(0.25 0.01 250);
    --border: oklch(0.32 0.001 0);
    --muted: oklch(0.24 0.01 250);
    --muted-foreground: oklch(0.70 0.01 250);
}
```

### 5.4 Tailwind Classes

**Use semantic color classes:**
```html
<div class="bg-background text-foreground">
  <div class="bg-card border border-border">
    <p class="text-muted-foreground">Muted text</p>
  </div>
</div>
```

**Use dark: prefix when needed:**
```html
<div class="bg-background dark:bg-card">
  <p class="text-foreground dark:text-muted-foreground">
```

**Never hardcode colors:**
```html
<!-- BAD -->
<div class="bg-white text-black dark:bg-gray-800 dark:text-white">

<!-- GOOD -->
<div class="bg-background text-foreground">
```

### 5.5 Theme Persistence

Theme preference is saved in `localStorage` and persists across sessions. On first load, respects system preference (`prefers-color-scheme`).

---

## 6. API Client

The `Form` class provides reusable form UI:

```javascript
class Form {
    constructor(container, options) {
        this.container = container;
        this.options = options;
    }
    
    render() {
        // All labels and buttons use i18n
        this.container.innerHTML = `
            <form class="space-y-4">
                ${this.options.fields.map(field => `
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2"
                               data-i18n="${field.i18nLabel}">${field.label}</label>
                        <input class="w-full px-4 py-2 bg-background border border-border 
                                      text-foreground rounded-lg focus:ring-2 focus:ring-primary"
                               data-i18n-placeholder="${field.i18nPlaceholder}" />
                    </div>
                `).join('')}
                <button class="bg-primary text-primary-foreground px-4 py-2 rounded-lg"
                        data-i18n="${this.options.submitI18n}">Submit</button>
            </form>
        `;
        
        // Translate after rendering
        if (typeof i18n !== 'undefined' && i18n.translatePage) {
            i18n.translatePage();
        }
    }
}
```

### 6.1 API Client (`next/assets/js/api.js`)

The `Api` class handles all API requests:

```javascript
class Api {
    constructor() {
        // Computes base path to /next/api/index.php
        this.baseUrl = basePath + '/api/index.php';
    }
    
    async request(module, action, data = null, params = {}) {
        const url = new URL(this.baseUrl, window.location.origin);
        url.searchParams.set('module', module);
        if (action != null) {
            url.searchParams.set('action', action);
        }
        
        // Add params to URL
        Object.keys(params).forEach(key => {
            url.searchParams.set(key, params[key]);
        });
        
        const options = {
            method: data ? 'POST' : 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        };
        
        if (data) {
            options.body = JSON.stringify({ ...data, action });
        }
        
        const response = await fetch(url, options);
        // ... error handling
        return result.data;
    }
    
    async get(module, action, params = {}) {
        return this.request(module, action, null, params);
    }
    
    async post(module, action, data = {}, params = {}) {
        return this.request(module, action, data, params);
    }
}

const api = new Api();
```

### 6.2 Module-Specific API Helpers

Predefined helpers for each module:

```javascript
const AuthApi = {
    login: (username, password) => api.post('auth', 'login', { username, password }),
    logout: () => api.post('auth', 'logout'),
    checkSession: () => api.get('auth', 'session'),
    getModules: () => api.get('auth', 'getModules')
};

const DashboardApi = {
    getDashboard: () => api.get('dashboard', 'dashboard'),
    getEventsNeedingResponse: () => api.get('dashboard', 'eventsNeedingResponse'),
    respondToEvent: (otype, oid, attending, reason) => 
        api.post('dashboard', 'respondToEvent', { otype, oid, attending, reason })
};

const UsersApi = {
    list: () => api.get('users', 'list'),
    get: (id) => api.get('users', 'get', { id }),
    create: (data) => api.post('users', 'create', data),
    update: (id, data) => api.post('users', 'update', { id, ...data }),
    delete: (id) => api.post('users', 'delete', { id })
};
```

### 6.3 API URL Pattern

**Base URL:** `/next/api/index.php`

**Pattern:** `?module={module}&action={action}&{params}`

**Examples:**
```
GET  /next/api/index.php?module=dashboard&action=dashboard
GET  /next/api/index.php?module=rehearsals&id=42
POST /next/api/index.php?module=users&action=create
```

### 6.4 Response Format

**Success:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "error": "Error message",
  "code": 400
}
```

---

## 7. Routing and Deeplinks

### 7.1 Overview

BNote Next Generation supports direct links (deeplinks) to event details (concerts and rehearsals). The routing system automatically handles authentication flow, ensuring users can access event details whether they're logged in or not.

### 7.2 Routing Utility (`next/assets/js/routing.js`)

The `Routing` object provides URL parameter parsing and navigation state management:

```javascript
const Routing = {
    // Parse event parameters from URL
    getEventFromUrl() {
        // Returns { type: 'R'|'C', id: number } or null
        // Supports: ?rehearsal={id} or ?concert={id}
    },
    
    // Store pending navigation in sessionStorage
    storePendingNavigation(eventType, eventId) {
        // Persists through login redirect
    },
    
    // Get and clear pending navigation
    getPendingNavigation() {
        // Returns stored navigation and clears it
    },
    
    // Build dashboard URL with event parameters
    buildDashboardUrl(eventType, eventId) {
        // Returns: 'dashboard.html?rehearsal=123' or 'dashboard.html?concert=456'
    },
    
    // Clean URL by removing event parameters
    cleanUrl() {
        // Uses history.replaceState to update URL without reload
    }
};
```

### 7.3 URL Scheme

**Rehearsals:**
```
dashboard.html?rehearsal={id}
```

**Concerts:**
```
dashboard.html?concert={id}
```

**Examples:**
- `dashboard.html?rehearsal=536` - Opens rehearsal #536
- `dashboard.html?concert=123` - Opens concert #123

### 7.4 Authentication Flow

The routing system automatically handles authentication:

**Not Logged In:**
1. User visits `dashboard.html?rehearsal=123`
2. Redirected to `login.html?rehearsal=123`
3. Event parameter stored in `sessionStorage`
4. After successful login, redirected to `dashboard.html?rehearsal=123`
5. Dashboard shows event detail

**Already Logged In:**
1. User visits `dashboard.html?concert=456`
2. Dashboard shows event detail immediately

### 7.5 Implementation Details

**Login Page (`next/login.html`):**
- Checks for event parameters on page load
- Stores event parameters in `sessionStorage` before redirect
- Preserves event parameters after successful login
- Handles authenticated users with event parameters

**Dashboard Page (`next/dashboard.html`):**
- Parses URL parameters on initialization
- Checks `sessionStorage` for pending navigation (from login redirect)
- Initializes `EventDetail` component when event parameter found
- Handles browser back/forward navigation

**Event Detail Component (`next/assets/js/event-detail.js`):**
- Uses correct URL format (`?rehearsal=123` or `?concert=456`)
- Updates browser history with `pushState` for back button support
- Cleans URL when navigating back to dashboard

### 7.6 Access Control

**Past Events:**
- Past concerts and rehearsals are accessible via deeplinks
- Access control checks user permissions, not event dates
- Super users can access all events (past and future)
- Regular users can access events they're associated with (regardless of date)

**Error Handling:**
- Invalid event ID: Shows error message in event detail view
- Event not found: Shows "Event not found" error
- Missing permissions: API returns 403, shows appropriate error
- Malformed URL parameters: Ignored, shows normal dashboard

### 7.7 Usage Examples

**Creating a Deeplink:**
```javascript
// Build URL for a rehearsal
const url = Routing.buildDashboardUrl('R', 536);
// Returns: 'dashboard.html?rehearsal=536'

// Build URL for a concert
const url = Routing.buildDashboardUrl('C', 123);
// Returns: 'dashboard.html?concert=123'
```

**Parsing URL Parameters:**
```javascript
// Get event from current URL
const event = Routing.getEventFromUrl();
if (event) {
    console.log(`Event type: ${event.type}, ID: ${event.id}`);
    // Initialize event detail
    EventDetail.init(event.type, event.id);
}
```

**Storing Pending Navigation:**
```javascript
// Store event for after login
Routing.storePendingNavigation('R', 536);

// Later, retrieve and use
const pending = Routing.getPendingNavigation();
if (pending) {
    window.location.href = Routing.buildDashboardUrl(
        pending.eventType, 
        pending.eventId
    );
}
```

### 7.8 Future Extensions

The URL scheme supports future participation links:
- `dashboard.html?rehearsal=123&token=abc123` - Participation link with token
- `dashboard.html?concert=456&participation=yes` - Direct participation flow

The routing utility can be extended to handle these parameters without breaking existing functionality.

### 7.9 Browser History

The routing system uses the History API for proper browser navigation:
- `pushState` - Adds event detail view to history
- `popstate` - Handles browser back/forward buttons
- `replaceState` - Cleans URL when navigating back to dashboard

**Example:**
```javascript
// When opening event detail
const state = { view: 'event-detail', eventType: 'R', eventId: 536 };
const url = '?rehearsal=536';
history.pushState(state, '', url);

// Browser back button automatically triggers popstate event
window.addEventListener('popstate', (event) => {
    if (event.state && event.state.view === 'event-detail') {
        EventDetail.init(event.state.eventType, event.state.eventId);
    } else {
        EventDetail.navigateBack();
    }
});
```

---

## 8. Component System

### 8.1 Form Component (`next/assets/js/form.js`)

The `Form` class provides reusable form UI:

```javascript
class Form {
    constructor(container, options) {
        this.container = container;
        this.options = options;
    }
    
    render() {
        // All labels and buttons use i18n
        this.container.innerHTML = `
            <form class="space-y-4">
                ${this.options.fields.map(field => `
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2"
                               data-i18n="${field.i18nLabel}">${field.label}</label>
                        <input class="w-full px-4 py-2 bg-background border border-border 
                                      text-foreground rounded-lg focus:ring-2 focus:ring-primary"
                               data-i18n-placeholder="${field.i18nPlaceholder}" />
                    </div>
                `).join('')}
                <button class="bg-primary text-primary-foreground px-4 py-2 rounded-lg"
                        data-i18n="${this.options.submitI18n}">Submit</button>
            </form>
        `;
        
        // Translate after rendering
        if (typeof i18n !== 'undefined' && i18n.translatePage) {
            i18n.translatePage();
        }
    }
}
```

---

## 9. UI Components

### 9.1 Badge Component (`next/assets/js/badge.js`)

Reusable badge with i18n support:

```javascript
const Badge = {
    render(text, color = 'default', i18nKey = null) {
        // Use i18n if key provided
        const displayText = i18nKey ? i18n.t(i18nKey) : text;
        
        // Use semantic colors for dark mode
        return `
            <span class="px-2 py-1 rounded-md text-xs font-medium 
                         bg-muted text-muted-foreground">
                ${displayText}
            </span>
        `;
    }
};
```

### 9.2 Loading States

Always show loading states with i18n:

```javascript
// Loading spinner with i18n text
const loadingHTML = `
    <div class="flex items-center justify-center p-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <p class="ml-3 text-muted-foreground" data-i18n="js.common.loading">Loading...</p>
    </div>
`;
```

### 9.3 Toast Notifications

Toast messages must use i18n:

```javascript
function showToast(message, type = 'info') {
    // message should be i18n key or already translated
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 bg-card border border-border 
                       text-card-foreground px-4 py-3 rounded-lg shadow-lg`;
    toast.textContent = typeof message === 'string' && message.startsWith('js.') 
        ? i18n.t(message) 
        : message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 3000);
}

// Usage
showToast('js.users.created'); // Uses i18n
```

---

## 11. Best Practices

### 11.1 Internationalization

**MANDATORY:** All user-facing strings must use i18n:

```javascript
// GOOD - Always use i18n
const title = i18n.t('js.dashboard.welcome');
showToast(i18n.t('js.users.created'));
button.textContent = i18n.t('js.common.save');

// BAD - Never hardcode strings
const title = 'Welcome';
showToast('User created');
button.textContent = 'Save';
```

**HTML:**
```html
<!-- Always use data-i18n attributes -->
<h1 data-i18n="js.dashboard.welcome">Welcome</h1>
<input data-i18n-placeholder="js.login.usernamePlaceholder" />
<button data-i18n-label="js.common.save">Save</button>
```

### 11.2 Dark Mode

**MANDATORY:** All components must support dark mode:

```html
<!-- Use semantic color classes -->
<div class="bg-background text-foreground">
  <div class="bg-card border border-border">
    <p class="text-muted-foreground">Text</p>
  </div>
</div>

<!-- Use dark: prefix only when needed -->
<div class="bg-background dark:bg-card">
```

**Never hardcode colors:**
```html
<!-- BAD -->
<div class="bg-white text-black dark:bg-gray-800">

<!-- GOOD -->
<div class="bg-background text-foreground">
```

### 11.3 Error Handling

Always handle errors with i18n messages:

```javascript
try {
    const data = await DashboardApi.getDashboard();
    // Handle success
} catch (error) {
    // Use i18n for error messages
    showToast(i18n.t('js.error.dashboardLoadFailed'));
    console.error('Dashboard error:', error);
}
```

### 11.4 Date/Time Formatting

Always use i18n formatting:

```javascript
// GOOD - Uses locale from system config
const dateStr = i18n.formatDate(event.begin);
const timeStr = i18n.formatTime(event.begin);

// BAD - Hardcoded locale
const dateStr = new Date(event.begin).toLocaleDateString('de-DE');
```

---

### 11.5 Routing and Deeplinks

**Always use Routing utility for event navigation:**
```javascript
// GOOD - Use Routing utility
const event = Routing.getEventFromUrl();
if (event) {
    EventDetail.init(event.type, event.id);
}

// BAD - Manual URL parsing
const params = new URLSearchParams(window.location.search);
const rehearsalId = params.get('rehearsal');
```

**Store navigation state for login flow:**
```javascript
// Store before redirect
Routing.storePendingNavigation('R', 536);

// Retrieve after login
const pending = Routing.getPendingNavigation();
if (pending) {
    window.location.href = Routing.buildDashboardUrl(
        pending.eventType, 
        pending.eventId
    );
}
```

**Clean URLs when appropriate:**
```javascript
// Clean URL when navigating back to dashboard
Routing.cleanUrl();
```

---

## 12. Module Examples

### 12.1 Dashboard Module (`next/assets/js/dashboard.js`)

Example of a complete module with i18n and dark mode:

```javascript
const Dashboard = {
    async init(session) {
        this.session = session;
        
        // Initialize i18n translations
        await App.initI18n();
        
        // Load dashboard data
        await this.loadDashboard();
    },
    
    async loadDashboard() {
        try {
            const data = await DashboardApi.getDashboard();
            this.render(data);
        } catch (error) {
            // Use i18n for error message
            showToast(i18n.t('js.error.dashboardLoadFailed'));
        }
    },
    
    render(data) {
        // All text uses i18n
        const container = document.getElementById('dashboard-container');
        container.innerHTML = `
            <h1 data-i18n="js.dashboard.welcome">Welcome</h1>
            <p data-i18n="js.dashboard.subtitle">Welcome to %p</p>
            <!-- Content with semantic colors for dark mode -->
            <div class="bg-card border border-border rounded-lg p-4">
                <!-- Dashboard content -->
            </div>
        `;
        
        // Translate page after rendering
        if (typeof i18n !== 'undefined' && i18n.translatePage) {
            i18n.translatePage();
        }
    }
};
```

### 12.2 Sidebar Module (`next/assets/js/sidebar.js`)

Sidebar with i18n module names:

```javascript
const Sidebar = {
    async init(currentModule) {
        const modules = await AuthApi.getModules();
        this.renderModules(modules, currentModule);
    },
    
    renderModules(modules, currentModule) {
        const nav = document.getElementById('sidebar-nav');
        nav.innerHTML = modules.map(module => `
            <a href="${module.url}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg 
                      bg-sidebar-accent text-sidebar-foreground
                      hover:bg-sidebar-accent/80">
                <i data-lucide="${module.icon}"></i>
                <span data-i18n="${module.i18nKey}">${module.name}</span>
            </a>
        `).join('');
        
        // Translate and initialize icons
        i18n.translatePage();
        lucide.createIcons();
    }
};
```

---

**Document Status:** Updated  
**Last Updated:** 2026-01-27  
**See Also:** [README.md](../next/README.md) for overview, [Event Deeplink Plan](../.cursor/plans/event_deeplink_feature_b28b78cc.plan.md) for deeplink implementation details
