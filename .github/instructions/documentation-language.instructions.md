---
description: Documentation standards and language policy for all project files
name: Documentation & Language Policy
applyTo: "**"
---

# Documentation and Language Policy Instructions

**Applies to**: All documentation files, code comments, commit messages, and project communication  
**Last Updated**: January 23, 2026  
**Project**: Contact Form to API WordPress Plugin

---

## 🌍 Language Policy

### CRITICAL: English-Only Rule

**ALL technical project files MUST be written in ENGLISH:**

✅ **Always in English:**
- Code comments (PHP, JavaScript, CSS)
- Documentation files (`.md`)
- Commit messages
- Pull Request descriptions
- Issue descriptions
- GitHub Actions workflows (`.yml`)
- Scripts (`.sh`, `.php`, etc.)
- README files
- API documentation
- Code variable/function names
- Configuration files comments

❌ **Exception - Spanish Allowed:**
- User-facing content in WordPress admin (translation files)
- Spanish strings in plugin translation files (`.pot`, `.po`)
- Content entered in WordPress admin by end users

### Rationale

**Why English for Technical Content:**

1. **International Standard**
   - English is the lingua franca of software development
   - All major frameworks and libraries use English
   - Stack Overflow, GitHub, documentation resources are in English

2. **Tool Compatibility**
   - Better integration with IDEs and development tools
   - AI assistants (like Copilot) work better with English
   - Syntax highlighting and linting tools expect English

3. **Team Collaboration**
   - Accessible to international developers
   - Easier to onboard new team members
   - Facilitates code reviews and collaboration

4. **Consistency**
   - Plugin codebase is fully in English
   - WordPress core and standards use English
   - Industry best practices expect English

5. **Maintainability**
   - Easier to search for issues/solutions online
   - Better documentation resources available
   - Consistent with open-source practices

---

## 📝 Documentation Standards

### File Naming Conventions

```
README.md                   # Project overview (always uppercase)
CHANGELOG.md               # Version history (always uppercase)
CONTRIBUTING.md            # Contribution guidelines
LICENSE.md                 # License information
{specific-topic}.md        # Topic-specific docs (lowercase with hyphens)
```

**Rules:**
- Major docs: UPPERCASE.md (README, CHANGELOG, LICENSE)
- Specific guides: lowercase-with-hyphens.md
- Always use `.md` extension
- No spaces in filenames

### Document Structure

**Every documentation file should include:**

```markdown
# Document Title

**Purpose**: Brief description of what this document covers  
**Last Updated**: YYYY-MM-DD  
**Audience**: Who should read this (developers, users, admins, etc.)

---

## Table of Contents

1. [Section 1](#section-1)
2. [Section 2](#section-2)

---

## Section 1

Content...

---

## Additional Resources

- Related documentation links
- External references
```

### Markdown Best Practices

**Headers:**
```markdown
# H1 - Document Title (only one per file)
## H2 - Main Sections
### H3 - Subsections
#### H4 - Sub-subsections (use sparingly)
```

**Emphasis:**
```markdown
**Bold** for important terms and emphasis
*Italic* for subtle emphasis (use sparingly)
`code` for inline code, commands, file paths
```

**Code Blocks:**
````markdown
```php
// Always specify language for syntax highlighting
function example() {
    return true;
}
```

```bash
# Use bash for terminal commands
composer install
vendor/bin/phpcs
```
````

**Alerts/Callouts:**
```markdown
**⚠️ WARNING**: Important warning message

**✅ TIP**: Helpful tip or best practice

**❌ AVOID**: Things to avoid

**🔥 CRITICAL**: Critical information

**💡 NOTE**: Additional information
```

---

## ✍️ Writing Style Guide

### Tone and Voice

- **Clear and Concise**: Get to the point quickly
- **Professional**: Maintain technical accuracy
- **Helpful**: Anticipate questions and provide context
- **Direct**: Use active voice ("Run the command" not "The command should be run")
- **Consistent**: Use the same terminology throughout

### Technical Writing Rules

**DO ✅:**
- Use present tense ("The function returns..." not "The function will return...")
- Use second person for instructions ("You can run..." not "One can run...")
- Define acronyms on first use (CF7 = Contact Form 7)
- Include examples for complex concepts
- Use numbered lists for sequential steps
- Use bullet lists for non-sequential items
- Add code examples with comments

