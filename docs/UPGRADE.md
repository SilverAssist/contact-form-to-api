# Upgrade Guide

**Plugin**: Contact Form to API  
**From**: Version 1.x  
**To**: Version 2.0.0  
**Last Updated**: January 23, 2026

---

## Overview

Version 2.0.0 introduces a comprehensive architecture refactoring to improve code quality, maintainability, and adherence to SOLID principles. This guide helps you upgrade from 1.x to 2.0.0.

---

## Breaking Changes Summary

### Phase 1 (v2.0.0-alpha) - ✅ Current Release

**Status**: **NO BREAKING CHANGES**

Phase 1 is purely additive:
- New Model classes added
- New Repository interfaces added
- New directory structure created
- All existing code continues to work unchanged

**Action Required**: None - upgrade is safe and transparent.

### Future Phases (v2.0.0-beta and beyond)

Future phases will introduce breaking changes with deprecation warnings. This guide will be updated as each phase is released.

---

## Upgrade Steps

### For End Users (WordPress Admin)

1. **Backup Your Database**
   ```bash
   # Create database backup before upgrading
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
   - Regenerate autoloader: `composer dump-autoload -o`

### For Developers

#### 1. Update Dependencies

```bash
cd wp-content/plugins/contact-form-to-api
composer update
composer dump-autoload -o
```

#### 2. Run Quality Checks

```bash
# Check for deprecation warnings
composer phpcs

# Verify no errors
composer phpstan

# Run tests
composer test
```

#### 3. Update Custom Integrations (If Any)

If your code directly uses plugin classes, review deprecation notices:

```php
// Old way (deprecated in future phases)
$logger = new \SilverAssist\ContactFormToAPI\Core\RequestLogger();
$logger->log( $data );

// New way (recommended for new code)
use SilverAssist\ContactFormToAPI\Model\LogEntry;

$entry = new LogEntry(
    form_id: 123,
    endpoint: 'https://api.example.com',
    method: 'POST',
    status: 'success',
    request_data: $data
);
```

---

## Compatibility Matrix

| Component | 1.x | 2.0.0-alpha | Notes |
|-----------|-----|-------------|-------|
| WordPress | 6.5+ | 6.5+ | No change |
| PHP | 8.2+ | 8.2+ | No change |
| CF7 | 5.9+ | 5.9+ | No change |
| Database | v1 schema | v1 schema | No migration needed |
| Settings | Compatible | Compatible | No migration needed |
| Hooks | Compatible | Compatible | All hooks maintained |

---

## New Features in 2.0.0-alpha

### Phase 1: Model Layer (Complete)

Type-safe domain models for better code quality:

```php
use SilverAssist\ContactFormToAPI\Model\LogEntry;
use SilverAssist\ContactFormToAPI\Model\FormSettings;
use SilverAssist\ContactFormToAPI\Model\Statistics;

// Example: Create log entry
$entry = new LogEntry(
    form_id: 123,
    endpoint: 'https://api.example.com/webhook',
    method: 'POST',
    status: 'success'
);

// Type-safe methods
if ( $entry->is_successful() ) {
    // Handle success
}

if ( $entry->is_retry() ) {
    // Handle retry
}
```

### Phase 2: Logging Services (Complete)

Specialized services for log management:

```php
use SilverAssist\ContactFormToAPI\Service\Logging\LogWriter;
use SilverAssist\ContactFormToAPI\Service\Logging\LogReader;
use SilverAssist\ContactFormToAPI\Service\Logging\LogStatistics;
use SilverAssist\ContactFormToAPI\Service\Logging\RetryManager;

// Example: Create log entry
$writer = LogWriter::instance();
$log_id = $writer->save( $log_entry );

// Example: Query logs
$reader = LogReader::instance();
$logs = $reader->get_logs( array( 'status' => 'success' ) );

// Example: Get statistics
$stats = LogStatistics::instance();
$metrics = $stats->get_overview();

// Example: Manage retries
$retry = RetryManager::instance();
$retry_id = $retry->create_retry_entry( $original_log_id );
```

### Phase 3: Controller & Service Separation (Complete)

Cleaner separation between routing and business logic:

```php
use SilverAssist\ContactFormToAPI\Controller\ContactForm\SubmissionController;
use SilverAssist\ContactFormToAPI\Service\ContactForm\SubmissionProcessor;

// Controller handles hooks and routing (automatically loaded)
$controller = SubmissionController::instance();

// Service handles business logic (automatically loaded)
$processor = SubmissionProcessor::instance();

// You typically don't need to interact with these directly -
// they work automatically via WordPress hooks
```

### Repository Interfaces (Complete)

Clear contracts for data access:

```php
use SilverAssist\ContactFormToAPI\Repository\LogRepositoryInterface;
use SilverAssist\ContactFormToAPI\Repository\SettingsRepositoryInterface;

