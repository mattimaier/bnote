# Entity Colors and Icons Configuration

## Overview
Create a centralized JSON configuration file for entity colors and icons that can be used across the application, replacing hardcoded values in multiple JavaScript files.

## Default Entity Configuration

Based on codebase analysis and global color scheme, here are the default colors and icons:

### Entities from entities.json:
- **rehearsal**: Blue (#3399FF - primary), `music` icon
- **concert**: Orange (oklch(0.68 0.20 80) - accent), `trumpet` icon
- **contact**: Purple (#A855F7), `user-circle` icon
- **location**: Orange (#F97316), `map-pin` icon
- **user**: Blue (#3B82F6), `user` icon

### Additional entities found:
- **song/repertoire**: Indigo (#6366F1), `music` icon
- **task**: Green (#25A65A - success), `check-square` icon
- **meeting**: Chart-3 (oklch(0.62 0.18 150)), `users` icon
- **appointment**: Chart-4 (oklch(0.58 0.22 20)), `calendar` icon
- **equipment**: Gray (#6B7280), `package` icon
- **tour**: Chart-5 (oklch(0.65 0.15 300)), `map` icon

## Implementation Plan

### 1. Create Entity Configuration File
**File**: `bnote-next-generation/config/entity-config.json`

Structure with defaults (no displayName - will be localized):

```json
{
  "entities": {
    "rehearsal": {
      "color": "#3399FF",
      "icon": "music"
    },
    "concert": {
      "color": "oklch(0.68 0.20 80)",
      "icon": "trumpet"
    },
    "contact": {
      "color": "#A855F7",
      "icon": "user-circle"
    },
    "location": {
      "color": "#F97316",
      "icon": "map-pin"
    },
    "user": {
      "color": "#3B82F6",
      "icon": "user"
    },
    "song": {
      "color": "#6366F1",
      "icon": "music"
    },
    "repertoire": {
      "color": "#6366F1",
      "icon": "music"
    },
    "task": {
      "color": "#25A65A",
      "icon": "check-square"
    },
    "meeting": {
      "color": "oklch(0.62 0.18 150)",
      "icon": "users"
    },
    "appointment": {
      "color": "oklch(0.58 0.22 20)",
      "icon": "calendar"
    },
    "equipment": {
      "color": "#6B7280",
      "icon": "package"
    },
    "tour": {
      "color": "oklch(0.65 0.15 300)",
      "icon": "map"
    }
  }
}
```

### 2. Create Configuration Loader Utility
**File**: `bnote-next-generation/assets/js/entity-config.js`

Features:
- Load and cache JSON config
- Provide helper functions: `getEntityConfig(entityType)`
- Generate Tailwind-compatible classes for colors
- Support CSS variable references
- Generate light/muted variants automatically using color-mix

### 3. Update Existing Files
Replace hardcoded configs in:
- `assets/js/search-results.js` - `getListItemTypeConfig()` method
- `assets/js/event-renderer.js` - `getEventTypeConfig()` method  
- `assets/js/dashboard.js` - `getEventTypeConfig()` method
- `assets/js/event-detail.js` - Event type config

### 4. Integration
- Add `entity-config.js` to `app.html` script loading (before other modules)
- Ensure config loads synchronously or provide loading state
- Support both hex and oklch color formats
- Auto-generate light/muted variants for badges and backgrounds

## Files to Create/Modify

### New Files
- `bnote-next-generation/config/entity-config.json` - Main configuration
- `bnote-next-generation/assets/js/entity-config.js` - Configuration loader utility

### Modified Files
- `bnote-next-generation/assets/js/search-results.js`
- `bnote-next-generation/assets/js/event-renderer.js`
- `bnote-next-generation/assets/js/dashboard.js`
- `bnote-next-generation/assets/js/event-detail.js`
- `bnote-next-generation/app.html` - Add config loader script
