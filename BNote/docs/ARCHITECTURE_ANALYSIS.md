# BNote Architecture Analysis
**Date:** 2026-01-25  
**Purpose:** Comprehensive analysis of current BNote architecture for REST API + JavaScript UI refactoring

---

## Executive Summary

BNote is a PHP-based ensemble management system with a clean 3-tier architecture:
- **Data Layer** (`/src/data/`): Database access and business data logic
- **Logic Layer** (`/src/logic/`): Business rules and controllers
- **Presentation Layer** (`/src/presentation/`): UI rendering and user interaction

**Current State:**
- 33 data modules, 16 controllers, 33 view modules
- Server-side rendering with full page reloads
- PHP session-based authentication
- Module-based permission system
- Existing API at `/src/export/api.php` (legacy, function-based)

**Target State:**
- RESTful JSON API backend (PHP)
- Vanilla JavaScript SPA frontend
- No page reloads (AJAX interactions)
- Progressive migration (old + new UI coexist)

---

## 1. Directory Structure Analysis

### 1.1 Core Directories

```
BNote/
├── src/
│   ├── data/              # Data Access Layer
│   │   ├── modules/       # 33 module data classes
│   │   ├── abstractdata.php
│   │   ├── database.php
│   │   ├── systemdata.php # Core system & permissions
│   │   └── applicationdataprovider.php
│   ├── logic/             # Business Logic Layer
│   │   ├── modules/       # 16 module controllers
│   │   ├── controller.php # Main router
│   │   ├── defaultcontroller.php
│   │   ├── securitymanager.php
│   │   └── init.php       # Session & initialization
│   ├── presentation/      # Presentation Layer
│   │   ├── modules/       # 33 module views
│   │   ├── widgets/       # Reusable UI components
│   │   ├── banner.php
│   │   ├── navigation.php
│   │   └── optionsbar.php
│   └── export/            # Existing API (legacy)
│       ├── api.php
│       ├── BNoteApiImpl.php
│       └── BNoteApiInterface.php
├── main.php               # Entry point
├── content.php            # Layout wrapper
├── dirs.php               # Directory constants
└── config/                # Configuration files
```

### 1.2 Module Inventory

#### Data Modules (33 total)
1. `abstimmungdata.php` - Voting/Polls
2. `accommodationdata.php` - Accommodations
3. `admindata.php` - Administration
4. `appointmentdata.php` - Appointments
5. `aufgabendata.php` - Tasks
6. `calendardata.php` - Calendar
7. `customfieldsdata.php` - Custom fields
8. `equipmentdata.php` - Equipment
9. `financedata.php` - Finance
10. `genredata.php` - Genres
11. `gruppendata.php` - Groups
12. `hilfedata.php` - Help
13. `instrumentedata.php` - Instruments
14. `kommunikationdata.php` - Communication
15. `konfigurationdata.php` - Configuration
16. `kontaktdatendata.php` - Contact data
17. `kontaktedata.php` - Contacts
18. `konzertedata.php` - Concerts/Performances
19. `locationsdata.php` - Locations
20. `logindata.php` - Authentication
21. `mitspielerdata.php` - Members
22. `nachrichtendata.php` - Messages
23. `outfitsdata.php` - Outfits
24. `probendata.php` - Rehearsals
25. `probenphasendata.php` - Rehearsal phases
26. `programdata.php` - Programs/Setlists
27. `recpaydata.php` - Receipts/Payments
28. `repertoiredata.php` - Repertoire
29. `sharedata.php` - File sharing
30. `startdata.php` - Dashboard/Start
31. `statsdata.php` - Statistics
32. `tourdata.php` - Tours
33. `traveldata.php` - Travel
34. `userdata.php` - Users
35. `websitedata.php` - Website

#### Controller Modules (16 total)
Controllers exist for modules with custom business logic:
- `aufgabencontroller.php` - Tasks
- `calendarcontroller.php` - Calendar
- `customfieldscontroller.php` - Custom fields
- `financecontroller.php` - Finance
- `instrumentecontroller.php` - Instruments
- `kommunikationcontroller.php` - Communication
- `konfigurationcontroller.php` - Configuration
- `kontaktecontroller.php` - Contacts
- `konzertecontroller.php` - Concerts
- `logincontroller.php` - Authentication
- `programcontroller.php` - Programs
- `recpaycontroller.php` - Receipts/Payments
- `repertoirecontroller.php` - Repertoire
- `startcontroller.php` - Dashboard
- `tourcontroller.php` - Tours
- `usercontroller.php` - Users
- `websitecontroller.php` - Website

