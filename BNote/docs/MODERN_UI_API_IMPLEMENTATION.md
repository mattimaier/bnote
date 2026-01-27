# Modern UI with Lightweight JSON API - Implementation Guide

## Overview

This document describes the architecture and implementation approach for the modern JavaScript-based UI that uses a lightweight JSON API layer. This approach was chosen to:

- **Separate concerns**: New UI is completely isolated from old PHP-based UI
- **Preserve existing logic**: API wraps existing PHP data/logic classes without rewriting
- **Fast implementation**: Simple action-based endpoints (not full REST)
- **Easy maintenance**: Direct PHP method calls, minimal abstraction layers

## Architecture

### Directory Structure

```
BNote/
├── next/                        # New app (frontend + API)
│   ├── index.html              # Entry point (routes to login/dashboard)
│   ├── login.html              # Login page
│   ├── dashboard.html          # Dashboard page
│   ├── debug.html              # API debugging tool
│   ├── api/                    # Lightweight JSON API layer
│   │   ├── index.php          # API router (entry point)
│   │   ├── bootstrap.php      # Dependency loader (base classes)
│   │   ├── response.php       # JSON response helper
│   │   ├── auth.php           # Authentication helpers
│   │   ├── logger.php         # API logging system
│   │   ├── modules/           # Module-specific API handlers
│   │   │   ├── auth.php      # Login/logout/session
│   │   │   └── dashboard.php # Dashboard data
│   │   └── ENABLE_API_LOGGING.sql
│   └── assets/
│       ├── css/
│       │   └── app.css        # Shared theme + component styles
│       └── js/
│           ├── api.js         # API client
│           ├── auth.js        # Authentication helpers
│           ├── app.js         # App initialization
│           └── tailwind-config.js
│
└── [existing BNote code unchanged]
```

### Key Principles

1. **Complete Separation**: New app (UI + API) in `/next/` directory
2. **No PHP in UI**: All HTML pages are static - all logic via JavaScript API calls
3. **Session-Based Auth**: Uses existing PHP sessions (no token system needed)
4. **Direct Access**: New UI accessible directly at `/next/index.html` (no feature flag routing)
5. **Progressive Enhancement**: Can add more pages incrementally without affecting old UI

## API Layer

### API Router (`/next/api/index.php`)

The API router handles all requests and routes them to module-specific handlers.

**URL Pattern:**
```
GET/POST /next/api/index.php?module={module}&action={action}&id={id}
```

**Example:**
```
GET /next/api/index.php?module=dashboard&action=dashboard
POST /next/api/index.php?module=auth&action=login
```

**Request Flow:**
1. Start session (if not already started)
2. Change working directory to project root (for relative paths)
3. Load core BNote files (`dirs.php`, `init.php`)
4. Load API bootstrap (base classes)
5. Load API helpers (`response.php`, `auth.php`, `logger.php`)
6. Validate module name (security)
7. Load module file from `/next/api/modules/{module}.php`
8. Check authentication (except for `auth` module)
9. Instantiate module handler class (`{Module}Module`)
10. Call `handle()` method
11. Return JSON response

**Response Format:**
```json
// Success
{
  "success": true,
  "data": { ... }
}

// Error
{
  "success": false,
  "error": "Error message",
  "code": 400
}
```

### Bootstrap System (`/next/api/bootstrap.php`)

**Purpose**: Loads all required base classes before module-specific classes are loaded.

**Why it's needed**: PHP classes have dependencies. For example:
- `StartData` extends `AbstractLocationData`
- `AbstractLocationData` extends `AbstractData`
- `AbstractData` uses `FieldType`

**Loading Order:**
1. `FieldType` (used by all data classes)
2. `AbstractData` (base class, loads `ApplicationDataProvider` conditionally)
3. `AbstractLocationData` (extends `AbstractData`)

**Note**: `Database` and `Regex` are loaded by `Systemdata` (in `init.php`).

**When to update**: If you encounter "Class X not found" errors, check:
1. What class is missing?
2. What does it extend/use?
3. Add the required class to `next/api/bootstrap.php` in dependency order

### Module Handler Pattern

Each module API file follows this pattern:

