# Service/Logging Directory

**Phase**: 2  
**Since**: 2.0.0  
**Status**: Active

---

## Overview

This directory contains specialized logging services extracted from the monolithic `RequestLogger` class as part of the Phase 2 architecture refactoring. Each service follows the Single Responsibility Principle and handles a specific aspect of API request logging.

---

## Services

### LogWriter
**File**: `LogWriter.php`  
**Purpose**: Create and update log entries  
**Responsibilities**:
- Start new log entries before API requests
- Complete log entries with response data
- Update retry counts
- Delete old logs
- Handle encryption of sensitive data
- Anonymize headers

**Key Methods**:
- `start_request()` - Create initial log entry
- `complete_request()` - Update with response data
- `update_retry_count()` - Update retry counter
- `delete_old_logs()` - Clean up old entries

---

### LogReader
**File**: `LogReader.php`  
**Purpose**: Read and query log entries  
**Responsibilities**:
- Retrieve individual log entries
- Get recent logs for forms
- Get request data for retries
- Decrypt encrypted log data

**Key Methods**:
- `get_log()` - Get single log by ID
- `get_recent_logs()` - Get recent logs for form
- `get_request_for_retry()` - Get retryable request data
- `decrypt_log_fields()` - Decrypt encrypted fields

---

### LogStatistics
**File**: `LogStatistics.php`  
**Purpose**: Calculate statistics and metrics  
**Responsibilities**:
- Calculate success/failure rates
- Get request counts by time period
- Calculate average response times
- Retrieve recent errors

**Key Methods**:
- `get_statistics()` - Comprehensive stats with date filtering
- `get_count_last_hours()` - Count requests in time window
- `get_success_rate_last_hours()` - Success percentage
- `get_avg_response_time_last_hours()` - Average execution time
- `get_recent_errors()` - Recent failed requests

---

### RetryManager
**File**: `RetryManager.php`  
**Purpose**: Manage retry logic and tracking  
**Responsibilities**:
- Count retry attempts
- Track retry history
- Determine error resolution status
- Manage retry limits

**Key Methods**:
- `count_retries()` - Count retry attempts for log
- `get_retries_for_log()` - Get all retry entries
- `has_successful_retry()` - Check if error was resolved
- `get_successful_retry_id()` - Get ID of successful retry
- `count_errors_by_resolution()` - Error resolution statistics
- `get_resolved_error_ids()` - IDs of resolved errors

---

## Architecture Benefits

### Before (Phase 1)
- Single `RequestLogger` class with 1,011 lines and 23 methods
- God class with mixed responsibilities
- Difficult to test and maintain
- Hard to extend with new features

### After (Phase 2)
- Four focused services with clear responsibilities
- Each service ~200-300 lines
- Easier to test each component
- Simple to extend individual services
- Better separation of concerns

---

## Usage Example

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;

// Writing logs
$writer = new LogWriter();
$log_id = $writer->start_request( $form_id, $endpoint, 'POST', $data, $headers );
$writer->complete_request( $log_id, $response );

// Reading logs
$reader = new LogReader();
$log = $reader->get_log( $log_id );
$request_data = $reader->get_request_for_retry( $log_id );

// Statistics
$stats = new LogStatistics();
$metrics = $stats->get_statistics( $form_id );
$success_rate = $stats->get_success_rate_last_hours( 24 );

// Retry management
$retry_manager = new RetryManager();
$retry_count = $retry_manager->count_retries( $log_id );
$has_success = $retry_manager->has_successful_retry( $log_id );
```

---

## Backward Compatibility

The original `RequestLogger` class remains as a facade that delegates to these new services. This ensures:
- No breaking changes for existing code
- Gradual migration path
- Deprecation warnings guide developers to new APIs

See `Core/RequestLogger.php` for facade implementation.

---

## Future Enhancements

Potential improvements for future versions:
- Unit tests for all logging services
- Repository pattern implementation for data access
- Event dispatching for log creation/updates
- Batch operations for improved performance
- Query builder for complex log filtering
- Cache layer for frequently accessed statistics

---

**Last Updated**: January 24, 2026  
**Maintained By**: Silver Assist Development Team
