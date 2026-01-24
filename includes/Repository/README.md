# Repository Layer

**Version**: 2.0.0  
**Since**: Phase 1  
**Status**: Interfaces Only (Implementation in Phase 2)

---

## Overview

This directory contains repository interfaces that define contracts for data access operations. Repositories abstract database operations and provide a clean separation between business logic and data persistence.

The Repository pattern provides:
- **Abstraction**: Hide database implementation details
- **Testability**: Easy to mock for unit tests
- **Flexibility**: Can swap implementations without changing business logic
- **Type Safety**: Clear contracts with typed parameters and returns

---

## Available Interfaces

### LogRepositoryInterface

Defines contract for log entry data access operations.

**Interface Methods:**

```php
use SilverAssist\ContactFormToAPI\Repository\LogRepositoryInterface;
use SilverAssist\ContactFormToAPI\Model\LogEntry;
use SilverAssist\ContactFormToAPI\Model\Statistics;

interface LogRepositoryInterface {
    // Save a log entry
    public function save( LogEntry $entry );
    
    // Find by ID
    public function find_by_id( int $id ): ?LogEntry;
    
    // Find with filters
    public function find_all( array $filters = array() ): array;
    
    // Delete entries
    public function delete( array $ids ): int;
    
    // Get statistics
    public function get_statistics( array $filters = array() ): Statistics;
    
    // Count entries
    public function count( array $filters = array() ): int;
}
```

**Future Implementation:**
```php
// Phase 2 will add:
class LogRepository implements LogRepositoryInterface {
    // Implementation using wpdb
}
```

---

### SettingsRepositoryInterface

Defines contract for settings data access operations.

**Interface Methods:**

```php
use SilverAssist\ContactFormToAPI\Repository\SettingsRepositoryInterface;
use SilverAssist\ContactFormToAPI\Model\FormSettings;

interface SettingsRepositoryInterface {
    // Global settings
    public function get( string $key, $default_value = null );
    public function set( string $key, $value ): bool;
    
    // Form-specific settings
    public function get_form_settings( int $form_id ): FormSettings;
    public function save_form_settings( FormSettings $settings ): bool;
    public function delete_form_settings( int $form_id ): bool;
}
```

**Future Implementation:**
```php
// Phase 2 will add:
class SettingsRepository implements SettingsRepositoryInterface {
    // Implementation using WordPress options/post meta
}
```

---

## Design Patterns

### Repository Pattern Benefits

1. **Separation of Concerns**
   - Business logic doesn't know about database
   - Data access logic is centralized
   - Easy to change storage mechanism

2. **Testability**
   ```php
   // Mock repository in tests
   $mock_repo = $this->createMock( LogRepositoryInterface::class );
   $mock_repo->method( 'find_by_id' )
       ->willReturn( $test_log_entry );
   
   // Test service with mock
   $service = new LogService( $mock_repo );
   ```

3. **Type Safety**
   - All parameters and returns are typed
   - IDE autocomplete works perfectly
   - PHPStan can validate usage

---

## Migration Path

### Phase 1 (Current)
- ✅ Interfaces defined
- ✅ Contracts established
- ⏸️ No implementation yet
- ⏸️ Existing code continues to work

### Phase 2 (Planned)
- Create concrete implementations
- Migrate existing data access to repositories
- Update services to use repositories
- Add deprecation notices to old methods

### Phase 3 (Planned)
- Remove deprecated data access methods
- Fully repository-based architecture
- Enhanced testing coverage

---

## Current Usage

**In Phase 1, these are interfaces only.** Existing code continues to use direct data access:

```php
// Current approach (still works)
global $wpdb;
$results = $wpdb->get_results( "SELECT * FROM {$table}" );
```

**In Phase 2, new repository implementations will be available:**

```php
// Future approach (Phase 2)
use SilverAssist\ContactFormToAPI\Repository\LogRepository;

$repository = new LogRepository();
$logs = $repository->find_all( array( 'status' => 'error' ) );
```

---

## Implementation Guidelines (Phase 2)

When implementing these interfaces:

1. **Use wpdb for Database Access**
   ```php
   global $wpdb;
   $results = $wpdb->get_results( 
       $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
   );
   ```

2. **Return Model Instances**
   ```php
   public function find_by_id( int $id ): ?LogEntry {
       // Fetch from database
       $row = $wpdb->get_row( /* ... */ );
       
       if ( ! $row ) {
           return null;
       }
       
       // Convert to model
       return LogEntry::from_array( (array) $row );
   }
   ```

3. **Handle Errors Gracefully**
   ```php
   public function save( LogEntry $entry ) {
       try {
           // Database operations
           return $wpdb->insert_id;
       } catch ( Exception $e ) {
           // Log error
           return false;
       }
   }
   ```

4. **Support Filtering**
   ```php
   public function find_all( array $filters = array() ): array {
       $where = array( '1=1' );
       $values = array();
       
       if ( isset( $filters['status'] ) ) {
           $where[] = 'status = %s';
           $values[] = $filters['status'];
       }
       
       // Build and execute query
   }
   ```

---

## Testing Strategy

### Interface Testing
```php
// Test that classes implement interface correctly
public function testImplementsInterface(): void {
    $repo = new LogRepository();
    $this->assertInstanceOf( LogRepositoryInterface::class, $repo );
}
```

### Mocking for Unit Tests
```php
// Mock repository for service tests
$mock = $this->createMock( LogRepositoryInterface::class );
$mock->expects( $this->once() )
    ->method( 'save' )
    ->with( $this->isInstanceOf( LogEntry::class ) )
    ->willReturn( 123 );
```

---

## References

- [Martin Fowler - Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
- [PHP-FIG Standards](https://www.php-fig.org/)
- [WordPress Database Class](https://developer.wordpress.org/reference/classes/wpdb/)

---

**Last Updated**: January 23, 2026  
**Maintained By**: Silver Assist Development Team
