# CI/CD Workflows

This document describes the continuous integration and deployment workflows for the Contact Form 7 to API plugin.

## 📋 Table of Contents

- [Workflow Overview](#workflow-overview)
- [GitHub Actions Workflows](#github-actions-workflows)
- [Quality Checks](#quality-checks)
- [Development Workflow](#development-workflow)
- [Release Automation](#release-automation)

## Workflow Overview

The project uses GitHub Actions for automated testing, quality checks, and releases. All workflows follow SilverAssist standards and are configured in `.github/workflows/`.

### Workflow Architecture

```
.github/workflows/
├── quality-checks.yml      # Reusable quality check workflow
├── ci.yml                  # Pull request validation
├── release.yml             # Automated releases
└── dependency-updates.yml  # Automated dependency updates
```

## GitHub Actions Workflows

### Quality Checks (Reusable Workflow)

**File**: `.github/workflows/quality-checks.yml`

**Purpose**: Reusable workflow for running all quality checks (PHPCS, PHPStan, PHPUnit)

**Features**:

- PHP version matrix support (8.2, 8.3, 8.4)
- Composer dependency caching
- WordPress Test Suite setup
- MySQL service for integration tests
- Parallel execution when possible

**Usage**:

```yaml
jobs:
  quality:
    uses: ./.github/workflows/quality-checks.yml
    with:
      php-version: '8.2'
```

**Steps**:

1. Checkout code
2. Setup PHP with extensions
3. Validate composer.json
4. Install Composer dependencies (with caching)
5. Setup WordPress Test Suite
6. Run PHPCS (WordPress-Extra)
7. Run PHPStan (Level 8)
8. Run PHPUnit with WordPress
9. Upload test results

### CI - Pull Request Validation

**File**: `.github/workflows/ci.yml`

**Triggers**:

```yaml
on:
  pull_request:
    branches: [main, develop]
```

**Purpose**: Validate all pull requests before merge

**Process**:

1. Run quality checks via reusable workflow
2. Check for PHPCS violations
3. Verify PHPStan Level 8 compliance
4. Ensure all tests pass
5. Report status to PR

**Required Checks**:

- ✅ Composer validation
- ✅ PHPCS (WordPress-Extra)
- ✅ PHPStan (Level 8)
- ✅ PHPUnit (37 tests)

### Release - Automated Releases

**File**: `.github/workflows/release.yml`

**Triggers**:

```yaml
on:
  push:
    tags:
      - 'v*'
```

**Purpose**: Automate release creation and package building

**Process**:

1. Run quality checks
2. Build release package
3. Create GitHub release
4. Upload release assets
5. Trigger update notifications

See [RELEASE_PROCESS.md](RELEASE_PROCESS.md) for detailed release instructions.

### Dependency Updates

**File**: `.github/workflows/dependency-updates.yml`

**Triggers**:

```yaml
on:
  schedule:
    - cron: '0 0 * * 1'  # Weekly on Monday
  workflow_dispatch:
```

**Purpose**: Keep dependencies up to date

**Process**:

1. Check for outdated dependencies
2. Create update branch
3. Run quality checks
4. Create pull request if tests pass

## Quality Checks

### Local Quality Check Script

**Script**: `scripts/run-quality-checks.sh`

Run all quality checks locally:

```bash
./scripts/run-quality-checks.sh
```

**Execution Order**:

1. **Composer Validation**
   - Validates `composer.json` structure
   - Checks for security issues
   - Verifies lock file consistency

2. **PHPCS (WordPress-Extra)**
   - Coding standards compliance
   - WordPress best practices
   - Security checks
   - Documentation standards

3. **PHPStan (Level 8)**
   - Static type analysis
   - Type safety verification
   - Code quality checks
   - WordPress compatibility

4. **PHPUnit (WordPress Test Suite)**
   - Unit tests
   - Integration tests
   - WordPress compatibility tests
   - Code coverage analysis

### Quality Check Options

```bash
# Run with auto-fix
./scripts/run-quality-checks.sh --fix

# Skip WordPress Test Suite installation
./scripts/run-quality-checks.sh --skip-wp-setup

# Specify WordPress version
./scripts/run-quality-checks.sh --wp-version=6.5

# Custom database settings
./scripts/run-quality-checks.sh --db-name=custom_test --db-user=user --db-pass=pass
```

### Individual Checks

Run specific quality checks:

```bash
# PHPCS only
composer phpcs

# Auto-fix PHPCS issues
composer phpcbf

# PHPStan only
composer phpstan

# PHPUnit only
composer test

# Test coverage report
composer test:coverage
```

## Development Workflow

### Branch Strategy

```
main (production)
  └── develop (integration)
      └── feature/* (features)
      └── fix/* (bug fixes)
      └── refactor/* (refactoring)
```

### Feature Development

1. **Create feature branch**:

   ```bash
   git checkout -b feature/your-feature-name develop
   ```

2. **Develop with quality checks**:

   ```bash
   # Make changes
   # Run quality checks frequently
   ./scripts/run-quality-checks.sh
   ```

3. **Commit with conventional commits**:

   ```bash
   git commit -m "feat(scope): description"
   ```

4. **Push and create PR**:

   ```bash
   git push origin feature/your-feature-name
   ```

5. **Address CI feedback**:
   - Fix any failing checks
   - Respond to code review comments
   - Update documentation

6. **Merge after approval**:
   - PR is squashed and merged to develop
   - CI runs on develop branch
   - Manual testing in staging environment

### Bug Fix Workflow

1. **Create fix branch**:

   ```bash
   git checkout -b fix/bug-description main
   ```

2. **Write failing test**:

   ```bash
   # Add test that reproduces bug
   vendor/bin/phpunit tests/Unit/BugTest.php
   ```

3. **Implement fix**:

   ```bash
   # Fix the bug
   # Run quality checks
   ./scripts/run-quality-checks.sh
   ```

4. **Verify fix**:

   ```bash
   # Ensure test now passes
   vendor/bin/phpunit tests/Unit/BugTest.php
   ```

5. **Create PR to main**:
   - Include bug description
   - Reference issue number
   - Include test coverage

## Release Automation

### Version Management

Update version across all files:

```bash
./scripts/update-version.sh 1.2.0
```

**Files Updated**:

- `contact-form-to-api.php` (Plugin header)
- `CONTACT_FORM_TO_API_VERSION` constant
- `package.json` (if exists)
- All `@version` PHPDoc tags
- `composer.json` version

### Build Process

Create release package:

```bash
./scripts/build-release.sh
```

**Build Steps**:

1. Validate version numbers
2. Run quality checks
3. Install production dependencies
4. Copy files to build directory
5. Exclude development files
6. Create ZIP archive

**Output**: `contact-form-to-api-{version}.zip`

### GitHub Release

1. **Tag release**:

   ```bash
   git tag -a v1.2.0 -m "Release version 1.2.0"
   git push origin v1.2.0
   ```

2. **Automated process** (via GitHub Actions):
   - Quality checks run
   - Build package created
   - GitHub release created
   - Release notes from CHANGELOG.md
   - ZIP uploaded as asset
   - Update notifications sent

3. **Manual verification**:
   - Check GitHub release page
   - Download and test ZIP
   - Verify update mechanism

### Hotfix Releases

For critical bug fixes:

```bash
# Create hotfix branch
git checkout -b hotfix/1.2.1 main

# Make fix and update version
./scripts/update-version.sh 1.2.1

# Run quality checks
./scripts/run-quality-checks.sh

# Commit and tag
git commit -m "fix: critical bug description"
git tag -a v1.2.1 -m "Hotfix version 1.2.1"

# Push tag (triggers release workflow)
git push origin v1.2.1

# Merge back to main and develop
git checkout main
git merge hotfix/1.2.1
git checkout develop
git merge hotfix/1.2.1
```

## Workflow Monitoring

### GitHub Actions Dashboard

Monitor workflow status:

1. Go to repository **Actions** tab
2. View recent workflow runs
3. Check for failures
4. Review logs for debugging

### Status Badges

Add to README.md:

```markdown
![CI Status](https://github.com/SilverAssist/contact-form-to-api/workflows/CI/badge.svg)
![Quality Checks](https://github.com/SilverAssist/contact-form-to-api/workflows/Quality%20Checks/badge.svg)
```

### Notifications

Configure GitHub notifications for:

- Failed workflow runs
- Pull request checks
- Release completions
- Security alerts

## Troubleshooting

### Common Issues

**PHPCS Failures**:

```bash
# Auto-fix most issues
composer phpcbf

# Review remaining issues
composer phpcs
```

**PHPStan Errors**:

```bash
# Increase memory limit
php -d memory_limit=512M vendor/bin/phpstan analyse

# Check specific file
vendor/bin/phpstan analyse includes/Core/Plugin.php
```

**PHPUnit Failures**:

```bash
# Run specific test
vendor/bin/phpunit tests/Unit/PluginTest.php

# Run with verbose output
vendor/bin/phpunit --testdox

# Check test database
mysql -u root -p -e "SHOW DATABASES LIKE 'cf7_api_test%';"
```

**WordPress Test Suite Issues**:

```bash
# Reinstall test suite
rm -rf /tmp/wordpress-tests-lib /tmp/wordpress
./scripts/install-wp-tests.sh cf7_api_test root '' localhost latest

# Check environment variables
echo $WP_TESTS_DIR
echo $WP_CORE_DIR
```

## Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [WordPress Test Suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [SilverAssist Standards](https://gist.github.com/miguelcolmenares/227180b8983df6ad4ec3ced113677853)
- [Semantic Versioning](https://semver.org/)
- [Conventional Commits](https://www.conventionalcommits.org/)
