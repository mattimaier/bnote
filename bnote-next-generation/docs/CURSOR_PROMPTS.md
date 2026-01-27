# Cursor Prompt Sequence for BNote UI Modernization

## Setup Phase (One-time)

### Prompt 1: Add Tailwind CSS
```

Add Tailwind CSS to BNote:

1. Create /style/tailwind-modern.css with Tailwind 3.x CDN:
```html
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4/dist/tailwind.min.css" rel="stylesheet">
```

2. Update /src/presentation/head.php to include this stylesheet AFTER existing styles (for compatibility)
3. Test: Open browser, inspect element, verify Tailwind classes work

Do NOT remove existing stylesheets yet (we need fallback during transition).

```

---

## Widget Modernization (Repeat for Each Widget)

### Prompt Template: Modernize Widget
```

Modernize /src/presentation/widgets/[WIDGET_NAME].php:

Context:

- This widget is used across all 30+ BNote modules
- Keep ALL PHP logic and method signatures unchanged
- Only update HTML output and CSS classes

Tasks:

1. Analyze the current HTML output in this widget
2. Replace old CSS classes with Tailwind utility classes
3. Use these Tailwind patterns:
    - Forms: max-w-2xl mx-auto space-y-6 bg-white shadow-lg rounded-lg p-6
    - Inputs: w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500
    - Buttons: bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors
    - Tables: min-w-full divide-y divide-gray-200
4. Make it mobile-responsive (use sm:, md:, lg: breakpoints)
5. Keep German text unchanged
6. Preserve all PHP variables and logic

Show me the updated code.

```

### Specific Widget Prompts:

**form.php:**
```

Modernize /src/presentation/widgets/form.php:

Focus on:

- Form container: Clean white bg, shadow, rounded corners
- Input fields: Full width, proper padding, focus states
- Buttons: Primary blue, clear hover states
- Form sections: Proper spacing with space-y-4
- Responsive: Stack on mobile, side-by-side on desktop
- Keep all existing form submission logic

Show me the changes to the render() methods.

```

**table.php:**
```

Modernize /src/presentation/widgets/table.php:

Focus on:

- Table styling: Clean borders, hover rows
- Header: Light gray background, bold text
- Rows: Alternating subtle backgrounds
- Responsive: Horizontal scroll on mobile
- Actions column: Right-aligned, icon buttons
- Keep all existing data binding logic

Show me the table HTML generation code.

```

**box.php:**
```

Modernize /src/presentation/widgets/box.php:

Focus on:

- Card-style containers: white bg, shadow, rounded
- Header: Bold title, optional icon
- Content area: Proper padding
- Footer: Subtle top border if exists
- Keep all existing content injection logic

Show me the box rendering methods.

```

---

## Module Modernization (After Widgets)

### Prompt Template: Modernize Module View
```

Modernize /src/presentation/modules/[MODULE]view.php:

Context:

- This module uses the already-modernized widgets
- Focus on page layout and module-specific components
- Keep all controller interaction unchanged

Tasks:

1. Update page layout structure (header, main content, sidebar)
2. Replace old CSS classes with Tailwind
3. Ensure responsive design (mobile-first)
4. Use modernized widgets (Form, Table, Box classes)
5. Keep all PHP logic, data fetching, rights checks unchanged

Show me the updated view methods (start(), [action](), etc.).
