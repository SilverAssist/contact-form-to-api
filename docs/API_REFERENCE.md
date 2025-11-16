# API Reference

Complete API reference for Contact Form 7 to API plugin, documenting all hooks, filters, classes, and public APIs for developers.

## 📋 Table of Contents

- [Plugin Architecture](#plugin-architecture)
- [Hooks and Filters](#hooks-and-filters)
- [Classes](#classes)
- [Constants](#constants)
- [Integration Examples](#integration-examples)

## Plugin Architecture

### Namespace Structure

```
SilverAssist\ContactFormToAPI\
├── Core\
│   ├── Interfaces\
│   │   └── LoadableInterface
│   ├── Activator
│   ├── Plugin
│   └── Updater
└── ContactForm\
    └── Integration
```

### Component Loading

All components implement `LoadableInterface` with priority-based loading:

- **Priority 10**: Core components (Plugin, Activator)
- **Priority 20**: Services (API handlers)
- **Priority 30**: Admin components (Integration)
- **Priority 40**: Utilities

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

### Legacy Hooks (Backward Compatibility)

These hooks are maintained for backward compatibility with Paubox CF7 Integration:

- `paubox_cf7_before_send` → Use `cf7_api_before_send`
- `paubox_cf7_after_send` → Use `cf7_api_after_send`
- `paubox_cf7_request_data` → Use `cf7_api_request_data`

## Classes

### Core\Plugin

Main plugin controller implementing singleton pattern.

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

Initialize plugin components.

##### `get_version(): string`

Get current plugin version.

**Return**: `string` Version number

**Example**:

```php
$version = Plugin::instance()->get_version();
// Returns: "1.0.0"
```

### Core\Activator

Handles plugin activation, deactivation, and lifecycle.

#### Methods

##### `activate(): void`

Plugin activation handler.

**Example**:

```php
\register_activation_hook(CONTACT_FORM_TO_API_FILE, [
    'SilverAssist\ContactFormToAPI\Core\Activator',
    'activate'
]);
```

##### `deactivate(): void`

Plugin deactivation handler.

##### `create_tables(): void`

Create database tables (if needed).

### Core\Updater

Manages GitHub-based plugin updates.

#### Methods

##### `init(): void`

Initialize GitHub updater integration.

##### `check_for_update(): void`

Check for available updates.

### ContactForm\Integration

Contact Form 7 integration handler.

#### Methods

##### `instance(): Integration`

Get singleton instance.

##### `init(): void`

Initialize CF7 integration hooks.

##### `add_editor_panel(array $panels): array`

Add API Integration panel to CF7 editor.

**Parameters**:

- `array $panels` - Existing editor panels

**Return**: `array` Modified panels array

##### `save_form_settings(WPCF7_ContactForm $contact_form): void`

Save API settings for form.

**Parameters**:

- `\WPCF7_ContactForm $contact_form` - CF7 form object

##### `send_to_api(WPCF7_ContactForm $contact_form): void`

Send form submission to configured API.

**Parameters**:

- `\WPCF7_ContactForm $contact_form` - CF7 form object

### Core\Interfaces\LoadableInterface

Interface for loadable components.

#### Methods

##### `init(): void`

Initialize the component.

##### `get_priority(): int`

Get component loading priority.

**Return**: `int` Priority (10-40)

##### `should_load(): bool`

Determine if component should load.

**Return**: `bool` Whether to load component

## Constants

### Plugin Information

```php
CONTACT_FORM_TO_API_VERSION        // "1.0.0"
CONTACT_FORM_TO_API_FILE           // Main plugin file path
CONTACT_FORM_TO_API_DIR            // Plugin directory path
CONTACT_FORM_TO_API_URL            // Plugin URL
CONTACT_FORM_TO_API_BASENAME       // Plugin basename
CONTACT_FORM_TO_API_TEXT_DOMAIN    // "contact-form-to-api"
```

### Requirements

```php
CONTACT_FORM_TO_API_MIN_PHP_VERSION  // "8.2"
CONTACT_FORM_TO_API_MIN_WP_VERSION   // "6.5"
```

### Usage Examples

```php
// Get plugin directory
$plugin_dir = CONTACT_FORM_TO_API_DIR;

// Get plugin URL
$plugin_url = CONTACT_FORM_TO_API_URL;

// Load asset
\wp_enqueue_script(
    "cf7-api-admin",
    CONTACT_FORM_TO_API_URL . "assets/js/admin.js",
    ["jquery"],
    CONTACT_FORM_TO_API_VERSION,
    true
);
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
