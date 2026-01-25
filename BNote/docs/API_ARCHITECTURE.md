# BNote REST API Architecture
**Version:** 1.0  
**Date:** 2026-01-25  
**Purpose:** Complete REST API design specification for BNote backend

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

```
/api/v1/
```

**Examples:**
- `GET /api/v1/rehearsals`
- `POST /api/v1/rehearsals`
- `GET /api/v1/rehearsals/42`
- `PUT /api/v1/rehearsals/42`
- `DELETE /api/v1/rehearsals/42`

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
/api/
├── v1/
│   ├── index.php              # API router
│   ├── middleware/
│   │   ├── auth.php           # Authentication middleware
│   │   ├── permissions.php    # Permission checker
│   │   └── cors.php           # CORS handler
│   ├── lib/
│   │   ├── Response.php       # Response helper
│   │   ├── ErrorHandler.php   # Error handler
│   │   └── Logger.php         # API logger
│   ├── modules/
│   │   ├── auth.php           # Authentication endpoints
│   │   ├── rehearsals.php     # Rehearsals endpoints
│   │   ├── concerts.php       # Concerts endpoints
│   │   ├── contacts.php       # Contacts endpoints
│   │   ├── calendar.php       # Calendar endpoints
│   │   ├── tasks.php           # Tasks endpoints
│   │   ├── messages.php        # Messages endpoints
│   │   ├── programs.php        # Programs endpoints
│   │   ├── members.php         # Members endpoints
│   │   ├── finance.php         # Finance endpoints
│   │   ├── equipment.php       # Equipment endpoints
│   │   ├── repertoire.php      # Repertoire endpoints
│   │   ├── locations.php       # Locations endpoints
│   │   ├── groups.php          # Groups endpoints
│   │   ├── instruments.php     # Instruments endpoints
│   │   ├── votes.php           # Votes/Polls endpoints
│   │   ├── appointments.php    # Appointments endpoints
│   │   ├── tours.php           # Tours endpoints
│   │   ├── travel.php          # Travel endpoints
│   │   ├── accommodation.php   # Accommodations endpoints
│   │   ├── outfits.php         # Outfits endpoints
│   │   ├── share.php           # File sharing endpoints
│   │   ├── stats.php           # Statistics endpoints
│   │   ├── admin.php           # Admin endpoints
│   │   ├── config.php          # Configuration endpoints
│   │   └── dashboard.php       # Dashboard endpoints
│   └── .htaccess              # URL rewriting
└── .htaccess                  # API routing
```

### 2.2 URL Patterns

**Resource-Based URLs:**
```
GET    /api/v1/{resource}           # List resources
POST   /api/v1/{resource}           # Create resource
GET    /api/v1/{resource}/{id}      # Get resource
PUT    /api/v1/{resource}/{id}      # Update resource
DELETE /api/v1/{resource}/{id}      # Delete resource
```

**Sub-Resources:**
```
GET    /api/v1/{resource}/{id}/{sub-resource}  # Get sub-resources
POST   /api/v1/{resource}/{id}/{sub-resource}  # Create sub-resource
```

**Actions (when CRUD doesn't fit):**
```
POST   /api/v1/{resource}/{id}/{action}       # Custom action
```

**Examples:**
```
GET    /api/v1/rehearsals
GET    /api/v1/rehearsals/42
GET    /api/v1/rehearsals/42/participants
POST   /api/v1/rehearsals/42/participants
POST   /api/v1/rehearsals/42/participate
GET    /api/v1/contacts/5/instruments
GET    /api/v1/calendar/events?from=2026-01-01&to=2026-01-31
```

### 2.3 Resource Naming

**Plural Nouns:**
- `rehearsals` (not `rehearsal`)
- `concerts` (not `concert`)
- `contacts` (not `contact`)
- `tasks` (not `task`)

**Exceptions:**
- `auth` (authentication)
- `dashboard` (aggregate data)
- `stats` (statistics)

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
POST /api/v1/auth/login
Body: {
    "username": "user@example.com",
    "password": "password123"
}
Response: {
    "success": true,
    "data": {
        "user": { ... },
        "token": "..." // Optional, for mobile
    }
}
```

**Logout:**
```
POST /api/v1/auth/logout
Response: {
    "success": true,
    "message": "Logged out successfully"
}
```

**Session Check:**
```
GET /api/v1/auth/session
Response: {
    "success": true,
    "data": {
        "authenticated": true,
        "user": { ... }
    }
}
```

**Token Refresh (Future):**
```
POST /api/v1/auth/refresh
Body: {
    "token": "..."
}
Response: {
    "success": true,
    "data": {
        "token": "..."
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
GET /api/v1/rehearsals?page=1&limit=50&sort=begin&order=asc&filter[status]=confirmed
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
    },
    "meta": {
        "timestamp": "2026-01-25T12:00:00Z",
        "version": "1.0"
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
    ],
    "meta": {
        "pagination": {
            "page": 1,
            "limit": 50,
            "total": 150,
            "pages": 3
        },
        "timestamp": "2026-01-25T12:00:00Z"
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data",
        "details": [
            {
                "field": "begin",
                "message": "Begin date is required"
            }
        ]
    },
    "meta": {
        "timestamp": "2026-01-25T12:00:00Z"
    }
}
```

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

