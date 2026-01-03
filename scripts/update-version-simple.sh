#!/bin/bash

###############################################################################
# Contact Form 7 to API - Simple Version Update Script
#
# A robust version updater that handles macOS sed quirks and maintains
# consistency across all plugin files
#
# Usage: ./scripts/update-version-simple.sh <new-version> [--no-confirm]
# Example: ./scripts/update-version-simple.sh 1.0.1
#
# @package ContactFormToAPI
# @since 1.0.0
# @author Silver Assist
# @version     1.1.3
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

# Validate input
if [ $# -eq 0 ]; then
    print_error "No version specified"
    echo "Usage: $0 <new-version> [--no-confirm]"
    echo "Example: $0 1.0.3"
    echo "Example: $0 1.0.3 --no-confirm"
    exit 1
fi

# Check for help option
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Contact Form 7 to API - Version Update Script"
    echo ""
    echo "Usage: $0 <new-version> [--no-confirm] [--force]"
    echo ""
    echo "Arguments:"
    echo "  <new-version>    New version in semantic versioning format (e.g., 1.0.3)"
    echo "  --no-confirm     Skip confirmation prompts (useful for CI/CD)"
    echo "  --force          Force update all files even if version matches"
    echo ""
    echo "Examples:"
    echo "  $0 1.0.3"
    echo "  $0 1.0.3 --no-confirm"
    echo "  $0 1.0.3 --no-confirm --force"
    echo ""
    echo "This script updates version numbers across all plugin files including:"
    echo "  • Main plugin file header and constants"
    echo "  • PHP files @version tags"
    echo "  • CSS and JavaScript files"
    echo "  • Documentation files"
    echo "  • Script files"
    echo ""
    exit 0
fi

NEW_VERSION="$1"
NO_CONFIRM=false
FORCE_UPDATE=false

# Parse arguments
for arg in "${@:2}"; do
    case "$arg" in
        --no-confirm)
            NO_CONFIRM=true
            ;;
        --force)
            FORCE_UPDATE=true
            ;;
        *)
            print_error "Invalid argument: $arg"
            echo "Usage: $0 <new-version> [--no-confirm] [--force]"
            exit 1
            ;;
    esac
done

