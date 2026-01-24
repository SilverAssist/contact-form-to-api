# Model Layer

**Version**: 2.0.0  
**Since**: Phase 1  
**Status**: Stable (Non-Breaking)

---

## Overview

This directory contains type-safe domain models for the Contact Form to API plugin. These models provide a clean, object-oriented interface for working with plugin data.

All models follow PHP 8.2+ standards with strict typing and comprehensive PHPDoc documentation.

---

## Available Models

### LogEntry

Represents an API request log entry with complete request/response data.

**Usage:**
```php
use SilverAssist\ContactFormToAPI\Model\LogEntry;

// Create a new log entry
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

if ( $entry->is_retry() ) {
    // Handle retry
}

// Convert to array (for backward compatibility)
$array_data = $entry->to_array();

// Create from array (from database)
$entry = LogEntry::from_array( $db_row );
```

**Key Methods:**
- `is_successful()`: Check if request succeeded
- `is_error()`: Check if request failed
- `is_retry()`: Check if this is a retry attempt
- `to_array()`: Convert to array representation
- `from_array()`: Create from database data

---

### FormSettings

Represents CF7 form API integration configuration.

**Usage:**
```php
use SilverAssist\ContactFormToAPI\Model\FormSettings;

// Create from post meta
$settings = FormSettings::from_meta( $form_id, $meta );

// Access configuration
if ( $settings->is_enabled() ) {
    $endpoint = $settings->get_endpoint();
    $method = $settings->get_method();
    $mappings = $settings->get_field_mappings();
}

// Convert to array
$config = $settings->to_array();
```

**Key Methods:**
- `is_enabled()`: Check if integration is enabled
- `get_endpoint()`: Get API endpoint URL
- `get_method()`: Get HTTP method
- `get_field_mappings()`: Get field mapping configuration
- `is_debug_mode()`: Check if debug mode is enabled

---

### ApiResponse

Represents an API response with complete metadata.

**Usage:**
```php
use SilverAssist\ContactFormToAPI\Model\ApiResponse;

// Create response
$response = new ApiResponse(
    status_code: 200,
    body: $response_body,
    headers: $response_headers,
    is_success: true,
    execution_time: 1.5
);

// Check status
if ( $response->is_success() ) {
    $data = $response->get_body();
}

// Get metadata
$exec_time = $response->get_execution_time();
$status = $response->get_status_code();
```

**Key Methods:**
- `is_success()`: Check if request was successful
- `get_status_code()`: Get HTTP status code
- `get_body()`: Get response body
- `get_headers()`: Get response headers
- `get_execution_time()`: Get request duration
- `get_error_message()`: Get error message if failed

---

### Statistics

Represents aggregated log statistics with calculated rates.

**Usage:**
```php
use SilverAssist\ContactFormToAPI\Model\Statistics;

// Create from database query
$stats = Statistics::from_query( $query_result, $recent_logs );

// Access counts
$total = $stats->get_total();
$success = $stats->get_success();
$errors = $stats->get_error();

// Get calculated rates
$success_rate = $stats->get_success_rate(); // 0-100
$error_rate = $stats->get_error_rate();     // 0-100

// Get metrics
$avg_time = $stats->get_avg_execution_time();
$recent = $stats->get_recent_logs();
```

**Key Methods:**
- `get_total()`: Total log count
- `get_success()`: Successful requests
- `get_error()`: Error count
- `get_success_rate()`: Success percentage (0-100)
- `get_error_rate()`: Error percentage (0-100)
- `get_avg_execution_time()`: Average execution time

---

## Design Patterns

### Immutability

Models favor immutability where possible:
- Constructor sets required fields
- Setters only for fields that change during lifecycle
- No public property access

### Type Safety

All models use strict typing:
- PHP 8.2+ type hints on all methods
- Nullable types where appropriate
- PHPDoc annotations for complex types

### Factory Methods

Models provide factory methods for creation:
- `from_array()`: Create from database array
- `from_meta()`: Create from WordPress post meta
- `from_query()`: Create from database query result

### Conversion Methods

Models provide conversion methods:
- `to_array()`: Convert to array (backward compatibility)

---

## Backward Compatibility

All models are designed for gradual adoption:

**Old Way (Still Works):**
```php
// Array-based approach (existing code)
$log_data = array(
    'form_id' => 123,
    'endpoint' => 'https://api.test',
    'status' => 'success',
);
```

**New Way (Recommended):**
```php
// Type-safe model approach (new code)
$log_entry = new LogEntry(
    form_id: 123,
    endpoint: 'https://api.test',
    method: 'POST',
    status: 'success'
);

// Convert to array when needed
$log_data = $log_entry->to_array();
```

---

## Testing

Current unit tests:
- `tests/Unit/Model/LogEntryTest.php`

Planned additional unit tests:
- `tests/Unit/Model/FormSettingsTest.php`
- `tests/Unit/Model/ApiResponseTest.php`
- `tests/Unit/Model/StatisticsTest.php`

---

## Future Enhancements

Planned for Phase 2:
- Integration with Repository pattern
- Validation rules on models
- Event dispatching on model changes
- Model collections for bulk operations

---

**Last Updated**: January 23, 2026  
**Maintained By**: Silver Assist Development Team
