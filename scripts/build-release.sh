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
    
    # Install production dependencies
    if command -v composer >/dev/null 2>&1; then
        print_status "  • Installing production dependencies with Composer..."
        
        # Save current composer.lock for restoration
        if [ -f "composer.lock" ]; then
            cp "composer.lock" "composer.lock.backup"
        fi
        
        # Install production-only dependencies
        if composer install --no-dev --optimize-autoloader --no-interaction; then
            if [ -d "${PROJECT_ROOT}/vendor" ]; then
                cp -r "${PROJECT_ROOT}/vendor" "$PACKAGE_DIR/"
                print_success "  ✓ Composer dependencies installed successfully"
            else
                print_error "Vendor directory not created after composer install"
                exit 1
            fi
            
            # Restore development dependencies for local development
            print_status "  • Restoring development dependencies..."
            if [ -f "composer.lock.backup" ]; then
                mv "composer.lock.backup" "composer.lock"
            fi
            composer install --optimize-autoloader --no-interaction >/dev/null 2>&1
        else
            print_error "Failed to install Composer dependencies"
            exit 1
        fi
    else
        print_error "Composer not found. This plugin requires Composer dependencies."
        print_error "Please install Composer: https://getcomposer.org/download/"
        exit 1
    fi
    
    # Copy composer files to package
    cp "${PROJECT_ROOT}/composer.json" "$PACKAGE_DIR/"
    
    cd "${PROJECT_ROOT}"
else
    print_error "composer.json not found"
    exit 1
fi

