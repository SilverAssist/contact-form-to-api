# Translation Files

This directory contains translation files for the Contact Form 7 to API plugin.

Translation files will be generated automatically when the plugin is prepared for WordPress.org distribution.

## File Structure

```
languages/
├── contact-form-to-api.pot       # Template file (generated)
├── contact-form-to-api-es_ES.po  # Spanish translation
├── contact-form-to-api-es_ES.mo  # Spanish compiled
├── contact-form-to-api-fr_FR.po  # French translation  
├── contact-form-to-api-fr_FR.mo  # French compiled
└── ...                           # Other languages
```

## Contributing Translations

To contribute translations:
1. Copy `contact-form-to-api.pot` to `contact-form-to-api-[locale].po`
2. Translate all strings in the .po file
3. Compile to .mo using `msgfmt` or Poedit
4. Submit via GitHub pull request
