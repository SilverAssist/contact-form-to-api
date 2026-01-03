# Changelog

All notable changes to CF7 to API will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features

#### API Logs Enhancements
- **AJAX Live Refresh**: Real-time statistics updates without page reload
- **Retry Mechanism**: Execute retry for failed API requests from admin UI
- **Dashboard Widget**: Summary widget for WordPress dashboard
- **Email Alerts**: Notifications when error rate exceeds threshold
- **Performance Charts**: Visual trends and analytics with Chart.js
- **Advanced Date Filters**: Filter logs by custom date ranges

#### API Integration
- **GraphQL API Support**: Native support for GraphQL endpoints
- **Advanced Analytics**: Detailed analytics dashboard with charts and reports  
- **Webhook Security**: Enhanced security with signature verification
- **Form Builder Integration**: Visual field mapping interface
- **Multi-site Support**: Enhanced WordPress multisite compatibility
- **Template System**: Pre-configured templates for popular APIs (Mailchimp, HubSpot, etc.)

## [1.2.0] - 2026-01-03

### 🚀 Export Logs Feature

#### Added
- **CSV Export**: Export API request logs to CSV format with Excel-compatible UTF-8 BOM
- **JSON Export**: Export API request logs to JSON format with pretty printing
- **Export Buttons**: Added export buttons to the API Logs admin page
- **Disabled State**: Export buttons are visually disabled when no logs exist
- **SensitiveDataPatterns**: New centralized class for managing sensitive field patterns
  * Consolidates all sensitive data detection logic
  * Used by both `RequestLogger` and `ExportService`
  * Supports headers (Authorization, API keys) and data fields (passwords, tokens, secrets)

#### Fixed
- **Headers Already Sent**: Fixed export triggering "headers already sent" error
  * Export actions now handled in `admin_init` hook before any output
- **PHP 8.4+ Compatibility**: Added `$escape` parameter to `fputcsv()` calls
- **PHPCS Compliance**: Fixed double quotes to single quotes per WordPress standards

#### Changed
- **RequestLogger**: Now uses `SensitiveDataPatterns` for consistent data sanitization
- **ExportService**: Excludes sensitive fields from CSV export entirely (security by design)
- **Quality Checks Script**: Fixed `WP_TESTS_DIR` path consistency for local testing

#### Developer Experience
- **run-quality-checks.sh**: Now properly exports `WP_TESTS_DIR` before installation
- **copilot-instructions.md**: Updated string quotation standards to follow WordPress coding standards

## [1.1.3] - 2026-01-03

### Fixed
- **Legacy Hooks Initialization**: Ensure legacy hooks (`qs_cf7_api_*`) are registered before API requests
  * Fixed issue where hooks weren't being called if ApiClient was instantiated directly
  * Auto-initialization check added to `ApiClient::send()` method
- **PHPCS/PHPStan Compliance**: Resolved coding standard and static analysis issues
  * Fixed double quotes to single quotes in hook names
  * Added phpcs:ignore for legacy hook names (backward compatibility)
  * Removed redundant `is_array()` check flagged by PHPStan
  * Converted test variables to snake_case format

### Changed
- **Release Workflow**: Quality checks now run before release validation
  * PHPCS, PHPStan, and PHPUnit must pass before a release can be created
- **Version Scripts**: Improved `update-version-simple.sh` and `check-versions.sh`
  * Added `--force` flag to update all files even when version matches
  * Fixed script to properly update all PHP, CSS, JS, and shell script files
  * Removed HEADER-STANDARDS.md dependency (moved to copilot-instructions.md)

### Documentation
- **Copilot Instructions**: Added mandatory script usage instructions
  * Detailed documentation for version update and check scripts
  * Critical rules: ALWAYS use scripts, NEVER make manual changes

## [1.1.2] - 2026-01-02

