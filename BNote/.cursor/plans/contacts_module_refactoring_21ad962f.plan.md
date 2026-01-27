---
name: Contacts Module Refactoring
overview: Refactor the contacts module (main.php?mod=3) to modern UI using reusable Form and Table components. Add to sidebar, implement powerful filtering, group switching, and modernize all functionality including integration, groups management, printing, vcard, and privacy features.
todos: []
isProject: false
---

# Contacts Module Refactoring Plan

## Overview

Refactor the contacts module (`main.php?mod=3`) to use the modern "next" app architecture. Reuse existing Form and Table components, add powerful filtering capabilities, implement group switching UI, and modernize all functionality while maintaining feature parity with the legacy module.

## Current State Analysis

### Legacy Module Structure

**Files:**

- `src/presentation/modules/kontakteview.php` - Main view (826 lines)
- `src/logic/modules/kontaktecontroller.php` - Controller (269 lines)
- `src/data/modules/kontaktedata.php` - Data access (481 lines)
- `src/presentation/modules/gruppenview.php` - Groups submodule (102 lines)
- `src/data/modules/gruppendata.php` - Groups data access

**Key Features:**

1. **Contact List** (`start()`):

   - Group tabs for filtering (Members, Admins, custom groups, "All")
   - Table with: Name, Instrument, Address, Phone, Email/Web
   - Uses jQuery DataTables for search/sort
   - Default shows Members group (group=2)

2. **Integration Mode** (`integration()`):

   - Bulk selection interface using `GroupSelector` widget
   - Select members (with group filter)
   - Select rehearsals, rehearsal phases, concerts, votes
   - Creates relations: `rehearsal_contact`, `rehearsalphase_contact`, `concert_contact`, `vote_group`
   - Poor UX: checkbox lists in boxes

3. **Groups Management** (`groups` submodule):

   - CRUD for contact groups
   - Table view with edit/delete
   - View group members

4. **Printing** (`selectPrintGroups()`):

   - Select groups and custom fields to print
   - Generates printable table per group
   - Uses `GroupSelector` for selection

5. **VCard Import/Export**:

   - Import: File upload + group selection
   - Export: Direct link to `export/kontakte.vcd` (vCard format)

6. **GDPR/Privacy** (`gdprOk()`):

   - Table showing GDPR consent status
   - Generate codes, send emails, delete non-consenting contacts

**Data Structure:**

- Contact fields: id, surname, name, nickname, company, phone, mobile, business, email, web, notes, address (reference), instrument (reference), is_conductor, birthday, status, share_* flags
- Custom fields support (dynamic)
- Group membership via `contact_group` table
- Address joined from `address` table

**Current UI Issues:**

- Old jQuery DataTables (not modern)
- GroupSelector uses basic checkboxes in boxes (poor UX)
- No advanced filtering (only group tabs)
- Integration mode has confusing layout
- No responsive design

## Implementation Plan

### 1. Create Contacts API Module (`api/modules/contacts.php`)

**Structure (mirroring UsersModule):**

```php
class ContactsModule {
    private $data;
    
    public function __construct() {
        if (!Auth::checkModule('Contact')) {
            Response::error('Access denied', 403);
        }
        $this->data = new KontakteData();
    }
    
    public function handle() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
        switch ($action) {
            case 'list': return $this->listContacts();
            case 'get': return $this->getContact();
            case 'create': return $this->createContact();
            case 'update': return $this->updateContact();
            case 'delete': return $this->deleteContact();
            case 'getGroups': return $this->getGroups();
            case 'getGroupContacts': return $this->getGroupContacts();
            // Integration
            case 'getMembers': return $this->getMembers();
            case 'getRehearsals': return $this->getRehearsals();
            case 'getPhases': return $this->getPhases();
            case 'getConcerts': return $this->getConcerts();
            case 'getVotes': return $this->getVotes();
            case 'integrate': return $this->integrate();
            // Groups submodule
            case 'listGroups': return $this->listGroups();
            case 'getGroup': return $this->getGroup();
            case 'createGroup': return $this->createGroup();
            case 'updateGroup': return $this->updateGroup();
            case 'deleteGroup': return $this->deleteGroup();
            case 'getGroupMembers': return $this->getGroupMembers();
            // Printing
            case 'getPrintData': return $this->getPrintData();
            // VCard
            case 'importVCard': return $this->importVCard();
            // GDPR
            case 'getGdprStatus': return $this->getGdprStatus();
            case 'generateGdprCodes': return $this->generateGdprCodes();
            case 'sendGdprMail': return $this->sendGdprMail();
            case 'deleteGdprNok': return $this->deleteGdprNok();
        }
    }
}
```

