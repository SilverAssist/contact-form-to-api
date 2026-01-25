---
description: Testing standards, best practices, and conventions for PHPUnit tests
name: Testing Standards
applyTo: "tests/**/*.php"
---

# PHPUnit Testing Standards for Contact Form to API Plugin

**Applies to**: `tests/**/*.php`  
**Last Updated**: January 25, 2026  
**Project**: Contact Form to API WordPress Plugin v2.0.0

---

## 🗂️ Test Organization

```
tests/
├── bootstrap.php            # Test setup and WordPress loading
├── Helpers/
│   ├── TestCase.php        # Base test case (extends WP_UnitTestCase)
│   └── CF7TestCase.php     # Base test case for CF7 tests
├── Unit/                    # Unit tests (MVC folder structure)
│   ├── Config/
│   │   └── SettingsTest.php
│   ├── Controller/
│   │   └── Admin/
│   │       └── LogsControllerTest.php
│   ├── Core/
│   │   ├── ActivatorTest.php
│   │   └── PluginTest.php
│   ├── Infrastructure/
│   │   └── ListTable/
│   │       └── RequestLogTableTest.php
│   ├── Model/
│   │   ├── LogEntryTest.php
│   │   ├── FormSettingsTest.php
│   │   └── StatisticsTest.php
│   └── Service/
│       ├── Api/
│       │   └── ApiClientTest.php
│       ├── ContactForm/
│       │   └── SubmissionProcessorTest.php
│       ├── Export/
│       │   └── ExportServiceTest.php
│       ├── Logging/
│       │   ├── LogReaderTest.php
│       │   ├── LogStatisticsTest.php
│       │   ├── LogWriterTest.php
│       │   └── RetryManagerTest.php
│       ├── Migration/
│       │   └── MigrationServiceTest.php
│       ├── Notification/
│       │   └── EmailAlertServiceTest.php
│       └── Security/
│           ├── EncryptionServiceTest.php
│           └── SensitiveDataPatternsTest.php
├── Integration/             # Integration tests (WordPress required)
│   ├── EncryptedLoggingTest.php
│   ├── FormSubmissionLoggingTest.php
│   ├── MigrationIntegrationTest.php
│   ├── RetryTraceabilityTest.php
│   └── WordPressIntegrationTest.php
├── ContactForm/
│   └── IntegrationTest.php  # CF7 specific tests
└── data/                    # Test fixtures and data
```

---

## ✅ Must-Follow Rules

### 1. Base Class: All Tests Extend TestCase (WP_UnitTestCase)

```php
// ✅ CORRECT - Use our TestCase which extends WP_UnitTestCase
use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;

class MyTest extends TestCase {
    // Tests here
}
```

### 2. Method Names Use WordPress snake_case Convention

Since TestCase extends WP_UnitTestCase, use WordPress-style snake_case for lifecycle methods:

```php
// ✅ CORRECT - WordPress snake_case style (WP_UnitTestCase)
public function set_up(): void
public function tear_down(): void
public static function set_up_before_class(): void
public static function tear_down_after_class(): void

// Test methods use camelCase
public function testMethodReturnsExpectedValue(): void

// ❌ WRONG - PHPUnit camelCase style (don't use with WP_UnitTestCase)
public function setUp(): void
public function tearDown(): void
```

### 3. Test Method Naming Convention

```php
// ✅ CORRECT - Descriptive test names in camelCase
public function testStartRequestCreatesLogEntry(): void
public function testCompleteRequestUpdatesStatus(): void
public function testSearchMatchesSenderName(): void

// ❌ WRONG - Vague names
public function testMethod(): void
public function testItWorks(): void
```

### 4. Database Queries: Use %i for Table Names

WordPress 6.2+ supports `%i` placeholder for identifiers (table/column names):

```php
// ✅ CORRECT - Use %i for table names
$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table_name, $id );
$wpdb->prepare( 'DELETE FROM %i WHERE form_id = %d', $table_name, $form_id );
$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );

// ❌ WRONG - Variable interpolation in queries
$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id );
$wpdb->query( "DELETE FROM {$table_name}" );
```

### 5. Skip When Dependencies Missing