**Note:** Modules without custom controllers use `DefaultController`.

#### View Modules (33 total)
All modules have corresponding view classes in `/src/presentation/modules/`.

---

## 2. Data Flow Analysis

### 2.1 Request Flow (Current)

```
User Request (main.php?mod=X&mode=Y&id=Z)
    ↓
init.php
    ├── session_start()
    ├── Load SystemData
    └── Check logout
    ↓
Controller.php
    ├── Check permissions (userHasPermission)
    ├── Validate $_GET parameters (security)
    ├── Load module classes:
    │   ├── {Module}Data (from /src/data/modules/)
    │   ├── {Module}Controller (from /src/logic/modules/ or DefaultController)
    │   └── {Module}View (from /src/presentation/modules/)
    └── Initialize module
    ↓
DefaultController.start() or CustomController.start()
    ├── Check $_GET['mode']
    ├── Call View method (e.g., $view->addEntity())
    └── View renders HTML
    ↓
View Class
    ├── Call Data methods (e.g., $data->findAll())
    ├── Render widgets (Form, Table, etc.)
    └── Output HTML
    ↓
Response (Full HTML page)
```

### 2.2 Data Layer Pattern

All data classes extend `AbstractData` which provides:
- `findAll()` - Get all records
- `findById($id)` - Get single record
- `add($values)` - Create record
- `update($id, $values)` - Update record
- `delete($id)` - Delete record
- `getFields()` - Get field definitions
- `validate($values)` - Validate data

**Example Pattern (ProbenData):**
```php
class ProbenData extends AbstractLocationData {
    function __construct() {
        $this->fields = array(
            "id" => array("ID", FieldType::INTEGER),
            "begin" => array("Start", FieldType::DATETIME),
            // ...
        );
        $this->table = "rehearsal";
        $this->init();
    }
    
    // Custom methods
    function getRehearsal($id) { /* ... */ }
    function getParticipants($rid) { /* ... */ }
}
```

### 2.3 Controller Layer Pattern

**DefaultController Pattern:**
```php
class DefaultController {
    public function start() {
        if(isset($_GET['mode'])) {
            $mode = $_GET['mode'];
            $this->view->$mode();  // Call view method
        } else {
            $this->view->start();  // Default view
        }
    }
}
```

**Custom Controller Pattern:**
```php
class StartController extends DefaultController {
    public function start() {
        if(isset($_GET['mode'])) {
            if($mode == "saveParticipation") {
                $this->saveParticipation();  // Custom logic
            } else {
                $this->view->$mode();
            }
        }
    }
}
```

### 2.4 View Layer Pattern

**CrudView Pattern (most modules):**
```php
class ProbenView extends CrudView {
    public function start() {
        $this->showAllTable();  // List all
    }
    
    public function addEntity() {
        $this->addEntityForm();  // Show form
    }
    
    public function add() {
        // Validate & save
        $this->getData()->add($_POST);
        $this->start();  // Redirect to list
    }
    
    public function view() {
        $rehearsal = $this->getData()->getRehearsal($_GET['id']);
        // Render detail view
    }
}
```

---

## 3. Authentication & Authorization

### 3.1 Authentication Flow

**Session-Based Authentication:**
1. User submits login form (`main.php?mod=login&mode=login`)
2. `LoginController->doLogin()` validates credentials
3. On success: `$_SESSION["user"] = $userId`
4. On failure: Redirect to login with error

**Session Check:**
- `SystemData->isUserAuthenticated()` checks `$_SESSION["user"]`
- Also supports HTTP Basic Auth via `$_SERVER["PHP_AUTH_USER"]`

**Logout:**
- `main.php?mod=logout` or `main.php?mod=login&mode=logout`
- Destroys session: `session_destroy()`

### 3.2 Permission System

**Module Permissions:**
- Stored in `privilege` table: `(user, module)`
- Checked via `SystemData->userHasPermission($moduleId)`
- Cached in `$user_module_permission` array

