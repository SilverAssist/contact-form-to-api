# Advanced Date Range Filters - Feature Overview

## What's New? 🎉

The API Logs page now includes powerful date range filtering capabilities, making it easy to find logs from specific time periods.

## Quick Start

### Using Preset Filters

1. Navigate to **Contact > API Logs**
2. Look for the "Date Filter" dropdown near the top of the page
3. Select from preset options:
   - **Today** - Today's logs only
   - **Yesterday** - Yesterday's logs
   - **Last 7 Days** - Past week
   - **Last 30 Days** - Past month
   - **This Month** - Current calendar month
4. The page automatically refreshes with filtered results

### Using Custom Date Range

1. Select **"Custom Range"** from the Date Filter dropdown
2. The custom date inputs will appear
3. Enter a start date (required)
4. Optionally enter an end date
5. Click **"Apply Filter"**

### Clearing Filters

- Click the **"Clear Filter"** button to remove the date filter
- The filter badge disappears and all logs are shown

## Features

### Smart Validation ✅
- Start date must be before or equal to end date
- Invalid dates show helpful error messages
- Date format: YYYY-MM-DD (handled automatically by date picker)

### Filter Persistence 🔄
- Date filters remain active when:
  - Navigating between pages
  - Sorting columns
  - Searching logs
  - Changing other filters

### Export Integration 📥
- CSV and JSON exports respect active date filters
- Export only the logs you see on screen
- Combines with status, form ID, and search filters

### Visual Feedback 👁️
- Active filter badge shows current selection
- Statistics update to reflect filtered timeframe
- Clear indication of filtered state

## Example Use Cases

### Debugging Recent Issues
1. Select "Last 7 Days" filter
2. Filter by status: "Errors"
3. Export filtered results for analysis

### Monthly Reports
1. Select "This Month" filter
2. View success rate statistics
3. Export as CSV for reporting

### Investigating Specific Date
1. Select "Custom Range"
2. Set both start and end to same date
3. View all logs from that specific day

### Compliance Auditing
1. Select "Custom Range"
2. Enter compliance period dates
3. Export complete filtered dataset

## Technical Details

### Date Queries
All date filters use efficient MySQL date functions:
- Preset filters use `CURDATE()`, `DATE_SUB()`, etc.
- Custom ranges use `BETWEEN` for optimal performance
- Indexed `created_at` column ensures fast queries

### Browser Compatibility
- Modern browsers: Native HTML5 date picker
- Older browsers: Text input with format validation
- Mobile-friendly responsive design

### Security
- All inputs sanitized and validated
- SQL injection protection via prepared statements
- XSS protection with output escaping

## Tips & Tricks 💡

1. **Quick Today View**: Select "Today" for instant daily monitoring
2. **Week Retrospective**: Use "Last 7 Days" for weekly reviews
3. **Date Range Shortcuts**: Leave end date empty to filter "from date to now"
4. **Combined Filters**: Stack date filters with form/status filters for precise results
5. **Export Filtered Data**: Always apply filters before exporting for accurate datasets

## Keyboard Shortcuts

- **Tab**: Navigate between filter controls
- **Enter**: Submit filter form (in custom date inputs)
- **Escape**: Close date picker (browser native)

## Mobile Experience

The date filter is fully responsive:
- Stacked layout on mobile devices
- Touch-friendly date picker
- Optimized button sizes
- Readable filter badge

## Troubleshooting

**Q: Date filter not showing?**
- Ensure you're on the API Logs page (Contact > API Logs)
- Check that JavaScript is enabled

**Q: Statistics not updating?**
- Statistics reflect total logs, not filtered subset
- Filter badge shows active selection

**Q: Export includes unfiltered data?**
- Verify filter badge is visible before exporting
- Check that date parameters are in the export URL

**Q: Custom date validation error?**
- Ensure start date is before or equal to end date
- Use YYYY-MM-DD format (automatic with date picker)

## Feedback & Support

Found an issue or have a suggestion? Please report it on our GitHub repository.

---

**Version**: 1.1.3+
**Last Updated**: 2026-01-03
**Compatibility**: WordPress 6.5+, PHP 8.2+