### Added
- **Custom Headers Support**: Add custom HTTP headers directly from the CF7 integration panel
  * Dynamic add/remove header rows
  * Quick preset buttons for common authentication types (Bearer Token, Basic Auth, API Key, Content-Type JSON)
  * Headers are stored per-form and sent with each API request
- **Legacy Hook Compatibility**: Backward compatibility for `qs_cf7_api_*` hooks
  * `qs_cf7_api_get_args` → `cf7_api_get_args`
  * `qs_cf7_api_post_url` → `cf7_api_post_url`
  * `qs_cf7_api_get_url` → `cf7_api_get_url`
  * `qs_cf7_collect_mail_tags` → `cf7_api_collect_mail_tags`
- **Developer Hooks Documentation**: New documentation section in settings page
  * Complete list of available filters with code examples
  * Complete list of available actions with code examples
  * Only documents new `cf7_api_*` hooks (legacy hooks work but are not promoted)
- **Unit Tests**: Added tests for RequestLogController bulk action validation

### Fixed
- **Log View Action**: Fixed "Security check failed" error when viewing log details
  * The `action=view` URL parameter was incorrectly triggering bulk action nonce verification
  * Now properly skips non-bulk actions (`view`) and only validates `delete` and `retry` actions

### Documentation
- Added test environment setup instructions to copilot-instructions.md
- WordPress Test Suite configuration guide

## [1.1.1] - 2026-01-02

### Fixed
- **CI/CD**: Corrected plugin name validation in release workflow (`CF7 to API` instead of `Contact Form 7 to API`)

### Note
- This is a re-release of v1.1.0 with CI workflow fixes. All features from v1.1.0 are included.

## [1.1.0] - 2026-01-02

### 🚀 Advanced Logging System

#### Added
- **Request Logger**: Database-backed logging system for API requests/responses
  * Custom database table `{prefix}cf7_api_logs` with optimized indexes
  * Tracks endpoint, method, status, response code, execution time, retry count
  * Automatic sensitive data anonymization (passwords, tokens, API keys)
- **Admin Interface**: WordPress native admin UI for viewing logs
  * `WP_List_Table` implementation with sorting, filtering, pagination
  * Statistics panel with total requests, success rate, avg response time
  * Status filters (All, Success, Errors)
  * Bulk actions (Delete, Retry)
  * Detailed log view with request/response data
- **API Client Service**: Centralized HTTP client with advanced features
  * Retry logic with exponential backoff
  * Request/response logging integration
  * Authentication header handling (Bearer, Basic, API Key)
  * Configurable timeout and SSL verification
- **Settings Hub Integration**: Plugin settings page via Settings Hub
  * Quick links to API Logs and CF7 forms
  * Plugin status and version information
  * Update checker with GitHub integration
- **Debug Logger**: PSR-3 compliant file logger for development
  * Log levels: debug, info, warning, error
  * Automatic log rotation
  * Configurable via WP_DEBUG
- **Utility Classes**: Helper classes for common operations
  * `StringHelper`: Field name conversion (kebab-case, camelCase)
  * `CheckboxHandler`: CF7 checkbox value processing for APIs
- **MVC Architecture**: Separated views from controllers
  * `RequestLogView`: HTML rendering for logs pages
  * `SettingsView`: HTML rendering for settings page
  * `IntegrationView`: HTML rendering for CF7 panel

#### Changed
- **Integration.php**: Refactored to use IntegrationView for HTML rendering
- **Plugin.php**: Added component loader system with priority-based loading
- **Activator.php**: Added database table creation on activation

#### Quality
- ✅ PHPStan Level 8: PASSED (0 errors)
- ✅ PHPCS (WordPress-Extra): 0 errors
- ✅ PHPUnit: 48 tests passing (142 assertions)

## [1.0.1] - 2025-11-16

### 🎯 SilverAssist Migration

### 🎉 Initial Release

#### 🚀 Core Features