**DON'T ❌:**
- Use jargon without explanation
- Assume reader knowledge
- Mix tenses inconsistently
- Use passive voice excessively
- Create walls of text without formatting
- Skip prerequisites or setup steps

---

## 💬 Commit Message Standards

### Conventional Commits Format

**Required format:**
```
type(scope): brief description

Detailed explanation of changes (optional)

- Bullet point 1
- Bullet point 2

Closes #issue-number (if applicable)
```

### Commit Types

| Type | Usage | Example |
|------|-------|---------|
| `feat` | New features | `feat: Add search by sender name` |
| `fix` | Bug fixes | `fix: Resolve N+1 query in log table` |
| `docs` | Documentation only | `docs: Update README with new features` |
| `style` | Code style (formatting, no logic change) | `style: Format PHP files with PHPCS` |
| `refactor` | Code refactoring | `refactor: Extract LogWriter from RequestLogger` |
| `perf` | Performance improvements | `perf: Cache resolved error IDs` |
| `test` | Add or update tests | `test: Add unit tests for sender search` |
| `build` | Build system changes | `build: Update composer dependencies` |
| `ci` | CI/CD changes | `ci: Add Copilot setup steps workflow` |
| `chore` | Maintenance tasks | `chore: Update dependencies` |
| `revert` | Revert previous commit | `revert: Revert "feat: Add feature X"` |

### Commit Message Examples

**Good ✅:**
```bash
feat: Add unresolved errors filter with resolved badge

- Add "Unresolved" tab in logs table
- Show green "Resolved" badge for errors with successful retry
- Cache resolved IDs to prevent N+1 queries
- Add count_errors_by_resolution() method

Closes #56
```

**Bad ❌:**
```bash
updates
# Too vague

Fixed bug
# No context, no issue reference

Added new feature to filter errors
# Too verbose, should be split into subject and body
```

---

## 📋 Pull Request Standards

### PR Title Format

Same as commit messages:
```
type: brief description
```

Examples:
- `feat: Add search by sender name functionality`
- `fix: Resolve SQL string quotation issue`
- `docs: Update CHANGELOG with new features`

### PR Description Template

```markdown
## Description

Brief summary of changes (1-2 sentences).

## Changes

- Specific change 1
- Specific change 2
- Specific change 3

## Testing

- [ ] PHPStan Level 8 passes
- [ ] PHPCS WordPress-Extra passes
- [ ] Unit tests pass
- [ ] Integration tests pass

## Related Issues

Closes #issue-number
Relates to #issue-number

## Checklist

- [ ] Code follows WordPress Coding Standards
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] No breaking changes (or documented)
```

---

## 🔤 Code Comments Standards

### PHP Comments

**File Headers:**
```php
<?php
/**
 * Brief file description.
 *
 * More detailed description if needed.
 *
 * @package    SilverAssist\ContactFormToAPI
 * @subpackage Core
 * @since      1.1.0
 * @version    1.3.14
 * @author     Silver Assist
 */

\defined( 'ABSPATH' ) || exit;
```

**Class Headers:**
```php
/**
 * Class description.
 *
 * Detailed explanation of what the class does.
 *
 * @since 1.1.0
 */
class ClassName {
    // Class implementation.
}
```

**Method/Function Comments:**
```php
/**
 * Brief description of what the method does.
 *
 * More detailed explanation if needed.
 *
 * @since 1.1.0
 *
 * @param string $param1 Description of parameter.
 * @param array  $param2 Description of array parameter.
 * @return bool True on success, false on failure.
 */
public function method_name( string $param1, array $param2 ): bool {
    // Implementation.
}
```

**Inline Comments:**
```php
// Single-line comment for brief explanations.

/*
 * Multi-line comment for longer explanations
 * that span multiple lines.
 */

// TODO: Future enhancement description.
// FIXME: Known issue that needs fixing.
// NOTE: Important information.
```

---

## ✅ Documentation Checklist

**Before committing documentation:**

- [ ] Written in English (except user-facing Spanish content)
- [ ] Spell-checked
- [ ] Grammar-checked
- [ ] Code examples tested and working
- [ ] Links verified (no broken links)
- [ ] Proper Markdown formatting
- [ ] Headers properly structured (H1 → H2 → H3)
- [ ] Consistent terminology used
- [ ] Acronyms defined on first use

---

**Last Updated**: January 23, 2026  
**Maintained By**: Silver Assist  
**Repository**: https://github.com/SilverAssist/contact-form-to-api
