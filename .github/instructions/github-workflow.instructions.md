---
description: GitHub workflow standards including branch management, PRs, issues, and releases
name: GitHub Workflow
applyTo: "**"
---

# GitHub Workflow Instructions for Contact Form to API Plugin

**Applies to**: All GitHub operations, branch management, issues, PRs, and releases  
**Last Updated**: January 23, 2026  
**Project**: Contact Form to API WordPress Plugin

---

## 🌳 Branch Management

### Branch Naming Conventions

```
main                → Production branch (default)
feature/description → New features (e.g., feature/search-by-sender-name)
fix/description     → Bug fixes (e.g., fix/n+1-query-performance)
chore/description   → Maintenance tasks (e.g., chore/update-deps)
hotfix/description  → Emergency fixes (e.g., hotfix/security-patch)
release/vX.Y.Z      → Release preparation (e.g., release/v1.3.14)
refactor/description → Code refactoring (e.g., refactor/extract-log-writer)
```

### Branch Hierarchy

```
main (default branch)
 ↑
 ├── feature/search-by-sender-name
 ├── feature/resolved-errors-filter
 ├── fix/sql-string-quotation
 └── refactor/mvc-architecture
```

### CRITICAL Rules

**✅ DO:**
- Create feature branches from `main`
- Merge feature branches back to `main` via PR
- Delete branches after PR merge
- Keep branches up to date with `main`

**❌ NEVER:**
- Commit directly to `main`
- Skip the PR review process
- Merge without CI passing
- Leave stale branches

### Branch Creation Workflow

```bash
# 1. Start new feature from main
git checkout main
git pull origin main
git checkout -b feature/new-feature

# 2. Work and commit
git add .
git commit -m "feat: Add new feature"

# 3. ⚠️ MANDATORY: Run quality checks BEFORE pushing
composer phpcs        # Must pass (0 errors)
composer phpstan      # Must pass (Level 8)
composer test         # Must pass (all tests green)

# 4. Push to remote (only after quality checks pass)
git push -u origin feature/new-feature

# 5. Create PR to main
gh pr create --title "feat: Add new feature" \
  --base main \
  --label "enhancement" | cat

# 6. After PR merge, delete branch (LOCAL + REMOTE)
git checkout main
git pull origin main
git branch -d feature/new-feature           # Delete local
git push origin --delete feature/new-feature # Delete remote
```

---

## 🏷️ Issue Management

### Issue Creation Template

```bash
gh issue create \
  --title "feat: Add new functionality" \
  --body "**Description:**
Implement new functionality with:
- Feature 1
- Feature 2
- Feature 3

**Acceptance Criteria:**
- [ ] Criteria 1
- [ ] Criteria 2
- [ ] Criteria 3

**Technical Notes:**
- Implementation details
- Dependencies" \
  --label "enhancement" | cat
```

### Required Labels for Each Issue

**Type (Required):**
- `enhancement` - New features
- `bug` - Bug fixes
- `documentation` - Documentation updates
- `refactoring` - Code refactoring
- `architecture` - Architecture changes
- `testing` - Unit/integration tests
- `security` - Security issues
- `performance` - Performance optimization

**Priority (Recommended):**
- `major-release` - Breaking changes (2.0.0)
- `minor-release` - New features (1.4.0)
- `patch-release` - Bug fixes (1.3.15)

### Issue Commands

```bash
# List issues
gh issue list | cat

# View specific issue
gh issue view 42 | cat

# Close issue
gh issue close 42 --comment "Completed in PR #55" | cat

# Add labels to existing issue
gh issue edit 42 --add-label "testing" | cat
```

---

## 🔀 Pull Request Management

### PR Creation

```bash
# Standard PR to main
gh pr create \
  --title "feat: Add search by sender name" \
  --body "Implements search by sender name functionality.

**Changes:**
- Added sender name extraction from form data
- Implemented hybrid SQL/PHP search
- Added unit tests for search matching
- Updated CHANGELOG

**Testing:**
- ✅ PHPStan Level 8 passes
- ✅ PHPCS clean
- ✅ All tests passing

Closes #55" \
  --base main \
  --label "enhancement" | cat
```

### PR Review Checklist

**🚨 CRITICAL: Run Quality Checks LOCALLY Before Creating PR**