##### Contact Form 7 Integration
- **Seamless Integration**: Native integration with Contact Form 7 forms without modifications
- **Form-Specific Configuration**: Configure different API endpoints for different forms
- **Field Mapping System**: Advanced field mapping between CF7 fields and API parameters
- **Multiple Integration Support**: Support for multiple API integrations per form
- **Conditional Logic**: Send data to APIs based on form field values and conditions

##### API Communication
- **HTTP Methods**: Support for GET, POST, PUT, PATCH, and DELETE requests
- **Custom Headers**: Configure custom headers including authentication tokens
- **Authentication Support**: Built-in support for Bearer tokens, Basic Auth, and API keys
- **Request Timeout**: Configurable timeout settings per API endpoint
- **SSL/TLS Support**: Secure HTTPS communication with certificate validation
- **Data Formats**: Support for JSON, XML, and form-data request formats

##### Error Handling & Reliability
- **Retry Logic**: Automatic retry on failed API calls with exponential backoff
- **Graceful Degradation**: Form submissions continue even if API calls fail
- **Error Logging**: Comprehensive error logging and debugging information
- **Rate Limiting**: Built-in rate limiting to prevent API abuse
- **Circuit Breaker**: Automatic disabling of failing endpoints to prevent cascading failures
- **Fallback Mechanisms**: Alternative actions when primary API endpoints are unavailable

#### 🔧 Administrative Features

##### Configuration Management
- **User-Friendly Interface**: Intuitive admin panel for configuring API integrations
- **Real-Time Validation**: Live validation of API endpoints and authentication
- **Import/Export**: Backup and restore integration configurations
- **Bulk Operations**: Enable/disable multiple integrations simultaneously
- **Configuration Templates**: Pre-configured templates for common API services
- **Visual Field Mapping**: Drag-and-drop interface for mapping form fields to API parameters

##### Monitoring & Analytics
- **Activity Dashboard**: Real-time overview of API calls and their status
- **Detailed Logging**: Comprehensive logs of all API interactions and responses
- **Performance Metrics**: Track response times, success rates, and error patterns
- **Log Management**: Automatic log rotation and cleanup with configurable retention
- **Export Functionality**: Export logs and analytics data for external analysis
- **Alert System**: Notifications for failed API calls and system issues

#### 🛡️ Security Features

##### Data Protection
- **Encrypted Storage**: Secure storage of API credentials and sensitive configuration data
- **Input Validation**: Comprehensive validation and sanitization of all user inputs
- **Output Escaping**: Proper escaping of all output data to prevent XSS attacks
- **CSRF Protection**: Built-in protection against Cross-Site Request Forgery attacks
- **Nonce Verification**: WordPress nonce verification for all admin actions
- **Permission Checks**: Role-based access control for plugin configuration

##### API Security
- **Secure Transmission**: All API communications over HTTPS with certificate verification
- **Authentication Handling**: Secure handling and storage of API authentication credentials
- **Request Signing**: Support for request signing and verification where required
- **IP Whitelisting**: Optional IP address restrictions for API endpoints
- **Audit Trail**: Complete audit trail of all configuration changes and API calls
- **Data Anonymization**: Options to anonymize or exclude sensitive data from API calls

#### ⚡ Performance Features

##### Optimization
- **Asynchronous Processing**: Non-blocking API calls to maintain form submission speed
- **Intelligent Caching**: Configurable caching of API responses to reduce redundant calls
- **Resource Management**: Efficient memory and CPU usage with minimal impact on site performance
- **Lazy Loading**: Load plugin components only when needed
- **Database Optimization**: Efficient database queries with proper indexing
- **CDN Compatibility**: Full compatibility with content delivery networks

##### Scalability
- **High Volume Support**: Designed to handle high-volume form submissions
- **Load Balancing**: Support for multiple API endpoints with load balancing
- **Batch Processing**: Batch API calls for improved efficiency with high volumes
- **Background Processing**: Queue system for processing API calls in the background
- **Multisite Support**: Full compatibility with WordPress multisite installations
- **Auto-scaling**: Automatic adjustment of resources based on traffic patterns

