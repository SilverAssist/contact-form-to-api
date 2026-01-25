# Changelog

All notable changes to CF7 to API will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Form Filter Dropdown**: Visible dropdown selector to filter logs by Contact Form 7 form
  - Dropdown appears in filter controls between Status and Date filters
  - Lists all forms that have at least one log entry
  - Shows "All Forms" default option to display logs from all forms
  - Gracefully handles deleted forms (displays as "Form #123")
  - Auto-submits on selection for immediate filtering (no button click needed)
  - Active filter tag shows selected form with individual remove capability
  - Filter persists across pagination and combines with status/date filters
  - Alphabetically ordered by form title for easy navigation
  - New `LogReader::get_forms_with_logs()` method retrieves distinct forms with logs
  - Comprehensive unit test coverage (6 tests for all scenarios)

## [2.0.0] - 2026-01-24

### Added

- **MVC Architecture**: Complete restructuring following Model-View-Controller principles
  - **Model Layer**: Type-safe domain models (`LogEntry`, `FormSettings`, `ApiResponse`, `Statistics`)
  - **Repository Layer**: Data access interfaces (`LogRepositoryInterface`, `SettingsRepositoryInterface`)
  - **Controller Layer**: Request routing and hook management
    - `Controller/Admin/LogsController`: Admin logs page routing
    - `Controller/ContactForm/SubmissionController`: Form submission handling
  - **Service Layer**: Business logic separated from controllers
    - `Service/Logging/`: LogWriter, LogReader, LogStatistics, RetryManager
    - `Service/Security/`: EncryptionService, SensitiveDataPatterns
    - `Service/ContactForm/SubmissionProcessor`: API communication logic
  - **View Layer**: Reusable partials for maintainability
    - `View/Admin/Logs/Partials/`: StatisticsPartial, DateFilterPartial, ExportButtonsPartial
    - `View/Admin/Settings/Partials/GlobalSettingsPartial`
  - **Config Layer**: Centralized configuration (`Config/Settings`)
- **Unresolved Errors Filter**: New filter to show only errors that haven't been successfully retried
  - "Unresolved" tab in logs table shows errors pending resolution
  - "All Errors" renamed to distinguish from unresolved filter
  - Error resolution counts displayed in filter badges
- **Search by Sender Name/Lastname**: Extended search functionality to filter logs by sender name
  - Search now includes name and lastname fields from form submissions
  - Respects anonymization rules: fields marked as sensitive via settings are excluded from search
  - Maintains existing SQL-based search for endpoint and error_message (full dataset)
  - PHP-based filtering for name/lastname applied to the first 5,000 logs in the current sort order; on sites with more than 5,000 logs, name/lastname searches may not include older matches

### Changed

- **Visual Resolved Indicator**: Errors with successful retries now show a "Resolved" badge
  - Green badge appears next to error status when retry was successful
  - Tooltip explains the error was resolved via manual retry
  - Makes it easy to identify resolved errors at a glance

### Developer

- **New Service Classes**: Specialized services following Single Responsibility Principle
  - `Service\Logging\LogWriter`: Log creation, updates, deletion with encryption
  - `Service\Logging\LogReader`: Log retrieval with decryption
  - `Service\Logging\LogStatistics`: Statistics and metrics calculations
  - `Service\Logging\RetryManager`: Retry management and error resolution tracking
  - `Service\ContactForm\SubmissionProcessor`: Form submission business logic
- **New Model Classes**: Type-safe domain models with full PHPStan Level 8 compliance
  - `Model\LogEntry`: API request log representation
  - `Model\FormSettings`: Form configuration
  - `Model\ApiResponse`: API response data
  - `Model\Statistics`: Aggregated statistics
- **Exception Classes**: Custom exceptions for better error handling
  - `Exception\ApiException`: API-related errors
  - `Exception\ValidationException`: Validation errors with detailed tracking
- **PSR-4 Namespace Organization**: Proper directory structure
  - `Service\Security\*`: Security-related services
  - `Config\Settings`: Configuration management
- **Architecture Documentation**: See `docs/ARCHITECTURE.md` for complete structure
- **Migration Guide**: See `docs/UPGRADE.md` for 2.0.0 migration instructions

### Notes

