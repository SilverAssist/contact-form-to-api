#!/bin/bash

###############################################################################
# Contact Form 7 to API - Pre-Push Checks
#
# Runs all quality checks locally before pushing to GitHub
# This script replicates the checks that run in GitHub Actions workflows
#
# Usage: ./scripts/pre-push-checks.sh
#
# @package ContactFormToAPI
# @since 1.0.1
# @author Silver Assist
# @version 1.0.1
###############################################################################

# Colors for output
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
BLUE="\033[0;34m"
NC="\033[0m" # No Color

# Function to print colored output
print_header() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Get project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT" || exit 1

# Track overall status
OVERALL_STATUS=0

# Header
echo -e "${GREEN}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                                                               ║"
echo "║         Contact Form 7 to API - Pre-Push Checks              ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

print_info "Running all quality checks before push to GitHub..."

###############################################################################
# 1. COMPOSER VALIDATION
###############################################################################
print_header "1️⃣  Composer Validation"

if composer validate --strict --no-check-all 2>&1; then
    print_success "Composer validation passed"
else
    print_error "Composer validation failed"
    OVERALL_STATUS=1
fi

###############################################################################
# 2. PHPCS (WordPress Coding Standards)
###############################################################################
print_header "2️⃣  PHPCS - WordPress Coding Standards"

if [ -f "vendor/bin/phpcs" ] && [ -f "phpcs.xml" ]; then
    print_info "Running PHPCS with WordPress Coding Standards..."
    
    # First show full report for visibility
    vendor/bin/phpcs --report=full || true
    
    echo ""
    print_info "Checking for errors (warnings are allowed)..."
    
    if vendor/bin/phpcs --report=summary --error-severity=1 --warning-severity=0; then
        print_success "PHPCS passed - No errors found (warnings are informational only)"
    else
        print_error "PHPCS failed - Errors found"
        OVERALL_STATUS=1
    fi
else
    print_warning "phpcs or phpcs.xml not found, skipping..."
fi

###############################################################################
# 3. PHPSTAN (Static Analysis)
###############################################################################
print_header "3️⃣  PHPStan - Static Analysis"

if [ -f "vendor/bin/phpstan" ] && [ -f "phpstan.neon" ]; then
    print_info "Running PHPStan Level 8..."
    
    if vendor/bin/phpstan analyse --memory-limit=256M; then
        print_success "PHPStan passed - Level 8, 0 errors"
    else
        print_error "PHPStan failed"
        OVERALL_STATUS=1
    fi
else
    print_warning "PHPStan not configured, skipping..."
fi

###############################################################################
# 4. VERSION CONSISTENCY CHECK
###############################################################################
print_header "4️⃣  Version Consistency Check"

if [ -f "./scripts/check-versions.sh" ]; then
    if ./scripts/check-versions.sh; then
        print_success "Version consistency check passed"
    else
        print_error "Version consistency check failed"
        OVERALL_STATUS=1
    fi
else
    print_warning "Version check script not found, skipping..."
fi

###############################################################################
# 5. SECURITY SCAN
###############################################################################
print_header "5️⃣  Security Scan"

print_info "Checking for common security issues..."

# Check for hardcoded credentials (excluding test files and comments)
print_info "Scanning for hardcoded credentials..."
SECURITY_ISSUES=$(grep -r -i "password\|secret\|key\|token" --include="*.php" . \
   | grep -v "vendor/" \
   | grep -v ".git/" \
   | grep -v "tests/" \
   | grep -v "/\*\*" \
   | grep -v "^\s*\*" \
   | grep -v "^\s*//" \
   | grep -E "(=|:)\s*['\"][^'\"]{8,}['\"]" || true)

if [ -n "$SECURITY_ISSUES" ]; then
    print_error "Potential hardcoded credentials found:"
    echo "$SECURITY_ISSUES"
    OVERALL_STATUS=1
else
    print_success "No hardcoded credentials found"
fi

# Check for SQL injection patterns (excluding test files)
print_info "Scanning for SQL injection vulnerabilities..."
SQL_ISSUES=$(grep -r -E "\\\$_(GET|POST|REQUEST|COOKIE)\[.*\].*\\\$wpdb" --include="*.php" . \
   | grep -v "vendor/" \
   | grep -v ".git/" \
   | grep -v "tests/" || true)

if [ -n "$SQL_ISSUES" ]; then
    print_error "Potential SQL injection vulnerability found:"
    echo "$SQL_ISSUES"
    OVERALL_STATUS=1
else
    print_success "No SQL injection patterns found"
fi

# Check for XSS vulnerabilities (excluding test files)
print_info "Scanning for XSS vulnerabilities..."
XSS_ISSUES=$(grep -r -E "echo.*\\\$_(GET|POST|REQUEST|COOKIE)" --include="*.php" . \
   | grep -v "vendor/" \
   | grep -v ".git/" \
   | grep -v "tests/" \
   | grep -v "esc_" || true)

if [ -n "$XSS_ISSUES" ]; then
    print_error "Potential XSS vulnerability found:"
    echo "$XSS_ISSUES"
    OVERALL_STATUS=1
else
    print_success "No XSS vulnerabilities found"
fi

if [ $OVERALL_STATUS -eq 0 ]; then
    print_success "Security scan completed successfully"
fi

###############################################################################
# 6. PHP SYNTAX CHECK
###############################################################################
print_header "6️⃣  PHP Syntax Check"

print_info "Running PHP syntax check..."
SYNTAX_ERRORS=$(find . -name "*.php" -not -path "./vendor/*" -not -path "./.git/*" -exec php -l {} \; | grep -v "No syntax errors" || true)

if [ -n "$SYNTAX_ERRORS" ]; then
    print_error "PHP syntax errors found:"
    echo "$SYNTAX_ERRORS"
    OVERALL_STATUS=1
else
    print_success "PHP syntax check passed"
fi

###############################################################################
# FINAL SUMMARY
###############################################################################
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ $OVERALL_STATUS -eq 0 ]; then
    echo -e "${GREEN}"
    echo "╔═══════════════════════════════════════════════════════════════╗"
    echo "║                                                               ║"
    echo "║              ✅  ALL PRE-PUSH CHECKS PASSED!                  ║"
    echo "║                                                               ║"
    echo "║              Safe to push to GitHub 🚀                        ║"
    echo "║                                                               ║"
    echo "╚═══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    exit 0
else
    echo -e "${RED}"
    echo "╔═══════════════════════════════════════════════════════════════╗"
    echo "║                                                               ║"
    echo "║              ❌  SOME CHECKS FAILED!                          ║"
    echo "║                                                               ║"
    echo "║              Please fix issues before pushing                 ║"
    echo "║                                                               ║"
    echo "╚═══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    exit 1
fi