**Key Methods:**

- `listContacts($groupId = null)`: Return contacts, optionally filtered by group
- `getContact($id)`: Single contact with joined address/instrument
- `createContact()`, `updateContact()`, `deleteContact()`: CRUD operations
- `getGroups()`: All active groups
- `getGroupContacts($groupId)`: Contacts in specific group
- Integration methods return data for selection (members, rehearsals, etc.)
- `integrate()`: Process bulk integration (create relations)

### 2. Create Contacts Frontend Module (`next/assets/js/contacts.js`)

**Structure:**

```javascript
const Contacts = {
    // State
    contacts: [],
    groups: [],
    selectedGroup: null,
    filters: {},
    table: null,
    
    // Modes
    currentMode: 'list', // 'list', 'integration', 'groups', 'print', 'vcard', 'privacy'
    
    async init(session) {
        // Check permissions
        // Load groups
        // Load contacts
        // Initialize UI
    },
    
    // Main list view
    renderContactList() { },
    renderGroupTabs() { },
    applyFilters() { },
    
    // Integration mode
    showIntegration() { },
    renderIntegrationForm() { },
    
    // Groups management
    showGroups() { },
    renderGroupsTable() { },
    
    // Other modes...
}
```

### 3. Create Contacts Page (`next/contacts.html`)

**Structure:**

- Shared sidebar (like users.html)
- Top action bar with mode buttons:
  - Integration
  - Groups
  - Print
  - VCard Import
  - VCard Export
  - Privacy
- Group tabs/switcher
- Filter bar (advanced filtering)
- Main content area (table or form based on mode)
- Modals for forms

### 4. Add to Sidebar

**Update `next/users.html` and `next/dashboard.html`:**

- Add "Contacts" menu item with permission check
- Update `sidebar.js` to check Contact module permission
- Add `data-page="contacts"` for highlighting

### 5. Group Switching UI

**Implementation:**

- Tab-style switcher above table (replacing old nav-tabs)
- Modern tabs using Tailwind CSS
- Active group highlighted
- "All" option to show all contacts
- Group selection persists in URL/state

**Design:**

- Horizontal tabs with active state
- Smooth transitions
- Responsive (scrollable on mobile)

### 6. Advanced Filtering System

**Filter Components:**

- Text search (name, email, phone, etc.)
- Group filter (dropdown, multi-select)
- Instrument filter (dropdown)
- Status filter (dropdown)
- Custom field filters (dynamic based on custom fields)
- Address filters (city, zip)
- Date filters (birthday range)

**Filter UI:**

- Collapsible filter bar above table
- Filter chips/badges showing active filters
- Clear all filters button
- Filter combinations (AND logic)
- Real-time filtering as user types

**Implementation:**

- Extend Table component with filter support
- Or create separate FilterBar component
- Filter state management
- URL query params for shareable filtered views

### 7. Integration Mode (Einpassung)

**Modern UX Improvements:**

**Current:** Checkbox lists in boxes (poor UX)

**New Design:**

- Multi-column layout with searchable dropdowns
- Each section (Members, Rehearsals, etc.) has:
  - Searchable dropdown with typeahead
  - Selected items shown as chips/badges
  - Quick select buttons (Select All, Clear)
  - Count of selected items
- Visual feedback for selections
- Preview of what will be created

**Implementation:**

- Use Form component with custom field types
- Create `multi-select-dropdown` field type
- Or use privilege-checkbox style but with better UX
- Searchable dropdowns for each entity type
- Selected items displayed as removable chips

**Form Structure:**

