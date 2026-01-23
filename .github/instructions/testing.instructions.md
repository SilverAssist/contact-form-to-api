---
description: Testing standards, best practices, and conventions for PHPUnit tests
name: Testing Standards
applyTo: "tests/**/*.php"
---

# PHPUnit Testing Standards for Contact Form to API Plugin

**Applies to**: `tests/**/*.php`  
**Last Updated**: January 23, 2026  
**Project**: Contact Form to API WordPress Plugin

---

## 🗂️ Test Organization

```
tests/
├── bootstrap.php            # Test setup and WordPress loading
├── Helpers/
│   ├── TestCase.php        # Base test case for unit tests
│   └── CF7TestCase.php     # Base test case for CF7 tests
├── Unit/                    # Unit tests (no WordPress required)
│   ├── DataAnonymizationTest.php
│   ├── EmailAlertServiceTest.php
│   ├── EncryptionServiceTest.php
│   ├── ExportServiceTest.php
│   ├── LoggerTest.php
│   ├── MigrationServiceTest.php
│   ├── PluginTest.php
│   ├── RequestLogControllerTest.php
│   ├── RequestLogTableTest.php
│   ├── RequestLoggerStatisticsTest.php
│   ├── SensitiveDataPatternsTest.php
│   └── SettingsTest.php
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

### 1. Method Names Are camelCase

```php
// ✅ CORRECT
public function setUp(): void
public function tearDown(): void
public function testMethodReturnsExpectedValue(): void

// ❌ WRONG - WordPress snake_case style
public function set_up(): void
public function tear_down(): void
public function test_method_returns_expected_value(): void
```

### 2. Test Method Naming Convention

```php
// ✅ CORRECT - Descriptive test names
public function testStartRequestCreatesLogEntry(): void
public function testCompleteRequestUpdatesStatus(): void
public function testSearchMatchesSenderName(): void

// ❌ WRONG - Vague names
public function testMethod(): void
public function testItWorks(): void
```

### 3. Skip When Dependencies Missing

```php
public function setUp(): void {
    parent::setUp();
    
    // Skip if WordPress not loaded.
    if ( ! function_exists( 'add_action' ) ) {
        $this->markTestSkipped( 'WordPress not loaded' );
        return; // IMPORTANT: always return after skip.
    }
    
    // Skip if CF7 not available.
    if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
        $this->markTestSkipped( 'Contact Form 7 not loaded' );
        return;
    }
    
    $this->service = new MyService();
}
```

### 4. Always Clean Up Resources

```php
public function tearDown(): void {
    // Clean up test data.
    $this->service = null;
    $this->logger = null;
    
    // Reset any global state.
    parent::tearDown();
}
```

### 5. Use PHPDoc Group Annotations

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
class RetryTraceabilityTest extends WP_UnitTestCase {
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

namespace SilverAssist\ContactFormToAPI\Tests\Unit;

use SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase;
use SilverAssist\ContactFormToAPI\Core\ClassName;

/**
 * ClassName test case.
 *
 * @group unit
 * @covers \SilverAssist\ContactFormToAPI\Core\ClassName
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
    public function setUp(): void {
        parent::setUp();
        $this->instance = new ClassName();
    }

    /**
     * Tear down test fixtures.
     */
    public function tearDown(): void {
        $this->instance = null;
        parent::tearDown();
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
public function searchMatchingDataProvider(): array {
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
class DatabaseIntegrationTest extends WP_UnitTestCase {

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test table or use WordPress tables.
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cf7_api_logs';
    }

    /**
     * Clean up after each test.
     */
    public function tearDown(): void {
        global $wpdb;
        
        // Clean up test data.
        $wpdb->query( "DELETE FROM {$this->table_name} WHERE form_id = 99999" );
        
        parent::tearDown();
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
        
        // Retrieve and verify.
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ),
            ARRAY_A
        );
        
        $this->assertSame( 'https://api.example.com', $row['endpoint'] );
    }
}
```

### Mocking WordPress Functions

```php
/**
 * Test with mocked WordPress functions.
 */
public function testWithMockedFunction(): void {
    // Use WP_Mock or Brain\Monkey for function mocking.
    // Or use WordPress test suite's built-in mocking.
    
    // Example: Test that function is called.
    $this->expectAction( 'cf7_api_log_created' );
    
    $this->logger->start_request( 1, 'https://api.test', 'POST', array() );
}
```

---

## ✅ Quality Checklist

### Before Committing Tests

- [ ] All tests pass: `composer test`
- [ ] PHPCS clean: `composer phpcs tests/`
- [ ] PHPStan Level 8: `composer phpstan`
- [ ] Test names are descriptive
- [ ] PHPDoc annotations present (@group, @covers)
- [ ] setUp/tearDown clean resources
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
vendor/bin/phpunit tests/Unit/RequestLoggerTest.php

# Run specific test method
vendor/bin/phpunit --filter testSearchMatchesSenderName

# Run tests by group
vendor/bin/phpunit --group unit
vendor/bin/phpunit --group integration

# Run with coverage (requires Xdebug)
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

**Last Updated**: January 23, 2026  
**Maintained By**: Silver Assist  
**Repository**: https://github.com/SilverAssist/contact-form-to-api
