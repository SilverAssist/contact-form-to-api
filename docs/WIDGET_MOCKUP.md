# Dashboard Widget Visual Mockup

## Widget Appearance

The CF7 API Status widget appears on the WordPress dashboard with the following layout:

```
┌─────────────────────────────────────────────────────────────────┐
│  CF7 API Status                                          [hide] │
├─────────────────────────────────────────────────────────────────┤
│  Last 24 Hours                                                  │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                     │
│  │    42    │  │  95.2%   │  │  245 ms  │                     │
│  │ Requests │  │ Success  │  │   Avg    │                     │
│  │          │  │  Rate    │  │ Response │                     │
│  └──────────┘  └──────────┘  └──────────┘                     │
│                                                                 │
│  Recent Errors (2)                                              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ⚠ 2 errors require attention                            │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  • Contact Form: 500 Server Error...        5 mins ago [View] │
│  • Newsletter: Timeout after 30 seconds...   1 hour ago [View] │
│                                                                 │
│  [View All Logs]  [Settings]                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Success Rate Color Coding

The Success Rate card changes color based on the percentage:

### High Success (90%+)
```
┌──────────┐
│  98.5%   │ ← Green background (#e7f5ec)
│ Success  │   Green text (#00a32a)
│  Rate    │   Green border (#68de7c)
└──────────┘
```

### Medium Success (70-89%)
```
┌──────────┐
│  82.3%   │ ← Yellow background (#fcf9e8)
│ Success  │   Orange text (#996800)
│  Rate    │   Yellow border (#f0c930)
└──────────┘
```

### Low Success (<70%)
```
┌──────────┐
│  45.2%   │ ← Red background (#fcf0f1)
│ Success  │   Red text (#d63638)
│  Rate    │   Red border (#f86368)
└──────────┘
```

## No Errors State

When there are no errors in the last 24 hours:

```
┌─────────────────────────────────────────────────────────────────┐
│  Recent Errors (0)                                              │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ No errors in the last 24 hours ✓                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                    (Green background)                           │
└─────────────────────────────────────────────────────────────────┘
```

## Mobile Responsive View

On mobile devices (< 782px width), the widget stacks vertically:

```
┌─────────────────────┐
│  CF7 API Status     │
├─────────────────────┤
│  Last 24 Hours      │
│                     │
│  ┌─────────────┐    │
│  │     42      │    │
│  │  Requests   │    │
│  └─────────────┘    │
│                     │
│  ┌─────────────┐    │
│  │   95.2%     │    │
│  │  Success    │    │
│  │    Rate     │    │
│  └─────────────┘    │
│                     │
│  ┌─────────────┐    │
│  │   245 ms    │    │
│  │    Avg      │    │
│  │  Response   │    │
│  └─────────────┘    │
│                     │
│  Recent Errors (2)  │
│  • Contact Form...  │
│    [View]           │
│  • Newsletter...    │
│    [View]           │
│                     │
│  [View All Logs]    │
│  [Settings]         │
└─────────────────────┘
```

## Dark Mode

In dark mode (WordPress 5.7+ with auto color scheme):

```
┌─────────────────────────────────────────────────────────────────┐
│  CF7 API Status                                    (Dark theme) │
├─────────────────────────────────────────────────────────────────┤
│  Background: #1e1e1e                                           │
│  Text: #f0f0f1                                                 │
│  Borders: #3c3c3c                                              │
│                                                                 │
│  Cards maintain semantic colors but adjusted for dark:         │
│  - Success: Green tones                                        │
│  - Medium: Yellow/Orange tones                                 │
│  - Low: Red tones                                              │
│  - Errors: Dark red background                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Interactive Elements

### Hover Effects

All stat cards have hover effects:
- Slight upward translation (2px)
- Subtle shadow
- Smooth transition (0.2s)

### Links

- Error "View" links → cf7-api-logs page with log_id parameter
- "View All Logs" button → cf7-api-logs page
- "Settings" button → Contact Form 7 forms page (wpcf7)

### Screen Options

The widget can be hidden using WordPress Screen Options:
1. Click "Screen Options" at top of dashboard
2. Uncheck "CF7 API Status"
3. Widget is hidden but preference saved

## WordPress Standards Compliance

### Widget Position
- Column: "normal" (main column, not side)
- Priority: "high" (appears near top)
- Can be reordered by dragging (WordPress default)

### Capability
- Visible only to users with `manage_options` capability
- Typically Administrators and Super Admins
- Editors and below do not see the widget

### Accessibility
- Semantic HTML5 structure
- ARIA-compliant (WordPress handles widget ARIA)
- Keyboard navigable
- Screen reader friendly
- Color contrast meeting WCAG AA standards

## CSS Grid Layout

The three stat cards use CSS Grid:

```css
.cf7-widget-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
```

On mobile:
```css
@media screen and (max-width: 782px) {
    .cf7-widget-stats {
        grid-template-columns: 1fr;
    }
}
```

## Typography

- Card values: 28px, bold (700)
- Card labels: 12px, uppercase, letter-spacing: 0.5px
- Section headers: 13px, semi-bold (600)
- Error items: 13px, normal
- Error time: 12px, gray

## Spacing

- Widget padding: None (WordPress handles)
- Section margins: 20px vertical
- Card padding: 15px
- Card gap: 12px
- Error item padding: 10px 12px
- Error item margin: 8px bottom

## Performance

### Load Time
- CSS: ~3.8 KB (minified)
- No JavaScript required
- Database queries: 4 total
  - Count total requests
  - Count success requests
  - Calculate success rate
  - Get recent errors (LIMIT 5)

### Caching
- Widget content generated on page load
- No AJAX or real-time updates
- Future: Could add transient caching (5-15 minutes)

### Database Impact
- All queries use indexes (created_at column)
- Time window limited to 24 hours
- Error list limited to 5 items
- Minimal impact on dashboard load time

## Browser Support

Tested and working on:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari
- Chrome Mobile

Fallbacks for older browsers:
- CSS Grid falls back to block layout
- Flexbox for action buttons (widely supported)
- Standard colors if dark mode not supported

## Internationalization

All text strings are translatable:
- Widget title: "CF7 API Status"
- Time labels: "Last 24 Hours", "ago"
- Stats labels: "Requests", "Success Rate", "Avg Response Time"
- Error labels: "Recent Errors", "errors require attention"
- Actions: "View All Logs", "Settings", "View"
- Messages: "No errors in the last 24 hours", "Unknown Form", "Unknown error"

Translation files can be generated from:
```bash
wp i18n make-pot . languages/contact-form-to-api.pot
```
