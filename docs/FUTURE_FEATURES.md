# Future Features Roadmap

**Document Version**: 1.0.0  
**Last Updated**: January 24, 2026  
**Status**: Planning / Ideas

This document outlines potential features for future versions of Contact Form 7 to API, based on competitive analysis (Flamingo) and user needs.

---

## Table of Contents

1. [High Priority](#high-priority)
2. [Medium Priority](#medium-priority)
3. [Low Priority](#low-priority)
4. [Competitive Analysis](#competitive-analysis)

---

## High Priority

### 1. GDPR Personal Data Eraser

**Problem**: WordPress provides privacy tools for GDPR compliance, but our plugin doesn't integrate with them.

**Solution**: Implement WordPress Privacy Tools integration.

**How WordPress Privacy Eraser Works**:

1. Admin enters user's email in Tools → Erase Personal Data
2. WordPress sends confirmation email to user
3. User confirms → WordPress calls all registered erasers with `callback($email_address, $page)`
4. Each eraser must find and delete data associated with that email

**Technical Challenge: Encryption Compatibility**

When encryption is enabled, the email is stored encrypted inside `request_data` JSON:

```
┌─────────────────────────────────────────────────────────────────┐
│ request_data (encrypted) = "eyJub25jZSI6IjEyM..."              │
│                                                                 │
│ After decrypt:                                                  │
│ {"your-name": "Miguel", "your-email": "miguel@example.com"}    │
└─────────────────────────────────────────────────────────────────┘

WordPress calls: eraser_callback("miguel@example.com", 1)
Problem: Cannot do SQL LIKE '%miguel@example.com%' on encrypted data!
```

**Recommended Solution**: Add `email_hash` column to logs table.

**Database Migration**:

```sql
ALTER TABLE {prefix}cf7_api_logs 
ADD COLUMN email_hash varchar(64) DEFAULT NULL,
ADD INDEX email_hash (email_hash);
```

**Implementation**:

```php
// 1. On form submission (LogWriter::start_request)
$email = $this->extract_email_from_data($request_data);
$email_hash = $email ? hash('sha256', strtolower(trim($email))) : null;
$insert_data['email_hash'] = $email_hash;

// 2. Privacy eraser callback
function cf7_api_privacy_eraser($email_address, $page = 1) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf7_api_logs';
    
    // Hash the email we're looking for
    $email_hash = hash('sha256', strtolower(trim($email_address)));
    
    // Fast indexed lookup and delete
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM %i WHERE email_hash = %s LIMIT 500",
        $table,
        $email_hash
    ));
    
    // Check if more remain
    $remaining = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM %i WHERE email_hash = %s",
        $table,
        $email_hash
    ));
    
    return [
        'items_removed'  => $deleted > 0,
        'items_retained' => false,
        'messages'       => [],
        'done'           => $remaining == 0,
    ];
}

// 3. Register the eraser
add_filter('wp_privacy_personal_data_erasers', function($erasers) {
    $erasers['cf7-api-logs'] = [
        'eraser_friendly_name' => __('CF7 to API Logs', 'contact-form-to-api'),
        'callback'             => 'cf7_api_privacy_eraser',
    ];
    return $erasers;
});
```

**Email Field Detection**:

CF7 forms can have different email field names. Add setting to configure:

```php
// Settings option
'email_fields' => ['your-email', 'email', 'correo', 'user-email'],

// Extract email from form data
function extract_email_from_data($data) {
    $email_fields = Settings::instance()->get('email_fields', ['your-email', 'email']);
    foreach ($email_fields as $field) {
        if (!empty($data[$field]) && is_email($data[$field])) {
            return $data[$field];
        }
    }
    return null;
}
```

**Migration for Existing Logs**:

Existing logs need their `email_hash` populated:

```php
// Background migration (run via cron or admin action)
function migrate_email_hashes_batch($batch_size = 100) {
    global $wpdb;
    $encryption = EncryptionService::instance();
    
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT id, request_data FROM %i 
         WHERE email_hash IS NULL LIMIT %d",
        $wpdb->prefix . 'cf7_api_logs',
        $batch_size
    ));
    
    foreach ($logs as $log) {
        $decrypted = $encryption->decrypt($log->request_data);
        $data = json_decode($decrypted, true);
        $email = extract_email_from_data($data);
        
        $hash = $email ? hash('sha256', strtolower(trim($email))) : '';
        $wpdb->update($table, ['email_hash' => $hash], ['id' => $log->id]);
    }
    
    return count($logs); // Return processed count
}
```

**Synergy with Contact Book Feature**:

This `email_hash` column benefits both features:

- **GDPR Eraser**: Fast lookup to delete user's logs
- **Contact Book**: Index for unique contacts table

**Benefits**:

- GDPR compliance out of the box
- Works with encrypted data (O(1) lookup via hash index)
- Users can request their data deletion
- Reduces legal liability for site owners
- Scales to millions of logs

**Reference**: Flamingo implements this in `admin/includes/privacy.php` (but without encryption support)

---

### 2. Contact Book / Address Book

**Problem**: No way to see unique contacts across all form submissions.

**Solution**: Extract unique emails from logs and create a contact directory.

**Features**:

- List of unique email addresses from all submissions
- Per-contact submission history
- Metrics: "This email has submitted 15 forms"
- Last contact date
- Export contacts to CSV

**Technical Challenge: Encryption Compatibility**

When encryption is enabled, email/name fields are stored encrypted inside `request_data` JSON. This prevents direct SQL queries like `SELECT DISTINCT email`.

**Recommended Solution**: Use SHA-256 hashes for indexing while keeping real data encrypted.

**Database Schema**:

```sql
CREATE TABLE {prefix}cf7_api_contacts (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    email_hash varchar(64) NOT NULL,           -- SHA-256 hash for lookups
    first_seen datetime NOT NULL,
    last_seen datetime NOT NULL,
    last_log_id bigint(20) UNSIGNED NOT NULL,  -- Reference to decrypt real data
    submission_count int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY email_hash (email_hash),        -- Enables efficient lookups
    KEY last_seen (last_seen),
    KEY last_log_id (last_log_id)
);
```

**How It Works**:

```php
// On form submission:
$email_hash = hash('sha256', strtolower(trim($email)));

// To display contact:
// 1. Find contact by email_hash
// 2. Get last_log_id
// 3. Decrypt request_data from that log
// 4. Extract and display real name/email
```

**Encryption Compatibility Matrix**:

| Operation | With Encryption | Without Encryption |
|-----------|-----------------|-------------------|
| Search by email | ✅ Via hash | ✅ Direct |
| Count submissions | ✅ Counter field | ✅ Counter field |
| Display email | ✅ Decrypt from log | ✅ Direct |
| Search by name | ⚠️ Requires name_hash | ✅ Direct |

**Admin UI**:

- New submenu: "Contacts" under main plugin menu
- List table with search/filter (searches by hash)
- Click contact to see all related logs (decrypts on display)

---

## Medium Priority

### 3. Form Filter Dropdown

**Problem**: Filtering by form works but isn't discoverable - users must click on a form name in the table or manually add `?form_id=X` to the URL.

**Solution**: Add a visible dropdown selector in the filters section.

**Current State** (already implemented):

- ✅ Filter by form works via `?form_id=X` URL parameter
- ✅ Stats update when filtering by form
- ✅ Export respects form filter
- ✅ Clicking form name in table applies filter
- ❌ **Missing**: Visible dropdown in filters section

**Implementation**:

Add a `<select>` dropdown in `DateFilterPartial.php`:

```php
// Get forms that have logs
$forms_with_logs = $wpdb->get_results(
    "SELECT DISTINCT l.form_id, p.post_title 
     FROM {$wpdb->prefix}cf7_api_logs l
     LEFT JOIN {$wpdb->posts} p ON l.form_id = p.ID
     ORDER BY p.post_title ASC"
);

// Render dropdown
<select name="form_id" id="form-filter">
    <option value=""><?php _e('All Forms', 'contact-form-to-api'); ?></option>
    <?php foreach ($forms_with_logs as $form): ?>
        <option value="<?php echo $form->form_id; ?>" <?php selected($current_form_id, $form->form_id); ?>>
            <?php echo esc_html($form->post_title ?: "Form #{$form->form_id}"); ?>
        </option>
    <?php endforeach; ?>
</select>
```

**UI Location**:

```
┌─────────────────────────────────────────────────────────────────┐
│ cf7-api-filters section:                                        │
│ ┌───────────────┐  ┌───────────────┐  ┌─────────────────────┐  │
│ │ Date Filter ▼ │  │ Form Filter ▼ │  │ 🔍 Search...        │  │
│ └───────────────┘  └───────────────┘  └─────────────────────┘  │
│                           │                                     │
│                           ├── All Forms                         │
│                           ├── Contact Form                      │
│                           ├── Support Request                   │
│                           └── Newsletter Signup                 │
└─────────────────────────────────────────────────────────────────┘
```

**Benefits**:

- Discoverable UI for form filtering
- Consistent with existing date filter UX
- No database changes required
- ~50 lines of code
- Stats grid automatically updates with filter

---

### 4. Granular Alert Preferences

**Problem**: Current `EmailAlertService` only alerts when error thresholds are exceeded. A single important lead could fail without triggering an alert if the threshold isn't met.

**Solution**: Extend the existing alert system with granular notification preferences.

**Current State** (already implemented):

- ✅ `EmailAlertService` monitors hourly error rates
- ✅ Alerts when `error_threshold` OR `rate_threshold` exceeded
- ✅ Configurable recipients via `alert_recipients`
- ✅ Cooldown period to prevent spam

**What's Missing**:

- ❌ Per-submission failure alerts (when a log exhausts all retries)
- ❌ User choice of which alert types to receive

**Implementation**:

Extend `EmailAlertService` with new alert type:

```php
// New setting: alert_types (array)
'alert_types' => [
    'threshold' => true,      // Existing: high error rate alerts
    'individual' => false,    // New: per-submission failure alerts
],

// In RetryService, after max retries exhausted:
public function mark_permanently_failed(int $log_id, int $form_id): void {
    $this->update_log_status($log_id, 'failed');
    
    // Trigger individual failure alert if enabled
    EmailAlertService::instance()->maybe_send_individual_alert($log_id, $form_id);
}

// New method in EmailAlertService:
public function maybe_send_individual_alert(int $log_id, int $form_id): void {
    $settings = Settings::instance();
    
    if (!$settings->is_alerts_enabled()) {
        return;
    }
    
    $alert_types = $settings->get('alert_types', []);
    if (empty($alert_types['individual'])) {
        return;
    }
    
    // No cooldown for individual alerts - they're event-driven
    $this->send_individual_failure_alert($log_id, $form_id);
}
```

**Settings UI Extension**:

```
┌─────────────────────────────────────────────────────────────────┐
│ Email Alerts                                                    │
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ ☑ Enable email alerts                                       ││
│ │                                                             ││
│ │ Alert recipients: [admin@example.com________________]       ││
│ │                                                             ││
│ │ Alert Types:                                                ││
│ │ ☑ High error rate (threshold-based)                        ││
│ │   └─ Trigger when: [5] errors OR [20]% error rate/hour     ││
│ │   └─ Cooldown: [4] hours between alerts                    ││
│ │                                                             ││
│ │ ☐ Individual submission failures                           ││
│ │   └─ Alert when a submission fails after all retries       ││
│ │   └─ ☐ Include form data in email (privacy consideration) ││
│ └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

**Benefits**:

- Extends existing service (no new classes needed)
- User controls granularity based on their needs
- Low-traffic sites can enable individual alerts
- High-traffic sites can stick to threshold-only
- Backward compatible (default: only threshold alerts)

**Effort**: ~100 lines to extend `EmailAlertService` + settings UI changes

---

### 5. Enhanced Bulk Actions

**Problem**: Limited batch operations on logs.

**Solution**: Improve existing bulk action UX and add export selection.

**Current Bulk Actions** (already implemented):

- ✅ Delete selection
- ✅ Retry failed logs

**Enhancements to Add**:

- **Export selection**: Export only checkbox-selected logs (currently exports all filtered results)
- **Select all matching filter**: Option to select all logs matching current filter, not just current page (with confirmation dialog for large selections)
- **Progress indicator**: Show progress for operations on many logs
- **Background processing**: Use AJAX for large batches to prevent timeouts

**Implementation Notes**:

```php
// "Select all matching filter" needs count + confirmation
$matching_count = $list_table->get_total_items();
if ($matching_count > 100) {
    // Show confirmation: "Apply action to all {$matching_count} matching logs?"
}
```

**Effort**: ~150 lines (mostly JS for progress UI)

---

### 6. Response Action Hook (Developer Extensibility)

**Problem**: Developers may need to act on API responses (store lead IDs, trigger notifications, log to external services) but there's no extension point.

**Solution**: Add the `cf7_api_after_response` filter hook that fires after each API response.

**Philosophy**: Keep the plugin focused on its core purpose (sending form data to APIs) while allowing developers to extend functionality without modifying plugin code.

**Implementation**:

Add ~20 lines in `SubmissionProcessor.php` after receiving response:

```php
$response_data = apply_filters('cf7_api_after_response', $response_data, $context);
```

**Full Documentation**: See [API Reference → cf7_api_after_response](API_REFERENCE.md#cf7_api_after_response) for:

- Complete parameter documentation
- Example use cases (CRM integration, Slack notifications, error logging)
- Code snippets

**Benefits**:

- Zero UI complexity
- Plugin stays focused on core purpose  
- Unlimited extensibility for developers
- No maintenance burden for edge-case features
- Follows WordPress hook conventions

**Effort**: ~20 lines of code (documentation already in API_REFERENCE.md)

---

## Low Priority

### 7. REST API Endpoints

**Problem**: No programmatic access to log data.

**Solution**: Expose logs via WordPress REST API.

**Endpoints**:

```
GET  /wp-json/cf7-api/v1/logs
GET  /wp-json/cf7-api/v1/logs/{id}
POST /wp-json/cf7-api/v1/logs/{id}/retry
GET  /wp-json/cf7-api/v1/stats
GET  /wp-json/cf7-api/v1/contacts
```

**Authentication**: Standard WordPress REST API authentication (nonce, application passwords, OAuth).

**Use Cases**:

- External dashboards
- Mobile app integration
- Custom reporting tools
- Automation workflows (Zapier, n8n)

---

### 8. Multi-Endpoint per Form

**Problem**: Can only send form data to one API.

**Solution**: Support multiple endpoints per form submission.

**Features**:

- Primary endpoint (required)
- Secondary endpoints (optional, array)
- Failover mode: If primary fails, try secondary
- Parallel mode: Send to all simultaneously
- Conditional: Send to endpoint B only if endpoint A returns X

**Configuration**:

```
Form Settings:
├── Endpoint 1: CRM API (primary)
├── Endpoint 2: Email Marketing API
├── Endpoint 3: Slack Notification
└── Mode: Parallel / Sequential / Failover
```

---

### 9. Field Transformations

**Problem**: Form data often needs transformation before API submission.

**Solution**: Built-in field transformation functions.

**Available Transformations**:

- Text: uppercase, lowercase, trim, truncate
- Date: format conversion (d/m/Y → Y-m-d)
- Phone: normalization (+1-555-123-4567 → 15551234567)
- Email: lowercase, validate
- Custom: PHP callback (advanced users)
- Computed: Concatenate fields, math operations

**UI**:

```
Field: [your-phone]
├── API Field: phone_number
├── Transform: Phone Normalize
└── Format: E.164 (no spaces, with country code)
```

---

### 10. Import/Export Configuration

**Problem**: Difficult to migrate settings between environments.

**Solution**: Full configuration import/export.

**Exportable Data**:

- Global plugin settings
- Per-form API configurations

**Format**: JSON or encrypted JSON (for sensitive data like API keys).

**Use Cases**:

- Staging → Production migration
- Backup before updates
- Share configurations between sites

---

### 11. Advanced Statistics & Reporting

**Problem**: Current statistics are basic.

**Solution**: Comprehensive analytics dashboard.

**Features**:

- Response time trends (graphs)
- Error rate by endpoint
- Peak submission hours/days
- API availability monitoring
- Custom date range reports
- Export reports as PDF

**Metrics**:

- Average response time
- 95th percentile response time
- Success rate by form
- Most common error codes
- Submissions per hour/day/week

---

---

## Competitive Analysis

### CF7 to API vs Flamingo

| Feature | CF7 to API | Flamingo |
|---------|------------|----------|
| **Core Purpose** | API Integration | Message Storage |
| **API Calls** | ✅ Core feature | ❌ Not supported |
| **Retry Failed** | ✅ Automatic | ❌ N/A |
| **Encryption** | ✅ libsodium | ❌ Plaintext |
| **Response Logging** | ✅ Full request/response | ❌ N/A |
| **Contact Book** | ❌ Not yet | ✅ Address Book |
| **GDPR Eraser** | ❌ Not yet | ✅ Integrated |
| **Spam Detection** | N/A (CF7 handles) | ✅ Akismet |
| **Auto Cleanup** | ✅ Cron + retention | ✅ Cron jobs |
| **Form Filter UI** | ❌ Not yet (URL only) | ✅ Dropdown |
| **CSV Export** | ✅ Yes | ✅ Yes |
| **Modern Architecture** | ✅ PSR-4, PHP 8.2 | ❌ Procedural |
| **Test Suite** | ✅ PHPUnit | ❌ None |
| **Code Quality** | ✅ PHPCS + PHPStan | ❌ Basic |

### Key Takeaways

1. **Flamingo excels at**: Contact management, GDPR compliance, message storage
2. **CF7 to API excels at**: API integration, retry logic, security, code quality
3. **Spam handling**: Delegated to CF7 (Akismet, reCAPTCHA) - not our responsibility
4. **Opportunity**: Combine best of both worlds while maintaining our technical superiority

---

## Contributing

If you'd like to contribute to any of these features:

1. Check if there's an existing issue for the feature
2. Create a new issue if not, referencing this document
3. Discuss implementation approach before coding
4. Follow [SilverAssist Development Standards](https://gist.github.com/miguelcolmenares/227180b8983df6ad4ec3ced113677853)

---

**Document Maintainer**: Silver Assist Team  
**Feedback**: Open an issue on GitHub with the label `feature-request`