```php
<?php
/**
 * {Module} API module
 * 
 * Note: This file is loaded after next/api/index.php has changed working directory to project root
 * So relative paths in {module}data.php will work correctly
 */
// dirs.php, init.php, and bootstrap.php are already loaded by next/api/index.php
// All base classes (FieldType, AbstractData, AbstractLocationData) are loaded
// Load module-specific dependencies (next/api/modules -> project root is ../../..)
require_once __DIR__ . '/../../../src/data/modules/{module}data.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

class {Module}Module {
    private $data;
    
    public function __construct() {
        // Check module permission
        global $system_data;
        $moduleId = $system_data->getModuleId('{Module}');
        if ($moduleId && !$system_data->userHasPermission($moduleId)) {
            Response::error('Access denied', 403);
        }
        
        // Initialize existing data class (don't rewrite!)
        $this->data = new {Module}Data();
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                return $this->list();
            case 'get':
                return $this->get($_GET['id'] ?? $_POST['id']);
            case 'create':
                return $this->create();
            case 'update':
                return $this->update($_GET['id'] ?? $_POST['id']);
            case 'delete':
                return $this->delete($_GET['id'] ?? $_POST['id']);
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    private function list() {
        // Use existing method - no rewriting!
        $items = $this->data->findAllNoRef();
        // Remove header row (index 0) if present
        unset($items[0]);
        return array_values($items);
    }
    
    private function get($id) {
        return $this->data->get{Module}($id);
    }
    
    private function create() {
        // Validate using existing validation
        $this->data->validate($_POST);
        
        // Create using existing method
        $id = $this->data->create($_POST);
        
        // Return created record
        return $this->data->get{Module}($id);
    }
    
    private function update($id) {
        $this->data->validate($_POST);
        $this->data->update($id, $_POST);
        return $this->data->get{Module}($id);
    }
    
    private function delete($id) {
        $this->data->delete($id);
        return ['deleted' => true];
    }
}
```

### Adding a New API Module

1. **Create `/next/api/modules/{module}.php`** following the pattern above
2. **Check dependencies**: What classes does `{Module}Data` need?
   - If it extends `AbstractLocationData`, bootstrap already loaded it
   - If it needs other classes, add `require_once` statements
3. **Use existing methods**: Don't rewrite logic, wrap existing `{Module}Data` methods
4. **Handle JSON POST data**: If module accepts POST, parse from `php://input`:
   ```php
   $rawInput = file_get_contents('php://input');
   if (!empty($rawInput)) {
       $jsonInput = json_decode($rawInput, true);
       if ($jsonInput) {
           $_POST = array_merge($_POST, $jsonInput);
       }
   }
   ```
5. **Test with curl**:
   ```bash
   curl -X GET "http://localhost:8888/bnote/BNote/next/api/index.php?module={module}&action=list" \
     -H "Cookie: PHPSESSID=..."
   ```

### API Helpers

#### Response Helper (`/next/api/response.php`)

```php
Response::success($data);        // Returns 200 with JSON
Response::error($message, $code); // Returns error with JSON
```

#### Auth Helper (`/next/api/auth.php`)

```php
Auth::check();                    // Check if user authenticated
Auth::getUserId();                // Get current user ID
Auth::checkModule($moduleName);   // Check module permissions
```

#### Logger (`/next/api/logger.php`)

Logs all API requests and responses when enabled via database setting `api_detailed_logging`.

**Enable logging:**
```sql
-- Run next/api/ENABLE_API_LOGGING.sql
INSERT INTO configuration (param, value, is_active) 
VALUES ('api_detailed_logging', '1', 1)
ON DUPLICATE KEY UPDATE value = '1', is_active = 1;
```

**Log location:** `/log/api/api_YYYY-MM-DD.log` (project root; unchanged)

**Log format:** JSON lines, one per request/response

## JavaScript UI Layer

### API Client (`/next/assets/js/api.js`)

**Path Calculation Logic:**

The API client automatically calculates the correct API path based on the current page location. The API lives at `/next/api/index.php`.

**How it works:**
1. Get current pathname (e.g., `/bnote/BNote/next/login.html`)
2. Find `/next/` or `/next` in the pathname
3. Take the path **up to and including** `next`: `/bnote/BNote/next` or `/bnote/BNote/next/`
4. Append `api/index.php`: `/bnote/BNote/next/api/index.php`

**Important**: This logic handles both `/next/` (with trailing slash) and `/next` (without trailing slash) patterns.

**Usage:**
```javascript
// Module-specific helpers are auto-created
const api = new Api();

// Use module helpers
AuthApi.login(username, password);
DashboardApi.getDashboard();
```

### Adding a New API Module Helper

In `/next/assets/js/api.js`, add:

```javascript
const {Module}Api = {
    list: () => api.get('{module}', 'list'),
    get: (id) => api.get('{module}', 'get', { id }),
    create: (data) => api.post('{module}', 'create', data),
    update: (id, data) => api.post('{module}', 'update', { ...data, id }),
    delete: (id) => api.post('{module}', 'delete', { id })
};
```

### Authentication Flow (`/next/assets/js/auth.js`)

```javascript
// Check if user is logged in
const session = await Auth.checkSession();
if (session.authenticated) {
    // User is logged in
}

// Login
await Auth.login(username, password);

// Logout
await Auth.logout();

// Redirect helpers
Auth.redirectIfAuthenticated();    // Use on login page
Auth.redirectIfNotAuthenticated();  // Use on protected pages
```

### Adding a New UI Page

