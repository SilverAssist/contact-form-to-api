# Dashboard Widget Feature

## Overview
This document describes the Dashboard Widget feature for Contact Form 7 to API plugin.

## Implementation

### New Files Created

1. **includes/Admin/DashboardWidget.php**
   - Main widget class implementing LoadableInterface
   - Singleton pattern for consistent instantiation
   - Registers with WordPress dashboard via `wp_dashboard_setup` hook
   - Collects statistics from RequestLogger
   - Delegates rendering to DashboardWidgetView

2. **includes/Admin/Views/DashboardWidgetView.php**
   - Static view class for HTML rendering
   - Renders statistics cards (requests, success rate, response time)
   - Displays recent errors with form names and timestamps
   - Provides action links to logs and settings pages
   - Includes helper methods for formatting (time_ago, truncate, etc.)

3. **assets/css/dashboard-widget.css**
   - Modern responsive styling for the widget
   - Color-coded success rate indicators (green/yellow/red)
   - Grid layout for statistics cards
   - Error list styling with timestamps
   - Dark mode support
   - Mobile-responsive design

4. **tests/Unit/RequestLoggerStatisticsTest.php**
   - Comprehensive unit tests for new statistics methods
   - Tests for count, success rate, average response time
   - Tests for recent errors retrieval
   - Edge case testing (no logs, filtering, limits)

### Modified Files

1. **includes/Core/RequestLogger.php**
   - Added `get_count_last_hours(int $hours, ?string $status = null): int`
     - Counts requests in a time window
     - Optionally filters by status (success, error, etc.)
   - Added `get_success_rate_last_hours(int $hours): float`
     - Calculates success rate percentage (0-100)
   - Added `get_avg_response_time_last_hours(int $hours): float`
     - Returns average response time in milliseconds
   - Added `get_recent_errors(int $limit = 5): array`
     - Retrieves most recent error logs
     - Ordered by created_at DESC

2. **includes/Admin/Loader.php**
   - Added DashboardWidget initialization
   - Widget is loaded only for users with manage_options capability

## Features

### Dashboard Widget Display

The widget displays on the WordPress dashboard (wp-admin/index.php) and includes:

1. **Quick Statistics (Last 24 Hours)**
   - Total Requests: Count of all API requests
   - Success Rate: Percentage with color coding
     - Green: 90%+ success rate
     - Yellow: 70-89% success rate
     - Red: Below 70% success rate
   - Average Response Time: In milliseconds

2. **Recent Errors Section**
   - Lists up to 5 most recent errors
   - Shows form name, error message (truncated), and time ago
   - Link to view full log details
   - Alert message if errors present
   - Success message if no errors

3. **Action Links**
   - "View All Logs": Links to cf7-api-logs page
   - "Settings": Links to Contact Form 7 forms page

### Capability Check
- Widget only visible to users with `manage_options` capability
- Automatically hidden via `should_load()` method

### Widget Configuration
- Registered at "normal" priority with "high" placement
- Can be hidden via WordPress Screen Options
- Appears in the default dashboard column

## Architecture Decisions

### Singleton Pattern
Both DashboardWidget and RequestLogger follow the singleton pattern for consistency with other plugin components.

### View Separation
HTML rendering is separated into DashboardWidgetView following MVC pattern used throughout the plugin.

### Time-Based Queries
All statistics use MySQL DATE_SUB with INTERVAL to ensure database-level performance and accuracy.

### Error Handling
Methods return sensible defaults (0, empty arrays) when no data exists, ensuring the widget always displays correctly.

## Testing

### Unit Tests
The `RequestLoggerStatisticsTest` class provides comprehensive coverage:
- Tests for all four new methods
- Edge cases (no data, empty results)
- Filtering and limiting functionality
- Data accuracy validation

### Manual Testing
To test the widget:
1. Activate the plugin in WordPress
2. Navigate to wp-admin/index.php
3. The "CF7 API Status" widget should appear
4. Submit some CF7 forms with API integrations
5. Widget should update with statistics

## Internationalization (i18n)

All user-facing strings use WordPress i18n functions:
- `__()` for translated strings
- `esc_html_e()` for escaped output
- `sprintf()` for dynamic content with translator comments
- Text domain: `'contact-form-to-api'` (literal string required for wp i18n extraction)

Translator comments added for all sprintf placeholders explaining what each variable represents.

## Performance Considerations

- Statistics queries use database indexes (created_at)
- Queries limited to 24-hour window for dashboard relevance
- Error list limited to 5 items to prevent widget bloat
- CSS loaded only on dashboard page (hook check)

## Browser Compatibility

The CSS uses modern features but with fallbacks:
- CSS Grid with auto-fit for responsive layout
- Flexbox for action buttons
- Media queries for mobile devices
- Dark mode support via prefers-color-scheme

## Future Enhancements

Potential improvements for future versions:
- Configurable time window (12h, 24h, 7d)
- Click to refresh statistics
- Chart/graph visualization option
- Export statistics as CSV
- Email alerts for high error rates
