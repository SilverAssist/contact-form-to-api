# Architecture Documentation

**Version**: 2.0.0 (Phase 1 - Foundation)  
**Last Updated**: January 23, 2026  
**Status**: In Progress - Phase 1 Complete

---

## Overview

This document describes the architectural evolution of the Contact Form to API plugin from version 1.x to 2.0.0. The refactoring follows a phased approach to improve code organization, maintainability, and adherence to SOLID principles and MVC patterns.

---

## Architecture Goals

1. **Single Responsibility Principle**: Each class has one clear purpose
2. **Type Safety**: Strong typing with PHP 8.2+ features and PHPStan Level 8
3. **Testability**: Smaller, focused classes that are easier to unit test
4. **Maintainability**: Clear boundaries and separation of concerns
5. **Backward Compatibility**: Gradual migration with facade patterns
6. **WordPress Standards**: Full compliance with WordPress Coding Standards

---

## Directory Structure (Target - Phase 6)

```
includes/
├── Core/                           # Bootstrap & lifecycle only
│   ├── Plugin.php                 # Main plugin controller
│   ├── Activator.php              # Activation/deactivation hooks
│   └── Interfaces/
│       └── LoadableInterface.php  # Component loading contract
│
├── Model/                          # Domain models (NEW in Phase 1)
│   ├── LogEntry.php               # API request log entry
│   ├── FormSettings.php           # CF7 form configuration
│   ├── ApiResponse.php            # API response data
│   └── Statistics.php             # Aggregated log statistics
│
├── Repository/                     # Data access layer (NEW in Phase 1)
│   ├── LogRepositoryInterface.php      # Log data access contract
│   └── SettingsRepositoryInterface.php # Settings data access contract
│
├── Service/                        # Business logic
│   ├── Logging/                   # Log management services (Phase 2)
│   │   ├── LogWriter.php          # Create/update logs
│   │   ├── LogReader.php          # Query logs
│   │   ├── LogStatistics.php      # Statistics calculations
│   │   └── RetryManager.php       # Retry logic
│   ├── Api/
│   │   └── ApiClient.php          # HTTP client
│   ├── Security/                  # Security services (Phase 4)
│   │   ├── EncryptionService.php  # Data encryption
│   │   └── SensitiveDataPatterns.php # PII detection
│   ├── Export/
│   │   └── ExportService.php      # Log export (CSV/JSON)
│   ├── Migration/
│   │   └── MigrationService.php   # Data migration
│   └── Notification/
│       └── EmailAlertService.php  # Email alerts
│
├── Controller/                     # HTTP/Admin request handling
│   ├── Admin/                     # Admin controllers (Phase 3)
│   │   ├── DashboardController.php
│   │   ├── LogsController.php
│   │   └── SettingsController.php
│   └── ContactForm/               # CF7 controllers (Phase 3)
│       └── SubmissionController.php
│
├── View/                           # Presentation layer
│   ├── Admin/                     # Admin views (Phase 5)
│   │   ├── Dashboard/
│   │   ├── Logs/
│   │   │   ├── ListView.php
│   │   │   ├── DetailView.php
│   │   │   └── Partials/
│   │   └── Settings/
│   │       ├── SettingsView.php
│   │       └── Partials/
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
│   ├── ApiException.php           # (Phase 2)
│   └── ValidationException.php    # (Phase 2)
│
└── Utils/                          # Helpers & utilities
    ├── DebugLogger.php            # PSR-3 file logger
    └── StringHelper.php           # String manipulation
```

---

## Phase 1: Foundation (Completed) ✅

**Status**: Complete  
**Version**: 2.0.0-alpha  
**Breaking Changes**: None (additive only)

### Changes

1. **New Directory Structure**
   - Created `Model/`, `Repository/`, `Controller/`, `View/`, `Infrastructure/` directories
   - Established namespace structure for future refactoring

2. **Model Layer** (NEW)
   - `LogEntry`: Type-safe representation of API request logs
   - `FormSettings`: Type-safe form configuration
   - `ApiResponse`: Type-safe API response data
   - `Statistics`: Type-safe aggregated statistics

3. **Repository Interfaces** (NEW)
   - `LogRepositoryInterface`: Contract for log data access
   - `SettingsRepositoryInterface`: Contract for settings data access

4. **Benefits**
   - Foundation for future refactoring phases
   - Type safety for domain objects
   - Clear contracts for data access
   - Zero breaking changes to existing code

### Usage Example (New Code)

```php
use SilverAssist\ContactFormToAPI\Model\LogEntry;

// Create type-safe log entry
$entry = new LogEntry(
    form_id: 123,
    endpoint: 'https://api.example.com/webhook',
    method: 'POST',
    status: 'success',
    request_data: $form_data,
    request_headers: $headers
);

// Check status
if ( $entry->is_successful() ) {
    // Handle success
}

// Convert to array (compatible with existing code)
$array_data = $entry->to_array();
```

---

## Phase 2: Extract RequestLogger (Planned)

