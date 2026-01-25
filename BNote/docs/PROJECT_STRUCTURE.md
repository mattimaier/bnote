# BNote Project Structure - UI Modernization Phase

## Directory Overview

```

BNote/
├── src/
│   ├── data/           ← DON'T TOUCH (Phase 1)
│   ├── logic/          ← DON'T TOUCH (Phase 1)
│   └── presentation/   ← MODIFY THIS
│       ├── widgets/    ← START HERE (highest priority)
│       └── modules/    ← THEN THESE
├── style/              ← Add tailwind.css here
├── main.php            ← Entry point (leave alone)
└── .cursorrules        ← Your AI guide

```

## Widget Files (Modernize First)

Located in `/src/presentation/widgets/`

### High Priority (Do First):
1. **form.php** - Form rendering (used everywhere)
2. **table.php** - Data tables (used in most modules)
3. **box.php** - Container boxes/cards
4. **field.php** - Form fields
5. **writing.php** - Text editor/display

### Medium Priority:
6. **dataview.php** - Data display widget
7. **error.php** - Error messages
8. **message.php** - Success/info messages
9. **dropdown.php** - Dropdown menus
10. **filterbox.php** - Filter controls

### Lower Priority:
11. All remaining widgets (chat, filebrowser, etc.)

## Module View Files (After Widgets)

Located in `/src/presentation/modules/`

### Phase 1A (Core):
1. **loginview.php** - Login page
2. **startview.php** - Dashboard
3. **userview.php** - User profile

### Phase 1B (High-traffic):
4. **probenview.php** - Rehearsals
5. **konzerteview.php** - Concerts
6. **calendarview.php** - Calendar
7. **mitspieler** - Members

### Phase 1C (Communication):
8. **kommunikationview.php** - Messaging
9. **nachrichtenview.php** - News
10. **shareview.php** - File sharing

### Phase 1D (Remaining 20+ modules):
- Do these last, one by one

## Testing Files

After each widget/module modernization:
1. Open browser to BNote
2. Navigate to affected module
3. Check layout (desktop + mobile)
4. Verify functionality unchanged

## Backup Strategy

Before starting:
```bash
git init
git add .
git commit -m "Backup before UI modernization"
git tag before-ui-modernization
```

After each widget:

```bash
git add src/presentation/widgets/form.php
git commit -m "Modernized form.php widget with Tailwind"
```


## Rollback if Needed

```bash
git checkout before-ui-modernization
```


## Progress Tracking

Create `/BNote/docs/UI_PROGRESS.md`:

```markdown
# UI Modernization Progress

## Widgets
- [ ] form.php
- [ ] table.php
- [ ] box.php
- [ ] field.php
- [ ] writing.php
- [ ] dataview.php
- [ ] error.php
- [ ] message.php
- [ ] dropdown.php
- [ ] filterbox.php

## Modules
- [ ] loginview.php
- [ ] startview.php
- [ ] userview.php
- [ ] probenview.php
- [ ] konzerteview.php
...
```

Check off as you complete each file.
