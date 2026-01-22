# User Guide

Complete user guide for Contact Form 7 to API plugin features and configuration.

## 📋 Table of Contents

- [Dashboard Widget](#dashboard-widget)
- [API Logs](#api-logs)
- [Date Range Filters](#date-range-filters)
- [Status Filter](#status-filter)
- [Global Settings](#global-settings)
- [Troubleshooting](#troubleshooting)

---

## Dashboard Widget

The CF7 API Status widget provides a quick overview of your API integration health directly on the WordPress dashboard.

### Location

The widget appears automatically on **WordPress Admin → Dashboard** for users with administrator permissions.

### Widget Components

#### Quick Statistics (Last 24 Hours)

| Metric | Description |
|--------|-------------|
| **Total Requests** | Count of all API requests in the last 24 hours |
| **Success Rate** | Percentage of successful requests with color coding |
| **Avg Response Time** | Average API response time in milliseconds |

**Success Rate Color Coding:**
- 🟢 **Green**: 90%+ success rate (healthy)
- 🟡 **Yellow**: 70-89% success rate (needs attention)
- 🔴 **Red**: Below 70% success rate (critical)

#### Recent Errors Section

- Lists up to 5 most recent API errors
- Shows form name, error message, and time since error
- Direct links to view full error details
- Alert message when errors require attention

#### Action Links

- **View All Logs**: Navigate to the complete API logs page
- **Settings**: Access Contact Form 7 settings

### Widget Visibility

- Only visible to administrators (`manage_options` capability)
- Can be hidden via WordPress **Screen Options** (top right of dashboard)
- Drag and drop to reposition on dashboard

---

## API Logs

Access detailed logs of all API requests at **Contact → API Logs**.

### Statistics Panel

Displays aggregated metrics at the top:
- Total Requests
- Successful requests
- Failed requests
- Average Response Time

### Filtering Options

| Filter | Description |
|--------|-------------|
| **Status** | All, Success, or Errors |
| **Form** | Filter by specific Contact Form 7 form |
| **Search** | Search in endpoint URLs and error messages |
| **Date** | Filter by date range (see below) |

### Log Details

Click on any log entry to view:
- Full endpoint URL
- HTTP method and status code
- Request headers (with sensitive data redacted)
- Request payload
- Response body
- Error messages (if any)

### Bulk Actions

- **Delete**: Remove selected log entries
- **Retry**: Re-execute failed API requests

### Export Options

Export filtered logs as:
- **CSV**: Spreadsheet format for analysis
- **JSON**: Developer-friendly format

---

## Date Range Filters

Filter API logs by specific time periods using preset options or custom date ranges.

### Preset Filters

Select from the "Date Filter" dropdown:

| Option | Description |
|--------|-------------|
| **All Time** | Show all logs (default) |
| **Today** | Today's logs only |
| **Yesterday** | Yesterday's logs |
| **Last 7 Days** | Past week |
| **Last 30 Days** | Past month |
| **This Month** | Current calendar month |

### Custom Date Range

1. Select **"Custom Range"** from the dropdown
2. Enter a **Start Date** (required)
3. Optionally enter an **End Date**
4. Click **"Apply Filter"**

> **Tip**: Leave end date empty to show logs from start date to present.

### Filter Features

- ✅ **Persistence**: Filters remain active when paginating or sorting
- ✅ **Export Integration**: CSV/JSON exports respect active filters
- ✅ **Visual Feedback**: Active filter badge shows current selection
- ✅ **Validation**: Prevents invalid date combinations

### Clearing Filters

Click the **"Clear Filters"** button to remove all active filters and show all logs.

---

## Status Filter

Filter API logs by request status to focus on successes or errors.

### Available Options

Select from the "Status" dropdown:

| Option | Description |
|--------|-------------|
| **All** | Show all logs (default) |
| **Success** | Show only successful requests (HTTP 2xx) |
| **Error** | Show all failed requests (client and server errors) |

### Using Status Filters

1. Select an option from the **"Status"** dropdown
2. Optionally combine with **Date** or other filters
3. Click **"Apply Filters"**

### Filter Features

- ✅ **Combination**: Use with date filters for precise filtering
- ✅ **Persistence**: Remains active across pagination and sorting
- ✅ **Export Integration**: Respects status filter when exporting logs
- ✅ **Visual Feedback**: Active filters shown in badge below controls
- ✅ **Statistics Update**: Stats grid reflects filtered results

### Example Use Cases

- **Error Analysis**: Filter by "Error" status to view only failed requests
- **Success Audit**: Filter by "Success" status to review successful integrations
- **Time-based Error Review**: Combine "Error" status with "Yesterday" date filter

---

## Global Settings

Configure plugin-wide settings at **Silver Assist Dashboard → Settings Hub → Global Settings**.

### Retry Configuration

Control how failed API requests can be retried:

| Setting | Default | Description |
|---------|---------|-------------|
| **Max retries per entry** | 3 | Maximum retries for a single failed request (0-10) |
| **Max retries per hour** | 10 | Global rate limit for all retries (0-100) |

### Sensitive Data Patterns

Define field patterns to redact from logs for security:

- One pattern per line
- Case-insensitive matching
- Default patterns: `password`, `token`, `secret`, `api_key`, `apikey`, `api-key`

**Add custom patterns** for your sensitive fields:
```
credit_card
ssn
bank_account
```

### Logging Control

| Setting | Default | Description |
|---------|---------|-------------|
| **Enable API request logging** | ✓ Enabled | Toggle database logging on/off |

> **Note**: Disabling logging prevents all API requests from being recorded. Useful for GDPR compliance or performance optimization.

### Log Retention

Automatically delete old logs to maintain database health:

| Option | Description |
|--------|-------------|
| **Never** | Keep all logs indefinitely |
| **7 days** | Delete logs older than 7 days |
| **14 days** | Delete logs older than 14 days |
| **30 days** | Delete logs older than 30 days (default) |
| **60 days** | Delete logs older than 60 days |
| **90 days** | Delete logs older than 90 days |

Cleanup runs automatically via WordPress cron (daily).

---

## Migrating Legacy Logs to Encrypted Format

> **Note**: This feature will be available in a future release. See [Issue #37](https://github.com/SilverAssist/contact-form-to-api/issues/37) for details.

If you upgraded from a version prior to encryption support, your existing logs may contain unencrypted sensitive data. The migration tool allows you to encrypt these legacy logs.

### When to Migrate

Consider migration if:
- You upgraded from a pre-encryption version
- Your logs page shows warnings about unencrypted entries
- You need to ensure all stored data is encrypted for compliance

### Starting Migration

1. Navigate to **Settings → CF7 to API → Migration**
2. Review the count of unencrypted logs
3. Click **Start Migration** to begin the process

### Migration Progress

The migration runs in batches to prevent timeouts:
- **Batch size**: 100 logs per batch (configurable)
- **Progress indicator**: Shows percentage complete
- **Pause/Resume**: Stop and continue migration at any time
- **Background processing**: Uses WordPress cron for large datasets

### What Gets Migrated

| Data Type | Action |
|-----------|--------|
| Request URL | Encrypted |
| Request Headers | Encrypted |
| Request Body | Encrypted |
| Response Body | Encrypted |
| Form Data | Encrypted |

### Migration Status Indicators

| Status | Meaning |
|--------|---------|
| 🔴 Pending | Logs not yet migrated |
| 🟡 In Progress | Migration currently running |
| 🟢 Complete | All logs encrypted |

### Post-Migration

After migration completes:
- All sensitive data is encrypted at rest
- Decryption happens on-demand when viewing logs
- Original encryption version tracked for auditing

---

## Troubleshooting

### Dashboard Widget Issues

**Widget not appearing?**
- Verify you have administrator permissions
- Check Screen Options to ensure widget isn't hidden
- Ensure plugin is activated

**Statistics showing zero?**
- Submit some forms with API integrations configured
- Wait a moment for data to process
- Check that logging is enabled in Global Settings

### Date Filter Issues

**Filter not working?**
- Ensure JavaScript is enabled in your browser
- Check that date format is correct (YYYY-MM-DD)
- Verify start date is before or equal to end date

**Export includes wrong data?**
- Verify filter badge is visible before exporting
- Check URL contains date parameters

### Logging Issues

**Logs not appearing?**
- Verify logging is enabled in Global Settings
- Check that forms have API integrations configured
- Look for PHP errors in debug log

**Sensitive data visible in logs?**
- Add field patterns to Global Settings
- New patterns only apply to future logs
- Existing logs retain original data

### Cron Job Issues

**Log cleanup not running?**
- Verify WP-Cron is enabled in wp-config.php
- Check scheduled events with WP-CLI: `wp cron event list`
- Manually trigger: `wp cron event run cf7_api_cleanup_old_logs`

---

## Support

For issues or feature requests, please visit our GitHub repository.

---

**Version**: 1.2.0+  
**Compatibility**: WordPress 6.5+, PHP 8.2+, Contact Form 7 5.7+