# Create readme.txt for WordPress.org
print_status "Generating WordPress.org readme.txt..."
cat > "${PACKAGE_DIR}/readme.txt" << EOF
=== Contact Form 7 to API ===
Contributors: silverassist
Tags: contact-form-7, api, webhook, integration, forms
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: ${VERSION}
License: Polyform-Noncommercial-1.0.0
License URI: https://github.com/SilverAssist/contact-form-to-api/blob/main/LICENSE

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
    # Remove common development files
    find "${PACKAGE_DIR}/vendor" -name "*.md" -delete
    find "${PACKAGE_DIR}/vendor" -name "*.txt" -delete
    find "${PACKAGE_DIR}/vendor" -name ".git*" -delete
    find "${PACKAGE_DIR}/vendor" -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "test" -type d -exec rm -rf {} + 2>/dev/null || true
    find "${PACKAGE_DIR}/vendor" -name "docs" -type d -exec rm -rf {} + 2>/dev/null || true
    
    # Clean up GitHub updater package - keep only essential PHP files
    UPDATER_DIR="${PACKAGE_DIR}/vendor/silverassist/wp-github-updater"
    if [ -d "$UPDATER_DIR" ]; then
        print_status "  • Cleaning GitHub updater package..."
        
        # Remove git directory
        rm -rf "$UPDATER_DIR/.git"
        rm -rf "$UPDATER_DIR/.github"
        
        # Remove development files
        rm -f "$UPDATER_DIR"/*.md 2>/dev/null || true
        rm -f "$UPDATER_DIR"/*.txt 2>/dev/null || true
        rm -f "$UPDATER_DIR"/*.xml 2>/dev/null || true
        rm -f "$UPDATER_DIR"/*.neon 2>/dev/null || true
        rm -f "$UPDATER_DIR"/.* 2>/dev/null || true
        
        # Remove examples and other non-essential directories
        rm -rf "$UPDATER_DIR/examples" 2>/dev/null || true
        rm -rf "$UPDATER_DIR/tests" 2>/dev/null || true
        rm -rf "$UPDATER_DIR/docs" 2>/dev/null || true
        
        # Keep only src/ directory and composer.json
        if [ -d "$UPDATER_DIR/src" ] && [ -f "$UPDATER_DIR/composer.json" ]; then
            print_success "    ✓ GitHub updater cleaned (kept only src/ and composer.json)"
        else
            print_warning "    ⚠ GitHub updater structure unexpected"
        fi
    fi
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

# Check if Composer autoloader exists
if [ ! -f "${PACKAGE_DIR}/vendor/autoload.php" ]; then
    print_error "Composer autoloader missing from package"
    print_error "The plugin requires 'vendor/autoload.php' to function properly"
    exit 1
fi

# Check if GitHub updater package is included
if [ ! -d "${PACKAGE_DIR}/vendor/silverassist/wp-github-updater" ]; then
    print_warning "GitHub updater package not found in vendor directory"
    print_warning "Automatic updates may not work properly"
else
    print_success "  ✓ GitHub updater package included"
fi

# Check if required directories exist
required_dirs=("src" "assets" "languages")
for dir in "${required_dirs[@]}"; do
    if [ ! -d "${PACKAGE_DIR}/${dir}" ]; then
        print_warning "Directory missing from package: ${dir}"
    else
        print_status "  ✓ ${dir}/ directory included"
    fi
done

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

# Clean up for local development (move CI/CD files)
if [ -z "$GITHUB_ACTIONS" ]; then
    print_status "Cleaning up for local development..."
    
    # Create CI folder for non-essential files
    CI_DIR="${BUILD_DIR}/ci-artifacts"
    mkdir -p "$CI_DIR"
    
    # Move release folder to CI artifacts (it's just an unpacked version)
    if [ -d "$RELEASE_DIR" ]; then
        mv "$RELEASE_DIR" "$CI_DIR/"
    fi
    
    print_success "  ✓ CI/CD artifacts moved to ci-artifacts/ folder"
    print_status "  📁 Main build folder now contains only: contact-form-to-api-${VERSION}.zip"
fi

# Summary
echo ""
echo "=================================================="
echo "BUILD COMPLETED SUCCESSFULLY"
echo "=================================================="
echo "Version: $VERSION"
echo "Build directory: $BUILD_DIR"

if [ -z "$GITHUB_ACTIONS" ]; then
    echo ""
    print_success "📦 MAIN OUTPUT:"
    echo "  🎯 Ready to use: ${BUILD_DIR}/contact-form-to-api-${VERSION}.zip"
    echo ""
    print_status "📁 CI/CD ARTIFACTS (moved to ci-artifacts/):"
    echo "  📋 Release folder (unpacked version)"
else
    echo "Package directory: $PACKAGE_DIR"
    if [ -f "$ZIP_PATH" ]; then
        echo "ZIP archive: $ZIP_PATH"
    fi
fi
echo ""
print_success "🎉 Release package ready for distribution!"
echo ""

print_status "Next steps:"
if [ -z "$GITHUB_ACTIONS" ]; then
    echo "  1. Test the package: build/contact-form-to-api-${VERSION}.zip"
    echo "  2. Install in a clean WordPress installation"
    echo "  3. Upload to WordPress.org (if applicable)"
    echo "  4. Create GitHub release (CI/CD will handle this automatically)"
else
    echo "  1. Test the package in a clean WordPress installation"
    echo "  2. Upload to WordPress.org SVN repository (if applicable)"
    echo "  3. Create GitHub release with the ZIP file"
    echo "  4. Update any distribution channels"
fi
echo ""

print_warning "Remember to test the package before distributing!"

# Final file listing
if [ -z "$GITHUB_ACTIONS" ]; then
    echo ""
    print_success "🎯 Ready to distribute: build/contact-form-to-api-${VERSION}.zip"
    
    if [ -f "${BUILD_DIR}/contact-form-to-api-${VERSION}.zip" ]; then
        ZIP_SIZE=$(du -h "${BUILD_DIR}/contact-form-to-api-${VERSION}.zip" | cut -f1)
        echo "   Size: ${ZIP_SIZE}"
    fi
else
    echo "Package contents:"
    find "${PACKAGE_DIR}" -type f | sed "s|${PACKAGE_DIR}/|  - |" | head -20
    if [ $(find "${PACKAGE_DIR}" -type f | wc -l) -gt 20 ]; then
        echo "  ... and $(($(find "${PACKAGE_DIR}" -type f | wc -l) - 20)) more files"
    fi
fi

exit 0