```javascript
{
    key: 'members',
    label: 'Members',
    type: 'multi-select-searchable',
    options: members.map(m => ({
        value: m.id,
        label: `${m.name} ${m.surname}`,
        searchText: `${m.name} ${m.surname} ${m.email}`
    }))
}
```

### 8. Groups Management

**Features:**

- Table of groups (reuse Table component)
- Add/Edit/Delete groups
- View group members
- Group form using Form component

**Implementation:**

- Groups table with actions (3-dots menu)
- Group form modal (name, description, is_active)
- Group members view (sub-table or modal)

### 9. Printing Functionality

**Bridge Old Code:**

- Keep PHP printing logic
- Modern selection UI using Form component
- Select groups and custom fields
- Submit to API endpoint that calls legacy `printMembers()`
- Return printable HTML or trigger print dialog

**Alternative:** Generate print view in frontend using selected data

### 10. VCard Import/Export

**Import:**

- File upload using Form component
- Group selection (privilege-checkbox style)
- Submit to API
- Show import results

**Export:**

- Button triggers download
- Link to existing `export/kontakte.vcd` endpoint
- Or generate via API

### 11. Privacy/GDPR Management

**Features:**

- Table showing GDPR consent status
- Actions: Generate Codes, Send Mail, Delete Non-Consenting
- Buttons from old code, modernized

**Implementation:**

- GDPR status table (reuse Table component)
- Action buttons in top bar
- Modals for confirmations
- Bridge to legacy PHP methods

## File Changes

### New Files

- `api/modules/contacts.php` - Contacts API module
- `next/assets/js/contacts.js` - Contacts frontend module
- `next/contacts.html` - Contacts page
- `next/assets/js/contacts-api.js` - API client (or extend api.js)

### Modified Files

- `next/assets/js/api.js` - Add ContactsApi object
- `next/assets/js/sidebar.js` - Add contacts permission check
- `next/users.html` - Add contacts menu item (or create shared template)
- `next/dashboard.html` - Add contacts menu item
- `next/assets/js/form.js` - Add multi-select-searchable field type (optional)
- `next/assets/js/table.js` - Enhance filtering capabilities

## Implementation Details

### API Endpoints

**Contacts:**

- `GET /api?module=contacts&action=list&group=2` - List contacts (optional group filter)
- `GET /api?module=contacts&action=get&id=1` - Get single contact
- `POST /api?module=contacts&action=create` - Create contact
- `POST /api?module=contacts&action=update&id=1` - Update contact
- `POST /api?module=contacts&action=delete&id=1` - Delete contact
- `GET /api?module=contacts&action=getGroups` - Get all groups
- `GET /api?module=contacts&action=getGroupContacts&group=2` - Get contacts in group

**Integration:**

- `GET /api?module=contacts&action=getMembers&group=2` - Get members for selection
- `GET /api?module=contacts&action=getRehearsals` - Get future rehearsals
- `GET /api?module=contacts&action=getPhases` - Get rehearsal phases
- `GET /api?module=contacts&action=getConcerts` - Get future concerts
- `GET /api?module=contacts&action=getVotes` - Get active votes
- `POST /api?module=contacts&action=integrate` - Process integration

**Groups:**

- `GET /api?module=contacts&action=listGroups` - List all groups
- `GET /api?module=contacts&action=getGroup&id=1` - Get single group
- `POST /api?module=contacts&action=createGroup` - Create group
- `POST /api?module=contacts&action=updateGroup&id=1` - Update group
- `POST /api?module=contacts&action=deleteGroup&id=1` - Delete group
- `GET /api?module=contacts&action=getGroupMembers&id=1` - Get group members

**Other:**

- `GET /api?module=contacts&action=getPrintData` - Get print data
- `POST /api?module=contacts&action=importVCard` - Import vCard
- `GET /api?module=contacts&action=getGdprStatus` - Get GDPR status
- `POST /api?module=contacts&action=generateGdprCodes` - Generate codes
- `POST /api?module=contacts&action=sendGdprMail` - Send GDPR emails
- `POST /api?module=contacts&action=deleteGdprNok` - Delete non-consenting

