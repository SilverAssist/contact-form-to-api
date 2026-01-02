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
├── docs/                          # Documentation
│   ├── WORKFLOWS.md
│   ├── RELEASE_PROCESS.md
│   ├── API_REFERENCE.md
│   └── ADMIN_INTERFACE.md
├── includes/                      # Source code (PSR-4)
│   ├── Admin/                     # Priority 30 - Admin components
│   │   ├── Loader.php            # Admin component loader
│   │   ├── SettingsPage.php      # Settings Hub integration controller
│   │   ├── RequestLogController.php # Request logs admin controller
│   │   ├── RequestLogTable.php   # WP_List_Table for logs
│   │   └── Views/                # Separated view templates
│   │       ├── RequestLogView.php    # Request log HTML rendering
│   │       └── SettingsView.php      # Settings page HTML rendering
│   ├── ContactForm/              # Priority 30 - CF7 Integration
│   │   ├── Integration.php       # CF7 hooks and panel logic
│   │   └── Views/
│   │       └── IntegrationView.php   # CF7 panel HTML rendering
│   ├── Core/                     # Priority 10 - Core components
│   │   ├── Interfaces/
│   │   │   └── LoadableInterface.php # Component contract
│   │   ├── Activator.php         # Lifecycle management
│   │   ├── RequestLogger.php     # API request/response DB logger
│   │   └── Plugin.php            # Main plugin controller
│   ├── Services/                 # Priority 20 - Business logic services
│   │   ├── Loader.php            # Services component loader
│   │   ├── ApiClient.php         # HTTP client with retry logic
│   │   └── CheckboxHandler.php   # Checkbox value processing
│   └── Utils/                    # Priority 40 - Utility classes
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
├── HEADER-STANDARDS.md
└── README.md
```

## Architecture Overview

### Component Priority System (LoadableInterface)
All components implement `LoadableInterface` with priority-based loading:
- **Priority 10**: Core (Plugin, Activator)
- **Priority 20**: Services (ApiClient, CheckboxHandler)
- **Priority 30**: Admin & ContactForm (SettingsPage, RequestLogController, Integration)
- **Priority 40**: Utils (Logger, StringHelper)

### Dual Logger Architecture
The plugin has two distinct logging systems:
- **`Core\RequestLogger`**: Database-backed logger for API request/response tracking (shown in admin UI)
- **`Utils\DebugLogger`**: PSR-3 compliant file logger for plugin debugging (dev/troubleshooting)

### MVC Pattern in Admin
Admin components follow MVC separation:
- **Controllers**: `RequestLogController`, `SettingsPage` (routing, actions)
- **Views**: `Views/RequestLogView`, `Views/SettingsView` (HTML rendering)
- **Models**: `RequestLogTable` (WP_List_Table data handling)

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
define("CF7_API_TEXT_DOMAIN", "contact-form-to-api");
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

### String Quotation Standards
- **MANDATORY**: ALL strings in PHP and JavaScript MUST use double quotes: `"string"`
- **i18n Functions**: ALL WordPress i18n functions MUST use double quotes: `__("Text", "contact-form-to-api")`, `esc_html_e("Text", "contact-form-to-api")`
- **FORBIDDEN**: Single quotes for strings: `'string'` or `__('text', 'domain')`
- **Exception**: Only use single quotes inside double-quoted strings when necessary
- **SQL Queries**: Use double quotes for string literals in SQL: `WHERE option_value = "1"`
- **sprintf() Placeholders**: When using `sprintf()` with positional placeholders like `%1$d`, escape the `$` to prevent PHP variable interpretation: `"Query complexity %1\$d exceeds maximum %2\$d"`

### Documentation Requirements
- **PHP**: Complete PHPDoc documentation for ALL classes, methods, and properties
- **JavaScript**: Complete JSDoc documentation for ALL functions (in English)
- **@since tags**: Required for all public APIs
- **English only**: All documentation must be in English for international collaboration

### WordPress i18n Standards
- **Text domain**: `"contact-form-to-api"` - MANDATORY for all i18n functions
- **ALL user-facing strings**: Must use WordPress i18n functions with double quotes
- **Functions**: `__("text", "contact-form-to-api")`, `esc_html_e("text", "contact-form-to-api")`, etc.
- **JavaScript i18n**: Pass translated strings from PHP via `wp_localize_script()`
- **Forbidden**: Hardcoded user-facing strings without translation functions

#### sprintf() Placeholder Standards
- **Simple placeholders**: Use `%d`, `%s`, `%f` for sequential arguments: `sprintf(__("Found %d items", "domain"), $count)`
- **Positional placeholders**: Use `%1\$d`, `%2\$s` with escaped `$` for non-sequential: `__("Value %1\$d exceeds limit %2\$d", "domain")`
- **Translator comments**: ALWAYS add comments for placeholders: `/* translators: %d: number of items found */`
- **Multiple placeholders**: Use positional numbering for clarity: `%1\$d` for first, `%2\$s` for second, etc.
- **Escaping requirement**: In double-quoted strings, escape `$` in placeholders to prevent variable interpretation

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
- **No In-Method Imports**: NEVER use fully qualified names in methods
- **Same Namespace Rule**: NEVER import classes from same namespace
- **WordPress Functions**: Use `\` prefix for ALL WordPress functions in namespaced classes
  * Examples: `\add_action()`, `\get_option()`, `\is_ssl()`, `\wp_enqueue_script()`
- **PHP Native Functions**: NO `\` prefix for built-in functions
  * Examples: `array_key_exists()`, `explode()`, `trim()`, `sprintf()`

### WordPress Integration Standards
- **Hooks**: Use `\add_action("init", [$this, "method"])` with array callbacks
- **i18n**: All user-facing strings use `\__()`, `\esc_html__()`, `\_e()`, `\esc_html_e()`
- **Text Domain**: `CF7_API_TEXT_DOMAIN` constant (value: `"contact-form-to-api"`)
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
2. Build release package
3. Create GitHub release
4. Upload release assets

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

### scripts/update-version.sh
**Purpose**: Update version across all files

**Updates**:
- Main plugin file header
- Plugin constant
- package.json (if exists)
- All @version PHPDoc tags
- composer.json version

**Validation**: Checks version consistency

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
define("CF7_API_TEXT_DOMAIN", "contact-form-to-api");

// System requirements
define("CF7_API_MIN_PHP_VERSION", "8.2");
define("CF7_API_MIN_WP_VERSION", "6.5");
```

