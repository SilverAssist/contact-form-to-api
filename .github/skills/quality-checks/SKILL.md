---
name: quality-checks
description: Run code quality checks including PHPCS, PHPStan, and PHPUnit tests. Use before committing, pushing, or when CI fails. Includes auto-fix commands and troubleshooting.
---

# Quality Checks Skill

## When to Use

- Before committing any PHP code
- When CI/CD pipeline fails
- When PHPCS or PHPStan errors need fixing
- Before creating a PR

## Quick Reference

### Full Quality Check Suite

```bash
./scripts/run-quality-checks.sh
```

### Individual Commands

```bash
# 1. Auto-fix formatting issues (run FIRST)
vendor/bin/phpcbf

# 2. Check remaining issues (MUST have 0 errors)
vendor/bin/phpcs

# 3. Static analysis (MUST pass)
vendor/bin/phpstan analyse includes/ --level=8

# 4. Run all tests (MUST pass)
vendor/bin/phpunit

# 5. Run specific test file
vendor/bin/phpunit --filter TestClassName

# 6. Run specific test method
vendor/bin/phpunit --filter test_method_name
```

## Common PHPCS Errors and Fixes

### String Quotation

```php
// ❌ WRONG - Double quotes without interpolation
$status = "active";

// ✅ CORRECT - Single quotes for simple strings
$status = 'active';

// ✅ CORRECT - Double quotes for interpolation
$path = "includes/{$directory}/file.php";
```

### WordPress Function Prefix

```php
// ❌ WRONG - Missing backslash in namespaced code
add_action('init', [$this, 'init']);

// ✅ CORRECT - Backslash prefix for WP functions
\add_action('init', [$this, 'init']);
```

### Inline Comments

```php
// ❌ WRONG - Missing period
// This is a comment

// ✅ CORRECT - Ends with punctuation
// This is a comment.
```

### i18n Ordered Placeholders

```php
// ❌ WRONG - Simple placeholders with multiple args
sprintf(__('Batch %d status: %s', 'contact-form-to-api'), $num, $status)

// ✅ CORRECT - Ordered placeholders
sprintf(
    /* translators: %1$d: batch number, %2$s: status */
    __('Batch %1$d status: %2$s', 'contact-form-to-api'),
    $num,
    $status
)
```

## PHPStan Common Issues

### Missing Return Types

```php
// ❌ WRONG
public function get_data() { return []; }

// ✅ CORRECT
public function get_data(): array { return []; }
```

### Undefined Variables

Always initialize variables before conditional blocks.

## Test Environment Setup

If tests fail with "WordPress Test Suite not found":

```bash
# Set environment variables
export WP_TESTS_DIR="/tmp/wordpress-tests-lib"
export WP_CORE_DIR="/tmp/wordpress"

# Install test suite (if not done)
./scripts/install-wp-tests.sh wordpress_test root "" localhost latest
```

## Zero Tolerance Policy

CI/CD will reject code with:

- Any PHPCS errors
- Any PHPStan Level 8 errors
- Any failing tests

**Always run quality checks locally before pushing.**
