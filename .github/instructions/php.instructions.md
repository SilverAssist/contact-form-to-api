---
description: PHP coding standards, WordPress conventions, and security best practices
name: PHP Standards
applyTo: "**/*.php"
---

# PHP Code Quality Standards for Contact Form to API Plugin

**Applies to**: `**/*.php`  
**Last Updated**: January 23, 2026  
**Project**: Contact Form to API WordPress Plugin

---

## Project Context

**WordPress Plugin**: Contact Form to API v1.3.x (PSR-4, PHP 8.2+, WordPress 6.5+)  
**Namespace**: `SilverAssist\ContactFormToAPI`  
**Repository Root**: `/` (plugin files at repository root)  
**Language Policy**: ALL code and comments MUST be in English

---

## Quality Gates (MANDATORY Before Commit)

**CRITICAL**: ALWAYS run these validations BEFORE committing PHP code:

```bash
# From repository root

# 1. Auto-fix formatting (REQUIRED FIRST)
vendor/bin/phpcbf

# 2. Check WordPress Coding Standards (MUST PASS - 0 errors)
vendor/bin/phpcs

# 3. Static Analysis Level 8 (MUST PASS - 0 errors)
vendor/bin/phpstan analyse includes/ --level=8

# 4. Run tests (MUST PASS - all green)
vendor/bin/phpunit
```

**Zero tolerance**: Code with PHPCS errors or PHPStan errors will be rejected by CI/CD.

---

## WordPress Coding Standards (PHPCS)

### 1. Inline Comments MUST End with Punctuation

```php
// ✅ CORRECT
// This is a proper comment.
the_content();

// Process the data before sending to API.
if ( $condition ) {
    // Execute the request.
    $this->send_request();
}

// ❌ WRONG - Will fail PHPCS
// This is wrong
the_content();

// No period here
process_data();
```

### 2. No Trailing Whitespace

- Configure editor to auto-remove trailing spaces on save
- PHPCBF auto-fixes this violation
- Check: no spaces/tabs at end of lines

### 3. Use Tabs for Indentation (WordPress Standard)

```php
// ✅ CORRECT
if ( $condition ) {
	echo 'Use tabs';
}

// ❌ WRONG
if ( $condition ) {
    echo 'Spaces fail PHPCS';
}
```

### 4. Spaces Inside Parentheses

```php
// ✅ CORRECT - Spaces inside
if ( $condition ) {
    do_something( $param1, $param2 );
}

// ❌ WRONG - No spaces
if ($condition) {
    do_something($param1, $param2);
}
```

### 5. String Quotation

```php
// ✅ CORRECT - Single quotes for strings without interpolation
$status = 'error';
$where .= ' AND status IN (\'error\', \'client_error\', \'server_error\')';

// ✅ CORRECT - Double quotes for interpolation
$message = "Error in form {$form_id}";

// ❌ WRONG - Double quotes without interpolation
$status = "error";
```

---

## Security (CRITICAL)

### Nonce Validation (CSRF Protection)

**ALWAYS validate nonces in AJAX handlers and form submissions.**

```php
// ✅ CORRECT - Verify nonce FIRST in AJAX handlers.
public function ajax_handler(): void {
    check_ajax_referer( 'cf7_api_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
        return;
    }
    
    // Process the request.
    wp_send_json_success( $data );
}

// ❌ WRONG - No nonce check.
public function ajax_handler(): void {
    $data = $_POST['data']; // Vulnerable to CSRF!
}
```

### Input Sanitization

**Always sanitize user input before using or storing:**

```php
$text   = \sanitize_text_field( \wp_unslash( $_POST['field'] ) );
$email  = \sanitize_email( $_POST['email'] );
$url    = \esc_url_raw( $_POST['url'] );  // For database storage.
$int    = \absint( $_POST['number'] );     // Positive integer.
$key    = \sanitize_key( $_POST['key'] );  // Lowercase alphanumeric.
$array  = \array_map( 'sanitize_text_field', $_POST['items'] );
```

### Output Escaping

**ALL dynamic output MUST be escaped.**

| Function | Use Case | Example |
|----------|----------|---------|
| `esc_html()` | Plain text (NO HTML) | `<h1><?php echo esc_html( $title ); ?></h1>` |
| `esc_url()` | URLs (href, src) | `<a href="<?php echo esc_url( $url ); ?>">` |
| `esc_attr()` | HTML attributes | `<div class="<?php echo esc_attr( $class ); ?>">` |
| `wp_kses_post()` | Rich HTML content | `<?php echo wp_kses_post( $content ); ?>` |

### Database Queries

**ALWAYS use prepared statements with placeholders:**

