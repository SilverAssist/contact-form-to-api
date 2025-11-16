# Contact Form 7 to API - Copilot Instructions

## Project Overview
This is a WordPress plugin that integrates Contact Form 7 with external APIs, allowing form submissions to be sent to custom API endpoints with advanced configuration options. The plugin follows a **form-specific configuration approach** with no global admin panels - all settings are configured directly in each CF7 form via custom editor tabs.

## Architecture Overview
- **Approach**: Direct CF7 form integration with per-form API configuration
- **No Global Admin**: All configuration happens at the form level via CF7 editor tabs  
- **Streamlined Structure**: Three-layer architecture with clear separation of concerns
- **Main Plugin File**: Handles all dependency verification, lifecycle, and plugin setup
- **Core Plugin Class**: Lightweight coordinator for components and admin assets
- **Integration Class**: Complete CF7 functionality with all original plugin features preserved
- **Plugin Constants**: Centralized configuration via constants defined in main plugin file

## Development Standards

### Code Style & Quality
- Follow WordPress Coding Standards (WPCS)
- Use PSR-4 autoloading for namespaced classes
- Maintain consistent DocBlock formatting across all files
- All PHP files must have proper headers with @package, @since, @author, @version tags
- Use semantic versioning (semver.org)
- Maintain backward compatibility when possible

### Optimized File Structure
```
contact-form-to-api/
├── .github/
│   ├── workflows/          # GitHub Actions workflows
│   └── copilot-instructions.md
├── assets/
│   ├── css/admin.css      # CF7 admin styles (modern)
│   └── js/admin.js        # CF7 admin scripts (ES6+)
├── languages/             # Translation files (empty, ready for i18n)
├── scripts/               # Development and build scripts
│   ├── build-release.sh
│   ├── check-versions.sh
│   └── update-version-simple.sh
├── src/                   # Streamlined source code (PSR-4)
│   ├── Core/Plugin.php    # Main plugin initialization
│   └── ContactForm/Integration.php # Complete CF7 integration
├── contact-form-to-api.php # Main plugin file
├── composer.json         # Dependencies and PSR-4 autoloading
├── CHANGELOG.md          # Version history
├── MIGRATION-SUMMARY.md  # Migration documentation
├── README.md             # Project documentation
└── HEADER-STANDARDS.md   # Header formatting standards
```

## Core Components Architecture

### contact-form-to-api.php - Main Plugin File  
**Purpose**: Plugin entry point with dependency verification and lifecycle management
**Pattern**: Singleton with comprehensive requirement checking
**Key Responsibilities**:
- Plugin constants definition (VERSION, TEXT_DOMAIN, paths, requirements)
- WordPress/PHP/CF7 dependency verification with admin notices
- Plugin activation/deactivation lifecycle management
- Database table creation and plugin options setup
- Textdomain loading for i18n
- Composer dependency verification
- Core Plugin class initialization

**Core Methods**:
- `check_requirements()`: Comprehensive dependency verification
- `activate()/deactivate()`: Complete plugin lifecycle management  
- `create_database_tables()`: API logs table creation
- `load_textdomain()`: i18n initialization
- `php_version_notice()/wp_version_notice()/cf7_dependency_notice()`: Admin notices
- `is_contact_form_7_active()`: CF7 detection using multiple methods

### src/Core/Plugin.php - Component Coordinator
**Purpose**: Lightweight component coordinator and asset manager
**Size**: ~180 lines - Focused on coordination and admin assets
**Pattern**: Singleton focusing on component orchestration

**Core Responsibilities**:
- ContactForm Integration initialization
- Admin asset management (CSS/JS loading)
- Component coordination between modules
- Admin scripts localization for CF7 pages

**Key Methods**:
- `init_components()`: Initialize CF7 Integration component
- `register_hooks()`: Admin-only hook registration
- `admin_enqueue_scripts()`: CF7-specific asset loading with localization
- Uses plugin constants from main file for version and paths

### src/ContactForm/Integration.php - Complete CF7 Integration
**Purpose**: Complete Contact Form 7 to API integration functionality
**Size**: ~700 lines - All original plugin features preserved  
**Integration Method**: Direct CF7 hooks and filters

**Core Functionality**:
- **CF7 Editor Integration**: Adds "API Integration" tab via `wpcf7_editor_panels`
- **Form Processing**: Hooks into `wpcf7_before_send_mail` for API calls
- **Field Mapping**: Dynamic mapping between CF7 fields and API parameters
- **Multiple Formats**: Support for GET/POST params, JSON, and XML payloads
- **Debug Logging**: Comprehensive error tracking and debugging
- **Mail Tag System**: Custom mail tags for dynamic content

