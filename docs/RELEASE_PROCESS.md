# Release Process

This document describes the release process for Contact Form 7 to API plugin, including versioning strategy, changelog management, and deployment procedures.

## 📋 Table of Contents

- [Versioning Strategy](#versioning-strategy)
- [Pre-Release Checklist](#pre-release-checklist)
- [Release Preparation](#release-preparation)
- [Creating a Release](#creating-a-release)
- [Post-Release Tasks](#post-release-tasks)
- [Hotfix Releases](#hotfix-releases)

## Versioning Strategy

### Semantic Versioning

This project follows [Semantic Versioning 2.0.0](https://semver.org/):

```
MAJOR.MINOR.PATCH
```

**Version Components**:

- **MAJOR**: Incompatible API changes, breaking changes
- **MINOR**: New features, backward-compatible functionality
- **PATCH**: Bug fixes, backward-compatible fixes

**Examples**:

- `1.0.0` → `2.0.0`: Breaking changes (new namespace structure)
- `1.0.0` → `1.1.0`: New feature (XML payload support)
- `1.0.0` → `1.0.1`: Bug fix (mail tag processing issue)

### Version Number Format

```
v1.2.3
├─ v: Git tag prefix (required)
├─ 1: Major version
├─ 2: Minor version
└─ 3: Patch version
```

### Pre-Release Versions

For testing and staging:

```
v1.2.0-alpha.1    # Alpha release
v1.2.0-beta.1     # Beta release
v1.2.0-rc.1       # Release candidate
```

## Pre-Release Checklist

### Code Quality

- [ ] All tests passing (37/37)
- [ ] PHPCS clean (0 errors in source)
- [ ] PHPStan Level 8 passing (0 errors)
- [ ] Code coverage ≥80%
- [ ] No deprecation warnings
- [ ] All TODOs resolved

### Documentation

- [ ] CHANGELOG.md updated
- [ ] README.md current
- [ ] PHPDoc comments complete
- [ ] API documentation updated
- [ ] Migration guide (for breaking changes)

### Testing

- [ ] Manual testing on latest WordPress
- [ ] Manual testing on minimum WordPress (6.5)
- [ ] Contact Form 7 compatibility verified
- [ ] Multisite compatibility tested
- [ ] PHP 8.2, 8.3 compatibility verified

### Security

- [ ] Security audit completed
- [ ] No hardcoded credentials
- [ ] Input validation verified
- [ ] Output escaping verified
- [ ] Nonce verification in place
- [ ] Capability checks implemented

## Release Preparation

### Step 1: Update Version Number

Use the version update script:

```bash
./scripts/update-version.sh 1.2.0
```

**Manual verification**:

```bash
# Check version consistency
grep -r "1.2.0" contact-form-to-api.php composer.json

# Verify PHPDoc @version tags updated
grep -r "@version 1.2.0" includes/
```

### Step 2: Update CHANGELOG.md

Follow [Keep a Changelog](https://keepachangelog.com/) format:

```markdown
## [1.2.0] - 2025-11-16

### Added
- XML payload format support
- Custom header configuration
- Debug mode toggle

### Changed
- Improved error handling
- Updated API timeout defaults

### Fixed
- Mail tag processing for nested arrays
- CF7 submission hook priority

### Security
- Enhanced input validation
- Added sanitization for custom headers
```

**Sections to include**:

- `Added`: New features
- `Changed`: Changes in existing functionality
- `Deprecated`: Soon-to-be removed features
- `Removed`: Removed features
- `Fixed`: Bug fixes
- `Security`: Security improvements

### Step 3: Run Quality Checks

Execute complete quality check suite:

```bash
./scripts/run-quality-checks.sh
```

**Expected results**:

```
✓ Composer validation passed
✓ PHPCS passed (source code)
✓ PHPStan Level 8 passed (0 errors)
✓ PHPUnit passed (37/37 tests)
```

**If checks fail**:

```bash
# Fix PHPCS issues
composer phpcbf

# Review PHPStan errors
composer phpstan

# Debug failing tests
vendor/bin/phpunit --testdox
```

### Step 4: Build Release Package

Create release ZIP file:

```bash
./scripts/build-release.sh
```

**Verification**:

```bash
# Extract and verify contents
unzip -l contact-form-to-api-1.2.0.zip

# Check file permissions
zipinfo contact-form-to-api-1.2.0.zip

# Verify no development files included
unzip -l contact-form-to-api-1.2.0.zip | grep -E '(tests|node_modules|\.git)'
```

### Step 5: Commit Changes

Commit version update and changelog:

```bash
git add -A
git commit -m "chore: prepare release v1.2.0

- Update version to 1.2.0
- Update CHANGELOG.md with release notes
- Run quality checks (all passing)"

git push origin main
```

## Creating a Release

### Automated Release (Recommended)

1. **Create Git tag**:

   ```bash
   git tag -a v1.2.0 -m "Release version 1.2.0"
   ```

2. **Push tag** (triggers GitHub Actions):

   ```bash
   git push origin v1.2.0
   ```

3. **GitHub Actions workflow**:
   - Runs quality checks
   - Builds release package
   - Creates GitHub release
   - Uploads ZIP asset
   - Generates release notes

4. **Verify release**:
   - Check [GitHub Releases](https://github.com/SilverAssist/contact-form-to-api/releases)
   - Download and test ZIP file
   - Verify release notes

### Manual Release

If automated release fails:

1. **Create release package**:

   ```bash
   ./scripts/build-release.sh
   ```

2. **Create GitHub release**:
   - Go to [New Release](https://github.com/SilverAssist/contact-form-to-api/releases/new)
   - Select tag: `v1.2.0`
   - Release title: `Version 1.2.0`
   - Description: Copy from CHANGELOG.md
   - Upload: `contact-form-to-api-1.2.0.zip`
   - Publish release

3. **Verify GitHub Updater**:

   ```bash
   # Test update check in WordPress
   # Dashboard → Updates → Check for updates
   ```

## Post-Release Tasks

### Update WordPress.org (If Applicable)

```bash
# SVN commit to WordPress.org repository
svn checkout https://plugins.svn.wordpress.org/contact-form-to-api
cd contact-form-to-api

# Copy files to trunk
cp -r /path/to/plugin/* trunk/

# Create tag
svn cp trunk tags/1.2.0

# Commit
svn ci -m "Release version 1.2.0"
```

### Announcement

**Channels**:

- GitHub release notes
- Plugin changelog
- Documentation website
- Twitter/social media
- Email notification (if applicable)

**Template**:

```markdown
🎉 Contact Form 7 to API v1.2.0 Released!

New features:
- XML payload support
- Enhanced error handling
- Debug mode improvements

Download: https://github.com/SilverAssist/contact-form-to-api/releases/tag/v1.2.0

Full changelog: https://github.com/SilverAssist/contact-form-to-api/blob/main/CHANGELOG.md
```

### Version Bump for Development

Prepare for next development cycle:

```bash
# Update to next dev version
./scripts/update-version.sh 1.3.0-dev

git commit -am "chore: bump version to 1.3.0-dev"
git push origin main
```

### Monitor Release

- Check for error reports
- Monitor GitHub issues
- Review support requests
- Track download statistics
- Monitor plugin ratings

## Hotfix Releases

### When to Create Hotfix

- Critical security vulnerability
- Major bug affecting all users
- Data loss or corruption issue
- Plugin activation/deactivation failure

### Hotfix Process

1. **Create hotfix branch** from `main`:

   ```bash
   git checkout -b hotfix/1.2.1 main
   ```

2. **Implement fix**:

   ```bash
   # Make necessary changes
   # Add regression test
   vendor/bin/phpunit tests/Unit/HotfixTest.php
   ```

3. **Update version**:

   ```bash
   ./scripts/update-version.sh 1.2.1
   ```

4. **Update CHANGELOG.md**:

   ```markdown
   ## [1.2.1] - 2025-11-17
   
   ### Fixed
   - Critical: Fixed data sanitization vulnerability (CVE-XXXX-XXXXX)
   ```

5. **Quality checks**:

   ```bash
   ./scripts/run-quality-checks.sh
   ```

6. **Commit and tag**:

   ```bash
   git commit -am "fix: critical data sanitization vulnerability"
   git tag -a v1.2.1 -m "Hotfix version 1.2.1"
   ```

7. **Push** (triggers release):

   ```bash
   git push origin v1.2.1
   ```

8. **Merge back**:

   ```bash
   # Merge to main
   git checkout main
   git merge hotfix/1.2.1
   git push origin main
   
   # Merge to develop
   git checkout develop
   git merge hotfix/1.2.1
   git push origin develop
   
   # Delete hotfix branch
   git branch -d hotfix/1.2.1
   ```

9. **Expedited announcement**:
   - Mark as critical update
   - Notify all users
   - Document security issue (after users updated)

## Release Schedule

### Regular Releases

- **Major releases**: 6-12 months
- **Minor releases**: 1-3 months
- **Patch releases**: As needed
- **Security releases**: Immediate

### Release Timeline

**Week 1-2: Development**

- Feature development
- Code review
- Documentation updates

**Week 3: Testing**

- Quality checks
- Manual testing
- Beta release (optional)

**Week 4: Release**

- Final testing
- Release candidate
- Production release

## Rollback Procedure

If release has critical issues:

1. **Immediate response**:

   ```bash
   # Create rollback tag pointing to previous version
   git tag -a v1.2.0-rollback -m "Rollback to v1.1.0"
   git push origin v1.2.0-rollback
   ```

2. **Notify users**:
   - Update release notes
   - Mark release as deprecated
   - Provide downgrade instructions

3. **Create hotfix**:
   - Fix critical issues
   - Follow hotfix process
   - Release corrected version

## Resources

- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)

## Support

- Issues: https://github.com/SilverAssist/contact-form-to-api/issues
- Documentation: https://github.com/SilverAssist/contact-form-to-api/wiki
- Email: info@silverassist.com