### 6.1 Router (`/api/v1/index.php`)

**Responsibilities:**
- Parse URL and route to module endpoint
- Apply middleware (auth, permissions, CORS)
- Handle errors globally
- Log requests

**Implementation:**
```php
<?php
require_once __DIR__ . '/../../dirs.php';
require_once __DIR__ . '/../../src/logic/init.php';
require_once __DIR__ . '/lib/Response.php';
require_once __DIR__ . '/lib/ErrorHandler.php';
require_once __DIR__ . '/lib/Logger.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/permissions.php';
require_once __DIR__ . '/middleware/cors.php';

// Set JSON headers
header('Content-Type: application/json');

// CORS middleware
CorsMiddleware::handle();

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/v1', '', $path);
$segments = array_filter(explode('/', $path));

// Route to module
$resource = $segments[0] ?? 'dashboard';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

// Load module endpoint
$moduleFile = __DIR__ . '/modules/' . $resource . '.php';
if (!file_exists($moduleFile)) {
    Response::notFound("Resource '$resource' not found");
    exit;
}

require_once $moduleFile;

// Auth middleware
if (!AuthMiddleware::check()) {
    Response::unauthorized('Authentication required');
    exit;
}

// Execute endpoint
try {
    $module = new $resource();
    $result = $module->handle($method, $id, $action);
    Response::success($result);
} catch (Exception $e) {
    ErrorHandler::handle($e);
}
```

### 6.2 Response Helper (`/api/v1/lib/Response.php`)

```php
class Response {
    public static function success($data, $meta = []) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => date('c'),
                'version' => '1.0'
            ], $meta)
        ]);
        exit;
    }
    
    public static function created($data) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'meta' => ['timestamp' => date('c')]
        ]);
        exit;
    }
    
    public static function error($code, $message, $details = []) {
        http_response_code(self::getHttpCode($code));
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'meta' => ['timestamp' => date('c')]
        ]);
        exit;
    }
    
    public static function unauthorized($message = 'Authentication required') {
        self::error('AUTH_REQUIRED', $message);
    }
    
    public static function forbidden($message = 'Permission denied') {
        self::error('AUTH_FORBIDDEN', $message);
    }
    
    public static function notFound($message = 'Resource not found') {
        self::error('RESOURCE_NOT_FOUND', $message);
    }
    
    private static function getHttpCode($code) {
        $map = [
            'AUTH_REQUIRED' => 401,
            'AUTH_FORBIDDEN' => 403,
            'RESOURCE_NOT_FOUND' => 404,
            'VALIDATION_ERROR' => 422,
        ];
        return $map[$code] ?? 500;
    }
}
```

### 6.3 Authentication Middleware (`/api/v1/middleware/auth.php`)

```php
class AuthMiddleware {
    public static function check() {
        global $system_data;
        
        // Check session
        if (isset($_SESSION['user']) && $_SESSION['user'] > 0) {
            return $system_data->isUserAuthenticated();
        }
        
        // Check token (future)
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = self::extractToken($_SERVER['HTTP_AUTHORIZATION']);
            return self::validateToken($token);
        }
        
        return false;
    }
    
    private static function extractToken($header) {
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private static function validateToken($token) {
        // TODO: Implement token validation
        // For now, return false (session-only)
        return false;
    }
}
```

### 6.4 Permissions Middleware (`/api/v1/middleware/permissions.php`)

```php
class PermissionsMiddleware {
    public static function check($moduleName) {
        global $system_data;
        
        $moduleId = $system_data->getModuleId($moduleName);
        if (!$moduleId) {
            return false;
        }
        
        return $system_data->userHasPermission($moduleId);
    }
}
```

### 6.5 CORS Middleware (`/api/v1/middleware/cors.php`)

```php
class CorsMiddleware {
    public static function handle() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Allow same-origin (web UI)
        if ($origin === $_SERVER['HTTP_HOST']) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        // Allow configured origins (for mobile apps)
        $allowedOrigins = self::getAllowedOrigins();
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");
        
        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    private static function getAllowedOrigins() {
        // TODO: Load from configuration
        return [];
    }
}
```

### 6.6 Logger (`/api/v1/lib/Logger.php`)

```php
class Logger {
    private static $logFile = __DIR__ . '/../../log/api.log';
    
    public static function info($message, $context = []) {
        self::write('INFO', $message, $context);
    }
    
    public static function error($code, $message, $exception = null) {
        $context = ['code' => $code];
        if ($exception) {
            $context['trace'] = $exception->getTraceAsString();
        }
        self::write('ERROR', $message, $context);
    }
    
    private static function write($level, $message, $context = []) {
        $log = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        file_put_contents(
            self::$logFile,
            json_encode($log) . "\n",
            FILE_APPEND
        );
    }
}
```