// Future implementations will follow these interfaces
// Existing code continues to work during transition
```

---

## Deprecation Timeline

### Phase 1 (Current - v2.0.0-alpha)

- **Status**: No deprecations
- **Action**: None required

### Phase 2 (Planned - v2.0.0-beta)

- **Deprecates**: Direct usage of `RequestLogger` methods
- **Alternative**: Use `LogWriter`, `LogReader`, `LogStatistics` services
- **Timeline**: Warnings in 2.0.0-beta, removal in 2.2.0
- **Status**: Complete - Phase 2 merged (PR #62)

### Phase 3 (Current - v2.0.0-rc)

- **Status**: Complete
- **New Classes**: `SubmissionController`, `SubmissionProcessor`
- **Deprecates**: Direct usage of `Integration` class methods (in future release)
- **Alternative**: Use `SubmissionController` (Controller layer) and `SubmissionProcessor` (Service layer)
- **Timeline**: New classes available now, deprecation warnings planned for 2.1.0, removal in 2.2.0
- **Impact**: Zero breaking changes - new classes coexist with old Integration.php

### Phase 4 (Current - v2.0.0-rc)

- **Status**: Complete - January 24, 2026
- **Changes**: Namespace reorganization for services
- **Impact**: Breaking changes for direct class usage
- **Alternative**: Use new namespaces for moved classes
- **Timeline**: Completed in PR #64

**Breaking Changes:**
```php
// OLD namespaces (no longer valid):
use SilverAssist\ContactFormToAPI\Core\EncryptionService;
use SilverAssist\ContactFormToAPI\Core\Settings;
use SilverAssist\ContactFormToAPI\Core\SensitiveDataPatterns;

// NEW namespaces (required):
use SilverAssist\ContactFormToAPI\Service\Security\EncryptionService;
use SilverAssist\ContactFormToAPI\Config\Settings;
use SilverAssist\ContactFormToAPI\Service\Security\SensitiveDataPatterns;
```

**Who is affected:**
- Developers who import these classes directly in custom code
- Extensions that extend or modify these classes
- No impact on WordPress admin users or CF7 form users

### Phase 5 (Current - v2.0.0)

- **Status**: Complete - January 24, 2026
- **Changes**: View splitting into partials
- **Impact**: Non-breaking (internal refactoring)
- **Alternative**: Use new partial classes for better maintainability
- **Timeline**: Completed in PR #XX (replace with actual PR number)

**Non-Breaking Changes:**
```php
// ✅ OLD (still works, deprecated):
use SilverAssist\ContactFormToAPI\Admin\Views\RequestLogView;
RequestLogView::render_statistics();
RequestLogView::render_filters();

// ✅ NEW (recommended):
use SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials\StatisticsPartial;
use SilverAssist\ContactFormToAPI\View\Admin\Logs\Partials\DateFilterPartial;

StatisticsPartial::render();
DateFilterPartial::render();
```

**Benefits:**
- 28% reduction in RequestLogView size (1,006 → 725 lines)
- Better code organization and maintainability
- Reusable UI components
- No breaking changes for existing code

**Who is affected:**
- Only developers extending view classes (uncommon)
- No impact on WordPress admin users or CF7 form users

---

- **Changes**: Namespace reorganization
- **Impact**: Autoloader updates, no code changes needed
- **Timeline**: Immediate with fallback support

---

## Rollback Procedure

If you need to rollback to 1.x:

1. **Deactivate Plugin**
   ```bash
   wp plugin deactivate contact-form-to-api
   ```

2. **Restore Old Version**
   - Download 1.x release from GitHub
   - Replace plugin files

3. **Restore Database** (if schema changed in later phases)
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

**GitHub Issues**: https://github.com/SilverAssist/contact-form-to-api/issues

---

## Testing Recommendations

### Manual Testing Checklist

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
# Run full test suite
composer test

# Check coding standards
composer phpcs

# Run static analysis
composer phpstan
```

---

## Migration Timeline

### Current Status: Phase 5 ✅

- ✅ **Phase 1**: Foundation architecture complete (PR #58)
- ✅ **Phase 2**: RequestLogger extracted into services (PR #62)
- ✅ **Phase 3**: Integration split into Controller/Service layers (PR #63)
- ✅ **Phase 4**: Service reorganization and namespace consolidation (PR #75)
- ✅ **Phase 5**: View splitting into maintainable partials (PR #75)
- ⏳ **Phase 6**: Final cleanup (TBD)

### Upcoming Phases

- **Phase 6**: Final cleanup and documentation
  - Remove any remaining deprecated code
  - Comprehensive testing and validation
  - Finalize 2.0.0 release notes

---

## FAQ

**Q: Do I need to update my form configurations?**  
A: No, all existing form configurations continue to work without changes.

**Q: Will my API logs be preserved?**  
A: Yes, all log data is preserved. Database schema remains unchanged in Phase 1.

**Q: Are custom hooks affected?**  
A: No, all existing hooks continue to work. New hooks may be added in future phases.

**Q: What about performance?**  
A: Phase 1 is purely architectural - no performance changes expected.

**Q: When should I upgrade?**  
A: Phase 1 is safe to upgrade immediately. It's purely additive with no breaking changes.

---

**Document Version**: 2.0.0  
**Last Updated**: January 24, 2026  
**Maintained By**: Silver Assist Development Team
