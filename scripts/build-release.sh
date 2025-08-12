#!/bin/bash

###############################################################################
# Contact Form 7 to API - Build Release Script
#
# Creates a production-ready plugin release package
#
# Usage: ./scripts/build-release.sh [version]
#
# @package ContactFormToAPI
# @since 1.0.0
# @author Silver Assist
# @version 1.0.0
###############################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="contact-form-to-api"

print_status "Contact Form 7 to API - Release Builder"
print_status "Project root: ${PROJECT_ROOT}"

# Check if we're in the right directory
if [ ! -f "${PROJECT_ROOT}/contact-form-to-api.php" ]; then
    print_error "Main plugin file not found. Make sure you're running this from the project root."
    exit 1
fi

# Get version
if [ -n "$1" ]; then
    VERSION="$1"
else
    VERSION=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PROJECT_ROOT}/contact-form-to-api.php" | cut -d' ' -f2)
fi

if [ -z "$VERSION" ]; then
    print_error "Could not detect version. Please provide version as argument."
    echo "Usage: $0 [version]"
    exit 1
fi

print_status "Building release for version: ${VERSION}"

# Create build directory
BUILD_DIR="${PROJECT_ROOT}/build"
RELEASE_DIR="${BUILD_DIR}/release"
PACKAGE_DIR="${RELEASE_DIR}/${PLUGIN_SLUG}"

print_status "Creating build directories..."
rm -rf "$BUILD_DIR"
mkdir -p "$PACKAGE_DIR"

# Copy main plugin files
print_status "Copying plugin files..."

# Main plugin file
cp "${PROJECT_ROOT}/contact-form-to-api.php" "$PACKAGE_DIR/"

# Source code
if [ -d "${PROJECT_ROOT}/src" ]; then
    cp -r "${PROJECT_ROOT}/src" "$PACKAGE_DIR/"
    print_status "  ✓ Source code copied"
fi

# Assets
if [ -d "${PROJECT_ROOT}/assets" ]; then
    cp -r "${PROJECT_ROOT}/assets" "$PACKAGE_DIR/"
    print_status "  ✓ Assets copied"
fi

# Languages
if [ -d "${PROJECT_ROOT}/languages" ]; then
    cp -r "${PROJECT_ROOT}/languages" "$PACKAGE_DIR/"
    print_status "  ✓ Language files copied"
fi

# Documentation
for doc_file in README.md CHANGELOG.md LICENSE; do
    if [ -f "${PROJECT_ROOT}/${doc_file}" ]; then
        cp "${PROJECT_ROOT}/${doc_file}" "$PACKAGE_DIR/"
        print_status "  ✓ ${doc_file} copied"
    fi
done

# Composer dependencies (production only)
print_status "Installing production dependencies..."
if [ -f "${PROJECT_ROOT}/composer.json" ]; then
    cd "${PROJECT_ROOT}"
    
    # Create temporary composer.json without dev dependencies
    TEMP_COMPOSER=$(mktemp)
    cat composer.json > "$TEMP_COMPOSER"
    
    # Install production dependencies in build directory
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null
        
        if [ -d "${PROJECT_ROOT}/vendor" ]; then
            cp -r "${PROJECT_ROOT}/vendor" "$PACKAGE_DIR/"
            print_status "  ✓ Composer dependencies installed"
            
            # Restore dev dependencies
            composer install --optimize-autoloader --no-interaction >/dev/null 2>&1
        fi
    else
        print_warning "Composer not found. Skipping dependency installation."
    fi
    
    # Copy composer files
    cp "${PROJECT_ROOT}/composer.json" "$PACKAGE_DIR/"
    
    cd "${PROJECT_ROOT}"
fi

# Create readme.txt for WordPress.org
print_status "Generating WordPress.org readme.txt..."
cat > "${PACKAGE_DIR}/readme.txt" << EOF
=== Contact Form 7 to API ===
Contributors: silverassist
Tags: contact-form-7, api, webhook, integration, forms
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: ${VERSION}
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Integrate Contact Form 7 with external APIs. Send form submissions to custom API endpoints with advanced configuration options.

== Description ==

Contact Form 7 to API seamlessly integrates your Contact Form 7 forms with external APIs, allowing you to send form submissions to custom endpoints with advanced configuration options, field mapping, and comprehensive error handling.