**Permission Check Flow:**
```php
// In Controller.php constructor
if(!$system_data->userHasPermission($system_data->getModuleId())) {
    header("location: main.php?mod=login&fwd=...");
}
```

**Special Cases:**
- Public modules: Always accessible (`category == "public"`)
- Login/Help modules: Always accessible
- Super users: Access to all modules
- Admin group (ID=1): Access to admin modules

### 3.3 File Permissions

**SecurityManager** handles file system access:
- User folders: `data/share/users/user_{id}/`
- Group folders: `data/share/groups/group_{id}/`
- Admin access: Full access to all folders
- Group members: Access to their group folders

**Methods:**
- `canUserAccessFile($file, $uid)` - Check file access
- `userFilePermission($action, $file)` - Check read/write/delete
- `isUserAdmin($uid)` - Check admin status

---

## 4. Routing System

### 4.1 Current Routing

**URL Pattern:**
```
main.php?mod={moduleId}&mode={action}&id={recordId}&...
```

**Examples:**
- `main.php?mod=1` - Dashboard (module 1 = Start)
- `main.php?mod=3&mode=view&id=42` - View contact #42
- `main.php?mod=3&mode=addEntity` - Add new contact
- `main.php?mod=5&mode=programs&sub=view&id=10` - Sub-module routing

**Module Resolution:**
1. `$_GET["mod"]` can be:
   - Numeric ID (e.g., `1`)
   - Module name (e.g., `"Proben"`)
2. `SystemData->getModuleId($name)` resolves to numeric ID
3. Module name derived from `module` table

**Mode Resolution:**
- `$_GET["mode"]` maps to View method name
- Default: `start()` method
- Sub-modules: Custom routing (e.g., `programs&sub=view`)

### 4.2 Security Validation

**Input Validation:**
```php
// In Controller.php
foreach($_GET as $key => $value) {
    if(!preg_match("/^[[:alnum:]...]{1,255}$/", $value)) {
        new BNoteError("Attack detected");
    }
}
```

**SQL Injection Prevention:**
- All queries use prepared statements
- Parameters passed as arrays: `array(array("i", $id))`

---

## 5. Database Schema Patterns

### 5.1 Common Tables

**Core Tables:**
- `module` - Module definitions
- `privilege` - User module permissions
- `user` - User accounts
- `contact` - Contact information
- `group` - Groups
- `user_group` - User-group relationships

**Module-Specific Tables:**
- `rehearsal` - Rehearsals
- `concert` - Concerts
- `rehearsal_user` - Participation tracking
- `concert_user` - Participation tracking
- `program` - Setlists
- `song` - Songs
- `program_song` - Program-song relationships
- `location` - Locations
- `address` - Addresses
- `instrument` - Instruments
- `task` - Tasks
- `vote` - Votes/Polls
- `message` - Messages
- `discussion` - Comments/Discussions

### 5.2 Foreign Key Patterns

**Common References:**
- `location` → `location.id`
- `contact` → `contact.id`
- `user` → `user.id`
- `group` → `group.id`
- `instrument` → `instrument.id`
- `address` → `address.id`

**Many-to-Many:**
- `rehearsal_user` - (rehearsal, user, participate, reason)
- `concert_user` - (concert, user, participate, reason)
- `program_song` - (program, song, rank)
- `user_group` - (user, group)

---

## 6. Existing API Analysis

### 6.1 Current API Structure

**Location:** `/src/export/api.php`

**Pattern:**
- Function-based (not RESTful)
- Single endpoint: `api.php?func={functionName}&param1=value1&...`
- Uses `BNoteApiImpl` class
- Methods defined in `BNoteApiInterface`

**Authentication:**
- Session-based (same as web UI)
- Also supports HTTP Basic Auth

**Example Usage:**
```
GET /src/export/api.php?func=getRehearsals
GET /src/export/api.php?func=getRehearsalsWithParticipation&user=5
POST /src/export/api.php?func=saveParticipation&otype=R&oid=10
```

**Available Functions (from BNoteApiImpl):**
- `getRehearsals()`
- `getRehearsalsWithParticipation($user)`
- `saveParticipation($otype, $oid, $participate, $reason)`
- `addComment($otype, $oid, $message)`
- `getUserInfo()`
- `hasUserAccess($moduleId)`
- `getGroups()`
- `getVoteResult($vid)`
- `getSongsToPractise($rid)`
- `getVersion()`