**Key Methods**:
- `add_integrations_tab()`: Adds CF7 editor tab
- `render_integration_panel()`: Renders form configuration UI
- `send_data_to_api()`: Main API communication handler  
- `get_record()`: Data transformation and mapping
- `send_lead()`: HTTP request execution with retry logic
- `parse_json()/get_xml()`: Format-specific processors
- `log_error()`: Error logging and debugging
- `get_mail_tags()`: Available form fields extraction

**Supported Features**:
- GET, POST, PUT, PATCH HTTP methods
- JSON and XML payload formats
- Custom headers and authentication
- Field validation and transformation
- Error handling with retry mechanisms
- Debug mode with detailed logging

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
**Size**: ~500 lines of modern JavaScript

**Core Class: CF7ApiAdmin**
**Key Methods**:
- `handleInputTypeChange()`: Dynamic UI for params/XML/JSON modes
- `insertMailTag()`: Smart mail tag insertion with cursor positioning
- `validateUrl()`: Real-time API URL validation
- `testApiConnection()`: Async API endpoint testing
- `toggleDebugLog()`: Debug information display control
- `showValidationMessage()`: User feedback system

## Plugin-Specific Architecture Standards

### Contact Form 7 Integration Approach
- **Direct Form Integration**: All configuration happens directly in CF7 form edit pages via custom tab
- **No General Admin Panel**: Plugin has no global settings page - all configuration is form-specific
- **Tab Integration**: Use CF7's `wpcf7_editor_panels` filter to add custom "API Integration" tab
- **Form Data Processing**: Hook into `wpcf7_before_send_mail` for form submission processing
- **Field Mapping**: Support dynamic field mapping between CF7 fields and API parameters
- **Multiple Formats**: Support GET/POST parameters, JSON, and XML payload formats

### API Integration Standards
- **Multiple Methods**: Support GET, POST, PUT, PATCH requests
- **Flexible Authentication**: Support Bearer tokens, Basic Auth, API keys, and custom headers
- **Error Handling**: Comprehensive error logging and retry mechanisms
- **Debug Logging**: Store last API call details for troubleshooting
- **Field Transformation**: Support data transformation during field mapping
- **Conditional Processing**: Support conditional API calls based on form data

### Version Management Standards
- Version numbers are managed centrally and updated via scripts
- Use update-version-simple.sh for version updates
- All @version tags across files must remain synchronized
- Version consistency is verified via check-versions.sh

### Header Standards
- All files must include consistent headers with project information
- Use @since tag for when functionality was introduced
- Use @version tag for current version (updated with releases)
- Follow the format specified in HEADER-STANDARDS.md

## Development Commands

### Version Management
- `./scripts/update-version-simple.sh X.Y.Z` - Update version across all files
- `./scripts/check-versions.sh` - Verify version consistency
- `./scripts/build-release.sh` - Create release package

### Quality Assurance
- `composer install` - Install dependencies
- `composer validate` - Validate composer.json
- Run WordPress coding standards checks
- Execute unit test suite

## Release Process
1. Update version using update-version-simple.sh
2. Update CHANGELOG.md with release notes
3. Verify all tests pass
4. Create release commit and tag
5. Build release package
6. Deploy to WordPress.org (if applicable)

## Support & Maintenance
- Maintain compatibility with latest WordPress versions
- Support latest Contact Form 7 versions
- Regular security audits
- Performance optimization
- Community support and documentation

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

## Modern PHP 8+ Conventions