```php
// ✅ CORRECT - Prepared statement with %i for table name (WordPress 6.2+).
$results = $wpdb->get_results(
    $wpdb->prepare(
        'SELECT * FROM %i WHERE status = %s AND form_id = %d',
        $table_name,
        $status,
        $form_id
    ),
    ARRAY_A
);

// ✅ CORRECT - Dynamic WHERE with single prepare() call.
$conditions = array( '1=1' );
$values     = array( $table_name ); // First value is table name for %i.

if ( $filter_status ) {
    $conditions[] = 'status = %s';
    $values[]     = $status;
}

$where_clause = implode( ' AND ', $conditions );
$query        = "SELECT * FROM %i WHERE {$where_clause}";
$results      = $wpdb->get_results( $wpdb->prepare( $query, ...$values ), ARRAY_A );

// ❌ WRONG - Direct interpolation (SQL injection risk).
$results = $wpdb->get_results(
    "SELECT * FROM {$table_name} WHERE status = '{$status}'"
);
```

**Placeholder Reference:**
| Placeholder | Use Case | Example |
|-------------|----------|---------|
| `%i` | Identifiers (table/column names) | `SELECT * FROM %i` |
| `%s` | Strings | `WHERE status = %s` |
| `%d` | Integers | `WHERE id = %d` |
| `%f` | Floats | `WHERE price = %f` |

---

## Plugin Architecture (PSR-4)

### Directory Structure (Repository Root)

```
/                          # Repository root
├── includes/              # PSR-4 classes (SilverAssist\ContactFormToAPI namespace)
│   ├── Admin/            # Admin controllers and views
│   │   ├── Views/        # HTML rendering classes
│   │   ├── RequestLogController.php
│   │   ├── RequestLogTable.php
│   │   └── ...
│   ├── ContactForm/      # CF7 integration
│   │   ├── Views/
│   │   └── Integration.php
│   ├── Core/             # Core functionality
│   │   ├── Interfaces/
│   │   ├── Plugin.php
│   │   ├── RequestLogger.php
│   │   ├── Settings.php
│   │   └── ...
│   ├── Exceptions/       # Custom exceptions
│   ├── Services/         # Business logic services
│   │   ├── ApiClient.php
│   │   ├── ExportService.php
│   │   └── ...
│   └── Utils/            # Utility classes
│       ├── DebugLogger.php
│       └── StringHelper.php
├── tests/                # PHPUnit tests
│   ├── Unit/            # Unit tests (no WordPress)
│   ├── Integration/     # Integration tests (WordPress)
│   └── Helpers/         # Test utilities
├── contact-form-to-api.php  # Main plugin file
└── vendor/              # Composer dependencies
```

### Key Conventions

1. **All files MUST start with:**
```php
<?php
/**
 * File description.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since      1.1.0
 */

namespace SilverAssist\ContactFormToAPI\Core;

\defined( 'ABSPATH' ) || exit;
```

2. **Classes implement LoadableInterface for initialization:**
```php
class MyService implements LoadableInterface {
    private static ?MyService $instance = null;
    private bool $initialized = false;

    public static function instance(): MyService {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        if ( $this->initialized ) {
            return;
        }
        // Initialization logic.
        $this->initialized = true;
    }

    public function get_priority(): int {
        return 10;
    }

    public function should_load(): bool {
        return true;
    }
}
```

3. **Use fully qualified function calls with backslash:**
```php
// ✅ CORRECT - Backslash for global functions.
\sanitize_text_field( $input );
\wp_send_json_success( $data );
\add_action( 'init', array( $this, 'init' ) );

// ❌ WRONG - No backslash.
sanitize_text_field( $input );
```

---

## Type Safety (PHPStan Level 8)

### Strict Types Required

```php
// ✅ CORRECT - Full type hints.
public function process_log( int $log_id, string $status ): bool {
    return true;
}

/**
 * Get logs with filters.
 *
 * @param array<string, mixed> $filters Filter parameters.
 * @return array<int, array<string, mixed>> Log entries.
 */
public function get_logs( array $filters ): array {
    return array();
}

// ❌ WRONG - Missing types.
public function process_log( $log_id, $status ) {
    return true;
}
```

### Nullable Types

```php
// ✅ CORRECT
private ?int $log_id = null;

public function get_log( int $log_id ): ?array {
    // May return null if not found.
}

// ✅ CORRECT - Union types for PHP 8.0+
public function set_value( int|string $value ): void {
}
```

### PHPDoc for Complex Types

```php
/**
 * @param array{
 *     id: int,
 *     status: string,
 *     form_id: int,
 *     endpoint: string,
 *     error_message?: string
 * } $log_entry Log entry data.
 * @return array{items: array<int, array<string, mixed>>, total: int}
 */
public function process_entry( array $log_entry ): array {
}
```

---

## Internationalization (i18n)

### Pre-PR Requirement: Update .pot File

**MANDATORY before every PR** - regenerate translation template to capture new/changed strings:

```bash
wp i18n make-pot . languages/contact-form-to-api.pot --domain=contact-form-to-api
```

### Text Domain (CRITICAL)

**Always use literal string** - extraction tools cannot parse variables:

```php
// ✅ CORRECT - Literal text domain (extractable).
__( 'Error occurred', 'contact-form-to-api' );
esc_html_e( 'Success!', 'contact-form-to-api' );

// ❌ WRONG - Variable/constant (NOT extractable).
__( 'Error', $text_domain );
__( 'Error', PLUGIN_TEXT_DOMAIN );
```

