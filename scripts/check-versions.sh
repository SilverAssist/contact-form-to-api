#!/bin/bash

###############################################################################
# Contact Form 7 to API - Version Consistency Checker
#
# Verifies that all @version tags across the plugin are consistent
# and match the main plugin version
#
# Usage: ./scripts/check-versions.sh
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

print_status "Contact Form 7 to API - Version Consistency Check"
print_status "Project root: ${PROJECT_ROOT}"

# Check if we're in the right directory
if [ ! -f "${PROJECT_ROOT}/contact-form-to-api.php" ]; then
    print_error "Main plugin file not found. Make sure you're running this from the project root."
    exit 1
fi

# Get the main plugin version (the authoritative version)
MAIN_VERSION=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PROJECT_ROOT}/contact-form-to-api.php" | cut -d' ' -f2)

if [ -z "$MAIN_VERSION" ]; then
    print_error "Could not detect main plugin version from contact-form-to-api.php"
    exit 1
fi

print_status "Main plugin version: ${MAIN_VERSION}"
echo ""

# Initialize counters
TOTAL_FILES=0
CONSISTENT_FILES=0
INCONSISTENT_FILES=0

# Function to check version in file (search only in header - first 20 lines)
check_file_version() {
    local file="$1"
    local file_type="$2"
    
    if [ ! -f "$file" ]; then
        return
    fi
    
    # Search for @version in the first 20 lines only (header section)
    local version=$(head -20 "$file" | grep -o "@version [0-9]\+\.[0-9]\+\.[0-9]\+" | cut -d' ' -f2 | head -1)
    
    if [ -n "$version" ]; then
        TOTAL_FILES=$((TOTAL_FILES + 1))
        local filename=$(basename "$file")
        
        if [ "$version" = "$MAIN_VERSION" ]; then
            print_success "✓ $file_type: $filename ($version)"
            CONSISTENT_FILES=$((CONSISTENT_FILES + 1))
        else
            print_error "✗ $file_type: $filename ($version) - Expected: $MAIN_VERSION"
            INCONSISTENT_FILES=$((INCONSISTENT_FILES + 1))
        fi
    fi
}

# 1. Check main plugin file constant
print_status "Checking main plugin file..."

# Check the constant definition
CONSTANT_VERSION=$(grep "define('CONTACT_FORM_TO_API_VERSION'" "${PROJECT_ROOT}/contact-form-to-api.php" | grep -o "[0-9]\+\.[0-9]\+\.[0-9]\+" | head -1)

if [ -n "$CONSTANT_VERSION" ]; then
    TOTAL_FILES=$((TOTAL_FILES + 1))
    if [ "$CONSTANT_VERSION" = "$MAIN_VERSION" ]; then
        print_success "✓ Plugin constant: CONTACT_FORM_TO_API_VERSION ($CONSTANT_VERSION)"
        CONSISTENT_FILES=$((CONSISTENT_FILES + 1))
    else
        print_error "✗ Plugin constant: CONTACT_FORM_TO_API_VERSION ($CONSTANT_VERSION) - Expected: $MAIN_VERSION"
        INCONSISTENT_FILES=$((INCONSISTENT_FILES + 1))
    fi
fi

# Check @version tag in main file
check_file_version "${PROJECT_ROOT}/contact-form-to-api.php" "Main file @version"

echo ""

# 2. Check PHP files
print_status "Checking PHP source files..."

if [ -d "${PROJECT_ROOT}/src" ]; then
    find "${PROJECT_ROOT}/src" -name "*.php" -type f | while read -r php_file; do
        check_file_version "$php_file" "PHP file"
    done
else
    print_warning "Source directory (src/) not found"
fi

echo ""

# 3. Check CSS files
print_status "Checking CSS files..."

