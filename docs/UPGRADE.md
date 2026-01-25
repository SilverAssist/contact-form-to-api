# Upgrade Guide

**Plugin**: Contact Form to API  
**Version**: 2.0.0  
**Last Updated**: January 25, 2026

---

## Overview

This guide helps you upgrade to version 2.0.0 of Contact Form to API.

---

## Requirements

| Component | Minimum Version |
|-----------|-----------------|
| PHP | 8.2+ |
| WordPress | 6.5+ |
| Contact Form 7 | 5.9+ |

---

## Upgrade Steps

### For End Users (WordPress Admin)

1. **Backup Your Database**

   ```bash
   wp db export backup-before-upgrade.sql
   ```

2. **Backup Plugin Settings**
   - Export all form configurations (Settings → Export)
   - Save a copy of your API endpoints and credentials

3. **Update the Plugin**
   - Via WordPress Admin: Plugins → Update
   - Via GitHub: Download latest release and replace plugin files

4. **Verify Functionality**
   - Check API Logs page loads correctly
   - Submit a test form to verify API integration works
   - Review logs to ensure data is captured

5. **Clear Caches**
   - Clear WordPress object cache
   - Clear any page caching plugins

### For Developers

#### 1. Update Dependencies

```bash
cd wp-content/plugins/contact-form-to-api
composer update
composer dump-autoload -o
```

#### 2. Run Quality Checks

```bash
composer phpcs
composer phpstan
composer test
```

#### 3. Update Custom Code

If your code directly uses plugin classes, update the namespace imports:

```php
// Security services
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use SilverAssist\ContactFormToAPI\Service\Security\SensitiveDataPatterns;

// Configuration
use SilverAssist\ContactFormToAPI\Config\Settings;

// Logging services
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;

// Controllers
use SilverAssist\ContactFormToAPI\Controller\ContactForm\SubmissionController;

// Services
use SilverAssist\ContactFormToAPI\Service\ContactForm\SubmissionProcessor;
```

---

## What's New in 2.0.0

### Type-Safe Domain Models

```php
use SilverAssist\ContactFormToAPI\Model\LogEntry;
use SilverAssist\ContactFormToAPI\Model\FormSettings;
use SilverAssist\ContactFormToAPI\Model\Statistics;

$entry = new LogEntry(
    form_id: 123,
    endpoint: 'https://api.example.com/webhook',
    method: 'POST',
    status: 'success'
);

if ( $entry->is_successful() ) {
    // Handle success
}
```

### Specialized Logging Services

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;

// Write logs
$writer = LogWriter::instance();
$log_id = $writer->save( $log_entry );

// Query logs
$reader = LogReader::instance();
$logs = $reader->get_logs( array( 'status' => 'success' ) );

// Get statistics
$stats = LogStatistics::instance();
$metrics = $stats->get_overview();

// Manage retries
$retry = RetryManager::instance();
$retry_id = $retry->create_retry_entry( $original_log_id );
```

### Controller/Service Separation

```php
use SilverAssist\ContactFormToAPI\Controller\ContactForm\SubmissionController;
use SilverAssist\ContactFormToAPI\Service\ContactForm\SubmissionProcessor;

// These are automatically loaded via WordPress hooks
// You typically don't need to interact with them directly
```

### Repository Interfaces

```php
use SilverAssist\ContactFormToAPI\Repository\LogRepositoryInterface;
use SilverAssist\ContactFormToAPI\Repository\SettingsRepositoryInterface;
```

---

## Rollback Procedure

If you need to rollback:

1. **Deactivate Plugin**

   ```bash
   wp plugin deactivate contact-form-to-api
   ```

2. **Restore Old Version**
   - Download previous release from GitHub
   - Replace plugin files

3. **Restore Database** (if needed)

   ```bash
   wp db import backup-before-upgrade.sql
   ```

4. **Reactivate Plugin**

   ```bash
   wp plugin activate contact-form-to-api
   ```

---

## Common Issues

### Issue: "Class not found" error

**Cause**: Autoloader cache not regenerated

**Solution**:

```bash
cd wp-content/plugins/contact-form-to-api
composer dump-autoload -o
```

### Issue: PHP errors in logs

**Cause**: PHP version incompatibility

**Solution**: Ensure PHP 8.2+ is installed:

```bash
php -v
# Should show PHP 8.2.0 or higher
```

### Issue: Settings not loading

**Cause**: Cache not cleared

**Solution**:

```bash
wp cache flush
wp transient delete --all
```

---

## Testing Checklist

### Manual Testing

- [ ] Plugin activates without errors
- [ ] Settings page loads correctly
- [ ] API Logs page displays data
- [ ] Form submission creates log entry
- [ ] API request is sent successfully
- [ ] Error logs capture failures
- [ ] Retry functionality works
- [ ] Export functionality works
- [ ] Dashboard widget displays stats

### Automated Testing

```bash
composer test
composer phpcs
composer phpstan
```

---

## FAQ

**Q: Do I need to update my form configurations?**  
A: No, all existing form configurations continue to work without changes.

**Q: Will my API logs be preserved?**  
A: Yes, all log data is preserved. Database schema remains unchanged.

**Q: Are custom hooks affected?**  
A: No, all existing hooks continue to work.

**Q: What about performance?**  
A: No performance changes expected. The refactoring is purely architectural.

---

## Support

### Before Opening an Issue

1. Check this upgrade guide
2. Review CHANGELOG.md for known issues
3. Search existing GitHub issues
4. Enable debug mode and check logs

### Reporting Issues

Include in your bug report:

- WordPress version
- PHP version
- Plugin version
- Error messages from debug log
- Steps to reproduce

**GitHub Issues**: <https://github.com/SilverAssist/contact-form-to-api/issues>

---

**Document Version**: 2.0.0  
**Last Updated**: January 25, 2026  
**Maintained By**: Silver Assist Development Team