# Validate version format
if ! [[ $NEW_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    print_error "Invalid version format. Use semantic versioning (e.g., 1.0.3)"
    exit 1
fi

# Get project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

print_status "Updating Contact Form 7 to API to version ${NEW_VERSION}"
print_status "Project root: ${PROJECT_ROOT}"

# Check if we're in the right directory
if [ ! -f "${PROJECT_ROOT}/contact-form-to-api.php" ]; then
    print_error "Main plugin file not found. Make sure you're running this from the project root."
    exit 1
fi

# Get current version
CURRENT_VERSION=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PROJECT_ROOT}/contact-form-to-api.php" | cut -d' ' -f2)

if [ -z "$CURRENT_VERSION" ]; then
    print_error "Could not detect current version"
    exit 1
fi

print_status "Current version: ${CURRENT_VERSION}"
print_status "New version: ${NEW_VERSION}"

# Check if versions are the same
if [ "$CURRENT_VERSION" = "$NEW_VERSION" ]; then
    if [ "$FORCE_UPDATE" = true ]; then
        print_status "Force update enabled - updating all files to version ${NEW_VERSION}"
    elif [ "$NO_CONFIRM" = false ]; then
        print_warning "Current version and new version are the same (${NEW_VERSION})"
        echo ""
        read -p "$(echo -e ${YELLOW}[CONFIRM]${NC} Continue anyway? [y/N]: )" -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_warning "Version update cancelled"
            exit 0
        fi
    else
        print_status "Same version detected in CI mode - exiting successfully (no changes needed)"
        print_status "Use --force to update all files even when version matches"
        exit 0
    fi
else
    # Confirm with user only if not in no-confirm mode
    if [ "$NO_CONFIRM" = false ]; then
        echo ""
        read -p "$(echo -e ${YELLOW}[CONFIRM]${NC} Update version from ${CURRENT_VERSION} to ${NEW_VERSION}? [y/N]: )" -n 1 -r
        echo ""

        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_warning "Version update cancelled"
            exit 0
        fi
    else
        print_status "Running in non-interactive mode (--no-confirm)"
    fi
fi

echo ""
print_status "Starting version update process..."

# Initialize deferred commands file (for self-modification)
rm -f "${PROJECT_ROOT}/.version_update_deferred"

# Function to update file using perl (more reliable than sed on macOS)
update_file() {
    local file="$1"
    local pattern="$2"
    local description="$3"
    
    if [ -f "$file" ]; then
        # Special handling for the script modifying itself
        local current_script="${BASH_SOURCE[0]}"
        local current_script_abs="$(cd "$(dirname "$current_script")" && pwd)/$(basename "$current_script")"
        local file_abs="$(cd "$(dirname "$file")" && pwd)/$(basename "$file")"
        
        # Check if this script is trying to modify itself
        if [ "$current_script_abs" = "$file_abs" ]; then
            print_status "  Deferring self-modification for $description"
            # Store the modification for later execution
            echo "perl -i -pe '$pattern' '$file'" >> "${PROJECT_ROOT}/.version_update_deferred"
            return 0
        fi
        
        # Create backup
        cp "$file" "$file.bak" 2>/dev/null || {
            print_warning "  Could not create backup for $description - skipping"
            return 0
        }
        
        # Apply perl substitution
        if perl -i -pe "$pattern" "$file" 2>/dev/null; then
            # Verify the change was made
            if ! cmp -s "$file" "$file.bak" 2>/dev/null; then
                print_status "  Updated $description"
                rm "$file.bak" 2>/dev/null || true
                return 0
            else
                print_warning "  No changes made to $description (pattern not found or already updated)"
                mv "$file.bak" "$file" 2>/dev/null || true
                return 0
            fi
        else
            print_warning "  Could not process $description (perl substitution failed)"
            mv "$file.bak" "$file" 2>/dev/null || true
            return 0
        fi
    else
        print_warning "  File not found: $file"
        return 0
    fi
}

# 1. Update main plugin file
print_status "Updating main plugin file..."

# Update plugin header
update_file "${PROJECT_ROOT}/contact-form-to-api.php" \
    "s/Version: [0-9]+\\.[0-9]+\\.[0-9]+/Version: ${NEW_VERSION}/g" \
    "plugin header"

# Update constant
update_file "${PROJECT_ROOT}/contact-form-to-api.php" \
    "s/define\\('CF7_API_VERSION', '[0-9]+\\.[0-9]+\\.[0-9]+'\\)/define('CF7_API_VERSION', '${NEW_VERSION}')/g" \
    "plugin constant"

# Update @version tag
update_file "${PROJECT_ROOT}/contact-form-to-api.php" \
    "s/\\@version [0-9]+\\.[0-9]+\\.[0-9]+/\\@version ${NEW_VERSION}/g" \
    "main file @version tag"

print_success "Main plugin file processing completed"

# 2. Update PHP files
print_status "Updating PHP files..."

php_update_count=0
php_dir=""

# Determine which directory contains PHP files
if [ -d "${PROJECT_ROOT}/includes" ]; then
    php_dir="${PROJECT_ROOT}/includes"
elif [ -d "${PROJECT_ROOT}/src" ]; then
    php_dir="${PROJECT_ROOT}/src"
fi

if [ -n "$php_dir" ]; then
    # Find and update ALL PHP files in the directory
    while IFS= read -r php_file; do
        if [ -f "$php_file" ]; then
            file_name=$(basename "$php_file")
            update_file "$php_file" \
                "s/\\@version [0-9]+\\.[0-9]+\\.[0-9]+/\\@version ${NEW_VERSION}/g" \
                "$file_name"
            php_update_count=$((php_update_count + 1))
        fi
    done < <(find "$php_dir" -name "*.php" -type f 2>/dev/null)
    
    if [ $php_update_count -gt 0 ]; then
        print_success "PHP files processed ($php_update_count files)"
    else
        print_status "No PHP files found in $php_dir"
    fi
else
    print_warning "No PHP source directory found (includes/ or src/)"
fi

# 3. Update CSS files  
print_status "Updating CSS files..."

css_update_count=0
if [ -d "${PROJECT_ROOT}/assets/css" ]; then
    for css_file in "${PROJECT_ROOT}/assets/css"/*.css; do
        if [ -f "$css_file" ]; then
            file_name=$(basename "$css_file")
            # Update @version tags in CSS files
            update_file "$css_file" \
                "s/\\@version [0-9]+\\.[0-9]+\\.[0-9]+/\\@version ${NEW_VERSION}/g" \
                "$file_name"
            css_update_count=$((css_update_count + 1))
        fi
    done
    if [ $css_update_count -gt 0 ]; then
        print_success "CSS files processed ($css_update_count files)"
    else
        print_status "No CSS files found"
    fi
else
    print_warning "CSS directory not found"
fi

# 4. Update JavaScript files
print_status "Updating JavaScript files..."

js_update_count=0
if [ -d "${PROJECT_ROOT}/assets/js" ]; then
    for js_file in "${PROJECT_ROOT}/assets/js"/*.js; do
        if [ -f "$js_file" ]; then
            file_name=$(basename "$js_file")
            # Update @version tags in JS files
            update_file "$js_file" \
                "s/\\@version [0-9]+\\.[0-9]+\\.[0-9]+/\\@version ${NEW_VERSION}/g" \
                "$file_name"
            js_update_count=$((js_update_count + 1))
        fi
    done
    if [ $js_update_count -gt 0 ]; then
        print_success "JavaScript files processed ($js_update_count files)"
    else
        print_status "No JavaScript files found"
    fi
else
    print_warning "JavaScript directory not found"
fi

# 5. Update version scripts
print_status "Updating version scripts..."

script_update_count=0
if [ -d "${PROJECT_ROOT}/scripts" ]; then
    for script_file in "${PROJECT_ROOT}/scripts"/*.sh; do
        if [ -f "$script_file" ]; then
            script_name=$(basename "$script_file")
            update_file "$script_file" \
                "s/\\@version [0-9]+\\.[0-9]+\\.[0-9]+/\\@version ${NEW_VERSION}/g" \
                "$script_name"
            script_update_count=$((script_update_count + 1))
        fi
    done
    if [ $script_update_count -gt 0 ]; then
        print_success "Version scripts processed ($script_update_count files)"
    else
        print_status "No script files found"
    fi
else
    print_warning "Scripts directory not found"
fi

# 6. Update README.md if it contains version references
print_status "Checking README.md for version references..."

if [ -f "${PROJECT_ROOT}/README.md" ]; then
    if grep -q "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" "${PROJECT_ROOT}/README.md" 2>/dev/null; then
        update_file "${PROJECT_ROOT}/README.md" \
            "s/Version: [0-9]+\\.[0-9]+\\.[0-9]+/Version: ${NEW_VERSION}/g" \
            "README.md version references"
        print_success "README.md processed"
    else
        print_status "No version references found in README.md"
    fi
else
    print_warning "README.md not found"
fi

echo ""
print_success "✨ Version update completed successfully!"

# Execute any deferred modifications (like self-modification)
if [ -f "${PROJECT_ROOT}/.version_update_deferred" ]; then
    print_status "Executing deferred modifications..."
    
    while IFS= read -r command; do
        if [ -n "$command" ]; then
            print_status "  Executing: $command"
            if eval "$command" 2>/dev/null; then
                print_status "  ✓ Deferred modification completed"
            else
                print_warning "  ⚠ Deferred modification failed (continuing anyway)"
            fi
        fi
    done < "${PROJECT_ROOT}/.version_update_deferred"
    
    # Clean up deferred commands file
    rm -f "${PROJECT_ROOT}/.version_update_deferred"
    
    print_success "Deferred modifications completed"
fi

echo ""
print_status "Summary of changes:"
echo "  • Main plugin file: contact-form-to-api.php"
echo "  • PHP files: includes/**/*.php"
echo "  • CSS files: assets/css/*.css"
echo "  • JavaScript files: assets/js/*.js"
echo "  • Documentation: README.md (if applicable)"
echo "  • Version scripts: scripts/*.sh"
echo ""
print_status "Next steps:"
echo "  1. Verify changes: ./scripts/check-versions.sh"
echo "  2. Review the changes: git diff"
echo "  3. Test the plugin with new version"
echo "  4. Update CHANGELOG.md with version ${NEW_VERSION} changes (REQUIRED)"
echo "  5. Commit changes: git add . && git commit -m '🔧 Update version to ${NEW_VERSION}'"
echo "  6. Create tag: git tag v${NEW_VERSION}"
echo "  7. Push changes: git push origin main && git push origin v${NEW_VERSION}"
echo ""
print_warning "Remember: This script only updates @version tags, not @since tags!"
print_warning "New files should have their @since tag set manually to the version when they were introduced."
print_warning "🚨 IMPORTANT: Don't forget to update CHANGELOG.md with v${NEW_VERSION} changes before committing!"
