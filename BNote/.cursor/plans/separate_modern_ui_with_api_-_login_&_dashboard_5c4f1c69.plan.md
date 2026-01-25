---
name: Separate Modern UI with API - Login & Dashboard
overview: Create a completely separate modern JavaScript UI that uses only the API, starting with login and dashboard. The new UI will be isolated from the old PHP-based UI and accessed via feature flag routing in main.php.
todos:
  - id: api-infrastructure
    content: "Create API infrastructure: /api/index.php router, /api/response.php helper, /api/auth.php helpers"
    status: pending
  - id: auth-api
    content: Create /api/modules/auth.php with login, logout, and session actions
    status: pending
  - id: dashboard-api
    content: Create /api/modules/dashboard.php with dashboard, inbox, and news actions
    status: pending
  - id: js-api-client
    content: Create /app/assets/js/api.js with Api class and module helpers (AuthApi, DashboardApi)
    status: pending
  - id: js-auth
    content: Create /app/assets/js/auth.js with authentication flow helpers
    status: pending
  - id: js-app
    content: Create /app/assets/js/app.js for app initialization and session checking
    status: pending
  - id: login-page
    content: Create /app/login.html with modern Tailwind UI and API-based authentication
    status: pending
  - id: dashboard-page
    content: Create /app/dashboard.html with news, inbox, and stats display
    status: pending
  - id: entry-point
    content: Create /app/index.html as entry point that routes to login or dashboard
    status: pending
  - id: main-routing
    content: Add feature flag routing to main.php to redirect to /app/index.html when enabled
    status: pending
isProject: false
---

# Separate Modern UI with API - Login & Dashboard Implementation Plan

## Strategy Overview

Create a **completely separate** modern UI that:

- Lives in its own directory (`/app/`)
- Uses **only** the API (no direct PHP includes from old code)
- Has its own entry point routing
- Uses Tailwind CSS via CDN
- Focuses on Login and Dashboard in first iteration

## Architecture

```
/app/                    # New modern UI (completely separate)
├── index.html          # Entry point (login or dashboard)
├── login.html          # Login page
├── dashboard.html      # Dashboard page
├── assets/
│   ├── css/           # Custom styles (if needed)
│   └── js/
│       ├── api.js     # API client
│       ├── auth.js    # Authentication logic
│       └── app.js     # App initialization
└── components/        # Reusable UI components (optional)

/api/                   # Lightweight JSON API (separate from old code)
├── index.php          # API router
├── response.php       # JSON response helper
├── auth.php           # Auth helpers
└── modules/
    ├── auth.php       # Login/logout/session API
    └── dashboard.php  # Dashboard data API
```

## Phase 1: API Infrastructure (1 hour)

### 1.1 Create API Directory Structure

Create `/api/` directory with core files:

**File: `/api/index.php`**

- Router that handles all API requests
- Session management
- Routes to module handlers
- Returns JSON responses

**File: `/api/response.php`**

- `Response::success($data)` - Returns JSON success response
- `Response::error($message, $code)` - Returns JSON error response

**File: `/api/auth.php`**

- `Auth::check()` - Check if user authenticated
- `Auth::getUserId()` - Get current user ID
- `Auth::checkModule($name)` - Check module permissions

### 1.2 Key Implementation Details

- API uses existing PHP session system (no changes to session handling)
- All paths use `__DIR__` for absolute paths (no relative path issues)
- API router validates module names (security)
- Error handling returns proper HTTP status codes

## Phase 2: Auth API Module (30 minutes)

### 2.1 Create `/api/modules/auth.php`

**Actions:**

- `login` (POST) - Authenticate user
  - Input: `username`, `password`
  - Uses existing `LoginData` and `LoginController` logic
  - Returns: user info + permissions
- `logout` (POST) - Destroy session
- `session` (GET) - Check current session status

**Implementation:**

- Wraps existing `LoginController::doLogin()` logic
- Uses existing `LoginData` validation
- Preserves existing password encryption (`BNot3pW3ncryp71oN`)
- Returns user contact info and module permissions

## Phase 3: Dashboard API Module (30 minutes)

### 3.1 Create `/api/modules/dashboard.php`

**Actions:**

- `dashboard` (GET) - Get full dashboard data
  - Returns: news, inbox items, stats
- `inbox` (GET) - Get inbox items (optional filter by `otype`)
- `news` (GET) - Get news items

**Implementation:**

- Uses existing `StartData::getNews()`
- Uses existing `StartData::getInboxItems()`
- Formats data for JSON response
- Removes header rows from database selections

## Phase 4: JavaScript API Client (30 minutes)

### 4.1 Create `/app/assets/js/api.js`

**Features:**

- `Api` class with `request()`, `get()`, `post()` methods
- Automatic path calculation (works from any subdirectory)
- Error handling with detailed logging
- Session cookie handling (`credentials: 'same-origin'`)

**Module Helpers:**

- `AuthApi.login(username, password)`
- `AuthApi.logout()`
- `AuthApi.checkSession()`
- `DashboardApi.getDashboard()`
- `DashboardApi.getInbox(otype?)`
- `DashboardApi.getNews()`

## Phase 5: Modern Login Page (1 hour)

### 5.1 Create `/app/login.html`

**Features:**

- Standalone HTML page (no PHP includes)
- Tailwind CSS via CDN
- Modern, clean login form
- Client-side validation
- API-based authentication
- Redirects to dashboard on success
- Error handling with user-friendly messages

**Implementation:**

