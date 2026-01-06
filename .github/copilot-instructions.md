# Contact Form 7 to API - Copilot Instructions

## 📋 Project Overview
This is a WordPress plugin that integrates Contact Form 7 with external APIs, allowing form submissions to be sent to custom API endpoints with advanced configuration options. The plugin follows **SilverAssist WordPress Plugin Development Standards** and uses a **form-specific configuration approach** with no global admin panels.

## 🏗️ SilverAssist Architecture Standards

This plugin follows the comprehensive [SilverAssist WordPress Plugin Development Standards](https://gist.github.com/miguelcolmenares/227180b8983df6ad4ec3ced113677853).

### Key Standards
- **Namespace**: `SilverAssist\ContactFormToAPI\`
- **Directory**: `includes/` (PSR-4 autoloading)
- **PHP Version**: 8.2+ (modern PHP features required)
- **WordPress Version**: 6.5+ minimum
- **Code Quality**: PHPCS (WordPress-Extra), PHPStan Level 8, PHPUnit
- **Testing**: WordPress Test Suite with `WP_UnitTestCase`
- **Patterns**: LoadableInterface, Singleton, Component Loaders
- **CI/CD**: GitHub Actions with reusable workflows

### Required SilverAssist Packages
- `silverassist/wp-github-updater ^1.2` - Automatic updates from GitHub releases
- `silverassist/wp-settings-hub ^1.1` - Unified settings interface

## 🎯 Plugin-Specific Approach
- **Direct CF7 Integration**: All configuration happens at the form level via CF7 editor tabs
- **No Global Admin Panel**: Settings are form-specific, not plugin-wide
- **LoadableInterface Pattern**: All components implement priority-based loading
- **Singleton Pattern**: Main classes use singleton with `instance()` method
- **Plugin Constants**: All configuration via `CF7_API_*` constants

### File Structure
```
contact-form-to-api/
├── .github/
│   ├── workflows/              # GitHub Actions workflows
│   │   ├── quality-checks.yml # Reusable quality workflow
│   │   ├── ci.yml            # PR validation
│   │   ├── release.yml       # Automated releases
│   │   └── dependency-updates.yml
│   └── copilot-instructions.md
├── assets/
│   ├── css/
│   │   ├── admin.css              # CF7 admin styles
│   │   ├── request-log.css        # Request logs page styles
│   │   └── settings-page.css      # Settings Hub page styles
│   └── js/
│       ├── admin.js               # CF7 admin scripts (ES6+)
│       ├── admin-check-updates.js # GitHub updater button
│       └── api-log-admin.js       # Request logs interactions
├── docs/                          # Documentation (user-facing)
│   ├── API_REFERENCE.md          # Hooks, filters, and developer reference
│   └── USER_GUIDE.md             # Complete user documentation
├── includes/                      # Source code (PSR-4)
│   ├── Admin/                     # Priority 25-30 - Admin components
│   │   ├── Loader.php            # Admin component loader
│   │   ├── SettingsPage.php      # Settings Hub registration (Priority 25)
│   │   ├── GlobalSettingsController.php # Form handling only (Priority 26)
│   │   ├── RequestLogController.php # Request logs admin controller
│   │   ├── RequestLogTable.php   # WP_List_Table for logs
│   │   └── Views/                # Separated view templates
│   │       ├── RequestLogView.php    # Request log HTML rendering
│   │       ├── SettingsView.php      # Main settings page (renders ALL settings)
│   │       └── DashboardWidgetView.php # Dashboard widget HTML
│   ├── ContactForm/              # Priority 30 - CF7 Integration
│   │   ├── Integration.php       # CF7 hooks and panel logic
│   │   └── Views/
│   │       └── IntegrationView.php   # CF7 panel HTML rendering
│   ├── Core/                     # Priority 10 - Core components
│   │   ├── Interfaces/
│   │   │   └── LoadableInterface.php # Component contract
│   │   ├── Activator.php         # Lifecycle management
│   │   ├── EncryptionService.php # libsodium encryption for logs
│   │   ├── RequestLogger.php     # API request/response DB logger
│   │   ├── Settings.php          # Global settings singleton (stores all plugin settings)
│   │   ├── SensitiveDataPatterns.php # Sensitive data detection patterns
│   │   └── Plugin.php            # Main plugin controller
│   ├── Exceptions/               # Custom exception classes
│   │   └── DecryptionException.php # Thrown when decryption fails
│   ├── Services/                 # Priority 20 - Business logic services
│   │   ├── Loader.php            # Services component loader
│   │   ├── ApiClient.php         # HTTP client with retry logic
│   │   ├── CheckboxHandler.php   # Checkbox value processing
│   │   ├── EmailAlertService.php # Email alerts for high error rates
│   │   ├── ExportService.php     # Log export (CSV/JSON)
│   │   └── MigrationService.php  # Legacy log encryption migration
│   └── Utils/                    # Priority 40 - Utility classes
│       ├── DateFilterTrait.php   # Reusable date filtering for SQL queries
│       ├── DebugLogger.php       # PSR-3 file logger for debugging
│       └── StringHelper.php      # String manipulation utilities
├── languages/                     # Translation files
│   ├── contact-form-to-api.pot
│   └── README.md
├── scripts/                       # Build and quality scripts
│   ├── run-quality-checks.sh
│   ├── build-release.sh
│   ├── check-versions.sh
│   ├── install-wp-tests.sh
│   ├── pre-push-checks.sh
│   └── update-version-simple.sh
├── tests/                         # WordPress Test Suite
│   ├── bootstrap.php
│   ├── Helpers/
│   │   ├── TestCase.php
│   │   └── CF7TestCase.php
│   ├── Unit/
│   │   ├── PluginTest.php
│   │   └── LoggerTest.php
│   ├── Integration/
│   │   └── WordPressIntegrationTest.php
│   └── ContactForm/
│       └── IntegrationTest.php
├── contact-form-to-api.php       # Main plugin file
├── composer.json                 # Dependencies
├── phpcs.xml                     # PHPCS configuration
├── phpstan.neon                  # PHPStan configuration
├── phpunit.xml                   # PHPUnit configuration
├── CHANGELOG.md
├── CONTRIBUTING.md
└── README.md
```

## Architecture Overview

### Component Priority System (LoadableInterface)
All components implement `LoadableInterface` with priority-based loading:
- **Priority 10**: Core (Plugin, Activator, EncryptionService)
- **Priority 20**: Services (ApiClient, CheckboxHandler, MigrationService)
- **Priority 30**: Admin & ContactForm (SettingsPage, RequestLogController, Integration)
- **Priority 40**: Utils (Logger, StringHelper)

### Dual Logger Architecture
The plugin has two distinct logging systems:
- **`Core\RequestLogger`**: Database-backed logger for API request/response tracking (shown in admin UI)
- **`Utils\DebugLogger`**: PSR-3 compliant file logger for plugin debugging (dev/troubleshooting)

### MVC Pattern in Admin
Admin components follow MVC separation:
- **Controllers**: `RequestLogController`, `SettingsPage`, `GlobalSettingsController` (routing, actions)
- **Views**: `Views/RequestLogView`, `Views/SettingsView`, `Views/DashboardWidgetView` (HTML rendering)
- **Models**: `RequestLogTable` (WP_List_Table data handling)

### Settings Architecture (IMPORTANT)
The plugin settings use a specific architecture that MUST be followed:

**SettingsPage.php** (Priority 25):
- Registers plugin with Settings Hub via `register_plugin()`
- Calls `SettingsView::render_page()` to render the UI
- Handles admin notices from query params
- URL: `/wp-admin/admin.php?page=contact-form-to-api`

**GlobalSettingsController.php** (Priority 26):
- **DOES NOT register a tab** in Settings Hub
- Only handles form submission via `admin_post_cf7_api_save_global_settings`
- Handles AJAX requests (e.g., `cf7_api_send_test_email`)
- Enqueues JavaScript for settings page functionality
- Provides nonce methods: `get_nonce_action()`, `get_nonce_name()`

**SettingsView.php**:
- Renders ALL settings UI including Global Settings form
- Calls `render_global_settings_section()` which includes:
  - Retry Configuration
  - Sensitive Data Patterns
  - Logging Control
  - Log Retention
  - Email Alerts (if feature enabled)
- Form posts to `admin-post.php` with action `cf7_api_save_global_settings`

**Adding New Settings**:
1. Add setting to `Core/Settings.php` with getter method
2. Add form field to `SettingsView::render_global_settings_section()` or create new `render_*_settings()` method
3. Handle sanitization in `GlobalSettingsController::handle_save_settings()`
4. DO NOT create separate tabs in Settings Hub for plugin features

## Core Components Architecture

### contact-form-to-api.php - Main Plugin File
**Purpose**: Plugin entry point with dependency verification and lifecycle management
**Pattern**: SilverAssist main file pattern with prefixed globals and Update URI

**Required Elements**:
- **Update URI header**: `Update URI: https://github.com/SilverAssist/contact-form-to-api` (for GitHub updater)
- **Plugin constants**: All configuration via `CF7_API_*` prefixed constants
- **Prefixed globals**: `$GLOBALS["cf7_api_autoloader_loaded"]` to prevent duplicate loading
- **Autoloader validation**: Secure autoloader path validation with `realpath()` and `strpos()`
- **Dependency checks**: WordPress/PHP version, Contact Form 7 availability
- **Admin notices**: User-friendly error messages for missing requirements
- **Lifecycle hooks**: Activation, deactivation via `Activator` class
- **Plugin initialization**: `Plugin::instance()->init()` on `plugins_loaded` hook

**Core Constants**:
```php
define("CF7_API_VERSION", "1.0.0");
define("CF7_API_PLUGIN_FILE", __FILE__);
define("CF7_API_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("CF7_API_PLUGIN_URL", plugin_dir_url(__FILE__));
define("CF7_API_PLUGIN_BASENAME", plugin_basename(__FILE__));
define("CF7_API_MIN_PHP_VERSION", "8.2");
define("CF7_API_MIN_WP_VERSION", "6.5");
```

### includes/Core/Interfaces/LoadableInterface.php - Component Contract
**Purpose**: Define contract for all loadable components with priority-based loading
**Pattern**: Interface with three required methods

**Required Methods**:
- `init(): void` - Initialize the component (register hooks, set up functionality)
- `get_priority(): int` - Return component priority (10=Core, 20=Services, 30=Admin, 40=Utils)
- `should_load(): bool` - Determine if component should load (conditional loading logic)

**Priority System**:
- **10**: Core components (Plugin, Activator)
- **20**: Services (API handlers, data processors)
- **30**: Admin components (settings pages, metaboxes)
- **40**: Utility components (helpers, formatters)

### includes/Core/Activator.php - Lifecycle Management
**Purpose**: Handle plugin activation, deactivation, and uninstallation
**Pattern**: Static methods for WordPress lifecycle hooks

**Key Methods**:
- `activate(): void` - Plugin activation
  * Check requirements (PHP, WordPress, dependencies)
  * Create database tables via `create_tables()`
  * Initialize default settings
  * Set plugin version option
  * Trigger setup actions

- `deactivate(): void` - Plugin deactivation
  * Clear WordPress caches
  * Cleanup scheduled events
  * Flush rewrite rules
  * Do NOT delete data (preserve user settings)

- `uninstall(): void` - Plugin uninstallation (called from uninstall.php)
  * Check uninstall permission
  * Remove plugin options (if user configured)
  * Drop database tables (if user configured)
  * Cleanup transients and caches

- `create_tables(): void` - **PUBLIC STATIC** for test reuse
  * Use `dbDelta()` for schema changes
  * Proper SQL formatting with double spaces after PRIMARY KEY
  * Set charset/collation from `$wpdb`
  * Error handling for table creation failures

- `drop_tables(): void` - Remove plugin database tables
  * Uses `$wpdb->prepare()` with `%i` placeholder for table name escaping
  * The `%i` placeholder (identifier) was introduced in WordPress 6.2

**IMPORTANT - Database Table Name Handling**:
```php
// ✅ CREATE TABLE - Uses dbDelta() which has its own rules
// dbDelta() does NOT support prepared statements - it parses raw SQL
// Table name interpolation is SAFE because it comes from $wpdb->prefix + literal string
$sql = "CREATE TABLE {$table_name} (...)";
\dbDelta( $sql );

// ✅ DROP TABLE - Uses %i placeholder for identifier escaping (WP 6.2+)
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
```

**Why this is secure**: The `$table_name` is always constructed from `$wpdb->prefix . 'cf7_api_logs'` - no user input is involved. The `dbDelta()` function cannot use prepared statements due to its SQL parsing requirements.

**Testing Pattern**:
```php
// In wpSetUpBeforeClass() - BEFORE data insertion
Activator::create_tables();
// This avoids MySQL implicit COMMIT issues
```

### includes/Core/Plugin.php - Main Controller
**Purpose**: Central plugin coordinator implementing LoadableInterface
**Pattern**: Singleton with LoadableInterface implementation

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor

**Key Methods**:
- `instance(): self` - Get singleton instance
- `init(): void` - Initialize plugin
  * Load components via `load_components()`
  * Initialize hooks via `init_hooks()`
  * Load textdomain for i18n
  * Initialize GitHub updater via `init_updater()`

- `load_components(): void` - Load all plugin components
  * Iterate through component array
  * Check `should_load()` before initialization
  * Sort by priority using `get_priority()`
  * Call `init()` on each loadable component

- `init_updater(): void` - Configure GitHub updater
  * Use `UpdaterConfig` pattern for settings
  * Configure repository: `SilverAssist/contact-form-to-api`
  * Set up automatic updates from GitHub releases

- `get_priority(): int` - Return 10 (Core priority)
- `should_load(): bool` - Check WordPress version and dependencies

### includes/ContactForm/Integration.php - CF7 Integration
**Purpose**: Complete Contact Form 7 to API integration functionality
**Pattern**: Singleton implementing LoadableInterface with View delegation
**Integration**: Direct CF7 hooks via `wpcf7_editor_panels` and `wpcf7_before_send_mail`

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor

**Key Methods**:
- `init(): void` - Register CF7 hooks
  * `wpcf7_editor_panels` - Add "API Integration" tab
  * `wpcf7_before_send_mail` - Process form submissions
  * Admin enqueue scripts/styles
- `render_integration_panel()` - Delegates to IntegrationView
- `get_priority(): int` - Return 30 (Admin priority)
- `should_load(): bool` - Return `is_admin()` (admin-only functionality)

**CF7 Integration Features**:
- **Editor Tab**: Adds custom "API Integration" tab to CF7 form editor
- **Field Mapping**: Dynamic mapping between CF7 fields and API parameters
- **Multiple Formats**: GET/POST params, JSON, XML payloads
- **HTTP Methods**: GET, POST, PUT, PATCH support
- **Authentication**: Bearer tokens, Basic Auth, API keys, custom headers
- **Error Handling**: Comprehensive logging and retry mechanisms
- **Debug Mode**: Detailed logging for troubleshooting

### includes/ContactForm/Views/IntegrationView.php - CF7 Panel HTML
**Purpose**: HTML rendering for CF7 API Integration panel
**Pattern**: Static View class with render methods

**Key Methods**:
- `render_panel(...)` - Main panel rendering with all sections
- `render_base_fields()` - URL and enable checkbox
- `render_input_type_field()` - Input type selector
- `render_method_field()` - HTTP method selector
- `render_retry_config()` - Retry configuration section
- `render_params_mapping()` - Field mapping table
- `render_xml_template()` - XML template editor
- `render_json_template()` - JSON template editor
- `render_debug_section()` - Logs and statistics display

### includes/Services/ApiClient.php - HTTP Client Service
**Purpose**: Centralized HTTP client with retry logic and logging
**Pattern**: Singleton implementing LoadableInterface
**Priority**: 20 (Services layer)

**Implements**: `LoadableInterface`
**Singleton**: `instance()` method, private constructor

**Key Methods**:
- `request(string $url, array $config): array` - Execute HTTP request
- `post(string $url, array $data, array $headers = []): array` - POST shortcut
- `get(string $url, array $params = [], array $headers = []): array` - GET shortcut
- `get_priority(): int` - Returns 20 (Services priority)
- `should_load(): bool` - Returns true (always available)

**Features**:
- Retry logic with exponential backoff
- Request/response logging via Core\RequestLogger
- Authentication header handling (Bearer, Basic, API Key)
- Configurable timeout and SSL verification
- Error categorization and handling

### includes/Services/CheckboxHandler.php - Checkbox Value Processing
**Purpose**: Handle CF7 checkbox values for API submission
**Pattern**: Static utility class

**Key Methods**:
- `is_checkbox_value(mixed $value): bool` - Check if value represents checkbox
- `is_checkbox_checked(mixed $value): bool` - Determine if checkbox is checked
- `convert_checkbox_value(mixed $value, array $options = []): mixed` - Convert to API format

**Conversion Options**:
- `true_value` - Value for checked state (default: "1")
- `false_value` - Value for unchecked state (default: "0")
- `format` - Output format: "boolean", "string", "integer"

### includes/Admin/RequestLogController.php - Request Logs Admin
**Purpose**: Admin interface for viewing API request/response logs
**Pattern**: MVC Controller with View delegation

**Key Methods**:
- `handle_page_request(): void` - Route to appropriate view
- `show_logs_list(): void` - Display logs list table
- `show_log_detail(int $log_id): void` - Display single log detail
- `process_bulk_actions(): void` - Handle bulk delete/export

### includes/Admin/Views/RequestLogView.php - Request Logs HTML
**Purpose**: HTML rendering for request logs pages
**Pattern**: Static View class with render methods

**Key Methods**:
- `render_page(RequestLogTable $table, array $statistics): void` - Main page
- `render_statistics(array $stats): void` - Stats cards
- `render_detail(array $log): void` - Single log detail view
- `render_notices(array $notices): void` - Admin notices

### includes/Admin/Views/SettingsView.php - Settings Page HTML
**Purpose**: HTML rendering for Settings Hub documentation page
**Pattern**: Static View class with render methods

**Key Methods**:
- `render_page(): void` - Main settings page
- `render_how_to_section(): void` - Usage instructions
- `render_quick_links_section(): void` - Quick navigation links
- `render_status_section(): void` - Plugin status display

### includes/Core/RequestLogger.php - API Request Logger
**Purpose**: Database-backed logging for API requests/responses
**Pattern**: Singleton implementing LoadableInterface
**Storage**: Custom database table `{prefix}cf7_api_logs`

**Key Methods**:
- `log(array $data): int|false` - Log request/response to database
- `get_logs(array $args = []): array` - Retrieve logs with filtering
- `get_log(int $id): array|null` - Get single log entry
- `delete_logs(array $ids): int` - Delete log entries
- `get_statistics(): array` - Get log statistics for dashboard

### includes/Core/EncryptionService.php - Database Encryption
**Purpose**: Transparent database-level encryption for sensitive request data using libsodium
**Pattern**: Singleton implementing LoadableInterface
**Priority**: 10 (Core - load early before data access)
**Since**: 1.3.0

**Encryption Details**:
- **Algorithm**: XSalsa20 stream cipher with Poly1305 MAC (authenticated encryption)
- **Key Derivation**: HKDF from WordPress `AUTH_KEY` constant
- **Fallback**: Secure key generation stored in `wp_options` if `AUTH_KEY` unavailable
- **Version**: Tracks encryption version for future algorithm upgrades

**Key Methods**:
- `encrypt(string $plaintext): string` - Encrypt data with authenticated encryption
- `decrypt(string $data): string` - Decrypt data (handles legacy plaintext transparently)
- `is_encryption_enabled(): bool` - Check if encryption is enabled in settings
- `is_encrypted(string $data): bool` - Detect if data appears to be encrypted
- `is_plaintext_json(string $data): bool` - Detect legacy unencrypted JSON data
- `get_version(): int` - Get current encryption version (for migrations)
- `is_sodium_available(): bool` - Static check for Sodium extension

**Encrypted Fields** (in `cf7_api_logs` table):
- `request_data` - Form submission data sent to API
- `request_headers` - HTTP headers including auth tokens
- `response_data` - API response body
- `response_headers` - API response headers

**Important**: Always use `RequestLogger::decrypt_log_fields()` when displaying logs in UI.

### includes/Services/MigrationService.php - Legacy Log Migration
**Purpose**: Batch migration of unencrypted legacy logs to encrypted format
**Pattern**: Singleton implementing LoadableInterface
**Priority**: 20 (Services)
**Since**: 1.3.4

**Key Methods**:
- `get_unencrypted_count(): int` - Count logs with `encryption_version = 0`
- `migrate_batch(int $batch_size, bool $dry_run): array` - Migrate a batch of logs
- `get_progress(): array` - Get migration progress from transient
- `save_progress(array $progress): void` - Save progress to transient
- `reset_progress(): void` - Clear migration progress

**Migration Results Array**:
```php
array{
    'processed' => int,  // Total logs processed in batch
    'success' => int,    // Successfully encrypted
    'failed' => int,     // Failed to encrypt
    'remaining' => int,  // Logs still needing migration
    'errors' => array    // Error messages
}
```

**AJAX Endpoint**: `cf7_api_migrate_logs` - Used by admin UI for batch processing with progress bar.

### includes/Utils/DebugLogger.php - Debug File Logger
**Purpose**: PSR-3 compliant file logger for plugin debugging
**Pattern**: Singleton implementing LoadableInterface
**Storage**: File at `wp-content/uploads/cf7-to-api-debug.log`

**Key Methods**:
- `debug(string $message, array $context = []): void` - Debug level
- `info(string $message, array $context = []): void` - Info level
- `warning(string $message, array $context = []): void` - Warning level
- `error(string $message, array $context = []): void` - Error level
- `log(string $level, string $message, array $context = []): void` - Generic log

### includes/Utils/DateFilterTrait.php - Date Filtering Trait
**Purpose**: Reusable date filtering logic for SQL queries
**Pattern**: PHP Trait (used by RequestLogTable and RequestLogController)
**Since**: 1.2.0

**Key Methods**:
- `build_date_filter_clause(string $filter, string $start, string $end): array` - Build SQL clause for date filters
- `build_custom_date_range_clause(string $start, string $end): array` - Build custom date range clause
- `is_valid_date_format(string $date): bool` - Validate Y-m-d date format
- `get_date_filter_params(): array` - Get sanitized date filter params from $_GET

**Supported Filters**:
- `today` - Current day only
- `yesterday` - Previous day only
- `7days` - Last 7 days
- `30days` - Last 30 days
- `month` - Current month
- `custom` - Custom date range (start/end)

**Return Format**:
```php
array{
    'clause' => 'AND DATE(created_at) BETWEEN %s AND %s',
    'values' => ['2026-01-01', '2026-01-31']
}
```

### includes/Utils/SensitiveDataPatterns.php - Sensitive Data Detection
**Purpose**: Centralized patterns for detecting and masking sensitive data
**Pattern**: Static utility class
**Since**: 1.2.0

**Key Methods**:
- `get_header_patterns(): array` - Get patterns for sensitive headers
- `get_data_patterns(): array` - Get patterns for sensitive data fields
- `is_sensitive_header(string $header): bool` - Check if header is sensitive
- `is_sensitive_field(string $field): bool` - Check if field is sensitive

### includes/Utils/StringHelper.php - String Utilities
**Purpose**: String manipulation utilities for field mapping
**Pattern**: Static utility class

**Key Methods**:
- `kebab_to_camel(string $string): string` - Convert kebab-case to camelCase
- `camel_to_kebab(string $string): string` - Convert camelCase to kebab-case
- `fields_match(string $field1, string $field2): bool` - Case-insensitive field comparison

## Asset Architecture

### assets/css/admin.css - Modern CF7 Admin Styles
**Purpose**: Styling for CF7 API integration interface
**Features**:
- Responsive design for CF7 admin tabs
- Input type toggles and validation states
- Debug log styling and collapsible sections
- Mail tag buttons and insertion feedback
- Modern WordPress admin compliance

### assets/js/admin.js - ES6+ Admin Functionality  
**Purpose**: Interactive functionality for CF7 integration
**Architecture**: ES6 Class-based with jQuery integration

**Core Functionality**:
- **Mail Tag Insertion**: Smart insertion with cursor positioning
- **URL Validation**: Real-time API endpoint validation
- **API Testing**: Async endpoint connectivity testing
- **Debug Controls**: Toggle debug information display
- **Validation Feedback**: User-friendly error messages

## 🚨 CRITICAL CODING STANDARDS - MANDATORY COMPLIANCE

### String Quotation Standards (WordPress Coding Standards)
The WordPress Coding Standards (enforced by PHPCS with `WordPress-Extra` ruleset) require specific quotation rules:

- **Single quotes for simple strings**: Use single quotes for strings that don't require variable interpolation or special characters: `'string'`
- **Double quotes for interpolation**: Use double quotes ONLY when string interpolation is needed: `"prefix_{$variable}"`
- **Double quotes for special characters**: Use double quotes when strings contain escape sequences: `"\n"`, `"\t"`
- **Array keys**: Use single quotes for array keys: `$array['key']`
- **i18n Functions**: Use single quotes for text domain: `__('Text', 'contact-form-to-api')`

**Examples**:
```php
// ✅ CORRECT - Single quotes for simple strings
$status = 'active';
$key = 'api_key';
$message = __('Error occurred', 'contact-form-to-api');

// ✅ CORRECT - Double quotes for interpolation
$path = "includes/{$directory}/file.php";
$log = "User {$user_id} logged in";

// ❌ WRONG - Double quotes without interpolation (PHPCS error)
$status = "active";  // Should be 'active'
```

**PHPCS Rule**: `Squiz.Strings.DoubleQuoteUsage.NotRequired` - Strings that don't require double quotes must use single quotes.

### Indentation Standards (WordPress Coding Standards)
**CRITICAL**: WordPress Coding Standards require **TABS for indentation**, not spaces. Incorrect indentation causes PHPCS errors like: `Line indented incorrectly; expected 1 tabs, found 2`

#### Rules:
- **Use TABS only**: Never use spaces for indentation
- **1 tab = 1 indentation level**: Each nesting level adds exactly ONE tab
- **Alignment with spaces**: After initial tab indentation, use spaces for alignment if needed
- **Switch/case**: Case statements are indented ONE level from switch, code inside case is ONE more level

#### Common Errors:
```php
// ❌ WRONG - Expected 1 tab, found 2 (double indentation)
class MyClass {
		public function method() {  // 2 tabs instead of 1
		}
}

// ✅ CORRECT - Proper single tab indentation
class MyClass {
	public function method() {  // 1 tab
		$variable = 'value';      // 2 tabs (inside method)
	}
}

// ❌ WRONG - Spaces used for indentation
class MyClass {
    public function method() {  // 4 spaces instead of 1 tab
    }
}

// ✅ CORRECT - Switch/case indentation
switch ( $value ) {
	case 'option1':           // 1 tab from switch
		$result = 'one';      // 2 tabs (inside case)
		break;
	case 'option2':
		$result = 'two';
		break;
	default:
		$result = 'default';
}
```

**PHPCS Rules**:
- `Generic.WhiteSpace.DisallowSpaceIndent` - Spaces cannot be used for indentation
- `Generic.WhiteSpace.ScopeIndent` - Code must be indented correctly

**Editor Configuration**: Ensure your editor uses tabs (not spaces) with tab width of 4 for PHP files.

### Documentation Requirements
- **PHP**: Complete PHPDoc documentation for ALL classes, methods, and properties
- **JavaScript**: Complete JSDoc documentation for ALL functions (in English)
- **@since tags**: Required for all public APIs
- **English only**: All documentation must be in English for international collaboration

### WordPress i18n Standards
- **Text domain**: `'contact-form-to-api'` - MANDATORY for all i18n functions (single quotes)
- **ALL user-facing strings**: Must use WordPress i18n functions
- **Functions**: `__('text', 'contact-form-to-api')`, `esc_html_e('text', 'contact-form-to-api')`, etc.
- **JavaScript i18n**: Pass translated strings from PHP via `wp_localize_script()`
- **Forbidden**: Hardcoded user-facing strings without translation functions

#### sprintf() Placeholder Standards
**CRITICAL**: WordPress PHPCS (`WordPress.WP.I18n.MissingArgDomainDefault`) requires **ordered/positional placeholders** for translatable strings with multiple arguments.

- **Single placeholder**: Use simple format: `sprintf(__('Found %d items', 'contact-form-to-api'), $count)`
- **Multiple placeholders - ALWAYS use positional format**: Use `%1$d`, `%2$s`, `%3$f` etc.
  ```php
  // ✅ CORRECT - Ordered placeholders (PHPCS compliant)
  sprintf(
      /* translators: %1$d: batch number, %2$s: batch status */
      __('Batch %1$d completed with status: %2$s', 'contact-form-to-api'),
      $batch_number,
      $status
  )
  
  // ❌ WRONG - Simple placeholders with multiple args (PHPCS error)
  sprintf(__('Batch %d completed with status: %s', 'contact-form-to-api'), $batch_number, $status)
  ```
- **Translator comments**: ALWAYS add comments for placeholders explaining each variable
- **Why positional?**: Translators may need to reorder placeholders in different languages

**PHPCS Rule**: `WordPress.WP.I18n.UnorderedPlaceholdersText` - Multiple placeholders must use ordered format.

## Modern PHP 8.2+ Conventions

### PHP Coding Standards
- **PHP Version**: 8.2+ required for modern features
- **Double quotes for all strings**: `"string"` not `'string'` - MANDATORY
- **String interpolation**: Use `"prefix_{$variable}"` instead of concatenation
- **Short array syntax**: `[]` not `array()`
- **Typed properties**: Use property type declarations
- **Return types**: Declare return types for all methods
- **Constructor property promotion**: Use when appropriate
- **Match expressions**: Prefer `match` over `switch` for cleaner code
- **Null coalescing**: Use `??` operator for null checks
- **Named arguments**: Use for clarity in complex function calls

### Namespace and Use Statement Standards
- **Namespace**: `SilverAssist\ContactFormToAPI\` - MANDATORY prefix
- **PSR-4 Autoloading**: `includes/` directory mapped to namespace
- **Import Organization**: All `use` statements at top after namespace
- **Alphabetical Ordering**: ALWAYS sort `use` statements alphabetically
- **Same Namespace Rule**: NEVER import classes from same namespace
- **WordPress Functions**: Use `\` prefix for ALL WordPress functions in namespaced classes
  * Examples: `\add_action()`, `\get_option()`, `\is_ssl()`, `\wp_enqueue_script()`
- **PHP Native Functions**: NO `\` prefix for built-in functions
  * Examples: `array_key_exists()`, `explode()`, `trim()`, `sprintf()`

#### CRITICAL: Always Use `use` Statements - Avoid FQCN
**MANDATORY**: Always prefer `use` statements over Fully Qualified Class Names (FQCN) in code.

```php
// ✅ CORRECT - Import with use statement
namespace SilverAssist\ContactFormToAPI\Services;

use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Core\EncryptionService;
use SilverAssist\ContactFormToAPI\Utils\DebugLogger;

class MyService {
    public function __construct() {
        $settings = Settings::instance();  // Clean, readable
        $logger = DebugLogger::instance();
    }
}

// ❌ WRONG - FQCN in method body
class MyService {
    public function __construct() {
        $settings = \SilverAssist\ContactFormToAPI\Core\Settings::instance();  // Verbose, hard to read
    }
}
```

**Benefits of `use` statements**:
- Cleaner, more readable code
- Easier refactoring (change import once, not every usage)
- IDE autocomplete works better
- Follows PSR-12 coding standard
- Reduces line length issues

**When NOT to use `use` statements**:
- Classes in the SAME namespace (use short name directly)
- WordPress core classes that may not exist (use `class_exists()` check first)
- External plugin classes (use `class_exists()` check first)

### WordPress Integration Standards
- **Hooks**: Use `\add_action("init", [$this, "method"])` with array callbacks
- **i18n**: All user-facing strings use `\__()`, `\esc_html__()`, `\_e()`, `\esc_html_e()`
- **Text Domain**: ALWAYS use literal string `'contact-form-to-api'` (required for i18n extraction tools)
- **Sanitization**: Use `\sanitize_text_field()`, `\sanitize_email()`, etc.
- **Escaping**: Use `\esc_html()`, `\esc_attr()`, `\esc_url()` for output
- **Nonces**: Verify with `\wp_verify_nonce()` for forms
- **Capabilities**: Check with `\current_user_can()` for permissions

## Testing Standards (WordPress Test Suite)

### Test Environment Setup
- **Framework**: WordPress Test Suite + PHPUnit 9.6+
- **Base Class**: Extend `WP_UnitTestCase` for integration tests
- **Test Directory**: `tests/` with namespaced structure
- **Bootstrap**: `tests/bootstrap.php` loads WordPress test environment

### Local Testing Environment Variables
**CRITICAL**: The WordPress Test Suite requires environment variables to locate the test library and configuration.

#### Required Environment Variables
```bash
# Path to WordPress test library (contains includes/functions.php)
export WP_TESTS_DIR="/tmp/wordpress-tests-lib"

# Path to WordPress core installation for tests
export WP_CORE_DIR="/tmp/wordpress"

# Database configuration for tests (uses separate test database)
export WP_TESTS_DB_NAME="wordpress_test"
export WP_TESTS_DB_USER="root"
export WP_TESTS_DB_PASSWORD=""
export WP_TESTS_DB_HOST="localhost"
```

#### Quick Setup for Local Testing
```bash
# 1. Install WordPress Test Suite (run once)
./scripts/install-wp-tests.sh wordpress_test root "" localhost latest

# 2. Set environment variables (add to ~/.zshrc or ~/.bashrc for persistence)
export WP_TESTS_DIR="/tmp/wordpress-tests-lib"
export WP_CORE_DIR="/tmp/wordpress"

# 3. Run tests
./vendor/bin/phpunit

# 4. Run specific test file
./vendor/bin/phpunit --filter RequestLogControllerTest

# 5. Run specific test suite
./vendor/bin/phpunit --testsuite "Unit Tests"
```

#### Troubleshooting Test Environment
- **"WordPress Test Suite not found"**: Set `WP_TESTS_DIR` environment variable
- **"Database connection failed"**: Verify MySQL credentials and that test database exists
- **"Table doesn't exist"**: Run `Activator::create_tables()` in `wpSetUpBeforeClass()`

### Test File Organization
```
tests/
├── bootstrap.php                    # WordPress test environment
├── Helpers/
│   ├── TestCase.php                # Base test case with helpers
│   └── CF7TestCase.php             # CF7-specific test helpers
├── Unit/                           # Unit tests (isolated)
│   └── PluginTest.php
├── Integration/                    # Integration tests (WordPress)
│   └── WordPressIntegrationTest.php
└── ContactForm/
    └── IntegrationTest.php         # CF7 integration tests
```

### Critical Testing Patterns

#### Database Schema Creation Pattern
**CRITICAL**: Use `wpSetUpBeforeClass()` for `CREATE TABLE` statements to avoid MySQL implicit COMMIT issues.

```php
/**
 * Set up before class - runs ONCE before any tests
 * CRITICAL: Use this for CREATE TABLE to avoid MySQL implicit COMMIT
 */
public static function wpSetUpBeforeClass(): void {
    parent::wpSetUpBeforeClass();
    
    // Create tables BEFORE inserting any test data
    Activator::create_tables();
    
    // This prevents MySQL's implicit COMMIT from breaking transactions
}
```

**Why This Matters**:
- MySQL implicitly commits on `CREATE TABLE`
- This breaks PHPUnit's transaction rollback
- Creates orphaned test data in database
- Causes test pollution and failures

#### Factory Pattern for Test Data
```php
use function Tests\Helpers\create_test_form;
use function Tests\Helpers\create_test_submission;

public function test_form_submission(): void {
    $form_id = static::factory()->post->create([
        "post_type" => "wpcf7_contact_form",
        "post_status" => "publish",
    ]);
    
    // Test implementation
}
```

### Test Method Naming
- **Pattern**: `test_<method_name>_<scenario>_<expected_result>()`
- **Examples**: 
  * `test_activate_creates_database_tables()`
  * `test_form_submission_sends_to_api_successfully()`
  * `test_integration_should_not_load_on_frontend()`

### Assertions Best Practices
- Use specific assertions: `assertSame()` over `assertEquals()` for type safety
- Test both success and failure scenarios
- Verify WordPress integration (options, transients, hooks)
- Check database state changes
- Validate API responses and error handling

## Quality Tools Configuration

### PHPCS (WordPress Coding Standards)
**Configuration**: `phpcs.xml`
```xml
<rule ref="WordPress-Extra">
    <exclude name="WordPress.Files.FileName"/>
</rule>
<config name="minimum_wp_version" value="6.5"/>
<config name="prefixes" value="cf7_api"/>
```

**Key Rules**:
- WordPress-Extra ruleset
- File naming exceptions for PSR-4
- Prefix validation for global functions
- Minimum WordPress version checks

### PHPStan (Static Analysis)
**Configuration**: `phpstan.neon`
```neon
parameters:
    level: 8
    paths:
        - includes/
    bootstrapFiles:
        - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
    ignoreErrors:
        - '#Function (add_action|add_filter|get_option) not found#'
```

**Requirements**:
- Level 8 (strictest)
- WordPress stubs for core functions
- Analyze `includes/` directory
- Type safety enforcement

### PHPUnit (Testing Framework)
**Configuration**: `phpunit.xml.dist`
```xml
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
            <directory>tests/ContactForm</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Quality Check Script
**File**: `scripts/run-quality-checks.sh`
**Purpose**: Run all quality checks with proper exit codes for CI/CD

**Checks**:
1. Composer validation
2. PHPCS coding standards
3. PHPStan static analysis
4. PHPUnit test suite

**Exit Codes**: Non-zero on any failure (CI/CD compatible)

## CI/CD Workflows (GitHub Actions)

### Workflow Architecture
- **Reusable Workflow**: `.github/workflows/quality-checks.yml`
- **PR Validation**: `.github/workflows/ci.yml`
- **Release Automation**: `.github/workflows/release.yml`
- **Dependency Updates**: `.github/workflows/dependency-updates.yml`

### quality-checks.yml (Reusable)
```yaml
name: Quality Checks
on:
  workflow_call:
    inputs:
      php-version:
        required: false
        type: string
        default: '8.2'
```

**Features**:
- Reusable across workflows
- PHP version matrix support
- Composer install with caching
- Run quality checks script
- Proper exit code handling

### ci.yml (Pull Request Validation)
```yaml
on:
  pull_request:
    branches: [main, develop]
jobs:
  quality:
    uses: ./.github/workflows/quality-checks.yml
```

**Triggers**: Pull requests to main/develop
**Runs**: Quality checks via reusable workflow

### release.yml (Automated Releases)
```yaml
on:
  push:
    tags:
      - 'v*'
```

**Process**:
1. Run quality checks
2. Validate version consistency (`check-versions.sh`)
3. Build release package
4. Create GitHub release
5. Upload release assets (ZIP, checksums)

### ⚠️ CRITICAL: Immutable Tags and Releases

**GitHub enforces immutability** on tags and releases in this repository. Once a tag is used (even if the release fails), it **CANNOT be reused**.

#### 🚨 NEVER Create Releases Manually
**ALWAYS let the `release.yml` workflow create the GitHub release.** The workflow handles:
- Quality checks validation
- Version consistency verification
- Building the release package (ZIP, checksums)
- Creating the GitHub release with proper notes
- Uploading all release assets

**❌ FORBIDDEN - Do NOT use these commands**:
```bash
# NEVER do this - will cause "immutable release" errors
gh release create v1.3.5 --title "..." --notes "..."
```

**✅ CORRECT - Only create and push the tag**:
```bash
# Let the workflow do everything else
git tag v1.3.6 -m "Release v1.3.6"
git push origin v1.3.6
# Then monitor: gh run list --workflow=release.yml --limit 3
```

#### If a Release Workflow Fails
1. **DO NOT** try to delete and recreate the same tag
2. **DO NOT** manually create a release with the same version
3. **INCREMENT the version** (e.g., v1.3.5 → v1.3.6 → v1.3.7)
4. Update all version tags using `./scripts/update-version-simple.sh`
5. Commit, push, and create a new tag

**Common failure scenarios**:
- **Version consistency check fails**: Run `./scripts/update-version-simple.sh X.Y.Z` to update all `@version` tags
- **"Cannot upload assets to immutable release"**: A manual release was created; increment version
- **"Cannot create ref due to creations being restricted"**: Tag was previously used; increment version

**Correct release workflow**:
```bash
# 1. Update all versions
./scripts/update-version-simple.sh 1.3.6

# 2. Update CHANGELOG.md with new version section

# 3. Verify the CF7_API_VERSION constant was updated (script should handle this)
grep "CF7_API_VERSION" contact-form-to-api.php

# 4. Verify consistency
./scripts/check-versions.sh

# 5. Commit and push
git add -A
git commit -m "chore: bump version to 1.3.6 for release"
git push origin main

# 6. Create and push tag (triggers release workflow - DO NOT create release manually!)
git tag v1.3.6 -m "Release v1.3.6"
git push origin v1.3.6

# 7. Monitor workflow (DO NOT intervene unless it fails)
gh run list --workflow=release.yml --limit 3
gh run watch <run-id> --exit-status
```

## Build and Release Scripts

### scripts/build-release.sh
**Purpose**: Create distributable plugin package

**Process**:
1. Validate version numbers
2. Run quality checks
3. Install production dependencies
4. Copy files to build directory
5. Exclude development files
6. Create ZIP archive

**Output**: `contact-form-to-api-{version}.zip`

### scripts/update-version-simple.sh
**Purpose**: Update version across all files

**Updates**:
- Main plugin file header (`Version:` and `CF7_API_VERSION` constant)
- All PHP files `@version` tags in `includes/`
- CSS files `@version` tags in `assets/css/`
- JavaScript files `@version` tags in `assets/js/`
- Shell scripts `@version` tags in `scripts/`
- README.md version references (if applicable)

**Usage**:
```bash
# Interactive mode (prompts for confirmation)
./scripts/update-version-simple.sh 1.2.0

# Non-interactive mode (for CI/CD)
./scripts/update-version-simple.sh 1.2.0 --no-confirm

# Force update all files even if version matches
./scripts/update-version-simple.sh 1.2.0 --no-confirm --force
```

### scripts/check-versions.sh
**Purpose**: Verify version consistency across all plugin files

**Checks**:
- Main plugin file header and constant
- All PHP files in `includes/`
- CSS files in `assets/css/`
- JavaScript files in `assets/js/`
- Shell scripts in `scripts/`

**Usage**:
```bash
./scripts/check-versions.sh
```

## 🚨 MANDATORY: Use Scripts - No Manual Changes

### Critical Rule: ALWAYS Use Automation Scripts
**NEVER make manual changes** to version numbers, file headers, or any content that is managed by automation scripts. This ensures consistency and prevents human error.

### Version Management
- **ALWAYS** use `./scripts/update-version-simple.sh` to update versions
- **ALWAYS** use `./scripts/check-versions.sh` to verify version consistency
- **NEVER** manually edit `@version` tags in any file
- **NEVER** manually edit the `Version:` header in the main plugin file
- **NEVER** manually edit the `CF7_API_VERSION` constant

**Correct workflow for version updates**:
```bash
# 1. Update all versions using the script
./scripts/update-version-simple.sh 1.2.0 --no-confirm --force

# 2. Verify all versions are consistent
./scripts/check-versions.sh

# 3. Review changes
git diff

# 4. Commit changes
git add -A && git commit -m "chore: Bump version to 1.2.0"
```

### Quality Checks
- **ALWAYS** use `./scripts/run-quality-checks.sh` before committing
- **NEVER** skip quality checks assuming code is correct

### Release Process
- **ALWAYS** create only the git tag and let `release.yml` workflow create the GitHub release
- **NEVER** use `gh release create` manually - it causes immutable release conflicts
- **NEVER** manually create ZIP files or release packages
- **ALWAYS** increment version if a release fails (tags are immutable)

### Why This Matters
1. **Consistency**: Scripts ensure ALL files are updated uniformly
2. **Error Prevention**: Eliminates typos and forgotten files
3. **Audit Trail**: Git history shows script-driven changes
4. **CI/CD Compatibility**: Scripts work in automated pipelines
5. **Script Validation**: Using scripts validates they work correctly

## Plugin Constants - MANDATORY USAGE

### Available Plugin Constants
The following constants are defined in the main plugin file (`contact-form-to-api.php`) and **MUST be used** instead of hardcoded values:

```php
// Core plugin information
define("CF7_API_VERSION", "1.0.0");
define("CF7_API_FILE", __FILE__);
define("CF7_API_DIR", plugin_dir_path(__FILE__));
define("CF7_API_URL", plugin_dir_url(__FILE__));
define("CF7_API_BASENAME", plugin_basename(__FILE__));

// System requirements
define("CF7_API_MIN_PHP_VERSION", "8.2");
define("CF7_API_MIN_WP_VERSION", "6.5");
```

### Mandatory Constant Usage Rules
- **Text Domain**: ALWAYS use literal string `'contact-form-to-api'` for i18n functions
  - ✅ Correct: `\__("Text", 'contact-form-to-api')`
  - ❌ Wrong: `\__("Text", $variable)` or `\__("Text", CONSTANT)`
  - **Reason**: WordPress i18n extraction tools (wp i18n make-pot) cannot parse variables/constants

- **Plugin Version**: ALWAYS use `CF7_API_VERSION` for version references
  - ✅ Correct: `CF7_API_VERSION`
  - ❌ Wrong: `"1.0.0"`

- **Plugin Paths**: ALWAYS use path constants for file operations
  - ✅ Correct: `CF7_API_DIR . "assets/css/admin.css"`
  - ❌ Wrong: `plugin_dir_path(__FILE__) . "assets/css/admin.css"`

- **Plugin URLs**: ALWAYS use URL constants for asset loading
  - ✅ Correct: `CF7_API_URL . "assets/js/admin.js"`
  - ❌ Wrong: `plugin_dir_url(__FILE__) . "assets/js/admin.js"`

### Benefits of Using Constants
1. **Centralized Configuration**: One place to change values
2. **Consistency**: Same values used throughout the plugin
3. **Maintenance**: Easy to update plugin information
4. **Version Management**: Automated version updates via scripts
5. **Error Prevention**: Reduces typos in hardcoded values

## GitHub CLI Workflows

### 🚨 CRITICAL: Pager Configuration
**ALWAYS** append `| cat` to ALL `gh` commands to prevent the terminal from waiting for interactive pager input.

**Why**: GitHub CLI uses a pager by default for output. In non-interactive environments (like AI agents), this causes the terminal to hang indefinitely waiting for user input.

**Rule**: Every `gh` command MUST end with `| cat`:
```bash
# ✅ CORRECT - Always use | cat
gh pr checks | cat
gh run list | cat
gh run view <run-id> --log | cat

# ❌ WRONG - Terminal will hang
gh pr checks
gh run list
```

### Monitor CI/CD Status
```bash
# View workflow runs
gh run list | cat

# Watch specific workflow run
gh run view <run-id> | cat

# View workflow run logs
gh run view <run-id> --log | cat

# Check status of latest run
gh run list --limit 1 | cat

# View failed logs only
gh run view <run-id> --log-failed | cat
```

### Common Workflow Tasks
```bash
# List all workflows
gh workflow list | cat

# View workflow details
gh workflow view <workflow-name> | cat

# Trigger manual workflow (no output, no | cat needed)
gh workflow run <workflow-name>

# Re-run failed jobs (no output, no | cat needed)
gh run rerun <run-id>

# Cancel running workflow (no output, no | cat needed)
gh run cancel <run-id>
```

### Pull Request Commands
```bash
# View PR checks status
gh pr checks | cat

# View PR status
gh pr status | cat

# View PR details
gh pr view <pr-number> | cat
```

### Responding to PR Review Comments

**IMPORTANT**: Each reviewer comment MUST be responded to INDIVIDUALLY. Do NOT create a single summary comment for all feedback. The goal is to close/resolve each review thread with a specific response explaining how it was addressed.

#### Process Overview
1. **Get all review threads** with their IDs and content
2. **Analyze each comment** to determine if it requires a code fix or just clarification
3. **Make code fixes** if needed, commit, and push
4. **Reply to EACH thread individually** explaining the resolution
5. **Verify** all threads have been responded to

#### Step 1: Get Review Threads (GraphQL - Recommended)
```bash
# Get all review thread IDs with their comments and resolution status
gh api graphql -f query='
query {
  repository(owner: "<owner>", name: "<repo>") {
    pullRequest(number: <pr-number>) {
      reviewThreads(first: 50) {
        nodes {
          id
          path
          line
          isResolved
          comments(first: 5) {
            nodes {
              id
              body
              author { login }
            }
          }
        }
      }
    }
  }
}' | cat
```

#### Step 2: Analyze and Fix
For each comment:
- **Valid issue**: Make the code fix, note what was changed
- **Already correct**: Prepare explanation of why current code is correct
- **Won't fix**: Prepare justification

#### Step 3: Commit and Push Fixes
```bash
git add -A && git commit -m "fix: Address PR review comments" && git push
```

#### Step 4: Reply to EACH Thread Individually
**CRITICAL**: Use a separate GraphQL mutation for EACH review thread. This allows GitHub to mark each thread as resolved.

```bash
# Reply to thread 1
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6THREAD1",
    body: "Fixed in commit abc1234. Added the missing backslash prefix to `get_option()`."
  }) {
    comment { id }
  }
}' | cat

# Reply to thread 2
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6THREAD2",
    body: "Already correct. The `is-dismissible` class was intentionally omitted because there is no AJAX handler to persist the dismissal state."
  }) {
    comment { id }
  }
}' | cat

# Continue for each remaining thread...
```

#### Response Templates
Use these templates for consistency:

**For fixed issues**:
```
Fixed in commit <short-sha>. <Brief description of the fix>.
```

**For already correct code**:
```
Already correct. <Explanation of why the current implementation is correct>.
```

**For won't fix**:
```
Won't fix. <Justification for keeping current implementation>.
```

**For clarification needed**:
```
Could you clarify <specific question>? The current implementation does <explanation>.
```

#### Alternative: REST API Method
If GraphQL doesn't work, use REST API:

```bash
# Get comment IDs
gh api repos/<owner>/<repo>/pulls/<pr-number>/comments --jq '.[] | "ID: \(.id) | File: \(.path):\(.line // .original_line)"' | cat

# Get latest commit SHA
gh api repos/<owner>/<repo>/pulls/<pr-number>/commits --jq '.[-1].sha' | cat

# Reply to each comment individually
gh api repos/<owner>/<repo>/pulls/<pr-number>/comments -X POST --input - <<EOF | cat
{
  "body": "Fixed in commit abc1234. Description of the fix.",
  "commit_id": "<full-40-char-sha>",
  "path": "<file-path>",
  "line": <line-number>,
  "in_reply_to": <comment-id>
}
EOF
```

#### Important Notes
- **ALWAYS use `| cat`** at the end of commands to avoid pager issues
- **One response per thread** - never batch responses into a single comment
- **Be specific** - reference exact commit SHAs and describe what changed
- **Close the loop** - each response should clearly indicate resolution status
- The `commit_id` in REST API MUST be a full 40-character SHA from the PR
- GraphQL thread IDs start with `PRRT_` and are found in the reviewThreads query
