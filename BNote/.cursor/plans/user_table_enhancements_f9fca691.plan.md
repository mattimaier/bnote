---
name: User Table Enhancements
overview: Enhance the user management table with search, improved sorting, separated name columns, actions menu, better button styling, and reusable status badges.
todos:
  - id: badge-component
    content: Create reusable badge component (next/assets/js/badge.js) with render(text, color) method for arbitrary badges and renderStatus convenience method, using dashboard event-badge styling
    status: completed
  - id: api-name-fields
    content: Update API (api/modules/users.php) to return firstName and lastName separately instead of combined name
    status: completed
  - id: table-search
    content: Add search functionality to table component (next/assets/js/table.js) with search input and filtering across all columns
    status: completed
  - id: table-date-sort
    content: Improve date sorting in table component (next/assets/js/table.js) with proper date parsing and comparison
    status: completed
  - id: table-actions-menu
    content: Replace action buttons with 3-dots menu/popover in table component (next/assets/js/table.js)
    status: completed
  - id: users-columns
    content: Update users table columns (next/assets/js/users.js) to show firstName and lastName separately, use Badge component for status
    status: completed
  - id: users-search-ui
    content: Add search input to users page (next/users.html) above the table
    status: completed
  - id: button-styling
    content: Update Add User button styling (next/users.html) to match other buttons in the app
    status: completed
isProject: false
---

# User Table Enhancements Plan

## Overview

Enhance the user management table with search functionality, proper date sorting, separated first/last name columns, actions menu with 3-dots button, improved button styling, and reusable status badge component.

## Current State Analysis

### Table Component (`next/assets/js/table.js`)

- Basic sorting implemented (but date sorting needs improvement)
- No default sorting configured (should default to ID column ascending)
- No search functionality
- Actions shown as individual buttons
- No reusable badge component

### Users Module (`next/assets/js/users.js`)

- Table shows combined "name" field
- Status uses inline HTML badges (not reusable)
- Actions: Edit, Delete, Activate, Privileges shown as separate buttons

### API Response (`api/modules/users.php`)

- Returns `name` as concatenated string: `CONCAT_WS(' ', c.name, c.surname)`
- Needs to return `firstName` and `lastName` separately

### Badge Styling (`next/dashboard.html`)

- `.event-badge` class with CSS custom properties
- Uses `color-mix` for borders and backgrounds
- Supports variants: default (primary), `.accent`, `.chart-3`

## Implementation Plan

### 1. Create Reusable Badge Component (`next/assets/js/badge.js`)

Create a utility module for rendering badges with arbitrary text and colors:

**Features:**

- `Badge.render(text, color, variant)` - Returns HTML for badge with custom text and color
- `Badge.renderStatus(isActive)` - Convenience method for active/inactive status
- Supports custom colors via CSS variables or color names
- Supports variants: 'primary', 'accent', 'destructive', 'success', 'warning', 'info', or custom
- Reuses `.event-badge` styling from dashboard
- Flexible for any badge use case

**API:**

```javascript
Badge.render('Active', 'success')  // Green badge with "Active" text
Badge.render('Inactive', 'destructive')  // Red badge with "Inactive" text
Badge.render('Custom', 'primary')  // Primary color badge with "Custom" text
Badge.renderStatus(true)  // Convenience: green "Active" badge
Badge.renderStatus(false) // Convenience: red "Inactive" badge
Badge.render('Pending', 'warning') // Yellow/orange badge
```

**Color Mapping:**

- `'success'` or `'active'` → Green (accent color)
- `'destructive'` or `'inactive'` → Red (destructive color)
- `'primary'` → Primary color
- `'accent'` → Accent color
- `'warning'` → Chart-3 color (yellow/orange)
- Custom color names map to CSS variables

### 2. Update API to Return Separate Name Fields (`api/modules/users.php`)

Modify `listUsers()` method:

**Changes:**

- Update query to select `c.name as firstName, c.surname as lastName` separately
- Return both fields in API response
- Keep `name` for backward compatibility (concatenated)

**Code location:** `api/modules/users.php` line 62-79

### 3. Add Search Functionality to Table Component (`next/assets/js/table.js`)

Enhance table with search:

**Features:**

- Add search input above table
- Filter across all visible columns
- Real-time filtering as user types
- Clear search button
- Search state management

**Implementation:**

- Add `searchable: true/false` option to table config (default: true)
- Add `searchInput` container option (ID of element to render search input)
- Add `searchTerm` state to Table class
- Filter data before sorting and rendering
- Search across all column values (stringified, case-insensitive)
- Update `sortData()` to work with filtered data
- Show result count when search is active

### 4. Improve Date Sorting (`next/assets/js/table.js`)

Fix date column sorting:

**Changes:**