= Key Features =

* **Seamless CF7 Integration**: Works with any Contact Form 7 form
* **Multiple API Endpoints**: Configure different endpoints for different forms
* **Advanced Field Mapping**: Map CF7 fields to API parameters
* **HTTP Method Support**: GET, POST, PUT, PATCH, DELETE
* **Custom Headers**: Configure authentication and custom headers
* **Error Handling**: Comprehensive error logging and retry logic
* **Security**: Encrypted credential storage and input validation

= Use Cases =

* Send leads to CRM systems
* Connect to email marketing platforms
* Integrate with custom databases
* Trigger webhooks and notifications
* Connect to third-party services

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/contact-form-to-api/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Contact > API Integration to configure your first integration
4. Map your form fields to API parameters
5. Test your integration and go live!

== Frequently Asked Questions ==

= Does this work with any Contact Form 7 form? =

Yes! The plugin works with any existing Contact Form 7 form without requiring modifications.

= Can I send data to multiple APIs? =

Yes, you can configure multiple API integrations for each form with conditional logic.

= Is it secure? =

Yes, the plugin includes comprehensive security features including encrypted credential storage, input validation, and CSRF protection.

= What happens if an API call fails? =

The plugin includes retry logic and graceful degradation. Form submissions will still work even if API calls fail.

== Screenshots ==

1. Main configuration interface
2. Field mapping interface
3. API testing tools
4. Activity monitoring dashboard

== Changelog ==

= ${VERSION} =
* Initial release
* Seamless Contact Form 7 integration
* Multiple API endpoint support
* Advanced field mapping
* Comprehensive error handling
* Security features and validation

== Upgrade Notice ==

= ${VERSION} =
Initial release of Contact Form 7 to API integration plugin.
EOF

print_success "  ✓ WordPress.org readme.txt generated"

# Clean up development files
print_status "Cleaning up development files..."

# Remove development and build files
rm -rf "${PACKAGE_DIR}/.git"
rm -rf "${PACKAGE_DIR}/.github"
rm -rf "${PACKAGE_DIR}/scripts"
rm -rf "${PACKAGE_DIR}/tests"
rm -rf "${PACKAGE_DIR}/node_modules"
rm -f "${PACKAGE_DIR}/.gitignore"
rm -f "${PACKAGE_DIR}/.gitattributes"
rm -f "${PACKAGE_DIR}/composer.lock"
rm -f "${PACKAGE_DIR}/package.json"
rm -f "${PACKAGE_DIR}/package-lock.json"
rm -f "${PACKAGE_DIR}/phpunit.xml"
rm -f "${PACKAGE_DIR}/.travis.yml"
rm -f "${PACKAGE_DIR}/.circleci"
rm -f "${PACKAGE_DIR}/webpack.config.js"
rm -f "${PACKAGE_DIR}/gulpfile.js"
rm -f "${PACKAGE_DIR}/Gruntfile.js"
rm -f "${PACKAGE_DIR}/.eslintrc"
rm -f "${PACKAGE_DIR}/.editorconfig"
rm -f "${PACKAGE_DIR}/HEADER-STANDARDS.md"

# Remove vendor development files if present
if [ -d "${PACKAGE_DIR}/vendor" ]; then
    find "${PACKAGE_DIR}/vendor" -name "*.md" -delete
    find "${PACKAGE_DIR}/vendor" -name "*.txt" -delete
    find "${PACKAGE_DIR}/vendor" -name ".git*" -delete
    find "${PACKAGE_DIR}/vendor" -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "test" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "docs" -type d -exec rm -rf {} + 2>/dev/null || true
fi

print_status "  ✓ Development files removed"

# Validate the package
print_status "Validating package..."

# Check if main plugin file exists
if [ ! -f "${PACKAGE_DIR}/contact-form-to-api.php" ]; then
    print_error "Main plugin file missing from package"
    exit 1
fi

# Check if version matches
PACKAGE_VERSION=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PACKAGE_DIR}/contact-form-to-api.php" | cut -d' ' -f2)
if [ "$PACKAGE_VERSION" != "$VERSION" ]; then
    print_error "Version mismatch in package: expected $VERSION, found $PACKAGE_VERSION"
    exit 1
fi

print_success "  ✓ Package validation passed"