---

## 7. Endpoint Patterns

### 7.1 Standard CRUD Pattern

**List:**
```php
GET /api/v1/rehearsals
Response: {
    "success": true,
    "data": [
        { "id": 1, "begin": "...", ... },
        { "id": 2, "begin": "...", ... }
    ],
    "meta": {
        "pagination": { "page": 1, "limit": 50, "total": 100 }
    }
}
```

**Get:**
```php
GET /api/v1/rehearsals/42
Response: {
    "success": true,
    "data": {
        "id": 42,
        "begin": "2026-02-01 19:00:00",
        "end": "2026-02-01 21:00:00",
        "location": { "id": 5, "name": "..." },
        "participants": [ ... ]
    }
}
```

**Create:**
```php
POST /api/v1/rehearsals
Body: {
    "begin": "2026-02-01 19:00:00",
    "end": "2026-02-01 21:00:00",
    "location": 5
}
Response: {
    "success": true,
    "data": {
        "id": 43,
        "begin": "2026-02-01 19:00:00",
        ...
    }
}
```

**Update:**
```php
PUT /api/v1/rehearsals/42
Body: {
    "begin": "2026-02-01 20:00:00",
    "end": "2026-02-01 22:00:00",
    "location": 5
}
Response: {
    "success": true,
    "data": {
        "id": 42,
        "begin": "2026-02-01 20:00:00",
        ...
    }
}
```

**Delete:**
```php
DELETE /api/v1/rehearsals/42
Response: {
    "success": true,
    "message": "Rehearsal deleted"
}
```

### 7.2 Sub-Resource Pattern

**Get Participants:**
```php
GET /api/v1/rehearsals/42/participants
Response: {
    "success": true,
    "data": [
        { "id": 1, "name": "...", "participate": 1 },
        { "id": 2, "name": "...", "participate": 0 }
    ]
}
```

**Add Participant:**
```php
POST /api/v1/rehearsals/42/participants
Body: {
    "user_id": 5,
    "participate": 1,
    "reason": ""
}
Response: {
    "success": true,
    "data": {
        "id": 123,
        "user_id": 5,
        "participate": 1
    }
}
```

### 7.3 Action Pattern

**Participate:**
```php
POST /api/v1/rehearsals/42/participate
Body: {
    "participate": 1,  // 1=yes, 0=no, 2=maybe
    "reason": "Will attend"
}
Response: {
    "success": true,
    "data": {
        "participate": 1,
        "reason": "Will attend"
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

**Cacheable Endpoints:**
- `GET /api/v1/instruments` - Static data
- `GET /api/v1/groups` - Changes infrequently
- `GET /api/v1/locations` - Changes infrequently

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

**Allow clients to request specific fields:**
```
GET /api/v1/rehearsals?fields=id,begin,end,location
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

## 10. Versioning

### 10.1 Version Strategy

**URL-based versioning:**
- `/api/v1/` - Current version
- `/api/v2/` - Future version (when breaking changes needed)

**Versioning Rules:**
- Breaking changes → New version
- Non-breaking changes → Same version
- Deprecation → Announce in v1, remove in v2

### 10.2 Deprecation Process

1. **Announce:** Add deprecation notice in response headers
2. **Document:** Update API docs with deprecation date
3. **Support:** Keep deprecated endpoint for 6 months
4. **Remove:** Remove in next major version

**Deprecation Header:**
```
Deprecation: true
Sunset: Sat, 25 Jul 2026 12:00:00 GMT
Link: <https://api.example.com/docs/v2>; rel="successor-version"
```

---

## 11. API Testing

### 11.1 Testing Endpoints

**Health Check:**
```
GET /api/v1/health
Response: {
    "success": true,
    "data": {
        "status": "ok",
        "database": "connected",
        "version": "1.0.0"
    }
}
```

**Test Authentication:**
```
GET /api/v1/auth/test
Response: {
    "success": true,
    "data": {
        "authenticated": true,
        "user_id": 5
    }
}
```

### 11.2 Testing Tools

**Recommended:**
- cURL (command line)
- Postman (GUI)
- Browser DevTools (for web UI)
- PHPUnit (for automated tests)

---

## 12. Documentation

### 12.1 API Documentation

**Location:** `/docs/API_ENDPOINTS.md`

**Contents:**
- All endpoints documented
- Request/response examples
- Error codes
- Authentication requirements

### 12.2 OpenAPI/Swagger (Future)

**Consider generating OpenAPI spec:**
- Auto-generate from code
- Interactive API docs
- Client SDK generation

---

**Document Status:** Complete  
**Last Updated:** 2026-01-25  
**Next:** See `API_ENDPOINTS.md` for detailed endpoint specifications