```php
public function set_up(): void {
    parent::set_up();
    
    // Skip if CF7 not available.
    if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
        $this->markTestSkipped( 'Contact Form 7 not loaded' );
        return; // IMPORTANT: always return after skip.
    }
    
    $this->service = new MyService();
}
```

### 6. Always Clean Up Resources

```php
public function tear_down(): void {
    // Clean up test data.
    $this->service = null;
    $this->logger = null;
    
    // Reset any global state.
    parent::tear_down();
}
```

### 7. Use PHPDoc Group Annotations

```php
/**
 * Tests for RequestLogger class.
 *
 * @group unit
 * @group logging
 * @covers \SilverAssist\ContactFormToAPI\Core\RequestLogger
 */
class RequestLoggerTest extends TestCase {
}

/**
 * Integration tests for retry traceability.
 *
 * @group integration
 * @group retry
 * @requires extension pdo_mysql
 */
class RetryTraceabilityTest extends TestCase {
}
```

---

## 📋 Test Structure

### Basic Test Structure

```php
<?php
/**
 * Tests for ClassName.
 *
 * @package SilverAssist\ContactFormToAPI\Tests\Unit
 */

namespace SilverAssist\ContactFormToAPI\Tests\Unit\Service\Logging;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Service\Logging\ClassName;

/**
 * ClassName test case.
 *
 * @group unit
 * @group service
 * @group logging
 * @covers \SilverAssist\ContactFormToAPI\Service\Logging\ClassName
 */
class ClassNameTest extends TestCase {

    /**
     * Instance under test.
     *
     * @var ClassName|null
     */
    private ?ClassName $instance = null;

    /**
     * Set up test fixtures.
     */
    public function set_up(): void {
        parent::set_up();
        $this->instance = new ClassName();
    }

    /**
     * Tear down test fixtures.
     */
    public function tear_down(): void {
        $this->instance = null;
        parent::tear_down();
    }

    /**
     * Test that method returns expected value.
     */
    public function testMethodReturnsExpectedValue(): void {
        $result = $this->instance->method( 'input' );
        
        $this->assertSame( 'expected', $result );
    }
}
```

### Data Provider Pattern

```php
/**
 * Data provider for search matching tests.
 *
 * @return array<string, array{item: array<string, mixed>, search: string, expected: bool}>
 */
public static function searchMatchingDataProvider(): array {
    return array(
        'exact match' => array(
            'item'     => array( 'name' => 'John Doe' ),
            'search'   => 'john',
            'expected' => true,
        ),
        'partial match' => array(
            'item'     => array( 'name' => 'John Doe' ),
            'search'   => 'doe',
            'expected' => true,
        ),
        'no match' => array(
            'item'     => array( 'name' => 'John Doe' ),
            'search'   => 'jane',
            'expected' => false,
        ),
    );
}

/**
 * Test search matching with various inputs.
 *
 * @dataProvider searchMatchingDataProvider
 * @param array<string, mixed> $item     Item to search.
 * @param string               $search   Search term.
 * @param bool                 $expected Expected result.
 */
public function testSearchMatching( array $item, string $search, bool $expected ): void {
    $result = $this->instance->matches_search( $item, $search );
    
    $this->assertSame( $expected, $result );
}
```

### Testing Private Methods

```php
/**
 * Test private method using reflection.
 */
public function testPrivateMethodBehavior(): void {
    $reflection = new \ReflectionClass( $this->instance );
    $method = $reflection->getMethod( 'privateMethod' );
    $method->setAccessible( true );
    
    $result = $method->invoke( $this->instance, 'arg1', 'arg2' );
    
    $this->assertSame( 'expected', $result );
}
```

---

## 🧪 Assertion Best Practices

### Use Specific Assertions

```php
// ✅ CORRECT - Specific assertions
$this->assertSame( 'expected', $actual );      // Strict type + value
$this->assertEquals( 5, $count );              // Value equality
$this->assertTrue( $result );                  // Boolean true
$this->assertFalse( $result );                 // Boolean false
$this->assertNull( $value );                   // Null check
$this->assertNotNull( $value );                // Not null
$this->assertCount( 3, $array );               // Array count
$this->assertEmpty( $array );                  // Empty check
$this->assertNotEmpty( $array );               // Not empty
$this->assertArrayHasKey( 'key', $array );     // Array key exists
$this->assertInstanceOf( ClassName::class, $obj ); // Type check

// ❌ WRONG - Generic assertions
$this->assertTrue( $result === 'expected' );   // Use assertSame
$this->assertTrue( count( $array ) === 3 );    // Use assertCount
```

