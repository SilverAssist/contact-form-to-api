---
name: i18n-translations
description: Generate and update translation files (.pot) for WordPress i18n. Use before creating PRs to ensure all translatable strings are captured. Required workflow before any PR submission.
---

# i18n Translation Management Skill

## When to Use
- **MANDATORY before every PR** - to capture new/changed strings
- Adding new user-facing strings
- Modifying existing translatable text
- Before releases to ensure translation files are up-to-date

## ⚠️ CRITICAL: Update .pot Before Every PR

**WHY**: If you don't regenerate the `.pot` file before a PR:
- New strings won't be available for translators
- Changed strings will have stale translations
- The translation file will become out of sync with the code

## Generate Translation Template

### Using WP-CLI (Recommended)
```bash
# From plugin root directory
wp i18n make-pot . languages/contact-form-to-api.pot --domain=contact-form-to-api
```

### Options for make-pot
```bash
# Include specific headers
wp i18n make-pot . languages/contact-form-to-api.pot \
  --domain=contact-form-to-api \
  --package-name="CF7 to API" \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/SilverAssist/contact-form-to-api/issues"}'

# Exclude vendor and node_modules (usually automatic)
wp i18n make-pot . languages/contact-form-to-api.pot \
  --domain=contact-form-to-api \
  --exclude=vendor,node_modules,.git
```

## Pre-PR Checklist

Before creating any PR, run these commands:

```bash
# 1. Regenerate .pot file
wp i18n make-pot . languages/contact-form-to-api.pot --domain=contact-form-to-api

# 2. Check if .pot was modified
git status languages/

# 3. If modified, commit the changes
git add languages/contact-form-to-api.pot
git commit -m "chore: update translation template"
```

## Translatable String Rules

### Text Domain
Always use the literal string `'contact-form-to-api'`:
```php
// ✅ CORRECT - Literal string (extractable)
__('Text here', 'contact-form-to-api');

// ❌ WRONG - Variable (NOT extractable)
__('Text here', $text_domain);
__('Text here', CONSTANT);
```

### Functions That Require Translation
```php
// Basic translation
__('Text', 'contact-form-to-api');
_e('Text', 'contact-form-to-api');

// With escaping (preferred for output)
esc_html__('Text', 'contact-form-to-api');
esc_html_e('Text', 'contact-form-to-api');
esc_attr__('Text', 'contact-form-to-api');
esc_attr_e('Text', 'contact-form-to-api');

// Plurals
_n('One item', '%d items', $count, 'contact-form-to-api');
_n_noop('One item', '%d items', 'contact-form-to-api');

// Context for ambiguous strings
_x('Post', 'noun', 'contact-form-to-api');
_x('Post', 'verb', 'contact-form-to-api');
```

### Placeholder Comments (MANDATORY for sprintf)
```php
// ✅ CORRECT - Translator comment explains placeholders
sprintf(
    /* translators: %1$s: form name, %2$d: submission count */
    __('Form "%1$s" has %2$d submissions', 'contact-form-to-api'),
    $form_name,
    $count
);

// ❌ WRONG - No translator comment
sprintf(__('Form "%1$s" has %2$d submissions', 'contact-form-to-api'), $form_name, $count);
```

## File Structure
```
languages/
├── contact-form-to-api.pot       # Template (source of truth)
├── contact-form-to-api-es_ES.po  # Spanish translation
├── contact-form-to-api-es_ES.mo  # Compiled Spanish
└── README.md                     # Translation guide
```

## Common Issues

### "String not appearing in .pot"
1. Check text domain is literal string, not variable
2. Ensure file is not in excluded directory (vendor, node_modules)
3. Verify function is a recognized i18n function

### "WP-CLI not installed"
```bash
# Install WP-CLI globally (macOS)
brew install wp-cli

# Or download directly
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

## Integration with PR Workflow

Add this to your PR preparation routine:
1. Run quality checks (`./scripts/run-quality-checks.sh`)
2. **Generate translations** (`wp i18n make-pot ...`)
3. Commit any changes
4. Push and create PR
