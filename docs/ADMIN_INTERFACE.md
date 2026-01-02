# WordPress Admin Interface - API Logs

## Overview

The admin interface for managing API request logs, implemented using WordPress native `WP_List_Table` combined with a custom database table for optimal performance.

## Menu Location

**WordPress Admin → Contact Form 7 → API Logs**

## Main Components

### 1. Statistics Panel

Displays aggregated metrics at the top of the logs page:

| Metric | Description |
|--------|-------------|
| Total Requests | Count of all API calls logged |
| Successful | Requests completed with success status |
| Failed | Requests that resulted in errors |
| Avg Response Time | Average execution time in seconds |

The statistics can be filtered by form when using the `form_id` query parameter.

### 2. Status Filters (Views)

Filter logs by status using tabs:

- **All** - All logged requests
- **Success** - Successful API calls
- **Errors** - Failed requests (includes error, client_error, server_error)

### 3. List Table Columns

| Column | Sortable | Description |
|--------|----------|-------------|
| Form | ✓ | Form name with link to filter by form |
| Endpoint | ✗ | API URL (truncated, with view details link) |
| Method | ✗ | HTTP method (GET, POST, etc.) |
| Status | ✓ | Request status with color indicator |
| Response | ✓ | HTTP response code |
| Time (s) | ✓ | Execution time in seconds |
| Retries | ✓ | Number of retry attempts |
| Date | ✓ | Request timestamp (default sort: DESC) |

### 4. Bulk Actions

- **Delete** - Remove selected log entries
- **Retry** - Re-execute failed requests

### 5. Row Actions

- **View Details** - Open detailed log view
- **Delete** - Remove single log entry

## Log Detail Page

Shows complete information for a single log entry:

### Request Information
- Endpoint URL
- HTTP Method  
- Request status
- Timestamp
- Execution time
- Retry count

### Request Headers
Pretty-printed JSON with sensitive data redacted (Authorization, API keys, etc.)

### Request Data
Form submission data sent to the API

### Response Information
- HTTP response code
- Response headers
- Response body (formatted JSON)

### Error Message
Displayed for failed requests with error details.

## Visual Indicators

### Status Colors
- **Success** - Green background
- **Error** - Red background
- **Client Error** - Light red background
- **Server Error** - Red background
- **Pending** - Yellow background
- **Timeout** - Orange background

### HTTP Method Badges
Color-coded badges for each HTTP method type.

## Privacy & Security

- **Sensitive Data Redaction**: Authorization headers, API keys, passwords, and tokens are automatically masked as `***REDACTED***`
- **JSON Pretty Printing**: Request/response data formatted for readability
- **Confirmation Dialogs**: Required for destructive actions

## Screen Options

Users can configure:
- **Items per page**: Number of logs displayed (default: 20)

## Search Functionality

The search box searches across:
- Endpoint URLs
- Error messages

## CSS Styling

Styles are loaded from:
```
assets/css/api-log-admin.css
```

Key style classes:
- `.cf7-api-stats-summary` - Statistics panel container
- `.stats-grid` - Grid layout for stat boxes
- `.stat-box` - Individual statistic display
- `.status-badge` - Status indicator badges
- `.method-badge` - HTTP method badges

## JavaScript Functionality

Scripts loaded from:
```
assets/js/api-log-admin.js
```

Features:
- Delete confirmation dialogs
- Bulk action validation

## Database Schema

Logs are stored in a custom table: `{prefix}cf7_api_logs`

Indexed columns for performance:
- `form_id`
- `status`
- `created_at`

## Performance Optimizations

- **Direct SQL queries** - No ORM overhead
- **Indexed columns** - Fast filtering and sorting
- **Pagination** - Limits data loaded per request
- **Lazy loading** - Details only loaded when accessed

## Related Files

### Controllers
- [RequestLogController.php](../includes/Admin/RequestLogController.php) - Route handling and actions

### Views  
- [RequestLogView.php](../includes/Admin/Views/RequestLogView.php) - HTML rendering

### Table
- [RequestLogTable.php](../includes/Admin/RequestLogTable.php) - WP_List_Table implementation

### Logger
- [RequestLogger.php](../includes/Core/RequestLogger.php) - Database operations

## Hooks

### Actions

```php
// Before displaying logs page
do_action('cf7_api_before_logs_page');

// After log deletion
do_action('cf7_api_log_deleted', $log_id);

// After bulk deletion
do_action('cf7_api_logs_bulk_deleted', $log_ids);
```

### Filters

```php
// Modify logs query arguments
$args = apply_filters('cf7_api_logs_query_args', $args);

// Modify statistics output
$stats = apply_filters('cf7_api_logs_statistics', $stats, $form_id);
```

## Future Improvements

Potential enhancements for future versions:

1. **Export** - CSV/JSON export functionality
2. **Dashboard Widget** - Summary widget for WordPress dashboard
3. **Date Range Filter** - Filter logs by date range
4. **Charts** - Visual representation of trends
5. **Email Alerts** - Notifications when error rate exceeds threshold
