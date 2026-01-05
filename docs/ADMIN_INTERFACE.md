# API Logs Interface

Guide for using the API Logs admin interface.

## Location

**WordPress Admin → Contact → API Logs**

---

## Statistics Panel

At the top of the page, you'll see aggregated metrics:

| Metric | Description |
|--------|-------------|
| **Total Requests** | Count of all API calls logged |
| **Successful** | Requests completed successfully |
| **Failed** | Requests that resulted in errors |
| **Avg Response Time** | Average execution time |

> **Tip**: Statistics update based on active filters.

---

## Filtering Logs

### Status Tabs

Quick filter by status:
- **All** - All logged requests
- **Success** - Successful API calls
- **Errors** - Failed requests

### Search

Use the search box to find logs by:
- Endpoint URLs
- Error messages

### Date Filters

See [Date Range Filters](USER_GUIDE.md#date-range-filters) in the User Guide.

---

## Log Table

### Columns

| Column | Description |
|--------|-------------|
| **Form** | Form name (click to filter by form) |
| **Endpoint** | API URL (truncated) |
| **Method** | HTTP method (GET, POST, etc.) |
| **Status** | Request status with color indicator |
| **Response** | HTTP response code |
| **Time** | Execution time in seconds |
| **Retries** | Number of retry attempts |
| **Date** | Request timestamp |

### Sorting

Click column headers to sort. Default: newest first.

### Row Actions

Hover over any row to see:
- **View Details** - Open full log information
- **Delete** - Remove this log entry

---

## Bulk Actions

Select multiple logs using checkboxes, then choose:
- **Delete** - Remove selected entries
- **Retry** - Re-execute failed requests

---

## Log Detail View

Click "View Details" on any log to see complete information:

### Request Information
- Full endpoint URL
- HTTP method
- Request status
- Timestamp
- Execution time
- Retry count

### Request Headers
Headers sent to the API (sensitive data automatically redacted).

### Request Data
Form submission data sent to the API.

### Response Information
- HTTP response code
- Response headers
- Response body

### Error Message
For failed requests, shows error details.

---

## Status Colors

| Status | Color | Meaning |
|--------|-------|---------|
| Success | 🟢 Green | Request completed successfully |
| Error | 🔴 Red | Request failed |
| Client Error | 🟠 Orange | 4xx HTTP errors |
| Server Error | 🔴 Red | 5xx HTTP errors |
| Timeout | 🟡 Yellow | Request timed out |

---

## Screen Options

Click **Screen Options** (top right) to configure:
- **Items per page**: Number of logs displayed (default: 20)

---

## Privacy & Security

- **Sensitive Data**: Authorization headers, API keys, passwords, and tokens are automatically masked as `***REDACTED***`
- **Confirmation**: Destructive actions require confirmation

---

## Export

Export filtered logs using the export buttons:
- **CSV** - Spreadsheet format
- **JSON** - Developer format

Exports respect all active filters (status, form, date, search).

---

## Related

- [User Guide](USER_GUIDE.md) - Complete plugin documentation
- [API Reference](API_REFERENCE.md) - Hooks and filters for developers