- **Quality Gates**: PHPCS WordPress-Extra (0 errors) and PHPStan Level 8 (0 errors) compliance
- All logging functionality migrated to dedicated services in `Service\Logging\`
- See `docs/UPGRADE.md` for migration guidance

## [1.3.13] - 2026-01-23

### Added

- **New "From" Column in Logs Table** (#54): Quick sender identification without opening log details
  - Displays sender name and masked email (e.g., `John Doe <jo***@example.com>`)
  - Extracts data from common form fields (name, firstname, your-name, email, your-email, etc.)
  - Email masking preserves privacy while allowing identification
  - Shows "Unknown" when sender info cannot be extracted

### Changed

- **Renamed "Form" Column to "Channel"** (#54): Better reflects the source/channel of submission
- **Improved Endpoint Column** (#54): Fixed width with CSS text-overflow ellipsis
  - Full URL visible on hover via title attribute
  - Cleaner table layout without PHP-based truncation

### Fixed

- **Empty Status Filter Bug** (#54): Fixed table showing no results when URL has empty status parameter
  - Added `! empty()` check to prevent filtering with empty string
  - URLs like `?status&date_filter` now work correctly
- **Status Filter Auto-Submit** (#54): Status dropdown now submits form automatically on change
- **"All Time" Date Filter Auto-Submit** (#54): Previously only non-empty filter values triggered auto-submit

## [1.3.11] - 2026-01-22

### Fixed

- **Log Filters UI** (#50, #48): Fixed broken filters after refactor
  - Filters now use the WordPress `.filtered-by` pattern
  - Active filters are shown as removable tags
  - "Clear all" link added, duplicate "Clear Filters" button removed
  - Unified into a single filter bar with native admin styles

## [1.3.10] - 2026-01-22

### Added

- **Status Filter Dropdown** (#47): New status filter UI control on logs page
  - Dropdown with "All", "Success", and "Error" options
  - Appears alongside date filter for consistent UI
  - Preserves form_id and search parameters when filtering
  - Combined active filters badge shows both status and date filters
  - Already integrated with pagination, sorting, and export URLs
- **Date-Aware Statistics Grid** (#47): Stats now reflect active date filters
  - Stats grid displays date context label (e.g., "Total Requests (Yesterday)")
  - All statistics (total, successful, failed, avg time) respect date filters
  - Date context shown as "(All Time)", "(Today)", "(Last 7 Days)", etc.

### Changed

- **Dashboard Widget Recent Errors** (#47): Now limited to last 24 hours
  - `get_recent_errors()` accepts optional `$hours` parameter
  - Dashboard widget passes 24-hour filter to match other statistics
  - More relevant error display focusing on recent issues
- **Failed Requests Count** (#47): Excludes successfully retried errors
  - `get_statistics()` uses subquery to exclude errors with successful retries
  - Matches logic from `get_count_last_hours()` for consistency
  - Shows only unresolved failures in stats grid

### Enhanced

- **`RequestLogger::get_statistics()`** (#47):
  - Now accepts optional date parameters: `get_statistics(?int $form_id, ?string $date_start = null, ?string $date_end = null)`
  - Returns statistics filtered by date range when parameters provided
  - Failed requests count excludes successfully retried errors
  - Backward compatible (date parameters optional)
- **`RequestLogger::get_recent_errors()`** (#47):
  - Now accepts optional hours parameter: `get_recent_errors(int $limit = 5, ?int $hours = null)`
  - Filters errors by time window when hours specified
  - Backward compatible (hours parameter optional)

### Documentation

- **USER_GUIDE.md** (#47):
  - Added dedicated "Status Filter" section
  - Updated filtering examples with combined filter use cases
  - Updated "Clearing Filters" to reference both status and date filters
- **API_REFERENCE.md** (#47):
  - Updated `get_statistics()` method documentation with new parameters
  - Updated `get_recent_errors()` method documentation with new parameters
  - Added examples for date-filtered statistics queries

## [1.3.9] - 2026-01-19

### Fixed

- **Dashboard Widget Error Count** (#46): Errors with successful manual retries are now excluded from the error count
  - `get_count_last_hours()` excludes errors that have a successful retry entry
  - Prevents inflated error counts when issues have been resolved via manual retry
  - Error count now reflects only unresolved failures
- **Dashboard Widget Settings Button URL** (#46): Fixed Settings button redirecting to wrong page
  - Changed from `?page=cf7-api-settings` to `?page=contact-form-to-api`

### Changed

- **Success Rate Calculation**: `get_success_rate_last_hours()` now considers successfully retried errors as effective successes
  - Original errors with successful retries contribute positively to success rate
  - More accurate representation of actual API reliability

### Developer

- **RequestLogger Query Updates**:
  - `get_count_last_hours()` with `status='error'` now uses subquery to exclude retried errors
  - `get_success_rate_last_hours()` tracks `retried_successfully` count separately
  - `get_recent_errors()` already excluded retried errors (no change needed)
- **PHPCS Compliance**: Changed SQL string assignments to single quotes with escaped inner quotes per WordPress Coding Standards

## [1.3.8] - 2026-01-15

### Added

- **Manual Retry Traceability** (#44): Complete visibility into retry outcomes for failed API requests
  - New link from original failed entry to successful retry: "→ View successful retry (#102)"
  - "Manual Retry Count" field in Request Information section (separate from automatic retries)
  - Green success styling on retry information notice when retry succeeded
  - Integration test suite (`RetryTraceabilityTest.php`) covering all retry scenarios

### Changed

- **Retry Button Behavior**: Disabled after successful manual retry with tooltip "Already successfully retried"
- **List View Actions**: "Retry" action hidden for entries that already have a successful manual retry
- **Bulk Retry Operations**: Skip entries with successful retries during bulk retry, with feedback on skipped count

### Fixed

- **Duplicate Retry Prevention**: Users can no longer create unnecessary retry attempts after success

### Developer

- **RequestLogger New Methods**:
  - `get_successful_retry_id(int $log_id): ?int` - Returns ID of first successful retry
  - `has_successful_retry(int $log_id): bool` - Boolean check for successful retry existence
  - `get_retries_for_log(int $log_id): array` - Returns all retry attempts with status
- **Performance**: Single `RequestLogger` instance cached per page render, all queries use existing `retry_of` index (O(log n))
- **No database schema changes required**

## [1.3.7] - 2026-01-06

### Fixed

- **Sensitive Data Anonymization**: Fixed case-insensitive matching for custom sensitive patterns
  - Custom patterns with mixed case (e.g., `primaryPhone`, `primaryEmail`) now correctly match field names
  - Pattern comparison is now case-insensitive on both sides (field name and pattern)

### Added

- **Test Coverage**: Added test for custom sensitive patterns with mixed case from Settings

## [1.3.6] - 2026-01-06

### Changed

- **Version bump**: Re-release of v1.3.5 due to GitHub tag immutability constraints
- All changes from v1.3.5 are included in this release

## [1.3.5] - 2026-01-06 [YANKED]

> **Note**: This version was yanked due to GitHub tag immutability. Use v1.3.6 instead.

#### Medium Priority

- **Performance Charts**: Visual trends and analytics dashboard with Chart.js
  - Success/error rate over time
  - Response time trends
  - Top endpoints by volume
- **Template System**: Pre-configured templates for popular APIs
  - HubSpot, Mailchimp, Salesforce, Zapier
  - One-click setup with guided configuration

#### Low Priority

- **Multi-site Support**: Enhanced WordPress multisite compatibility
  - Network-wide settings management
  - Per-site configuration overrides

## [1.3.5] - 2026-01-06

### Added

- **Legacy Hook Compatibility** (#38): Full backward compatibility with Query Solutions "cf7-to-api" plugin hooks
  - Centralized all legacy hook bridges in `Integration::register_legacy_hooks()`
  - Legacy hooks run at priority 5 (before new hooks at priority 10)
  - Supports themes/plugins using `qs_cf7_*` hooks without code changes
- **Dual Plugin Conflict Detection**: Admin notice when both legacy and new plugins are active
  - Warns users about potential duplicate API submissions
  - Dismissible notice with clear instructions
- **Automatic Database Schema Migration**: Auto-updates database on admin_init
  - Adds missing `encryption_version` column for upgrades from older versions
  - Version-tracked schema updates via `cf7_api_db_version` option
- **API Reference Documentation**: Comprehensive hooks documentation in `docs/API_REFERENCE.md`
  - Complete hook mapping table (legacy → new)
  - Migration guide with code examples
  - All 9 legacy hooks documented with examples

### Changed

- **Hook Registration Centralization**: Moved all legacy hook bridges from `ApiClient.php` to `Integration.php`
  - Single location for all backward compatibility hooks
  - Improved maintainability and reduced code duplication
  - Clear PHPDoc documentation for each hook mapping

### Fixed

- **Theme Compatibility**: Fixed issue where themes using legacy hooks (e.g., Oasis theme) weren't triggering API logs
  - Legacy hook `qs_cf7_api_before_sent_to_api` now correctly bridges to `cf7_api_before_send_to_api`
  - Note: Legacy used "sent" (past tense), new uses "send" (present tense)

### Legacy Hook Mappings

| Legacy Hook | New Hook | Type |
|-------------|----------|------|
| `qs_cf7_collect_mail_tags` | `cf7_api_collect_mail_tags` | Filter |
| `qs_cf7_api_before_sent_to_api` | `cf7_api_before_send_to_api` | Action |
| `qs_cf7_api_after_sent_to_api` | `cf7_api_after_send_to_api` | Action |
| `set_record_value` | `cf7_api_set_record_value` | Filter |
| `cf7api_create_record` | `cf7_api_create_record` | Filter |
| `qs_cf7_api_get_args` | `cf7_api_get_args` / `cf7_api_post_args` | Filter |
| `qs_cf7_api_get_url` | `cf7_api_get_url` | Filter |
| `qs_cf7_api_post_url` | `cf7_api_post_url` | Filter |

## [1.3.4] - 2026-01-06

### Added

- **Legacy Log Migration Tool** (#37): Batch migration tool to encrypt existing unencrypted logs
  - One-click migration from Global Settings encryption section
  - AJAX-based batch processing with real-time progress bar
  - Dry-run mode for previewing changes without committing
  - Pause/cancel capability during migration
  - Automatic detection of legacy plaintext logs (encryption_version = 0)
  - Transient-based progress tracking for resume capability
  - Comprehensive unit tests with 13 test cases

### Fixed

- **Custom Headers Not Saving** (#36): Fixed issue where custom headers configured in the Authentication & Custom Headers section were not persisting after saving the form
  - Changed filter hook from `wpcf7_contact_form_properties` to `wpcf7_pre_construct_contact_form_properties`
  - CF7's `set_properties()` uses `array_intersect_key()` which filters out properties not in defaults
  - New hook ensures custom properties are registered in CF7's defaults before filtering occurs

## [1.3.3] - 2026-01-05

### Fixed

- **Release workflow**: Fixed immutable release issue preventing artifact upload

## [1.3.2] - 2026-01-05

### Fixed

- **Release workflow**: Updated all @version tags to match plugin version (fixes CI version consistency check)

## [1.3.1] - 2026-01-05

### Added

- **Integration Tests for Form Submission Logging**: Comprehensive test suite validating API logging functionality
  - 19 new integration tests in `FormSubmissionLoggingTest.php`
  - Mock HTTP responses via WordPress `pre_http_request` filter
  - Tests for success, error, retry counts, execution time, and HTTP status codes
- **API Testing Script**: Shell script for manual API submission testing (`scripts/test-api-submission.sh`)

### Fixed

- **Settings Page Redirect URL** (#37): Fixed incorrect redirect after saving global settings
  - Changed redirect from `?page=cf7-api-settings` to `?page=contact-form-to-api`
- **API Logs Menu Visibility**: Hide "API Logs" submenu when logging is disabled in global settings
  - Prevents user confusion when logging is turned off
  - Added access blocking for direct URL access to logs page
  - Added blocking for CSV export when logging is disabled
- **Form Integration Panel UX**: Hide "Recent API Calls" and "Statistics" sections when logging is disabled
  - Shows informative notice with link to Global Settings to enable logging
  - Prevents confusion from showing stale logs when new logs aren't being captured

### Changed

- **RequestLogController**: Added logging status checks throughout the controller
  - New `is_logging_enabled()` private method
  - Menu registration checks logging status before adding submenu
  - Page request handler blocks access when logging disabled
  - Export handler blocks exports when logging disabled

## [1.3.0] - 2026-01-05

### Added

- **Database-Level Encryption for Sensitive Request Data** (#33): Encrypt API logs at rest using libsodium
  - New `EncryptionService` class using libsodium (Sodium) authenticated encryption
  - XSalsa20 stream cipher with Poly1305 MAC for data integrity
  - HKDF key derivation from WordPress `AUTH_KEY` constant
  - Secure fallback key generation stored in `wp_options` if `AUTH_KEY` unavailable
  - Encrypted fields: `request_data`, `request_headers`, `response_data`, `response_headers`
  - New `encryption_version` column in database (0=plaintext, 1=encrypted)
  - Transparent decryption via `RequestLogger::decrypt_log_fields()` method
  - Encryption settings in Global Settings page with status indicator
  - Statistics showing encrypted vs unencrypted logs count
  - Graceful degradation: falls back to plaintext if Sodium unavailable
  - Backward compatible with existing unencrypted logs

### Fixed

- **Data Anonymization Breaking Retry Functionality** (#31): Moved sensitive data anonymization from storage layer to presentation layer
  - Original form data now stored in database (needed for retry functionality)
  - Authorization headers still redacted at storage (security requirement)
  - UI views anonymize data at render time using `RequestLogger::anonymize_data()`
  - Export functionality continues to anonymize data correctly
  - Backward compatible with existing anonymized logs

## [1.2.1] - 2026-01-03

### Fixed

- **CSS Class Conflicts**: Renamed generic `.success` and `.error` classes to `.stat-success` and `.stat-error` to prevent external JavaScript from manipulating stat boxes
- **CSS Specificity**: Added `!important` to `.cf7-api-hidden` utility class to ensure it overrides other display rules

## [1.2.0] - 2026-01-03

### Added

- **Email Alerts for High API Error Rates**: Proactive monitoring with email notifications (#24)
  - New `EmailAlertService` monitors hourly error statistics via WordPress cron
  - Dual threshold detection: error count (default: 10/hr) and error rate (default: 20%)
  - Configurable cooldown period (1-24 hours) prevents alert spam
  - HTML email template with error details, recent failures, and direct log link
  - Multiple recipients support (comma-separated email addresses)
  - "Send Test Email" button with AJAX validation
  - Dynamic cron rescheduling when check interval changes
  - All alert settings stored in `cf7_api_global_settings` option
- **Retry Failed Requests from Admin UI**: Manual retry mechanism for failed API requests (#21)
  - Retry button on log detail page for failed requests (error, client_error, server_error statuses)
  - Bulk retry action in logs list table for multiple failed requests
  - Rate limiting: Maximum 3 retries per log entry, 10 retries per hour globally
  - New log entries created for retries, linked to original via `retry_of` column
  - Retry information displayed on detail page (shows if entry is a retry and retry count)
  - Comprehensive admin notices for retry results (success, failed, skipped, rate limit)
  - Confirmation dialogs for single and bulk retry actions
  - All retry-related UI strings are translatable (i18n ready)
- **Database Schema Enhancement**: Added `retry_of` column to `cf7_api_logs` table
  - Links retry attempts to original failed requests
  - Indexed for performance on retry tracking queries
  - Automatically added via `dbDelta()` on existing installations
- **RequestLogger New Methods**: Enhanced logging capabilities for retry functionality
  - `get_log()`: Retrieve single log entry by ID
  - `get_request_for_retry()`: Extract complete request data for replay
  - `count_retries()`: Count retry attempts for a specific log entry
  - `start_request()`: Now accepts optional `$retry_of` parameter to link retries
- **ApiClient Retry Method**: New `retry_from_log()` method
  - Replays failed requests from log history
  - Preserves original request data (URL, method, headers, body)
  - Automatically detects content type from headers
  - Returns detailed result with success status and new log ID
- **Advanced Date Range Filters**: Filter logs by custom date ranges (#19)
  - Preset filters: Today, Yesterday, Last 7 Days, Last 30 Days, This Month
  - Custom date range picker with HTML5 date inputs
  - Client-side date validation with error alerts
  - Filters persist across pagination via URL parameters
  - Statistics update to reflect filtered date range
  - All UI strings are translatable (i18n ready)
- **DateFilterTrait**: Centralized trait for date filtering logic
  - Shared between `RequestLogTable` and `RequestLogController`
  - Eliminates code duplication (DRY principle)
  - Methods: `build_date_filter_clause()`, `build_custom_date_range_clause()`, `is_valid_date_format()`, `get_date_filter_params()`
- **Dashboard Widget**: Summary widget for WordPress dashboard displaying API request statistics (#23)
  - At-a-glance statistics: total requests, success rate, and average response time (last 24 hours)
  - Recent errors list showing last 5 failed requests with timestamps and error messages
  - Quick action links to "View All Logs" and "Settings" pages
  - Color-coded success rate indicator (green ≥90%, yellow 70-90%, red <70%)
  - Error count badge with warning indicator
  - Responsive design for all viewport sizes
  - Capability-based visibility (only visible to users with `manage_options`)
  - Can be hidden via WordPress Screen Options
- **Export Logs Feature**: Export API request logs in multiple formats (#20)
  - CSV export with all log fields for spreadsheet analysis (UTF-8 BOM for Excel)
  - JSON export with full structured data with pretty printing
  - Filter-aware exports (respects current search and status filters)
  - Secure file handling with proper HTTP headers
  - Disabled state for export buttons when no logs exist
  - Export limit of 10,000 records to prevent memory exhaustion
- **SensitiveDataPatterns**: New centralized class for managing sensitive field patterns
  - Consolidates all sensitive data detection logic
  - Used by both `RequestLogger` and `ExportService`
  - Supports headers (Authorization, API keys) and data fields (passwords, tokens, secrets)
  - Now reads custom patterns from global settings
- **RequestLogger Statistics Methods**: New methods for dashboard analytics
  - `get_count_last_hours()`: Count requests in time window with optional status filter
  - `get_success_rate_last_hours()`: Calculate success percentage
  - `get_avg_response_time_last_hours()`: Average response time in milliseconds
  - `get_recent_errors()`: Retrieve most recent failed requests
- **Global Plugin Settings Page**: Centralized configuration for plugin-wide settings (#28)
  - New `Core\Settings` singleton class for settings management
  - New `Admin\GlobalSettingsController` for form handling
  - **Retry Configuration**: Configure `max_manual_retries` and `max_retries_per_hour` from admin
  - **Sensitive Data Patterns**: Add custom field patterns for anonymization (project-specific)
  - **Logging Control**: Toggle API request logging on/off (useful for GDPR compliance)
  - **Log Retention**: Auto-delete logs older than X days via WP-Cron (7, 14, 30, 60, 90 days or never)
  - Settings Hub integration for unified admin experience
  - All settings persist to `cf7_api_global_settings` option
  - Sensible defaults with graceful fallbacks
  - All UI strings are translatable (i18n ready)

### Fixed

- **Headers Already Sent**: Fixed export triggering "headers already sent" error
  - Export actions now handled in `admin_init` hook before any output
- **PHP 8.4+ Compatibility**: Added `$escape` parameter to `fputcsv()` calls

### Changed

- **RequestLogger**: Now uses `SensitiveDataPatterns` for consistent data sanitization
- **RequestLogger**: Retry limits now read from global settings instead of hardcoded constants
- **RequestLogger**: Respects `logging_enabled` setting (can be disabled globally)
- **SensitiveDataPatterns**: Merges custom patterns from global settings with built-in defaults
- **ExportService**: Excludes sensitive fields from CSV export entirely (security by design)
- **Plugin.php**: Registers daily cron job for log cleanup based on retention settings
- **Activator.php**: Initializes default global settings on plugin activation
- Refactored FQCN to `use` statements across Core classes for better readability
- `RequestLogTable` now uses `DateFilterTrait` for date filtering
- `RequestLogController` now uses `DateFilterTrait` for statistics date filtering
- Replaced inline styles with CSS classes for better maintainability
- Moved `Dashboard Widget` and `Export Logs` from planned features to released
- All new code follows WordPress coding standards (PHPCS WordPress-Extra)
- PHPStan Level 8 compliance for all new classes

### Security

- Fixed potential XSS vulnerability in URL construction using `add_query_arg()`
- Proper date validation before SQL query construction

## [1.1.3] - 2026-01-03

### Fixed

- **Legacy Hooks Initialization**: Ensure legacy hooks (`qs_cf7_api_*`) are registered before API requests
  - Fixed issue where hooks weren't being called if ApiClient was instantiated directly
  - Auto-initialization check added to `ApiClient::send()` method
- **PHPCS/PHPStan Compliance**: Resolved coding standard and static analysis issues
  - Fixed double quotes to single quotes in hook names
  - Added phpcs:ignore for legacy hook names (backward compatibility)
  - Removed redundant `is_array()` check flagged by PHPStan
  - Converted test variables to snake_case format

### Changed

- **Release Workflow**: Quality checks now run before release validation
  - PHPCS, PHPStan, and PHPUnit must pass before a release can be created
- **Version Scripts**: Improved `update-version-simple.sh` and `check-versions.sh`
  - Added `--force` flag to update all files even when version matches
  - Fixed script to properly update all PHP, CSS, JS, and shell script files
  - Removed HEADER-STANDARDS.md dependency (moved to copilot-instructions.md)

### Documentation

- **Copilot Instructions**: Added mandatory script usage instructions
  - Detailed documentation for version update and check scripts
  - Critical rules: ALWAYS use scripts, NEVER make manual changes

## [1.1.2] - 2026-01-02

### Added

- **Custom Headers Support**: Add custom HTTP headers directly from the CF7 integration panel
  - Dynamic add/remove header rows
  - Quick preset buttons for common authentication types (Bearer Token, Basic Auth, API Key, Content-Type JSON)
  - Headers are stored per-form and sent with each API request
- **Legacy Hook Compatibility**: Backward compatibility for `qs_cf7_api_*` hooks
  - `qs_cf7_api_get_args` → `cf7_api_get_args`
  - `qs_cf7_api_post_url` → `cf7_api_post_url`
  - `qs_cf7_api_get_url` → `cf7_api_get_url`
  - `qs_cf7_collect_mail_tags` → `cf7_api_collect_mail_tags`
- **Developer Hooks Documentation**: New documentation section in settings page
  - Complete list of available filters with code examples
  - Complete list of available actions with code examples
  - Only documents new `cf7_api_*` hooks (legacy hooks work but are not promoted)
- **Unit Tests**: Added tests for RequestLogController bulk action validation

### Fixed

- **Log View Action**: Fixed "Security check failed" error when viewing log details
  - The `action=view` URL parameter was incorrectly triggering bulk action nonce verification
  - Now properly skips non-bulk actions (`view`) and only validates `delete` and `retry` actions

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
  - Custom database table `{prefix}cf7_api_logs` with optimized indexes
  - Tracks endpoint, method, status, response code, execution time, retry count
  - Automatic sensitive data anonymization (passwords, tokens, API keys)
- **Admin Interface**: WordPress native admin UI for viewing logs
  - `WP_List_Table` implementation with sorting, filtering, pagination
  - Statistics panel with total requests, success rate, avg response time
  - Status filters (All, Success, Errors)
  - Bulk actions (Delete, Retry)
  - Detailed log view with request/response data
- **API Client Service**: Centralized HTTP client with advanced features
  - Retry logic with exponential backoff
  - Request/response logging integration
  - Authentication header handling (Bearer, Basic, API Key)
  - Configurable timeout and SSL verification
- **Settings Hub Integration**: Plugin settings page via Settings Hub
  - Quick links to API Logs and CF7 forms
  - Plugin status and version information
  - Update checker with GitHub integration
- **Debug Logger**: PSR-3 compliant file logger for development
  - Log levels: debug, info, warning, error
  - Automatic log rotation
  - Configurable via WP_DEBUG
- **Utility Classes**: Helper classes for common operations
  - `StringHelper`: Field name conversion (kebab-case, camelCase)
  - `CheckboxHandler`: CF7 checkbox value processing for APIs
- **MVC Architecture**: Separated views from controllers
  - `RequestLogView`: HTML rendering for logs pages
  - `SettingsView`: HTML rendering for settings page
  - `IntegrationView`: HTML rendering for CF7 panel

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
