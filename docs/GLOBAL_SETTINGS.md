# Global Settings Feature

## Overview

The Global Settings feature provides a centralized configuration interface for plugin-wide settings. This allows administrators to customize retry limits, sensitive data patterns, logging behavior, and log retention without modifying code.

## Location

The Global Settings page can be accessed from:
- **Silver Assist Dashboard** → Settings Hub → Global Settings
- **Quick Links** section on the main plugin documentation page

## Settings Categories

### 1. Retry Configuration

Controls how many times failed API requests can be retried.

- **Maximum retries per entry** (default: 3)
  - Maximum number of times a single failed request can be manually retried
  - Range: 0-10
  - Prevents excessive retry attempts on permanently failing endpoints

- **Maximum retries per hour** (default: 10)
  - Global rate limit for all retry attempts across all requests
  - Range: 0-100
  - Protects against API rate limiting and abuse

### 2. Sensitive Data Patterns

Defines field name patterns that should be redacted in logs for security and privacy compliance.

- **Field patterns to anonymize**
  - One pattern per line
  - Case-insensitive substring matching
  - Default patterns: password, token, secret, api_key, apikey, api-key
  - Examples of custom patterns:
    - `credit_card`
    - `ssn`
    - `tax_id`
    - `bank_account`

### 3. Logging Control

Enables or disables API request logging entirely.

- **Enable API request logging** (default: enabled)
  - When disabled, API requests will not be logged to the database
  - Useful for GDPR compliance or performance optimization
  - Logs are required for debugging and audit trails

### 4. Log Retention

Automatically deletes old logs to maintain database health.

- **Delete logs older than** (default: 30 days)
  - Options: Never, 7, 14, 30, 60, or 90 days
  - Scheduled via WP-Cron (runs daily)
  - Set to "Never" to keep all logs indefinitely

## Technical Implementation

### Settings Storage

Settings are stored in WordPress options table:
- **Option name**: `cf7_api_global_settings`
- **Storage format**: Serialized array

### Architecture Components

1. **`Core\Settings`** - Settings model with defaults and type-safe accessors
2. **`Admin\GlobalSettingsController`** - Form handling and validation
3. **`Admin\Views\GlobalSettingsView`** - HTML rendering
4. **`Core\Plugin`** - WP-Cron registration and cleanup execution
5. **`Core\Activator`** - Default settings initialization on activation

### Integration Points

Settings are consumed by:
- **`Core\RequestLogger`** - Checks logging enabled and retry limits
- **`Core\SensitiveDataPatterns`** - Merges custom patterns with defaults
- **`Admin\RequestLogController`** - Enforces retry rate limits
- **`Admin\Views\RequestLogView`** - Displays retry button status

### WP-Cron Cleanup

When log retention is enabled:
1. Daily cron job (`cf7_api_cleanup_old_logs`) is scheduled
2. Deletes logs older than configured retention period
3. Logs cleanup result via DebugLogger
4. Automatically reschedules when retention setting changes
5. Cleared on plugin deactivation

## Usage Examples

### Reading Settings Programmatically

```php
use SilverAssist\ContactFormToAPI\Core\Settings;

// Get settings instance
$settings = Settings::instance();
$settings->init();

// Get individual settings
$max_retries = $settings->get_max_manual_retries();
$logging_enabled = $settings->is_logging_enabled();
$patterns = $settings->get_sensitive_patterns();

// Get with custom default
$custom_value = $settings->get('custom_key', 'default_value');
```

### Updating Settings Programmatically

```php
// Update single setting
$settings->set('max_manual_retries', 5);

// Update multiple settings
$settings->update([
    'max_manual_retries' => 5,
    'logging_enabled' => false,
    'log_retention_days' => 60,
]);

// Reset to defaults
$settings->reset();
```

### Checking Retry Limits

```php
use SilverAssist\ContactFormToAPI\Core\RequestLogger;

// Get current limits from settings
$max_manual = RequestLogger::get_max_manual_retries();
$max_hourly = RequestLogger::get_max_retries_per_hour();
```

## Filters and Hooks

### Available Filters

```php
// Modify settings before save (future enhancement)
add_filter('cf7_api_settings_before_save', function($settings) {
    // Modify settings array
    return $settings;
});
```

### Available Actions

```php
// After cleanup job runs (future enhancement)
add_action('cf7_api_after_cleanup', function($deleted_count) {
    // Custom cleanup actions
});
```

## Security Considerations

1. **Capability Check**: Only users with `manage_options` capability can access
2. **Nonce Verification**: All form submissions are protected by WordPress nonces
3. **Input Sanitization**: All inputs are sanitized using WordPress functions
4. **Data Redaction**: Sensitive patterns are applied to all logged data
5. **Rate Limiting**: Prevents abuse of retry functionality

## Database Schema

No additional database tables required. Settings are stored in `wp_options`:

```sql
SELECT * FROM wp_options WHERE option_name = 'cf7_api_global_settings';
```

## Performance Impact

- **Settings Load**: Cached in memory, loaded once per request
- **Log Retention**: Daily cron job runs during low-traffic periods
- **Logging Toggle**: When disabled, eliminates all database writes for logs

## Troubleshooting

### Settings Not Saving

1. Check user has `manage_options` capability
2. Verify nonce is being generated correctly
3. Check WordPress debug log for errors
4. Ensure database is writable

### Cron Job Not Running

1. Verify WP-Cron is enabled (not disabled in wp-config.php)
2. Check that `wp_schedule_event()` is being called
3. Use WP-CLI to verify scheduled events: `wp cron event list`
4. Manually trigger: `wp cron event run cf7_api_cleanup_old_logs`

### Custom Patterns Not Working

1. Verify patterns are saved (check database)
2. Ensure patterns use lowercase (matching is case-insensitive)
3. Check logs are being created after pattern addition
4. Patterns apply to new logs only, not existing ones

## Future Enhancements

Potential improvements for future versions:

1. **Import/Export Settings** - Backup and restore configuration
2. **Settings History** - Track changes to settings over time
3. **Advanced Retry Strategy** - Exponential backoff configuration
4. **Pattern Testing Tool** - Test patterns against sample data
5. **Cleanup Preview** - Show how many logs would be deleted
6. **Custom Cron Schedule** - Configure cleanup frequency
7. **Email Notifications** - Alert on cleanup completion or errors

## Version History

- **1.2.0** - Initial implementation of Global Settings page