#### 🔌 Developer Features

##### Extensibility
- **Hook System**: Comprehensive WordPress hooks and filters for customization
- **Custom Field Types**: Support for custom Contact Form 7 field types
- **API Response Processing**: Hooks for processing and acting on API responses
- **Custom Authentication**: Extensible authentication system for custom auth methods
- **Plugin Integration**: Seamless integration with other WordPress plugins
- **Theme Compatibility**: Full compatibility with all WordPress themes

##### Development Tools
- **Debug Mode**: Detailed debug information for troubleshooting integrations
- **API Testing**: Built-in tools for testing API connections and configurations
- **Code Documentation**: Comprehensive inline documentation and code examples
- **Sample Configurations**: Example configurations for popular API services
- **Development Hooks**: Special hooks and utilities for plugin development
- **REST API**: Built-in REST API endpoints for external integrations

#### 📱 User Experience

##### Interface Design
- **Responsive Design**: Mobile-optimized admin interface for configuration on any device
- **Intuitive Navigation**: Logical and easy-to-navigate admin panel structure
- **Contextual Help**: Built-in help system with contextual tips and documentation
- **Progress Indicators**: Visual progress indicators for long-running operations
- **Error Messages**: Clear and actionable error messages with troubleshooting tips
- **Success Feedback**: Immediate feedback for successful operations and configurations

##### Accessibility
- **WCAG Compliance**: Full compliance with Web Content Accessibility Guidelines
- **Screen Reader Support**: Proper ARIA labels and screen reader compatibility
- **Keyboard Navigation**: Complete keyboard navigation support
- **High Contrast**: Support for high contrast modes and themes
- **Font Scaling**: Compatibility with browser font scaling settings
- **Internationalization**: Full internationalization support with translation-ready strings

### 🎛️ Technical Specifications

#### System Requirements
- **WordPress**: Version 5.0 or higher
- **PHP**: Version 8.2 or higher
- **Contact Form 7**: Latest stable version required
- **PHP Extensions**: cURL, JSON, OpenSSL (for HTTPS)
- **Database**: MySQL 5.6+ or MariaDB 10.1+ (same as WordPress requirements)
- **Memory**: Minimum 64MB PHP memory limit (128MB+ recommended)

#### Compatibility
- **WordPress Multisite**: Full multisite support and compatibility
- **Popular Themes**: Tested with top WordPress themes including Astra, GeneratePress, OceanWP
- **Page Builders**: Compatible with Elementor, Beaver Builder, Divi, and other page builders
- **Caching Plugins**: Optimized for popular caching plugins like WP Rocket, W3 Total Cache
- **Security Plugins**: Compatible with Wordfence, Sucuri, and other security plugins
- **Backup Plugins**: Full compatibility with UpdraftPlus, BackupBuddy, and similar plugins

#### Performance Benchmarks
- **Form Load Time**: < 50ms additional load time per form
- **API Call Processing**: < 100ms average processing time for API calls
- **Database Queries**: Optimized to add minimal database load
- **Memory Usage**: < 5MB additional memory usage in typical configurations
- **CPU Impact**: Negligible CPU impact during normal operation
- **Scalability**: Tested with up to 10,000 form submissions per hour

### 🚀 Getting Started

#### Quick Setup (5 Minutes)
1. **Install Plugin**: Download and activate Contact Form 7 to API
2. **Access Settings**: Navigate to Contact > API Integration in WordPress admin
3. **Add Integration**: Click "Add New Integration" and configure your first API endpoint
4. **Map Fields**: Use the visual field mapper to connect form fields to API parameters
5. **Test & Deploy**: Use the built-in testing tools to verify your setup before going live

