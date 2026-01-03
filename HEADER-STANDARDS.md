# Contact Form 7 to API - Header Standards

This document defines the header formatting standards for the Contact Form 7 to API WordPress plugin. All files in this project must follow these exact formatting guidelines to maintain consistency and professional code documentation.

## Overview

All PHP, CSS, JavaScript, and other code files must include standardized headers with project information. This ensures consistent documentation, proper attribution, and version tracking across the entire codebase.

## Version Management

**Important**: Version numbers in this document are managed automatically by the `update-version-simple.sh` script. Never manually edit version numbers in this file - they will be overwritten during the next version update.

Current project version: **1.1.2**

## File Header Standards

### Main Plugin File Header

The main plugin file (`contact-form-to-api.php`) must include both the WordPress plugin header AND the standard file header:

```php
<?php
/**
 * Contact Form 7 to API
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: Contact Form 7 to API
 * Plugin URI: https://github.com/SilverAssist/contact-form-to-api
 * Description: Integrate Contact Form 7 with external APIs. Send form submissions to custom API endpoints with advanced configuration options, field mapping, and error handling.
 * Version: 1.1.2
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Silver Assist
 * Author URI: https://silverassist.com
 * License: Polyform-Noncommercial-1.0.0
 * License URI: https://polyformproject.org/licenses/1.0.0/
 * Text Domain: contact-form-to-api
 * Domain Path: /languages
 * Requires Plugins: contact-form-7
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}
```

### PHP Class Files Header

All PHP files containing classes must use this header format:

```php
<?php
/**
 * Contact Form 7 to API - [Component Name]
 *
 * [Brief description of the file's purpose]
 *
 * @package ContactFormToAPI
 * @subpackage [Subpackage if applicable]
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}
```

**Examples:**

```php
<?php
/**
 * Contact Form 7 to API - API Manager
 *
 * Handles API endpoint configuration and communication
 *
 * @package ContactFormToAPI
 * @subpackage API
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

```php
<?php
/**
 * Contact Form 7 to API - Admin Panel
 *
 * Manages the WordPress admin interface and settings
 *
 * @package ContactFormToAPI
 * @subpackage Admin
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

### CSS Files Header

All CSS files must include this header:

```css
/**
 * Contact Form 7 to API - [Stylesheet Name]
 *
 * [Brief description of the stylesheet's purpose]
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

**Examples:**

```css
/**
 * Contact Form 7 to API - Admin Styles
 *
 * Styles for the WordPress admin interface and settings pages
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

```css
/**
 * Contact Form 7 to API - Frontend Styles
 *
 * Frontend styles for form integration indicators and feedback
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

### JavaScript Files Header

All JavaScript files must include this header:

```javascript
/**
 * Contact Form 7 to API - [Script Name]
 *
 * [Brief description of the script's purpose]
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

**Examples:**

```javascript
/**
 * Contact Form 7 to API - Admin Scripts
 *
 * JavaScript functionality for the admin interface and configuration
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

```javascript
/**
 * Contact Form 7 to API - API Testing
 *
 * Frontend scripts for testing API integrations and endpoints
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */
```

### Bash Script Files Header

All shell scripts must include this header:

```bash
#!/bin/bash

###############################################################################
# Contact Form 7 to API - [Script Name]
#
# [Brief description of the script's purpose]
#
# Usage: ./[script-name].sh [options]
#
# @package ContactFormToAPI
# @since 1.0.0
# @author Silver Assist
# @version 1.1.2
###############################################################################
```

**Examples:**

```bash
#!/bin/bash

###############################################################################
# Contact Form 7 to API - Version Update Script
#
# Updates version numbers across all plugin files
#
# Usage: ./update-version.sh <new-version>
#
# @package ContactFormToAPI
# @since 1.0.0
# @author Silver Assist
# @version 1.1.2
###############################################################################
```

```bash
#!/bin/bash

###############################################################################
# Contact Form 7 to API - Build Release Script
#
# Creates a production-ready plugin release package
#
# Usage: ./build-release.sh [version]
#
# @package ContactFormToAPI
# @since 1.0.0
# @author Silver Assist
# @version 1.1.2
###############################################################################
```

## Header Field Definitions

### Required Fields

All files must include these fields:

- **@package**: Always \"ContactFormToAPI\"
- **@since**: Version when the file was first introduced
- **@author**: Always \"Silver Assist\"
- **@version**: Current plugin version (managed automatically)

### Optional Fields

- **@subpackage**: Used for organizing related files (Admin, API, Core, etc.)
- **@license**: Polyform-Noncommercial-1.0.0 for all files
- **@copyright**: Can be added for specific files if needed

### Version Tags Usage

**Critical Information for v1.0.0**: 	.0.1` for all new files.

