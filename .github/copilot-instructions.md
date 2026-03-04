# Contact Form 7 to API — Project Context

WordPress plugin integrating Contact Form 7 with external APIs.
Follows **SilverAssist WordPress Plugin Development Standards** (see global instructions and skills).

| Attribute | Value |
|-----------|-------|
| Namespace | `SilverAssist\ContactFormToAPI\` |
| Text Domain | `contact-form-to-api` |
| Version | 2.3.0 |
| Database Table | `{prefix}cf7_api_logs` |

## Plugin-Specific Architecture

### Components (LoadableInterface)
- **10**: Core (Plugin, Activator, EncryptionService)
- **20**: Services (ApiClient, LogWriter, LogReader, LogStatistics, RetryManager, MigrationService)
- **30**: Admin & Controllers (SettingsPage, LogsController, SubmissionController)
- **40**: Utils (DebugLogger, StringHelper)

### Key Directories
- `includes/Service/` — Business logic (Logging, Api, Security, Migration)
- `includes/View/` — HTML rendering (static classes)
- `includes/Controller/` — Request handlers

### Dual Logger System
- **`Service\Logging\*`**: Database logs for API tracking (admin UI)
  - `LogWriter` — Create/update logs
  - `LogReader` — Query logs
  - `LogStatistics` — Statistics calculations
  - `RetryManager` — Retry logic
- **`Utils\DebugLogger`**: File logs for debugging (`wp-content/uploads/`)

### Data Encryption (libsodium)
Sensitive API data is encrypted at rest using `Service\Security\EncryptionService`:
- **Algorithm**: XSalsa20 + Poly1305 (authenticated encryption)
- **Key**: Derived from WordPress `AUTH_KEY` via HKDF
- **Encrypted fields**: `request_data`, `request_headers`, `response_data`, `response_headers`
- **Always decrypt for display**: Use `LogReader::decrypt_log_fields()`

## Quick References

| Task | Command |
|------|---------|
| Quality checks | `./scripts/run-quality-checks.sh` |
| Update versions | `./scripts/update-version-simple.sh X.Y.Z` |
| Verify versions | `./scripts/check-versions.sh` |
| Update translations | `wp i18n make-pot . languages/contact-form-to-api.pot --domain=contact-form-to-api` |
