---
name: github-cli
description: GitHub CLI commands for workflows, PRs, and issues. Use when checking CI status, monitoring workflows, or interacting with GitHub. CRITICAL - always append '| cat' to prevent pager hang.
---

# GitHub CLI Skill

## When to Use

- Checking CI/CD workflow status
- Monitoring releases and deployments
- Managing PRs and issues
- Debugging failed workflows

## 🚨 CRITICAL: Always Use `| cat`

**EVERY `gh` command MUST end with `| cat`** to prevent terminal hang from pager.

```bash
# ✅ CORRECT
gh pr checks | cat
gh run list | cat

# ❌ WRONG - Terminal will hang indefinitely
gh pr checks
gh run list
```

## Workflow Commands

### List Recent Workflow Runs

```bash
gh run list | cat
gh run list --workflow=release.yml --limit 5 | cat
gh run list --workflow=ci.yml --limit 5 | cat
```

### View Specific Run

```bash
gh run view <run-id> | cat
gh run view <run-id> --log | cat
gh run view <run-id> --log-failed | cat
```

### Watch Run (Interactive)

```bash
gh run watch <run-id> --exit-status
```

### Re-run Failed Jobs

```bash
gh run rerun <run-id>
gh run rerun <run-id> --failed
```

### Cancel Running Workflow

```bash
gh run cancel <run-id>
```

## Pull Request Commands

### Check PR Status

```bash
gh pr checks | cat
gh pr status | cat
gh pr view | cat
gh pr view <pr-number> | cat
```

### List PRs

```bash
gh pr list | cat
gh pr list --state open | cat
gh pr list --state merged --limit 10 | cat
```

### View PR Diff

```bash
gh pr diff | cat
gh pr diff <pr-number> | cat
```

### Create PR

```bash
gh pr create --title "feat: Description" --body "Details..."
```

## Issue Commands

### List Issues

```bash
gh issue list | cat
gh issue list --state open | cat
gh issue list --label "bug" | cat
```

### View Issue

```bash
gh issue view <issue-number> | cat
```

## Release Commands

### List Releases

```bash
gh release list | cat
gh release list --limit 5 | cat
```

### View Release

```bash
gh release view <tag> | cat
gh release view v1.3.13 | cat
```

### ⚠️ NEVER Create Releases Manually

```bash
# ❌ FORBIDDEN - Let release.yml workflow handle this
gh release create v1.3.14 --title "..." --notes "..."
```

## Repository Information

### View Repo

```bash
gh repo view | cat
gh repo view SilverAssist/contact-form-to-api | cat
```

### Clone

```bash
gh repo clone SilverAssist/contact-form-to-api
```

## Debugging CI Failures

### Quick Diagnostic Flow

```bash
# 1. List recent runs
gh run list --limit 5 | cat

# 2. View failed run details
gh run view <run-id> | cat

# 3. Get failed job logs
gh run view <run-id> --log-failed | cat

# 4. If needed, full logs
gh run view <run-id> --log | cat
```

### Common Failure Reasons

1. **PHPCS errors**: Run `vendor/bin/phpcbf` locally
2. **PHPStan errors**: Check type hints and return types
3. **Test failures**: Run `vendor/bin/phpunit` locally
4. **Version mismatch**: Run `./scripts/check-versions.sh`

## Authentication

If commands fail with auth errors:

```bash
gh auth status
gh auth login
```