### Contact Table Columns

```javascript
[
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'First Name', sortable: true },
    { key: 'surname', label: 'Last Name', sortable: true },
    { key: 'nickname', label: 'Nickname', sortable: true },
    { key: 'instrumentname', label: 'Instrument', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'phone', label: 'Phone', sortable: true },
    { key: 'mobile', label: 'Mobile', sortable: true },
    { key: 'city', label: 'City', sortable: true },
    { key: 'status', label: 'Status', sortable: true, render: (value) => Badge.render(value, 'primary') }
]
```

### Filter Implementation

**Filter Bar Component:**

- Collapsible section
- Filter inputs (text, select, date range)
- Active filter chips
- Clear button

**Filter State:**

```javascript
{
    search: '',
    group: null,
    instrument: null,
    status: null,
    city: '',
    hasEmail: null,
    customFields: {}
}
```

### Integration Mode UX

**Layout:**

```
┌─────────────────────────────────────────┐
│ Members (5 selected)                    │
│ [Search members...]                    │
│ Selected: [John Doe ×] [Jane Smith ×]  │
│ [Select All] [Clear]                    │
├─────────────────────────────────────────┤
│ Rehearsals (3 selected)                │
│ [Search rehearsals...]                  │
│ Selected: [2024-01-15 ×] [2024-01-22 ×]│
│ [Select All] [Clear]                    │
├─────────────────────────────────────────┤
│ ... (Phases, Concerts, Votes)           │
└─────────────────────────────────────────┘
[Integrate] [Cancel]
```

**Multi-Select Component:**

- Searchable dropdown (typeahead)
- Selected items as chips
- Quick actions (Select All, Clear)
- Visual count

### Groups Management

**Groups Table:**

- Columns: ID, Name, Description, Active, Member Count
- Actions: View Members, Edit, Delete
- Add Group button

**Group Form:**

- Name (required)
- Description (optional)
- Active (checkbox)
- Using Form component

### Printing

**Selection Form:**

- Group selection (privilege-checkbox style)
- Custom field selection (privilege-checkbox style)
- Submit button

**Print View:**

- Bridge to PHP `printMembers()` method
- Or generate print view in frontend
- Print-optimized CSS
- Per-group tables

### VCard

**Import:**

- File upload field (Form component)
- Group selection
- Submit to API
- Success/error feedback

**Export:**

- Button/link to export endpoint
- Or API endpoint that generates vCard

### Privacy/GDPR

**GDPR Table:**

- Columns: Name, Email, GDPR OK, Last Contact
- Actions: Generate Codes, Send Mail, Delete
- Status badges (OK/Not OK)

**Actions:**

- Generate Codes button → API call
- Send Mail button → API call
- Delete Non-Consenting button → Confirmation → API call

## UI/UX Considerations

**Group Tabs:**

- Modern tab design matching app style
- Active tab highlighted
- Smooth transitions
- Responsive (horizontal scroll on mobile)

**Filter Bar:**

- Collapsible (starts expanded)
- Clean, organized layout
- Filter chips for active filters
- Clear visual hierarchy

**Integration Mode:**

- Clear section headers
- Searchable dropdowns
- Selected items visible as chips
- Preview of integration result
- Progress indicator during processing

**Table:**

- Reuse existing Table component
- Inline editing for quick updates
- Row click opens edit modal
- Actions menu (3-dots) for each row

**Responsive Design:**

- Mobile-friendly filters
- Collapsible sections
- Touch-friendly controls
- Optimized table display

## Testing Considerations

- Test all CRUD operations
- Test group filtering
- Test advanced filters
- Test integration mode (all entity types)
- Test groups management
- Test printing (various group/field combinations)
- Test vCard import/export
- Test GDPR functionality
- Test permissions
- Test responsive layouts
- Test with large datasets

## Future Enhancements

- Bulk operations (bulk edit, bulk delete)
- Export to CSV/Excel
- Advanced search (full-text)
- Contact merge functionality
- Contact history/audit log
- Custom field management UI
- Contact templates
- Import from CSV
- Contact relationships/connections
- Contact notes/timeline