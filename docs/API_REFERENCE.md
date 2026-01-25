# API Reference

Complete API reference for Contact Form 7 to API plugin, documenting all hooks, filters, classes, and public APIs for developers.

## Table of Contents

- [Plugin Architecture](#plugin-architecture)
- [Hooks and Filters](#hooks-and-filters)
- [Legacy Hook Compatibility](#legacy-hook-compatibility)
- [Classes](#classes)
- [Constants](#constants)
- [Integration Examples](#integration-examples)

---

## Plugin Architecture

### Namespace Structure

```
SilverAssist\ContactFormToAPI\
├── Core\                           # Bootstrap & lifecycle
│   ├── Plugin.php                 # Main plugin controller
│   ├── Activator.php              # Lifecycle management
│   ├── RequestLogger.php          # API request logging facade
│   └── Interfaces\
│       └── LoadableInterface.php  # Component contract
│
├── Config\                         # Configuration
│   └── Settings.php               # Plugin settings
│
├── Model\                          # Domain models
│   ├── LogEntry.php               # Log entry model
│   ├── FormSettings.php           # Form settings model
│   ├── ApiResponse.php            # API response model
│   └── Statistics.php             # Statistics model
│
├── Repository\                     # Data access contracts
│   ├── LogRepositoryInterface.php
│   └── SettingsRepositoryInterface.php
│
├── Service\                        # Business logic
│   ├── Logging\                   # Log management
│   │   ├── LogWriter.php
│   │   ├── LogReader.php
│   │   ├── LogStatistics.php
│   │   └── RetryManager.php
│   ├── Api\
│   │   └── ApiClient.php          # HTTP client
│   ├── Security\
│   │   ├── EncryptionService.php
│   │   └── SensitiveDataPatterns.php
│   ├── Export\
│   │   └── ExportService.php
│   ├── Migration\
│   │   └── MigrationService.php
│   ├── Notification\
│   │   └── EmailAlertService.php
│   └── ContactForm\
│       └── SubmissionProcessor.php
│
├── Controller\                     # Request handling
│   ├── Admin\
│   │   ├── DashboardController.php
│   │   ├── LogsController.php
│   │   └── SettingsController.php
│   └── ContactForm\
│       └── SubmissionController.php
│
├── View\                           # Presentation
│   ├── Admin\
│   │   ├── Logs\
│   │   │   └── Partials\
│   │   └── Settings\
│   │       └── Partials\
│   └── ContactForm\
│       └── IntegrationView.php
│
├── Infrastructure\                 # WordPress integration
│   ├── ListTable\
│   │   └── RequestLogTable.php
│   ├── Widget\
│   │   └── DashboardWidget.php
│   └── Handler\
│       └── CheckboxHandler.php
│
├── Exception\                      # Custom exceptions
│   ├── DecryptionException.php
│   ├── ApiException.php
│   └── ValidationException.php
│
└── Utils\                          # Utilities
    ├── DebugLogger.php
    ├── DateFilterTrait.php
    └── StringHelper.php
```

### Component Loading

All components implement `LoadableInterface` with priority-based loading:

- **Priority 10**: Core (Plugin, Activator, RequestLogger)
- **Priority 20**: Services (ApiClient, LogWriter, etc.)
- **Priority 30**: Admin & ContactForm (Controllers, Views)
- **Priority 40**: Utilities (DebugLogger, StringHelper)

### Dual Logger Architecture

| Logger | Purpose | Storage |
|--------|---------|---------|
| `Core\RequestLogger` | API request/response tracking | Database |
| `Utils\DebugLogger` | Plugin debugging | File |

---

## Hooks and Filters

### Action Hooks

#### `cf7_api_before_send`

Fires before sending data to API endpoint.

**Parameters**:

- `array $data` - Form submission data
- `\WPCF7_ContactForm $contact_form` - CF7 form object
- `array $config` - API configuration

```php
\add_action("cf7_api_before_send", function($data, $contact_form, $config) {
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

```php
\add_action("cf7_api_after_send", function($response, $data, $contact_form) {
    if (\is_wp_error($response)) {
        \error_log("API Error: " . $response->get_error_message());
    }
}, 10, 3);
```

#### `cf7_api_request_error`

Fires when API request fails.

**Parameters**:

- `\WP_Error $error` - Error object
- `array $data` - Form data that failed to send
- `\WPCF7_ContactForm $contact_form` - CF7 form object

```php
\add_action("cf7_api_request_error", function($error, $data, $contact_form) {
    \do_action("custom_error_logger", [
        "type" => "cf7_api",
        "message" => $error->get_error_message(),
        "data" => $data
    ]);
}, 10, 3);
```

#### `cf7_api_before_send_to_api`

Action fired before sending form data to the API endpoint.

**Parameters**:

- `array $record` - The prepared record data including URL and fields

```php
\add_action("cf7_api_before_send_to_api", function($record) {
    \error_log("Sending to API: " . $record["url"]);
}, 10, 1);
```

#### `cf7_api_after_send_to_api`

Action fired after receiving API response.

**Parameters**:

- `array $record` - The sent record data
- `array|WP_Error $response` - API response or error

```php
\add_action("cf7_api_after_send_to_api", function($record, $response) {
    if (!\is_wp_error($response)) {
        $code = \wp_remote_retrieve_response_code($response);
        \error_log("API Response Code: " . $code);
    }
}, 10, 2);
```

### Filter Hooks

#### `cf7_api_request_data`

Filter form data before sending to API.

**Parameters**:

- `array $data` - Form submission data
- `\WPCF7_ContactForm $contact_form` - CF7 form object
- `array $config` - API configuration

**Return**: `array` Modified form data

```php
\add_filter("cf7_api_request_data", function($data, $contact_form, $config) {
    $data["submitted_at"] = date("Y-m-d H:i:s");
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

```php
\add_filter("cf7_api_request_args", function($args, $data, $contact_form) {
    $args["headers"]["X-Custom-Header"] = "Custom Value";
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

```php
\add_filter("cf7_api_response_data", function($response_data, $raw_response, $contact_form) {
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

```php
\add_filter("cf7_api_field_mapping", function($mapping, $contact_form) {
    $mapping["user_ip"] = "client_ip_address";
    return $mapping;
}, 10, 2);
```

#### `cf7_api_debug_enabled`

Filter debug mode status.

**Parameters**:

- `bool $enabled` - Whether debug is enabled
- `\WPCF7_ContactForm $contact_form` - CF7 form object

**Return**: `bool` Debug enabled status

```php
\add_filter("cf7_api_debug_enabled", function($enabled, $contact_form) {
    if ($contact_form->id() === 123) {
        return true;
    }
    return $enabled;
}, 10, 2);
```

#### `cf7_api_collect_mail_tags`

Filter form tags before processing in API integration panel.

**Parameters**:

- `array $tags` - Array of WPCF7_FormTag objects

**Return**: `array` Modified array of form tags

```php
\add_filter("cf7_api_collect_mail_tags", function($tags) {
    $custom_tag = (object) [
        'type' => 'text',
        'name' => 'api_timestamp',
        'values' => []
    ];
    $tags[] = $custom_tag;
    return $tags;
}, 10, 1);
```

#### `cf7_api_set_record_value`

Filter individual field values before adding to the record.

**Parameters**:

- `mixed $value` - The field value
- `string $api_field_name` - The API field name

**Return**: `mixed` Modified field value

```php
\add_filter("cf7_api_set_record_value", function($value, $api_field_name) {
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

```php
\add_filter("cf7_api_create_record", function($record, $submitted_data, $data_map, $type, $template) {
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

```php
\add_filter("cf7_api_post_args", function($args) {
    $args["headers"]["X-API-Version"] = "2.0";
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

```php
\add_filter("cf7_api_post_url", function($url) {
    return \add_query_arg("version", "2", $url);
}, 10, 1);
```

---

## Legacy Hook Compatibility

This plugin provides full backward compatibility with the legacy "Contact Form 7 to API" plugin by Query Solutions (`cf7-to-api`).

### Hook Mapping Table

| Legacy Hook (Query Solutions) | New Hook (Silver Assist) | Type |
|------------------------------|--------------------------|------|
| `qs_cf7_collect_mail_tags` | `cf7_api_collect_mail_tags` | Filter |
| `qs_cf7_api_before_sent_to_api` | `cf7_api_before_send_to_api` | Action |
| `qs_cf7_api_after_sent_to_api` | `cf7_api_after_send_to_api` | Action |
| `set_record_value` | `cf7_api_set_record_value` | Filter |
| `cf7api_create_record` | `cf7_api_create_record` | Filter |
| `qs_cf7_api_get_args` | `cf7_api_get_args` | Filter |
| `qs_cf7_api_post_args` | `cf7_api_post_args` | Filter |
| `qs_cf7_api_get_url` | `cf7_api_get_url` | Filter |
| `qs_cf7_api_post_url` | `cf7_api_post_url` | Filter |

### How Legacy Compatibility Works

The plugin registers bridge hooks at priority 5 that automatically call legacy hooks:

1. **Existing code continues to work**: Any theme or plugin using `qs_cf7_*` hooks will function without changes
2. **Priority order**: Legacy hooks run first (priority 5), then new hooks (priority 10)
3. **No conflicts**: Both hook systems can coexist safely

### Migration Guide

```php
// Before (legacy)
\add_action("qs_cf7_api_before_sent_to_api", "my_before_api_handler", 10, 1);
\add_filter("set_record_value", "my_value_filter", 10, 2);

// After (new)
\add_action("cf7_api_before_send_to_api", "my_before_api_handler", 10, 1);
\add_filter("cf7_api_set_record_value", "my_value_filter", 10, 2);
```

---

## Classes

### Core\Plugin

Main plugin controller implementing singleton pattern.

**Implements**: `LoadableInterface`

```php
use SilverAssist\ContactFormToAPI\Core\Plugin;

$plugin = Plugin::instance();
```

#### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `instance()` | `Plugin` | Get singleton instance |
| `init()` | `void` | Initialize plugin |
| `load_components()` | `void` | Load all plugin components |
| `get_priority()` | `int` | Returns 10 (Core priority) |
| `should_load()` | `bool` | Check dependencies |

### Core\Activator

Handles plugin activation, deactivation, and lifecycle.

#### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `activate()` | `void` | Plugin activation handler |
| `deactivate()` | `void` | Plugin deactivation handler |
| `uninstall()` | `void` | Plugin uninstallation handler |
| `create_tables()` | `void` | Create database tables |

### Core\RequestLogger

Database-backed logging for API requests/responses.

**Implements**: `LoadableInterface`
**Storage**: Custom database table `{prefix}cf7_api_logs`

```php
use SilverAssist\ContactFormToAPI\Core\RequestLogger;

$logger = RequestLogger::instance();
$log_id = $logger->log($data);
$logs = $logger->get_logs(['per_page' => 20]);
```

#### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `instance()` | `RequestLogger` | Get singleton instance |
| `log(array $data)` | `int\|false` | Log request/response |
| `get_logs(array $args)` | `array` | Retrieve logs with filtering |
| `get_log(int $id)` | `array\|null` | Get single log entry |
| `delete_logs(array $ids)` | `int` | Delete log entries |
| `get_statistics(?int $form_id, ?string $start, ?string $end)` | `array` | Get statistics |
| `get_recent_errors(int $limit, ?int $hours)` | `array` | Get recent errors |
| `get_request_for_retry(int $log_id)` | `?array` | Get request data for retry |
| `count_retries(int $log_id)` | `int` | Count retries for log |
| `has_successful_retry(int $log_id)` | `bool` | Check for successful retry |
| `decrypt_log_fields(array $log)` | `array` | Decrypt log fields |

### Service\Logging\LogWriter

Create and update log entries.

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;

$writer = LogWriter::instance();
$log_id = $writer->save($log_entry);
```

### Service\Logging\LogReader

Query and retrieve log entries.

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;

$reader = LogReader::instance();
$logs = $reader->get_logs(['status' => 'success']);
```

### Service\Logging\LogStatistics

Calculate log statistics.

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;

$stats = LogStatistics::instance();
$metrics = $stats->get_overview();
```

### Service\Logging\RetryManager

Manage retry operations.

```php
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;

$retry = RetryManager::instance();
$retry_id = $retry->create_retry_entry($original_log_id);
```

### Service\Api\ApiClient

Centralized HTTP client with retry logic.

**Implements**: `LoadableInterface`

```php
use SilverAssist\ContactFormToAPI\Service\Api\ApiClient;

$client = ApiClient::instance();
$response = $client->post($url, $data, $headers);
```

#### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `instance()` | `ApiClient` | Get singleton instance |
| `request(string $url, array $config)` | `array` | Execute HTTP request |
| `post(string $url, array $data, array $headers)` | `array` | POST request shortcut |
| `get(string $url, array $params, array $headers)` | `array` | GET request shortcut |

### Service\Security\EncryptionService

Data encryption service.

```php
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;

$encryption = EncryptionService::instance();
$encrypted = $encryption->encrypt($data);
$decrypted = $encryption->decrypt($encrypted);
```

### Controller\ContactForm\SubmissionController

CF7 submission controller handling hook registration.

**Implements**: `LoadableInterface`

```php
use SilverAssist\ContactFormToAPI\Controller\ContactForm\SubmissionController;

$controller = SubmissionController::instance();
```

### Service\ContactForm\SubmissionProcessor

Form submission processing service.

```php
use SilverAssist\ContactFormToAPI\Service\ContactForm\SubmissionProcessor;

$processor = SubmissionProcessor::instance();
```

### Utils\DebugLogger

PSR-3 compliant file logger.

**Storage**: `wp-content/uploads/cf7-to-api-debug.log`

```php
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

$logger = DebugLogger::instance();
$logger->debug("Debug message", ['context' => 'value']);
$logger->error("Error message");
```

### Utils\DateFilterTrait

Reusable trait for date filtering in SQL queries.

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
        // Use $filter['clause'] and $filter['values'] in SQL
    }
}
```

**Supported Filters**: `today`, `yesterday`, `7days`, `30days`, `month`, `custom`

### Utils\StringHelper

String manipulation utilities.

```php
use SilverAssist\ContactFormToAPI\Utils\StringHelper;

$camel = StringHelper::kebab_to_camel('my-field-name'); // myFieldName
$kebab = StringHelper::camel_to_kebab('myFieldName');   // my-field-name
$match = StringHelper::fields_match('Email', 'email');   // true
```

---

## Constants

### Plugin Information

```php
CF7_API_VERSION           // "2.0.0"
CF7_API_PLUGIN_FILE       // Main plugin file path
CF7_API_PLUGIN_DIR        // Plugin directory path
CF7_API_PLUGIN_URL        // Plugin URL
CF7_API_PLUGIN_BASENAME   // Plugin basename
```

### Requirements

```php
CF7_API_MIN_PHP_VERSION   // "8.2"
CF7_API_MIN_WP_VERSION    // "6.5"
```

### Usage

```php
\wp_enqueue_script(
    "cf7-api-admin",
    CF7_API_PLUGIN_URL . "assets/js/admin.js",
    ["jquery"],
    CF7_API_VERSION,
    true
);
```

---

## Integration Examples

### Custom API Handler

```php
function custom_cf7_api_integration() {
    \add_filter("cf7_api_request_data", function($data, $contact_form, $config) {
        $data["api_key"] = \get_option("custom_api_key");
        $data["contact"] = [
            "name" => $data["your-name"],
            "email" => $data["your-email"],
        ];
        unset($data["your-name"], $data["your-email"]);
        return $data;
    }, 10, 3);
    
    \add_action("cf7_api_after_send", function($response, $data, $contact_form) {
        if (!\is_wp_error($response)) {
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

### Conditional API Routing

```php
\add_filter("cf7_api_request_args", function($args, $data, $contact_form) {
    $department = $data["department"] ?? "general";
    
    $endpoints = [
        "sales" => "https://api.example.com/sales",
        "support" => "https://api.example.com/support",
        "general" => "https://api.example.com/general"
    ];
    
    if (isset($endpoints[$department])) {
        $args["url"] = $endpoints[$department];
    }
    
    return $args;
}, 10, 3);
```

### Error Notification

```php
\add_action("cf7_api_request_error", function($error, $data, $contact_form) {
    \wp_mail(
        \get_option("admin_email"),
        "CF7 API Error: " . $contact_form->title(),
        sprintf(
            "API request failed:\n\nError: %s\n\nForm Data:\n%s",
            $error->get_error_message(),
            print_r($data, true)
        )
    );
}, 10, 3);
```

### Dynamic Field Mapping

```php
\add_filter("cf7_api_field_mapping", function($mapping, $contact_form) {
    switch ($contact_form->id()) {
        case 123:
            $mapping = [
                "your-name" => "full_name",
                "your-email" => "email_address",
            ];
            break;
        case 456:
            $mapping = [
                "first-name" => "first_name",
                "last-name" => "last_name",
            ];
            break;
    }
    return $mapping;
}, 10, 2);
```

---

## Testing

### Test Helpers

```php
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Tests\Helpers\CF7TestCase;
```

### Database Table Testing

```php
use SilverAssist\ContactFormToAPI\Core\Activator;

class RequestLoggerTest extends TestCase {
    public static function wpSetUpBeforeClass(): void {
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

---

## Support

- **GitHub Issues**: [Report an issue](https://github.com/SilverAssist/contact-form-to-api/issues)
- **Documentation**: [Plugin Wiki](https://github.com/SilverAssist/contact-form-to-api/wiki)
- **Email**: <info@silverassist.com>

---

**Document Version**: 2.0.0  
**Last Updated**: January 25, 2026  
**Maintained By**: Silver Assist Development Team