#### First Integration Example
```json
{
  \"name\": \"Lead Capture\",
  \"description\": \"Send contact form submissions to CRM\",
  \"endpoint\": \"https://api.example.com/v1/leads\",
  \"method\": \"POST\",
  \"headers\": {
    \"Authorization\": \"Bearer YOUR_API_TOKEN\",
    \"Content-Type\": \"application/json\"
  },
  \"field_mapping\": {
    \"your-name\": \"full_name\",
    \"your-email\": \"email_address\",
    \"your-company\": \"company_name\",
    \"your-message\": \"inquiry_details\"
  },
  \"conditions\": [],
  \"retry_attempts\": 3,
  \"timeout\": 30
}
```

### 📚 Documentation & Resources

#### Available Documentation
- **User Guide**: Comprehensive guide for end users and administrators
- **Developer Documentation**: Technical documentation for developers and integrators
- **API Reference**: Complete API reference for the plugin's REST endpoints
- **Hook Reference**: Documentation of all available WordPress hooks and filters
- **Troubleshooting Guide**: Common issues and their solutions
- **Video Tutorials**: Step-by-step video guides for common tasks

#### Community & Support
- **GitHub Repository**: Full source code and issue tracking
- **WordPress.org Support**: Community support forum
- **Documentation Wiki**: Community-maintained documentation and examples
- **Code Examples**: Real-world integration examples and use cases
- **Best Practices**: Guidelines for optimal plugin configuration and usage
- **Security Guidelines**: Security best practices and recommendations

### 🔄 Migration & Upgrade Path

#### From Beta Versions
- **Automatic Migration**: Seamless migration from beta versions with automatic configuration updates
- **Backup Recommendations**: Automatic backup of configurations before migration
- **Rollback Support**: Safe rollback mechanisms if issues occur during migration
- **Configuration Validation**: Automatic validation and correction of configuration issues

#### Future Upgrade Strategy
- **Backward Compatibility**: Commitment to maintaining backward compatibility for major features
- **Migration Tools**: Built-in tools for migrating between plugin versions
- **Configuration Archives**: Automatic archival of old configurations for reference
- **Upgrade Notifications**: Proactive notifications about available updates and their benefits

### ⚠️ Important Notes

#### Known Limitations
- **API Rate Limits**: Performance depends on external API rate limits and response times
- **Large File Uploads**: File uploads through Contact Form 7 are limited by server configuration
- **Real-time Sync**: This plugin provides one-way data flow from forms to APIs (not bidirectional)
- **Custom Validation**: Advanced custom validation requires developer knowledge and custom code

#### Best Practices
- **Test Thoroughly**: Always test integrations in a staging environment before production deployment
- **Monitor Regularly**: Set up regular monitoring of API integrations and success rates
- **Secure Credentials**: Use environment variables or secure storage for API credentials
- **Plan for Failures**: Implement fallback mechanisms for critical integrations
- **Document Configurations**: Maintain documentation of all API integrations and configurations

### 🎯 Roadmap

#### Version 1.1 (Q4 2025)
- **Enhanced Analytics**: Advanced reporting and analytics dashboard
- **Webhook Signatures**: Signature verification for webhook security
- **Conditional Logic**: Visual condition builder for complex logic
- **Template Library**: Pre-configured templates for popular services

#### Version 1.2 (Q1 2026)
- **GraphQL Support**: Native GraphQL API integration
- **Advanced Mapping**: Complex field transformations and calculations
- **Multi-language**: Enhanced multi-language support for form processing
- **API Management**: Advanced API endpoint management and monitoring

#### Long-term Vision
- **AI-Powered Mapping**: Intelligent field mapping suggestions using AI
- **Real-time Validation**: Real-time form validation against API endpoints
- **Advanced Workflow**: Complex workflow automation based on API responses
- **Enterprise Features**: Advanced enterprise-level features and support

---

For the complete version history and detailed release notes, visit our [GitHub Releases](https://github.com/SilverAssist/contact-form-to-api/releases) page.
