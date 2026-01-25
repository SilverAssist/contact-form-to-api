---
name: create-component
description: Create new plugin components following LoadableInterface pattern. Use when adding new services, controllers, views, or models. Includes file templates and registration steps.
---

# Create Component Skill

## When to Use

- Adding a new Service, Controller, View, or Model
- Creating a new admin page or feature
- Adding infrastructure components

## Architecture Overview

### Priority System (LoadableInterface)

- **10**: Core (Plugin, Activator, EncryptionService)
- **20**: Services (ApiClient, MigrationService, business logic)
- **30**: Admin & Controllers (SettingsPage, RequestLogController)
- **40**: Utils (DebugLogger, StringHelper)

### Directory Structure

```
includes/
├── Admin/           # Priority 30 - Admin UI components
├── Config/          # Configuration management
├── Controller/      # HTTP/Admin request handlers
│   ├── Admin/
│   └── ContactForm/
├── Core/            # Priority 10 - Bootstrap & lifecycle
├── Exception/       # Custom exceptions
├── Infrastructure/  # WordPress integrations
├── Model/           # Domain models (no LoadableInterface)
├── Repository/      # Data access interfaces
├── Service/         # Priority 20 - Business logic
├── Utils/           # Priority 40 - Utilities
└── View/            # HTML rendering (static classes)
```

## Component Templates

### Service Class (Priority 20)

```php
<?php
/**
 * Service Name
 *
 * Description of what this service does.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage Service\Category
 * @since X.Y.Z
 * @version X.Y.Z
 */

namespace SilverAssist\ContactFormToAPI\Service\Category;

use SilverAssist\ContactFormToAPI\Core\Interfaces\LoadableInterface;

\defined('ABSPATH') || exit;

/**
 * Class ServiceName
 *
 * Detailed description.
 */
class ServiceName implements LoadableInterface {
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton.
     */
    private function __construct() {}

    /**
     * Initialize the service.
     *
     * @return void
     */
    public function init(): void {
        // Register hooks here.
    }

    /**
     * Get loading priority.
     *
     * @return int
     */
    public function get_priority(): int {
        return 20;
    }

    /**
     * Check if should load.
     *
     * @return bool
     */
    public function should_load(): bool {
        return true;
    }
}
```

### View Class (Static, No LoadableInterface)

```php
<?php
/**
 * View Name
 *
 * HTML rendering for feature.
 *
 * @package SilverAssist\ContactFormToAPI
 * @subpackage View\Category
 * @since X.Y.Z
 * @version X.Y.Z
 */

namespace SilverAssist\ContactFormToAPI\View\Category;

\defined('ABSPATH') || exit;

/**
 * Class ViewName
 *
 * Renders HTML for feature.
 */
class ViewName {
    /**
     * Render the main view.
     *
     * @param array $data Data to render.
     * @return void
     */
    public static function render(array $data): void {
        ?>
        <div class="wrap">
            <h1><?php \esc_html_e('Title', 'contact-form-to-api'); ?></h1>
            <!-- HTML content -->
        </div>
        <?php
    }
}
```

## Registration Steps

### 1. Add to Plugin.php Components

Edit `includes/Core/Plugin.php`:

```php
private function get_components(): array {
    return [
        // ... existing components
        \SilverAssist\ContactFormToAPI\Service\Category\ServiceName::class,
    ];
}
```

### 2. Add Use Statement (Alphabetically)

```php
use SilverAssist\ContactFormToAPI\Service\Category\ServiceName;
```

### 3. Run Quality Checks

```bash
vendor/bin/phpcs includes/Service/Category/ServiceName.php
vendor/bin/phpstan analyse includes/ --level=8
```

## Checklist

- [ ] Follows namespace convention `SilverAssist\ContactFormToAPI\`
- [ ] Implements `LoadableInterface` (if needs initialization)
- [ ] Has complete PHPDoc with `@since` and `@version`
- [ ] Uses `\` prefix for WordPress functions
- [ ] Single quotes for simple strings
- [ ] Registered in `Plugin.php` components array
- [ ] Passes PHPCS and PHPStan
