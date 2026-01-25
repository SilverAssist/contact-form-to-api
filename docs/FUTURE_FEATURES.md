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

**Implementation**:
```php
// Hook: wp_privacy_personal_data_erasers
// Allows deletion of logs for a specific email from Tools > Erase Personal Data
add_filter('wp_privacy_personal_data_erasers', function($erasers) {
    $erasers['cf7-api-logs'] = [
        'eraser_friendly_name' => __('CF7 to API Logs', 'contact-form-to-api'),
        'callback' => 'cf7_api_privacy_eraser',
    ];
    return $erasers;
});
```

**Benefits**:
- GDPR compliance out of the box
- Users can request their data deletion
- Reduces legal liability for site owners

**Reference**: Flamingo implements this in `admin/includes/privacy.php`

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

### 3. Spam Detection Integration

**Problem**: No spam filtering before sending to external APIs.

**Solution**: Integrate with Akismet (optional) and add spam management.

**Features**:
- Check submission against Akismet before API call
- Mark logs as spam/not-spam
- Filter logs by spam status
- Option to skip API call for detected spam
- Spam log cleanup (separate from regular cleanup)

**Settings**:
- Enable Akismet integration (if Akismet is active)
- Action on spam: "Log only" / "Skip API call" / "Send anyway"

---

## Medium Priority

### 4. Channels / Categories for Logs

**Problem**: Hard to organize and filter logs when managing multiple forms.

**Solution**: Add taxonomy-like organization for logs.

**Features**:
- Auto-assign channel based on form ID
- Custom tags for logs
- Filter by channel/tag in admin
- Separate statistics per channel

**Use Cases**:
- "Contact Form" vs "Support Request" vs "Newsletter Signup"
- Group by endpoint URL
- Custom business categories

---

### 5. Webhook Notifications

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

### 6. Field Mapping Templates

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

### 7. Enhanced Bulk Actions

**Problem**: Limited batch operations on logs.

**Solution**: Expand bulk action capabilities.

**Current Bulk Actions** (already implemented):
- ✅ Delete selection
- ✅ Retry failed logs

**New Actions to Add**:
- Export selection (CSV/JSON) - Currently exports filtered results, not checkbox selection
- Re-send to different endpoint
- Mark as read/unread
- Add/remove tags (requires Channels feature #4)

**UI Improvements**:
- Select all matching filter (not just current page)
- Progress indicator for long operations
- Background processing for large batches

---

### 8. Response Parsing & Conditional Actions

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

### 9. REST API Endpoints

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

### 10. Multi-Endpoint per Form

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

### 11. Field Transformations

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

### 12. Import/Export Configuration

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

### 13. Advanced Statistics & Reporting

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
| **Spam Detection** | ❌ Not yet | ✅ Akismet |
| **Auto Cleanup** | ✅ Cron + retention | ✅ Cron jobs |
| **Channels/Tags** | ❌ Not yet | ✅ Taxonomies |
| **CSV Export** | ✅ Yes | ✅ Yes |
| **Modern Architecture** | ✅ PSR-4, PHP 8.2 | ❌ Procedural |
| **Test Suite** | ✅ PHPUnit | ❌ None |
| **Code Quality** | ✅ PHPCS + PHPStan | ❌ Basic |

### Key Takeaways

1. **Flamingo excels at**: Contact management, spam handling, GDPR compliance
2. **CF7 to API excels at**: API integration, retry logic, security, code quality
3. **Opportunity**: Combine best of both worlds while maintaining our technical superiority

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
