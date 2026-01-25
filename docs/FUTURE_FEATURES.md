# Future Features Roadmap

**Document Version**: 2.0.0  
**Last Updated**: January 25, 2026  
**Status**: Planning / Ideas

This document outlines potential features for future versions of Contact Form 7 to API, focused on its core purpose: **sending form data to APIs reliably**.

> **Design Philosophy**: Keep the plugin focused. Features that don't directly support the core mission of "Send Forms to API" are out of scope.

---

## Table of Contents

1. [Planned Features](#planned-features) (5 features)
2. [Competitive Analysis](#competitive-analysis)
3. [Out of Scope](#out-of-scope)

---

## Planned Features

### 1. Form Filter Dropdown (UX)

**Priority**: High | **Effort**: ~50 lines

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

### 2. Granular Alert Preferences (Monitoring)

**Priority**: Medium | **Effort**: ~100 lines

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

### 3. Enhanced Bulk Actions (UX)

**Priority**: Low | **Effort**: ~150 lines

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

### 4. Response Action Hook (Extensibility)

**Priority**: High | **Effort**: ~20 lines

**Problem**: Developers may need to act on API responses (store lead IDs, trigger notifications, log to external services) but there's no extension point.

**Solution**: Add the `cf7_api_after_response` filter hook that fires after each API response.

**Philosophy**: Keep the plugin focused on its core purpose (sending form data to APIs) while allowing developers to extend functionality without modifying plugin code.

**The Hook**:

```php
/**
 * Fires after an API response is received, allowing custom actions.
 *
 * @since 2.1.0
 *
 * @param array $response {
 *     API response data.
 *
 *     @type int    $status_code HTTP status code (200, 400, 500, etc.)
 *     @type array  $headers     Response headers as key-value pairs.
 *     @type string $body        Raw response body.
 *     @type array  $body_parsed Parsed response (if JSON), null otherwise.
 *     @type float  $duration    Request duration in seconds.
 * }
 * @param array $context {
 *     Submission context.
 *
 *     @type int    $log_id      The log entry ID.
 *     @type int    $form_id     The CF7 form ID.
 *     @type string $form_title  The CF7 form title.
 *     @type array  $form_data   Original form submission data.
 *     @type string $endpoint    The API endpoint URL.
 *     @type bool   $is_retry    Whether this was a retry attempt.
 *     @type int    $attempt     Attempt number (1 = first try).
 * }
 * @return array Modified response array (or original if no changes).
 */
$response = apply_filters('cf7_api_after_response', $response, $context);
```

**Example Use Cases**:

```php
// Example 1: Store CRM lead ID in a custom table
add_filter('cf7_api_after_response', function($response, $context) {
    if ($response['status_code'] !== 200) {
        return $response;
    }
    
    $body = $response['body_parsed'];
    if (!empty($body['lead_id'])) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'my_leads', [
            'log_id'  => $context['log_id'],
            'lead_id' => $body['lead_id'],
            'email'   => $context['form_data']['your-email'] ?? '',
        ]);
    }
    
    return $response;
}, 10, 2);

// Example 2: Send Slack notification on specific response
add_filter('cf7_api_after_response', function($response, $context) {
    $body = $response['body_parsed'];
    
    if (!empty($body['priority']) && $body['priority'] === 'high') {
        wp_remote_post('https://hooks.slack.com/...', [
            'body' => json_encode([
                'text' => "🚨 High priority lead from {$context['form_title']}!"
            ])
        ]);
    }
    
    return $response;
}, 10, 2);

// Example 3: Log errors to external service
add_filter('cf7_api_after_response', function($response, $context) {
    if ($response['status_code'] >= 400) {
        error_log(sprintf(
            '[CF7-API] Error %d on form %s: %s',
            $response['status_code'],
            $context['form_title'],
            $response['body']
        ));
    }
    
    return $response;
}, 10, 2);
```

**Implementation Location**:

In `SubmissionProcessor.php` after receiving response (~20 lines).

**Documentation**: When implemented, add full hook documentation to [API_REFERENCE.md](API_REFERENCE.md#filter-hooks) under "Filter Hooks" section.

**Benefits**:

- Zero UI complexity
- Plugin stays focused on core purpose
- Unlimited extensibility for developers
- No maintenance burden for edge-case features
- Follows WordPress hook conventions

**Effort**: ~20 lines of code + API_REFERENCE.md documentation

---

### 5. Import/Export Configuration (DevOps)

**Priority**: Low | **Effort**: ~100 lines

**Problem**: Difficult to migrate plugin settings between environments (staging → production).

**Solution**: Export/import global plugin settings as JSON.

**Scope** (intentionally limited):

- ✅ Global plugin settings (retention, alerts, encryption toggle, etc.)
- ❌ Per-form API configurations (form IDs differ between environments)
- ❌ Logs or contacts data

**Implementation**:

```php
// Export
$settings = get_option('cf7_api_settings', []);
$export = json_encode($settings, JSON_PRETTY_PRINT);

// Import (with validation)
$import = json_decode($json, true);
if ($this->validate_settings($import)) {
    update_option('cf7_api_settings', $import);
}
```

**UI**:

Settings page → "Backup & Restore" section:
- **Export**: Download button → `cf7-api-settings-2026-01-25.json`
- **Import**: File upload with validation feedback

**Security Considerations**:

- Sensitive data (like API keys) are per-form, not in global settings
- No encryption needed for export file
- Validate JSON structure before import

**Use Cases**:

- Backup settings before plugin updates
- Replicate configuration across multiple sites
- Agency workflow: configure once, deploy to clients

---

## Out of Scope

The following features were considered but intentionally excluded to keep the plugin focused on its core purpose: **sending form data to APIs**.

| Feature | Reason | Alternative |
|---------|--------|-------------|
| **GDPR Personal Data Eraser** | Compliance feature, not API-related | Use log retention policy (auto-delete after X days) |
| **Contact Book / Address Book** | Data visualization, not API-related | Contacts should live in your CRM (where we send data) |
| **REST API Endpoints** | Exposes logs, not part of sending to APIs | Use `cf7_api_after_response` hook for integrations |
| **Multi-Endpoint per Form** | Complex UI, edge case | Use `cf7_api_after_response` hook to send to secondary endpoints |
| **Field Transformations UI** | Complex UI for niche use cases | Use `cf7_api_set_record_value` filter hook |
| **Advanced Statistics** | Analytics beyond monitoring | Export CSV and use Excel/Google Sheets |
| **Spam Detection** | CF7's responsibility | Use CF7 + Akismet or reCAPTCHA |

> **Philosophy**: If a feature doesn't directly help "send form data to APIs reliably", it's out of scope. For extensibility, we provide hooks instead of bloated features.

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
| **Contact Book** | ❌ Out of scope | ✅ Address Book |
| **GDPR Eraser** | ❌ Out of scope | ✅ Integrated |
| **Spam Detection** | N/A (CF7 handles) | ✅ Akismet |
| **Auto Cleanup** | ✅ Cron + retention | ✅ Cron jobs |
| **Form Filter UI** | 🔜 Planned | ✅ Dropdown |
| **CSV Export** | ✅ Yes | ✅ Yes |
| **Modern Architecture** | ✅ PSR-4, PHP 8.2 | ❌ Procedural |
| **Test Suite** | ✅ PHPUnit | ❌ None |
| **Code Quality** | ✅ PHPCS + PHPStan | ❌ Basic |

### Key Takeaways

1. **Different purposes**: Flamingo stores messages locally; we send them to external APIs
2. **CF7 to API excels at**: API integration, retry logic, security, code quality
3. **Intentionally out of scope**: Contact Book, GDPR Eraser (use retention policy instead)
4. **Philosophy**: Stay focused on core purpose, extend via hooks not features

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