### Ordered Placeholders (MANDATORY for Multiple Args)

```php
// ✅ CORRECT - Positional placeholders with translator comment.
sprintf(
    /* translators: %1$s: form name, %2$d: submission count */
    __( 'Form "%1$s" has %2$d submissions', 'contact-form-to-api' ),
    $form_name,
    $count
);

// ❌ WRONG - Unordered placeholders (PHPCS error).
sprintf( __( 'Form "%s" has %d submissions', 'contact-form-to-api' ), $form_name, $count );
```

### Translation Functions Reference

| Function | Use Case |
|----------|----------|
| `__()` | Return translated string |
| `_e()` | Echo translated string |
| `esc_html__()` | Return translated + escaped for HTML |
| `esc_html_e()` | Echo translated + escaped for HTML |
| `esc_attr__()` | Return translated + escaped for attribute |
| `_n()` | Singular/plural forms |
| `_x()` | Translation with context |

---

## Data Encryption (libsodium)

### Architecture Overview

The plugin encrypts sensitive API data using **libsodium** (PHP Sodium extension):
- **Algorithm**: XSalsa20 stream cipher with Poly1305 MAC (authenticated encryption)
- **Key Derivation**: HKDF from WordPress `AUTH_KEY` constant
- **Storage**: Encrypted data stored in `{prefix}cf7_api_logs` table

### Encrypted Fields

| Column | Content |
|--------|---------|
| `request_data` | Form submission data sent to API |
| `request_headers` | HTTP headers including auth tokens |
| `response_data` | API response body |
| `response_headers` | API response headers |
| `encryption_version` | Tracks encryption version (0 = unencrypted) |

### Using EncryptionService

```php
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;

// Encrypt data before storage.
$encryption = EncryptionService::instance();
$encrypted = $encryption->encrypt( wp_json_encode( $sensitive_data ) );

// Store in database.
$wpdb->insert( $table_name, [
    'request_data' => $encrypted,
    'encryption_version' => $encryption->get_version(),
] );

// Decrypt when reading.
$encrypted_data = $row->request_data;
$decrypted = $encryption->decrypt( $encrypted_data );
$data = json_decode( $decrypted, true );
```

### Key Methods

| Method | Purpose |
|--------|---------|
| `encrypt( string $plaintext )` | Encrypt data (returns base64) |
| `decrypt( string $data )` | Decrypt data (handles legacy plaintext) |
| `is_encrypted( string $data )` | Check if data appears encrypted |
| `is_encryption_enabled()` | Check if encryption is enabled in settings |
| `get_version()` | Get current encryption version |

### Important Notes

1. **Decryption handles legacy data**: If data is plaintext JSON, it's returned as-is
2. **Graceful degradation**: If Sodium unavailable, data stored unencrypted
3. **Memory cleanup**: Service uses `sodium_memzero()` to clear sensitive data
4. **Always decrypt for display**: Use `RequestLogger::decrypt_log_fields()` before showing logs in admin UI

---

## Build & Validation Workflow

### Before Making Changes

```bash
# From repository root

# Install dependencies if needed
composer install
```

### After Making Changes (MANDATORY)

```bash
# Step 1: Auto-fix what can be fixed
vendor/bin/phpcbf

# Step 2: Check for remaining issues
vendor/bin/phpcs

# Step 3: Run static analysis
vendor/bin/phpstan analyse includes/ --level=8

# Step 4: Run tests
vendor/bin/phpunit

# Step 5: Verify specific files pass
vendor/bin/phpcs path/to/modified/file.php
vendor/bin/phpstan analyse path/to/modified/file.php --level=8
```

### Common PHPCS Errors & Fixes

| Error | Fix |
|-------|-----|
| `Inline comments must end in full-stops` | Add `.` to end of comment |
| `Whitespace found at end of line` | Remove trailing spaces (phpcbf auto-fixes) |
| `Tabs must be used to indent lines` | Replace spaces with tabs |
| `Use single quotes when not evaluating` | Change `"string"` to `'string'` |
| `Detected usage of a non-sanitized input` | Add `sanitize_*()` function |
| `OutputNotEscaped` | Use `esc_html()`, `esc_url()`, `esc_attr()` |

---

## Quick Reference

### Composer Scripts

```bash
composer phpcs      # Run PHPCS
composer phpcbf     # Auto-fix with PHPCBF
composer phpstan    # Run PHPStan Level 8
composer test       # Run PHPUnit tests
composer check      # Run all checks
```

### Quality Thresholds

| Tool | Requirement | Command |
|------|-------------|---------|
| PHPCS | 0 errors | `composer phpcs` |
| PHPStan | Level 8, 0 errors | `composer phpstan` |
| PHPUnit | All tests pass | `composer test` |

---

**Last Updated**: January 23, 2026  
**Maintained By**: Silver Assist  
**Repository**: https://github.com/SilverAssist/contact-form-to-api