- **@since**: Indicates when the file or feature was first introduced. This should NEVER change.
- **@version**: Current version of the file, updated with each release that modifies the file.

**Example for new files in v1.0.0:**
```php
 * @since 1.0.0      <- Never changes (when file was created)
 * @version 1.1.2    <- Updates with releases that modify this file
```

**Example for files modified in future versions:**
```php
 * @since 1.0.0      <- Still shows original introduction version
 * @version 1.1.2    <- Updated to show last modification version
```

## Standard Project Information

### Contact and Attribution
- **Author**: Silver Assist
- **Plugin Name**: Contact Form 7 to API
- **Package Name**: ContactFormToAPI
- **Text Domain**: contact-form-to-api
- **License**: Polyform-Noncommercial-1.0.0

### URLs and Links
- **Plugin URI**: https://github.com/SilverAssist/contact-form-to-api
- **Author URI**: https://silverassist.com
- **License URI**: https://polyformproject.org/licenses/noncommercial/1.0.0/

## File Organization by Package

### Core Components (`@subpackage Core`)
- Plugin initialization and core functionality
- Configuration management
- Database interactions

### Admin Interface (`@subpackage Admin`)
- WordPress admin panel integration
- Settings pages and forms
- User interface components

### API Integration (`@subpackage API`)
- API endpoint management
- Request/response handling
- Authentication mechanisms

### Contact Form Integration (`@subpackage ContactForm`)
- Contact Form 7 hooks and filters
- Form field mapping
- Submission processing

## Version Update Process

**Important**: Never manually edit @version tags. The plugin uses automated version management:

1. **Automatic Updates**: Version numbers are updated via `./scripts/update-version-simple.sh`
2. **Consistency Check**: Use `./scripts/check-versions.sh` to verify all files have consistent versions
3. **Release Process**: All version updates are part of the formal release process

## Quality Assurance

### Header Validation
All headers must:
- Include all required fields
- Use consistent formatting
- Have accurate package information
- Include appropriate descriptions

### Automated Checks
The build process includes automated validation of:
- Header presence in all code files
- Consistent version numbering
- Proper package attribution
- License information accuracy

## Examples by File Type

### Complete PHP Class Example

```php
<?php
/**
 * Contact Form 7 to API - API Endpoint Manager
 *
 * Manages API endpoint configurations, handles requests and responses,
 * and provides validation for API integrations.
 *
 * @package ContactFormToAPI
 * @subpackage API
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

namespace ContactFormToAPI\\API;

/**
 * API Endpoint Manager Class
 *
 * @since 1.0.0
 * @version 1.1.2
 */
class EndpointManager
{
    // Class implementation...
}
```

### Complete CSS File Example

```css
/**
 * Contact Form 7 to API - Admin Interface Styles
 *
 * Provides styling for the plugin's admin interface, including
 * configuration forms, status indicators, and responsive layouts.
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */

/* Admin interface styles */
.cf7-api-admin-container {
    /* Styles... */
}
```

### Complete JavaScript File Example

```javascript
/**
 * Contact Form 7 to API - Admin Configuration Scripts
 *
 * Provides interactive functionality for the admin configuration
 * interface, including API testing and field mapping.
 *
 * @package ContactFormToAPI
 * @since 1.0.0
 * @author Silver Assist
 * @version 1.1.2
 * @license Polyform-Noncommercial-1.0.0
 */

(function($) {
    'use strict';
    
    // Script implementation...
    
})(jQuery);
```

## Compliance and Maintenance

### Developer Responsibilities
- All new files must include proper headers
- Headers must be added before committing code
- Version numbers must not be manually edited
- Descriptions should be clear and accurate

### Review Process
- All pull requests must include proper headers
- Headers are validated during code review
- Automated checks prevent merging of non-compliant code

### Documentation Updates
This header standards document is updated with each major release to reflect any changes in formatting requirements or project information.

---

**Last Updated**: Version 1.0.1  
**Next Review**: With version 1.1.2 release

For questions about header standards, please refer to the project's contribution guidelines or contact the development team.