### Mandatory Constant Usage Rules
- **Text Domain**: ALWAYS use `CF7_API_TEXT_DOMAIN` for i18n functions
  - ✅ Correct: `\__("Text", CF7_API_TEXT_DOMAIN)`
  - ❌ Wrong: `\__("Text", "contact-form-to-api")`

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

### IMPORTANT: Pager Configuration
**ALWAYS** use `PAGER=cat gh ...` or `gh ... | cat` to prevent interactive pager issues.

### Monitor CI/CD Status
```bash
# View workflow runs
PAGER=cat gh run list
gh run list | cat

# Watch specific workflow run
PAGER=cat gh run view <run-id>
gh run view <run-id> | cat

# View workflow run logs
PAGER=cat gh run view <run-id> --log
gh run view --log | cat

# Check status of latest run
PAGER=cat gh run list --limit 1
```

### Common Workflow Tasks
```bash
# List all workflows
PAGER=cat gh workflow list

# View workflow details
PAGER=cat gh workflow view <workflow-name>

# Trigger manual workflow
gh workflow run <workflow-name>

# Re-run failed jobs
gh run rerun <run-id>

# Cancel running workflow
gh run cancel <run-id>
```

### Pull Request Commands
```bash
# View PR checks status
PAGER=cat gh pr checks
gh pr checks | cat

# View PR status
PAGER=cat gh pr status

# View PR details
PAGER=cat gh pr view <pr-number>
```