- Uses `AuthApi.login()` for authentication
- Stores session (handled by PHP cookies)
- Redirects to `/app/dashboard.html` on success
- Shows error messages for failed login

### 5.2 Create `/app/assets/js/auth.js`

**Features:**

- `Auth.login(username, password)` - Handle login flow
- `Auth.logout()` - Handle logout
- `Auth.checkSession()` - Check if user is logged in
- `Auth.redirectIfAuthenticated()` - Redirect to dashboard if already logged in
- `Auth.redirectIfNotAuthenticated()` - Redirect to login if not logged in

## Phase 6: Modern Dashboard Page (1.5 hours)

### 6.1 Create `/app/dashboard.html`

**Features:**

- Standalone HTML page
- Tailwind CSS styling
- Displays:
  - News section
  - Inbox items (rehearsals, concerts, tasks)
  - Statistics (upcoming rehearsals, concerts)
- Loading states
- Error handling
- Responsive design

**Implementation:**

- Uses `DashboardApi.getDashboard()` to fetch data
- Renders news items dynamically
- Renders inbox items with click handlers
- Displays stats cards
- Handles empty states

### 6.2 Create `/app/assets/js/app.js`

**Features:**

- App initialization
- Session checking on page load
- Redirects to login if not authenticated
- Global error handling
- Loading state management

## Phase 7: Entry Point & Routing (30 minutes)

### 7.1 Create `/app/index.html`

**Purpose:** Entry point that routes to login or dashboard based on session

**Implementation:**

- Checks session via `AuthApi.checkSession()`
- Redirects to `login.html` if not authenticated
- Redirects to `dashboard.html` if authenticated

### 7.2 Modify `main.php` for Feature Flag Routing

**Add to `main.php` (after line 18, before controller initialization):**

```php
// Check for modern UI feature flag
$useModernUI = $system_data->getDynamicConfigParameter("use_modern_ui");
if ($useModernUI == "1" || $useModernUI == 1) {
    // Redirect to new UI entry point
    header("Location: app/index.html");
    exit;
}
```

**Note:** This is the ONLY modification to existing code - a simple redirect.

## Phase 8: Styling & Polish (30 minutes)

### 8.1 Tailwind CSS Configuration

Add to each HTML page:

```html
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: {
            50: '#eff6ff',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8',
          },
        },
      },
    },
  }
</script>
```

### 8.2 Custom Styles (if needed)

Create `/app/assets/css/app.css` for any custom styles not covered by Tailwind.

## Implementation Checklist

### API Layer

- [ ] Create `/api/index.php` router
- [ ] Create `/api/response.php` helper
- [ ] Create `/api/auth.php` helpers
- [ ] Create `/api/modules/auth.php` (login, logout, session)
- [ ] Create `/api/modules/dashboard.php` (dashboard, inbox, news)

### JavaScript Foundation

- [ ] Create `/app/assets/js/api.js` (API client)
- [ ] Create `/app/assets/js/auth.js` (auth helpers)
- [ ] Create `/app/assets/js/app.js` (app initialization)

### Modern UI Pages

- [ ] Create `/app/index.html` (entry point/router)
- [ ] Create `/app/login.html` (login page)
- [ ] Create `/app/dashboard.html` (dashboard page)

### Integration

- [ ] Add feature flag check to `main.php`
- [ ] Test login flow end-to-end
- [ ] Test dashboard data loading
- [ ] Test session persistence
- [ ] Test error handling

## File Structure

```
BNote/
├── api/                          # NEW - API layer
│   ├── index.php
│   ├── response.php
│   ├── auth.php
│   └── modules/
│       ├── auth.php
│       └── dashboard.php
├── app/                          # NEW - Modern UI
│   ├── index.html
│   ├── login.html
│   ├── dashboard.html
│   └── assets/
│       ├── css/
│       │   └── app.css (optional)
│       └── js/
│           ├── api.js
│           ├── auth.js
│           └── app.js
├── main.php                      # MODIFY - Add feature flag routing
└── [existing BNote code unchanged]
```

## Key Design Decisions

1. **Complete Separation**: New UI in `/app/` directory, API in `/api/` directory - no mixing with old code
2. **No PHP in UI**: All HTML pages are static - all logic via JavaScript API calls
3. **Session-Based Auth**: Uses existing PHP sessions (no token system needed)
4. **Feature Flag**: Single point of control in `main.php` to switch between old/new UI
5. **Progressive Enhancement**: Can add more pages incrementally without affecting old UI

## Testing Strategy

1. **API Testing**: Test each endpoint with curl/browser
2. **Login Flow**: Test login → session → dashboard redirect
3. **Session Persistence**: Test that session persists across page loads
4. **Error Handling**: Test invalid credentials, network errors
5. **Dashboard Data**: Verify all data loads correctly

## Time Estimate

- Phase 1 (API Infrastructure): 1 hour
- Phase 2 (Auth API): 30 minutes
- Phase 3 (Dashboard API): 30 minutes
- Phase 4 (JS API Client): 30 minutes
- Phase 5 (Login Page): 1 hour
- Phase 6 (Dashboard Page): 1.5 hours
- Phase 7 (Routing): 30 minutes
- Phase 8 (Styling): 30 minutes

**Total: ~6 hours**

## Next Steps After This Plan

Once login and dashboard are working:

- Add more API modules (rehearsals, concerts, etc.)
- Add more UI pages
- Add navigation/sidebar
- Add user profile page
- Progressive migration of remaining modules