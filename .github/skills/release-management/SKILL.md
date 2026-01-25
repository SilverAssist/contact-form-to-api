---
name: release-management
description: Create new releases for the WordPress plugin. Use when creating tags, bumping versions, or preparing releases. Includes version script usage, immutable tag rules, and CHANGELOG updates.
---

# Release Management Skill

## When to Use

- User asks to create a new release
- User wants to bump the version
- User needs to prepare for a release
- A previous release failed and needs retry

## ⚠️ CRITICAL: Immutable Tags and Releases

GitHub enforces **immutability** on tags and releases. Once a tag is used (even if release fails), it **CANNOT be reused**.

### 🚨 NEVER Create Releases Manually

**ALWAYS let the `release.yml` workflow create the GitHub release.**

```bash
# ❌ FORBIDDEN - causes "immutable release" errors
gh release create v1.3.5 --title "..." --notes "..."

# ✅ CORRECT - Only create and push the tag
git tag v1.3.6 -m "Release v1.3.6"
git push origin v1.3.6
```

## Release Workflow

### Step 1: Update All Versions

```bash
./scripts/update-version-simple.sh 1.3.X --no-confirm --force
```

This script updates:

- Main plugin file (`Version:` header and `CF7_API_VERSION` constant)
- All PHP `@version` tags in `includes/`
- All CSS `@version` tags in `assets/css/`
- All JS `@version` tags in `assets/js/`
- All shell scripts `@version` tags

### Step 2: Update CHANGELOG.md

Add a new section at the top:

```markdown
## [1.3.X] - YYYY-MM-DD

### Added
- New features...

### Changed
- Changes...

### Fixed
- Bug fixes...
```

### Step 3: Verify Consistency

```bash
./scripts/check-versions.sh
```

Must show all files with matching version.

### Step 4: Commit and Push

```bash
git add -A
git commit -m "chore: bump version to 1.3.X for release"
git push origin main
```

### Step 5: Create Tag (Triggers Release)

```bash
git tag v1.3.X -m "Release v1.3.X"
git push origin v1.3.X
```

### Step 6: Monitor Workflow

```bash
gh run list --workflow=release.yml --limit 3 | cat
gh run watch <run-id> --exit-status
```

## If a Release Fails

1. **DO NOT** try to delete and recreate the same tag
2. **DO NOT** manually create a release
3. **INCREMENT** the version (e.g., v1.3.5 → v1.3.6)
4. Start from Step 1 with the new version