**Limitations:**
- Not RESTful (function-based routing)
- Inconsistent response formats
- No proper error handling
- Limited to specific functions
- No CRUD operations for most modules

---

## 7. Widget System Analysis

### 7.1 Widget Components

**Location:** `/src/presentation/widgets/`

**Key Widgets:**
1. `form.php` - Form generation
2. `field.php` - Input fields (text, date, select, etc.)
3. `table.php` - Data tables (with DataTables.js integration)
4. `box.php` - Content boxes
5. `link.php` - Action links/buttons
6. `dropdown.php` - Select dropdowns
7. `dataview.php` - Key-value data display
8. `participation.php` - Participation widget
9. `filebrowser.php` - File browser
10. `card.php` - Card component
11. `message.php` - Message display
12. `error.php` - Error display

**Widget Pattern:**
```php
class Form implements iWriteable {
    public function write() {
        // Output HTML
    }
}
```

**Usage:**
```php
$form = new Form("Title", "action.php");
$form->addElement("Name", new Field("name", "", FieldType::CHAR));
$form->write();
```

---

## 8. Language System

**Location:** `/lang/`

**Files:**
- `lang_de.php` - German (primary)
- `lang_en.php` - English
- `lang_fr.php` - French

**Usage:**
```php
Lang::txt("ModuleName_key")
```

**Pattern:**
- Keys follow `{ModuleName}_{method}_{key}` convention
- Example: `"ProbenData_construct.begin"`

---

## 9. Key Dependencies

### 9.1 PHP Dependencies
- PHP 7.4+ (assumed)
- MySQL/MariaDB
- Session support
- XML parsing (for config)
- GD library (for images, if used)

### 9.2 JavaScript Dependencies (Current)
- jQuery
- DataTables.js (for tables)
- FullCalendar.js (for calendar)
- TinyMCE (for rich text editing)
- Dropzone.js (for file uploads)
- jqPlot (for charts)

### 9.3 CSS Dependencies
- Tailwind CSS 3.x (via CDN) - **NEW from Phase 1**
- Bootstrap Icons (for icons)
- Custom theme CSS (`style/css/{theme}/bnote.css`)

---

## 10. Security Considerations

### 10.1 Current Security Measures

1. **Input Validation:**
   - Regex validation on all `$_GET` parameters
   - SQL injection prevention via prepared statements
   - XSS prevention (output escaping in views)

2. **Authentication:**
   - Session-based (secure cookies)
   - Password hashing (crypt with salt)
   - HTTP Basic Auth support

3. **Authorization:**
   - Module-level permissions
   - File-level permissions (SecurityManager)
   - Group-based access control

4. **File Security:**
   - Path traversal prevention (`../` blocked)
   - User/group folder isolation
   - Admin-only access to sensitive areas

### 10.2 Security Gaps for API

1. **CSRF Protection:**
   - Current: None (relies on same-origin)
   - API needs: CSRF tokens or SameSite cookies

2. **Rate Limiting:**
   - Current: None
   - API needs: Rate limiting per user/IP

3. **API Authentication:**
   - Current: Session-based (works for same-origin)
   - API needs: Token-based for mobile apps

4. **CORS:**
   - Current: Not configured
   - API needs: CORS headers for cross-origin requests

---

## 11. Performance Considerations

### 11.1 Current Performance

**Strengths:**
- Server-side rendering (fast initial load)
- Database queries optimized (prepared statements)
- Session caching (permission checks)

**Weaknesses:**
- Full page reloads (slow navigation)
- No client-side caching
- No API response caching
- Large HTML payloads

### 11.2 API Performance Requirements

1. **Response Times:**
   - List endpoints: < 200ms
   - Detail endpoints: < 100ms
   - Create/Update: < 300ms

2. **Caching Strategy:**
   - Static data (instruments, groups): Cache 1 hour
   - User-specific data: No cache
   - Public data: Cache 5 minutes

3. **Pagination:**
   - All list endpoints must support pagination
   - Default: 50 items per page
   - Max: 200 items per page

---

## 12. Mobile App Considerations

### 12.1 Future KMP Integration

**Requirements:**
1. **Stateless Authentication:**
   - Token-based (JWT or session token)
   - No cookie dependency

