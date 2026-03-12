#!/bin/bash

################################################################################
# Contact Form 7 to API - NPM-based Asset Minification Script
#
# Uses PostCSS + cssnano for CSS minification and Grunt + uglify
# for JavaScript minification.
#
# @package SilverAssist\ContactFormToAPI
# @since 2.4.0
# @author Silver Assist
################################################################################

set -e

# Colors.
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

main() {
    echo -e "${BLUE}[INFO]${NC} Contact Form 7 to API - NPM Asset Minification"
    echo -e "${BLUE}[INFO]${NC} Project root: $PROJECT_ROOT"

    cd "$PROJECT_ROOT" || {
        echo -e "${RED}[ERROR]${NC} Failed to change to project root directory"
        return 1
    }

    if [ ! -f "contact-form-to-api.php" ]; then
        echo -e "${RED}[ERROR]${NC} Not in plugin root directory - missing contact-form-to-api.php"
        return 1
    fi

    for required in package.json postcss.config.js Gruntfile.js; do
        if [ ! -f "$required" ]; then
            echo -e "${RED}[ERROR]${NC} $required not found"
            return 1
        fi
    done

    if ! command -v node >/dev/null 2>&1; then
        echo -e "${RED}[ERROR]${NC} Node.js is required but not installed"
        return 1
    fi

    if ! command -v npm >/dev/null 2>&1; then
        echo -e "${RED}[ERROR]${NC} npm is required but not installed"
        return 1
    fi

    echo -e "${BLUE}[INFO]${NC} Node.js version: $(node --version)"
    echo -e "${BLUE}[INFO]${NC} npm version: $(npm --version)"

    # Install dependencies if needed.
    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}[INFO]${NC} Installing npm dependencies..."
        if ! npm install; then
            echo -e "${RED}[ERROR]${NC} Failed to install npm dependencies"
            return 1
        fi
        echo -e "${GREEN}[SUCCESS]${NC} Dependencies installed"
    else
        echo -e "${BLUE}[INFO]${NC} Ensuring dependencies are up to date..."
        if ! npm install; then
            echo -e "${RED}[ERROR]${NC} Failed to update npm dependencies"
            return 1
        fi
    fi

    # Clean and build.
    echo -e "${YELLOW}[INFO]${NC} Cleaning existing minified files..."
    rm -f assets/css/*.min.css assets/js/*.min.js

    echo -e "${YELLOW}[INFO]${NC} Running build process (CSS with PostCSS + JS with Grunt)..."
    if ! npm run build; then
        echo -e "${RED}[ERROR]${NC} Build process failed"
        return 1
    fi

    # Verify minified files.
    local expected_files=(
        "assets/css/admin.min.css"
        "assets/css/dashboard-widget.min.css"
        "assets/css/request-log.min.css"
        "assets/css/settings-page.min.css"
        "assets/css/variables.min.css"
        "assets/js/admin.min.js"
        "assets/js/api-log-admin.min.js"
        "assets/js/settings-page.min.js"
        "assets/js/migration.min.js"
    )

    local missing_files=0
    echo -e "${BLUE}[INFO]${NC} Verifying minified files..."

    for file in "${expected_files[@]}"; do
        if [ -f "$file" ]; then
            local original_file="${file%.min.*}.${file##*.}"
            if [ -f "$original_file" ]; then
                local original_size
                original_size=$(wc -c < "$original_file")
                local minified_size
                minified_size=$(wc -c < "$file")
                local reduction=$(( (original_size - minified_size) * 100 / original_size ))
                echo -e "${GREEN}[SUCCESS]${NC} ✓ $(basename "$file") (${reduction}% reduction: ${original_size} → ${minified_size} bytes)"
            else
                echo -e "${GREEN}[SUCCESS]${NC} ✓ $(basename "$file") created"
            fi
        else
            echo -e "${RED}[ERROR]${NC} ✗ $(basename "$file") not found"
            ((missing_files++))
        fi
    done

    echo ""
    if [ $missing_files -eq 0 ]; then
        echo -e "${GREEN}[SUCCESS]${NC} ✨ Asset minification completed successfully!"
        echo -e "${GREEN}[SUCCESS]${NC} All ${#expected_files[@]} minified files created"
    else
        echo -e "${YELLOW}[WARNING]${NC} Asset minification completed with $missing_files missing files"
    fi

    return 0
}

case "${1:-}" in
    -h|--help)
        echo "Contact Form 7 to API - NPM Asset Minification Script"
        echo ""
        echo "Usage: $0 [options]"
        echo ""
        echo "Options:"
        echo "  -h, --help    Show this help message"
        echo ""
        echo "Available npm commands:"
        echo "  npm run build      # Complete build (clean + minify)"
        echo "  npm run minify     # Minify CSS + JS without cleaning"
        echo "  npm run minify:css # Minify only CSS files with PostCSS"
        echo "  npm run minify:js  # Minify only JS files with Grunt"
        echo "  npm run clean      # Remove all .min.css and .min.js files"
        exit 0
        ;;
    "")
        main
        ;;
    *)
        echo -e "${RED}[ERROR]${NC} Unknown option: $1"
        exit 1
        ;;
esac
