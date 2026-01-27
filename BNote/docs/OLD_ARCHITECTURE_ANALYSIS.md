# BNote Legacy Architecture Analysis
**Version:** 1.0  
**Date:** 2026-01-27  
**Purpose:** Comprehensive analysis of the original BNote PHP architecture with detailed architecture and dataflow diagrams

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [System Architecture Diagram](#system-architecture-diagram)
4. [Request Flow Analysis](#request-flow-analysis)
5. [Data Flow Analysis](#data-flow-analysis)
6. [Layer-by-Layer Analysis](#layer-by-layer-analysis)
7. [Module System](#module-system)
8. [Widget System](#widget-system)
9. [Security Model](#security-model)
10. [Session Management](#session-management)
11. [Database Architecture](#database-architecture)
12. [Component Interactions](#component-interactions)

---

## Executive Summary

BNote is a PHP-based ensemble management system built on a **3-tier MVC (Model-View-Controller) architecture**. The system uses server-side rendering with full page reloads, PHP session-based authentication, and a module-based permission system.

### Key Characteristics

- **Architecture Pattern:** 3-tier MVC (Data/Logic/Presentation)
- **Rendering:** Server-side PHP rendering (full page reloads)
- **Authentication:** PHP session-based (`$_SESSION['user']`)
- **URL Pattern:** `main.php?mod={module}&mode={action}&id={id}`
- **Module Count:** 33 data modules, 16 controllers, 33 views
- **Widget System:** 22 reusable UI widgets
- **Database:** MySQL with custom Database abstraction layer
- **Language:** German (with translation system via `Lang::txt()`)

### Technology Stack

- **Backend:** PHP 7.4+ (object-oriented)
- **Database:** MySQL
- **Frontend:** HTML/CSS/JavaScript (minimal client-side JS)
- **Session:** PHP native sessions
- **Configuration:** XML files (`config/config.xml`, `config/company.xml`)

---

## Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Client Browser                            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  HTTP Request: main.php?mod=X&mode=Y&id=Z           │   │
│  └──────────────────────────────────────────────────────┘   │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    Entry Point (main.php)                    │
│  • Load dirs.php (directory constants)                       │
│  • Load init.php (system initialization)                     │
│  • Create Controller instance                                │
│  • Include head.php (HTML head)                               │
│  • Include content.php (main content area)                    │
│  • Include footer.php (HTML footer)                           │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│              System Initialization (init.php)                │
│  • Start PHP session                                         │
│  • Load all widget classes                                   │
│  • Validate mod parameter                                    │
│  • Initialize Systemdata (core system)                      │
│  • Load language file (lang.php)                            │
│  • Handle logout if needed                                   │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│              Main Controller (controller.php)                │
│  • Check user permissions                                    │
│  • Validate $_GET parameters (security)                     │
│  • Determine module name from URL                            │
│  • Load module classes:                                      │
│    - {Module}Data (from /src/data/modules/)                 │
│    - {Module}Controller (from /src/logic/modules/)          │
│    - {Module}View (from /src/presentation/modules/)         │
│  • Instantiate and wire components                           │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│         Module Controller (DefaultController or Custom)      │
│  • Check $_GET['mode'] parameter                            │
│  • Call corresponding View method                            │
│  • View method calls Data methods                            │
│  • View renders HTML using widgets                           │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│              Module View ({Module}View)                      │
│  • Implements AbstractView or CrudView                       │
│  • Calls Data layer methods                                  │
│  • Renders HTML using widgets                                │
│  • Outputs HTML directly (echo/print)                        │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│              Module Data ({Module}Data)                      │
│  • Extends AbstractData                                      │
│  • Defines fields, table, references                         │
│  • Implements CRUD operations                                │
│  • Uses Database class for queries                           │
└───────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                             │
│  • Database class (PDO wrapper)                              │
│  • MySQL connection                                          │
│  • Query execution                                           │
└─────────────────────────────────────────────────────────────┘
```

---

## System Architecture Diagram

### Complete System Architecture

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                                 │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Layout Components                                                   │   │
│  │  • head.php (HTML head, CSS, JS)                                     │   │
│  │  • banner.php (header/navigation bar)                                 │   │
│  │  • navigation.php (sidebar menu)                                       │   │
│  │  • optionsbar.php (action buttons)                                    │   │
│  │  • footer.php (footer)                                                │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Module Views (33 modules)                                          │   │
│  │  • AbstractView (base class)                                        │   │
│  │  • CrudView (CRUD operations)                                       │   │
│  │  • CrudRefView (with references)                                     │   │
│  │  • CrudRefLocationView (with location)                              │   │
│  │  • {Module}View classes (e.g., ProbenView, UserView)                │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Widget System (22 widgets)                                        │   │
│  │  • Form, Table, Field, Box, Card                                    │   │
│  │  • Link, Message, Error, Dropdown                                   │   │
│  │  • FileBrowser, HtmlEditor, Participation                            │   │
│  │  • GroupSelector, FilterBox, DataView                                │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                        │
                                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            LOGIC LAYER                                       │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Main Controller (controller.php)                                    │   │
│  │  • Routes requests to modules                                        │   │
│  │  • Checks permissions                                                │   │
│  │  • Validates input                                                   │   │
│  │  • Instantiates module components                                    │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Module Controllers (16 modules)                                    │   │
│  │  • DefaultController (template for all modules)                     │   │
│  │  • Custom controllers (LoginController, UserController, etc.)       │   │
│  │  • Coordinates between View and Data                                │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  System Components                                                   │   │
│  │  • init.php (system initialization)                                  │   │
│  │  • SecurityManager (permission checks)                               │   │
│  │  • Systemdata (core system data)                                    │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                        │
                                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                             DATA LAYER                                        │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Abstract Data Classes                                               │   │
│  │  • AbstractData (base DAO)                                           │   │
│  │  • AbstractLocationData (with location)                             │   │
│  │  • ApplicationDataProvider (shared data access)                     │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Module Data Classes (33 modules)                                   │   │
│  │  • {Module}Data classes (e.g., ProbenData, UserData)                 │   │
│  │  • Extend AbstractData                                               │   │
│  │  • Define fields, table, references                                 │   │
│  │  • Implement CRUD operations                                         │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Database Abstraction                                                │   │
│  │  • Database class (PDO wrapper)                                      │   │
│  │  • Query building and execution                                      │   │
│  │  • Transaction support                                               │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  System Data                                                        │   │
│  │  • Systemdata (core system)                                         │   │
│  │  • Module management                                                 │   │
│  │  • Permission system                                                 │   │
│  │  • Configuration (XML)                                               │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                        │
                                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                          DATABASE (MySQL)                                    │
│  • Tables for all entities (users, rehearsals, concerts, etc.)             │
│  • Module table (permissions)                                               │
│  • Configuration tables                                                      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Request Flow Analysis

### Detailed Request Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 1: HTTP Request                                                       │
│  URL: main.php?mod=proben&mode=addEntity&id=42                             │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 2: main.php                                                           │
│  • Load dirs.php (define directory constants)                                │
│  • Load init.php (system initialization)                                    │
│  • Handle login if mode=login                                                │
│  • Create Controller instance                                                │
│  • Include head.php                                                          │
│  • Include content.php                                                       │
│  • Include footer.php                                                        │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 3: init.php                                                           │
│  • session_start() (if not already started)                                │
│  • Load all widget classes (22 widgets)                                     │
│  • Validate mod parameter (alphanumeric, 1-100 chars)                      │
│  • Initialize Systemdata:                                                   │
│    - Load config/config.xml                                                 │
│    - Load config/company.xml                                                │
│    - Create Database connection                                             │
│    - Initialize user permissions                                            │
│  • Load lang.php (translation system)                                        │
│  • Handle logout if mod=logout                                              │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 4: Controller.php (constructor)                                       │
│  • Check permissions:                                                       │
│    - Get module ID from Systemdata                                          │
│    - Check userHasPermission(moduleId)                                     │
│    - If no permission → redirect to login                                   │
│  • Security validation:                                                     │
│    - Validate all $_GET parameters against regex                           │
│    - Prevent injection attacks                                              │
│  • Determine module name:                                                   │
│    - Get module title from Systemdata                                       │
│    - Convert to lowercase (e.g., "Proben" → "proben")                      │
│    - Check if login module (home, login, logout, etc.)                      │
│  • Load abstract classes:                                                   │
│    - AbstractData, AbstractLocationData                                      │
│    - AbstractView, CrudView, CrudRefView                                    │
│  • Load module-specific classes:                                            │
│    - Check if custom controller exists                                      │
│    - Load {Module}Data (e.g., probendata.php)                               │
│    - Load {Module}Controller (e.g., probencontroller.php or DefaultController)│
│    - Load {Module}View (e.g., probenview.php)                               │
│  • Instantiate components:                                                  │
│    - $moduleCtrl = new ProbenController()                                    │
│    - $moduleData = new ProbenData()                                         │
│    - $moduleView = new ProbenView($moduleCtrl)                              │
│  • Wire components:                                                         │
│    - $moduleCtrl->setData($moduleData)                                      │
│    - $moduleCtrl->setView($moduleView)                                      │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 5: content.php                                                       │
│  • Include banner.php (header/navigation)                                   │
│  • Include navigation.php (sidebar menu)                                    │
│  • Include optionsbar.php (action buttons)                                  │
│  • Call $mainController->getController()->start()                           │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 6: DefaultController.start() or CustomController.start()             │
│  • Check if view is set                                                     │
│  • Check $_GET['mode'] parameter:                                          │
│    - If mode exists → call $view->$mode()                                  │
│    - If no mode → call $view->start()                                      │
│  • Example: mode="addEntity" → $view->addEntity()                          │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 7: {Module}View method (e.g., ProbenView->addEntity())               │
│  • Call Data layer methods:                                                │
│    - $data->findAll() (for dropdowns, etc.)                                │
│    - $data->findById($id) (if editing)                                      │
│  • Create and render widgets:                                             │
│    - $form = new Form()                                                     │
│    - $form->addField(...)                                                   │
│    - $form->write() (outputs HTML)                                         │
│  • Handle form submission:                                                 │
│    - If POST → validate and save                                           │
│    - Call $data->add($values) or $data->update($id, $values)                │
│    - Redirect or show success message                                      │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 8: {Module}Data method (e.g., ProbenData->add($values))              │
│  • Validate input using $this->validate($values)                            │
│  • Build SQL query using Database class                                     │
│  • Execute query: $this->database->execute(...)                             │
│  • Return result or ID                                                      │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 9: Database class                                                    │
│  • Execute PDO query                                                       │
│  • Return result set or affected rows                                       │
└───────────────────────────────────────┬─────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  STEP 10: HTML Response                                                    │
│  • All HTML output via echo/print statements                                │
│  • Widgets output their HTML                                                │
│  • Full HTML page sent to browser                                          │
│  • Browser renders page (full page reload)                                  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Request Flow Sequence Diagram

```
User Browser          main.php          init.php         Controller      ModuleCtrl      ModuleView      ModuleData      Database
     │                   │                  │                  │               │               │               │               │
     │──HTTP Request───>│                  │                  │               │               │               │               │
     │  ?mod=X&mode=Y   │                  │                  │               │               │               │               │
     │                   │                  │                  │               │               │               │               │
     │                   │──load dirs.php──>│                  │               │               │               │               │
     │                   │                  │                  │               │               │               │               │
     │                   │──load init.php─>│                  │               │               │               │               │
     │                   │                  │                  │               │               │               │               │
     │                   │                  │──session_start()│               │               │               │               │
     │                   │                  │──load widgets──│               │               │               │               │
     │                   │                  │──Systemdata─────│               │               │               │               │
     │                   │                  │                  │               │               │               │               │
     │                   │<──return────────│                  │               │               │               │               │
     │                   │                  │                  │               │               │               │               │
     │                   │──new Controller()│                  │               │               │               │               │
     │                   │                  │                  │               │               │               │               │
     │                   │                  │                  │──check perm───│               │               │               │
     │                   │                  │                  │──validate─────│               │               │               │
     │                   │                  │                  │──load module───│               │               │               │
     │                   │                  │                  │──instantiate──>│               │               │               │
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │──setData()───>│               │               │
     │                   │                  │                  │                │──setView()───>│               │               │
     │                   │                  │                  │                │               │               │               │
     │                   │──include head───│                  │                │               │               │               │
     │                   │──include content│                  │                │               │               │               │
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │──start()──────>│               │               │
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │               │──$mode()──────>│               │
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │               │──findAll()────>│               │
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │               │               │──query───────>│
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │               │               │<──result──────│
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │               │<──data─────────│               │
     │                   │                  │                  │                │               │               │               │
     │                   │                  │                  │                │               │──render HTML──│               │
     │                   │                  │                  │                │               │               │               │
     │<──HTML Response───│                  │                  │                │               │               │               │
     │  (Full Page)      │                  │                  │                │               │               │               │
```

---

## Data Flow Analysis

### Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DATA FLOW: CREATE OPERATION                           │
└─────────────────────────────────────────────────────────────────────────────┘

User Input (HTML Form)
         │
         │ POST request with form data
         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  View Layer (ProbenView->addEntity())                                        │
│  • Receives $_POST data                                                      │
│  • Validates required fields                                                │
│  • Calls $this->getData()->add($values)                                      │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ $values = array('begin' => '...', ...)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Data Layer (ProbenData->add($values))                                       │
│  • Validates using $this->validate($values)                                 │
│  • Sanitizes input using $this->regex                                        │
│  • Builds SQL INSERT query                                                   │
│  • Calls $this->database->execute($query, $params)                           │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ SQL: INSERT INTO proben ...
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Database Layer (Database->execute())                                        │
│  • Uses PDO prepared statements                                             │
│  • Executes query                                                            │
│  • Returns last insert ID or affected rows                                   │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ Return: $id or true/false
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Data Layer (returns to View)                                                │
│  • Returns ID or success status                                              │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ Return: $id
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  View Layer (handles result)                                                 │
│  • Shows success message                                                     │
│  • Redirects to view mode or list                                           │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Data Flow: Read Operation

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DATA FLOW: READ OPERATION                             │
└─────────────────────────────────────────────────────────────────────────────┘

User Request (GET main.php?mod=proben&id=42)
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  View Layer (ProbenView->viewEntity())                                       │
│  • Gets $id from $_GET['id']                                                 │
│  • Calls $this->getData()->findById($id)                                     │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ $id = 42
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Data Layer (ProbenData->findById($id))                                      │
│  • Builds SQL SELECT query                                                   │
│  • Calls $this->database->getSelection($query, $params)                     │
│  • Processes result set                                                      │
│  • Handles references (joins)                                                │
│  • Returns associative array                                                 │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ SQL: SELECT * FROM proben WHERE id = ?
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Database Layer (Database->getSelection())                                   │
│  • Uses PDO prepared statements                                             │
│  • Executes query                                                            │
│  • Returns result set as array                                               │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ Return: array('id' => 42, 'begin' => '...', ...)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Data Layer (processes and returns)                                         │
│  • Formats dates/times                                                       │
│  • Resolves references (e.g., location_id → location name)                   │
│  • Returns formatted data                                                    │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ Return: $entity = array(...)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  View Layer (renders data)                                                   │
│  • Creates widgets (Form, Table, etc.)                                       │
│  • Populates widgets with data                                               │
│  • Calls widget->write() to output HTML                                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Data Flow: Update Operation

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DATA FLOW: UPDATE OPERATION                          │
└─────────────────────────────────────────────────────────────────────────────┘

User Request (POST main.php?mod=proben&mode=editEntity&id=42)
         │
         │ $_POST data + $_GET['id']
         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  View Layer (ProbenView->editEntity())                                       │
│  • Gets $id from $_GET['id']                                                 │
│  • Gets $values from $_POST                                                  │
│  • Calls $this->getData()->update($id, $values)                             │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ $id = 42, $values = array(...)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Data Layer (ProbenData->update($id, $values))                              │
│  • Validates using $this->validate($values)                                 │
│  • Builds SQL UPDATE query                                                   │
│  • Calls $this->database->execute($query, $params)                         │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ SQL: UPDATE proben SET ... WHERE id = ?
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Database Layer (Database->execute())                                        │
│  • Executes UPDATE query                                                     │
│  • Returns affected rows                                                     │
└───────────────────────────────┬─────────────────────────────────────────────┘
                                 │
                                 │ Return: affected rows count
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  View Layer (handles result)                                                 │
│  • Shows success message                                                     │
│  • Redirects to view mode                                                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Layer-by-Layer Analysis

### 1. Presentation Layer

**Location:** `/src/presentation/`

**Components:**

#### 1.1 Layout Components

- **head.php:** HTML `<head>` section, includes CSS/JS, sets page title
- **banner.php:** Top navigation bar with logo and user info
- **navigation.php:** Sidebar menu with module links
- **optionsbar.php:** Action buttons (Add, Edit, Delete, etc.)
- **footer.php:** Footer with copyright and links

#### 1.2 Module Views

**Base Classes:**
- **AbstractView:** Base class for all views
  - `start()` - Entry point (abstract method)
  - `getTitle()` - Get page title
  - `showOptions()` - Show action buttons
  - `backToStart()` - Back button
  - `deleteConfirmationMessage()` - Delete confirmation

- **CrudView:** Extends AbstractView, provides CRUD operations
  - `start()` - List view
  - `addEntity()` - Create form
  - `editEntity()` - Edit form
  - `viewEntity()` - View details
  - `deleteEntity()` - Delete confirmation and execution

- **CrudRefView:** Extends CrudView, adds reference handling
- **CrudRefLocationView:** Extends CrudRefView, adds location support

**Module View Pattern:**
```php
class ProbenView extends CrudRefLocationView {
    function start() {
        // List all rehearsals
        $data = $this->getData()->findAll();
        $table = new Table($data);
        $table->write();
    }
    
    function addEntity() {
        // Show create form
        $form = new Form();
        $form->addField(new Field("begin", "Begin", FieldType::DATETIME));
        $form->write();
        
        // Handle POST
        if(isset($_POST["begin"])) {
            $this->getData()->add($_POST);
            // Redirect or show message
        }
    }
}
```

#### 1.3 Widget System

**22 Widgets** located in `/src/presentation/widgets/`:

1. **Form** - HTML form with fields
2. **Table** - Data table with sorting
3. **Field** - Form field (input, select, textarea, etc.)
4. **Box** - Container box
5. **Card** - Card component
6. **Link** - Hyperlink with icon
7. **Message** - Success/error message
8. **Error** - Error display
9. **Dropdown** - Dropdown menu
10. **FileBrowser** - File upload/browser
11. **HtmlEditor** - Rich text editor
12. **GroupSelector** - Group selection widget
13. **FilterBox** - Filter/search box
14. **DataView** - Data display view
15. **Participation** - Participation status widget
16. **Chat** - Chat widget
17. **ListField** - List field widget
18. **PlainList** - Simple list
19. **SectionForm** - Sectioned form
20. **TextWriteable** - Text input widget
21. **Writing** - Writing widget
22. **IWriteable** - Interface for writeable widgets

**Widget Usage Pattern:**
```php
// Create widget
$form = new Form("?mod=proben&mode=addEntity");

// Add fields
$form->addField(new Field("begin", "Begin", FieldType::DATETIME));
$form->addField(new Field("location", "Location", FieldType::REFERENCE, "location"));

// Output HTML
$form->write();
```

### 2. Logic Layer

**Location:** `/src/logic/`

**Components:**

#### 2.1 Main Controller

**File:** `controller.php`

**Responsibilities:**
- Route requests to appropriate module
- Check user permissions
- Validate input parameters
- Load and instantiate module components
- Wire components together

**Key Methods:**
```php
class Controller {
    private $moduleCtrl;  // Module controller
    private $moduleView;  // Module view
    private $moduleData;  // Module data
    
    function __construct() {
        // 1. Check permissions
        if(!$system_data->userHasPermission($system_data->getModuleId())) {
            // Redirect to login
        }
        
        // 2. Validate $_GET parameters
        // Security check against injection
        
        // 3. Determine module name
        $modName = strtolower($system_data->getModuleTitle(-1, false));
        
        // 4. Load module classes
        require($GLOBALS['DIR_DATA_MODULES'] . $modName . "data.php");
        require($GLOBALS['DIR_PRESENTATION_MODULES'] . $modName . "view.php");
        
        // 5. Instantiate
        $this->moduleCtrl = new $controllerClass();
        $this->moduleData = new $dataClass();
        $this->moduleView = new $viewClass($this->moduleCtrl);
        
        // 6. Wire components
        $this->moduleCtrl->setData($this->moduleData);
        $this->moduleCtrl->setView($this->moduleView);
    }
}
```

#### 2.2 Module Controllers

**Base Class:** `DefaultController`

**Pattern:**
```php
class DefaultController {
    private $view;
    private $data;
    
    public function start() {
        if(isset($_GET['mode'])) {
            $mode = $_GET['mode'];
            $this->view->$mode();  // Call view method dynamically
        } else {
            $this->view->start();  // Default: start view
        }
    }
}
```

**Custom Controllers (16 modules):**
- LoginController - Handles authentication
- UserController - User management logic
- KonzerteController - Concert-specific logic
- TourController - Tour management
- FinanceController - Financial operations
- etc.

#### 2.3 System Components

**init.php:**
- Starts PHP session
- Loads all widget classes
- Initializes Systemdata
- Loads language file
- Handles logout

**SecurityManager:**
- Permission checking
- Access control

**Systemdata:**
- Core system configuration
- Module management
- User permissions
- Database connection

### 3. Data Layer

**Location:** `/src/data/`

**Components:**

#### 3.1 Abstract Data Classes

**AbstractData:**
- Base class for all data access objects
- Provides CRUD operations
- Field definition and validation
- Reference handling
- Database abstraction

**Key Methods:**
```php
abstract class AbstractData {
    protected $database;      // Database connection
    protected $regex;         // Regex validator
    protected $fields;        // Field definitions
    protected $references;    // Foreign key references
    protected $table;         // Database table name
    
    // CRUD operations
    function findAll() { ... }
    function findById($id) { ... }
    function add($values) { ... }
    function update($id, $values) { ... }
    function delete($id) { ... }
    
    // Validation
    function validate($values) { ... }
    
    // Field management
    function getFields() { ... }
}
```

**AbstractLocationData:**
- Extends AbstractData
- Adds location support
- Location reference handling

**ApplicationDataProvider:**
- Shared data access methods
- Common queries used across modules
- Helper methods for data operations

#### 3.2 Module Data Classes

**33 Module Data Classes**, each extending AbstractData or AbstractLocationData:

**Example:**
```php
class ProbenData extends AbstractLocationData {
    function __construct() {
        parent::__construct();
        
        // Define table
        $this->table = "probe";
        
        // Define fields
        $this->fields = array(
            "begin" => array("Begin", FieldType::DATETIME),
            "end" => array("End", FieldType::DATETIME),
            "location" => array("Location", FieldType::REFERENCE, "location"),
            "conductor" => array("Conductor", FieldType::REFERENCE, "contact")
        );
        
        // Define references
        $this->references = array(
            "location" => "location",
            "conductor" => "contact"
        );
    }
    
    // Custom methods
    function getUpcoming() {
        // Custom query for upcoming rehearsals
    }
}
```

#### 3.3 Database Abstraction

**Database Class:**
- PDO wrapper
- Prepared statements
- Query building
- Transaction support
- Result set handling

**Key Methods:**
```php
class Database {
    function getSelection($query, $params = array()) { ... }
    function execute($query, $params = array()) { ... }
    function colValue($query, $column, $params = array()) { ... }
    function beginTransaction() { ... }
    function commit() { ... }
    function rollback() { ... }
}
```

#### 3.4 System Data

**Systemdata Class:**
- Core system configuration
- Module management
- User permissions
- Database connection
- Configuration (XML files)

**Key Methods:**
```php
class Systemdata {
    public $dbcon;      // Database connection
    public $regex;      // Regex validator
    
    function getModuleId($name = null) { ... }
    function getModuleTitle($id = -1) { ... }
    function userHasPermission($moduleId) { ... }
    function getUserId() { ... }
    function getDynamicConfigParameter($key) { ... }
}
```

---

## Module System

### Module Structure

Each module consists of three components:

1. **Data Class** (`{Module}Data`) - Data access
2. **Controller Class** (`{Module}Controller` or `DefaultController`) - Business logic
3. **View Class** (`{Module}View`) - UI rendering

### Module Loading Process

```
1. User requests: main.php?mod=proben
   ↓
2. Controller.php determines module name: "proben"
   ↓
3. Load files:
   - /src/data/modules/probendata.php
   - /src/logic/modules/probencontroller.php (if exists, else DefaultController)
   - /src/presentation/modules/probenview.php
   ↓
4. Instantiate classes:
   - $data = new ProbenData()
   - $controller = new ProbenController() or new DefaultController()
   - $view = new ProbenView($controller)
   ↓
5. Wire components:
   - $controller->setData($data)
   - $controller->setView($view)
   ↓
6. Execute:
   - $controller->start()
   - Calls $view->start() or $view->$mode()
```

### Module Modes

Modules support different "modes" via `$_GET['mode']`:

- **start** (default) - List view
- **addEntity** - Create form
- **editEntity** - Edit form
- **viewEntity** - View details
- **deleteEntity** - Delete confirmation
- **Custom modes** - Module-specific actions

### Module Permissions

Permissions are checked at the Controller level:

```php
// In Controller.php constructor
if(!$system_data->userHasPermission($system_data->getModuleId())) {
    // Redirect to login
    header("location: main.php?mod=login&fwd=...");
}
```

Permissions are stored in the database:
- `module` table - List of modules
- `user_module` table - User-module permissions
- `group_module` table - Group-module permissions

---

## Widget System

### Widget Architecture

Widgets are reusable UI components that encapsulate HTML generation. They follow a consistent pattern:

```php
class Widget {
    protected $id;
    protected $classes;
    
    function write() {
        // Output HTML
        echo $this->getHtml();
    }
    
    protected function getHtml() {
        // Generate HTML
    }
}
```

### Widget Usage Pattern

```php
// In View class
function start() {
    // Create widget
    $table = new Table();
    
    // Configure widget
    $table->setData($this->getData()->findAll());
    $table->addColumn("begin", "Begin");
    $table->addColumn("location", "Location");
    
    // Output HTML
    $table->write();
}
```

### Key Widgets

**Form Widget:**
- Creates HTML forms
- Handles field rendering
- Form validation
- Submission handling

**Table Widget:**
- Displays data in table format
- Sorting support
- Column configuration
- Row actions

**Field Widget:**
- Various field types (text, select, date, etc.)
- Validation
- Reference handling

**Box/Card Widgets:**
- Container components
- Styling and layout

---

## Security Model

### Authentication

**Session-Based:**
- PHP native sessions (`$_SESSION['user']`)
- User ID stored in session
- Session checked on every request

**Login Process:**
```
1. User submits login form
2. LoginController->doLogin()
3. Validates credentials
4. Sets $_SESSION['user'] = $userId
5. Redirects to requested page or dashboard
```

**Logout Process:**
```
1. User clicks logout (mod=logout)
2. init.php detects logout
3. Clears $_SESSION
4. Destroys session
5. Redirects to login
```

### Authorization

**Permission System:**
- Module-based permissions
- User-level and group-level permissions
- Checked in Controller constructor

**Permission Check:**
```php
// In Controller.php
if(!$system_data->userHasPermission($system_data->getModuleId())) {
    // Redirect to login with forward parameter
    header("location: main.php?mod=login&fwd=" . urlencode($_SERVER["QUERY_STRING"]));
}
```

### Input Validation

**Parameter Validation:**
- All `$_GET` parameters validated against regex
- Prevents injection attacks
- Special handling for login forward parameter

**Data Validation:**
- Field-level validation in Data classes
- Regex validation for input
- Type checking
- Required field validation

### SQL Injection Prevention

**Prepared Statements:**
- All database queries use PDO prepared statements
- Parameters bound, not concatenated
- Safe from SQL injection

---

## Session Management

### Session Lifecycle

```
1. Request arrives
   ↓
2. init.php checks: session_status() === PHP_SESSION_NONE
   ↓
3. If not started: session_start()
   ↓
4. Systemdata checks $_SESSION['user']
   ↓
5. If user not authenticated:
   - Redirect to login
   - Store forward URL
   ↓
6. If authenticated:
   - Load user permissions
   - Continue with request
   ↓
7. Response sent
   ↓
8. Session persists (PHP handles)
```

### Session Data

**Stored in Session:**
- `$_SESSION['user']` - User ID (integer)
- PHP session ID (cookie: PHPSESSID)

**Session Configuration:**
- Managed by PHP
- Cookie-based (default)
- Server-side storage

---

## Database Architecture

### Database Connection

**Database Class:**
- PDO wrapper
- Singleton pattern (via Systemdata)
- Connection pooling (PHP handles)

**Connection:**
```php
// In Systemdata constructor
$this->dbcon = new Database();
// Database reads config from config/config.xml
```

### Query Pattern

**Prepared Statements:**
```php
// In Data class
$query = "SELECT * FROM probe WHERE id = ?";
$result = $this->database->getSelection($query, array(
    array("i", $id)
));
```

**Transaction Support:**
```php
$this->database->beginTransaction();
try {
    $this->database->execute($query1, $params1);
    $this->database->execute($query2, $params2);
    $this->database->commit();
} catch (Exception $e) {
    $this->database->rollback();
}
```

### Table Structure

**Entity Tables:**
- Each module has corresponding table(s)
- Standard fields: `id` (primary key)
- Foreign keys for references

**System Tables:**
- `module` - Module definitions
- `user` - User accounts
- `user_module` - User permissions
- `group_module` - Group permissions
- `configuration` - Dynamic configuration

---

## Component Interactions

### Component Relationships

```
┌─────────────────────────────────────────────────────────────┐
│                    Component Dependencies                   │
└─────────────────────────────────────────────────────────────┘

Controller
    │
    ├──> View (has reference)
    │       │
    │       ├──> Data (via Controller)
    │       │       │
    │       │       └──> Database (via Systemdata)
    │       │
    │       └──> Widgets (creates and uses)
    │
    └──> Data (has reference)
            │
            └──> Database (via Systemdata)

Systemdata
    │
    ├──> Database (creates and manages)
    │
    └──> Regex (creates)

All Components
    │
    └──> Systemdata (global access)
```

### Data Access Pattern

```
View
  │
  │ $this->getData()->findAll()
  │
  ▼
Controller
  │
  │ Returns $this->data
  │
  ▼
Data
  │
  │ $this->database->getSelection(...)
  │
  ▼
Database
  │
  │ PDO query
  │
  ▼
MySQL
```

### Widget Rendering Pattern

```
View
  │
  │ $form = new Form()
  │ $form->addField(new Field(...))
  │ $form->write()
  │
  ▼
Widget
  │
  │ Generates HTML
  │ echo $html
  │
  ▼
Browser
```

---

## Summary

### Architecture Strengths

1. **Clear Separation of Concerns:** 3-tier architecture (Data/Logic/Presentation)
2. **Modularity:** Module-based structure allows easy extension
3. **Reusability:** Widget system provides reusable UI components
4. **Security:** Input validation, prepared statements, permission system
5. **Consistency:** Standard patterns across all modules

### Architecture Limitations

1. **Server-Side Rendering:** Full page reloads on every action
2. **No API Layer:** Direct coupling between View and Data
3. **Limited Client-Side Interactivity:** Minimal JavaScript
4. **Tight Coupling:** View directly calls Data methods
5. **No State Management:** Stateless requests, no client-side state

### Migration Path

The Next Generation architecture (`/next/`) addresses these limitations:
- REST API layer separates frontend from backend
- JavaScript SPA eliminates page reloads
- Client-side state management
- Modern UI with dark mode and i18n

---

**Document Status:** Complete  
**Last Updated:** 2026-01-27  
**See Also:**
- [ARCHITECTURE_ANALYSIS.md](ARCHITECTURE_ANALYSIS.md) - General architecture overview
- [next/README.md](../next/README.md) - Next Generation architecture
