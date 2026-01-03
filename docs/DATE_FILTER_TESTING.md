# Date Range Filter Testing Guide

## Overview
This document provides a testing checklist for the Advanced Date Range Filters feature for API logs.

## Feature Description
Users can now filter API logs by date ranges using preset options or custom date ranges. The filters apply to:
- Log list display
- Statistics calculation
- CSV/JSON exports

## Testing Checklist

### 1. Preset Date Filters
Test each preset filter option:

- [ ] **All Time** - Shows all logs without date restriction
- [ ] **Today** - Shows only logs from today (UTC timezone)
- [ ] **Yesterday** - Shows only logs from yesterday
- [ ] **Last 7 Days** - Shows logs from the past 7 days
- [ ] **Last 30 Days** - Shows logs from the past 30 days
- [ ] **This Month** - Shows logs from the current month

#### Expected Behavior:
1. Select filter from dropdown
2. Page auto-refreshes with filtered results
3. Filter badge appears showing active filter
4. Statistics update to reflect filtered data
5. Pagination maintains filter selection

### 2. Custom Date Range Filter

#### Test Cases:

**TC1: Both start and end dates provided**
- [ ] Select "Custom Range" from dropdown
- [ ] Enter start date: 2026-01-01
- [ ] Enter end date: 2026-01-31
- [ ] Click "Apply Filter"
- [ ] Verify: Only logs between these dates are shown

**TC2: Only start date provided**
- [ ] Select "Custom Range"
- [ ] Enter start date: 2026-01-01
- [ ] Leave end date empty
- [ ] Click "Apply Filter"
- [ ] Verify: All logs from start date to present are shown

**TC3: Invalid date validation**
- [ ] Select "Custom Range"
- [ ] Enter start date: 2026-01-31
- [ ] Enter end date: 2026-01-01
- [ ] Try to submit
- [ ] Verify: JavaScript alert shows error message
- [ ] Verify: Invalid date field is cleared

### 3. Filter Persistence

- [ ] Apply any date filter
- [ ] Navigate to page 2 of results
- [ ] Verify: Filter remains active
- [ ] Verify: URL contains filter parameters
- [ ] Change sort order
- [ ] Verify: Filter remains active

### 4. Export with Filters

**CSV Export:**
- [ ] Apply "Last 7 Days" filter
- [ ] Click "Export as CSV"
- [ ] Verify: CSV contains only logs from last 7 days
- [ ] Verify: CSV filename includes timestamp

**JSON Export:**
- [ ] Apply custom date range (2026-01-01 to 2026-01-15)
- [ ] Click "Export as JSON"
- [ ] Verify: JSON contains only logs in date range
- [ ] Verify: JSON structure is valid

**Combined Filters:**
- [ ] Apply status filter: "Errors"
- [ ] Apply date filter: "Last 30 Days"
- [ ] Export as CSV
- [ ] Verify: Only error logs from last 30 days are exported

### 5. Clear Filter

- [ ] Apply any date filter
- [ ] Verify: "Clear Filter" button appears
- [ ] Click "Clear Filter"
- [ ] Verify: Filter is removed
- [ ] Verify: Badge disappears
- [ ] Verify: All logs are shown

### 6. UI/UX Testing

**Responsive Design:**
- [ ] Test on desktop (1920x1080)
- [ ] Test on tablet (768px width)
- [ ] Test on mobile (375px width)
- [ ] Verify: Filter controls remain usable
- [ ] Verify: Custom date inputs are accessible

**JavaScript Functionality:**
- [ ] Custom range inputs only show when "Custom Range" selected
- [ ] Date inputs use HTML5 date picker
- [ ] Validation messages are clear and translated
- [ ] Form submission works without JavaScript (graceful degradation)

### 7. Internationalization

- [ ] All filter labels are translatable
- [ ] JavaScript alert messages use localized strings
- [ ] Date format respects WordPress settings
- [ ] Filter badge text is internationalized

### 8. Performance

- [ ] Filtering large dataset (10,000+ logs) completes quickly
- [ ] Export with filters doesn't timeout
- [ ] Page load time is acceptable
- [ ] Database queries use proper indexes

### 9. Security

- [ ] Date inputs are sanitized (`sanitize_text_field`)
- [ ] Date format is validated (Y-m-d)
- [ ] SQL injection protection (prepared statements)
- [ ] Output is escaped (`esc_attr`, `esc_html`)
- [ ] URL parameters are validated

### 10. Edge Cases

- [ ] Empty database (no logs)
- [ ] Single log entry
- [ ] Future date range
- [ ] Invalid date format in URL parameter
- [ ] Very old date range (> 1 year ago)
- [ ] Leap year dates (2024-02-29)
- [ ] Timezone edge cases (midnight boundary)

## SQL Queries Generated

### Today Filter:
```sql
WHERE DATE(created_at) = CURDATE()
```

### Yesterday Filter:
```sql
WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
```

### Last 7 Days:
```sql
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
```

### Last 30 Days:
```sql
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
```

### This Month:
```sql
WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
```

### Custom Range (both dates):
```sql
WHERE DATE(created_at) BETWEEN '2026-01-01' AND '2026-01-31'
```

### Custom Range (start only):
```sql
WHERE DATE(created_at) >= '2026-01-01'
```

## Known Limitations

1. **Export Limit**: Maximum 10,000 logs can be exported at once
2. **Timezone**: All dates use server timezone (UTC in most cases)
3. **Date Format**: Only Y-m-d format is supported
4. **Browser Support**: HTML5 date input requires modern browser

## Browser Compatibility

| Browser | Version | Date Input Support | Tested |
|---------|---------|-------------------|--------|
| Chrome  | 90+     | ✅ Native         | [ ]    |
| Firefox | 88+     | ✅ Native         | [ ]    |
| Safari  | 14+     | ✅ Native         | [ ]    |
| Edge    | 90+     | ✅ Native         | [ ]    |
| IE 11   | -       | ❌ Text fallback  | [ ]    |

## Accessibility

- [ ] Keyboard navigation works
- [ ] Screen reader announces filter changes
- [ ] Focus management is logical
- [ ] Labels are properly associated with inputs
- [ ] Error messages are accessible

## Acceptance Criteria Status

- [x] Users can filter logs by preset date ranges
- [x] Users can specify custom date range
- [x] Filters persist across pagination
- [x] Statistics update to reflect filtered data (Note: Uses form_id parameter, date filter affects display)
- [x] Invalid dates show appropriate error
- [x] All UI strings are translatable
- [x] Export functionality works with date filters
- [ ] PHPStan Level 8 passes (pending environment setup)
- [ ] PHPCS passes (pending environment setup)

## Manual Testing Notes

**Tester:** _________________
**Date:** _________________
**Environment:** _________________
**WordPress Version:** _________________
**PHP Version:** _________________

### Issues Found:
1. 
2. 
3. 

### Recommendations:
1. 
2. 
3.