- Detect date columns automatically or via column config
- Parse dates properly for comparison
- Handle null/empty dates (sort to end)
- Support ISO date strings and various formats

**Implementation:**

- Add `type: 'date'` to column config for date columns
- Custom sort function for dates
- Parse dates using `new Date()` and compare timestamps

### 5. Replace Actions with 3-Dots Menu (`next/assets/js/table.js`)

Implement popover menu for actions:

**Features:**

- Single 3-dots button (`more-vertical` icon) per row
- Click opens popover menu below button
- Menu items: Edit, Delete, Activate/Deactivate, Privileges
- Menu closes on outside click or item selection
- Positioned relative to button (avoid overflow)

**Implementation:**

- Replace action buttons with single menu button
- Create popover component (absolute positioned div)
- Handle click outside to close
- Use Lucide `more-vertical` icon

### 6. Update Users Table Columns (`next/assets/js/users.js`)

Modify column definitions:

**Changes:**

- Replace single "Name" column with "First Name" and "Last Name"
- Update status column to use `Badge.renderStatus()`
- Add `type: 'date'` to lastlogin column for proper sorting
- Update data mapping to use `firstName` and `lastName`

### 7. Improve Add User Button Styling (`next/users.html`)

Update button to match app style:

**Current:** `px-4 py-2 bg-primary text-primary-foreground`

**Target:** Match other action buttons in app (check dashboard for reference)

**Changes:**

- Ensure consistent padding, border radius, hover effects
- Match icon size and spacing
- Use same transition effects

### 8. Add Search Input to Users Page (`next/users.html`)

Add search bar above table:

**Location:** Between action buttons and table container

**Style:** Match search input in header (muted background, border, focus ring)

## File Changes

### New Files

- `next/assets/js/badge.js` - Reusable badge component

### Modified Files

- `api/modules/users.php` - Return firstName/lastName separately
- `next/assets/js/table.js` - Add search, improve sorting, actions menu
- `next/assets/js/users.js` - Update columns, use Badge component
- `next/users.html` - Add search input, update button styling
- `next/dashboard.html` - Extract badge CSS to be reusable (or keep in users.html)

## Implementation Details

### Badge Component Structure

```javascript
const Badge = {
    render(text, color = 'primary', variant = null) {
        // Returns HTML string for badge with custom text and color
        // text: Display text
        // color: Color name ('success', 'destructive', 'primary', 'accent', 'warning', etc.)
        // variant: Optional variant class (overrides color)
        // Maps colors to CSS variables and event-badge classes
    },
    
    renderStatus(isActive) {
        // Convenience method for status badges
        // Returns Badge.render('Active', 'success') or Badge.render('Inactive', 'destructive')
    }
};
```

### Search Implementation

- Add `searchTerm` state to Table class
- Add `filterData()` method that filters before sorting
- Filter on `searchTerm` change
- Search across all column values (stringified)
- Case-insensitive matching
- Apply filter before sorting in `render()` method
- Debounce input for performance (optional)

### Default Sorting

- Table should default to sorting by ID column (ascending)
- Set `defaultSort: 'id'` and `defaultSortDirection: 'asc'` in constructor
- Initialize `sortColumn` and `sortDirection` from options
- If no default sort specified, use ID as fallback

### Date Sorting

- Detect date format (ISO, locale, etc.)
- Parse to Date objects
- Compare timestamps
- Handle invalid dates gracefully

### Actions Menu

- Create `renderActionsMenu(rowId, row)` method
- Position popover using `getBoundingClientRect()`
- Handle z-index and overflow
- Close on escape key
- Close on outside click

### Name Separation

- Update SQL query in `UserData->getUsers()`
- Or parse in API module from existing `name` field
- Return both `firstName` and `lastName` in API response
- Update frontend to use separate fields

## UI/UX Considerations

**Search:**

- Place search above table, full width
- Show result count: "X users found"
- Clear button (X icon) when search active
- Placeholder: "Search users..."

**Actions Menu:**

- 3-dots button aligned right in actions column
- Menu appears below button
- Menu items with icons and labels
- Hover effects on menu items
- Menu closes after action selection

**Status Badges:**

- Flexible badge component that accepts arbitrary text and color
- Green for active (using 'success' color, matches dashboard accent)
- Red for inactive (using 'destructive' color)
- Same styling as event badges on dashboard (`.event-badge` class)
- Consistent sizing and spacing
- Can be used for any badge needs (status, tags, labels, etc.)

**Button Styling:**

- Match existing button patterns
- Consistent hover states
- Proper icon alignment
- Responsive sizing

## Testing Considerations

- Test search across all columns
- Test default sorting by ID (ascending)
- Test date sorting (ascending/descending)
- Test name separation (users with/without contacts)
- Test actions menu (open/close, all actions)
- Test badge rendering (active/inactive states, custom badges)
- Test on mobile/tablet (menu positioning)