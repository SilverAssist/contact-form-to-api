# API Reference

Complete API reference for Contact Form 7 to API plugin, documenting all hooks, filters, classes, and public APIs for developers.

## 📋 Table of Contents

- [Plugin Architecture](#plugin-architecture)
- [Hooks and Filters](#hooks-and-filters)
- [Legacy Hook Compatibility](#legacy-hook-compatibility)
- [Classes](#classes)
- [Constants](#constants)
- [Integration Examples](#integration-examples)

## Plugin Architecture

### Namespace Structure

```
SilverAssist\ContactFormToAPI\
├── Admin\                          # Priority 30 - Admin components
│   ├── Loader.php                  # Admin component loader
│   ├── SettingsPage.php            # Settings Hub integration controller
│   ├── RequestLogController.php    # Request logs admin controller
│   ├── RequestLogTable.php         # WP_List_Table for logs
│   └── Views\                      # Separated view templates
│       ├── RequestLogView.php      # Request log HTML rendering
│       └── SettingsView.php        # Settings page HTML rendering
├── ContactForm\                    # Priority 30 - CF7 Integration
│   ├── Integration.php             # CF7 hooks and panel logic
│   └── Views\
│       └── IntegrationView.php     # CF7 panel HTML rendering
├── Core\                           # Priority 10 - Core components
│   ├── Interfaces\
│   │   └── LoadableInterface.php   # Component contract
│   ├── Activator.php               # Lifecycle management
│   ├── RequestLogger.php           # API request/response DB logger
│   └── Plugin.php                  # Main plugin controller
├── Services\                       # Priority 20 - Business logic services
│   ├── Loader.php                  # Services component loader
│   ├── ApiClient.php               # HTTP client with retry logic
│   └── CheckboxHandler.php         # Checkbox value processing
└── Utils\                          # Priority 40 - Utility classes
    ├── DateFilterTrait.php         # Reusable date filtering for SQL queries
    ├── DebugLogger.php             # PSR-3 file logger for debugging
    ├── SensitiveDataPatterns.php   # Sensitive data detection patterns
    └── StringHelper.php            # String manipulation utilities
```

### Component Loading

All components implement `LoadableInterface` with priority-based loading:

- **Priority 10**: Core components (Plugin, Activator, RequestLogger)
- **Priority 20**: Services (ApiClient, CheckboxHandler)
- **Priority 30**: Admin & ContactForm (SettingsPage, RequestLogController, Integration)
- **Priority 40**: Utilities (DebugLogger, StringHelper)

### Dual Logger Architecture

The plugin has two distinct logging systems:

- **`Core\RequestLogger`**: Database-backed logger for API request/response tracking (shown in admin UI)
- **`Utils\DebugLogger`**: PSR-3 compliant file logger for plugin debugging (dev/troubleshooting)

### MVC Pattern in Admin

Admin components follow MVC separation:

- **Controllers**: `RequestLogController`, `SettingsPage` (routing, actions)
- **Views**: `Views/RequestLogView`, `Views/SettingsView` (HTML rendering)
- **Models**: `RequestLogTable` (WP_List_Table data handling)

## Hooks and Filters

### Action Hooks

#### `cf7_api_before_send`

Fires before sending data to API endpoint.

**Parameters**:

- `array $data` - Form submission data
- `\WPCF7_ContactForm $contact_form` - CF7 form object
- `array $config` - API configuration

**Example**:

```php
\add_action("cf7_api_before_send", function($data, $contact_form, $config) {
    // Modify data before sending
    $data["custom_field"] = "custom_value";
    return $data;
}, 10, 3);
```

#### `cf7_api_after_send`

Fires after sending data to API endpoint.

**Parameters**:

- `array|WP_Error $response` - API response or error
- `array $data` - Sent form data
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Example**:

```php
\add_action("cf7_api_after_send", function($response, $data, $contact_form) {
    if (\is_wp_error($response)) {
        \error_log("API Error: " . $response->get_error_message());
    } else {
        \error_log("API Success: " . print_r($response, true));
    }
}, 10, 3);
```

#### `cf7_api_request_error`

Fires when API request fails.

**Parameters**:

- `\WP_Error $error` - Error object
- `array $data` - Form data that failed to send
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Example**:

```php
\add_action("cf7_api_request_error", function($error, $data, $contact_form) {
    // Log error to custom system
    \do_action("custom_error_logger", [
        "type" => "cf7_api",
        "message" => $error->get_error_message(),
        "data" => $data
    ]);
}, 10, 3);
```

### Filter Hooks

#### `cf7_api_request_data`

Filter form data before sending to API.

**Parameters**:

- `array $data` - Form submission data
- `\WPCF7_ContactForm $contact_form` - CF7 form object
- `array $config` - API configuration

**Return**: `array` Modified form data

**Example**:

```php
\add_filter("cf7_api_request_data", function($data, $contact_form, $config) {
    // Add timestamp
    $data["submitted_at"] = date("Y-m-d H:i:s");
    
    // Add site information
    $data["site_url"] = \get_site_url();
    
    return $data;
}, 10, 3);
```

#### `cf7_api_request_args`

Filter HTTP request arguments before sending.

**Parameters**:

- `array $args` - HTTP request arguments
- `array $data` - Form data
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Return**: `array` Modified request arguments

**Example**:

```php
\add_filter("cf7_api_request_args", function($args, $data, $contact_form) {
    // Add custom headers
    $args["headers"]["X-Custom-Header"] = "Custom Value";
    
    // Increase timeout for specific form
    if ($contact_form->id() === 123) {
        $args["timeout"] = 60;
    }
    
    return $args;
}, 10, 3);
```

#### `cf7_api_response_data`

Filter API response before processing.

**Parameters**:

- `array $response_data` - Decoded response data
- `array $raw_response` - Raw HTTP response
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Return**: `array` Modified response data

**Example**:

```php
\add_filter("cf7_api_response_data", function($response_data, $raw_response, $contact_form) {
    // Extract specific fields
    if (isset($response_data["data"]["lead_id"])) {
        $response_data["lead_id"] = $response_data["data"]["lead_id"];
    }
    
    return $response_data;
}, 10, 3);
```

#### `cf7_api_field_mapping`

Filter field mapping configuration.

**Parameters**:

- `array $mapping` - Field mapping array
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Return**: `array` Modified field mapping

**Example**:

```php
\add_filter("cf7_api_field_mapping", function($mapping, $contact_form) {
    // Add dynamic mapping
    $mapping["user_ip"] = "client_ip_address";
    $mapping["user_agent"] = "client_user_agent";
    
    return $mapping;
}, 10, 2);
```

#### `cf7_api_debug_enabled`

Filter debug mode status.

**Parameters**:

- `bool $enabled` - Whether debug is enabled
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Return**: `bool` Debug enabled status

**Example**:

```php
\add_filter("cf7_api_debug_enabled", function($enabled, $contact_form) {
    // Enable debug for specific forms
    if ($contact_form->id() === 123) {
        return true;
    }
    
    return $enabled;
}, 10, 2);
```

#### `cf7_api_collect_mail_tags`

Filter form tags before processing in API integration panel.

**Purpose**: Allows modification of the form tags collection used to build field mappings and templates in the CF7 admin interface. This is useful for adding custom tags, filtering out specific tag types, or modifying tag properties before they are displayed in the API integration panel.

**Used in**: `Integration::get_mail_tags()` - Called when rendering the API integration panel to collect available form fields.

**Parameters**:

- `array $tags` - Array of WPCF7_FormTag objects from `scan_form_tags()`

**Return**: `array` Modified array of form tags

**Example**:

```php
\add_filter("cf7_api_collect_mail_tags", function($tags) {
    // Add custom virtual field for API
    $custom_tag = (object) [
        'type' => 'text',
        'name' => 'api_timestamp',
        'values' => []
    ];
    $tags[] = $custom_tag;
    
    // Or filter out specific tag types
    return array_filter($tags, function($tag) {
        return $tag->type !== 'submit';
    });
}, 10, 1);
```

#### `cf7_api_before_send_to_api`

Action fired before sending form data to the API endpoint.

**Parameters**:

- `array $record` - The prepared record data including URL and fields

**Example**:

```php
\add_action("cf7_api_before_send_to_api", function($record) {
    // Log or modify before sending
    \error_log("Sending to API: " . $record["url"]);
}, 10, 1);
```

#### `cf7_api_after_send_to_api`

Action fired after receiving API response.

**Parameters**:

- `array $record` - The sent record data
- `array|WP_Error $response` - API response or error

**Example**:

```php
\add_action("cf7_api_after_send_to_api", function($record, $response) {
    if (!\is_wp_error($response)) {
        $code = \wp_remote_retrieve_response_code($response);
        \error_log("API Response Code: " . $code);
    }
}, 10, 2);
```

#### `cf7_api_set_record_value`

Filter individual field values before adding to the record.

**Parameters**:

- `mixed $value` - The field value
- `string $api_field_name` - The API field name

**Return**: `mixed` Modified field value

**Example**:

```php
\add_filter("cf7_api_set_record_value", function($value, $api_field_name) {
    // Transform phone numbers
    if ($api_field_name === "phone") {
        return preg_replace("/[^0-9]/", "", $value);
    }
    return $value;
}, 10, 2);
```

#### `cf7_api_create_record`

Filter the complete record before sending to API.

**Parameters**:

- `array $record` - The record data
- `array $submitted_data` - Original form submission data
- `array $data_map` - Field mapping configuration
- `string $type` - Record type (params, json, xml)
- `string $template` - Template string for json/xml

**Return**: `array` Modified record

**Example**:

```php
\add_filter("cf7_api_create_record", function($record, $submitted_data, $data_map, $type, $template) {
    // Add computed fields
    if ($type === "params") {
        $record["fields"]["full_name"] = trim(
            ($record["fields"]["first_name"] ?? "") . " " . 
            ($record["fields"]["last_name"] ?? "")
        );
    }
    return $record;
}, 10, 5);
```

#### `cf7_api_get_args` / `cf7_api_post_args`

Filter HTTP request arguments for GET/POST requests.

**Parameters**:

- `array $args` - WordPress HTTP API arguments

**Return**: `array` Modified arguments

**Example**:

```php
\add_filter("cf7_api_post_args", function($args) {
    // Add custom header
    $args["headers"]["X-API-Version"] = "2.0";
    // Increase timeout
    $args["timeout"] = 45;
    return $args;
}, 10, 1);
```

#### `cf7_api_get_url` / `cf7_api_post_url`

Filter the API URL before sending request.

**Parameters**:

- `string $url` - The API endpoint URL
- `array $record` - The record data (only for GET)

**Return**: `string` Modified URL

**Example**:

```php
\add_filter("cf7_api_post_url", function($url) {
    // Add API version to URL
    return \add_query_arg("version", "2", $url);
}, 10, 1);
```

## Legacy Hook Compatibility

### Overview

This plugin provides full backward compatibility with the legacy "Contact Form 7 to API" plugin by Query Solutions (`cf7-to-api`). If your theme or custom code uses the old `qs_cf7_*` hooks, they will continue to work without any modifications.

> **Migration Note**: While legacy hooks are fully supported, we recommend migrating to the new `cf7_api_*` hooks for new development. The legacy hooks are bridged at priority 5, so new hooks (priority 10) will run after legacy modifications are applied.

### Hook Mapping Table

| Legacy Hook (Query Solutions) | New Hook (Silver Assist) | Type | Notes |
|------------------------------|--------------------------|------|-------|
| `qs_cf7_collect_mail_tags` | `cf7_api_collect_mail_tags` | Filter | Form tags collection |
| `qs_cf7_api_before_sent_to_api` | `cf7_api_before_send_to_api` | Action | Note: "sent" → "send" |
| `qs_cf7_api_after_sent_to_api` | `cf7_api_after_send_to_api` | Action | Note: "sent" → "send" |
| `set_record_value` | `cf7_api_set_record_value` | Filter | Field value processing |
| `cf7api_create_record` | `cf7_api_create_record` | Filter | Record creation |
| `qs_cf7_api_get_args` | `cf7_api_get_args` | Filter | GET request arguments |
| `qs_cf7_api_get_args` | `cf7_api_post_args` | Filter | POST request arguments |
| `qs_cf7_api_get_url` | `cf7_api_get_url` | Filter | GET URL modification |
| `qs_cf7_api_post_url` | `cf7_api_post_url` | Filter | POST URL modification |

### How Legacy Compatibility Works

The plugin registers bridge hooks at priority 5 that automatically call the legacy hooks when the new hooks are fired. This means:

1. **Existing code continues to work**: Any theme or plugin using `qs_cf7_*` hooks will function without changes
2. **Priority order**: Legacy hooks run first (priority 5), then new hooks (priority 10)
3. **No conflicts**: Both hook systems can coexist safely

### Legacy Hook Examples

These examples show code that will continue to work with this plugin:

#### Using `qs_cf7_api_before_sent_to_api`

```php
// Legacy code - still works!
\add_action("qs_cf7_api_before_sent_to_api", function($record) {
    // Store submission data before API call
    \update_option("last_cf7_submission", $record);
}, 10, 1);
```

#### Using `qs_cf7_api_after_sent_to_api`

```php
// Legacy code - still works!
\add_action("qs_cf7_api_after_sent_to_api", function($record, $response) {
    // Process API response
    if (!\is_wp_error($response)) {
        $body = \wp_remote_retrieve_body($response);
        \error_log("API Response: " . $body);
    }
}, 10, 2);
```

#### Using `set_record_value`

```php
// Legacy code - still works!
\add_filter("set_record_value", function($value, $api_field_name) {
    // Convert checkbox arrays to comma-separated strings
    if (is_array($value)) {
        return implode(", ", $value);
    }
    return $value;
}, 10, 2);
```

#### Using `qs_cf7_api_get_args`

```php
// Legacy code - still works!
\add_filter("qs_cf7_api_get_args", function($args) {
    // Add authentication header
    $args["headers"]["Authorization"] = "Bearer " . \get_option("api_token");
    return $args;
}, 10, 1);
```

### Migration Guide

To migrate from legacy hooks to new hooks:

```php
// Before (legacy)
\add_action("qs_cf7_api_before_sent_to_api", "my_before_api_handler", 10, 1);
\add_action("qs_cf7_api_after_sent_to_api", "my_after_api_handler", 10, 2);
\add_filter("set_record_value", "my_value_filter", 10, 2);

// After (new)
\add_action("cf7_api_before_send_to_api", "my_before_api_handler", 10, 1);
\add_action("cf7_api_after_send_to_api", "my_after_api_handler", 10, 2);
\add_filter("cf7_api_set_record_value", "my_value_filter", 10, 2);
```

### Dual Plugin Warning

If both the legacy Query Solutions plugin (`cf7-to-api`) and this plugin are active simultaneously, an admin notice will be displayed warning about potential conflicts. To avoid duplicate API submissions:

1. **Deactivate the legacy plugin** after installing this one
2. Keep the legacy plugin installed (but deactivated) as a reference during migration
3. Test your forms to ensure API submissions work correctly

## Classes

### Core\Plugin

Main plugin controller implementing singleton pattern and LoadableInterface.

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor

#### Methods

##### `instance(): Plugin`

Get singleton instance.

**Return**: `Plugin` Plugin instance

**Example**:

```php
use SilverAssist\ContactFormToAPI\Core\Plugin;

$plugin = Plugin::instance();
```

##### `init(): void`

Initialize plugin and load all components.

##### `load_components(): void`

Load all plugin components based on priority.

##### `init_updater(): void`

Configure GitHub updater (via `silverassist/wp-github-updater` package).

##### `get_priority(): int`

Get component loading priority.

**Return**: `int` Returns 10 (Core priority)

##### `should_load(): bool`

Check WordPress version and dependencies.

**Return**: `bool` Whether component should load

### Core\Activator

Handles plugin activation, deactivation, and lifecycle management.

#### Methods

##### `activate(): void`

Plugin activation handler. Creates database tables and sets default options.

**Example**:

```php
\register_activation_hook(CF7_API_PLUGIN_FILE, [
    "SilverAssist\ContactFormToAPI\Core\Activator",
    "activate"
]);
```

##### `deactivate(): void`

Plugin deactivation handler. Cleans up scheduled events.

##### `uninstall(): void`

Plugin uninstallation handler. Removes all plugin data (called from uninstall.php).

##### `create_tables(): void`

Create database tables. Public static for test reuse.

**Example**:

```php
// In wpSetUpBeforeClass() - BEFORE data insertion
Activator::create_tables();
// This avoids MySQL implicit COMMIT issues
```

### Core\RequestLogger

Database-backed logging for API requests/responses.

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor
**Storage**: Custom database table `{prefix}cf7_api_logs`

#### Methods

##### `instance(): RequestLogger`

Get singleton instance.

##### `log(array $data): int|false`

Log request/response to database.

**Parameters**:

- `array $data` - Log data including request/response details

**Return**: `int|false` Log entry ID or false on failure

##### `get_logs(array $args = []): array`

Retrieve logs with filtering and pagination.

**Parameters**:

- `array $args` - Query arguments (per_page, page, orderby, order, search)

**Return**: `array` Array of log entries

##### `get_log(int $id): array|null`

Get single log entry by ID.

**Parameters**:

- `int $id` - Log entry ID

**Return**: `array|null` Log entry or null if not found

##### `delete_logs(array $ids): int`

Delete log entries by IDs.

**Parameters**:

- `array $ids` - Array of log IDs to delete

**Return**: `int` Number of deleted entries

##### `get_statistics(?int $form_id, ?string $date_start = null, ?string $date_end = null): array`

Get log statistics for a form with optional date filtering. Returns aggregated statistics about API calls. Can be filtered by date range to show statistics for specific time periods.

**Since**: 1.1.0 (date parameters added in 1.4.0)

**Parameters**:

- `int|null $form_id` - Form ID (0 or null for all forms)
- `string|null $date_start` - Optional start date in Y-m-d format (null for no filter)
- `string|null $date_end` - Optional end date in Y-m-d format (null for no filter)

**Return**: `array<string, int|float>` Statistics array with keys:
- `total_requests` - Total number of API requests
- `successful_requests` - Number of successful requests (HTTP 2xx)
- `failed_requests` - Number of failed requests excluding successfully retried errors
- `avg_execution_time` - Average execution time in seconds
- `max_retries` - Maximum number of retries

**Example**:

```php
$logger = new RequestLogger();

// Get all-time statistics for all forms
$all_stats = $logger->get_statistics(null);

// Get statistics for form ID 5
$form_stats = $logger->get_statistics(5);

// Get statistics for yesterday
$yesterday_stats = $logger->get_statistics(
    null,
    '2024-01-15',
    '2024-01-15'
);

// Get statistics for last 7 days
$week_stats = $logger->get_statistics(
    null,
    '2024-01-08',
    '2024-01-15'
);
```

##### `get_recent_errors(int $limit = 5, ?int $hours = null): array`

Get recent error logs with optional time filtering. Retrieves the most recent failed API requests for quick diagnostics. Excludes errors that have been successfully retried.

**Since**: 1.2.0 (hours parameter added in 1.4.0)

**Parameters**:

- `int $limit` - Maximum number of errors to retrieve (default: 5)
- `int|null $hours` - Optional time window in hours (null for all time)

**Return**: `array<int, array<string, mixed>>` Array of error log entries

**Example**:

```php
$logger = new RequestLogger();

// Get 5 most recent errors from all time
$recent_errors = $logger->get_recent_errors(5);

// Get 5 most recent errors from last 24 hours
$daily_errors = $logger->get_recent_errors(5, 24);

// Get 10 most recent errors from last hour
$hourly_errors = $logger->get_recent_errors(10, 1);
```

##### `get_request_for_retry(int $log_id): ?array`

Get request data for retrying a failed request. Retrieves complete request data needed to replay a failed API request.

**Since**: 1.2.0

**Parameters**:

- `int $log_id` - Log entry ID to retry

**Return**: `array<string, mixed>|null` Request data or null if not retryable

**Example**:

```php
$logger = RequestLogger::instance();
$request_data = $logger->get_request_for_retry(123);

if ($request_data) {
    // Request data contains: url, method, headers, body, form_id, original_log_id
    $api_client = ApiClient::instance();
    $api_client->request($request_data['url'], [
        'method'  => $request_data['method'],
        'headers' => $request_data['headers'],
        'body'    => $request_data['body'],
    ]);
}
```

##### `count_retries(int $log_id): int`

Count retries for a specific log entry. Counts how many times a specific log entry has been manually retried.

**Since**: 1.2.0

**Parameters**:

- `int $log_id` - Original log entry ID

**Return**: `int` Number of retry attempts

**Example**:

```php
$logger = RequestLogger::instance();
$retry_count = $logger->count_retries(123);

if ($retry_count >= RequestLogger::get_max_manual_retries()) {
    echo "Maximum retry limit reached";
}
```

##### `get_retries_for_log(int $log_id): array`

Get all retry entries for a log. Retrieves all manual retry attempts for a specific log entry.

**Since**: 1.3.8

**Parameters**:

- `int $log_id` - Original log entry ID

**Return**: `array<int, array{id: string, status: string, response_code: string|null, created_at: string}>` Array of retry entries

**Example**:

```php
$logger = RequestLogger::instance();
$retries = $logger->get_retries_for_log(123);

foreach ($retries as $retry) {
    echo "Retry #{$retry['id']}: {$retry['status']} at {$retry['created_at']}";
}
```

##### `has_successful_retry(int $log_id): bool`

Check if log entry has a successful manual retry. Determines if a failed request has been successfully retried.

**Since**: 1.3.8

**Parameters**:

- `int $log_id` - Original log entry ID

**Return**: `bool` True if has successful retry, false otherwise

**Example**:

```php
$logger = RequestLogger::instance();

if ($logger->has_successful_retry(123)) {
    echo "This failed request was successfully retried!";
}
```

##### `get_successful_retry_id(int $log_id): ?int`

Get ID of successful retry entry. Returns the ID of the first successful manual retry for a log entry. Used to create links from original failed entry to successful retry.

**Since**: 1.3.8

**Parameters**:

- `int $log_id` - Original log entry ID

**Return**: `int|null` ID of successful retry or null if none exists

**Example**:

```php
$logger = RequestLogger::instance();
$retry_id = $logger->get_successful_retry_id(123);

if ($retry_id) {
    $retry_url = admin_url("admin.php?page=cf7-api-logs&action=view&log_id={$retry_id}");
    echo "<a href='{$retry_url}'>View successful retry</a>";
}
```

##### `count_errors_by_resolution(): array`

Count error logs by resolution status. Returns total errors, resolved errors (with successful retry), and unresolved errors (pending).

**Since**: 1.3.14

**Return**: `array{total: int, resolved: int, unresolved: int}` Error counts by resolution status

**Example**:

```php
$logger = RequestLogger::instance();
$counts = $logger->count_errors_by_resolution();

echo "Total errors: {$counts['total']}";
echo "Resolved: {$counts['resolved']}";
echo "Unresolved: {$counts['unresolved']}";

// Display unresolved count in UI
if ($counts['unresolved'] > 0) {
    echo "<span class='error-badge'>{$counts['unresolved']} pending errors</span>";
}
```

##### `get_resolved_error_ids(): array`

Get IDs of error logs that have successful retries. Returns array of original error log IDs that have been successfully retried.

**Since**: 1.3.14

**Return**: `array<int>` Array of error log IDs with successful retries

**Example**:

```php
$logger = RequestLogger::instance();
$resolved_ids = $logger->get_resolved_error_ids();

// Filter out resolved errors from a list
$error_ids = [100, 101, 102, 103];
$unresolved_ids = array_diff($error_ids, $resolved_ids);

// Check if specific error is resolved
if (in_array(123, $resolved_ids, true)) {
    echo "Error #123 has been resolved";
}
```

##### `decrypt_log_fields(array $log): array`

Decrypt log fields for display. Decrypts encrypted log fields for viewing in admin or exports.

**Since**: 1.3.0

**Parameters**:

- `array<string, mixed> $log` - Log entry data

**Return**: `array<string, mixed>` Log entry with decrypted fields

**Example**:

```php
$logger = RequestLogger::instance();
$log = $logger->get_log(123);

// Decrypt sensitive fields for display
$decrypted_log = $logger->decrypt_log_fields($log);
echo $decrypted_log['request_data'];
```

##### `get_max_manual_retries(): int` (static)

Get maximum manual retries allowed from settings.

**Since**: 1.2.0

**Return**: `int` Maximum number of manual retry attempts allowed per log entry

**Example**:

```php
$max_retries = RequestLogger::get_max_manual_retries();
$current_retries = $logger->count_retries($log_id);

if ($current_retries < $max_retries) {
    // Allow retry
}
```

##### `get_max_retries_per_hour(): int` (static)

Get maximum retries per hour from settings. Rate limiting for manual retry operations.

**Since**: 1.2.0

**Return**: `int` Maximum number of manual retries allowed per hour

**Example**:

```php
$max_per_hour = RequestLogger::get_max_retries_per_hour();
echo "You can retry up to {$max_per_hour} requests per hour";
```

### Core\Interfaces\LoadableInterface

Interface for loadable components with priority-based loading.

#### Methods

##### `init(): void`

Initialize the component (register hooks, set up functionality).

##### `get_priority(): int`

Get component loading priority.

**Return**: `int` Priority (10=Core, 20=Services, 30=Admin, 40=Utils)

##### `should_load(): bool`

Determine if component should load (conditional loading logic).

**Return**: `bool` Whether to load component

### Services\ApiClient

Centralized HTTP client with retry logic and logging.

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor
**Priority**: 20 (Services layer)

#### Methods

##### `instance(): ApiClient`

Get singleton instance.

##### `request(string $url, array $config): array`

Execute HTTP request with full configuration.

**Parameters**:

- `string $url` - API endpoint URL
- `array $config` - Request configuration (method, headers, body, timeout, etc.)

**Return**: `array` Response data with status, body, and headers

##### `post(string $url, array $data, array $headers = []): array`

Execute POST request shortcut.

**Parameters**:

- `string $url` - API endpoint URL
- `array $data` - POST data
- `array $headers` - Optional headers

**Return**: `array` Response data

##### `get(string $url, array $params = [], array $headers = []): array`

Execute GET request shortcut.

**Parameters**:

- `string $url` - API endpoint URL
- `array $params` - Query parameters
- `array $headers` - Optional headers

**Return**: `array` Response data

##### `get_priority(): int`

Get component loading priority.

**Return**: `int` Returns 20 (Services priority)

##### `should_load(): bool`

Always returns true (service always available).

**Return**: `bool` Always true

**Features**:

- Retry logic with exponential backoff
- Request/response logging via Core\RequestLogger
- Authentication header handling (Bearer, Basic, API Key)
- Configurable timeout and SSL verification
- Error categorization and handling

### Services\CheckboxHandler

Handle CF7 checkbox values for API submission.

#### Methods

##### `is_checkbox_value(mixed $value): bool`

Check if value represents a checkbox field.

**Parameters**:

- `mixed $value` - Value to check

**Return**: `bool` True if value represents checkbox

##### `is_checkbox_checked(mixed $value): bool`

Determine if checkbox is in checked state.

**Parameters**:

- `mixed $value` - Checkbox value

**Return**: `bool` True if checkbox is checked

##### `convert_checkbox_value(mixed $value, array $options = []): mixed`

Convert checkbox value to API-friendly format.

**Parameters**:

- `mixed $value` - Original checkbox value
- `array $options` - Conversion options

**Return**: `mixed` Converted value

**Conversion Options**:

- `true_value` - Value for checked state (default: "1")
- `false_value` - Value for unchecked state (default: "0")
- `format` - Output format: "boolean", "string", "integer"

### ContactForm\Integration

Contact Form 7 integration handler implementing LoadableInterface.

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor
**Priority**: 30 (Admin priority)

#### Methods

##### `instance(): Integration`

Get singleton instance.

##### `init(): void`

Initialize CF7 integration hooks.

Registers hooks for:
- `wpcf7_editor_panels` - Add API Integration panel
- `wpcf7_before_send_mail` - Process form submission
- Admin enqueue scripts/styles

##### `render_integration_panel(): void`

Render API integration panel. Delegates to IntegrationView.

##### `get_priority(): int`

Get component loading priority.

**Return**: `int` Returns 30 (Admin priority)

##### `should_load(): bool`

Check if component should load.

**Return**: `bool` Returns `is_admin()` (admin-only functionality)

**CF7 Integration Features**:

- **Editor Tab**: Adds custom "API Integration" tab to CF7 form editor
- **Field Mapping**: Dynamic mapping between CF7 fields and API parameters
- **Multiple Formats**: GET/POST params, JSON, XML payloads
- **HTTP Methods**: GET, POST, PUT, PATCH support
- **Authentication**: Bearer tokens, Basic Auth, API keys, custom headers
- **Error Handling**: Comprehensive logging and retry mechanisms
- **Debug Mode**: Detailed logging for troubleshooting

### ContactForm\Views\IntegrationView

HTML rendering for CF7 API Integration panel.

#### Methods

##### `render_panel(...): void`

Main panel rendering with all sections.

##### `render_base_fields(): void`

Render URL and enable checkbox.

##### `render_input_type_field(): void`

Render input type selector.

##### `render_method_field(): void`

Render HTTP method selector.

##### `render_retry_config(): void`

Render retry configuration section.

##### `render_params_mapping(): void`

Render field mapping table.

##### `render_xml_template(): void`

Render XML template editor.

##### `render_json_template(): void`

Render JSON template editor.

##### `render_debug_section(): void`

Render logs and statistics display.

### Admin\RequestLogController

Admin interface controller for viewing API request/response logs.

**Uses**: `DateFilterTrait` (since 1.2.0)

#### Methods

##### `handle_page_request(): void`

Route to appropriate view based on request.

##### `show_logs_list(): void`

Display logs list table with date filtering support.

##### `show_log_detail(int $log_id): void`

Display single log detail.

**Parameters**:

- `int $log_id` - Log entry ID

##### `process_bulk_actions(): void`

Handle bulk delete/export actions.

### Admin\RequestLogTable

WP_List_Table implementation for displaying API request logs.

**Extends**: `WP_List_Table`
**Uses**: `DateFilterTrait` (since 1.2.0)

#### Methods

##### `get_date_filter_clause(): array`

Get SQL clause for date filtering based on current request parameters.

**Return**: `array{clause: string, values: array}` SQL clause and prepared statement values

##### `prepare_items(): void`

Prepare log items for display with filtering, sorting, and pagination.

##### `get_columns(): array`

Define table columns.

##### `get_sortable_columns(): array`

Define sortable columns.

##### `column_default(array $item, string $column_name): string`

Render default column content.

##### `column_status(array $item): string`

Render status column with color-coded badges.

### Admin\Views\RequestLogView

HTML rendering for request logs pages.

#### Methods

##### `render_page(RequestLogTable $table, array $statistics): void`

Main page rendering.

**Parameters**:

- `RequestLogTable $table` - WP_List_Table instance
- `array $statistics` - Statistics data

##### `render_statistics(array $stats): void`

Render statistics cards.

##### `render_date_filter(): void`

Render date filter UI with preset options and custom range picker. (Since 1.2.0)

**Features**:
- Preset filters: Today, Yesterday, Last 7 Days, Last 30 Days, This Month
- Custom date range with HTML5 date inputs
- Clear filter button when filter is active
- Persists filter selection via URL parameters

##### `render_detail(array $log): void`

Render single log detail view.

##### `render_notices(array $notices): void`

Render admin notices.

### Admin\Views\SettingsView

HTML rendering for Settings Hub documentation page.

#### Methods

##### `render_page(): void`

Main settings page rendering.

##### `render_how_to_section(): void`

Render usage instructions.

##### `render_quick_links_section(): void`

Render quick navigation links.

##### `render_status_section(): void`

Render plugin status display.

### Utils\DebugLogger

PSR-3 compliant file logger for plugin debugging.

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor
**Storage**: File at `wp-content/uploads/cf7-to-api-debug.log`

#### Methods

##### `instance(): DebugLogger`

Get singleton instance.

##### `debug(string $message, array $context = []): void`

Log debug level message.

##### `info(string $message, array $context = []): void`

Log info level message.

##### `warning(string $message, array $context = []): void`

Log warning level message.

##### `error(string $message, array $context = []): void`

Log error level message.

##### `log(string $level, string $message, array $context = []): void`

Generic log method.

**Parameters**:

- `string $level` - Log level (debug, info, warning, error)
- `string $message` - Log message
- `array $context` - Context data

### Utils\DateFilterTrait

Reusable trait for date filtering logic in SQL queries. Used by `RequestLogTable` and `RequestLogController` to avoid code duplication.

**Since**: 1.2.0

#### Methods

##### `build_date_filter_clause(string $filter, string $start = '', string $end = ''): array`

Build SQL clause for date filtering based on filter type.

**Parameters**:

- `string $filter` - Filter type: 'today', 'yesterday', '7days', '30days', 'month', 'custom'
- `string $start` - Start date for custom range (Y-m-d format)
- `string $end` - End date for custom range (Y-m-d format)

**Return**: `array{clause: string, values: array<int, string>}` SQL clause and prepared statement values

**Supported Filters**:

| Filter | SQL Generated |
|--------|---------------|
| `today` | `DATE(created_at) = CURDATE()` |
| `yesterday` | `DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)` |
| `7days` | `created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)` |
| `30days` | `created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)` |
| `month` | `MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())` |
| `custom` | `DATE(created_at) BETWEEN %s AND %s` |

**Example**:

```php
use SilverAssist\ContactFormToAPI\Utils\DateFilterTrait;

class MyClass {
    use DateFilterTrait;
    
    public function get_filtered_data(): array {
        $params = $this->get_date_filter_params();
        $filter = $this->build_date_filter_clause(
            $params['filter'],
            $params['start'],
            $params['end']
        );
        
        // Use $filter['clause'] and $filter['values'] in SQL query
    }
}
```

##### `build_custom_date_range_clause(string $start, string $end): array`

Build clause for custom date range.

**Parameters**:

- `string $start` - Start date (Y-m-d format)
- `string $end` - End date (Y-m-d format)

**Return**: `array{clause: string, values: array<int, string>}` SQL clause and values

##### `is_valid_date_format(string $date): bool`

Validate date format (Y-m-d).

**Parameters**:

- `string $date` - Date string to validate

**Return**: `bool` True if valid, false otherwise

##### `get_date_filter_params(): array`

Get sanitized date filter parameters from $_GET request.

**Return**: `array{filter: string, start: string, end: string}` Sanitized filter parameters

### Utils\SensitiveDataPatterns

Centralized class for managing sensitive field patterns. Used by `RequestLogger` and `ExportService` for data sanitization.

**Since**: 1.2.0

#### Methods

##### `get_header_patterns(): array`

Get patterns for sensitive HTTP headers.

**Return**: `array` Array of header patterns (Authorization, API keys, etc.)

##### `get_data_patterns(): array`

Get patterns for sensitive data fields.

**Return**: `array` Array of field patterns (passwords, tokens, secrets, etc.)

##### `is_sensitive_header(string $header): bool`

Check if a header name is sensitive.

**Parameters**:

- `string $header` - Header name to check

**Return**: `bool` True if sensitive

##### `is_sensitive_field(string $field): bool`

Check if a field name is sensitive.

**Parameters**:

- `string $field` - Field name to check

**Return**: `bool` True if sensitive

### Utils\StringHelper

String manipulation utilities for field mapping.

#### Methods

##### `kebab_to_camel(string $string): string`

Convert kebab-case to camelCase.

**Parameters**:

- `string $string` - Input string in kebab-case

**Return**: `string` String in camelCase

##### `camel_to_kebab(string $string): string`

Convert camelCase to kebab-case.

**Parameters**:

- `string $string` - Input string in camelCase

**Return**: `string` String in kebab-case

##### `fields_match(string $field1, string $field2): bool`

Case-insensitive field comparison.

**Parameters**:

- `string $field1` - First field name
- `string $field2` - Second field name

**Return**: `bool` True if fields match

## Constants

### Plugin Information

```php
CF7_API_VERSION           // "1.2.0"
CF7_API_PLUGIN_FILE       // Main plugin file path (__FILE__)
CF7_API_PLUGIN_DIR        // Plugin directory path (plugin_dir_path(__FILE__))
CF7_API_PLUGIN_URL        // Plugin URL (plugin_dir_url(__FILE__))
CF7_API_PLUGIN_BASENAME   // Plugin basename (plugin_basename(__FILE__))
```

### Requirements

```php
CF7_API_MIN_PHP_VERSION   // "8.2"
CF7_API_MIN_WP_VERSION    // "6.5"
```

### Text Domain

The text domain `"contact-form-to-api"` should be used as a **literal string** in all i18n functions:

```php
\__("Text", "contact-form-to-api");           // Correct
\esc_html__("Text", "contact-form-to-api");   // Correct
```

> **Note**: WordPress i18n extraction tools require literal strings, not constants or variables.

### Usage Examples

```php
// Get plugin directory
$plugin_dir = CF7_API_PLUGIN_DIR;

// Get plugin URL
$plugin_url = CF7_API_PLUGIN_URL;

// Load asset
\wp_enqueue_script(
    "cf7-api-admin",
    CF7_API_PLUGIN_URL . "assets/js/admin.js",
    ["jquery"],
    CF7_API_VERSION,
    true
);

// Check plugin version
if (version_compare(CF7_API_VERSION, "2.0.0", ">=")) {
    // Use new API features
}
```

## Integration Examples

### Custom API Handler

```php
/**
 * Custom API integration
 */
function custom_cf7_api_integration() {
    \add_filter("cf7_api_request_data", function($data, $contact_form, $config) {
        // Add custom authentication
        $data["api_key"] = \get_option("custom_api_key");
        
        // Transform data structure
        $data["contact"] = [
            "name" => $data["your-name"],
            "email" => $data["your-email"],
        ];
        unset($data["your-name"], $data["your-email"]);
        
        return $data;
    }, 10, 3);
    
    \add_action("cf7_api_after_send", function($response, $data, $contact_form) {
        if (!\is_wp_error($response)) {
            // Store lead ID in custom table
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . "custom_leads",
                [
                    "form_id" => $contact_form->id(),
                    "lead_id" => $response["body"]["lead_id"],
                    "created_at" => \current_time("mysql")
                ]
            );
        }
    }, 10, 3);
}
\add_action("plugins_loaded", "custom_cf7_api_integration");
```

### Conditional API Sending

```php
/**
 * Send to different APIs based on form field value
 */
\add_filter("cf7_api_request_args", function($args, $data, $contact_form) {
    // Get form metadata
    $department = $data["department"] ?? "general";
    
    // Route to different endpoints
    $endpoints = [
        "sales" => "https://api.example.com/sales",
        "support" => "https://api.example.com/support",
        "general" => "https://api.example.com/general"
    ];
    
    if (isset($endpoints[$department])) {
        // Override API URL
        $args["url"] = $endpoints[$department];
    }
    
    return $args;
}, 10, 3);
```

### Error Notification

```php
/**
 * Send admin notification on API errors
 */
\add_action("cf7_api_request_error", function($error, $data, $contact_form) {
    $admin_email = \get_option("admin_email");
    
    \wp_mail(
        $admin_email,
        "CF7 API Error: " . $contact_form->title(),
        sprintf(
            "API request failed:\n\nError: %s\n\nForm Data:\n%s",
            $error->get_error_message(),
            print_r($data, true)
        )
    );
}, 10, 3);
```

### Custom Field Mapping

```php
/**
 * Dynamic field mapping based on form
 */
\add_filter("cf7_api_field_mapping", function($mapping, $contact_form) {
    // Different mapping for different forms
    switch ($contact_form->id()) {
        case 123: // Contact form
            $mapping = [
                "your-name" => "full_name",
                "your-email" => "email_address",
                "your-message" => "message_body"
            ];
            break;
            
        case 456: // Registration form
            $mapping = [
                "first-name" => "first_name",
                "last-name" => "last_name",
                "email" => "email",
                "phone" => "phone_number"
            ];
            break;
    }
    
    return $mapping;
}, 10, 2);
```

### Response Handling

```php
/**
 * Process API response and update CF7 submission
 */
\add_filter("cf7_api_response_data", function($response_data, $raw_response, $contact_form) {
    // Extract lead ID from response
    if (isset($response_data["lead"]["id"])) {
        $lead_id = $response_data["lead"]["id"];
        
        // Store in form metadata
        \update_post_meta(
            $contact_form->id(),
            "last_lead_id",
            $lead_id
        );
        
        // Add to response for use in CF7 mail
        $response_data["cf7_lead_id"] = $lead_id;
    }
    
    return $response_data;
}, 10, 3);
```

## Testing

### Test Helpers

The plugin provides test helpers for WordPress Test Suite:

- **`Tests\Helpers\TestCase`**: Base test case class
- **`Tests\Helpers\CF7TestCase`**: CF7-specific test utilities

### Unit Testing

```php
use SilverAssist\ContactFormToAPI\Tests\Helpers\CF7TestCase;

class CustomIntegrationTest extends CF7TestCase {
    public function test_custom_api_filter(): void {
        $form = $this->createMockCF7Form();
        $data = $this->createMockSubmissionData();
        
        // Apply filter
        $filtered_data = \apply_filters(
            "cf7_api_request_data",
            $data,
            $form,
            []
        );
        
        $this->assertArrayHasKey("custom_field", $filtered_data);
    }
}
```

### Database Table Testing

```php
use SilverAssist\ContactFormToAPI\Core\Activator;
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

class RequestLoggerTest extends TestCase {
    public static function wpSetUpBeforeClass(): void {
        // Create tables BEFORE any data insertion
        Activator::create_tables();
    }
    
    public function test_log_creation(): void {
        $logger = RequestLogger::instance();
        $log_id = $logger->log([
            "form_id" => 123,
            "url" => "https://api.example.com/endpoint",
            "status" => 200
        ]);
        
        $this->assertIsInt($log_id);
    }
}
```

## Required Packages

The plugin relies on these SilverAssist packages:

- **`silverassist/wp-github-updater ^1.2`**: Automatic updates from GitHub releases
- **`silverassist/wp-settings-hub ^1.1`**: Unified settings interface

## Resources

- [Contact Form 7 Documentation](https://contactform7.com/docs/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Hooks Reference](https://developer.wordpress.org/reference/hooks/)
- [SilverAssist Standards](https://gist.github.com/miguelcolmenares/227180b8983df6ad4ec3ced113677853)

## Support

For API integration questions:

- GitHub Issues: [Report an issue](https://github.com/SilverAssist/contact-form-to-api/issues)
- Documentation: [Plugin Wiki](https://github.com/SilverAssist/contact-form-to-api/wiki)
- Email: info@silverassist.com
