---
name: database-operations
description: Database operations including table creation, migrations, and queries. Use when working with the cf7_api_logs table, encryption, or data migrations. Includes dbDelta rules and prepared statements.
---

# Database Operations Skill

## When to Use

- Creating or modifying database tables
- Writing SQL queries with user input
- Working with the `cf7_api_logs` table
- Implementing data migrations

## Database Schema

### Main Table: `{prefix}cf7_api_logs`

```sql
CREATE TABLE {prefix}cf7_api_logs (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    form_id bigint(20) UNSIGNED NOT NULL,
    form_title varchar(255) NOT NULL DEFAULT '',
    api_url varchar(2048) NOT NULL,
    request_method varchar(10) NOT NULL DEFAULT 'POST',
    request_data longtext,          -- Encrypted JSON
    request_headers longtext,       -- Encrypted JSON
    response_code int(11) DEFAULT NULL,
    response_data longtext,         -- Encrypted JSON
    response_headers longtext,      -- Encrypted JSON
    status varchar(20) NOT NULL DEFAULT 'pending',
    error_message text,
    response_time float DEFAULT NULL,
    retry_count int(11) NOT NULL DEFAULT 0,
    encryption_version int(11) NOT NULL DEFAULT 1,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY form_id (form_id),
    KEY status (status),
    KEY created_at (created_at)
) {charset_collate};
```

## Table Creation (dbDelta)

### CRITICAL: dbDelta Rules

```php
// ✅ CORRECT - dbDelta cannot use prepared statements
$table_name = $wpdb->prefix . 'cf7_api_logs';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE {$table_name} (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    ...
    PRIMARY KEY  (id)
) {$charset_collate};";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
\dbDelta($sql);
```

**Why no prepared statements?** `dbDelta()` parses raw SQL and cannot handle placeholders. Table name is safe because it's `$wpdb->prefix` + literal string.

### dbDelta Formatting Requirements

- **Two spaces** after `PRIMARY KEY`
- **No trailing comma** after last column
- Each column on its own line

## DROP TABLE (Use %i Placeholder)

```php
// ✅ CORRECT - WordPress 6.2+ identifier placeholder
$wpdb->query(
    $wpdb->prepare('DROP TABLE IF EXISTS %i', $table_name)
);
```

## Prepared Statements

### With String Values (%s)

```php
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM %i WHERE status = %s AND form_id = %d",
        $table_name,
        'success',
        $form_id
    )
);
```

### Placeholder Types

- `%s` - String (sanitized)
- `%d` - Integer
- `%f` - Float
- `%i` - Identifier (table/column name, WP 6.2+)

### ⚠️ NEVER Do This

```php
// ❌ DANGEROUS - SQL injection
$wpdb->query("SELECT * FROM $table WHERE id = $id");

// ❌ WRONG - Direct variable in WHERE
$wpdb->get_results("SELECT * FROM {$table} WHERE status = '{$status}'");
```

## Encrypted Fields

### Writing Encrypted Data

```php
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;

$encryption = EncryptionService::instance();
$encrypted_data = $encryption->encrypt(wp_json_encode($data));

$wpdb->insert(
    $table_name,
    [
        'request_data' => $encrypted_data,
        'encryption_version' => $encryption->get_version(),
    ],
    ['%s', '%d']
);
```

### Reading Encrypted Data

```php
$log = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM %i WHERE id = %d",
    $table_name,
    $log_id
));

// Decrypt before display
$log = RequestLogger::decrypt_log_fields($log);
```

## Migration Pattern

### Check for Unencrypted Logs

```php
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM %i WHERE encryption_version = 0",
    $table_name
));
```

### Batch Migration

```php
$batch = $wpdb->get_results($wpdb->prepare(
    "SELECT id, request_data, request_headers, response_data, response_headers
     FROM %i
     WHERE encryption_version = 0
     LIMIT %d",
    $table_name,
    $batch_size
));

foreach ($batch as $log) {
    // Encrypt and update
    $wpdb->update(
        $table_name,
        [
            'request_data' => $encryption->encrypt($log->request_data),
            'encryption_version' => 1,
        ],
        ['id' => $log->id],
        ['%s', '%d'],
        ['%d']
    );
}
```

## Testing Database Operations

### Create Tables in wpSetUpBeforeClass

```php
public static function wpSetUpBeforeClass(): void {
    parent::wpSetUpBeforeClass();
    
    // CRITICAL: Create tables BEFORE any data insertion
    // Avoids MySQL implicit COMMIT issues
    Activator::create_tables();
}
```