if [ -d "${PROJECT_ROOT}/assets/css" ]; then
    css_found=false
    for css_file in "${PROJECT_ROOT}/assets/css"/*.css; do
        if [ -f "$css_file" ]; then
            css_found=true
            check_file_version "$css_file" "CSS file"
        fi
    done
    
    if [ "$css_found" = false ]; then
        print_status "No CSS files found"
    fi
else
    print_warning "CSS directory (assets/css/) not found"
fi

echo ""

# 4. Check JavaScript files
print_status "Checking JavaScript files..."

if [ -d "${PROJECT_ROOT}/assets/js" ]; then
    js_found=false
    for js_file in "${PROJECT_ROOT}/assets/js"/*.js; do
        if [ -f "$js_file" ]; then
            js_found=true
            check_file_version "$js_file" "JavaScript file"
        fi
    done
    
    if [ "$js_found" = false ]; then
        print_status "No JavaScript files found"
    fi
else
    print_warning "JavaScript directory (assets/js/) not found"
fi

echo ""

# 5. Check script files
print_status "Checking script files..."

if [ -d "${PROJECT_ROOT}/scripts" ]; then
    script_found=false
    for script_file in "${PROJECT_ROOT}/scripts"/*.sh; do
        if [ -f "$script_file" ]; then
            script_found=true
            check_file_version "$script_file" "Script file"
        fi
    done
    
    if [ "$script_found" = false ]; then
        print_status "No script files found"
    fi
else
    print_warning "Scripts directory not found"
fi

echo ""

# 6. Check HEADER-STANDARDS.md for version references
print_status "Checking documentation files..."

if [ -f "${PROJECT_ROOT}/HEADER-STANDARDS.md" ]; then
    # Check for version references in HEADER-STANDARDS.md (first 50 lines to catch the main version reference)
    DOC_VERSION=$(head -50 "${PROJECT_ROOT}/HEADER-STANDARDS.md" | grep "Current project version:" | grep -o "[0-9]\+\.[0-9]\+\.[0-9]\+" | head -1)
    
    if [ -n "$DOC_VERSION" ]; then
        TOTAL_FILES=$((TOTAL_FILES + 1))
        if [ "$DOC_VERSION" = "$MAIN_VERSION" ]; then
            print_success "✓ Documentation: HEADER-STANDARDS.md ($DOC_VERSION)"
            CONSISTENT_FILES=$((CONSISTENT_FILES + 1))
        else
            print_error "✗ Documentation: HEADER-STANDARDS.md ($DOC_VERSION) - Expected: $MAIN_VERSION"
            INCONSISTENT_FILES=$((INCONSISTENT_FILES + 1))
        fi
    else
        print_warning "No version reference found in HEADER-STANDARDS.md"
    fi
else
    print_warning "HEADER-STANDARDS.md not found"
fi

# Check README.md for version references
if [ -f "${PROJECT_ROOT}/README.md" ]; then
    README_VERSIONS=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PROJECT_ROOT}/README.md" | cut -d' ' -f2)
    
    if [ -n "$README_VERSIONS" ]; then
        # Count unique versions in README
        UNIQUE_README_VERSIONS=$(echo "$README_VERSIONS" | sort -u)
        VERSION_COUNT=$(echo "$UNIQUE_README_VERSIONS" | wc -l | tr -d ' ')
        
        if [ "$VERSION_COUNT" -eq 1 ] && [ "$UNIQUE_README_VERSIONS" = "$MAIN_VERSION" ]; then
            print_success "✓ Documentation: README.md (all version references match $MAIN_VERSION)"
        else
            print_warning "Multiple or inconsistent version references found in README.md:"
            echo "$UNIQUE_README_VERSIONS" | while read -r version; do
                if [ "$version" = "$MAIN_VERSION" ]; then
                    print_status "  - $version (correct)"
                else
                    print_warning "  - $version (should be $MAIN_VERSION)"
                fi
            done
        fi
    else
        print_status "No version references found in README.md"
    fi
else
    print_warning "README.md not found"
fi

echo ""
echo "=================================================="
echo "VERSION CONSISTENCY REPORT"
echo "=================================================="
echo "Main plugin version: $MAIN_VERSION"
echo "Total files checked: $TOTAL_FILES"
echo -e "Consistent files: ${GREEN}$CONSISTENT_FILES${NC}"

if [ $INCONSISTENT_FILES -gt 0 ]; then
    echo -e "Inconsistent files: ${RED}$INCONSISTENT_FILES${NC}"
    echo ""
    print_error "❌ VERSION CONSISTENCY CHECK FAILED"
    echo ""
    print_status "To fix version inconsistencies, run:"
    echo "  ./scripts/update-version-simple.sh $MAIN_VERSION"
    echo ""
    exit 1
else
    echo -e "Inconsistent files: ${GREEN}0${NC}"
    echo ""
    print_success "✅ ALL VERSIONS ARE CONSISTENT"
    echo ""
    print_status "All $TOTAL_FILES files have consistent version numbers ($MAIN_VERSION)"
    echo ""
fi

# Additional checks and recommendations
echo "=================================================="
echo "ADDITIONAL INFORMATION"
echo "=================================================="

# Check if composer.json version matches
if [ -f "${PROJECT_ROOT}/composer.json" ]; then
    COMPOSER_VERSION=$(grep '"version"' "${PROJECT_ROOT}/composer.json" | grep -o "[0-9]\+\.[0-9]\+\.[0-9]\+" | head -1)
    if [ -n "$COMPOSER_VERSION" ]; then
        if [ "$COMPOSER_VERSION" = "$MAIN_VERSION" ]; then
            print_success "Composer.json version matches: $COMPOSER_VERSION"
        else
            print_warning "Composer.json version ($COMPOSER_VERSION) differs from plugin version ($MAIN_VERSION)"
            print_status "Consider updating composer.json manually if needed"
        fi
    fi
fi

# Check for git tags
if git rev-parse --git-dir > /dev/null 2>&1; then
    LATEST_TAG=$(git describe --tags --abbrev=0 2>/dev/null)
    if [ -n "$LATEST_TAG" ]; then
        TAG_VERSION=$(echo "$LATEST_TAG" | sed 's/^v//')
        if [ "$TAG_VERSION" = "$MAIN_VERSION" ]; then
            print_success "Latest git tag matches: $LATEST_TAG"
        else
            print_status "Latest git tag: $LATEST_TAG (plugin version: $MAIN_VERSION)"
            if [ "$TAG_VERSION" != "$MAIN_VERSION" ]; then
                print_status "Consider creating a new tag: git tag v$MAIN_VERSION"
            fi
        fi
    else
        print_status "No git tags found. Consider creating: git tag v$MAIN_VERSION"
    fi
fi

echo ""
print_status "Version check completed."

exit 0