### Add Assertion Messages

```php
// ✅ CORRECT - With helpful message
$this->assertSame(
    3,
    $result['total'],
    'Should count 3 total errors (700, 701, 703)'
);

$this->assertTrue(
    $method->invoke( $this->table, $item, 'john' ),
    'Should match lowercase search against mixed case name'
);
```

---

## 🔄 Integration Test Patterns

### Database Test Pattern

```php
/**
 * @group integration
 * @group database
 */
class DatabaseIntegrationTest extends TestCase {

    /**
     * Table name for tests.
     *
     * @var string
     */
    private string $table_name;

    /**
     * Set up test fixtures.
     */
    public function set_up(): void {
        parent::set_up();
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cf7_api_logs';
    }

    /**
     * Clean up after each test.
     */
    public function tear_down(): void {
        global $wpdb;
        
        // ✅ CORRECT - Use %i for table name
        $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE form_id = %d', $this->table_name, 99999 ) );
        
        parent::tear_down();
    }

    /**
     * Test database operations.
     */
    public function testInsertAndRetrieve(): void {
        global $wpdb;
        
        // Insert test data.
        $wpdb->insert(
            $this->table_name,
            array(
                'form_id'  => 99999,
                'endpoint' => 'https://api.example.com',
                'status'   => 'success',
            )
        );
        
        $id = $wpdb->insert_id;
        
        // ✅ CORRECT - Use %i for table name
        $row = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table_name, $id ),
            ARRAY_A
        );
        
        $this->assertSame( 'https://api.example.com', $row['endpoint'] );
    }
}
```

### Using WP_UnitTestCase Features

```php
/**
 * Test using WP_UnitTestCase factory methods.
 */
public function testWithFactory(): void {
    // Create a test user using factory.
    $user_id = $this->factory->user->create( array(
        'role' => 'administrator',
    ) );
    
    // Create a test post.
    $post_id = $this->factory->post->create( array(
        'post_author' => $user_id,
        'post_title'  => 'Test Post',
    ) );
    
    // Set current user.
    wp_set_current_user( $user_id );
    
    // Your test assertions...
    $this->assertTrue( current_user_can( 'manage_options' ) );
}
```

---

## ✅ Quality Checklist

### Before Committing Tests

- [ ] All tests pass: `composer test`
- [ ] PHPCS clean: `composer phpcs tests/`
- [ ] PHPStan Level 8: `composer phpstan`
- [ ] Test names are descriptive (camelCase)
- [ ] Lifecycle methods use snake_case (set_up, tear_down)
- [ ] PHPDoc annotations present (@group, @covers)
- [ ] Database queries use %i for table names
- [ ] set_up/tear_down clean resources
- [ ] Assertion messages are helpful
- [ ] Edge cases covered

### Test Coverage Goals

| Category | Target | Files |
|----------|--------|-------|
| Unit Tests | 80%+ | Core services, utilities |
| Integration Tests | 70%+ | Database, WordPress hooks |
| Edge Cases | 100% | Error handling, null checks |

---

## 🛠️ Running Tests

### Commands

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Unit/Service/Logging/LogWriterTest.php

# Run specific test method
vendor/bin/phpunit --filter testSearchMatchesSenderName

# Run tests by group
vendor/bin/phpunit --group unit
vendor/bin/phpunit --group integration

# Run with coverage (requires Xdebug or PCOV)
composer test:coverage

# Run in verbose mode
vendor/bin/phpunit -v
```

### PHPUnit Configuration

The `phpunit.xml` file configures:
- Test directories
- Bootstrap file
- Database configuration
- Coverage settings

---

**Last Updated**: January 24, 2026  
**Maintained By**: Silver Assist  
**Repository**: https://github.com/SilverAssist/contact-form-to-api
