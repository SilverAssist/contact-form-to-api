# Architecture Documentation

**Plugin**: Contact Form to API  
**Version**: 2.0.0  
**Last Updated**: January 25, 2026

---

## Overview

This document describes the architecture of the Contact Form to API plugin. The plugin follows SOLID principles and MVC patterns to ensure code quality, maintainability, and testability.

---

## Architecture Principles

1. **Single Responsibility Principle**: Each class has one clear purpose
2. **Type Safety**: Strong typing with PHP 8.2+ features and PHPStan Level 8
3. **Testability**: Smaller, focused classes that are easier to unit test
4. **Maintainability**: Clear boundaries and separation of concerns
5. **WordPress Standards**: Full compliance with WordPress Coding Standards

---

## Directory Structure

```
includes/
├── Core/                           # Bootstrap & lifecycle
│   ├── Plugin.php                 # Main plugin controller
│   ├── Activator.php              # Activation/deactivation hooks
│   └── Interfaces/
│       └── LoadableInterface.php  # Component loading contract
│
├── Config/                         # Configuration management
│   └── Settings.php               # Plugin settings
│
├── Model/                          # Domain models
│   ├── LogEntry.php               # API request log entry
│   ├── FormSettings.php           # CF7 form configuration
│   ├── ApiResponse.php            # API response data
│   └── Statistics.php             # Aggregated log statistics
│
├── Repository/                     # Data access layer
│   ├── LogRepositoryInterface.php      # Log data access contract
│   └── SettingsRepositoryInterface.php # Settings data access contract
│
├── Service/                        # Business logic
│   ├── Logging/                   # Log management services
│   │   ├── LogWriter.php          # Create/update logs
│   │   ├── LogReader.php          # Query logs
│   │   ├── LogStatistics.php      # Statistics calculations
│   │   └── RetryManager.php       # Retry logic
│   ├── Api/
│   │   └── ApiClient.php          # HTTP client
│   ├── Security/                  # Security services
│   │   ├── EncryptionService.php  # Data encryption
│   │   └── SensitiveDataPatterns.php # PII detection
│   ├── Export/
│   │   └── ExportService.php      # Log export (CSV/JSON)
│   ├── Migration/
│   │   └── MigrationService.php   # Data migration
│   ├── Notification/
│   │   └── EmailAlertService.php  # Email alerts
│   └── ContactForm/
│       └── SubmissionProcessor.php # Form processing logic
│
├── Controller/                     # HTTP/Admin request handling
│   ├── Admin/
│   │   ├── DashboardController.php
│   │   ├── LogsController.php
│   │   └── SettingsController.php
│   └── ContactForm/
│       └── SubmissionController.php # CF7 hook management
│
├── View/                           # Presentation layer
│   ├── Admin/
│   │   ├── Dashboard/
│   │   ├── Logs/
│   │   │   ├── ListView.php
│   │   │   ├── DetailView.php
│   │   │   └── Partials/
│   │   │       ├── StatisticsPartial.php
│   │   │       ├── DateFilterPartial.php
│   │   │       └── ExportButtonsPartial.php
│   │   └── Settings/
│   │       ├── SettingsView.php
│   │       └── Partials/
│   │           └── GlobalSettingsPartial.php
│   └── ContactForm/
│       └── IntegrationView.php
│
├── Infrastructure/                 # WordPress integration
│   ├── ListTable/
│   │   └── RequestLogTable.php   # WP_List_Table implementation
│   ├── Widget/
│   │   └── DashboardWidget.php   # Dashboard widget
│   └── Handler/
│       └── CheckboxHandler.php   # CF7 checkbox processing
│
├── Exception/                      # Custom exceptions
│   ├── DecryptionException.php
│   ├── ApiException.php
│   └── ValidationException.php
│
└── Utils/                          # Helpers & utilities
    ├── DebugLogger.php            # PSR-3 file logger
    ├── DateFilterTrait.php        # SQL date filtering
    └── StringHelper.php           # String manipulation
```

---

## Layer Descriptions

### Core Layer

The Core layer handles plugin bootstrap and lifecycle:

- **Plugin.php**: Main plugin controller implementing singleton pattern
- **Activator.php**: Database table creation and plugin activation/deactivation
- **LoadableInterface.php**: Contract for component loading with priorities

### Model Layer

Type-safe domain models representing business entities:

```php
use SilverAssist\ContactFormToAPI\Model\LogEntry;

$entry = new LogEntry(
    form_id: 123,
    endpoint: 'https://api.example.com/webhook',
    method: 'POST',
    status: 'success',
    request_data: $form_data,
    request_headers: $headers
);

if ( $entry->is_successful() ) {
    // Handle success
}

$array_data = $entry->to_array();
```

### Repository Layer

Interfaces defining contracts for data access:

- **LogRepositoryInterface**: Contract for log data operations
- **SettingsRepositoryInterface**: Contract for settings data operations

### Service Layer

Business logic organized by domain:

#### Logging Services

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;

// Write logs
$writer = LogWriter::instance();
$log_id = $writer->save( $log_entry );

// Query logs
$reader = LogReader::instance();
$logs = $reader->get_logs( array( 'status' => 'success' ) );

// Get statistics
$stats = LogStatistics::instance();
$metrics = $stats->get_overview();

// Manage retries
$retry = RetryManager::instance();
$retry_id = $retry->create_retry_entry( $original_log_id );
```

#### Security Services

```php
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use SilverAssist\ContactFormToAPI\Service\Security\SensitiveDataPatterns;

// Encrypt sensitive data
$encryption = EncryptionService::instance();
$encrypted = $encryption->encrypt( $sensitive_data );

// Check for sensitive fields
if ( SensitiveDataPatterns::is_sensitive_field( 'password' ) ) {
    // Handle sensitive data
}
```

#### Contact Form Services

```php
use SilverAssist\ContactFormToAPI\Service\ContactForm\SubmissionProcessor;

// Process form submissions (automatically loaded via hooks)
$processor = SubmissionProcessor::instance();
```

### Controller Layer

HTTP/Admin request handling with MVC separation:

```php
use SilverAssist\ContactFormToAPI\Controller\ContactForm\SubmissionController;

// Handles CF7 hooks and routing (automatically loaded)
$controller = SubmissionController::instance();
```

**Controller Responsibilities**:

- WordPress hook registration
- Request routing
- Admin UI coordination
- Delegation to services

### View Layer

Presentation layer with reusable partials:

```php
use SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials\StatisticsPartial;
use SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials\DateFilterPartial;

// Render UI components
StatisticsPartial::render( $stats );
DateFilterPartial::render();
```

### Infrastructure Layer

WordPress-specific integrations:

- **RequestLogTable**: WP_List_Table for displaying logs
- **DashboardWidget**: Admin dashboard widget
- **CheckboxHandler**: CF7 checkbox value processing

---

## Component Loading

All components implement `LoadableInterface` with priority-based loading:

| Priority | Layer | Components |
|----------|-------|------------|
| 10 | Core | Plugin, Activator |
| 20 | Services | ApiClient, LogWriter, LogReader, etc. |
| 30 | Admin/Controllers | SettingsPage, RequestLogController, SubmissionController |
| 40 | Utilities | DebugLogger, StringHelper |

```php
interface LoadableInterface {
    public function init(): void;
    public function get_priority(): int;
    public function should_load(): bool;
}
```

---

## Logging Architecture

The plugin has two distinct logging systems:

| Logger | Purpose | Storage |
|--------|---------|--------|
| `Service\Logging\*` | API request/response tracking | Database (admin UI) |
| `Utils\DebugLogger` | Plugin debugging | File (development) |

---

## Namespace Structure

```
SilverAssist\ContactFormToAPI\
├── Core\
├── Config\
├── Model\
├── Repository\
├── Service\
│   ├── Logging\
│   ├── Api\
│   ├── Security\
│   ├── Export\
│   ├── Migration\
│   ├── Notification\
│   └── ContactForm\
├── Controller\
│   ├── Admin\
│   └── ContactForm\
├── View\
│   ├── Admin\
│   └── ContactForm\
├── Infrastructure\
├── Exception\
└── Utils\
```

---

## Testing Strategy

### Unit Tests

Each class has dedicated unit tests:

- `tests/Unit/Model/LogEntryTest.php`
- `tests/Unit/Model/FormSettingsTest.php`
- `tests/Unit/Service/Logging/LogWriterTest.php`

### Quality Gates

- **PHPCS**: WordPress-Extra compliance
- **PHPStan**: Level 8 strict type checking
- **PHPUnit**: Unit and integration tests

---

## Requirements

| Component | Minimum Version |
|-----------|-----------------|
| PHP | 8.2+ |
| WordPress | 6.5+ |
| Contact Form 7 | 5.9+ |

---

## References

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [PHP-FIG PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)

---

**Document Version**: 2.0.0  
**Last Updated**: January 25, 2026  
**Maintained By**: Silver Assist Development Team