```bash
# Run ALL quality checks (all must pass)
composer phpcs        # WordPress Coding Standards - 0 errors required
composer phpstan      # Static Analysis Level 8 - 0 errors
composer test         # PHPUnit - all tests must pass
```

**⚠️ DO NOT CREATE PR IF ANY CHECK FAILS**

---

**Before Creating PR:**
- [ ] ✅ **PHPCS passes locally** (`composer phpcs` - 0 errors)
- [ ] ✅ **PHPStan passes locally** (`composer phpstan` - 0 errors)
- [ ] ✅ **All tests pass locally** (`composer test` - all green)
- [ ] All commits follow conventional commits format
- [ ] Documentation updated if needed

**PR Description Must Include:**
- [ ] Clear description of changes
- [ ] List of specific changes
- [ ] Testing notes
- [ ] Issue references (Closes #N)

### PR Commands

```bash
# List open PRs
gh pr list | cat

# View PR details
gh pr view 55 | cat

# View PR diff
gh pr diff 55 | cat

# Check PR status (CI checks)
gh pr checks 55 | cat

# Merge PR (squash recommended)
gh pr merge 55 --squash --delete-branch | cat

# Close PR without merging
gh pr close 55 --comment "Not needed anymore" | cat
```

### ⚠️ PR Reviews vs Comments (CRITICAL DISTINCTION)

**Reviews** and **Comments** are DIFFERENT things in GitHub:

| Type | Location | Purpose | How to Reply |
|------|----------|---------|--------------|
| **Reviews** | "Files changed" tab, inline on code | Request changes to specific lines | `gh api graphql` mutation |
| **Comments** | "Conversation" tab | General discussion | `gh pr comment` or MCP |

#### Reviews (Inline Code Feedback)
- Made directly on modified files in "Files changed" tab
- Reference specific lines of code
- Request changes to specific code sections
- Have thread IDs starting with `PRRT_`
- **MCP can READ but CANNOT REPLY** - Must use `gh api graphql`

#### Comments (General Discussion)
- Appear in "Conversation" tab
- General discussion about the PR
- Can be replied to via MCP or `gh pr comment`

### Responding to PR Reviews

**See full skill documentation:** `.github/skills/pr-review-response/SKILL.md`

#### Quick Reference

```bash
# 1. Get review threads
gh api graphql -f query='
query {
  repository(owner: "SilverAssist", name: "contact-form-to-api") {
    pullRequest(number: PR_NUMBER) {
      reviewThreads(first: 50) {
        nodes {
          id
          path
          line
          isResolved
          comments(first: 5) {
            nodes { body author { login } }
          }
        }
      }
    }
  }
}' | cat

# 2. Reply to each thread (SEPARATE mutation per thread)
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6XXXXX",
    body: "Applied in commit [abc1234](https://github.com/SilverAssist/contact-form-to-api/commit/abc1234). **Description.**"
  }) {
    comment { id }
  }
}' | cat
```

#### Response Format

```markdown
Applied in commit [SHA](commit-url). **Short description.**

**Changes:**
- Change 1
- Change 2

Explanation of what was done.
```

---

## 📋 Post-PR Merge Checklist (MANDATORY)

**After EVERY PR is merged, complete these steps:**

### 1. Clean Up Branches (Local + Remote)

```bash
# Switch to main and update
git checkout main
git pull origin main

# Delete local branch
git branch -d feature/your-feature

# Delete remote branch (if not auto-deleted)
git push origin --delete feature/your-feature

# Prune stale remote-tracking branches
git fetch --prune
```

### 2. Update Related Issue(s)

```bash
# Check if issue is still open
gh issue view ISSUE_NUMBER | cat

# Close the issue with PR reference
gh issue close ISSUE_NUMBER --comment "✅ Completed in PR #XX" | cat
```

---

## 🚀 Release & Deployment Flow

### Semantic Versioning

| Version | Type | Example | When to Use |
|---------|------|---------|-------------|
| MAJOR | X.0.0 | 2.0.0 | Breaking changes, architecture refactoring |
| MINOR | 1.X.0 | 1.4.0 | New features, backwards compatible |
| PATCH | 1.3.X | 1.3.15 | Bug fixes, documentation |

### Release Process

#### Phase 1: Prepare Release

```bash
# 1. Checkout main
git checkout main
git pull origin main

# 2. Create release branch
git checkout -b release/v1.3.14

# 3. Update version in main plugin file
# Change: Version: 1.3.13
# To:     Version: 1.3.14

# 4. Update CHANGELOG.md
# Move [Unreleased] content to new version section
```

#### Phase 2: Update CHANGELOG.md

**Transform [Unreleased] to version:**

```markdown
# Before:
## [Unreleased]

### Added
- New feature X

## [1.3.13] - 2026-01-23
...

# After:
## [Unreleased]

## [1.3.14] - 2026-01-24

### Added
- New feature X

## [1.3.13] - 2026-01-23
...
```

#### Phase 3: Commit and Tag

```bash
# Commit version changes
git add contact-form-to-api.php
git add CHANGELOG.md
git commit -m "chore: Prepare release v1.3.14"

# Push release branch
git push -u origin release/v1.3.14

# Create PR to main
gh pr create \
  --title "Release v1.3.14" \
  --base main | cat

# After PR merged, create tag FROM MAIN
git checkout main
git pull origin main
git tag -a v1.3.14 -m "Release v1.3.14"
git push origin v1.3.14
```

### Release Workflow (Automatic)

The `release.yml` workflow triggers on tag push and:
1. Runs quality checks
2. Builds production package
3. Creates GitHub Release
4. Uploads ZIP asset

---

## 🔧 GitHub CLI Commands Reference

### CRITICAL: Always Use `| cat`

**MANDATORY**: Always append `| cat` to `gh` commands to prevent terminal pagination.

```bash
# ❌ BAD (will hang waiting for user input)
gh issue list
gh pr view 42

# ✅ GOOD (completes immediately)
gh issue list | cat
gh pr view 42 | cat
```

### Quick Reference

```bash
# Issues
gh issue list | cat
gh issue create --label "enhancement" | cat
gh issue view NUMBER | cat
gh issue close NUMBER | cat

# Pull Requests
gh pr list | cat
gh pr create --base main | cat
gh pr view NUMBER | cat
gh pr checks NUMBER | cat
gh pr merge NUMBER --squash --delete-branch | cat

# Releases
gh release list | cat
gh release view TAG | cat

# Workflows
gh run list | cat
gh run view RUN_ID | cat
gh run watch
```

---

## ✅ Best Practices

### DO (Recommended) ✅

**Branch Management:**
- ✅ Always create feature branches from `main`
- ✅ Use descriptive branch names with type prefix
- ✅ Delete branches after PR merge
- ✅ Keep branches up to date with base branch

**Commits:**
- ✅ Follow Conventional Commits format
- ✅ Write clear, descriptive commit messages
- ✅ Make atomic commits (one logical change per commit)
- ✅ Reference issues in commits (#NUMBER)

**Pull Requests:**
- ✅ **ALWAYS run quality checks locally BEFORE creating PR**
- ✅ Wait for CI to pass before requesting review
- ✅ Write detailed PR descriptions
- ✅ Link related issues (Closes #NUMBER)
- ✅ Squash merge to keep history clean

**Quality:**
- ✅ Run `composer phpcs`, `composer phpstan`, `composer test` BEFORE pushing
- ✅ Fix all errors before creating PR
- ✅ Update documentation when needed

### DON'T (Avoid) ❌

- ❌ Never commit directly to `main`
- ❌ Don't merge PRs with failing CI
- ❌ Don't skip local quality checks
- ❌ Don't use vague commit messages
- ❌ Don't leave branches after merge
- ❌ Don't forget `| cat` on `gh` commands

---

## 🆘 Troubleshooting

### Issue: PR CI Failing

```bash
# 1. Check what failed
gh pr checks NUMBER | cat

# 2. Fix locally
composer phpcs        # Check PHPCS
composer phpstan      # Check PHPStan
composer test         # Run tests

# 3. Push fix
git add .
git commit -m "fix: Resolve CI errors"
git push
```

### Issue: Merge Conflicts

```bash
# 1. Update your branch with main
git checkout feature/my-feature
git fetch origin
git merge origin/main

# 2. Resolve conflicts manually
# Edit conflicted files

# 3. Mark resolved and commit
git add .
git commit -m "chore: Resolve merge conflicts"

# 4. Push updated branch
git push origin feature/my-feature
```

---

**Last Updated**: January 23, 2026  
**Maintained By**: Silver Assist  
**Repository**: https://github.com/SilverAssist/contact-form-to-api
