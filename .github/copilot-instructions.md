# Contact Form 7 to API - Copilot Instructions

## Project Overview

WordPress plugin integrating Contact Form 7 with external APIs. Follows **SilverAssist WordPress Plugin Development Standards**.

| Attribute | Value |
|-----------|-------|
| Namespace | `SilverAssist\ContactFormToAPI\` |
| PHP | 8.2+ |
| WordPress | 6.5+ |
| Standards | PHPCS (WordPress-Extra), PHPStan Level 8, PHPUnit |

## Architecture

### LoadableInterface Priority System
All components implement `LoadableInterface` with `init()`, `get_priority()`, `should_load()`:
- **10**: Core (Plugin, Activator, EncryptionService)
- **20**: Services (ApiClient, LogWriter, LogReader, LogStatistics, RetryManager, MigrationService)
- **30**: Admin & Controllers (SettingsPage, LogsController, SubmissionController)
- **40**: Utils (DebugLogger, StringHelper)

### Key Directories
- `includes/` - PSR-4 source code
- `includes/Service/` - Business logic (Logging, Api, Security, Migration)
- `includes/View/` - HTML rendering (static classes)
- `includes/Controller/` - Request handlers
- `tests/` - WordPress Test Suite

### Dual Logger System
- **`Service\Logging\*`**: Database logs for API tracking (admin UI)
  - `LogWriter` - Create/update logs
  - `LogReader` - Query logs
  - `LogStatistics` - Statistics calculations
  - `RetryManager` - Retry logic
- **`Utils\DebugLogger`**: File logs for debugging (`wp-content/uploads/`)

### Data Encryption (libsodium)
Sensitive API data is encrypted at rest using `Service\Security\EncryptionService`:
- **Algorithm**: XSalsa20 + Poly1305 (authenticated encryption)
- **Key**: Derived from WordPress `AUTH_KEY` via HKDF
- **Encrypted fields**: `request_data`, `request_headers`, `response_data`, `response_headers`
- **Always decrypt for display**: Use `LogReader::decrypt_log_fields()`

## Critical Rules

### Pre-PR Checklist (MANDATORY)
```bash
vendor/bin/phpcbf              # Auto-fix formatting
vendor/bin/phpcs               # Must pass with 0 errors
vendor/bin/phpstan analyse     # Must pass Level 8
vendor/bin/phpunit             # Must pass all tests
wp i18n make-pot . languages/contact-form-to-api.pot --domain=contact-form-to-api  # Update translations
```
⚠️ **Always regenerate `.pot` before PRs** to capture new/changed strings.

### WordPress Functions in Namespaced Code
```php
// ✅ Use backslash prefix for WP functions
\add_action('init', [$this, 'init']);
\get_option('option_name');

// ❌ No prefix for PHP native functions
array_key_exists($key, $array);
```

### String Quotation (PHPCS)
```php
// ✅ Single quotes for simple strings
$status = 'active';
__('Text', 'contact-form-to-api');

// ✅ Double quotes for interpolation
$path = "includes/{$directory}/file.php";
```

### i18n Ordered Placeholders
```php
// ✅ Multiple args require positional format
sprintf(
    /* translators: %1$d: count, %2$s: status */
    __('Found %1$d items with status %2$s', 'contact-form-to-api'),
    $count,
    $status
)
```

## Additional Resources

- **File-specific instructions**: `.github/instructions/*.instructions.md`
- **Specialized skills**: `.github/skills/*/SKILL.md`
  - `release-management` - Version bumps, tags, releases
  - `pr-review-response` - **Responding to PR reviews (NOT comments)** - Use `gh api graphql` to reply
  - `quality-checks` - PHPCS, PHPStan, PHPUnit troubleshooting
  - `create-component` - New services, controllers, views
  - `database-operations` - SQL, encryption, migrations
  - `github-cli` - Workflow monitoring, PR management
  - `i18n-translations` - Generate .pot files, translation workflow

### ⚠️ PR Reviews vs Comments

**Reviews** (inline on code in "Files changed") ≠ **Comments** (general discussion in "Conversation")

- **To READ reviews**: Use MCP `pull_request_read` or GraphQL
- **To REPLY to reviews**: Must use `gh api graphql` (MCP cannot reply to review threads)
- **See**: `.github/skills/pr-review-response/SKILL.md` for full workflow

## Quick References

| Task | Command/Location |
|------|------------------|
| Run all quality checks | `./scripts/run-quality-checks.sh` |
| Update versions | `./scripts/update-version-simple.sh X.Y.Z` |
| Verify version consistency | `./scripts/check-versions.sh` |
| **Update translations** | `wp i18n make-pot . languages/contact-form-to-api.pot --domain=contact-form-to-api` |
| Create component | See `create-component` skill |
| Database table | `{prefix}cf7_api_logs` |
| Text domain | `'contact-form-to-api'` (literal string always) |