2. **RESTful Design:**
   - Standard HTTP methods (GET, POST, PUT, DELETE)
   - Consistent URL patterns
   - JSON responses only

3. **Offline Support:**
   - Cacheable endpoints
   - ETag support for conditional requests
   - Last-modified headers

4. **Push Notifications:**
   - Webhook/notification system
   - Event-based triggers

---

## 13. Migration Challenges

### 13.1 Technical Challenges

1. **Session Management:**
   - API must work with existing sessions
   - Mobile apps need token-based auth
   - Solution: Support both (session for web, token for mobile)

2. **Permission Checks:**
   - Must preserve existing permission logic
   - API middleware must check permissions
   - Solution: Reuse `SystemData->userHasPermission()`

3. **File Uploads:**
   - Current: Form-based uploads
   - API needs: Multipart/form-data handling
   - Solution: Support both methods

4. **Complex Forms:**
   - Some forms have dynamic fields
   - Custom validation logic
   - Solution: API must return form schemas

### 13.2 User Experience Challenges

1. **Progressive Migration:**
   - Users must be able to switch between old/new UI
   - Feature flag system needed
   - Solution: Settings toggle + URL parameter

2. **Data Consistency:**
   - Old and new UI must show same data
   - Real-time updates (if implemented)
   - Solution: Shared API backend

3. **Learning Curve:**
   - Users familiar with old UI
   - New UI must be intuitive
   - Solution: Similar layout, better UX

---

## 14. Module Complexity Analysis

### 14.1 Simple Modules (Standard CRUD)

**Pattern:** Extend `CrudView`, use `AbstractData`

**Modules:**
- Instruments
- Genres
- Outfits
- Equipment
- Locations
- Accommodations
- Travel

**API Complexity:** Low (standard CRUD endpoints)

### 14.2 Medium Complexity Modules

**Pattern:** Custom methods, relationships

**Modules:**
- Contacts (relationships: instruments, groups, addresses)
- Rehearsals (participation tracking, groups, songs)
- Concerts (programs, participation, locations)
- Tasks (assignments, due dates)
- Programs (songs, templates)

**API Complexity:** Medium (custom endpoints + relationships)

### 14.3 Complex Modules

**Pattern:** Multiple sub-modules, complex business logic

**Modules:**
- Start/Dashboard (aggregates multiple data sources)
- Finance (receipts, payments, calculations)
- Communication (messages, discussions, notifications)
- Share (file system, permissions)
- Admin (system configuration, user management)
- Statistics (aggregations, reports)

**API Complexity:** High (multiple endpoints, complex logic)

---

## 15. Recommendations

### 15.1 API Design

1. **RESTful Structure:**
   - `/api/v1/{resource}/{id}`
   - Standard HTTP methods
   - Consistent response format

2. **Authentication:**
   - Session-based for web (backward compatible)
   - Token-based for mobile (new endpoint)

3. **Error Handling:**
   - Standard error response format
   - HTTP status codes
   - Detailed error messages

### 15.2 JavaScript Architecture

1. **Modular Structure:**
   - API client module
   - Component library
   - State management
   - Router (optional)

2. **Progressive Enhancement:**
   - Start with API + basic JS
   - Add components incrementally
   - Maintain backward compatibility

### 15.3 Migration Strategy

1. **Phase 1:** API Infrastructure
   - Create API router
   - Build core modules (auth, dashboard)
   - Test with curl/Postman

2. **Phase 2:** High-Traffic Modules
   - Rehearsals, Concerts, Calendar
   - Most-used features first

3. **Phase 3:** Remaining Modules
   - Batch create APIs
   - Standard patterns

4. **Phase 4:** UI Refactoring
   - Create new UI pages
   - Feature flag system
   - Gradual rollout

---

## 16. Next Steps

1. **Create API Architecture Document** (see `API_ARCHITECTURE.md`)
2. **Design API Endpoints** (see `API_ENDPOINTS.md`)
3. **Design JavaScript Architecture** (see `JS_ARCHITECTURE.md`)
4. **Create Migration Plan** (see `MIGRATION_PLAN.md`)
5. **Create Testing Checklist** (see `TESTING_CHECKLIST.md`)

---

**Document Status:** Complete  
**Last Updated:** 2026-01-25  
**Author:** AI Assistant (Auto)
