# BNote Next Generation REST API Architecture
**Version:** 2.0  
**Date:** 2026-01-27  
**Purpose:** REST API architecture for BNote Next Generation backend

---

## Table of Contents

1. [Overview](#overview)
2. [API Structure](#api-structure)
3. [Authentication & Authorization](#authentication--authorization)
4. [Request/Response Format](#requestresponse-format)
5. [Error Handling](#error-handling)
6. [API Infrastructure](#api-infrastructure)
7. [Endpoint Patterns](#endpoint-patterns)
8. [Security](#security)
9. [Performance](#performance)
10. [Versioning](#versioning)

---

## 1. Overview

### 1.1 Goals

- **RESTful Design:** Standard HTTP methods, resource-based URLs
- **JSON Only:** All requests/responses in JSON format
- **Backward Compatible:** Preserve existing PHP data/logic layers
- **Session Compatible:** Work with existing PHP sessions
- **Mobile Ready:** Support future KMP mobile app
- **Secure:** Maintain existing security standards

### 1.2 Base URL

The PHP API is served under the bnote-next-generation document root. The Next.js frontend proxies `/api/*` to this backend in development.

```
{bnote-next-generation}/api/index.php
```

**URL Pattern:**
```
?module={module}&action={action}&{params}
```

**Examples:**
- `GET …/api/index.php?module=rehearsals&id=42`
- `GET …/api/index.php?module=dashboard&action=dashboard`
- `POST …/api/index.php?module=users&action=create`
- `POST …/api/index.php?module=participation&action=save`

### 1.3 HTTP Methods

| Method | Usage | Idempotent |
|--------|-------|------------|
| GET | Retrieve resource(s) | Yes |
| POST | Create resource | No |
| PUT | Update resource (full) | Yes |
| PATCH | Update resource (partial) | No |
| DELETE | Delete resource | Yes |

**Note:** For simplicity, we'll use PUT for updates (full replacement).

---

## 2. API Structure

### 2.1 Directory Structure

```
bnote-next-generation/api/
├── index.php                  # API router
├── bootstrap.php              # Backend initialization
├── auth.php                   # Authentication helpers
├── response.php               # Response helper
├── logger.php                 # API logger
└── modules/
    ├── auth.php               # Authentication module
    ├── dashboard.php          # Dashboard module
    ├── users.php              # Users module
    ├── contacts.php           # Contacts module (dispatches to contacts/* handlers)
    ├── contacts/               # Contacts sub-handlers (CRUD, etc.)
    │   └── ContactsCRUD.php   # list, get, create, update, delete
    ├── rehearsals.php         # Rehearsals module
    ├── concerts.php           # Concerts module
    ├── participation.php      # Participation module
    └── translations.php       # Translations module
```

### 2.2 URL Patterns

**Module-Action Pattern:**
```
GET  …/api/index.php?module={module}&action={action}&{params}
POST …/api/index.php?module={module}&action={action}
```

**Examples:**
```
GET  …/api/index.php?module=dashboard&action=dashboard
GET  …/api/index.php?module=rehearsals&id=42
GET  …/api/index.php?module=users&action=list
POST …/api/index.php?module=users&action=create
POST …/api/index.php?module=participation&action=save
GET  …/api/index.php?module=translations&action=get&lang=de
```

**Module Handler Pattern:**
Each module implements a `{Module}Module` class with a `handle()` method that processes the action.

### 2.3 Module Naming

**Module Names (lowercase):**
- `auth` - Authentication
- `dashboard` - Dashboard data
- `users` - User management
- `contacts` - Contact management
- `rehearsals` - Rehearsal data
- `concerts` - Concert data
- `participation` - Participation status
- `translations` - Translation strings

**Action Names:**
- `list` - List resources
- `get` - Get single resource
- `create` - Create resource
- `update` - Update resource
- `delete` - Delete resource
- Module-specific actions (e.g., `dashboard`, `eventsNeedingResponse`)

---

## 3. Authentication & Authorization

### 3.1 Authentication Methods

**Method 1: Session-Based (Web UI)**
- Uses existing PHP sessions
- Cookie: `PHPSESSID`
- Same-origin requests only
- Backward compatible

**Method 2: Token-Based (Mobile/Future)**
- JWT or session token
- Header: `Authorization: Bearer {token}`
- Cross-origin support
- Stateless

**Implementation:**
```php
// Middleware checks both
if (isset($_SESSION['user'])) {
    // Session-based auth
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // Token-based auth
} else {
    // Unauthorized
}
```

### 3.2 Authentication Endpoints

**Login:**
```
POST …/api/index.php?module=auth&action=login
Body: {
    "username": "user@example.com",
    "password": "password123"
}
Response: {
    "success": true,
    "data": {
        "user": { ... }
    }
}
```

**Logout:**
```
POST …/api/index.php?module=auth&action=logout
Response: {
    "success": true,
    "data": true
}
```

**Session Check:**
```
GET …/api/index.php?module=auth&action=session
Response: {
    "success": true,
    "data": {
        "authenticated": true,
        "user": { ... }
    }
}
```

**Get User Language:**
```
GET …/api/index.php?module=auth&action=getUserLang
Response: {
    "success": true,
    "data": {
        "lang": "de",
        "country": "DE"
    }
}
```

### 3.3 Authorization (Permissions)

**Module Permissions:**
- Checked via `SystemData->userHasPermission($moduleId)`
- Middleware: `middleware/permissions.php`
- Returns 403 if user lacks permission

**Permission Check:**
```php
// In endpoint
$moduleId = $this->getModuleId('Rehearsals');
if (!$system_data->userHasPermission($moduleId)) {
    return Response::forbidden('Access denied to Rehearsals module');
}
```

**Resource-Level Permissions:**
- Some resources have owner-based access
- Example: Users can only edit their own profile
- Checked in endpoint logic

**File Permissions:**
- Handled by `SecurityManager`
- Checked in file-related endpoints

---

## 4. Request/Response Format

### 4.1 Request Format

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  # Optional
```

**Query Parameters:**
```
GET …/api/index.php?module=rehearsals&action=list&page=1&limit=50&sort=begin&order=asc&filter[status]=confirmed
```

**Common Query Parameters:**
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 50, max: 200)
- `sort` - Sort field
- `order` - Sort order (asc/desc)
- `filter[{field}]` - Filter by field value
- `search` - Full-text search
- `fields` - Comma-separated fields to return

**Body (POST/PUT):**
```json
{
    "begin": "2026-02-01 19:00:00",
    "end": "2026-02-01 21:00:00",
    "location": 5,
    "notes": "Regular rehearsal"
}
```

### 4.2 Response Format

**Success Response:**
```json
{
    "success": true,
    "data": {
        // Resource data
    }
}
```

**List Response:**
```json
{
    "success": true,
    "data": [
        { /* resource 1 */ },
        { /* resource 2 */ }
    ]
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Error message",
    "code": 400
}
```

**HTTP Status Codes:**
- 200 - Success
- 400 - Bad Request / Validation Error
- 401 - Unauthorized
- 403 - Forbidden
- 404 - Not Found
- 500 - Internal Server Error

### 4.3 HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST (resource created) |
| 204 | No Content | Successful DELETE (no body) |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Permission denied |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict (e.g., duplicate) |
| 422 | Unprocessable Entity | Validation error |
| 500 | Internal Server Error | Server error |

---

## 5. Error Handling

### 5.1 Error Response Structure

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message",
        "details": [
            {
                "field": "field_name",
                "message": "Field-specific error"
            }
        ],
        "trace": "..." // Only in development
    },
    "meta": {
        "timestamp": "2026-01-25T12:00:00Z",
        "request_id": "abc123"
    }
}
```

### 5.2 Error Codes

**Authentication Errors:**
- `AUTH_REQUIRED` - Authentication required
- `AUTH_INVALID` - Invalid credentials
- `AUTH_EXPIRED` - Session/token expired
- `AUTH_FORBIDDEN` - Permission denied

**Validation Errors:**
- `VALIDATION_ERROR` - General validation error
- `VALIDATION_REQUIRED` - Required field missing
- `VALIDATION_INVALID` - Invalid field value
- `VALIDATION_UNIQUE` - Duplicate value

**Resource Errors:**
- `RESOURCE_NOT_FOUND` - Resource doesn't exist
- `RESOURCE_CONFLICT` - Resource conflict
- `RESOURCE_LOCKED` - Resource locked

**System Errors:**
- `INTERNAL_ERROR` - Server error
- `DATABASE_ERROR` - Database error
- `FILE_ERROR` - File operation error

### 5.3 Error Handler Implementation

```php
class ErrorHandler {
    public static function handle($exception, $requestId = null) {
        $code = $exception->getCode() ?: 'INTERNAL_ERROR';
        $message = $exception->getMessage();
        
        // Log error
        Logger::error($code, $message, $exception);
        
        // Return JSON response
        http_response_code(self::getHttpCode($code));
        return Response::error($code, $message, $requestId);
    }
    
    private static function getHttpCode($errorCode) {
        $map = [
            'AUTH_REQUIRED' => 401,
            'AUTH_FORBIDDEN' => 403,
            'RESOURCE_NOT_FOUND' => 404,
            'VALIDATION_ERROR' => 422,
            // ...
        ];
        return $map[$errorCode] ?? 500;
    }
}
```

---

## 6. API Infrastructure

### 6.1 Router (`api/index.php`)

**Responsibilities:**
- Parse module and action from query parameters
- Load module handler file
- Check authentication (except auth/translations modules)
- Handle errors globally
- Log requests

**Implementation Pattern:**
```php
<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Change to bnote root (paths.php defines BNOTE_ROOT)
chdir(__DIR__ . '/..');

// Load BNote core
require_once 'dirs.php';
require_once 'src/logic/init.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');

// Get module and action
$module = $_GET['module'] ?? $_POST['module'] ?? 'dashboard';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Validate module name
if (!preg_match('/^[a-z]+$/', $module)) {
    Response::error('Invalid module name', 400);
}

// Load module file
$moduleFile = __DIR__ . '/modules/' . $module . '.php';
if (!file_exists($moduleFile)) {
    Response::error('Module not found: ' . $module, 404);
}

require_once $moduleFile;

// Check authentication (except auth and translations)
if ($module !== 'auth' && $module !== 'translations' && !Auth::check()) {
    Response::error('Authentication required', 403);
}

// Instantiate module handler
$className = ucfirst($module) . 'Module';
$handler = new $className();
$result = $handler->handle();
Response::success($result);
```

### 6.2 Response Helper (`api/response.php`)

```php
class Response {
    public static function success($data) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function error($message, $code = 400) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
```

### 6.3 Authentication Helper (`api/auth.php`)

```php
class Auth {
    public static function check() {
        global $system_data;
        return $system_data->isUserAuthenticated();
    }
    
    public static function getUserId() {
        global $system_data;
        return $system_data->getUserId();
    }
    
    public static function checkModule($moduleName) {
        global $system_data;
        $moduleId = $system_data->getModuleId($moduleName);
        if (!$moduleId) {
            return false;
        }
        return $system_data->userHasPermission($moduleId);
    }
}
```

### 6.4 Module Handler Pattern

Each module implements a handler class:

```php
class DashboardModule {
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                return $this->getDashboard();
            case 'eventsNeedingResponse':
                return $this->getEventsNeedingResponse();
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    private function getDashboard() {
        // Use existing BNote data classes
        $data = new StartData();
        return $data->getDashboardData();
    }
}
```

### 6.5 Logger (`api/logger.php`)

```php
class ApiLogger {
    public static function logRequest($module, $action, $method, $params, $body) {
        // Log to /log/api/api_YYYY-MM-DD.log
    }
    
    public static function logResponse($module, $action, $statusCode, $response, $responseTime) {
        // Log response
    }
    
    public static function logError($module, $action, $error, $code) {
        // Log errors
    }
}
```

---

## 7. Endpoint Examples

### 7.1 Dashboard Module

**Get Dashboard:**
```
GET …/api/index.php?module=dashboard&action=dashboard
Response: {
    "success": true,
    "data": {
        "company": "Band Name",
        "events": [ ... ],
        "news": [ ... ]
    }
}
```

**Get Events Needing Response:**
```
GET …/api/index.php?module=dashboard&action=eventsNeedingResponse
Response: {
    "success": true,
    "data": [
        { "otype": "R", "oid": 42, "begin": "...", ... }
    ]
}
```

### 7.2 Users Module

**List Users:**
```
GET …/api/index.php?module=users&action=list
Response: {
    "success": true,
    "data": [
        { "id": 1, "name": "...", "surname": "...", ... }
    ]
}
```

**Get User:**
```
GET …/api/index.php?module=users&action=get&id=5
Response: {
    "success": true,
    "data": {
        "id": 5,
        "name": "...",
        "privileges": [ ... ]
    }
}
```

**Create User:**
```
POST …/api/index.php?module=users&action=create
Body: {
    "name": "John",
    "surname": "Doe",
    "email": "john@example.com"
}
Response: {
    "success": true,
    "data": {
        "id": 123,
        "name": "John",
        ...
    }
}
```

### 7.3 Participation Module

**Get Participation Status:**
```
GET …/api/index.php?module=participation&action=get&event_id=42&event_type=R
Response: {
    "success": true,
    "data": {
        "status": 1,
        "reason": ""
    }
}
```

**Save Participation:**
```
POST …/api/index.php?module=participation&action=save
Body: {
    "event_id": 42,
    "event_type": "R",
    "status": 1,
    "reason": "Will attend"
}
Response: {
    "success": true,
    "data": true
}
```

### 7.4 Translations Module

**Get Translations:**
```
GET …/api/index.php?module=translations&action=get&lang=de
Response: {
    "success": true,
    "data": {
        "js.dashboard.welcome": "Willkommen",
        "js.common.save": "Speichern",
        ...
    }
}
```

---

## 8. Security

### 8.1 Input Validation

**All inputs must be validated:**
- Type checking
- Length limits
- Format validation (dates, emails, etc.)
- SQL injection prevention (prepared statements)
- XSS prevention (output escaping)

**Validation Helper:**
```php
class Validator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[] = ["field" => $field, "message" => "$field is required"];
            }
            
            // Type
            if (isset($rule['type'])) {
                if (!self::validateType($value, $rule['type'])) {
                    $errors[] = ["field" => $field, "message" => "$field must be {$rule['type']}"];
                }
            }
            
            // Format
            if (isset($rule['format']) && !preg_match($rule['format'], $value)) {
                $errors[] = ["field" => $field, "message" => "$field format is invalid"];
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
```

### 8.2 Rate Limiting

**Implementation:**
```php
class RateLimiter {
    private static $limits = [
        'default' => ['requests' => 100, 'window' => 3600], // 100/hour
        'auth' => ['requests' => 5, 'window' => 300],        // 5/5min
    ];
    
    public static function check($endpoint) {
        $limit = self::$limits[$endpoint] ?? self::$limits['default'];
        $key = self::getKey($endpoint);
        
        // Check Redis/file-based counter
        $count = self::getCount($key, $limit['window']);
        
        if ($count >= $limit['requests']) {
            Response::error('RATE_LIMIT_EXCEEDED', 'Rate limit exceeded', [], 429);
            exit;
        }
        
        self::increment($key, $limit['window']);
    }
}
```

### 8.3 CSRF Protection

**For web UI (same-origin):**
- Rely on SameSite cookies
- Optional: CSRF token in forms

**For API (cross-origin):**
- Token-based auth (no CSRF risk)
- CORS restrictions

---

## 9. Performance

### 9.1 Caching Strategy

**Cacheable Endpoints (Future):**
- `GET …/api/index.php?module=instruments&action=list` - Static data
- `GET …/api/index.php?module=groups&action=list` - Changes infrequently
- `GET …/api/index.php?module=locations&action=list` - Changes infrequently

**Cache Headers:**
```
Cache-Control: public, max-age=3600
ETag: "abc123"
Last-Modified: Wed, 25 Jan 2026 12:00:00 GMT
```

**Implementation:**
```php
class Cache {
    public static function get($key) {
        // Redis or file-based cache
    }
    
    public static function set($key, $value, $ttl = 3600) {
        // Store with TTL
    }
    
    public static function headers($etag, $lastModified) {
        header("ETag: $etag");
        header("Last-Modified: $lastModified");
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
            $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
            http_response_code(304);
            exit;
        }
    }
}
```

### 9.2 Pagination

**All list endpoints must support pagination:**
- Default: 50 items per page
- Max: 200 items per page
- Query params: `?page=1&limit=50`

**Response:**
```json
{
    "data": [ ... ],
    "meta": {
        "pagination": {
            "page": 1,
            "limit": 50,
            "total": 150,
            "pages": 3,
            "has_next": true,
            "has_prev": false
        }
    }
}
```

### 9.3 Field Selection

**Field Selection (Future):**
```
GET …/api/index.php?module=rehearsals&action=list&fields=id,begin,end,location
```

**Response:**
```json
{
    "data": [
        { "id": 1, "begin": "...", "end": "...", "location": 5 }
    ]
}
```

---

## 8. Module Implementation

### 8.1 Module Handler Pattern

All modules follow the same pattern:

```php
class {Module}Module {
    public function __construct() {
        // Check permissions if needed
        // Initialize data classes
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'default';
        
        switch ($action) {
            case 'list':
                return $this->list();
            case 'get':
                return $this->get();
            case 'create':
                return $this->create();
            // ... other actions
            default:
                Response::error('Unknown action: ' . $action, 400);
        }
    }
    
    private function list() {
        // Use existing BNote data classes
        $data = new {Module}Data();
        return $data->findAll();
    }
}
```

### 8.2 Implemented Modules

- `auth` - Authentication and session management
- `dashboard` - Dashboard data and events
- `users` - User management (CRUD, privileges, GDPR)
- `contacts` - Contact management (CRUD, groups, integration)
- `rehearsals` - Rehearsal detail with participants
- `concerts` - Concert detail with participants and metadata
- `participation` - Participation status management
- `translations` - Translation strings for frontend

---

## 9. Error Handling

### 9.1 Error Response Format

All errors follow consistent format:

```json
{
    "success": false,
    "error": "Human-readable error message",
    "code": 400
}
```

### 9.2 Common Error Codes

- 400 - Bad Request (validation errors, invalid parameters)
- 401 - Unauthorized (not authenticated)
- 403 - Forbidden (no permission for module)
- 404 - Not Found (module or resource not found)
- 500 - Internal Server Error (server errors)

### 9.3 Error Handling in Modules

```php
try {
    // Use existing BNote data/logic classes
    $data = new UsersData();
    $result = $data->findById($id);
    
    if (!$result) {
        Response::error('User not found', 404);
    }
    
    return $result;
} catch (BNoteError $e) {
    Response::error($e->getMessage(), 400);
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
```

---

## 10. Backend Integration

### 10.1 Using Existing BNote Classes

The API wraps existing BNote data and logic layers without modification:

```php
// Load existing data class
require_once __DIR__ . '/../../../src/data/modules/userdata.php';
$data = new UserData();

// Use existing methods
$users = $data->findAll();
$user = $data->findById($id);
$data->add($values);
```

### 10.2 Permission Checks

Use existing permission system:

```php
global $system_data;
$moduleId = $system_data->getModuleId('Users');
if (!$system_data->userHasPermission($moduleId)) {
    Response::error('Access denied', 403);
}
```

---

**Document Status:** Updated  
**Last Updated:** 2026-01-27  
**See Also:** [README.md](../README.md) for overview; [FEATURES_AND_BEHAVIORS.md](FEATURES_AND_BEHAVIORS.md) for UI behavior.