### PHP Coding Standards
- **Double quotes for all strings**: `"string"` not `'string'` - MANDATORY for both PHP and JavaScript
- **String interpolation**: Use `"prefix_{$variable}"` instead of `"prefix_" . $variable` when concatenating variables into strings
- **Short array syntax**: `[]` not `array()`
- **Namespaces**: Use descriptive namespaces like `ContactFormToAPI\ComponentType`
- **Singleton pattern**: `Class_Name::getInstance()` method pattern
- **WordPress hooks**: `\add_action("init", [$this, "method"])` with array callbacks
- **PHP 8+ Features**: Match expressions, array spread operator, typed properties
- **Match over Switch**: Use `match` expressions instead of `switch` statements when possible for cleaner, more concise code
- **Global function calls**: Use `\` prefix **ONLY for WordPress functions** in namespaced context (e.g., `\add_action()`, `\get_option()`, `\is_ssl()`). PHP native functions like `array_key_exists()`, `explode()`, `trim()`, `sprintf()` do NOT need the `\` prefix.
- **WordPress Function Rule**: ALL WordPress core functions, WordPress API functions, and plugin functions MUST use the `\` prefix when called from within namespaced classes
- **PHP Native Function Rule**: PHP built-in functions (string, array, math, etc.) should NOT use the `\` prefix as they are automatically resolved
- **WordPress i18n**: All user-facing strings MUST use WordPress i18n functions (`\__()`, `\esc_html__()`, `\esc_attr__()`, `\_e()`, `\esc_html_e()`) with text domain `"contact-form-to-api"`

### PHP `use` Statement Standards - MANDATORY COMPLIANCE

#### **Import Organization & Ordering**
- **MANDATORY**: All `use` statements MUST be placed at the top of the file, immediately after the namespace declaration
- **Alphabetical Ordering**: ALWAYS sort `use` statements alphabetically for consistent organization
- **No In-Method Imports**: NEVER use fully qualified class names within methods - use `use` statements instead
- **Same Namespace Rule**: NEVER import classes that are in the same namespace as the current file

## Coding Guidelines Specific to This Project
- Namespace: `ContactFormToAPI`
- Text domain: `"contact-form-to-api"`
- Minimum WordPress version: 5.0
- Minimum PHP version: 7.4
- Contact Form 7 dependency required
- Follow WordPress plugin development best practices

When working on this project:
1. Always maintain the established file structure
2. Follow the header standards exactly
3. Update version numbers using the provided scripts
4. Test all API integrations thoroughly
5. Document any new functionality
6. Maintain security best practices
7. Ensure WordPress and Contact Form 7 compatibility
8. Use double quotes for ALL strings - NO EXCEPTIONS
9. Implement form-specific configuration via CF7 editor tabs
10. No global admin panels - all settings are per-form
11. **ALWAYS use plugin constants** instead of hardcoded values - see Plugin Constants section below

## Plugin Constants - MANDATORY USAGE

### Available Plugin Constants
The following constants are defined in the main plugin file (`contact-form-to-api.php`) and **MUST be used** instead of hardcoded values:

```php
// Core plugin information
define("CONTACT_FORM_TO_API_VERSION", "1.0.0");
define("CONTACT_FORM_TO_API_PLUGIN_FILE", __FILE__);
define("CONTACT_FORM_TO_API_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("CONTACT_FORM_TO_API_PLUGIN_URL", plugin_dir_url(__FILE__));
define("CONTACT_FORM_TO_API_PLUGIN_BASENAME", plugin_basename(__FILE__));
define("CONTACT_FORM_TO_API_TEXT_DOMAIN", "contact-form-to-api");

// System requirements
define("CONTACT_FORM_TO_API_MIN_PHP_VERSION", "8.2");
define("CONTACT_FORM_TO_API_MIN_WP_VERSION", "6.5");
```

### Mandatory Constant Usage Rules
- **Text Domain**: ALWAYS use `CONTACT_FORM_TO_API_TEXT_DOMAIN` for i18n functions
  - ✅ Correct: `\__("Text", CONTACT_FORM_TO_API_TEXT_DOMAIN)`
  - ❌ Wrong: `\__("Text", "contact-form-to-api")`

- **Plugin Version**: ALWAYS use `CONTACT_FORM_TO_API_VERSION` for version references
  - ✅ Correct: `CONTACT_FORM_TO_API_VERSION`
  - ❌ Wrong: `"1.0.0"`

- **Plugin Paths**: ALWAYS use path constants for file operations
  - ✅ Correct: `CONTACT_FORM_TO_API_PLUGIN_DIR . "assets/css/admin.css"`
  - ❌ Wrong: `plugin_dir_path(__FILE__) . "assets/css/admin.css"`

- **Plugin URLs**: ALWAYS use URL constants for asset loading
  - ✅ Correct: `CONTACT_FORM_TO_API_PLUGIN_URL . "assets/js/admin.js"`
  - ❌ Wrong: `plugin_dir_url(__FILE__) . "assets/js/admin.js"`

### Benefits of Using Constants
1. **Centralized Configuration**: One place to change values
2. **Consistency**: Same values used throughout the plugin
3. **Maintenance**: Easy to update plugin information
4. **Version Management**: Automated version updates via scripts
5. **Error Prevention**: Reduces typos in hardcoded values
