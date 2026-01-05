# Contributing to Contact Form 7 to API

Thank you for your interest in contributing to Contact Form 7 to API! This document provides guidelines and instructions for contributing to this project.

## 📋 Table of Contents

- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Pull Request Process](#pull-request-process)
- [Project Architecture](#project-architecture)
- [CI/CD Workflows](#cicd-workflows)
- [Release Process](#release-process-maintainers)

## Development Setup

### Requirements

- **PHP**: 8.2 or higher
- **WordPress**: 6.5 or higher
- **Contact Form 7**: Latest version
- **Composer**: 2.x
- **MySQL**: 5.7+ or 8.0+ (for tests)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/SilverAssist/contact-form-to-api.git
cd contact-form-to-api
```

2. Install dependencies:
```bash
composer install
```

3. Set up WordPress Test Suite (for running tests):
```bash
./scripts/install-wp-tests.sh cf7_api_test root '' localhost latest
```

### WordPress Test Suite

The project uses the WordPress Test Suite for integration testing. The test environment is automatically set up when running quality checks.

**Environment Variables:**
```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
export WP_CORE_DIR=/tmp/wordpress/
```

**Manual Setup:**
```bash
# Install WordPress Test Suite
bash scripts/install-wp-tests.sh <db-name> <db-user> <db-pass> <db-host> <wp-version>

# Example
bash scripts/install-wp-tests.sh cf7_api_test root '' localhost latest
```

## Coding Standards

### PHP Standards

This project follows **WordPress-Extra** coding standards with SilverAssist extensions:

#### String Quotation (MANDATORY)
- **ALL strings MUST use double quotes**: `"string"` not `'string'`
- **i18n functions**: `__("Text", "contact-form-to-api")` not `__('text', 'domain')`
- **Exception**: Single quotes only inside double-quoted strings when necessary

#### PHP 8.2+ Modern Conventions
- Use typed properties and return types
- Use constructor property promotion where appropriate
- Prefer `match` over `switch` for cleaner code
- Use null coalescing operator `??`
- Use short array syntax `[]` not `array()`

#### WordPress Integration
- Prefix all WordPress functions with `\` in namespaced code: `\add_action()`, `\get_option()`
- NO `\` prefix for PHP native functions: `array_key_exists()`, `explode()`
- Text domain: `"contact-form-to-api"` (literal string, not constant)
- Use WordPress escaping: `\esc_html()`, `\esc_attr()`, `\esc_url()`
- Use WordPress sanitization: `\sanitize_text_field()`, `\sanitize_email()`

#### Namespace and Use Statements
- Namespace: `SilverAssist\ContactFormToAPI\`
- Import all classes at top of file (alphabetically sorted)
- NEVER import classes from same namespace
- NEVER use fully qualified names in methods

#### Documentation
- Complete PHPDoc for ALL classes, methods, and properties
- Required tags: `@since`, `@param`, `@return`, `@throws`
- ALL documentation in English
- Use translator comments for sprintf placeholders

### Quality Tools

Run all quality checks:
```bash
./scripts/run-quality-checks.sh
```

Individual checks:
```bash
# PHP CodeSniffer
composer phpcs

# Auto-fix PHPCS issues
composer phpcbf

# PHPStan (Level 8)
composer phpstan

# PHPUnit Tests
composer test

# Test Coverage
composer test:coverage
```

## Testing Requirements

### Test Coverage

- **Minimum Coverage**: 80% for new code
- **Test Types**: Unit tests, Integration tests, WordPress integration tests
- **Framework**: PHPUnit 9.6+ with WordPress Test Suite

### Writing Tests

1. Extend appropriate base class:
   - `SilverAssist\ContactFormToAPI\Tests\Helpers\TestCase` for unit tests
   - `SilverAssist\ContactFormToAPI\Tests\Helpers\CF7TestCase` for CF7 integration tests

2. Use descriptive test method names:
   - Pattern: `test_<method_name>_<scenario>_<expected_result>()`
   - Example: `test_activate_creates_database_tables()`

3. Test both success and failure scenarios

4. Use specific assertions: `assertSame()` over `assertEquals()`

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/PluginTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run with testdox output
vendor/bin/phpunit --testdox
```

## Pull Request Process

### Before Submitting

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Run quality checks**:
   ```bash
   ./scripts/run-quality-checks.sh
   ```
   All checks must pass before submitting PR.

3. **Update documentation**:
   - Add/update PHPDoc comments
   - Update README.md if needed
   - Add entry to CHANGELOG.md

4. **Write/update tests**:
   - Add tests for new functionality
   - Update existing tests if modifying behavior
   - Ensure all tests pass

### Commit Message Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Test updates
- `chore`: Build process or auxiliary tool changes

**Examples:**
```
feat(api): add support for XML payload format

- Implement XML serialization for form data
- Add XML content-type header support
- Add tests for XML format processing

Closes #123
```

```
fix(integration): resolve CF7 mail tag processing issue

- Fix mail tag replacement for nested arrays
- Update test assertions for edge cases

Fixes #456
```

### PR Checklist

- [ ] Code follows SilverAssist WordPress standards
- [ ] All strings use double quotes
- [ ] Text domain uses literal "contact-form-to-api"
- [ ] PHPDoc comments are complete
- [ ] All quality checks pass (PHPCS, PHPStan, PHPUnit)
- [ ] Tests added/updated for changes
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Commit messages follow conventional commits
- [ ] Branch is up to date with main

### Review Process

1. Submit PR with clear description
2. Address automated CI/CD feedback
3. Respond to code review comments
4. Make requested changes in new commits
5. Wait for approval from maintainers
6. PR will be squashed and merged

## Project Architecture

### Directory Structure

```
contact-form-to-api/
├── includes/              # Source code (PSR-4)
│   ├── Core/             # Core plugin functionality
│   │   ├── Interfaces/   # Contracts
│   │   ├── Activator.php # Lifecycle management
│   │   ├── Plugin.php    # Main controller
│   │   └── Updater.php   # GitHub updater
│   └── ContactForm/      # CF7 integration
│       └── Integration.php
├── assets/               # Frontend assets
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript
├── tests/               # Test suite
│   ├── Helpers/         # Test utilities
│   ├── Unit/           # Unit tests
│   ├── Integration/    # WordPress integration tests
│   └── ContactForm/    # CF7 integration tests
├── scripts/            # Build and utility scripts
├── docs/              # Documentation
└── languages/         # Translation files
```

### Key Patterns

#### LoadableInterface
All components implement `LoadableInterface`:
```php
interface LoadableInterface {
    public function init(): void;
    public function get_priority(): int;
    public function should_load(): bool;
}
```

#### Singleton Pattern
Main classes use singleton:
```php
public static function instance(): self {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

#### Priority System
- **10**: Core components
- **20**: Services
- **30**: Admin components
- **40**: Utility components

### Plugin Constants

**ALWAYS use plugin constants** instead of hardcoded values:

```php
CF7_API_VERSION        // Plugin version
CF7_API_FILE           // Main plugin file
CF7_API_DIR            // Plugin directory path
CF7_API_URL            // Plugin URL
CF7_API_BASENAME       // Plugin basename
CF7_API_MIN_PHP_VERSION
CF7_API_MIN_WP_VERSION
```

**i18n Text Domain**: Always use literal string `'contact-form-to-api'` (not a constant) for WordPress i18n extraction tools.

## CI/CD Workflows

### GitHub Actions

The project uses GitHub Actions for automated testing and releases:

```
.github/workflows/
├── quality-checks.yml      # Reusable quality check workflow
├── ci.yml                  # Pull request validation
├── release.yml             # Automated releases
└── dependency-updates.yml  # Automated dependency updates
```

### CI - Pull Request Validation

**Triggers**: All pull requests to `main` or `develop`

**Required Checks**:
- ✅ Composer validation
- ✅ PHPCS (WordPress-Extra)
- ✅ PHPStan (Level 8)
- ✅ PHPUnit (all tests passing)

### Branch Strategy

```
main (production)
  └── develop (integration)
      └── feature/* (features)
      └── fix/* (bug fixes)
```

### Development Workflow

1. Create feature branch from `develop`:
   ```bash
   git checkout -b feature/your-feature-name develop
   ```

2. Run quality checks frequently:
   ```bash
   ./scripts/run-quality-checks.sh
   ```

3. Commit with conventional commits:
   ```bash
   git commit -m "feat(scope): description"
   ```

4. Push and create PR to `develop`

5. After CI passes and approval, PR is squashed and merged

## Release Process (Maintainers)

### Versioning Strategy

Follow [Semantic Versioning 2.0.0](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward-compatible)
- **PATCH**: Bug fixes (backward-compatible)

### Pre-Release Checklist

- [ ] All tests passing
- [ ] PHPCS clean (0 errors)
- [ ] PHPStan Level 8 passing
- [ ] CHANGELOG.md updated
- [ ] Manual testing completed

### Creating a Release

1. **Update version**:
   ```bash
   ./scripts/update-version.sh 1.2.0
   ```

2. **Update CHANGELOG.md** with release notes

3. **Run quality checks**:
   ```bash
   ./scripts/run-quality-checks.sh
   ```

4. **Commit and tag**:
   ```bash
   git commit -am "chore: prepare release v1.2.0"
   git tag -a v1.2.0 -m "Release version 1.2.0"
   git push origin main --tags
   ```

5. GitHub Actions will automatically:
   - Run quality checks
   - Build release package
   - Create GitHub release with ZIP

### Hotfix Releases

For critical issues:

1. Create hotfix branch from `main`:
   ```bash
   git checkout -b hotfix/1.2.1 main
   ```

2. Implement fix with regression test

3. Update version to patch number (1.2.1)

4. Tag and push (triggers release)

5. Merge back to `main` and `develop`

## Resources

- [SilverAssist Standards](https://gist.github.com/miguelcolmenares/227180b8983df6ad4ec3ced113677853)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [PHPStan Documentation](https://phpstan.org/)
- [PHPUnit Documentation](https://phpunit.de/)
- [Contact Form 7 Documentation](https://contactform7.com/docs/)

## Hooks Reference

### Actions

```php
// Before displaying logs page
do_action('cf7_api_before_logs_page');

// After log deletion
do_action('cf7_api_log_deleted', $log_id);

// After bulk deletion
do_action('cf7_api_logs_bulk_deleted', $log_ids);

// Before sending data to API
do_action('cf7_api_before_send', $form_id, $data, $config);

// After API response received
do_action('cf7_api_after_send', $form_id, $response, $log_id);
```

### Filters

```php
// Modify logs query arguments
$args = apply_filters('cf7_api_logs_query_args', $args);

// Modify statistics output
$stats = apply_filters('cf7_api_logs_statistics', $stats, $form_id);

// Modify request data before sending
$data = apply_filters('cf7_api_request_data', $data, $form_id);

// Modify request headers
$headers = apply_filters('cf7_api_request_headers', $headers, $form_id);

// Modify API response handling
$response = apply_filters('cf7_api_response', $response, $form_id);
```

### Database Schema

Logs are stored in custom table: `{prefix}cf7_api_logs`

**Indexed columns** for performance:
- `form_id`
- `status`
- `created_at`

## Questions?

- Open an [issue](https://github.com/SilverAssist/contact-form-to-api/issues)
- Check existing [discussions](https://github.com/SilverAssist/contact-form-to-api/discussions)
- Review [documentation](https://github.com/SilverAssist/contact-form-to-api/wiki)

Thank you for contributing! 🎉