**Status**: Planned  
**Estimated Effort**: 3-4 days  
**Breaking Changes**: Yes (deprecation period: 2 minor versions)

### Problem

`RequestLogger.php` is a God class (1,012 lines, 23 methods) handling:
- Log creation and completion
- Log retrieval (single, multiple, recent)
- Statistics calculation
- Retry management
- Encryption/decryption
- Error resolution tracking
- Data anonymization
- Database operations

### Solution

Split into specialized services:

```
Service/Logging/
├── LogWriter.php      # Create/update/delete logs
├── LogReader.php      # Read/query logs
├── LogStatistics.php  # Statistics calculations
└── RetryManager.php   # Retry logic and tracking
```

### Migration Strategy

1. Create new service classes
2. Update `RequestLogger` to delegate to new services (facade pattern)
3. Add deprecation notices to old methods
4. Update all consumers over 2 minor versions
5. Remove facade in 2.1.0

---

## Phase 3: Split Integration.php (Planned)

**Status**: Planned  
**Estimated Effort**: 2-3 days  
**Breaking Changes**: Yes (deprecation period)

### Problem

`Integration.php` (791 lines) mixes:
- Controller responsibilities (hook registration)
- Service responsibilities (form processing)
- View delegation (admin panel rendering)

### Solution

```
Controller/ContactForm/
└── SubmissionController.php  # Hook registration, routing

Service/ContactForm/
└── SubmissionProcessor.php   # Form processing logic

View/ContactForm/
└── IntegrationView.php        # Already exists
```

---

## Phase 4: Reorganize Services (Planned)

**Status**: Planned  
**Estimated Effort**: 1-2 days  
**Breaking Changes**: Yes (namespace changes)

### Changes

1. Move `Core/EncryptionService.php` → `Service/Security/EncryptionService.php`
2. Move `Core/Settings.php` → `Config/Settings.php`
3. Move `Core/SensitiveDataPatterns.php` → `Service/Security/SensitiveDataPatterns.php`
4. Consolidate duplicate `Loader.php` files

---

## Phase 5: Split Views (Planned)

**Status**: Planned  
**Estimated Effort**: 2-3 days  
**Breaking Changes**: No (internal refactoring)

### Problem

Oversized view files:
- `RequestLogView.php`: 1,004 lines
- `SettingsView.php`: 942 lines

### Solution

Split into partial views for maintainability.

---

## Phase 6: Documentation & Cleanup (Planned)

**Status**: Planned  
**Estimated Effort**: 1-2 days  

1. Update inline documentation
2. Create UPGRADE.md guide
3. Remove deprecated code
4. Final test coverage review

---

## Backward Compatibility Strategy

### Facade Pattern

Existing classes will remain as facades during deprecation period:

```php
/**
 * @deprecated 2.0.0 Use LogWriter::save() instead
 * @see LogWriter::save()
 */
public function log( array $data ) {
    _deprecated_function( __METHOD__, '2.0.0', 'LogWriter::save()' );
    return $this->log_writer->save( LogEntry::from_array( $data ) );
}
```

### Deprecation Timeline

- **2.0.0**: New architecture introduced, old methods deprecated
- **2.1.0**: Deprecation warnings remain, alternative usage documented
- **2.2.0**: Deprecated methods removed

### External Integration Support

Hooks and filters maintain backward compatibility:

```php
// Old hook (deprecated but still works)
do_action( 'cf7_api_log_created', $log_id, $data );

// New hook (recommended)
do_action( 'cf7_api_log_entry_saved', $log_entry );
```

---

## Testing Strategy

### Unit Tests

Each new class has dedicated unit tests:
- `tests/Unit/Model/LogEntryTest.php`
- `tests/Unit/Model/FormSettingsTest.php`
- `tests/Unit/Model/ApiResponseTest.php`
- `tests/Unit/Model/StatisticsTest.php`

### Integration Tests

WordPress integration tests ensure backward compatibility:
- All existing tests continue to pass
- New tests added for new functionality
- PHPStan Level 8 compliance maintained
- PHPCS WordPress-Extra compliance maintained

### Quality Gates

- ✅ PHPCS: 0 errors, 0 warnings
- ✅ PHPStan: Level 8, 0 errors
- ✅ PHPUnit: 100% pass rate
- ✅ Code Coverage: >80% for new code

---

## Benefits Achieved (Phase 1)

1. **Type Safety**: Model classes provide compile-time type checking
2. **Documentation**: Clear contracts via interfaces
3. **Foundation**: Structure ready for future refactoring
4. **No Disruption**: Zero breaking changes to existing functionality
5. **Standards Compliance**: Full PHPCS and PHPStan compliance

---

## Next Steps

1. Review Phase 1 implementation
2. Get stakeholder approval for Phase 2
3. Create detailed Phase 2 implementation plan
4. Update CHANGELOG.md with Phase 1 notes
5. Create git tags for phase milestones

---

## References

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [PHP-FIG PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)

---

**Document Version**: 1.0.0  
**Last Updated**: January 23, 2026  
**Author**: Silver Assist Development Team