# Create ZIP archive
print_status "Creating ZIP archive..."

cd "$RELEASE_DIR"
ZIP_FILE="${PLUGIN_SLUG}-${VERSION}.zip"

if command -v zip >/dev/null 2>&1; then
    zip -r "$ZIP_FILE" "$PLUGIN_SLUG" >/dev/null 2>&1
    
    if [ -f "$ZIP_FILE" ]; then
        ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
        print_success "  ✓ ZIP archive created: ${ZIP_FILE} (${ZIP_SIZE})"
        
        # Move ZIP to build root for easier access
        mv "$ZIP_FILE" "${BUILD_DIR}/"
        ZIP_PATH="${BUILD_DIR}/${ZIP_FILE}"
    else
        print_error "Failed to create ZIP archive"
        exit 1
    fi
else
    print_warning "ZIP command not found. Archive not created."
    ZIP_PATH="${RELEASE_DIR}/${PLUGIN_SLUG}"
fi

cd "${PROJECT_ROOT}"

# Generate checksums
if [ -f "$ZIP_PATH" ]; then
    print_status "Generating checksums..."
    
    cd "$(dirname "$ZIP_PATH")"
    ZIP_FILENAME=$(basename "$ZIP_PATH")
    
    # MD5
    if command -v md5sum >/dev/null 2>&1; then
        md5sum "$ZIP_FILENAME" > "${ZIP_FILENAME}.md5"
        print_status "  ✓ MD5 checksum generated"
    elif command -v md5 >/dev/null 2>&1; then
        md5 "$ZIP_FILENAME" > "${ZIP_FILENAME}.md5"
        print_status "  ✓ MD5 checksum generated"
    fi
    
    # SHA256
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$ZIP_FILENAME" > "${ZIP_FILENAME}.sha256"
        print_status "  ✓ SHA256 checksum generated"
    elif command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "$ZIP_FILENAME" > "${ZIP_FILENAME}.sha256"
        print_status "  ✓ SHA256 checksum generated"
    fi
    
    cd "${PROJECT_ROOT}"
fi

# Generate build info
print_status "Generating build information..."

BUILD_INFO="${BUILD_DIR}/build-info.txt"
cat > "$BUILD_INFO" << EOF
Contact Form 7 to API - Build Information
========================================

Version: ${VERSION}
Build Date: $(date -u +"%Y-%m-%d %H:%M:%S UTC")
Build Host: $(hostname)
Build User: $(whoami)
Git Commit: $(git rev-parse HEAD 2>/dev/null || echo "N/A")
Git Branch: $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "N/A")

Package Contents:
$(find "${PACKAGE_DIR}" -type f | sed "s|${PACKAGE_DIR}/||" | sort)

Package Statistics:
Files: $(find "${PACKAGE_DIR}" -type f | wc -l | tr -d ' ')
Directories: $(find "${PACKAGE_DIR}" -type d | wc -l | tr -d ' ')
Size: $(du -sh "${PACKAGE_DIR}" | cut -f1)
EOF

print_success "  ✓ Build information generated"

# Summary
echo ""
echo "=================================================="
echo "BUILD COMPLETED SUCCESSFULLY"
echo "=================================================="
echo "Version: $VERSION"
echo "Build directory: $BUILD_DIR"
echo "Package directory: $PACKAGE_DIR"

if [ -f "$ZIP_PATH" ]; then
    echo "ZIP archive: $ZIP_PATH"
fi

echo "Build info: $BUILD_INFO"
echo ""
print_success "🎉 Release package ready for distribution!"
echo ""

print_status "Next steps:"
echo "  1. Test the package in a clean WordPress installation"
echo "  2. Upload to WordPress.org SVN repository (if applicable)"
echo "  3. Create GitHub release with the ZIP file"
echo "  4. Update any distribution channels"
echo ""

print_warning "Remember to test the package before distributing!"

# Final file listing
echo "Package contents:"
find "${PACKAGE_DIR}" -type f | sed "s|${PACKAGE_DIR}/|  - |" | head -20
if [ $(find "${PACKAGE_DIR}" -type f | wc -l) -gt 20 ]; then
    echo "  ... and $(($(find "${PACKAGE_DIR}" -type f | wc -l) - 20)) more files"
fi

exit 0
