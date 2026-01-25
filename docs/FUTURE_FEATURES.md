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

**Problem**: Currently, filtering by form requires clicking on a form name in the table, which isn't discoverable.

**Solution**: Add a dropdown selector in the filters section to filter logs by form.

**Current State**:
- ✅ Filter by form works via `?form_id=X` URL parameter
- ✅ Stats update when filtering by form
- ❌ No UI element to select form (must click form name in table)

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

### 4. Webhook Notifications

**Problem**: Currently only sends to one API endpoint per form.

**Solution**: Support multiple notification channels.

**Supported Channels**:
- Slack webhooks
- Discord webhooks
- Microsoft Teams
- Custom webhooks
- Enhanced email notifications

**Configuration**:
```
Per-form settings:
├── Primary API endpoint (existing)
├── Slack webhook URL (optional)
├── Discord webhook URL (optional)
└── Custom webhooks (array)
```

**Notification Triggers**:
- On successful submission
- On failed submission (after all retries)
- On specific response codes

---

### 5. Field Mapping Templates

**Problem**: Manual field mapping for each form is tedious.

**Solution**: Save and reuse mapping configurations.

**Features**:
- Save current mapping as template
- Apply template to new forms
- Import/export templates
- Default template for new forms

**Template Structure**:
```json
{
    "name": "CRM Integration",
    "mappings": {
        "[your-name]": "customer_name",
        "[your-email]": "email_address",
        "[your-phone]": "phone_number"
    },
    "headers": {
        "Content-Type": "application/json"
    }
}
```

---

### 6. Enhanced Bulk Actions

**Problem**: Limited batch operations on logs.

**Solution**: Expand bulk action capabilities.

**Current Bulk Actions** (already implemented):
- ✅ Delete selection
- ✅ Retry failed logs

**New Actions to Add**:
- Export selection (CSV/JSON) - Currently exports filtered results, not checkbox selection
- Re-send to different endpoint
- Mark as read/unread

**UI Improvements**:
- Select all matching filter (not just current page)
- Progress indicator for long operations
- Background processing for large batches

---

### 7. Response Parsing & Conditional Actions

**Problem**: No way to act on API response content.

**Solution**: Parse responses and trigger conditional actions.

**Features**:
```
IF response.status == "duplicate" THEN:
  - Skip confirmation email
  - Add tag "duplicate"

IF response.lead_id EXISTS THEN:
  - Store lead_id in post meta
  - Log as "synced"

IF response.error CONTAINS "rate_limit" THEN:
  - Delay retry by 1 hour
  - Send admin notification
```

**Configuration UI**:
- Response field path (JSONPath-like)
- Condition (equals, contains, exists, regex)
- Action to perform

---

## Low Priority

### 8. REST API Endpoints

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

### 9. Multi-Endpoint per Form

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

### 10. Field Transformations

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

### 11. Import/Export Configuration

**Problem**: Difficult to migrate settings between environments.

**Solution**: Full configuration import/export.

**Exportable Data**:
- Global plugin settings
- Per-form API configurations
- Field mapping templates
- Webhook configurations

**Format**: JSON or encrypted JSON (for sensitive data like API keys).

**Use Cases**:
- Staging → Production migration
- Backup before updates
- Share configurations between sites

---

### 12. Advanced Statistics & Reporting

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