1. **Create `/next/{page}.html`**:
   ```html
   <!DOCTYPE html>
   <html lang="de">
   <head>
       <meta charset="UTF-8">
       <title>{Page} - BNote</title>
       <script src="https://cdn.tailwindcss.com"></script>
   </head>
   <body>
       <!-- Your UI here -->
       
       <script src="assets/js/api.js"></script>
       <script src="assets/js/auth.js"></script>
       <script src="assets/js/app.js"></script>
       <script>
           // Check authentication
           Auth.redirectIfNotAuthenticated().then(() => {
               // Load page data
               load{Page}();
           });
           
           async function load{Page}() {
               try {
                   const data = await {Module}Api.list();
                   // Render data
               } catch (error) {
                   console.error('Error:', error);
               }
           }
       </script>
   </body>
   </html>
   ```

2. **Update `/next/index.html`** to include routing to new page if needed

## Common Issues and Solutions

### Issue: "Class X not found"

**Solution**: Add the missing class to `/next/api/bootstrap.php` in dependency order.

**Check:**
1. What class is missing?
2. What does it extend/use?
3. Load parent classes first

### Issue: "API Base URL wrong"

**Solution**: The path calculation in `api.js` uses the path up to and including `/next`, then appends `api/index.php`. If requests fail:
1. Verify the pathname format (must contain `/next/` or `/next`)
2. Check network tab for the requested URL
3. Update the path calculation logic in `api.js` if needed

### Issue: "Session already active" warnings

**Solution**: Already handled in `next/api/index.php` with:
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### Issue: "Undefined array key 'mod'"

**Solution**: Already handled in `init.php` - API calls don't provide `mod` parameter, so it's checked conditionally.

### Issue: "Invalid JSON response from server"

**Solution**: This usually means PHP errors are being returned as HTML. Check:
1. PHP error logs
2. API logs (`/log/api/api_YYYY-MM-DD.log` at project root)
3. Use debug tool (`/next/debug.html`) to see raw response

### Issue: Path resolution errors in module files

**Solution**: The API router changes working directory to project root:
```php
chdir($projectRoot);
```
This ensures relative paths in data classes work correctly.

## Debugging Tools

### API Debug Tool (`/next/debug.html`)

Access at: `http://localhost:8888/bnote/BNote/next/debug.html`

**Features:**
- Make API calls with form interface
- View pretty-printed JSON responses
- Copy cURL commands
- View request history
- See request/response details

**Usage:**
1. Enter module name (e.g., `auth`, `dashboard`)
2. Enter action (e.g., `login`, `dashboard`)
3. Select method (GET or POST)
4. Add request body for POST requests
5. Click "Send Request"
6. View response and copy cURL command if needed

### API Logging

When enabled, logs all API requests/responses to `/log/api/api_YYYY-MM-DD.log`.

**Log entries include:**
- Timestamp
- Module and action
- Request method, params, body
- Response status, data, response time
- Session ID, user ID, IP address

**Enable:**
```sql
-- Run next/api/ENABLE_API_LOGGING.sql
UPDATE configuration SET value = '1' WHERE param = 'api_detailed_logging';
```

## Testing

### Manual Testing with curl

```bash
# Login
curl -X POST "http://localhost:8888/bnote/BNote/next/api/index.php?module=auth&action=login" \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}' \
  -c cookies.txt

# Get dashboard (use cookie from login)
curl -X GET "http://localhost:8888/bnote/BNote/next/api/index.php?module=dashboard&action=dashboard" \
  -b cookies.txt
```

### Browser Testing

1. Open `/next/debug.html` for API testing
2. Open `/next/login.html` for UI testing
3. Check browser console for errors
4. Check network tab for API requests

## Best Practices

1. **Don't rewrite existing logic**: Wrap existing PHP methods, don't duplicate
2. **Use existing validation**: Call `$data->validate()` before create/update
3. **Preserve sessions**: Use existing PHP session system
4. **Handle errors gracefully**: Return proper HTTP status codes
5. **Log everything**: Enable API logging during development
6. **Test with debug tool**: Use `/next/debug.html` before implementing UI
7. **Check dependencies**: Always verify class dependencies when adding modules
8. **Path calculation**: The API client handles path calculation automatically - don't hardcode paths

## Migration Strategy

1. **Start small**: Implement login and dashboard first
2. **Add modules incrementally**: One module at a time
3. **Test thoroughly**: Use debug tool and browser testing
4. **Keep old UI**: Don't remove old PHP UI until new UI is complete
5. **Progressive enhancement**: Add features gradually

## Future Enhancements

- Add more API modules (rehearsals, concerts, contacts, etc.)
- Add navigation/sidebar to UI
- Add user profile page
- Add more UI pages for each module
- Add client-side routing (optional)
- Add state management (optional, for complex UIs)

## References

- Plan: `.cursor/plans/separate_modern_ui_with_api_-_login_&_dashboard_5c4f1c69.plan.md`
- API Router: `/next/api/index.php`
- Bootstrap: `/next/api/bootstrap.php`
- API Client: `/next/assets/js/api.js`
- Debug Tool: `/next/debug.html`
