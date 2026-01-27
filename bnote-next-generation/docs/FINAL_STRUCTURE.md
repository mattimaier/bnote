# bnote-next-generation Final Structure

## Project Layout

```
/Users/stefan/dev/bnote/
├── BNote/                        ← Original BNote codebase (READ ONLY)
│   ├── src/                      ← Old PHP codebase
│   ├── main.php
│   ├── config/
│   └── ...
└── bnote-next-generation/        ← BNote Next Generation (ONLY MODIFY THIS)
    ├── api/
    │   ├── paths.php             ← Path configuration (defines BNOTE_ROOT)
    │   ├── index.php             ← API router
    │   └── modules/              ← API modules
    ├── assets/
    │   ├── js/                   ← Frontend JavaScript
    │   └── css/                  ← Styles
    ├── docs/                     ← Documentation
    ├── lang/                     ← Translation files
    ├── src/
    │   └── data/
    │       └── modules/
    │           └── searchdata.php  ← Search functionality (moved from old codebase)
    └── .cursorrules              ← Development rules
```

## URL Structure

**Base URL:** `http://localhost:8888/bnote/bnote-next-generation/`

**Examples:**
- Login: `http://localhost:8888/bnote/bnote-next-generation/login.html`
- Dashboard: `http://localhost:8888/bnote/bnote-next-generation/dashboard.html`
- API: `http://localhost:8888/bnote/bnote-next-generation/api/index.php?module=auth&action=login`

## Path Configuration

All references to the original BNote codebase use the `BNOTE_ROOT` constant, defined in `bnote-next-generation/api/paths.php`:

```php
// Calculated path: bnote-next-generation/api/paths.php -> ../.. -> BNote/
define('BNOTE_ROOT', '/path/to/bnote/BNote');
```

**Benefits:**
- Single source of truth for BNote root path
- Easy to update if folder structure changes
- All modules use `BNOTE_ROOT` instead of relative paths
- Clear separation between bnote-next-generation and BNote

## Key Files

### Path Configuration
- `bnote-next-generation/api/paths.php` - Defines `BNOTE_ROOT` constant

### API Files Using BNOTE_ROOT
- `bnote-next-generation/api/index.php` - Main API router
- `bnote-next-generation/api/modules/rehearsals.php`
- `bnote-next-generation/api/modules/concerts.php`
- `bnote-next-generation/api/modules/auth.php`
- `bnote-next-generation/api/modules/contacts.php`
- `bnote-next-generation/api/modules/users.php`
- `bnote-next-generation/api/modules/dashboard.php`
- `bnote-next-generation/api/modules/participation.php`
- `bnote-next-generation/api/modules/translations.php`

### Files NOT Using BNOTE_ROOT
- `bnote-next-generation/api/modules/search.php` - Uses `bnote-next-generation/src/data/modules/searchdata.php` (internal)

## Separation Achieved

✅ **Complete Physical Separation**
- bnote-next-generation is a sibling folder to BNote
- No files shared between folders
- Clear visual separation in file system

✅ **Complete Code Separation**
- All Next app code in bnote-next-generation/
- Old codebase untouched in BNote/
- Path configuration ensures correct references

✅ **URL Separation**
- Clean URLs: `/bnote/bnote-next-generation/` instead of `/bnote/BNote/next/`
- Shorter, more intuitive paths

## Development Rules

**MOST IMPORTANT:** Never modify anything outside `/bnote-next-generation/` folder.

All references to old codebase use `BNOTE_ROOT` constant for consistency and maintainability.
